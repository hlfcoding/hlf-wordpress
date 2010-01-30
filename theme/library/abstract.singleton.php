<?php  if (! defined('MY_BASEPATH')) exit('No direct script access allowed');

abstract class Singleton {

    /**
     * @var     object      superglobal
     */
    private static $instance;
    
    public function __construct () 
    {
        self::$instance =& $this;
    }
    public static function &get_instance () 
    {
        return self::$instance;
    }
}

/* End of file abstract.singleton.php */
/* Location: ./library/abstract.singleton.php */