<?php  if ( ! defined('ABSPATH')) exit('No direct script access allowed');

/**
 * This is the boot-strap file for [Your Site Name]
 * It loads the theme library classes and vendor packages.
 * It has the custom theme settings that aren't in the db.
 * @version     1.0
 */

/* common functions */
// follows CI's implementation of the Singleton pattern
// use this function to get the global instance
// $my_t =& my_theme();
function &my_theme() 
{
    return MY_Theme::get_instance();
}
function my_t_define_constants () 
{
    if ( ! defined('MY_ISDEV'))
        { define('MY_ISDEV', in_array($_SERVER['REMOTE_ADDR'], array('127.0.0.1', '::1'))); }
    if ( ! defined('DS')) 
        { define('DS', DIRECTORY_SEPARATOR); }
    if ( ! defined('EOL')) 
        { define('EOL', PHP_EOL); }
    define('MY_ISADMIN', (is_admin() OR basename($_SERVER['PHP_SELF'], '.php') == 'wp-login'));
    // based on CI
    define('MY_BASEFILE', __FILE__);
    define('MY_BASEPATH', realpath(dirname(MY_BASEFILE))); // better than TEMPLATEPATH
    define('MY_BASEURL', get_bloginfo('template_directory'));
    define('MY_APPPATH', MY_BASEPATH . DS . 'library');
    // update WP
    add_filter('theme_root', create_function('$path', 'return MY_BASEPATH;'));
    // MY_TNAME, MY_TVERSION, MY_TAUTHOR
    $t_data = get_theme_data(MY_BASEPATH . DS . 'style.css');
    foreach (array('name', 'version', 'author') as $field) 
    {
        define('MY_T' . strtoupper($field), $t_data[ucwords($field)]);
    }
}
function my_autoload ($class_name)
{
    $class_name = strtr(strtolower($class_name),
        array('my_' => ''
            , '_' => '-'
       ));
    $directories = 
        array(MY_APPPATH . DS
            , getcwd()
       );
    $name_patterns = 
        array('%s.php'
            , 'class.%s.php'
            , 'abstract.%s.php'
            , 'interface.%s.php'
       );
    foreach ($directories as $directory) 
    {
        foreach ($name_patterns as $name_pattern) 
        {
            $path = $directory . sprintf($name_pattern, $class_name);
            // print $path;
            if (file_exists($path))
            {
                require $path;
                return;
            }
        }
    }
}

/* start custom environment */

// run above
my_t_define_constants();
spl_autoload_register('my_autoload');
ob_start(); // #? just to be safe

// only done once here
new MY_Theme(
    array('name' => '[Your Site Name]'
        , 'admin_pages' => 
            array('menu_page' =>
                    array('page_title' => '[Your Site Name] Theme Guide'
                        , 'menu_title' => '[Your Site Name] Site'
                        , 'file' => 'sm/main'
                        , 'icon_url' => MY_BASEURL . '/assets/img/favicon.png'
                    )
                , 'submenu_pages' =>
                    array(
                          // 1 =>
                            // array('page_title' => 'Getting Started'
                                // , 'menu_title' => 'Getting Started'
                                // , 'file' => 'sm/basics' // slug
                            // )
                    )
            )
        , 'widget_groups' =>
            array('sidebar_base' => // key is php/css id
                    array('visible_name' => 'General Sidebar Widgets'
                    )
                , 'sidebar_page' =>
                    array('visible_name' => 'General Page Sidebar Widgets'
                    )
                , 'sidebar_post' =>
                    array('visible_name' => 'General Post Sidebar Widgets'
                    )
                , 'sidebar_search' =>
                    array('visible_name' => 'Search Results Sidebar Widgets'
                    )
                , 'sidebar_admin' =>
                    array('visible_name' => 'Logged In Sidebar Widgets'
                    )
            )
        , 'hide_admin_menus' => 
            // TODO - change to allowed menu items
            // does not include utility-plugins - those that get activated per use
            array(
            )
        , 'routing' =>
            array('happenings' => array('children' => array('events', 'news'))
            )

        // uncomment these for use in development
        // , 'no_styles'    => TRUE
        // , 'no_scripts'   => TRUE
        // , 'no_widgets'   => TRUE

        // uncomment these for use in production
        // , 'no_save_plugins_list'             => FALSE    // save plugins
        // , 'no_hide_admin_menus'              => TRUE     // hide menus
        // , 'no_log_summary'                   => TRUE     // no benchmarking
        // , 'no_flush_custom_theme_options'    => FALSE    // flush options
   ));

// set debugging first
WPDG::$ignore_files[] = 'wp-includes/wp-db.php';
// WPDG::$write_to_file = TRUE;

// then run scripts
if ( ! MY_ISADMIN AND MY_ISDEV) 
{
    $tools_path = MY_Theme::$tools_path . DS;
    include $tools_path . 'my_wp-admin.php';
}

// finally vendors
require MY_APPPATH . DS . 'functions.sandbox.php';
require MY_APPPATH . DS . 'functions.cleanhtml.php';