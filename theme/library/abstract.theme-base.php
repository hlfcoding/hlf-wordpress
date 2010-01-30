<?php  if (! defined('MY_BASEPATH')) exit('No direct script access allowed');

/**
 * @version     1.0
 */
 
abstract class MY_Theme_Base extends Singleton 
{
    // this is a super class
    
    /**
     * @var     array       dependencies
     */
    protected $dependencies;
    /**
     * @var     string
     * @var     string
     */
    protected $phpversion, $name;
    /**
     * @var     array       cms page settings
     */
    protected $admin_pages;
    /**
     * @var     array       cms-viewable dynamic sidebar settings
     */    
    protected $widget_groups = array();
    /**
     * @var     array       pages to not show to make cms less complex
     *                      for maintenance crew
     */    
    protected $hide_admin_menus = array();
    /**
     * @var     object      css/js/etc.
     */
    protected $assets;
    /**
     * @var     array       wp and custom conditional tags
     */
    protected $is = array();
    /**
     * @var     array       toggle functionality
     *                      switches can be dynamically added and removed
     */
    protected $off = array();
    /**
     * @var     array       
     */
    protected $routing = array();
    /**
     * @var     array       saved wp and custom query results
     *                      also sets aside room for custom theme results
     *                      just for this run
     */
    protected $cache = 
        array('theme' => array()
            , 'wp_query' => NULL 
            , 'my_wp_queries' => array()
        );
    /**
     * @var     object      template customization interface
     */
    public $template;
    /**
     * @var     array       to log theme actions
     */
    public $flags = array();
    /**
     * @var     int|array   specific to comments partial
     */
    public $comments_count;

    static $assets_path, $assets_url, $widgets_path, $feeds_path, $admin_path, $builds_path, $tools_path;
    
    // __get
    // pseudo-constants / can be altered
    static $image_thumbnail = 'thumbnail';
    static $image_medium = 'medium';
    static $image_large = 'large';
    static $image_full = 'full';
    // wp
    // prefix
    static $template_tag = array('the_');
    static $conditional_tag = array('is_');
    static $conditional_tag_category = array('is_category_', 'is_cat_');
    static $conditional_tag_page = array('is_page_');
    static $category_id_by_slug = array('category_id_', 'cat_id_');
    static $post_tag_id_by_slug = array('tag_id_'); // unused
    static $page_id_by_slug = array('page_id_');
    static $tag_meta = array('the_meta_');
    static $tag_html_class = array('the_class_'); // for custom implementation
    static $do_return = array('get_');
    static $boolean_tag = array('ok_');
    static $cache_item_tag = array('saved_');
    // suffix
    static $shortcode_tag = array('_shortcode');
    // objects
    static $the_wp = array('wp');
    static $the_wp_query = array('wp_query');
    static $the_custom_wp_query = array('my_wp_query');
    static $the_post = array('post');
    static $the_author = array('author');
    static $the_user = array('current_user');
    static $the_page = array('current_page');
    static $the_custom_fields = array('post_meta');
    static $all_categories = array('categories', 'cats');
    static $all_post_tags  = array('tags');
    static $all_pages = array('pages');
    // __call
    static $new_wp_query = array('query', 'query_custom'); // replacing existing query object
    static $new_custom_wp_query = array('query_custom'); // saving and restoring original wp-query
    static $new_page_wp_query = array('load_page'); // shortcut for querying pages

    function __construct ($args = array()) 
    {
        if (! function_exists('my_t_define_constants')) 
        {
            die('Theme constants not defined.');
        }
        parent::__construct();
        // default
        self::$assets_path = MY_BASEPATH . DS . 'assets';
        self::$assets_url = MY_BASEURL . '/' . 'assets';
        self::$widgets_path = MY_BASEPATH . DS . 'widgets';
        self::$feeds_path = MY_BASEPATH . DS . 'feeds';
        self::$admin_path = MY_BASEPATH . DS . 'admin-pages';
        self::$builds_path = MY_BASEPATH . DS . 'builds';
        self::$tools_path = MY_BASEPATH . DS . 'tools';
        // custom overrides + dynamic property declarations
        // this allows use to save some space
        foreach ($args as $key => $value) 
        {
            if (property_exists($this, $key) OR $this->is_switch_var($key)) 
            {
                $this->{$key} = $value;
            }
        }
        $this->assets = new MY_Theme_Assets(
            array('handle' => self::$assets_path
                , 'url' => self::$assets_url
           ));
        $this->check_dependencies();
        $this->check_phpversion();
        $this->check_activation();
        $this->add_wp_hooks();
        if (MY_ISADMIN) 
        {
            add_action('admin_menu', array($this, 'add_admin_pages'), 1); // add first b/c we're important
        }
        // TODO - do admin
        $this->save_plugins_list();
        // outsource data sanitizing
        // wp-post/query interface, request var handling
        $this->template = new StdClass();
    }

    /* core / wp core */

    function __get ($name) 
    {    
        if (WPHF::has_prefix($name, 'constant_')
            AND $pseudo_constant = str_replace('constant_', '', $name) AND property_exists($this, $pseudo_constant))
        {
            return self::${$pseudo_constant};
        }
        if (WPHF::has_suffix($name, '_no_cache')
            AND $name = str_replace('_no_cache', '', $name)) 
        {
            $this->no_cache = TRUE;
        }
        else 
        {
            $this->no_cache = FALSE;
        }
        // section: wp 
        switch (TRUE) 
        {
            case ($this->is_switch_var($name)):
                
                return in_array($name, $this->off);
                
            break; case ($prefix = WPHF::has_prefix($name, self::$cache_item_tag, TRUE) AND $prefix !== FALSE): // get any cache item
                
                $key = str_replace($prefix, '', $name);
                $result = WPHF::element($key, $this->cache);
                return $result;
                
            break; case in_array($name, self::$the_wp): // alias for current query
                
                global $wp;
                return $wp;
                
            break; case in_array($name, self::$the_wp_query): // alias for current query
                
                global $wp_query;
                return $wp_query;
                
            break; case in_array($name, self::$the_custom_wp_query): // alias for current custom query
                
                $queries =& $this->cache['my_wp_queries'];
                if ( ! empty($queries)) 
                {
                    $key = count($queries) - 1;
                    return $queries[$key];
                }
                
            break; case in_array($name, self::$the_post): // alias for current post
                
                global $post; 
                return $post;
                
            break; case in_array($name, self::$the_author): // alias for current author
                
                global $authordata;
                $authordata->display_name = apply_filters('the_author', $authordata->display_name);
                return $authordata;
                
            break; case in_array($name, self::$the_user): // alias for current viewer / editor
                
                global $current_user;
                return $current_user;
                
            break; case in_array($name, self::$the_page): // process the request var
                
                $result = WPHF::element('the_page', $this->cache);
                if ( ! $result) 
                {
                    $result = explode('/', $this->wp->query_vars['pagename']);
                    $result = array_shift($result) . (($sub_page = array_pop($result) AND $sub_page) ? '_' . $sub_page : '');
                    $this->cache['the_page'] = $result;
                }
                return $result;
                
            break; case in_array($name, self::$the_custom_fields): // lazy load current post meta
                
                if ( ! array_key_exists('the_custom_fields', $this->cache)) 
                {
                    $this->cache['the_custom_fields'] = get_post_custom();
                }
                return WPHF::element('the_custom_fields', $this->cache);
                
            break; case in_array($name, self::$all_categories): // lazy load all post categories
                
                if ( ! array_key_exists('all_categories', $this->cache) OR $this->no_cache) 
                {
                    $categories = get_categories(array('hide_empty' => FALSE));
                    foreach ($categories as $key => $category) 
                    {
                        $this->cache['all_categories'][$category->slug] = $category;
                    }
                    // see below for defaults
                }
                return WPHF::element('all_categories', $this->cache);
            
            break; case in_array($name, self::$all_post_tags): // lazy load all post tags
                
                if ( ! array_key_exists('all_post_tags', $this->cache)) 
                {
                    $this->cache['all_post_tags'] = get_tags();
                    // array('orderby' => 'name', 'order' => 'ASC', 'hide_empty' => TRUE, 'exclude' => '', 'exclude_tree' => '', 'include' => '', 'number' => '', 'fields' => 'all', 'slug' => '', 'parent' => '', 'hierarchical' => TRUE, 'child_of' => 0, 'get' => '', 'name__like' => '', 'pad_counts' => FALSE, 'offset' => '', 'search' => '');
                }
                return WPHF::element('all_post_tags', $this->cache);
                
            break; case ($prefix = WPHF::has_prefix($name, self::$category_id_by_slug, TRUE) AND $prefix !== FALSE):
                // lazy load cat id<->slug hash to return id for slug
                if ( ! array_key_exists('category_id_by_slug', $this->cache)) 
                {
                    $category_ids = array();
                    foreach ($this->categories as $category) 
                    {
                        $var_name = $category->slug;
                        if ($category->parent != 0) 
                        {
                            $parent = get_category($category->parent);
                            $var_name = $parent->slug . '_' . $var_name;
                        } 
                        $category_ids[$prefix . $var_name] = $category->term_id;
                    }
                    $this->cache['category_id_by_slug'] = $category_ids;
                } 
                else 
                {
                    $category_ids = WPHF::element('category_id_by_slug', $this->cache, array());
                }
                // TODO - allow remap
                // _log($this->cache['category_id_by_slug']);
                return WPHF::element($name, $category_ids);
                
            break; case in_array($name, self::$all_pages): // lazy load all pages
            
                if ( ! array_key_exists('all_pages', $this->cache)) 
                {
                    $this->cache['all_pages'] = get_pages();
                }
                return WPHF::element('all_pages', $this->cache);
                
            break; case ($prefix = WPHF::has_prefix($name, self::$page_id_by_slug, TRUE) AND $prefix !== FALSE):
                // lazy load page id<->slug hash to return id for slug
                if ( ! array_key_exists('page_id_by_slug', $this->cache)) 
                {
                    $page_ids = array();
                    foreach ($this->pages as $page) 
                    {
                        $var_name = $page->post_name;
                        if ($page->post_parent != 0) 
                        {
                            $parent = get_page($page->post_parent);
                            $var_name = $parent->post_name . '_' . $var_name;
                        } 
                        $page_ids[$prefix . $var_name] = $page->ID;
                    }
                    $this->cache['page_id_by_slug'] = $page_ids;
                    // _log($page_ids);
                } 
                else 
                {
                    $page_ids = WPHF::element('page_id_by_slug', $this->cache);
                }
                // _log($this->cache['page_id_by_slug']);
                return WPHF::element($name, $page_ids);
                
            break; case (WPHF::has_prefix($name, self::$conditional_tag)): // get conditional result
                
                $result = WPHF::element($name, $this->is);
                if ( ! $result) 
                {
                    if (WPHF::has_prefix($name, self::$conditional_tag_category))
                    // lazy load is_category results
                    {
                        $the = $this->wp_query->in_the_loop;
                        $this->save_category_checks($the);
                        $result = WPHF::element($name, $this->is); // 
                    }
                    elseif ($prefix = WPHF::has_prefix($name, self::$conditional_tag_page, TRUE) AND $prefix !== FALSE) 
                    // lazy set is_page custom conditionals
                    // ex: is_page_current
                    {
                        $page_name = str_replace($prefix, '', $name);
                        $page_vars = explode('/', $this->wp->query_vars['pagename']);
                        if ($page_name == 'index') 
                        {
                            $result = (count($page_vars) == 1);
                        }
                        else 
                        {
                            $result = in_array($page_name, $page_vars);
                        }
                        $this->is[$name] = $result;
                    }
                }
                return $result;
                
            break; case ($prefix = WPHF::has_prefix($name, self::$tag_meta, TRUE) AND $prefix !== FALSE): // get post meta 
                
                $name = str_replace($prefix, '', $name);
                $meta = WPHF::element($name, $this->post_meta, array()); // linked lazy load
                if (is_array($meta)) 
                {
                    if (count($meta) == 1) 
                    {
                        return array_shift($meta); // #warn
                    }
                }
                return $meta; 
                
            break; 
        }
    }
    function __set ($name, $value) 
    {
        switch (TRUE) 
        {
            case ($this->is_switch_var($name)): // ex: no_styles
                if ($value === FALSE) 
                {
                    $key = array_search($name, $this->off);
                    if ($key !== FALSE) 
                    {
                        unset($this->off[$key]);
                    }
                    // _log();
                }
                else 
                {
                    if ( ! in_array($name, $this->off)) 
                    {
                        $this->off[] = $name;
                    }
                }
            // section: wp
            break; case in_array($name, self::$the_wp_query): // alias for current query
                global $wp_query;
                $wp_query = $value;
                // _log('trace', 'new query created');
            break; case in_array($name, self::$the_custom_wp_query):
                $this->cache['my_wp_queries'][] = $value;
            break; case in_array($name, self::$the_post): // alias for current query
                global $post;
                $post = $value;
            break;
        }
    }
    function __call ($name, $args) 
    {
        switch (TRUE) 
        {
            case in_array($name, self::$new_wp_query): // wp_query builder
                switch(count($args)) 
                {
                    case 2:
                        list($query, $backups) = $args;
                    break; case 1: default:
                        list($query) = $args;
                    break;
                }
                if (in_array($name, self::$new_custom_wp_query)) 
                {
                    $wp_query = NULL;
                }
                else 
                {
                    global $wp_query;
                }
                $wp_query = new WP_Query($query);
                while ( ! have_posts() AND ! @empty($backups)) 
                {
                    $wp_query = new WP_Query($backups[0]);
                    unset($backups[0]);
                }
                if (in_array($name, self::$new_custom_wp_query)) 
                {
                    $this->my_wp_query = $wp_query;
                }
                // _log('trace', 'new query created');
            break; case ($name == 'start_query_custom');
                $this->wp_query->in_the_loop = TRUE;
                $this->cache['temp_wp_query'] = $this->wp_query; // save
                $this->wp_query = $this->my_wp_query; // change
            break; case ($name == 'end_query_custom') :
                $this->wp_query = $this->cache['temp_wp_query']; // revert
                $this->wp_query->in_the_loop = FALSE;
                array_pop($this->cache['my_wp_queries']); // delete
            break; case in_array($name, self::$new_page_wp_query): 
                if (WPHF::element(0, $args) == FALSE) 
                    { return; }
                else 
                    { $pagename = $args[0]; }
                $custom_query_params = WPHF::custom_else_default(WPHF::element(1, $args), array());
                $query_params = array_merge(compact('pagename') + array('showposts' => 1, 'post_type' => 'page'), $custom_query_params);
                $query_function = WPHF::custom_else_default(WPHF::element(2, $args), 'query');
                $this->$query_function($query_params);
                if (have_posts()) {
                    the_post();
                    return TRUE;
                }
                else 
                {
                    trigger_error('Page not found.', E_USER_NOTICE);
                    return FALSE;
                }
            break;
        }
    }
    function is_switch_var ($name) 
    {
        return (strncmp($name, 'no_', 3) == 0);
    }
    function check_dependencies () 
    {
        if ( ! is_array($this->dependencies)) 
        {
            trigger_error('Theme dependencies not set');
        }
        foreach($this->dependencies as $constant) 
        {
            if ( ! defined($constant)) 
            {
                trigger_error('Dependency does not exist. Check plugin with the constant ' . $constant);
            }
        }
        if ( ! function_exists('my_t_define_constants')) 
        {
            trigger_error('Theme constants not set');
        }
    }
    function check_phpversion () 
    {
        if ($this->phpversion > (double) phpversion()) 
        {
            trigger_error('This theme requires PHP version ' . $this->phpversion,  E_USER_ERROR);
        }
    }

    /* cms */
    
    function add_admin_pages () 
    {
        extract($this->admin_pages);   
        if ( ! empty($menu_page)) // is actually used to add plugin pages, though the theme is almost a plugin
        {
            // set these in custom theme
            add_menu_page($menu_page['page_title'], $menu_page['menu_title'], $menu_page['access_level']
                , $menu_page['file'], array($this, $menu_page['function']), $menu_page['icon_url']);
            if ( ! empty($submenu_pages)) 
            {
                foreach ($submenu_pages as $submenu_page) 
                {
                    foreach (array('access_level', 'function') as $setting) 
                    {
                        $submenu_page[$setting] = WPHF::element($setting, $submenu_page) 
                            ? $submenu_page[$setting] : $menu_page[$setting];
                        if ($setting = 'function' AND ! function_exists($submenu_page[$setting])) 
                        {
                            $submenu_page[$setting] = $menu_page[$setting];
                        }
                    }
                    add_submenu_page($menu_page['file'], $submenu_page['page_title'], $submenu_page['menu_title']
                        , $submenu_page['access_level'], $submenu_page['file'], array($this, $submenu_page['function']));
                }
            }
        } 
        elseif ( ! empty($theme_page)) 
        {
            add_theme_page(
                  $theme_page['page_title']
                , $theme_page['menu_title']
                , $theme_page['access_level']
                , $theme_page['file']
                , array($this, $theme_page['function'])
                );
        }
    }
    function hide_admin_menus () 
    {
        global $submenu;
        foreach ($submenu as $group_name => $group) 
        {
            foreach ($group as $key => $item) 
            {
                list($name, $permission, $page) = $item;
                // _log($page, $name);
                if (in_array($page, $this->hide_admin_menus)) 
                {
                    unset($submenu[$group_name][$key]);
                    continue 1;
                }
            }
        }
    }
    function check_activation () 
    {
        $my_theme = get_option('my_theme');
        if ( ! $my_theme) 
        {
            $this->activate_theme();
        }
        elseif ( ! $this->no_flush_custom_theme_options)
        {
            $this->update_theme_options();
        }
    }
    function activate_theme () // append to db
    {
        $this->add_theme_options();
    }
    function deactivate_theme () // clean db
    {
        // TODO - 
    }
    function register_widgets () 
    {
        foreach ($this->widget_groups as $key => $widget_group_settings) 
        {
            if ( ! WPHF::element('id', $widget_group_settings)) // key is default `id`
            {
                $widget_group_settings['id'] = WPHF::dash($key);
            }
            $this->widget_groups[$key] = new MY_Theme_Widget_Group($widget_group_settings);
        }
        
        $widgets = WPHF::directory_map(self::$widgets_path, FALSE, array('php')); // non-recursive
        foreach ($widgets as $key => $widget_file) 
        {
            $widget_path = WPHF::d_path(array(self::$widgets_path, $widget_file));
            // class.name-sub-sub.php -> MY_Name_Sub_Sub_Widget
            $widget_class = WPHF::underscore(preg_replace('/^(?:(?:class|abstract|functions)[-_.])?/i'
                    , 'MY_', basename($widget_file, '.php'))
                , FALSE, FALSE, TRUE); // lower, path, camel
            $widgets[$key] = $widget_class;
            require $widget_path;
        }
        foreach ($widgets as $widget_class) 
        {
            // do not instantiate 'abstract' classes
            if (preg_match('/_Base$/', $widget_class) == 0) 
            {
                register_widget($widget_class);
            }
        }
    } 
    function add_theme_options ()
    {
        add_option('my_theme', $this->name);
        add_option('my_t_hide_advanced_admin', (int)( ! $this->no_hide_admin_menus));
    }
    function update_theme_options () 
    {
        update_option('my_t_hide_advanced_admin', (int)( ! $this->no_hide_admin_menus));
    }
    function theme_admin_controller ($vars = array()) // preprocess vars that get returned for view
    {
        extract($this->admin_pages);
        return get_defined_vars();
    } 
    function theme_admin_page () // by default deals with all theme cms pages
    {
        extract($this->theme_admin_controller());
        // get custom views
        ob_start();
        include WPHF::d_path(array(self::$admin_path, 'guide.php'));
        $output = ob_get_contents();
        if (WPHF::exists($links)) 
        {
            foreach ($links as $name => $link) 
            {
                extract($link);
                $patterns[] = "<a class=\"$name\"";
                $replacement = "<a class=\"$name\" href=\"$url\"";
                if (isset($tip)) 
                    { $replacement .= " title\"$tip\""; }
                $replacements[] = $replacement;
            }
            // TODO - works for now, though would rather use preg_replace
            $output = str_replace($patterns, $replacements, $output);
        }
        ob_end_clean();
        echo $output;
    }
    
    /* hooks */
    
    function add_wp_hooks () 
    {
        if ( ! MY_ISADMIN) // site only
        {
            // loop
            add_action('the_post', array($this, 'save_custom_fields'));
            add_action('query_vars', array($this, 'register_query_vars'));
            // init
            add_action('init', array($this, 'init')); // has child hooks
            add_action('template_redirect', array($this, 'save_wp_query'));
            add_action('template_redirect', array($this, 'redirect_templates'));
            // head
            WPHF::shift_hook('action', 'wp_head', 'wp_print_head_scripts', 9); // to 10
            if ( ! $this->no_styles) 
            {                
                add_action('wp_print_styles', array($this, 'print_styles'), 1);
                add_action('wp_print_styles', array($this, 'print_remote_styles'), 0);
                // wp_print_styles is priority 8
                // allows outputting in accord with queues
                add_action('wp_head', array($this, 'print_extra_styles'), 7);
                add_action('wp_head', array($this, 'print_ie_styles'), 9);
            }
            if ( ! $this->no_scripts) 
            {
                add_action('wp_print_scripts', array($this, 'print_scripts'), 1);
                add_action('wp_print_scripts', array($this, 'print_remote_scripts'), 0);
                add_action('wp_head', array($this, 'print_ie_scripts'), 11);
            }
            // foot
            if ( ! $this->no_log_summary)
            {
                add_action('wp_footer', array($this, 'log_run_summary'), 99);
            }
        }
        else 
        {
            // if ( ! $this->no_hide_admin_menus)
            if (get_option('my_t_hide_advanced_admin')) 
            {
                add_action('admin_menu', array($this, 'hide_admin_menus'), 99);
            }
        }
        if ( ! $this->no_widgets AND is_dir(self::$widgets_path))
        {
            add_action('widgets_init', array($this, 'register_widgets'));
        }
    }
    
    function _e () 
    {
        $args = func_get_args();
        if (count($args) > 1) 
        {
            if ($this->no_echo) 
            {
                $this->no_echo = FALSE;
                return call_user_func_array('sprintf', $args);
            } 
            else 
            {
                call_user_func_array('printf', $args);
            }            
        }
        else 
        {
            $str =& $args[0];
            if ($this->no_echo) 
            {
                $this->no_echo = FALSE;
                return $str;
            } 
            else 
            {
                echo $str;
            }
        }
    }
    
    abstract function print_remote_styles ();
    abstract function print_remote_scripts ();
    
    /* theme assets */
    
    function print_styles () 
    {
        // main layout
        wp_enqueue_style('style', get_bloginfo('stylesheet_url'), array(), MY_TVERSION, 'all');
        // add default style
        foreach ($this->assets->styles as $stylesheet) 
        {
            extract($stylesheet);
            // manual dependencies
            switch (TRUE) 
            {
                case ($file == 'color' OR $file == 'type'):
                default:
                    $deps[] = 'style';
                break;
            }
            do_action_ref_array('my_t_print_styles', compact(array_keys($stylesheet)));
            wp_enqueue_style($file, $url, $deps, $ver, $media);
        }
    }
    function print_ie_styles () 
    {
        foreach ($this->assets->styles_ie as $stylesheet) 
        {
            extract($stylesheet);
            echo preg_replace(
                      array("/[\s]{2}/", "/(\s\]){1}/")
                    , array(" ", "]")
                    , "<!--[if {$ie_op} IE {$ie_ver}]>")
                 . PHP_EOL; 
            echo $tag;
            echo "<![endif]-->" . PHP_EOL; 
        }
    }
    function print_extra_styles () // fix
    {
        $set = $this->assets->styles;
        $newest_ie = array_shift($this->assets->styles_ie);
        extract($newest_ie);
        echo preg_replace(
              array("/[\s]{2}/", "/(\s\]){1}/")
            , array(" ", "]")
            , "<!--[if {$ie_op} IE {$ie_ver}]>")
             . PHP_EOL; 
        foreach ($this->assets->styles_media as $mediasheet) 
        {
            extract($mediasheet, EXTR_PREFIX_ALL, 'm');
            echo $m_tag;
        }
        echo "<![endif]-->" . PHP_EOL; 
    }
    function print_scripts () 
    {
        foreach ($this->assets->scripts as $script) 
        {
            extract($script);
            wp_enqueue_script($file, $url, $deps, $ver);
            do_action_ref_array('my_t_print_scripts', array_values($script)); 
        }
    }
    function print_ie_scripts () 
    {
        foreach ($this->assets->scripts_ie as $script) 
        {
            extract($script);
            echo preg_replace(
                      array("/[\s]{2}/", "/(\s\]){1}/")
                    , array(" ", "]")
                    , "<!--[if {$ie_op} IE {$ie_ver}]>")
                 . PHP_EOL; 
            echo $tag;
            echo "<![endif]-->" . PHP_EOL; 
        }        
    }

    /* wp routing */
    
    function init () 
    {
        if ( ! $this->no_rewrite) 
        {
            global $wp_rewrite;
            add_action('generate_rewrite_rules', array($this, 'add_rewrite_rules'));
            $wp_rewrite->flush_rules();
            $this->add_permastructs($wp_rewrite);
            $this->add_rewrite_tags($wp_rewrite);
            add_filter('request', array($this, 'parse_query_vars'));
        }
    }
    function register_query_vars ($vars) 
    {
        return $vars;
    }
    function parse_query_vars ($vars) 
    {
        return $vars;
    }
    function add_rewrite_rules (&$wp_rewrite) 
    {
        $new_rules = array();
        $wp_rewrite->rules = $new_rules + $wp_rewrite->rules;
    }
    function add_rewrite_tags (&$wp_rewrite) {}
    function add_permastructs ($wp_rewrite) {}
    function redirect_templates () 
    {
        
    }
    
    /* wp helpers */
    
    function check_and_update_post ($post = NULL) 
    {
        $update_the_post = FALSE;
        if ( ! isset($post)) 
        {
            $post = $this->post;
            $update_the_post = TRUE;
        }
        if ( ! isset($post)) 
        {
            return;
        }
        $post_update = array('ID' => $post->ID);
        $post_update = apply_filters('my_t_check_and_update_post', $post_update);
        if (count($post_update) > 1) 
        {
            $result = wp_update_post($post_update);
            if ($result != 0 AND $update_the_post) // TODO - #bug
            {
                $this->post = $this->wp_query->post = get_post($post->ID);
            }
            // _log('trace', (($result == 0) ? 'failure' : 'success'));
        }
    }
    
    /* run-caching */
    
    function save_wp_query () 
    {
        // _log($this->wp_query);
        $this->cache['wp_query'] = $this->wp_query;
        foreach ((array) $this->wp_query as $property_name => $property_value) 
        {
            // save conditional tags
            if (WPHF::has_prefix($property_name, self::$conditional_tag)) 
            {
                $this->is[$property_name] = $property_value;
            }
        }
    }
    function save_custom_fields () 
    {
        // _log();
        $this->cache['the_custom_fields'] = get_post_custom();
    }
    function save_category_checks ($the = FALSE) 
    {
        $categories = $the ? get_the_category() : $this->categories;
            // just update if for one post
        $prefix = self::$conditional_tag_category[0];
        foreach ($categories as $category) 
        {
            $var_name = $category->slug;
            if ($category->parent != 0) 
            {
                $parent = get_category($category->parent);
                $var_name = $parent->slug . '_' . $var_name;
            }
            if ( ! empty($prefix)) // #bug
            {            
                $this->is[$prefix . $var_name] = in_category($category->slug, $this->post);
            } 
        }
    }
    function save_comments_count ($separate_comments = FALSE) 
    {
        if ($separate_comments) 
        { 
            $this->comments_count = array();
            foreach ($this->wp_query->comments_by_type as $type => $store) 
            {
                $this->comments_count[$type] = count($store);
            }
        }
        else 
        {
            $this->comments_count = count($this->wp_query->comments);
        }
        // _log($this->comments_count);
    }
    
    /* wp tools */
    
    function add_flag ($flag) 
    {
        if ( ! in_array($flag, $this->flags)) 
        {
            $safe_to_add = TRUE;
            $this->flags[] = $flag;
        } 
        else 
        {
            $safe_to_add = FALSE;
        }
        return $safe_to_add;
    }
    function save_plugins_list () 
    {
        if ( ! $this->no_save_plugins_list) 
        {
            $paths = get_option('active_plugins');
            foreach ($paths as &$path) 
            {
                $path = dirname($path);
            }
            $paths = implode(EOL, $paths);
            $nresult = file_put_contents(self::$tools_path . DS . 'plugins_list.txt', $paths);
            return $nresult;
        }
    }
    function log_run_summary () 
    {
        if ( ! WPDG::$write_to_file) 
        {
            WPDG::$instance->group('theme cache', array('Collapsed' => TRUE));
            foreach ($this->cache as $name => $cache_var) 
            {
                if (is_array($cache_var)) 
                {
                    $name .= ' (' . count($cache_var) . ') ';
                }
                _log($cache_var, $name);
            }
            WPDG::$instance->groupEnd();
            _log(get_num_queries(), 'number of queries');
            _log(timer_stop(0), 'runtime');
        }
    }
}

/* End of file abstract.theme-base.php */
/* Location: ./library/abstract.theme-base.php */