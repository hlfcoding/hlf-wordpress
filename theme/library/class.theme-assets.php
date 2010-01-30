<?php  if (! defined('MY_BASEPATH')) exit('No direct script access allowed');

/**
 * @version     1.0
 */

class MY_Theme_Assets {
    
    protected $handle, $url, $store;
    protected $dirs = 
        array('styles' => 'css'
            , 'scripts' => 'js'
       );
    protected $stores = 
        array('styles' => array()
            , 'scripts' => array()
       );
    
    function __construct ($args)
    {
        foreach ($args as $key => $value) 
        {
            if (property_exists($this, $key)) 
            {
                $this->{$key} = $value;
            }
        }
    }
    
    function __set ($name, $value) 
    {
        return TRUE;
        return FALSE;
    }
    
    function __get ($name)
    {
        if (array_key_exists($name, $this->stores)) 
        {
            $this->_get_assets($name);
            switch ($name) 
            {
                case 'styles':
                    return $this->stores['styles']['normal'];
                break; case 'scripts':
                    return $this->stores['scripts']['normal'];
                break; default:
                    return $this->stores[$name];
                break;
            }
        }
        else switch ($name) 
        {
            case 'styles_ie':
                return $this->stores['styles']['ie'];
            break; case 'styles_media':
                return $this->stores['styles']['media'];
            break; case 'scripts_ie':
                return $this->stores['scripts']['ie'];
            break; 
        }
        return FALSE;
    }
    
    function _get_assets ($type) 
    {
        if ( ! empty($this->stores[$type])) 
            { return; }
        $extensions = $this->_get_extensions($type);
        $store =& $this->stores[$type];
        $path = WPHF::d_path(array($this->handle, $this->dirs[$type]));
        $url = WPHF::path(array($this->url, $this->dirs[$type]));
        $store = WPHF::directory_map($path, FALSE, $extensions);
        $store = WPHF::md_array_flatten($store, TRUE);
        asort($store);
        foreach ($store as &$file) 
        {
            $path = pathinfo($file);
            $file = 
                array('file' => $path['filename']
                    , 'url' => WPHF::path(array($url, $file))
                    , 'deps' => array()
                    , 'ver' => MY_TVERSION
               );
        }
        if ($method = '_filter_' . $type AND method_exists($this, $method)) 
        // use custom sort method if possible
        {
            $this->$method();
        }
        else 
        // move `_file.ext` to the bottom of the list
        {
            foreach ($store as $key => $file) 
            {
                if (strncmp($file['file'], '_', 1) == 0) 
                {
                    $store[] = $store[$key];
                    unset($store[$key]);
                }
            }
        }
    }
    function _get_extensions ($type) 
    {
        return explode(' ', strtr($type, array('styles' => 'css', 'scripts' => 'js')));
    }
    // TODO - parse file header to get real version
    function _filter_styles () 
    {
        $store =& $this->stores['styles'];
        $store_new = 
            array('normal' => array()
                , 'ie' => array()
                , 'media' => array()
           );
        // separate
        foreach ($store as $stylesheet)
        {
            extract($stylesheet);
            $vars = array_keys($stylesheet);
            $media = 'all';
            $vars[] = 'media';
            $store_sub = 'normal';
            $is_media_style = ($media_matches = $this->is_media_style($url) AND $media_matches !== FALSE);
            $is_ie_style = ($ie_matches = $this->is_ie_style($url) AND $ie_matches !== FALSE);
            if ($is_media_style) 
            {
                list(, $media) = $media_matches;
                $store_sub = 'media';
            }
            elseif ($is_ie_style) 
            {
                list(, $ie_op, $ie_ver) = $ie_matches;
                $vars = array_merge($vars, array('ie_op', 'ie_ver'));
                $store_sub = 'ie';
            }
            if ($is_media_style OR $is_ie_style) 
            {
                $vars[] = 'tag';
                $tag = WPOT::tag('link', '',
                    array('href' => $url . '?ver=' . $ver
                        , 'rel' => 'stylesheet'
                        , 'type' => 'text/css'
                        , 'media' => $media
                        , 'id' => $file . '-css'
                   )) . PHP_EOL;
            }
            $stylesheet = compact($vars);
            $store_new[$store_sub][] = $stylesheet;
        }
        $store = $store_new;
        // sort
        usort($store['ie'], array($this, '_sort_ie_styles'));
    }
    function _sort_ie_styles ($a, $b) 
    {
        extract($a, EXTR_PREFIX_ALL, 'a');
        extract($b, EXTR_PREFIX_ALL, 'b');
        $shift = 1;
        switch (TRUE) 
        {
            case (strpos($a_ie_op, 'gte') !== FALSE
                 AND (strpos($b_ie_op, 'lte') !== FALSE OR (empty($b_ie_op) AND ! empty($b_ie_ver)))):
            case ($a_ie_ver > $b_ie_ver AND ! empty($b_ie_ver)):
            case (empty($a_ie_ver)):
                $shift = -1;
            break;
            case ($a_ie_ver == $b_ie_ver):
                $shift = 0;
            break;
        }
        return $shift;
    }
    function is_media_style ($url) 
    {
        return $this->_file_match(
              "/[-_\.]?(screen|print|mobile)\.css$/i"
            , $url
            , 2
           );
    }
    function is_ie_style ($url) 
    {
        return $this->_file_match(
              "/\/[-a-z_\.]?[0-9]{0,2}[-_\.]?(lt|gt|lte|gte|)[-_\.]?ie(6|7|8|)\.css$/i"
            , $url
            , 3
           );
    }
    // TODO - parse file header to get real version
    function _filter_scripts () 
    {
        $store =& $this->stores['scripts'];
        // get deps
        $deps_global = array();
        $deps_plugin = array();
        foreach ($store as $script)
        {
            extract($script);
            $pieces = explode('.', $file);
            $len = count($pieces);
            // library handle
            if ($len >= 2 AND ! in_array($pieces[0], $deps_global)) 
            {
                $deps_global[] = $pieces[0];
            }
            // plugin handle
            if ($len >= 3 AND ! in_array($pieces[1], $deps_plugin) AND ! WPHF::has_segment('widgets', $url)) 
            {
                $deps_plugin[] = $file;
            }
        }
        foreach ($store as &$script) 
        {
            extract($script);
            $pieces = explode('.', basename($file));
            $len = count($pieces);
            if ($len >= 2) 
            {
                $script['deps'] = array_merge($script['deps'], $deps_global);
            }
            if ($len < 3) 
            {
                $script['deps'] = array_merge($script['deps'], $deps_plugin);
            }
        }
        // add ie substore
        $store_new = 
            array('normal' => array()
                , 'ie' => array()
           );
        foreach ($store as &$script)
        {
            extract($script);
            $vars = array_keys($script);
            $store_sub = 'normal';
            $is_ie_script = ($matches = $this->is_ie_script($url) AND $matches !== FALSE);
            if ($is_ie_script) 
            {
                list(, $ie_op, $ie_ver, $ie_plugin) = $matches;
                if ($ie_plugin == 'png') 
                {
                    $ie_op = 'lte';
                    $ie_ver = 6;
                }
                $vars = array_merge($vars, array('ie_op', 'ie_ver'));
                $store_sub = 'ie';
                // make tag
                $vars[] = 'tag';
                $tag = WPOT::tag('script', '',
                    array('src' => $url . '?ver=' . $ver
                        , 'type' => 'text/javascript'
                        , 'charset' => 'UTF-8' // TODO - _assumption
                   )) . PHP_EOL;
            }
            $store_new[$store_sub][] = compact($vars);
        }
        $store = $store_new;
    }
    function is_ie_script ($url) 
    {
        // as long as: _01_lte_ie6_foopngfix.js
        // as short as: iepngfix.js
        return $this->_file_match(
              "/\/[a-z_\.\-]*[0-9]{0,2}[_\.\-]*(lt|gt|lte|gte|)[_\.\-]*ie(6|7|8|)(png|)[a-z_\.\-]*\.js$/i"
            , $url
            , 4
           );
    }
    function _file_match ($pattern, $file, $num_matches = 1) 
    {
        if (preg_match($pattern, trim($file), $matches) AND count($matches) == $num_matches) 
        {
            // _log($matches);
            return $matches;
        }
        else 
        {
            return FALSE;
        }
    }
}

/* End of file class.theme-assets.php */
/* Location: ./library/class.theme-assets.php */