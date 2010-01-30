<?php  if (! defined('MY_BASEPATH')) exit('No direct script access allowed');

/**
 * @version     1.0
 */
 
class MY_Theme extends MY_Theme_Base 
{
    // this is a super-class
    
    /**
     * helper vars
     */
    
    static $flag_post = array('post');
    static $flag_post_content = array('content');
    static $flag_post_preview = array('excerpt');
    static $flag_post_title = array('title');
    static $flag_post_event = array('event');
    
    static $id_admin = 2;

    /**
     * urls
     */
    static $tag_slug_pattern = '%s-tag';
    
    function __construct ($args = array()) 
    {
        $defaults = 
            array('name' => 'My Theme'
                , 'dependencies' => 
                    array('WP_HELPER_FUNCTIONS'
                        , 'WP_OUTPUT'
                        , 'WP_DEBUGGER'
                   )
                , 'phpversion' => 5.2
                , 'admin_pages' => 
                    array('menu_page' =>
                            array('page_title' => 'My Theme Settings'
                                , 'menu_title' => 'My Theme'
                                , 'access_level' => 'edit_posts'
                                , 'file' => 'my_theme' // query slug
                                , 'function' => 'theme_admin_page'
                                , 'icon_url' => ''
                            )
                        , 'submenu_pages' => array()
                        , 'theme_page' => 
                            array('page_title' => 'My Theme Settings'
                                , 'menu_title' => 'My Theme'
                                , 'access_level' => 5
                                , 'file' => 'my_theme' // query slug
                                , 'function' => 'theme_admin_page'
                            )
                   )
                , 'hide_admin_menus' => array()
                , 'widget_groups' => 
                    array()
                , 'no_styles' => FALSE
                , 'no_scripts' => FALSE
                , 'no_widgets' => FALSE
                , 'no_rewrite' => FALSE
                , 'no_save_plugins_list' => TRUE
                , 'no_hide_admin_menus' => FALSE
                , 'no_log_summary' => FALSE
                , 'no_flush_custom_theme_options' => TRUE
           );
        $args = WPHF::md_wp_parse_args($args, $defaults);
        // _log($args, __METHOD__);
        // prep for base theme
        $this->add_theme_hooks();
        parent::__construct($args);
        // customize wp on init
        $this->add_custom_wp_hooks();
        $this->wp_security_hooks();
        $this->wp_shortcode_hooks();
    }
    
    /* hooks */
    
    function wp_security_hooks () 
    {
        if (MY_ISADMIN) 
        {
            add_filter('login_errors', create_function('', 'return;')); 
        }
        remove_action('wp_head', 'wp_generator');
    }
    function wp_shortcode_hooks () 
    {
        remove_shortcode('gallery', 'gallery_shortcode');
        add_filter('the_content_rss', 'do_shortcode');
    }
    function add_theme_hooks () 
    {
        if ( ! MY_ISADMIN) 
        {
            add_action('my_t_print_styles', array($this, 'check_style'), 10, 5);
            add_action('my_t_print_scripts', array($this, 'check_script'), 10, 4);
        }   
    }
    function add_custom_wp_hooks () 
    {
        if ( ! MY_ISADMIN) 
        {
            add_shortcode('antispambot', array($this, 'antispambot_shortcode'));
            // TODO shortcode for permalinking
        } 
        else 
        {
            remove_all_filters('theme_root'); // #hack some crazy #bug
        }
    }
    
    /* cms */
    
    function add_theme_options () 
    {
        parent::add_theme_options();
    }
    function theme_admin_controller ()
    {
        extract(parent::theme_admin_controller());
        return get_defined_vars();
    }
    
    /* wp */
    
    function check_style ($file, $url, $deps, $ver, $media) 
    {
    }
    function check_script ($file, $url, $deps, $ver) 
    {
        if (WPHF::has_segment('widgets', $url)) 
        {
            wp_deregister_script(basename($file, '.js'));
        }
    }
    function print_remote_styles () {}
    function print_remote_scripts () 
    {
        wp_enqueue_script('comment-reply');
        wp_deregister_script('jquery');
        wp_enqueue_script(
              'jquery'
            , 'http://ajax.googleapis.com/ajax/libs/jquery/1.3.2/jquery.min.js' 
            , array()
            , '1.3.2'
       );
    }
    function init () 
    {
        parent::init();
    }
    
    function register_query_vars ($vars) 
    {
        $vars = array_merge($vars, self::$custom_query_vars);
        return parent::register_query_vars($vars); // end
    }
    function parse_query_vars ($vars) 
    {
        switch (TRUE) 
        {
            // case array_key_exists('publication-author', $vars):
                // $vars['publication-author'] = sprintf(self::$tag_slug_pattern, $vars['publication-author']); // back to tag slug
            // break;
        }
        return parent::parse_query_vars($vars);
    }
    function add_rewrite_rules (&$wp_rewrite) 
    {
        $new_rules = 
            array(//'publications\/([^\/]+)\/?$' => 'index.php?pagename=publications&publication-author=' . $wp_rewrite->preg_index(1)
            );
        $wp_rewrite->rules = $new_rules + $wp_rewrite->rules;
        parent::add_rewrite_rules($wp_rewrite); // end
    }
    function add_rewrite_tags (&$wp_rewrite) 
    {
        parent::add_rewrite_tags($wp_rewrite);
    }
    function add_permastructs (&$wp_rewrite) 
    {
        // $wp_rewrite->add_permastruct('publication-author', '%publication-author%', FALSE); // with_front
        parent::add_permastructs($wp_rewrite);
    }
    
    function __get ($name) 
    {
        switch (TRUE) 
        {
            case (WPHF::has_prefix($name, self::$conditional_tag)):
                // _log($name);
                switch (TRUE) 
                {
                    case in_array($name, self::$conditional_tag_by_admin):
                        return ($this->author->ID == self::$id_admin);
                    break; case in_array($name, self::$conditional_tag_last_post):
                        return ($this->wp_query->current_post + 1 == $this->wp_query->post_count);
                    break; case in_array($name, self::$conditional_tag_post_profile):
                        return TRUE;
                    break; case in_array($name, self::$conditional_tag_post_project):
                        return TRUE;
                    break; case in_array($name, self::$conditional_tag_post_event):
                        // TODO - 
                        return TRUE;
                    break; case in_array($name, self::$conditional_tag_post_news):
                        return TRUE;
                    break; case in_array($name, self::$conditional_tag_post_job):
                        return TRUE;
                    break;
                }
            break; case in_array($name, self::$boolean_tag): 
                switch (TRUE) 
                {
                    case in_array($name, self::$conditional_tag_by_admin):
                        return ($_COOKIE['wp-postpass_' . COOKIEHASH] == $this->post->post_password);
                    break;
                }
            break; case in_array($name, self::$post_tag_ids_people): // custom result
                $result = WPHF::element('post_tag_ids_people', $this->cache, array());
                if (empty($result)) 
                {
                    $people = query_posts(array('cat' => $this->cat_id_people));
                    $people_names = array();
                    foreach ($people as $person) 
                    { 
                        $people_names[] = sprintf(self::$tag_slug_pattern, $person->post_name); 
                    }
                    foreach ($this->tags as $tag) // only non-empty
                    {
                        if (in_array($tag->slug, $people_names)) 
                        {
                            $result[] = $tag->term_id;
                        }
                    }
                    $this->cache['post_tag_ids_people'] = $result;
                }
                return $result;
            break;
        } // #end custom template vars
        return parent::__get($name);
    }
    // contains most of the theme's custom public functionality
    // the code here mostly deals with the output
    // code for getting data are outsourced to theme-base and functions
    function __call ($name, $args = array()) 
    {
        $this->no_echo = FALSE;
        switch ($name) 
        {

        }
        trigger_error('Method not found', E_USER_WARNING);
    }
    // gateway
    function get_image ($args) 
    {
        $defaults = 
            array('query' => 
                    array('post_parent' => $this->post->ID
                        , 'post_status' => 'inherit'
                        , 'post_type' => 'attachment'
                        , 'post_mime_type' => 'image'
                        , 'numberposts' => -1
                        , 'orderby' => 'menu_order'
                        , 'order' => 'ASC'
                        , 'output' => ARRAY_A
                        // see get_posts()
                    )
                , 'type' => NULL
                , 'size' => 'large'
                , 'tip_key' => 'post_title'
                , 'default_attachment_id' => 0
                , 'default_attachment_size' => '' // full
                , 'filters' => 
                    array('image_location' => 1
                        , 'image_number' => 1
                        , 'stretch_width' => FALSE
                        , 'stretch_height' => FALSE
                        , 'no_background' => FALSE
                    )
            );
        extract(WPHF::md_wp_parse_args($args, $defaults));
        $type = WPHF::exists($type) ? $type : $size;
        // set up filters
        if ($filters['stretch_width']) 
        {
            $min_width = ( ! is_numeric($filters['stretch_width'])) ? get_option($size) : $filters['stretch_width'];
        }
        if ($filters['stretch_height']) 
        {
            $min_width = ! is_numeric($filters['stretch_height']) ? get_option($size) : $filters['stretch_height'];
        }
        // get attachments
        $images = $this->query_images($query, $size, $type, $default_attachment_id, $default_attachment_size, $tip_key);
        if ($query['numberposts'] > 1 OR $query['numberposts'] == -1) 
        {
            return $images;
        }
        // a selection to filter
        elseif (count($images) != 1 AND $diff = array_diff_assoc($defaults['filters'], $filters) AND empty($diff)) 
        {
            $images = $this->find_images($filters, $images);
            if (count($images) > 0 AND $filters['image_number'] > 1) // multiple matches
            {
                return $images;
            }
        }
        // just one image
        $images = array_shift($images);
        return $images;
    }
    // workhorse
    function query_images ($query, $size, $type, $default_id, $default_size, $tip_key, $src_keys = array('src', 'width', 'height')) 
    {
        $images = get_children($query);
        if ( ! empty($images)) 
        {
            foreach ($images as $id => &$image) 
            {
                $image = (array) $image;
                if ( ! empty($image)) 
                {
                    $file = wp_get_attachment_image_src($id, $size);
                    if (WPHF::_is_array($file)) 
                    {
                        $image['file'] = WPHF::array_combine_uneven($src_keys, array_values($file)); 
                        $image['file']['title'] = $image[$tip_key];
                        $image['file']['alt'] = $image['post_title'];
                        $image['file']['id'] = WPHF::underscore("{$size} {$image['post_title']}");
                        $image['file']['class'] = $type;
                    }
                }
                else 
                {
                    unset($image);
                }
            }
        }
        else // no images attached, get default image
        {
            $file = wp_get_attachment_image_src($default_id, $default_size);
            $image = array(); 
            $image['file'] = WPHF::array_combine_uneven($src_keys, array_values($file));
            $image['file']['alt'] = 'default';
            $image['file']['id'] = WPHF::underscore("{$size} default");
            $image['file']['class'] = $size;
            $images[] = $image;
        }
        return $images;
    }
    // extra
    function find_images ($filters, $images) 
    {
        $is_second_attempt = FALSE;
        $found = array();
        do {
            for ($i = 0; $i < count($images); $i++) { // foreach loop breaks order
                $image = current($images);
                if (    (($i + 1) == count($images) AND $is_second_attempt)
                    OR  ($image->menu_order == $filters['image_location'] 
                    AND  ( ! $filters['no_background'] OR ($filters['no_background'] AND in_array(pathinfo($image['src'], PATHINFO_EXTENSION), array('png', 'gif'))))
                    AND  (( ! $filters['stretch_width'] OR ($filters['stretch_width'] AND $image['width'] >= $min_width)) 
                    AND   ( ! $filters['stretch_height'] OR ($filters['stretch_height'] AND $image['height'] >= $min_height))
                    )) // checks all filters
                ) {
                    $found[] = $image;
                    break;
                }
                next($images);
            }
            if ( ! $is_second_attempt) { // desired image doesn't fit
                $is_second_attempt = TRUE;
            }
        }
        while (empty($found) AND ! $is_second_attempt);
        // if nothing matches, the last one is returned
        return $found;
    }
    // used with wp_list_pages
    function nav_add_section_description ($output) // adds category description to output
    {
        $pages = explode('<li', $output);
        $web_root = trailingslashit(get_bloginfo('url'));
        foreach ($pages as &$page) 
        {
            $matches = array();
            preg_match('/href="([^"]+)"/i', $page, $matches);
            // match base-theme's format
            // _log($matches);
            if ($url = WPHF::element(1, $matches) AND $url === FALSE) 
            {
                continue;
            }
            $cat_slug = WPHF::underscore(str_replace($web_root, '', $url), TRUE, TRUE); // do i, is path
            if ($cat_id = $this->{"cat_id_$cat_slug"} AND $cat_id !== FALSE) 
            {
                $category = get_category($cat_id);
                $page = preg_replace('/title\="[^"]*"/i', 'title="' . esc_attr($category->description) . '"', $page);
            }
        }
        $output = implode('<li', $pages);
        return $output;
    }
    function nav_filter_link_names ($output) 
    {
        $pages = explode('<li', $output);
        foreach ($pages as &$page) 
        {
            $page = str_replace('<span class="amp">&</span>', '&amp;', html_entity_decode($page));
            // _log($page);
            $page = stripslashes(preg_replace('/(<a[^>]*><span[^>]*>)([^<]+)(<\/span><\/a>)/ie'
                , '\'$1\' .  WPOT::truncate_abbr(strip_tags(\'$2\'), 20) . \'$3\'', $page));
            // _log($page);
        }
        $output = implode('<li', $pages);
        return $output;
    }
    /**
     * @see     class Walker_Page, function start_el
     */
    function nav_link_post_to_page ($output, $count = FALSE, $smarttip = FALSE) 
    // matches current query category against preg_match section $output
    // otherwise checks custom routing map
    {
        if ($this->is_single) 
        {
            $pages = explode('<li', $output);
            $web_root = trailingslashit(get_bloginfo('url'));
            $current_category = WPHF::element($this->wp_query->query['category_name'], $this->categories); // key is slug
            if ( ! $current_category) 
            {
                return $output;
            }
            foreach ($pages as &$page) 
            {
                preg_match('/href="[-a-z:_\/]+\/([-a-z]+)"/i', $page, $matches); // get the last part
                // match wp format
                if (count($matches) < 2) 
                {
                    continue;
                }
                $section_category = WPHF::element($matches[1], $this->categories);
                if ($section_category AND $section_category->slug == $current_category->slug) 
                {
                    $page = str_replace('class="', 'class="current_page_parent ', $page);
                }
                else 
                {
                    $custom_routing = WPHF::element('children', WPHF::element($matches[1], $this->routing));
                    if ($custom_routing AND in_array($current_category->slug, $custom_routing))
                    {
                        $page = str_replace('class="', 'class="current_page_ancestor ', $page);                    
                    }
                }
            }
            $output = implode('<li', $pages);
        }
        return $output;
    }
    function check_and_update_event ($post_update) 
    {
        $timestamp_end = strtotime(get_post_meta($this->post, 'end_date_time', TRUE));
        if ($timestamp_end < time() AND $this->is_category_events_upcoming) // update events date
        {
            $post_update['post_category'] = array($this->cat_id_events, $this->cat_id_events_past);
        }
        return $post_update;
    }
}

/* End of file class.theme.php */
/* Location: ./library/class.theme.php */