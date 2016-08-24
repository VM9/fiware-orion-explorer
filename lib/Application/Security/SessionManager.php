<?php

namespace Application\Security;

/**
 * Description of SessionManager
 *
 * @author Leonan S. Carvalho
 */
class SessionManager {

    static function sessionStart($name, $limit = 0, $path = '/', $domain = null, $secure = null) {
        // Define o nome do cookie da sessão
//        session_name($name . '_syss');
        // Define nivel SSL
        $https = isset($secure) ? $secure : isset($_SERVER['HTTPS']);
        // Define parâmetros do cookie
        session_set_cookie_params($limit, $path, $domain, $https, true);
        session_start();

        // Verifica se a validade da sessão não expirou
        if (self::validateSession()) {
            // Verifica se essa sessão é nova ou se é um a tentativa "hijacking"
            // http://pt.wikipedia.org/wiki/Session_hijacking

            if (!self::preventHijacking()) {
                // Reset  nos dados da sessão e regenera o ID
                $_SESSION = array();
                $_SESSION['IPaddress'] = $_SERVER['REMOTE_ADDR'];
                $_SESSION['userAgent'] = $_SERVER['HTTP_USER_AGENT'];
                self::regenerateSession();

                // 5% de chance da sessão ser renovada em qualquer request
                // A troca de id de sessão consome muito recurso e ativá-la a 
                // todo request pode causar stress ao servidor.
            } elseif (rand(1, 100) <= 5) {
                self::regenerateSession();
            }
        } else {
//            session_name($name . '_syss');
            $_SESSION = array();
            session_destroy();
            session_start();
        }
    }

    static protected function preventHijacking() {
        if (!isset($_SESSION['IPaddress']) || !isset($_SESSION['userAgent']))
            return true;

        if ($_SESSION['IPaddress'] != $_SERVER['REMOTE_ADDR'])
            return false;

        if ($_SESSION['userAgent'] != $_SERVER['HTTP_USER_AGENT'])
            return false;

        return true;
    }

    static function cleanSession() {
        if (session_status() !== PHP_SESSION_NONE) {
            $_SESSION = array();
            session_unset();
            session_destroy();
        }
    }

    static function regenerateSession() {
        //TODO-> Acertar a renovação da sessão.
        // Se essa sessão é obsoleta isso significa que ela já tem um novo id.
//        if (array_key_exists('OBSOLETE', $_SESSION) && $_SESSION['OBSOLETE'] == true) {
//            return;
//        }
//        // A sessão atual tem duração de 10 segundos
//        $_SESSION['OBSOLETE'] = true;
//        $_SESSION['EXPIRES'] = time() + 10;
        // Cria uma nova sessão sem destruir os dados da sessão atual, apenas criando um novo id.
//        $old_sessionid = session_id();
//        session_regenerate_id(false);
//        $new_sessionid = session_id();
//        echo "Old Session: $old_sessionid<br />";
//        echo "New Session: $new_sessionid<br />";
//        // Pega o id atual e fecha a sessão para permitir que outros scripts use ela.
////       
//        $newSession = session_id();
//        session_write_close();
////
////        // Define o ID da sessão e inicia ela novamente.
//        session_id($newSession);
////        session_start();
    }

    static protected function validateSession() {
        if (isset($_SESSION['OBSOLETE']) && !isset($_SESSION['EXPIRES']))
            return false;

        if (isset($_SESSION['EXPIRES']) && $_SESSION['EXPIRES'] < time())
            return false;

        return true;
    }

}
