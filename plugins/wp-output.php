<?php
/*
    Plugin Name: WordPress Output
    Plugin URI: http://pengxwang.com/
    Description: Theme helper functions
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

define('WP_OUTPUT', TRUE);

class WPOT {
    
    static function ln ($str) 
    {
        if (WPHF::_is_array($str)) 
        {
            foreach ($str as $s) 
            {
                self::ln($s);
            }
        }
        echo $str . PHP_EOL;
    }
    
    static function tag ($name, $data = '', $attr = array(), $do = TRUE, $esc_html = FALSE) // for chaining
    {
        if ( ! $do) 
        {
            return $data;
        }
        if ( ! WPHF::_is_scalar($data)) 
        {
            $data = '';
        }
        trim($data);
        $data = $esc_html ? esc_html($data) : $data;
        $attr = WPHF::parse_attributes($attr);
        if (self::is_tag($name)) // standard tag
        {
            if (strlen($data) == 0 AND ! in_array($name, array('script'))) 
            {
                return '';
            }
            $data = sprintf('<%1$s%3$s>%2$s</%1$s>', $name, $data, $attr);
        }
        elseif (self::is_tag($name, FALSE)) 
        {
            $data = sprintf('<%1$s%2$s />', $name, $attr);        
        }
        return $data;
    }
    static private function is_tag ($name, $has_child = TRUE) 
    {
        $tags_child = explode(' ', 'a abbr acronym address blockquote big caption cite code dl dd dt div em fieldset form h1 h2 h3 h4 h5 h6 ins label legend li ol ul noscript object optgroup option select p pre q samp span strong sub sup table tbody tr th td thead tfoot tt script style');
        $tags_no_child = explode(' ', 'link hr img input meta');
        $array = $has_child ? 'tags_child' : 'tags_no_child';
        return in_array($name, $$array);
    }
    /**
     * @param   $length     int     minimum number of characters, if possible
     */
    static function truncate ($str, $delim = ' ', $suffix = '&hellip;', $length = 150, $start = 0) 
    {
        if ($delim == ' ' AND strlen($str) < $length) 
        {
            return $str;
        }
        if ($delim == '. ') 
        {
            // flag fake sentences, abbreviations, etc.
            $str = preg_replace('/(\b[A-Z][A-Za-z0-9]+)(\.\s)/', '$1%period%', $str);
        }
        // try
        $substr = substr($str, $start, $length);
        $pos = strrpos($substr, $delim);
        // then
        if ($pos === FALSE) // nothing to trim
        {
            $pos = strpos($str, $delim, $start);
            $substr = substr($str, $start, $pos + 1);
            if (strlen($substr) == 1) // string is one unit 
            {
                $substr = $str;
            }
        }
        else 
        {
            $substr = substr($substr, 0, $pos + 1);
        }
        if ($delim == '. ') 
        {
            // revert fake sentences
            $substr = str_replace('%period%', '. ', $substr);
        }
        return rtrim($substr) . (($substr == $str) ? '' : ' ' . $suffix);
    }
    static function truncate_abbr ($str, $length = NULL) 
    {
        if (strlen($str) >= $length OR ! isset($length)) 
        {
            $abbr = '';
            foreach (explode(' ', $str) as $word) 
            {
                $word = strtolower(trim($word, "\s."));
                if ( ! in_array($word
                        , array('a', 'an', 'and', 'as', 'at', 'but', 'by', 'en', 'for', 'if', 'in', 'of'
                            , 'on', 'or', 'the', 'to', 'via', 'vs', 'v')))
                    { $abbr .= $word[0]; }
            }
            return strtoupper($abbr);
        }
        else 
        {
            return $str;
        }
    }
    
    /**
     * Templating
     * @link    http://us2.php.net/manual/en/function.vsprintf.php#89349
     */
    static function dsprintf () 
    {
        $data = func_get_args(); // get all the arguments
        $string = array_shift($data); // the string is the first one
        if (is_array(func_get_arg(1))) 
        { // if the second one is an array, use that
            $data = func_get_arg(1);
        }
        $used_keys = array();
        // get the matches, and feed them to our function
        $string = preg_replace('/\%\((.*?)\)(.)/e'
            , 'self::dsprintf_match(\'$1\', \'$2\', \$data, \$used_keys)',$string); 
        $data = array_diff_key($data, $used_keys); // diff the data with the used_keys
        return vsprintf($string, $data); // yeah!
    }
    private static function dsprintf_match ($m1, $m2, &$data, &$used_keys) 
    {
        if (isset($data[$m1])) 
        { // if the key is there
            $str = $data[$m1];
            $used_keys[$m1] = $m1; // dont unset it, it can be used multiple times
            return sprintf("%" . $m2, $str); // sprintf the string, so %s, or %d works like it should
        } 
        else 
        {
            return "%" . $m2; // else, return a regular %s, or %d or whatever is used
        }
    }
}