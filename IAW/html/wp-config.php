<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the website, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'wordpress' );

/** Database username */
define( 'DB_USER', 'administrador' );

/** Database password */
define( 'DB_PASSWORD', 'Usuario1' );

/** Database hostname */
define( 'DB_HOST', 'wordpres.cynidlv4stje.us-east-1.rds.amazonaws.com' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8mb4' );

/** The database collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication unique keys and salts.
 *
 * Change these to different unique phrases! You can generate these using
 * the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}.
 *
 * You can change these at any point in time to invalidate all existing cookies.
 * This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',         'I W{(Z4[OxQ{(C?p;iN3:7lrwMg/c7goTx/5RS`!VdX]~]rwFcB0Wr}R2em6C-3r' );
define( 'SECURE_AUTH_KEY',  'gpH :^ZcJB>1SwTVrx8O^ KG{%{=^urk<`0H<AdFG@<J~Jjg7yek29k5Afa;oV>x' );
define( 'LOGGED_IN_KEY',    'E1rlp(H^,CrG?sB={|NX/nTB?e,_/JO`/ODH6Kw/s-gC~p&<ltP*=FYbcy_I#A@F' );
define( 'NONCE_KEY',        'T`MNF_cj$8y_8}u-jEpOP(uA8QC+re]@:FtkD5*gg[[;f*P~ESMfysKGZ5pp>.H@' );
define( 'AUTH_SALT',        'X[p-0ra0qLu(|~<#!IpYU6PJN@XDNatVZ3*N%+zhaXIDdkZLowAJS26{9ubZSK(r' );
define( 'SECURE_AUTH_SALT', '/GnTiUDc7-n[cE]rM8i}Q}1]E+dxu&|.}gXM*%SWR xgk*Me3iPT-:QJ>*O)nOlT' );
define( 'LOGGED_IN_SALT',   'E!v}nj*1N^w))s0G$da2(!cT~&.G8g3_OZDDC40u:8G,d)p3!G@g!2`K4?Aj(s+v' );
define( 'NONCE_SALT',       'w7VnqwYbH:QH*UmpdW=[7jy-5]~zA2m#;kBDP{r9dq4Qz@UESwd4HRTF#=$JQ2QQ' );

/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 *
 * At the installation time, database tables are created with the specified prefix.
 * Changing this value after WordPress is installed will make your site think
 * it has not been installed.
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/#table-prefix
 */
$table_prefix = 'wp_';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the documentation.
 *
 * @link https://developer.wordpress.org/advanced-administration/debug/debug-wordpress/
 */
define( 'WP_DEBUG', false );

/* Add any custom values between this line and the "stop editing" line. */

define('WP_HOME', 'https://tdpn.ddns.net');
define('WP_SITEURL', 'https://tdpn.ddns.net');

if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
    $_SERVER['HTTPS'] = 'on';
}

/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
