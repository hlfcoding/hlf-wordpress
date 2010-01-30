<?php
/**
 * Front to the WordPress application. This file doesn't do anything, but loads
 * wp-blog-header.php which does and tells WordPress to load the theme.
 *
 * @package WordPress
 */

/**
 * Tells WordPress to load the WordPress theme and output it.
 *
 * @var bool
 */
define('WP_USE_THEMES', true);

// append
define('MY_ISDEV', ($_SERVER['REMOTE_ADDR'] == '127.0.0.1'));
// print(MY_ISDEV);

error_reporting((MY_ISDEV ? E_ALL : 0));
// phpinfo();

/**
 * @link    http://www.smashingmagazine.com/2009/01/26/10-steps-to-protect-the-admin-area-in-wordpress/
 */
define('MY_PAPPDEV', '');
define('MY_PAPPLIVE', '');

$dir = MY_ISDEV ? MY_PAPPDEV : MY_PAPPLIVE;
$my_do_update_db = FALSE;

function smartDir($dir) 
{
    global $my_do_update_db;
    if ( ! is_dir($dir)) 
    {
        $my_do_update_db = TRUE;
        $dir_old = FALSE;
        foreach (scandir(getcwd()) as $item) 
        {
            $item = getcwd() . DIRECTORY_SEPARATOR . $item . DIRECTORY_SEPARATOR;
            if (is_dir($item) AND in_array('wp-blog-header.php', scandir($item))) 
            {
                $dir_old =& $item;
                break;
            }
        }
        if ( ! $dir_old) 
        {
            trigger_error('Wordpress app directory not found.');
        }
        $success = rename($dir_old, $dir);         
    }
    return './' . $dir . '/';
}

// continue
require smartDir($dir) . 'wp-blog-header.php';