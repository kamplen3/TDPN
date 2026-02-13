<?php

class PBE_Upgrade {
	function __construct() {
		$this->check_uprade_db();
	}

	function check_uprade_db() {
		require_once PBE_DIR . 'inc/class-install.php';
		$version = get_option( 'pbe_db_version' );
		$current_version = PBE_Install::$version;
		if ( ! $version || version_compare( $version, $current_version, '<' ) ) {
			PBE_Install::upgrade();
		}
	}
}

if ( is_admin() ) {
	new PBE_Upgrade();
}
