<?php
if ( ! defined('MY_DEBUG')) 
{
    define('MY_DEBUG', TRUE);
    // define('MY_DEBUG', FALSE);
}
if ( ! defined('MY_ISDEV'))
{
    define('MY_ISDEV', ($_SERVER['REMOTE_ADDR'] == '127.0.0.1'));
}
function updateDB() 
{
    global $table_prefix;
    $link = mysql_connect(DB_HOST, DB_USER, DB_PASSWORD) OR die('Could not connect: ' . mysql_error());
    mysql_select_db(DB_NAME) OR die('Could not select database');
    
    $queries = array();
    $old_path = (MY_ISDEV ? MY_UBASELIVE . '/' . MY_PAPPLIVE : MY_UBASEDEV . '/' . MY_PAPPDEV);
    $new_path = (MY_ISDEV ? MY_UBASEDEV . '/' . MY_PAPPDEV : MY_UBASELIVE . '/' . MY_PAPPLIVE);
    $queries[] = "UPDATE `{$table_prefix}options` SET `option_value` = replace(`option_value`, '{$old_path}', '{$new_path}') WHERE `option_name` = 'siteurl';";
    $old_url = (MY_ISDEV ? MY_UBASELIVE : MY_UBASEDEV);
    $new_url = (MY_ISDEV ? MY_UBASEDEV : MY_UBASELIVE);
    $queries[] = "UPDATE `{$table_prefix}options` SET `option_value` = replace(`option_value`, '{$old_url}', '{$new_url}') WHERE `option_name` = 'home';";
    $queries[] = "UPDATE `{$table_prefix}posts` SET `guid` = replace(`guid`, '{$old_url}', '{$new_url}'), `post_content` = replace(`post_content`, '{$old_url}', '{$new_url}');";
    foreach ($queries as $query) { $result = mysql_query($query) or die('Query failed: ' . mysql_error()); }
    mysql_close($link);
}
// paths
define('MY_PAPPDEV', '');
define('MY_PAPPLIVE', '');
// urls
define('MY_UBASEDEV', 'http://');
define('MY_UBASELIVE', 'http://');

define('WP_DEBUG', FALSE); // 5.3 fix
// define('WP_DEBUG', MY_DEBUG);
if (MY_ISDEV) 
{
    define('WP_POST_REVISIONS', 0);
    define('AUTOSAVE_INTERVAL', 600);
}
/**
 * @link    http://www.smashingmagazine.com/2009/01/26/10-steps-to-protect-the-admin-area-in-wordpress/
 */
// fields in `wp_options` are not dynamic
define('WP_SITEURL', (MY_ISDEV ? MY_UBASEDEV . '/' . MY_PAPPDEV : MY_UBASELIVE . '/' . MY_PAPPLIVE));
// print(WP_SITEURL);
define('WP_HOME', (MY_ISDEV ? MY_UBASEDEV : MY_UBASELIVE));
// define('FORCE_SSL_ADMIN',   TRUE);

// continue
/**
 * @see     wp-config-sample.php
 */
define('AUTH_KEY',          '');
define('SECURE_AUTH_KEY',   '');
define('LOGGED_IN_KEY',     '');
define('NONCE_KEY',         '');

define('DB_NAME',           (MY_ISDEV ? '' : ''));
define('DB_USER',           (MY_ISDEV ? '' : ''));
define('DB_PASSWORD',       (MY_ISDEV ? '' : ''));
define('DB_HOST',           (MY_ISDEV ? '' : ''));
define('DB_CHARSET',        'utf8');
define('DB_COLLATE',        '');
define('WPLANG',            '');

$table_prefix  = 'wp_';

// updateDB();

if ( !defined('ABSPATH') )
    define('ABSPATH', dirname(__FILE__) . '/');

require_once(ABSPATH . 'wp-settings.php');