#!/usr/bin/env python3
# =================================================================
#.SYNOPSIS
#    Script de monitorizacion.
#.DESCRIPTION
#    Script que monitoriza los servicios web. 
#.EXAMPLE
#    .\monitorizacion.py
#.NOTES
#    Autor: Samuel Sáez y Samuel Ruiz
#    Fecha: 17/12/2025
#    Version: 1.0
#    Notas:
# =================================================================

import requests
import datetime
import os
import subprocess
import sys
import json
import argparse

# --- CONFIGURACIÓN POR DEFECTO ---
DEFAULT_SERVICES_CONFIG = {
    "apache2": ["http://localhost", "http://127.0.0.1"],
    "mariadb": []  
}
LOG_BASE_DIR = "/var/log/web_service_monitor"


# --- UTILIDADES ---
def get_daily_dir(base_dir):
    """Devuelve una ruta con la fecha: /base/YYYY-MM-DD/"""
    today = datetime.datetime.now().strftime("%Y-%m-%d")
    return os.path.join(base_dir, today)

def ensure_dir(path):
    """Crea directorios si no existen."""
    os.makedirs(path, exist_ok=True)

def get_log_path(daily_dir):
    """Ruta al archivo de log diario."""
    return os.path.join(daily_dir, "monitor.log")

def get_report_path(daily_dir):
    """Ruta al informe con marca de tiempo."""
    ts = datetime.datetime.now().strftime("%Y%m%d_%H%M%S")
    return os.path.join(daily_dir, f"report_{ts}.txt")

def log_message(log_path, level, message):
    """Escribe en el log y en la consola."""
    timestamp = datetime.datetime.now().strftime("%Y-%m-%d %H:%M:%S")
    entry = f"{timestamp} [{level}] {message}"
    print(entry)
    try:
        with open(log_path, "a") as f:
            f.write(entry + "\n")
    except IOError as e:
        print(f"ERROR AL ESCRIBIR LOG: {e}", file=sys.stderr)

def is_service_active(service_name):
    """Verifica si un servicio está activo (systemctl)."""
    try:
        result = subprocess.run(
            ["systemctl", "is-active", service_name],
            capture_output=True, text=True, timeout=10
        )
        return result.stdout.strip() == "active"
    except (subprocess.TimeoutExpired, FileNotFoundError, Exception):
        return False

def check_web_urls(urls, report_file, log_path):
    """Verifica disponibilidad web de una lista de URLs."""
    if not urls:
        with open(report_file, "a") as f:
            f.write("⚠️ Sin URLs para verificación web.\n")
        return True  # No hay verificación → no falla

    all_ok = True
    for url in urls:
        try:
            response = requests.get(url, timeout=5)
            if 200 <= response.status_code < 400:
                log_message(log_path, "SUCCESS", f"✅ {url} responde (Código: {response.status_code})")
                with open(report_file, "a") as f:
                    f.write(f"✅ UP: {url} (Código: {response.status_code})\n")
            else:
                log_message(log_path, "ERROR", f"❌ {url} responde con error (Código: {response.status_code})")
                with open(report_file, "a") as f:
                    f.write(f"❌ DOWN: {url} (Código: {response.status_code})\n")
                all_ok = False
        except requests.RequestException as e:
            log_message(log_path, "CRITICAL", f"🚨 {url} no responde: {e.__class__.__name__}")
            with open(report_file, "a") as f:
                f.write(f"🚨 CRITICAL: {url} (Error: {e.__class__.__name__})\n")
            all_ok = False
    return all_ok

def control_service(action, service_name, report_file, log_path):
    """Inicia o detiene un servicio con systemctl + sudo."""
    log_message(log_path, "INFO", f"	Intentando '{action}' el servicio '{service_name}'...")
    try:
        subprocess.run(
            ["sudo", "systemctl", action, service_name],
            check=True, capture_output=True, text=True, timeout=30
        )
        msg = f"✅ Servicio '{service_name}' {action}ed correctamente."
        log_message(log_path, "SUCCESS", msg)
        with open(report_file, "a") as f:
            f.write(f"➡️ ACCIÓN: {msg}\n")
    except (subprocess.CalledProcessError, subprocess.TimeoutExpired) as e:
        msg = f"🚨 Falló al {action} '{service_name}': {str(e)}"
        log_message(log_path, "CRITICAL", msg)
        with open(report_file, "a") as f:
            f.write(f"🚨 FALLO: {msg}\n")
    except FileNotFoundError:
        msg = "sudo o systemctl no encontrado."
        log_message(log_path, "CRITICAL", msg)
        with open(report_file, "a") as f:
            f.write(f"🚨 ERROR: {msg}\n")

def monitor_services(services_config, daily_dir):
    """Monitoriza múltiples servicios y genera informe + log."""
    log_path = get_log_path(daily_dir)
    report_path = get_report_path(daily_dir)

    ensure_dir(daily_dir)
    log_message(log_path, "INFO", "=== INICIO DE MONITORIZACIÓN ===")

    # Inicializar informe
    with open(report_path, "w") as f:
        f.write(f"INFORME DE MONITORIZACIÓN - {datetime.datetime.now()}\n")
        f.write("=" * 50 + "\n\n")

    global_ok = True

    for service, urls in services_config.items():
        log_message(log_path, "INFO", f"Monitorizando servicio: {service}")
        with open(report_path, "a") as f:
            f.write(f"\n➡️ SERVICIO: {service}\n")

        # 1. Estado del servicio
        active = is_service_active(service)
        status_str = "✅ ACTIVO" if active else "❌ INACTIVO"
        log_message(log_path, "INFO", f"  Estado: {status_str}")
        with open(report_path, "a") as f:
            f.write(f"  Estado del servicio: {status_str}\n")

        # 2. Verificación web (si aplica)
        web_ok = check_web_urls(urls, report_path, log_path)

        service_ok = active and web_ok
        if not service_ok:
            global_ok = False

    # Resumen final
    summary = "✅ TODO OK" if global_ok else "⚠️ PROBLEMAS DETECTADOS"
    with open(report_path, "a") as f:
        f.write("\n" + "=" * 50 + "\n")
        f.write(f"RESUMEN GENERAL: {summary}\n")
        f.write(f"Log: {log_path}\n")
        f.write(f"Informe: {report_path}\n")

    log_message(log_path, "INFO", f"=== FIN DE MONITORIZACIÓN ({summary}) ===")
    return report_path, log_path

# --- MENÚ MANUAL ---
def clear_screen():
    os.system("cls" if os.name == "nt" else "clear")

def show_menu(services_config, daily_dir):
    service_list = list(services_config.keys())

    while True:
        clear_screen()
        print("==========================================")
        print("   🌐 MONITOR DE SERVICIOS WEB (MANUAL) 🌐")
        print("==========================================")
        print("1) 🔍 Monitorizar todos los servicios")
        print("2) 🔍 Monitorizar un servicio específico")
        print("3) ▶️ Iniciar un servicio")
        print("4) 🛑 Detener un servicio")
        print("5) 📂 Ver rutas de logs/informes")
        print("6) ❌ Salir")
        print("------------------------------------------")
        choice = input("Elige una opción (1-6): ").strip()

        if choice == "1":
            report, log = monitor_services(services_config, daily_dir)
            input(f"\n✅ Monitorización completada.\nInforme: {report}\nLog: {log}\nPresiona Enter.")
        elif choice == "2":
            print("\nServicios disponibles:")
            for i, svc in enumerate(service_list, 1):
                print(f"  {i}) {svc}")
            try:
                idx = int(input("Selecciona número: ").strip()) - 1
                svc = service_list[idx]
                rep, lg = monitor_services({svc: services_config[svc]}, daily_dir)
                input(f"\n✅ Monitorización de '{svc}' completada.\nInforme: {rep}\nPresiona Enter.")
            except (ValueError, IndexError):
                input("❌ Selección inválida. Presiona Enter.")
        elif choice in ("3", "4"):
            action = "start" if choice == "3" else "stop"
            print("\nServicios disponibles:")
            for i, svc in enumerate(service_list, 1):
                print(f"  {i}) {svc}")
            try:
                idx = int(input("Selecciona número: ").strip()) - 1
                svc = service_list[idx]
                log_path = get_log_path(daily_dir)
                report_path = get_report_path(daily_dir)
                ensure_dir(daily_dir)
                with open(report_path, "w") as f:
                    f.write(f"ACCIÓN: {action.upper()} - {svc} - {datetime.datetime.now()}\n")
                control_service(action, svc, report_path, log_path)
                input(f"\nResultado en: {report_path}\nPresiona Enter.")
            except (ValueError, IndexError):
                input("❌ Selección inválida. Presiona Enter.")
        elif choice == "5":
            clear_screen()
            print("Rutas actuales:")
            print(f"  Directorio diario: {daily_dir}")
            print(f"  Log: {get_log_path(daily_dir)}")
            print(f"  Último informe: {get_report_path(daily_dir)} (ejemplo)")
            input("\nPresiona Enter.")
        elif choice == "6":
            log_path = get_log_path(daily_dir)
            log_message(log_path, "INFO", "Saliendo del modo manual.")
            print("¡Hasta pronto!")
            break
        else:
            input("Opción no válida. Presiona Enter.")

# --- PUNTO DE ENTRADA ---
def main():
    # Verificar dependencias
    try:
        import requests  # ya importado, pero doble verificación
    except ImportError:
        print("❌ Instala 'requests': pip install requests", file=sys.stderr)
        sys.exit(1)

    # Parser de argumentos
    parser = argparse.ArgumentParser(description="Monitor de servicios web (manual o automático con cron).")
    parser.add_argument("--cron", action="store_true", help="Modo automatizado (sin menú).")
    parser.add_argument("--log-dir", default=LOG_BASE_DIR, help="Directorio base de logs.")
    parser.add_argument("--config", default=None, help="Ruta a archivo JSON con servicios (opcional).")
    args = parser.parse_args()

    # Cargar configuración de servicios
    if args.config and os.path.isfile(args.config):
        try:
            with open(args.config, "r") as f:
                services_config = json.load(f)
        except Exception as e:
            print(f"❌ Error al cargar config JSON: {e}", file=sys.stderr)
            sys.exit(1)
    else:
        services_config = DEFAULT_SERVICES_CONFIG



    # Crear carpeta "automatico" si se ejecuta en modo cron
    if args.cron:
        auto_dir = os.path.join(LOG_BASE_DIR, "automatico")
        print("Creando directorio automático si no existe...")
        try:
            os.makedirs(auto_dir, exist_ok=True)
        except PermissionError:
            print(f"❌ No se pudo crear {auto_dir}. Ejecuta con sudo.", file=sys.stderr)



    # Directorio dinámico (por día)
    daily_dir = get_daily_dir(args.log_dir)

    # Verificar permisos de escritura
    try:
        ensure_dir(daily_dir)
    except PermissionError:
        print(f"❌ Sin permiso para escribir en {daily_dir}. Usa sudo o cambia --log-dir.", file=sys.stderr)
        sys.exit(1)

    if args.cron:
        # Modo automatizado (cron)
        report, log = monitor_services(services_config, daily_dir)
        print(f"Modo cron: Informe generado en {report}")
    else:
        # Modo manual (menú interactivo)
        show_menu(services_config, daily_dir)

if __name__ == "__main__":
    main()
