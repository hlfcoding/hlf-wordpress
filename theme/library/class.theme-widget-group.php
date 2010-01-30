<?php  if (! defined('MY_BASEPATH')) exit('No direct script access allowed');

/**
 * @version     1.0
 */

class MY_Theme_Widget_Group {
    
    var $groups = array();
    
    function __construct ($args = array()) 
    {
        // TODO - add more
        $defaults = 
            array('visible_name' => 'My Widgets Group ' . $this->num_groups
                , 'id' => 'widgets-' . $this->num_groups
                , 'widget_class' => 'widget'
                , 'widget_tag' => 'li'
                , 'title_class' => 'widgettitle'
                , 'title_tag' => 'h2'
           );
        $args = wp_parse_args($args, $defaults);
        $groups[$args['id']] = $args;
        extract($args);
        $sidebar = 
            array('name' => $visible_name
                , 'id' => $id
                , 'before-widget' => "<$widget_tag id=\"%1\$s\" class=\"$widget_class %2\$s\">"
                , 'after-widget' => "</$widget_tag>"
                , 'before-title' => "<$title_tag class=\"$title_class\" >"
                , 'after-title' => "</$title_tag>"
           );
        // _log($sidebar);
        $this->register($sidebar);
    }
    
    function register ($args) 
    {
        register_sidebar($args);
        $this->groups++;
    }
    
    function __get ($name) 
    {
        switch (TRUE) 
        {
            case ($name == 'num_groups'):
                return count($this->groups) + 1;
            break;
        }
    }
    
    function __set ($name, $value) 
    {
        return $value;
    }
}

/* End of file class.theme-widget-group.php */
/* Location: ./library/class.theme-widget-group.php */