<?php
//Begin Really Simple Security session cookie settings
@ini_set('session.cookie_httponly', true);
@ini_set('session.cookie_secure', true);
@ini_set('session.use_only_cookies', true);
//END Really Simple Security cookie settings
//Begin Really Simple Security key
define('RSSSL_KEY', 'FY3ZRLyJr1vt7chaJ8WfLaAFj3tnvVukGPFjEtZDMQJUo4vALD9z7L2TBUG8Z1wV');
//END Really Simple Security key

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
if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
    $_SERVER['HTTPS'] = 'on';
}

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'wordpress' );

/** Database username */
define( 'DB_USER', 'administrador' );

/** Database password */
define( 'DB_PASSWORD', 'Usuario1' );

/** Database hostname */
define( 'DB_HOST', 'mariadb.cbc8owua023o.us-east-1.rds.amazonaws.com' );

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
define( 'AUTH_KEY',         '{Kp2P#HB&A:-jpuSVyK/*`_{Dy:J1 tJ*U`5f6Hvh]ah8O!, T|/TDWpC&ynx8{<' );
define( 'SECURE_AUTH_KEY',  'w2U :/K7%a7R>ulbKl`[4Rz]#C3~L{>=,=5>3fh&4-FF}^,Y]4os1[mIJ#pK[(]n' );
define( 'LOGGED_IN_KEY',    'Ui8,9zu%e#a)Z|xXlT>}ir@=0}*8:VljzM]xu)$h6TgWMzG[tNBQ[7n*N@o#8r#0' );
define( 'NONCE_KEY',        '-m19TLU(m{k.}inTF#d(c-AG]^~7Gw0U_X~.[!Z&wpR`5xU`2($FJLNHuk&CXz9.' );
define( 'AUTH_SALT',        'LH1W`1px0v;7=K*2%ARl1n|6Z?$5p>;FGo[:!YyrBAb&4DZk@~BoH?8|v)Hk?zc_' );
define( 'SECURE_AUTH_SALT', '63J#Z_b+d%o,*qgbSHJ}u$L_lj+^rmC=n{fh^!{V9s6E+//Z/%9UTzUlo3%FJ5#:' );
define( 'LOGGED_IN_SALT',   '6Q,saOCdjA;%W_*j:U3E@rfia.Tz:u,Ehv4Mde~=C5rpqABG@mewp7:{V,hH*Xgs' );
define( 'NONCE_SALT',       '[cgGKZizxzTq,Vd9{l4^$oy>b*Jkrpexlu@Ps0>v/C`$&5AAi&!DN0HUXKqp:d_1' );

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

define('WP_HOME','http://tdpn.ddns.net');

define('WP_SITEURL','http://tdpn.ddns.net');

define('FORCE_SSL_ADMIN', false);
$_SERVER['HTTPS'] = 'off';


/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
