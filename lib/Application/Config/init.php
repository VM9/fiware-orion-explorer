<?php

namespace Application\Config;
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of init
 *
 * @author Leonan Carvalho
 */
final class init {

    //Standalone
    public static $initialized = false;
    
    //Global Config
    private $_config;
    //Development Config
    private $_config_dev;
    //Production Config
    private $_config_production;

    /**
     * Classe geradora de configuração do sistema
     * @param string $environment define o ambiente do sistema
     * @param array $config Adiciona ou Sobrescreve alguma configuração
     * @param boolean $defineall Registra todas as configs em super globais
     * @param string $definekey Chave única para não haver duplicidade nas configs
     * @author leonan.carvalho
     */
    function __construct($environment = "DEV", $config = array(), $defineall = true, $definekey = "CFG") {
        
        $this->_config = include('config_global.php');
        $this->_config_dev = include('config_dev.php');
        $this->_config_production = include('config_production.php');
        
        

        if ($environment == "DEV") {
            $this->_config = $this->_config_dev + $this->_config;
        } else {
            $this->_config = $this->_config_production + $this->_config;
        }

        $this->_config = $config + $this->_config;
        
        if (!self::$initialized && $defineall) {
            foreach ($this->_config as $key => $value) {
                define($definekey . "_" . str_replace(".", "_", $key), $value);
            }
        }
        self::$initialized = true;
    }

    public function get($key) {
        if (array_key_exists($key, $this->_config)) {
            return $this->_config[$key];
        } else {
            return null;
        }
    }

    public function getGroup($prefix) {
        $found = array();
        foreach ($this->_config as $key => $value) {
            $frag = explode(".", $key);
            if ($frag[0] == $prefix) {
                $found[$key] = $value;
            }
        }

        return $found;
    }

    public function getAll() {
        return $this->_config;
    }

}
