<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the web site, you can copy this file to "wp-config.php"
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
define( 'DB_NAME', 'projet' );

/** Database username */
define( 'DB_USER', 'root' );

/** Database password */
define( 'DB_PASSWORD', '' );

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
define( 'AUTH_KEY',         'SrckD6b+zkq4L#N0W0o5n0`i{I?M1yQ=qe(j;-(Cid8l1RKsA~<zB8xN9po[+Y,$' );
define( 'SECURE_AUTH_KEY',  '~vBN}P3<LzDUT3[5Q$f(3)puq&k+]!r.%*aWwU: lkQ5NyiS:;Fnb()SMyvW*JAZ' );
define( 'LOGGED_IN_KEY',    '6IRz#M/DS.<G_fsd{+9QpjLvXY.4lwNSb:1FS(-;] eNl ,scMO.zZU+RDu${4=t' );
define( 'NONCE_KEY',        'e|BIi@&1l{#4l?|Nh9I=hW^?dd@7|B^`I=)qkHmUk|O5icOy/.VZw|-#)5-r?UwO' );
define( 'AUTH_SALT',        '=W=~G7CT[ ov6w{iuoqU 0[%.|OoOd-5 h>-8IGX6?_][F1wvjEEyE:57i4SL6cx' );
define( 'SECURE_AUTH_SALT', '$CSJOe398#Pts6r80u.:C~maeurJ%t?r;9M;>S6-r!t5_[ }kH)sQ0C>3 e:=~.b' );
define( 'LOGGED_IN_SALT',   'GDu z^KH2@%lg]+>4,E6RZ+@awn_KT|%kz7iO I+oegGbm(H{Y+]HmKcH/ae5e)l' );
define( 'NONCE_SALT',       '(zf67W`svg8%4H+Vysg$J|)/%!]4m#a:8(AU}j(8WIO`Ng/95%J&LKVKH)L!}C)1' );

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
