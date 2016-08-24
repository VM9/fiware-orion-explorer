<?php
namespace Application;

/**
 * Description of App
 *
 * @author Leonan Carvalho
 */
class Figuardian  {
    public static $_config;
    private static $_privateToken;
    public  static $_publicToken;
    
    public function __construct() {
        $this->_config = self::getConfig();
    }
    
    public static function getConfig(){
        if (empty(self::$_config)) {
            self::$_config = new Config\init();
        }
        return self::$_config;
    }
}
