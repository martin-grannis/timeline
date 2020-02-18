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
 * @link https://codex.wordpress.org/Editing_wp-config.php
 *
 * @package WordPress
 */
// ** MySQL settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define('DB_NAME', 'stjohnstimeline');
/** MySQL database username */
define('DB_USER', 'root');
/** MySQL database password */
define('DB_PASSWORD', 'Wiudluq876efr5');
/** MySQL hostname */
define('DB_HOST', 'localhost');
/** Database Charset to use in creating database tables. */
define('DB_CHARSET', 'utf8mb4');
/** The Database Collate type. Don't change this if in doubt. */
define('DB_COLLATE', '');
/**#@+
 * Authentication Unique Keys and Salts.
 *
 * Change these to different unique phrases!
 * You can generate these using the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 * You can change these at any point in time to invalidate all existing cookies. This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define('AUTH_KEY',         ',CiH-Fd$az^zt@hKleT` #ZgS-1wMg Eb2mLCT[xnjKE`aG?I?|<6Z<#mSkgy[xR');
define('SECURE_AUTH_KEY',  '@&&tYL.37-ucnR3j|Iaw|ndy{bMg`;W/+Mk;<0Cs+p^Z>[&nfZwk7(ZNel_,6PF9');
define('LOGGED_IN_KEY',    'G-G#mdRpNa#T<^Ce?{DOS U1;<&|%l-AJS*4v`!!w%+#hmY;BBMc/v< :C,$dc99');
define('NONCE_KEY',        'Re<BxKAXy,fS1cLvPLF2IMD3Nm,kgV@oO@,:I;s2Hw$awP>Y(+MsM5~OFzy8FKEs');
define('AUTH_SALT',        '/:%[gA5}|T(w<A,8up-c%E2$taewMRc*+$lIBxF2bDTO41MEc@e_dA0CA0DS>clr');
define('SECURE_AUTH_SALT', ';EJyk+}|Npir^+S aj/fRP=s[7$8<&!5k75*X}?8D~^)T7]=B#qdL(ybES0NT(!x');
define('LOGGED_IN_SALT',   'EzSA:?_3a%;a%ffy/E7+4s(h-lTX^`SDpw:cR:$bV/38CRz@wr<dUC]+,)N@Sa4d');
define('NONCE_SALT',       '-^UEhRSdI<a0g)0*nHWI?$~mlQU$d[>[u=j@)Z<p/JRq$sjO39?w*3445^Mc&^8[');
/**#@-*/
/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix  = 'wp_';
/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the Codex.
 *
 * @link https://codex.wordpress.org/Debugging_in_WordPress
 */
/* That's all, stop editing! Happy blogging. */
/** Absolute path to the WordPress directory. */
if ( !defined('ABSPATH') )
	define('ABSPATH', dirname(__FILE__) . '/');
/** Sets up WordPress vars and included files. */
require_once(ABSPATH . 'wp-settings.php');
//error_reporting(E_ALL); ini_set('display_errors', 1);
//define('WP_DEBUG', true);