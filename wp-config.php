<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the
 * installation. You don't have to use the web site, you can
 * copy this file to "wp-config.php" and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * MySQL settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://wordpress.org/support/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** MySQL settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'secondassignment' );

/** MySQL database username */
define( 'DB_USER', 'root' );

/** MySQL database password */
define( 'DB_PASSWORD', '' );

/** MySQL hostname */
define( 'DB_HOST', 'localhost' );

/** Database Charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8mb4' );

/** The Database Collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication Unique Keys and Salts.
 *
 * Change these to different unique phrases!
 * You can generate these using the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 * You can change these at any point in time to invalidate all existing cookies. This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',         ']@8dCIE$AbYv_O9TZHJ@u/L02n(2j.ddF1_<-E!)fz3_3WnS,oFG<_*yzi33AuO*' );
define( 'SECURE_AUTH_KEY',  'K)( rk]}7x9JopA:fK1fx76:)KA,,f[L=#i3FQ97 hJ~<5d1/n @EEjaK0?pJ[O9' );
define( 'LOGGED_IN_KEY',    'XFV.{50O1^X}%+yhPCzE;z-(?J8Sx%U%fVMKX7>8Z&nG6prbyVRg8O|Tp^L_~[gj' );
define( 'NONCE_KEY',        'd[mP].i4XSqtZ~H)yje_wWJ`EfJGHM}^(&rBg~@gWfO&i&(UYTp*K[To9f2)&Y^=' );
define( 'AUTH_SALT',        '+e7:@f8Sg(u^4PfD{~bn<SqW7]V.e 7?Ydi^S-!0Y7KnTJ&xh.:.#Ybg+9;36#3t' );
define( 'SECURE_AUTH_SALT', '+tZqhF)SawnToFUXoe~q/.?0TztK(~Y-?]3UA$$*TI.,g9uGsbK3xELQ|SF`v&M&' );
define( 'LOGGED_IN_SALT',   'MzN0#dI#S7%^f(|,cM!A1%[p-e7(x%,Hd={G53>`.=q$ARch5#5AT!&s:uh/O:ws' );
define( 'NONCE_SALT',       'g_^N+5W=wi IUkF:=XM<5;,9.[n}) R2H`UM8BoxP2)hjgWtIT:.y505W1J>Y4xA' );

/**#@-*/

/**
 * WordPress Database Table prefix.
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
 * @link https://wordpress.org/support/article/debugging-in-wordpress/
 */
define( 'WP_DEBUG', false );

/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
