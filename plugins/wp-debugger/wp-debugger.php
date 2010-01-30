<?php
/*
    Plugin Name: WordPress Debugger
    Plugin URI: http://pengxwang.com/
    Description: Development and scaffolding tool
    Author: Peng X Wang
    Version: 1.1
    Author URI: http://pengxwang.com/
*/

/*
    Warning: before disabling this plugin, all global functions declared in this file
    must be redeclared in your own theme bootstrap, aka `functions.php`
    Alternatively, you can change the theme to `default` and reactivate the plugin
*/

// ignore certain files
// WPDG::$ignore_files[] = 'dir/file.php';

// Don't modify anything below this line

// plugin exists
define('WP_DEBUGGER', TRUE);

// link to files
require_once realpath(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'firephp.php';
WPDG::$file = realpath(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'log.txt';

// check if wp-admin
if ( ! defined('IS_ADMIN')) // if not using custom theme
    { define('IS_ADMIN', (is_admin() OR basename($_SERVER['PHP_SELF'], '.php') == 'wp-login')); }
if (IS_ADMIN AND ( ! defined('MY_DEBUG') OR ! MY_DEBUG) AND ! WP_DEBUG) // disable for wp-admin code, less strict
    { error_reporting(0); } 
elseif (defined('MY_DEBUG') AND MY_DEBUG)
    { error_reporting(E_ALL); }

// init accordingly
WPDG::enable();

class WPDG {
    
    static $enabled = FALSE;
    static $instance = NULL;
    static $show_all = FALSE;
    static $trace_errors = FALSE;
    static $ignore_files = array();
    static $write_to_file = FALSE;
    static $file = NULL;
    static $label = NULL;
    static $summarize_objects = FALSE;
    
    static $levels = 
        array(E_ERROR               => 'Error'
            , E_WARNING             => 'Warning'
            , E_PARSE               => 'Parsing Error'
            , E_NOTICE              => 'Notice'
            , E_CORE_ERROR          => 'Core Error'
            , E_CORE_WARNING        => 'Core Warning'
            , E_COMPILE_ERROR       => 'Compile Error'
            , E_COMPILE_WARNING     => 'Compile Warning'
            , E_USER_ERROR          => 'User Error'
            , E_USER_WARNING        => 'User Warning'
            , E_USER_NOTICE         => 'User Notice'
            , E_STRICT              => 'Runtime Notice'
            );
    
    static function error_reporting_on () 
    {
        $temp = $level = error_reporting(E_ALL);
        error_reporting($temp);
        return ($level !== 0);
    }
    
    static function enable () 
    {
        self::$instance =& FirePHP::getInstance(TRUE);
        self::$instance->setEnabled(self::error_reporting_on());
        if (self::$instance->getEnabled()) 
        {
            ob_start();
            error_reporting(E_ALL);
            set_error_handler('wpdg_error');
        }
        self::$enabled = TRUE;
    }
    static function handle_error ($severity, $message, $filepath, $line) 
    {
        if (self::$trace_errors) { self::show_trace(); }
        $severity_str = ( ! isset(self::$levels[$severity])) ? $severity : self::$levels[$severity];
        preg_match('/[^\/]+\/[^\/]+\.[a-z]+$/i', str_replace(DIRECTORY_SEPARATOR, '/', $filepath), $matches);
        $filepath = $matches[0];
        if (in_array($filepath, self::$ignore_files) AND ($severity == E_NOTICE OR $severity == E_WARNING)) return;
        $label = implode(' | ', array($severity_str, $filepath, $line));
        switch ($severity) 
        {
            case E_ERROR: case E_USER_ERROR: 
            case E_PARSE: 
            case E_COMPILE_ERROR:
            case E_CORE_ERROR:
                
                self::show_error($message, $label);
                // WPDG::show_trace($label);
                
            break; case E_WARNING: case E_USER_WARNING: 
            case E_NOTICE: case E_USER_NOTICE: 
            case E_COMPILE_WARNING:
            case E_CORE_WARNING:
                
                self::show_warning($message, $label);
                
            break;
        }
        if (self::$show_all) 
        {
            $message = 'An error outside of supported FirePHP errors. Catching it anyway';
            self::show_log($message, $label);            
        }
        return; // don't show anything else
    }
        
    static function show_error ($object, $label = NULL) 
    {
        if ( ! self::$write_to_file)
        {
            return self::$instance->error($object, $label);
        }
        else
        {
            return self::write_to_file($object, $label);
        }
    }
    
    static function show_warning ($object, $label = NULL) 
    {
        if ( ! self::$write_to_file)
        {
            return self::$instance->warn($object, $label);
        }
        else 
        {
            return self::write_to_file($object, $label);
        }
    }
    
    static function show_trace ($label = 'trace') 
    {
        return self::$instance->trace($label);
    }
    
    static function show_log ($object, $label) 
    {
        if ( ! self::$write_to_file)
        {
            return self::$instance->log($object, $label);
        }
        else 
        {
            return self::write_to_file($object, $label);
        }
    }

    static function show_info ($object, $label) 
    {
        if ( ! self::$write_to_file)
        {
            return self::$instance->info($object, $label);
        }
        else 
        {
            return self::write_to_file($object, $label);
        }
    }
    
    static function show_dump ($var, $key) 
    {
        return self::$instance->warn($var, $key);
    }
    
    static function write_to_file ($object, $label) 
    {
        if ( ! is_scalar($object)) 
        {
            $object = var_export($object, TRUE);
        }
        $line = $object;
        if ($label) 
        {
            $line = sprintf(date(DateTime::ATOM) . ' : %1$s : %2$s' . PHP_EOL, $label, $line);        
        } 
        return file_put_contents(self::$file, $line, FILE_APPEND);
    }
}

function wpdg_error ($severity, $message, $filepath, $line) 
{
    WPDG::handle_error($severity, $message, $filepath, $line);
    return TRUE;
}

function _log ($object = 'trace', $label = 'php') 
{
    if (WPDG::$enabled) 
    {
        $function = 'show_log';
        if ($object == 'trace') 
        {
            $function = 'show_trace';
            if ($label != 'php') 
            {
                WPDG::show_log($label, 'php');
            }
        }
        elseif (is_array($object) OR is_object($object)) 
        {
            if (is_object($object) AND WPDG::$summarize_objects) 
            {
                $object = array_keys((array) $object);
            }
            $label = $label == 'log' ? 'info' : $label;
            $function = 'show_info';
        }
        if (isset(WPDG::$label) AND ! empty(WPDG::$label)) 
        {
            $label = sprintf('%1$s -> %2$s', WPDG::$label, $label);
        }
        WPDG::$function($object, @$label);
    }
    // chaining
    return $object;
}

// TODO - untested
function _dump ($var, $key = 'dump') 
{
    WPDG::show_dump($var, $key);
}

// one-liners
function _log_file ($object = 'trace', $label = 'log') 
{
    WPDG::$write_to_file = TRUE;
    WPDG::write_to_file($object, $label);
    WPDG::$write_to_file = FALSE;
}
