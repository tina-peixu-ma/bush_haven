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
 * @link https://wordpress.org/documentation/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'wp_db' );

/** Database username */
define( 'DB_USER', 'wp_demouser' );

/** Database password */
define( 'DB_PASSWORD', 'testpassword' );

/** Database hostname */
define( 'DB_HOST', 'localhost' );

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
define( 'AUTH_KEY',         'IT9F2Z2/eW& mQSjrT5=8WUwJk*^;r6N15i{;!0:16okv|L0Qu-2X^L^n/M9<ZX2' );
define( 'SECURE_AUTH_KEY',  ')zDPu1koVFzR,KmaVXF/%D$qZ.a<qw0:3Y`1NodA!5@,H{E)0KksM7mkM.di,EG8' );
define( 'LOGGED_IN_KEY',    'cdY(kCeRaRtHiMl}|-`SdS&.R4v9O:nj=-#C?d>p5?o48{/S+H5zU7di{[GsW.gT' );
define( 'NONCE_KEY',        'u%,ik;QX6F[)Z,9_OQDW?E#0E}7+{X$LI?z@USejqAN$@!*;{LJ BO*0`Gi10Kb-' );
define( 'AUTH_SALT',        '+R7E!9%a[i+@I#%~`-Xo.=R}Kv6Y PXqwdU)]|tZJ<>(QVj%cQ.OSYjUR[=i<_UL' );
define( 'SECURE_AUTH_SALT', 'xHl1_CQQ)HY,P^&l@CS$[;u7^V|=<0y(T6.+:XU;~Nr/J~sId2QL!81}jpbw-,#+' );
define( 'LOGGED_IN_SALT',   'hp`$/Tfw8R.upQa2eixB2D+-*jj0s=L3zMzf Z?gt!1nv[,#Wr}mcQQdXYwu&gjb' );
define( 'NONCE_SALT',       'hH7rX$.=BjgOsuxZ|DQlpEWqyDy=s &4| 1&R|/:Z7eV.]*WaVJ~Lf^GG|B$xZ>:' );

/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
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
 * @link https://wordpress.org/documentation/article/debugging-in-wordpress/
 */
define( 'WP_DEBUG', false );

/* Add any custom values between this line and the "stop editing" line. */



/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
define('FS_METHOD', 'direct');

