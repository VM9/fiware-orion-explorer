<?php

namespace Application;

/**
 * Description of Util
 *
 * @author Leonan Carvalho
 */
class Util {

    public static function PrintJson($obj, $header = true) {
        if ($header) {
            header("Content-Type: application/json");
        }

        echo json_encode($obj);
    }
    
    public static function getBaseurl($includeHost = true) {
        if (PHP_SAPI !== "cli") {
            //Constrói a base da aplicação dinamicamente
            $requestscheme = (isset($_SERVER['REQUEST_SCHEME'])) ? $_SERVER['REQUEST_SCHEME'] . "://" : "http" . "://";
            $requesthost = $_SERVER['HTTP_HOST'];
            $self_php = str_replace('index.php', '', $_SERVER['PHP_SELF']);
            if(!$includeHost){
                return ($self_php == "//")? "/" : $self_php;
            }
            if ($self_php == "//") {
                $baseurl = $requestscheme . $requesthost . '/';
            } else {
                $baseurl = $requestscheme . $requesthost . $self_php;
            }

            $url_part = array_reverse(explode('backend', $baseurl));
            $baseurl = end($url_part);
        }
        
        return $baseurl;
    }

}
