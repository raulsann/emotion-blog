<?php
/**
 * The base configurations of the WordPress.
 *
 * This file has the following configurations: MySQL settings, Table Prefix,
 * Secret Keys, WordPress Language, and ABSPATH. You can find more information
 * by visiting {@link http://codex.wordpress.org/Editing_wp-config.php Editing
 * wp-config.php} Codex page. You can get the MySQL settings from your web host.
 *
 * This file is used by the wp-config.php creation script during the
 * installation. You don't have to use the web site, you can just copy this file
 * to "wp-config.php" and fill in the values.
 *
 * @package WordPress
 */

// ** Heroku Postgres settings - from Heroku Environment ** //
$db = parse_url($_ENV["DATABASE_URL"]);

// ** MySQL settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define('DB_NAME', trim($db["path"],"/"));

/** MySQL database username */
define('DB_USER', $db["user"]);

/** MySQL database password */
define('DB_PASSWORD', $db["pass"]);

/** MySQL hostname */
define('DB_HOST', $db["host"]);

/** Database Charset to use in creating database tables. */
define('DB_CHARSET', 'utf8');

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
define('AUTH_KEY',         '8[3v@LO@#[(;R`[T]NW|.ZCjiq?)Tt56d/-!5awJ5A>-v+Q-=p?1!6rEvNja:s$?');
define('SECURE_AUTH_KEY',  '-`>+]HXGZfK 6KD&mlz>-#~C+i4IzQB<eMV56bi<e|O6d6Y$/.qL`vPg}5 ?Ppq>');
define('LOGGED_IN_KEY',    '3`8bgivIVhwz|)Fr-edkKOD+rac-N1Wh%hfokjk$8Yd[W1 /_5i1zBI6*10VtP 9');
define('NONCE_KEY',        '-.KcLC.wo+{M?xS+#v|Hp>,.DH}cD`Rg_FxIRg?mBee>[&Va(p~X3@C_ruP!/y)6');
define('AUTH_SALT',        'WTvRp9+`36*uYC7c?21tR(u|+~Yc9N~0Q^a.k-[/MT[Z)xdKA&^a0l^3Re[+{gXQ');
define('SECURE_AUTH_SALT', 'b|x62aYsk3LP|0br@Bm<bV_)fqjR5Dmq@+.Y>J^k->!%)Dn`11YBa>=aKt}B}AS/');
define('LOGGED_IN_SALT',   'K bO&[*_Bs@:sx&t;+~+mrT[AXq%J]mGuq3S cx$9+}Lh:BC:d(SP~bPC5RN}zn9');
define('NONCE_SALT',       'q,TIE,:Fa6225#iW|KBzkB{/}(=/Co3CKUQ,4C~]K^[<&&KOFZ_&n^mEW& l3sCO');

/**#@-*/

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each a unique
 * prefix. Only numbers, letters, and underscores please!
 */
$table_prefix  = 'wp_';

/**
 * WordPress Localized Language, defaults to English.
 *
 * Change this to localize WordPress. A corresponding MO file for the chosen
 * language must be installed to wp-content/languages. For example, install
 * de_DE.mo to wp-content/languages and set WPLANG to 'de_DE' to enable German
 * language support.
 */
define('WPLANG', '');

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 */
define('WP_DEBUG', false);

/* That's all, stop editing! Happy blogging. */

/** Absolute path to the WordPress directory. */
if ( !defined('ABSPATH') )
	define('ABSPATH', dirname(__FILE__) . '/');

/** Sets up WordPress vars and included files. */
require_once(ABSPATH . 'wp-settings.php');
