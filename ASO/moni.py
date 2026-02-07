#!/usr/bin/env python3
# =================================================================
#.SYNOPSIS
#    Monitorización remota de máquinas y servicios vía SSH.
#.DESCRIPTION
#    Lee un archivo de máquinas, se conecta por SSH a cada una,
#    monitoriza servicios, puertos, HTTP y recursos del sistema.
#.EXAMPLE
#    ./monitor_remoto.py --hosts hosts.json
#.NOTES
#    Autor: Samuel Sáez y Samuel Ruiz (base)
#    Adaptación remota: integrado con SSH, JSON logs y multi-máquina
#    Versión: 3.0
# =================================================================

import datetime
import os
import subprocess
import sys
import json
import argparse
import requests

# --- CONFIGURACIÓN POR DEFECTO (SERVICIOS) ---
DEFAULT_SERVICES_CONFIG = {
    "apache2": {
        "urls": ["http://localhost"],
        "port": 80
    }
}

LOG_BASE_DIR = "/var/log/web_service_monitor"


# =========================
# UTILIDADES GENERALES
# =========================

def get_daily_dir(base_dir):
    today = datetime.datetime.now().strftime("%Y-%m-%d")
    return os.path.join(base_dir, today)

def ensure_dir(path):
    os.makedirs(path, exist_ok=True)

def get_log_path(daily_dir):
    return os.path.join(daily_dir, "monitor.log")

def get_report_path(daily_dir):
    ts = datetime.datetime.now().strftime("%Y%m%d_%H%M%S")
    return os.path.join(daily_dir, f"report_{ts}.txt")

def log_message(log_path, level, message):
    """Log en consola + JSON en archivo."""
    timestamp = datetime.datetime.now().strftime("%Y-%m-%d %H:%M:%S")
    print(f"{timestamp} [{level}] {message}")

    log_entry = {
        "timestamp": timestamp,
        "level": level,
        "message": message
    }

    try:
        with open(log_path, "a") as f:
            f.write(json.dumps(log_entry, ensure_ascii=False) + "\n")
    except IOError as e:
        print(f"ERROR AL ESCRIBIR LOG: {e}", file=sys.stderr)

def clear_screen():
    os.system("cls" if os.name == "nt" else "clear")


# =========================
# SSH Y MÁQUINAS
# =========================

def load_hosts(path):
    """Carga archivo JSON con máquinas."""
    try:
        with open(path, "r") as f:
            data = json.load(f)
            return data.get("servidores", [])
    except Exception as e:
        print(f"❌ Error al cargar archivo de máquinas: {e}", file=sys.stderr)
        sys.exit(1)

def ssh_exec(host, user, command, port=22, key_path="/home/ubuntu/.ssh/vockey.pem"):
    """Ejecuta un comando por SSH aceptando automáticamente el fingerprint y usando una clave"""
    try:
        result = subprocess.run(
            [
                "ssh",
                "-p", str(port),
                "-o", "StrictHostKeyChecking=no",
                "-o", "UserKnownHostsFile=/dev/null",
                "-o", f"IdentityFile={key_path}",
                "-o", "LogLevel=ERROR",  # Oculta warnings como el del fingerprint
                f"{user}@{host}",
                command
            ],
            capture_output=True,
            text=True,
            timeout=30
        )
        return result.stdout.strip(), result.stderr.strip()
    except Exception as e:
        return "", str(e)



# =========================
# MONITORIZACIÓN REMOTA
# =========================

def monitor_remote_machine(machine, services_config, base_dir):
    """Monitoriza una máquina completa vía SSH."""
    nombre = machine["nombre"]
    host = machine["host"]
    user = machine["usuario"]
    port_ssh = machine.get("puerto", 22)

    daily_dir = get_daily_dir(os.path.join(base_dir, nombre))
    ensure_dir(daily_dir)

    log_path = get_log_path(daily_dir)
    report_path = get_report_path(daily_dir)

    log_message(log_path, "INFO", f"=== MONITORIZANDO {nombre} ({host}) ===")

    with open(report_path, "w") as f:
        f.write(f"INFORME DE MONITORIZACIÓN - {nombre} - {datetime.datetime.now()}\n")
        f.write("=" * 60 + "\n\n")

    global_ok = True

    # --- CPU ---
    cpu_cmd = "top -bn1 | grep 'Cpu(s)' || echo 'CPU info no disponible'"
    cpu_out, cpu_err = ssh_exec(host, user, cpu_cmd, port_ssh)
    log_message(log_path, "INFO", f"CPU: {cpu_out}")
    with open(report_path, "a") as f:
        f.write("📊 CPU:\n")
        f.write(cpu_out + "\n\n")
    if cpu_err:
        log_message(log_path, "ERROR", f"Error CPU: {cpu_err}")
        global_ok = False

    # --- RAM ---
    ram_cmd = "free -m || echo 'RAM info no disponible'"
    ram_out, ram_err = ssh_exec(host, user, ram_cmd, port_ssh)
    log_message(log_path, "INFO", "RAM obtenida")
    with open(report_path, "a") as f:
        f.write("📊 RAM (free -m):\n")
        f.write(ram_out + "\n\n")
    if ram_err:
        log_message(log_path, "ERROR", f"Error RAM: {ram_err}")
        global_ok = False

    # --- DISCO ---
    disk_cmd = "df -h / || echo 'DISCO info no disponible'"
    disk_out, disk_err = ssh_exec(host, user, disk_cmd, port_ssh)
    log_message(log_path, "INFO", "DISCO obtenido")
    with open(report_path, "a") as f:
        f.write("📊 DISCO (/):\n")
        f.write(disk_out + "\n\n")
    if disk_err:
        log_message(log_path, "ERROR", f"Error DISCO: {disk_err}")
        global_ok = False

    # --- SERVICIOS, PUERTOS, HTTP ---
    with open(report_path, "a") as f:
        f.write("=" * 60 + "\n")
        f.write("🧩 SERVICIOS MONITORIZADOS\n\n")

    for service, data in services_config.items():
        urls = data.get("urls", [])
        port_local = data.get("port", None)

        with open(report_path, "a") as f:
            f.write(f"➡️ SERVICIO: {service}\n")

        # Estado del servicio
        svc_cmd = f"systemctl is-active {service}"
        svc_out, svc_err = ssh_exec(host, user, svc_cmd, port_ssh)
        active = svc_out.strip() == "active"
        status_str = "✅ ACTIVO" if active else "❌ INACTIVO"

        log_message(log_path, "INFO", f"{nombre} - {service}: {status_str}")
        with open(report_path, "a") as f:
            f.write(f"  Estado: {status_str}\n")

        if svc_err:
            log_message(log_path, "ERROR", f"{nombre} - Error estado servicio {service}: {svc_err}")
            with open(report_path, "a") as f:
                f.write(f"  Error estado servicio: {svc_err}\n")
            global_ok = False

        # Puerto local (en la máquina remota)
        port_ok = True
        if port_local:
            port_cmd = f"nc -z 127.0.0.1 {port_local} >/dev/null 2>&1 && echo OK || echo FAIL"
            port_out, port_err = ssh_exec(host, user, port_cmd, port_ssh)
            if "OK" in port_out:
                msg = f"  ✅ Puerto {port_local} escuchando en {nombre}"
                log_message(log_path, "SUCCESS", msg)
                with open(report_path, "a") as f:
                    f.write(msg + "\n")
            else:
                msg = f"  ❌ Puerto {port_local} NO está escuchando en {nombre}"
                log_message(log_path, "ERROR", msg)
                with open(report_path, "a") as f:
                    f.write(msg + "\n")
                port_ok = False
                global_ok = False
            if port_err:
                log_message(log_path, "ERROR", f"{nombre} - Error puerto {port_local}: {port_err}")
                with open(report_path, "a") as f:
                    f.write(f"  Error puerto: {port_err}\n")
                global_ok = False

        # HTTP (desde la máquina donde corre este script)
        http_ok = True
        for url in urls:
            try:
                start = datetime.datetime.now()
                r = requests.get(url, timeout=5)
                elapsed = (datetime.datetime.now() - start).total_seconds()
                msg = f"  HTTP {r.status_code} en {elapsed:.3f}s para {url}"
                level = "SUCCESS" if 200 <= r.status_code < 400 else "ERROR"
                log_message(log_path, level, f"{nombre} - {msg}")
                with open(report_path, "a") as f:
                    f.write(msg + "\n")
                if r.status_code >= 400:
                    http_ok = False
                    global_ok = False
            except Exception as e:
                msg = f"  ❌ Error HTTP en {url}: {e.__class__.__name__}"
                log_message(log_path, "CRITICAL", f"{nombre} - {msg}")
                with open(report_path, "a") as f:
                    f.write(msg + "\n")
                http_ok = False
                global_ok = False

        with open(report_path, "a") as f:
            f.write("\n")

        if not (active and port_ok and http_ok):
            global_ok = False

    summary = "✅ TODO OK" if global_ok else "⚠️ PROBLEMAS DETECTADOS"
    with open(report_path, "a") as f:
        f.write("=" * 60 + "\n")
        f.write(f"RESUMEN GENERAL: {summary}\n")
        f.write(f"Log: {log_path}\n")
        f.write(f"Informe: {report_path}\n")

    log_message(log_path, "INFO", f"{nombre} - FIN MONITORIZACIÓN ({summary})")
    return report_path, log_path


# =========================
# CONTROL REMOTO DE SERVICIOS
# =========================

def remote_control_service(machine, service_name, action, base_dir):
    """Inicia o detiene un servicio en una máquina remota."""
    nombre = machine["nombre"]
    host = machine["host"]
    user = machine["usuario"]
    port_ssh = machine.get("puerto", 22)

    daily_dir = get_daily_dir(os.path.join(base_dir, nombre))
    ensure_dir(daily_dir)

    log_path = get_log_path(daily_dir)
    report_path = get_report_path(daily_dir)

    log_message(log_path, "INFO", f"{nombre} - Intentando '{action}' en servicio '{service_name}'")

    cmd = f"sudo systemctl {action} {service_name}"
    out, err = ssh_exec(host, user, cmd, port_ssh)

    with open(report_path, "w") as f:
        f.write(f"ACCIÓN: {action.upper()} - {service_name} - {datetime.datetime.now()}\n")
        f.write("=" * 60 + "\n")
        f.write(f"Máquina: {nombre} ({host})\n\n")
        f.write("Salida:\n")
        f.write(out + "\n\n")
        if err:
            f.write("Errores:\n")
            f.write(err + "\n")

    if err:
        log_message(log_path, "CRITICAL", f"{nombre} - Error al {action} {service_name}: {err}")
    else:
        log_message(log_path, "SUCCESS", f"{nombre} - Servicio '{service_name}' {action} ejecutado correctamente")

    return report_path, log_path


# =========================
# MENÚ INTERACTIVO
# =========================

def show_menu_remote(hosts, services_config, base_dir):
    while True:
        clear_screen()
        print("==========================================")
        print("   🌐 MONITOR REMOTO DE MÁQUINAS 🌐")
        print("==========================================")
        print("1) 🔍 Monitorizar TODAS las máquinas")
        print("2) 🔍 Monitorizar UNA máquina específica")
        print("3) ▶️ Iniciar un servicio en una máquina")
        print("4) 🛑 Detener un servicio en una máquina")
        print("5) 📂 Ver rutas de logs/informes")
        print("6) ❌ Salir")
        print("------------------------------------------")

        choice = input("Elige una opción: ").strip()

        # 1) Monitorizar todas
        if choice == "1":
            for m in hosts:
                monitor_remote_machine(m, services_config, base_dir)
            input("\n✅ Monitorización de todas las máquinas completada.\nPulsa Enter para continuar.")

        # 2) Monitorizar una
        elif choice == "2":
            print("\nMáquinas disponibles:")
            for i, m in enumerate(hosts, 1):
                print(f"  {i}) {m['nombre']} ({m['host']})")

            try:
                idx = int(input("Selecciona número: ").strip()) - 1
                machine = hosts[idx]
                monitor_remote_machine(machine, services_config, base_dir)
                input("\n✅ Monitorización completada.\nPulsa Enter.")
            except Exception:
                input("❌ Selección inválida. Pulsa Enter.")

        # 3) Iniciar servicio
        elif choice == "3":
            print("\nMáquinas disponibles:")
            for i, m in enumerate(hosts, 1):
                print(f"  {i}) {m['nombre']} ({m['host']})")

            try:
                idx = int(input("Selecciona máquina: ").strip()) - 1
                machine = hosts[idx]

                print("\nServicios disponibles:")
                svc_list = list(services_config.keys())
                for i, svc in enumerate(svc_list, 1):
                    print(f"  {i}) {svc}")

                svc_idx = int(input("Selecciona servicio: ").strip()) - 1
                svc_name = svc_list[svc_idx]

                report, log = remote_control_service(machine, svc_name, "start", base_dir)
                input(f"\n✅ Acción completada.\nInforme: {report}\nLog: {log}\nPulsa Enter.")
            except Exception:
                input("❌ Selección inválida. Pulsa Enter.")

        # 4) Detener servicio
        elif choice == "4":
            print("\nMáquinas disponibles:")
            for i, m in enumerate(hosts, 1):
                print(f"  {i}) {m['nombre']} ({m['host']})")

            try:
                idx = int(input("Selecciona máquina: ").strip()) - 1
                machine = hosts[idx]

                print("\nServicios disponibles:")
                svc_list = list(services_config.keys())
                for i, svc in enumerate(svc_list, 1):
                    print(f"  {i}) {svc}")

                svc_idx = int(input("Selecciona servicio: ").strip()) - 1
                svc_name = svc_list[svc_idx]

                report, log = remote_control_service(machine, svc_name, "stop", base_dir)
                input(f"\n✅ Acción completada.\nInforme: {report}\nLog: {log}\nPulsa Enter.")
            except Exception:
                input("❌ Selección inválida. Pulsa Enter.")

        # 5) Ver rutas
        elif choice == "5":
            clear_screen()
            print("Rutas base:")
            print(f"  Directorio base de logs/informes: {base_dir}")
            print("  Cada máquina tendrá su subcarpeta dentro de este directorio.")
            input("\nPulsa Enter para continuar.")

        # 6) Salir
        elif choice == "6":
            print("Saliendo del monitor remoto...")
            break

        else:
            input("❌ Opción no válida. Pulsa Enter.")


# =========================
# PUNTO DE ENTRADA
# =========================

def main():
    # Verificar dependencia requests
    try:
        import requests  # noqa
    except ImportError:
        print("❌ Instala 'requests': pip install requests", file=sys.stderr)
        sys.exit(1)

    parser = argparse.ArgumentParser(description="Monitor remoto de máquinas vía SSH.")
    parser.add_argument("--cron", action="store_true", help="Modo automatizado (monitoriza todas las máquinas sin menú).")
    parser.add_argument("--log-dir", default=LOG_BASE_DIR, help="Directorio base de logs/informes.")
    parser.add_argument("--config-services", default=None, help="Ruta a archivo JSON con configuración de servicios (opcional).")
    args = parser.parse_args()

    # === CARGA AUTOMÁTICA DE ARCHIVOS JSON ===

    script_dir = os.path.dirname(os.path.abspath(__file__))

    # Archivo de hosts
    hosts_file = os.path.join(script_dir, "hosts.json")
    if not os.path.isfile(hosts_file):
        print(f"❌ No se encontró el archivo hosts.json en: {hosts_file}")
        print("Crea un archivo hosts.json en el mismo directorio que el script.")
        sys.exit(1)

    hosts = load_hosts(hosts_file)

    # Archivo de servicios (opcional)
    services_file = os.path.join(script_dir, "services.json")
    if os.path.isfile(services_file):
        try:
            with open(services_file, "r") as f:
                services_config = json.load(f)
            print(f"✔ Configuración de servicios cargada desde {services_file}")
        except Exception as e:
            print(f"❌ Error al cargar services.json: {e}")
            print("Usando configuración de servicios por defecto.")
            services_config = DEFAULT_SERVICES_CONFIG
    else:
        print("ℹ No se encontró services.json, usando configuración por defecto.")
        services_config = DEFAULT_SERVICES_CONFIG
        

    # Verificar directorio base
    try:
        ensure_dir(args.log_dir)
    except PermissionError:
        print(f"❌ Sin permiso para escribir en {args.log_dir}. Usa sudo o cambia --log-dir.", file=sys.stderr)
        sys.exit(1)

    # Modo cron: monitorizar todas las máquinas sin menú
    if args.cron:
        for m in hosts:
            monitor_remote_machine(m, services_config, args.log_dir)
        print("Modo cron: monitorización de todas las máquinas completada.")
    else:
        show_menu_remote(hosts, services_config, args.log_dir)


if __name__ == "__main__":
    main()
