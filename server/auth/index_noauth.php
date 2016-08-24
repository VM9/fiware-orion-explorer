<?php

date_default_timezone_set('America/Sao_Paulo');
set_time_limit(0);
session_cache_limiter('private');
session_cache_expire(160);

set_include_path(dirname(__FILE__) . '/../_lib' . PATH_SEPARATOR .
        dirname(__FILE__) . '/../_modules' . PATH_SEPARATOR .
        dirname(__FILE__) . '/../private' . PATH_SEPARATOR .
        get_include_path());


spl_autoload_register(function ($class) {
    if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
        $classname = str_replace('\\', '/', $class . '.php');
    } else {
        $classname = $class . '.php';
    }
    $filepath = getcwd() . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR .
            "%folder%" . DIRECTORY_SEPARATOR .
            $classname;

    $is_module = file_exists(str_replace("%folder%", "_modules", $filepath));
    $is_lib = file_exists(str_replace("%folder%", "_lib", $filepath));

    if ($is_module || $is_lib) {
        require_once($classname);
    } else {
        header('HTTP/1.0 500 Internal Error');
        echo \Application\Util::GenerateTemplate('500 Internal Error', '<p>Class ' . $classname . ' Not Found</p>');
        exit;
    }
});

//if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > 7200)) {
//    //Se a última atividade do servidor for a 1h atrás destruir a sessão para solicitar um novo login.
//    session_unset();     // unset $_SESSION variable for the run-time 
//    session_destroy();   // destroy session data in storage
////    $login = $_SERVER['REDIRECT_URL'] == '/server/auth/login';
//    //SE a origem do request não for o login, ele retorna erro 406 que caqusará o redirect para o login.php
////    if (!array_key_exists('usuario', $_SESSION) && !$login) {
////        header('HTTP/1.0 406 Nenhuma sessão ativa');
////        exit;
////    }
//}

use OAuth_io\OAuth;

//Init configuração
$config = \Application\Figuardian::getConfig();
//new Application\Config\init();
//\Application\Util::teste();
/* EX:
 * new Application\Config\init("DEV",array(),true,"CFG");
 *
 * echo CFG_db_host;
 * exit;
 */

/* EX:
 * new Config("DEV",array(),true,"CFG");
 *
 * echo CFG_db_host;
 * exit;
 */



//RestServer usando SlimFW
Slim\Slim::registerAutoloader();

$app = new \Slim\Slim(array(
    'mode' => 'development',
    'debug' => true
        ));

if (session_status() == PHP_SESSION_NONE) {
    Application\Security\SessionManager::sessionStart('orion');
}


//$app->add(new Application\CsrfProtection("APP-X", $config->get('sys.salt')));
//
//Captura todas as exceptions
$app->error(function(\Exception $e = null) use ($app) {
    $erro = array('status' => 'error',
        'text' => $e->getMessage(),
        "code" => $e->getCode(),
        "message" => $e->getPrevious());
    \Application\Util::PrintJson($erro);
});

$app->group('/v1', function () use($app) {
    $app->group('/oAuth2', function () use($app) {
        $app->any('/authorize', function () {
            try {
                $client_id = "cab5c94a18c04d27ab0a76fd551a71bc";
                $client_secret = "55681cdaaf76479f94fc972582dc57fa";
                $auth_code = $_REQUEST["code"];
//                $postBody = "grant_type=authorization_code&code=" . $code . "&redirect_uri=http://orionexplorer.com/server/auth/v1/oAuth2/authorize";
//                $postBody = array(
//                    'grant_type' => 'authorization_code',
//                    'code' => $code,
//                    'redirect_uri' => 'http://orionexplorer.com/server/auth/v1/oAuth2/token');

                $oAuth2 = new \Application\Security\oAuth2();

                $AccessToken = $oAuth2->GetAccessToken($client_id, $client_secret, $auth_code);

                if (is_object($AccessToken)) {
                    $Credentials = $oAuth2->GetCredentials($AccessToken->access_token);
                }

                if (is_object($Credentials)) {
//                   echo "<pre>";var_dump($Credentials);exit;
                    $UserDB = new \Application\DB\Models\sqlite\Users();
                    $userCod = md5($Credentials->email);
                    
                    
                    $Orion = new Orion\ContextBroker('localhost');
                    $update = new Orion\Operations\updateContext();
                    $update->setAction("APPEND")
                            ->addElement($userCod, "OrionExplorerUser")
                            ->addAttrinbute("name", "profile", $Credentials->displayName) //htmlentities($value, ENT_QUOTES)
                            ->addAttrinbute("email", "profile", $Credentials->email)
                            ->addAttrinbute("provider", "oauth", "FiWare")
                            ->addAttrinbute("lastlogin", "date", date('Y-m-d H:i:s'));
                    $Orion->updateContext($update->getRequest());


                    $user = $UserDB->getByCode($userCod);

                    if (null == $user) {
                        $user = new stdClass();
                        $user->cod = $userCod;
                        $user->name = $Credentials->displayName;
                        $user->email = $Credentials->email;
                        $user->pwd = $Credentials->id . $Credentials->app_id;

                        if (isset($Credentials->organizations) &&  is_array($Credentials->organizations) && isset($Credentials->organizations[0]->displayName)) {
                            $user->organization = $Credentials->organizations[0]->displayName;
                        }else{
                            $user->organization = " ";
                        }

                        $user->id = $UserDB->insert($user);
                    }
                    $_SESSION['Auth'] = array(
                        'credentials' => $Credentials,
                        'access_token' => $AccessToken,
                        'user' => (array) $user
                    );
                }
                
                
//                \Application\Util::PrintJson($_SESSION);
                echo "<script>", "window.location.href = '/./access_token/",$AccessToken->access_token,"'", "</script>";
                exit;
            } catch (Exception $ex) {
				//echo "<pre>";
				//var_dump($ex);exit;
				$code = $ex->getCode();
				$msg = $ex->getMessage();
                //var_dump($ex->getCode());
                //exit;
                echo "<script>", "window.location.href = '/./login.php?error=",$code,"&msg=",$msg,"'", "</script>";
                exit;
            }
        });
        $app->any('/me', function () {
            try {
                \Application\Util::PrintJson($_SESSION);
            } catch (Exception $ex) {
                var_dump($ex);
            }
        });

        $app->any('/token', function () {
            try {
                \Application\Util::PrintJson($_SESSION);
            } catch (Exception $ex) {
                var_dump($ex);
            }
        });
    });
});



$app->group('/oAuth', function () use($app) {
    $_SESSION['oAuth.io'] = array();
    $scope = $_SESSION['oAuth.io'];
    $OAuth = new OAuth($scope, false);
    $OAuth->initialize('ybogHCWImGm9e__VuL5jpHCFWKg', 'aMFyNm-qwyTLRyO8WAcgOryKdS0');

    $app->get(
            '/statustoken', function () use ($OAuth) {
        echo $OAuth->generateStateToken();
    });


    $app->post(
            '/accredit/:provider', function ($provider) use ($OAuth) {
        try {
            echo "<pre>";
            $params = json_decode(\Slim\Slim::getInstance()->request()->getBody());


            $request_object = $OAuth->auth($provider, array(
                'code' => $params->code,
                'force_refresh' => true
            ));
            $credentials = $request_object->getCredentials();
            $user = $request_object->me();
//            var_dump($credentials);
//            var_dump("user",$user);
//            print_r($user);
//            exit;

            $ret = array(
                'id' => $user['raw']['id'],
                'user' => $user,
                'credentials' => $credentials);
            $userCod = md5($user['email']);
            $Orion = new Orion\ContextBroker('localhost');
            $update = new Orion\Operations\updateContext();
            $update->setAction("APPEND")
                    ->addElement($userCod, "OrionExplorerUser")
                    ->addAttrinbute("name", "profile", $user['name']) //htmlentities($value, ENT_QUOTES)
                    ->addAttrinbute("email", "profile", $user['email'])
                    ->addAttrinbute("provider", "oauth", $provider)
                     ->addAttrinbute("lastlogin", "date", date('Y-m-d H:i:s'));
            $ret['retorion'] = json_decode($Orion->updateContext($update->getRequest()));
            
//            var_dump($ret);
//            var_dump($ret["id"]);
//            var_dump($user);
//            var_dump($params->access_token);
//            
//            exit;
            
            
                if (isset($user)) {
//                   echo "<pre>";var_dump($Credentials);exit;
                    $UserDB = new \Application\DB\Models\sqlite\Users();
                    $userData = $UserDB->getByCode($userCod);

                    if (null == $userData) {
                        $userData = new stdClass();
                        $userData->cod = $userCod;
                        $userData->name = $user['name'];
                        $userData->email = $user['email'];
                        $userData->pwd = $ret["id"] . $provider;
                        $userData->organization = " ";
                        

                        $userData->id = $UserDB->insert($userData);
                    }
                    $_SESSION['Auth'] = array(
                        'credentials' => $credentials,
                        'access_token' => $params->access_token,
                        'user' => (array) $userData
                    );
                }
          

            \Application\Util::PrintJson($ret);
            exit;
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    });

    $app->get(
            '/check', function () use ($OAuth) {




        $request_object = $OAuth->auth('facebook', array(
            'code' => $code
        ));


        var_dump($request_object);
        $credentials = $request_object->getCredentials();
        var_dump($credentials);
//        
//        $facebook_requester = $OAuth->create('facebook');
//$result = $facebook_requester->me(array('firstname', 'lastname', 'email'));

        exit;
    });
});


$app->get(
        '/validar', function () {

    try {
        if (array_key_exists('Auth', $_SESSION)) {

            $session = (array) $_SESSION['Auth']['user'];
//            var_dump($session);exit;
            $user = new stdClass();

            $user->i = $session['id'];
            $user->n = $session['name'];
            $user->u = $session['organization'];
            $user->r = 1;
            $user->o = $session['email'];
            $user->k = $session['cod'];

            \Application\Util::PrintJson($user);
        } else {
            //header('HTTP/1.0 406 No Active Session');
			$user = new stdClass();

            $user->i = 180;//ID de usuário válido
            $user->n = "Leonan Teste";
            $user->u = "vm9";
            $user->r = 1;
            $user->o = "j.leonancarvalho@gmail.com";
            $user->k = "39ee337c34bd0fbdb1f44da9c0c2b7f3"

            \Application\Util::PrintJson($user);
        }
    } catch (Exception $ex) {
        var_dump($ex);
    }
}
);


$app->any(
        '/logout', function () {
    echo session_destroy();
});

// GET route
$app->get(
        '/check', function () {
    //if (array_key_exists('Auth', $_SESSION) && array_key_exists('user', $_SESSION['Auth'])) {
        header('HTTP/1.0 200 OK');
    //} else {
    //    header('HTTP/1.0 406 No Active Session');
    //}
}
);

// POST route
$app->post(
        '/login', function () {
    $params = json_decode(\Slim\Slim::getInstance()->request()->getBody());

    if (isset($params->u) && isset($params->s) && ($params->u == "demo" && $params->s == "fi-ware")
    ) {
        $_SESSION['usuario'] = array();
        header('HTTP/1.0 200 OK');
        exit;
    } else {
        header('HTTP/1.0 401 Forbbinden');
        exit;
        //echo "false";
    }



    \Application\Util::PrintJson($params);
}
);

// PUT route
$app->put(
        '/put', function () {
    echo 'This is a PUT route';
}
);

// PATCH route
$app->patch('/patch', function () {
    echo 'This is a PATCH route';
});

// DELETE route
$app->delete(
        '/delete', function () {
    echo 'This is a DELETE route';
}
);

/**
 * Step 4: Run the Slim application
 *
 * This method should be called last. This executes the Slim application
 * and returns the HTTP response to the HTTP client.
 */
$app->run();
