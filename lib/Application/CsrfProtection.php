<?php

namespace Application;

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of CsrfProtection
 *
 * @author Leonan Carvalho
 */
class CsrfProtection extends \Slim\Middleware {

    /**
     * CSRF token key name.
     *
     * @var string
     */
    protected $key;

    /**
     * Secret random workd to be salted in requests.
     *
     * @var string
     */
    protected $salt;

    /**
     * Kind of requests do be checked
     *
     * @var array
     */
    protected $requests;

    /**
     * Constructor.
     *
     * @param string    $key        The CSRF token key name.
     * @return void
     */
    public function __construct($key = 'APP-X', $salt = "", $requests = array('POST', 'PUT', 'DELETE','GET')) {
        if (session_status() == PHP_SESSION_NONE) {
            Security\SessionManager::sessionStart('orion');
        }

        if (!is_string($key) || empty($key) || preg_match('/[^a-zA-Z0-9\-\_]/', $key)) {
            throw new \OutOfBoundsException('Invalid CSRF token key "' . $key . '"');
        }

        $this->key = $key;
        $this->salt = $salt;
        $this->requests = $requests;

        //Creating KEy
        if (!isset($_SESSION[$this->key])) {
//            $secret = hash("sha256", substr(md5(session_id() . $this->salt), 5, 10));
//            $secret = md5(uniqid(session_id(), true) . $this->salt);
//            $secret = hash_hmac('sha1', rand(), $this->salt); 

            $secret = md5(uniqid(rand() . $this->salt, true));
//            echo $secret;
            $_SESSION[$this->key] = array(
                'private' => $secret,
                'public' => Util::Criptografar($secret),
                'agent' => $_SERVER['HTTP_USER_AGENT'],
                'ip' => $_SERVER['REMOTE_ADDR']
            );
        }
    }

    /**
     * Call middleware.
     *
     * @return void
     */
    public function call() {
        // Attach as hook.
        $this->app->hook('slim.before', array($this, 'check'));

        // Call next middleware.
        $this->next->call();
    }

    /**
     * Check CSRF token is valid.
     * Note: Also checks POST data to see if a Moneris RVAR CSRF token exists.
     *
     * @return void
     */
    public function check() {
        
        // Check sessions are enabled.
        if (session_id() === '') {
            throw new \Exception('Sessions are required to use the CSRF Protection middleware.');
        }
        $csfr = $this->getProtection();
        
        // Validate the CSRF token.
        if (in_array($this->app->request()->getMethod(), $this->requests)) {
            
            $token = $csfr['private'];
            $userToken = $this->app->request()->headers->get($this->key);
            
            if (null == $userToken) {
                $this->app->halt(401, 'Invalid or missing Request token.');
            } else {

                $userCSFR = array(
                    'private' => Util::Decriptografar($userToken),
                    'public' => $csfr['public'], //O novo mecanismo de criptografia ele nunca gera igual mesmo que seja a mesma string, então para comparação esse valor é estático.
                    'agent' => $_SERVER['HTTP_USER_AGENT'],
                    'ip' => $_SERVER['REMOTE_ADDR']
                );

                
                
                
                //Verifico se há alguam diferença no array original com o fornecido
                //Com essa validação eu pego a troca de IP, de Agente e tudo mais.
                //Decisões podem ser tomadas de acordo com o que ocorrer, como por exemplo renovar o ID da requisição
                foreach (array_diff($csfr, $userCSFR) as $key => $value) {
                    switch ($key) {
                        case 'private':
                            $this->app->halt(401, 'Invalid Request token.');
                            break;
                        case 'public':
                            $this->app->halt(401, 'Unauthorized request');
                            break;
                        case 'agent':
                            Security\SessionManager::cleanSession();
                            $this->app->halt(401, 'Invalid or changed user agent'); //Pode ser que o 
                            break;
                        case 'ip': //Se o Ip foi alterado dar erro e relogar o usuário
                            Security\SessionManager::cleanSession();
                            $this->app->halt(401, 'Invalid or changed user address');
                            break;
                            
                        default:
                            $this->app->halt(401, 'Invalid or missing Default token.');
                            break;
                    }
                }
            }
        }else{
             $this->app->halt(401, 'Unauthorized request');
        }

        // Assign CSRF token key and value to view.
//        $this->app->view()->appendData(array(
//            'csrf_key' => $this->key,
//            'csrf_token' => $csfr,
//        ));
    }

    public function getProtection() {
        return $_SESSION[$this->key];
    }

}
