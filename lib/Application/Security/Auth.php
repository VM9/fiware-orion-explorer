<?php

namespace Application\Security;

use Application\Figuardian;
use Application\DB\Models\User\Users;
use Application\DB\Models\User\Groups;

/**
 * Description of App
 *
 * @author Leonan Carvalho
 */
class Auth {

    private $_config;
    private $_UserModel;
    private $_GroupsModel;
    public $_auth;
    private $_salt;
    public $_credentials;
    private $_attempt;

    public function __construct() {
         if (session_status() == PHP_SESSION_NONE) {
            SessionManager::sessionStart('figuardian');
        }        
        
        $this->_config = Figuardian::getConfig();
        $this->_salt = $this->_config->get('sys.salt');

        $this->_UserModel = new Users();
        $this->_GroupsModel = new Groups();
    }

    /**
     * 
     * Método que tenta logar o usuário e distribui entre as formas de login possíveis
     * 
     * @param string $method 
     * @param array $credentials
     * @param bool $remember
     * @param string $redirect
     */
    public function attempt($method, $credentials = array(), $remember = false, $redirect = null) {
        $this->attempt = $this->getSessionValue('attempts');
        $this->setSessionValue('attempts', $this->attempt + 1);
        
        $Session_credentials = $this->getSessionValue('credentials');
       
        
        
        switch ($method) {
            case 'id': 
                $user = $this->_UserModel->get($credentials['id']);
                $this->loginSucessfull($user, $credentials);
                break;
            case 'facebook':
                break;
            case 'gplus':
                break;
            case 'linkedin':
                break;
            default:
                $user = $this->_UserModel->getbyEmail($credentials['email']);
                //Valida Informações do usuário
                if($user === null){
                    throw new \Exception("Invalid Email", 406, null);
                }else{
                    //Valida Senha
                    if($this->validatePass($credentials['pwd'], $user['pwd'])){
                       $this->loginSucessfull($user, $credentials);
                    }else{
                        throw new \Exception("Invalid Password", 406, null);
                    }
                }
                break;
        }
    }
    
    public function loginSucessfull($user, $credentials){
        $this->setSessionValue('credentials', $credentials);
        $this->setSessionValue('user', $user);
        $this->setSessionValue('attempts', 0);
        $this->setSessionValue('isguest', false);
    }

    public function logout() {
        $this->initSession();
    }

    protected function clearUserDataFromStorage() {
        
    }

    public function login($email, $pwd, $remember = false) {        
        $credentials = array("email" => $email, "pwd" => $pwd);
        
        $this->attempt('basic', $credentials, $remember);
    }

    public function loginUsingId($id, $remember = false) {
        $credentials = 
        $this->attempt('id', $credentials, $remember);        
    }

    public function loginUsingEmail($email, $remember = false) {
        $this->attempt('email', $credentials, $remember);
    }

    public function loginUsingFacebook ($obj, $remember = false){}
    public function loginUsingGplus ($obj, $remember = false){}
    public function loginUsingLinkedin ($obj, $remember = false){}

    public function validate($field, $value) {
        return $field === $value;
    }
    public function validatePass($provided, $stored) {
        
        $decript = \Application\Util::Decriptografar($stored);
        
        return $provided === $decript;
    }

    public function check() {
        return !is_null($this->getUser());
    }

    public function guest() {
        return $this->getBasicCredentials('user.isguest');
    }

    public function setUser() {
        
    }

    public function getUser() {
        $user = $this->getSessionValue('user');
        if(is_array($user)){
            if(array_key_exists('pwd', $user)){
                unset($user['pwd']);
            }
            return $user;
        }else{
            return null;
        }        
    }

    public function getBasicInfo($field = null) {
        if (null != $field) {
            if (array_key_exists($field, $this->_credentials)) {
                return $this->_credentials[$field];
            } else {
                return null;
            }
        }
    }

    public function getCredentials() {
        $authSession = $this->getSession();
    }

    public function getSession() {
        if (!array_key_exists('SysAuth', $_SESSION)) {
            $this->initSession();
        } else {
            if(time() - $_SESSION['SysAuth']->start > 7600){
                SessionManager::regenerateSession();
//                session_destroy();   // destroy session data in storage
//                session_start();
//                $this->initSession();
            }
        }
        return $_SESSION['SysAuth'];
    }

    private function initSession() {
        if(array_key_exists('SysAuth', $_SESSION)){
            unset($_SESSION['SysAuth']);
        }
        $authobj = new \stdClass();
        $authobj->attempts = 0;
        $authobj->isguest = true;
        $authobj->credentials = array();
        $authobj->start = time();

        $_SESSION['SysAuth'] = $authobj;
    }
    
    private function setSessionValue ($key, $value){
         $_SESSION['SysAuth']->$key = $value;
    }
    
     private function getSessionValue ($key){
         if(isset($this->getSession()->$key)){
            return $this->getSession()->$key;
         }else{
             return null;
         }
    }
   /*
     * Retorna string Randomica
     * @param int $length
     * @return string $key
     */

    function randomkey($length = 10) {
        $chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz1234567890";
        $key = "";

        for ($i = 0; $i < $length; $i++) {
            $key .= $chars{rand(0, strlen($chars) - 1)};
        }

        return $key;
    }

}
