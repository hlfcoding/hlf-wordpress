<?php
/*
    Plugin Name: WordPress Helper Functions
    Plugin URI: http://pengxwang.com/
    Description: Additional utility tools.
    Author: Peng X Wang
    Version: 1.1
    Author URI: http://pengxwang.com/
*/

/*
    Warning: before disabling this plugin, all global functions declared in this file
    must be redeclared in your own theme bootstrap, aka `functions.php`
    Alternatively, you can change the theme to `default` and reactivate the plugin
*/

// plugin exists
define('WP_HELPER_FUNCTIONS', TRUE);

class WPHF {
    
    // var functions
    /**
     * @link    http://us3.php.net/manual/en/function.empty.php#88848
     */
    static function _empty () 
    { 
        foreach (func_get_args() as $args) // check multiple
        {
            if ( ! is_numeric($args)) 
            { 
                if (is_array($args)) // check array
                { 
                    if (count($args, 1) < 1) // md
                    { 
                        return TRUE;
                    } 
                }
                elseif ( ! isset($args) OR strlen(trim($args)) == 0) // check string
                {
                    return TRUE; 
                } 
            } 
        } 
        return FALSE; 
    }
    
    static function exists ($var)
    {
        $bool = @(isset($var) AND self::_empty($var) === FALSE);
        return $bool;
    }
    static function _is_array ($var) 
    {
        $bool = @(self::exists($var) AND is_array($var));
        return $bool; 
    }
    static function _is_scalar ($var) 
    {
        $bool = @(self::exists($var) AND is_scalar($var) AND ! is_bool($var));
        return $bool;
    }
    
    static function custom_else_default ($custom, $default = NULL, $echo = FALSE) 
    {
        if ( ! isset($default)) 
        {
            if (is_array($custom)) 
            {
                // _log(__METHOD__);
                $default = array();
            }
            elseif (is_string($custom)) 
            {
                $default = '';
            }
            elseif (is_numeric($custom)) 
            {
                $default = 0;
            }
        }
        $var = (self::exists($custom)) ? $custom : $default;
        if ($echo !== FALSE)
        {
            echo $var;
        }
        return $var;
    }
    
    // array functions
    static function element ($item, $array, $default = FALSE)
    {
        if ( ! isset($array[$item]) OR $array[$item] == "")
        {
            return $default;
        }
        return $array[$item];
    }
    
    static function element_ref ($item, &$array, $default = FALSE) 
    {
        if ( ! isset($array[$item]) OR $array[$item] == "")
        {
            return $default;
        }
        return $array[$item];        
    }
    
    static function element_lottery ($num_cycle, $num_show, $array = array())
    {
        $i = 0;
        $winners = $array;
        $is_array = self::_is_array($array);
        if ($is_array) 
        {
            $num_cycle = count($array);
        }
        if ($num_cycle > 1) 
        {
            $winners = array(); // clear
            while ($i < $num_show) 
            {
                $i++;
                do {
                    $winner = rand(1, $num_cycle);
                } while (in_array($winner, $winners));
                if ($is_array AND $e = self::element($winner - 1, array_values($array)) AND $e !== FALSE) 
                {
                    $winners[] = $e;
                }
                else 
                {
                    $winners[] = $winner;
                }
            }
        }
        return $winners;
    }
    
    /**
     * @link    http://us2.php.net/manual/en/function.array-values.php#89450
     */
    static function md_implode ($array, $glue = '')
    {
        if (is_array($array))
        {
            $output = '';
            foreach ($array as $v)
            {
                $output .= self::md_implode($v, $glue);
            }
            return $output;
        }
        else
        {
            return $array . $glue;
        }
    }
    static function md_array_flatten ($md_array)
    {
        $flat_array = explode('#|#', self::md_implode($md_array, '#|#')); // "#|#" is a sample delimiter
        array_pop($flat_array); // to remove last empty element
        return $flat_array;
    }
    
    static function md_extract (&$array, $type = EXTR_OVERWRITE, $prefix = '')
    {
        $vars = array();
        if ( ! self::_is_array($array))
        {
            return trigger_error ('extract_nested (): First argument should be an array', E_USER_WARNING);
        }
        if ( ! empty($prefix) AND ! preg_match ('#^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$#', $prefix))
        {
            return trigger_error ('extract_nested (): Third argument should start with a letter or an underscore', E_USER_WARNING);
        }
        if (($type == EXTR_PREFIX_SAME OR $type == EXTR_PREFIX_ALL OR $type == EXTR_PREFIX_IF_EXISTS) AND empty ($prefix))
        {
            return trigger_error ('extract_nested (): Prefix expected to be specified', E_USER_WARNING);
        }
        if ( ! empty($prefix)) 
        {
            $prefix .= '_';        
        }
        foreach ($array as $key => $val)
        {
            if ( ! self::_is_array($array[$key]))
            {
                switch ($type)
                {
                    default: case EXTR_OVERWRITE:
                        $vars[$key] = $val;
                    break; case EXTR_SKIP:
                        $vars[$key] = self::exists($vars[$key]) ? $vars[$key] : $val;
                    break; case EXTR_PREFIX_SAME:
                        if (self::exists($vars[$key]))
                        {
                            $vars[$prefix . $key] = $val;
                        }
                        else
                        {
                            $vars[$key] = $val;
                        }
                    break; case EXTR_PREFIX_ALL:
                        $vars[$prefix . $key] = $val;
                    break; case EXTR_PREFIX_INVALID:
                        if ( ! preg_match('#^[a-zA-Z_\x7f-\xff]$#', $key{0}))
                        {
                            $vars[$prefix . $key] = $val;
                        }
                        else
                        {
                            $vars[$key] = $val;
                        }
                    break; case EXTR_IF_EXISTS:
                        if (self::exists($vars[$key]))
                        {
                            $vars[$key] = $val;
                        }
                    break; case EXTR_PREFIX_IF_EXISTS:
                        if (self::exists($vars[$key]))
                        {
                            $vars[$prefix . $key] = $val;
                        }
                    break; case EXTR_REFS:
                        $vars[$key] =& $array[$key];
                    break;
                }
            }
            else
            {
                $vars = array_merge($vars, self::md_extract($array[$key], EXTR_PREFIX_ALL, $prefix . self::singular($key)));
            }
        }
        return $vars;
    }
    
    static function array_combine_uneven ($a, $b) 
    { 
        $a_count = count($a); 
        $b_count = count($b); 
        $size = ($a_count > $b_count) ? $b_count : $a_count; 
        $a = array_slice($a, 0, $size); 
        $b = array_slice($b, 0, $size); 
        return array_combine($a, $b); 
    }
    
    static function array_addition ($a, $b) 
    {
        return array_unique(array_merge($a, $b));
    }
        
    static function force_array ($var) 
    {
        if ( ! is_array($var)) 
        {
            if (is_scalar($var)) 
            {
                $var = array($var);
            }
            else 
            {
                $var = array(0);
            }
        }
        return $var;
    }    
    static function array_normal ($array, $force = TRUE, $reduce = FALSE) 
    {
        // _log($array, 'start');
        if ($force) 
        {
            $array = self::force_array($array);
        }
        while ((is_array(self::element(0, $array)) OR ($reduce AND is_array($array))) AND count($array) == 1) 
        {
            $array = array_shift($array);
        }
        // _log($array, 'end');
        return $array;
    }
    
    // path functions
    static function path ($segments, $separator = "/")
    {
        if ( ! is_array($segments)) 
        {
            trigger_error('Segments must be an array.');
            return '';
        }
        $other_separator = ($separator == "/") ? "\\" : "/";
        $first = TRUE;
        foreach ($segments as $i => &$segment) 
        {
            if (is_array($segment)) 
            {
                trigger_error('No multidimensional arrays.');
                return '';
            }
            if (empty($segment))
            {
                unset($segments[$i]);
            }
            $segment = str_replace($other_separator, $separator, ( ! $first ? trim($segment, "\\/") : $segment));
            $first = FALSE;
        }
        // _log($segments);
        return implode($separator, $segments);
    }
    static function d_path ($segments)
    {
        return self::path($segments, DIRECTORY_SEPARATOR);
    }
    
    static function has_segment ($segment, $path, $separator = "/") 
    {
        return in_array(strtolower($segment), explode($separator, strtolower($path)));
    }
    static function has_dir ($segment, $path) 
    {
        self::has_segment($segment, $path, DIRECTORY_SEPARATOR);
    } 
    
    // file functions
    static function directory_map ($source_dir, $top_level_only = FALSE, $matches = array(), $filter_matches = FALSE, $root_dir = NULL)
    {
        if ($fp = @opendir($source_dir))
        {
            $source_dir = rtrim($source_dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
            if ( ! isset($root_dir)) 
            {
                $root_dir = $source_dir;
            }
            $sub_dir = str_replace($root_dir, '', $source_dir);
            $filedata = array();
            while (FALSE !== ($file = readdir($fp)))
            {
                if (strncmp($file, '.', 1) == 0) // `.foo.ext` is a hidden file
                {
                    continue;
                }
                if ( ! empty($matches)) 
                {
                    $info = pathinfo($file);
                    $match = (array_key_exists('extension', $info) AND in_array($info['extension'], $matches));
                    $match = $filter_matches ? ( ! $match) : $match; // filter matches : only matches
                }
                else 
                {
                    $match = TRUE;
                }
                if ($top_level_only == FALSE AND @is_dir($source_dir . $file))
                {
                    $temp_array = array();
                    $temp_array = self::directory_map($source_dir . $file . DIRECTORY_SEPARATOR, FALSE, $matches, $filter_matches, $root_dir);
                    $filedata[$file] = $temp_array;
                }
                elseif ($match)
                {
                    $filedata[] = $sub_dir . $file;
                }
            }
            closedir($fp);
            return $filedata;
        }
    }
    
    // url functions
    static function real_url ($url, $only_absolute = FALSE)
    {
        if ($only_absolute) 
        {
            $url_parsed = parse_url($url);
            if (    ! $url_parsed
                 OR ! $url_parsed['scheme']
                 OR ! $url_parsed['host']
                 OR ! $url_parsed['path']) 
            {
                return FALSE;
            }
            if ( ! ($url_parsed['scheme'] == 'http'
                 OR $url_parsed['scheme'] == 'https'
                 OR $url_parsed['scheme'] == 'ftp')) 
            {
                return FALSE;
            }
        }
        $output = @get_headers($url);
        return $output ? TRUE : FALSE;
    }
    
    // string functions
    static function singular ($str)
    {
        $str = strtolower(trim($str));
        $end = substr($str, -3);
        if ($end == 'ies')
        {
            $str = substr($str, 0, strlen($str) - 3) . 'y';
        }
        elseif ($end == 'ses')
        {
            $str = substr($str, 0, strlen($str) - 2);
        }
        else
        {
            $end = substr($str, -1);
            if ($end == 's')
            {
                $str = substr($str, 0, strlen($str) - 1);
            }
        }
        return $str;
    }
    
    static function plural ($str, $force = FALSE)
    {
        $str = strtolower(trim($str));
        $end = substr($str, -1);
        if ($end == 'y')
        {
            // Y preceded by vowel => regular plural
            $vowels = array('a', 'e', 'i', 'o', 'u');
            $str = in_array(substr($str, -2, 1), $vowels) ? $str . 's' : substr($str, 0, -1) . 'ies';
        }
        elseif ($end == 's')
        {
            if ($force)
            {
                $str .= 'es';
            }
        }
        else
        {
            $str .= 's';
        }
        return $str;
    }
    
    static function possessive ($str) // TODO - `es`, `ies`, etc.
    {
        return "$str'" . (substr($str, -1) == 's' ? '' : 's');
    }
    
    static function camelize ($str, $lower = TRUE)
    {
        if ($lower)     { $str = 'x' . strtolower(trim($str)); }
        $str = ucwords(preg_replace('/[-_.\s]+/', ' ', $str));
        return substr(str_replace(' ', '', $str), 1);
    }
    
    static function underscore ($str, $lower = TRUE, $path = TRUE, $camel = FALSE)
    {
        if ($lower)     { $str = strtolower($str); }
        if ($camel)     { $str = ucwords(preg_replace('/(?:(?<=[a-z])[-_.\s](?=[a-z]))+/i', ' ', $str)); }
        return preg_replace('/[-.\s' . ( $path ? '\/' : '') . ']+/', '_', trim($str));
    }
    
    static function dash ($str, $lower = TRUE)
    {
        if ($lower)     { $str = strtolower($str); }
        return preg_replace('/[_.\s]+/', '-', trim($str));
    }
    
    static function humanize ($str, $lower = TRUE)
    {
        if ($lower)     { $str = strtolower($str); }
        return ucwords(preg_replace('/[_]+/', ' ', trim($str)));
    }
    static function str_replace_f ($search, $replace, $str) // example, to delete a suffix but only have a pattern %1$s_suffix
    {
        if (is_scalar($search) AND is_scalar($replace)) 
        {
            $search = preg_replace('/\%[0-9.+]*\$?[a-z]/i', '', $search); // removes tokens
            $str = str_replace($search, $replace, $str);
        }
        return $str;
    }
    // TODO - untested
    static function _strpos ($haystack, $needles, $insensitive = FALSE, $reverse = FALSE, $offset = 0) 
    {
        if ( ! self::_is_scalar($haystack)) 
        {
            return FALSE;
        }
        if (self::_is_scalar($needles)) 
        {
            $needles = array($needles);
        }
        switch (TRUE) 
        {
            case ($insensitive AND $reverse): 
                $function = 'strripos';
            break; case ($insensitive AND ! $reverse): 
                $function = 'stripos';
            break; case ( ! $insensitive AND $reverse): 
                $function = 'strrpos';
            break; case ( ! $insensitive AND ! $reverse): default: 
                $function = 'strpos';
            break;
        }
        foreach ($needles as $needle) 
        {
            if ($position = $function($haystack, $needle, $offset) AND $position !== FALSE) 
            {
                return array('position' => $position, 'needle' => $needle);
            }
        }
        return FALSE;
    }
    static function has_prefix ($str, $prefix, $return_prefix = FALSE) 
    {
        if (self::_is_array($prefix)) // search entire selection
        {
            foreach ($prefix as $piece) 
            {
                if (self::has_prefix($str, $piece)) 
                {
                    return $return_prefix ? $piece : TRUE;
                }
            }
            return FALSE;
        }
        // _log(array($str, $prefix));
        // _log((strlen($str) > strlen($prefix)), 1);
        // _log((strncmp($str, $prefix, strlen($prefix)) == 0), 2);
        return (strlen($str) > strlen($prefix) AND strncmp($str, $prefix, strlen($prefix)) == 0);
    }
    static function has_suffix ($str, $suffix, $return_suffix = FALSE) 
    {
        if (self::_is_array($suffix)) // search entire selection
        {
            foreach ($suffix as $piece) 
            {
                if (self::has_suffix($str, $piece)) 
                {
                    return $return_suffix ? $piece : TRUE;
                }
            }
            return FALSE;
        }
        return (strlen($str) > strlen($suffix) AND strrpos($str, $suffix) === strlen($str) - strlen($suffix));
    }
    // wordpress
    static function md_wp_parse_args ($args, $defaults, $flat = FALSE, $exceptions = array()) 
    {
        if (self::_is_array($args)) 
        {
            foreach ($args as $key => &$arg) 
            {
                if (self::_is_array($arg) 
                    AND ($e = self::element($key, $defaults) AND self::_is_array($e))
                    AND ! in_array($key, $exceptions)) 
                {
                    $arg = self::md_wp_parse_args($arg, $e);
                }
            }            
        }
        $args = wp_parse_args($args, $defaults);
        if ($flat) 
        {
            $args = self::md_extract($args);
        }
        return $args;
    }
    
    static function parse_attributes ($attributes, $javascript = FALSE)
    {
        if (is_string($attributes))
        {
            return ($attributes != '') ? ' '.$attributes : '';
        }
        $att = '';
        foreach ($attributes as $key => $val)
        {
            if ($key == 'title') 
            {
                $val = esc_attr($val);
            }
            if ( ! empty($val)) 
            {
                if ($javascript)
                {
                    $att .= $key . '=' . $val . ',';
                }
                else
                {
                    $att .= ' ' . $key . '="' . $val . '"';
                }
            }
        }
        if ($javascript AND $att != '')
        {
            $att = substr($att, 0, -1);
        }
        return $att;
    }
    
    static function shift_hook ($type, $name, $function, $priority, $direction = 1) 
    {
        $remove = 'remove_' . $type;
        $remove($name, $function, $priority);
        $add = 'add_' . $type;
        $priority += $direction;
        $add($name, $function, $priority);
    }
    
}