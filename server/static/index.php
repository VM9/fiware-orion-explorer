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
    require_once(str_replace('\\', '/', $class . '.php'));
});

//Init configuração
new Config();

/*EX:
 * new Config("DEV",array(),true,"CFG");
 *
 *echo CFG_db_host;
 *exit;
*/

if (session_status() == PHP_SESSION_NONE) {
    Application\Security\SessionManager::sessionStart('orion');
}

//RestServer usando SlimFW
\Slim\Slim::registerAutoloader();
$app = new \Slim\Slim(array(
    'mode' => 'development',
    'debug' => true,
    'log.writer' => new \Slim\Extras\Log\DateTimeFileWriter(array(
        'path' => dirname(__FILE__) . '/../logs',
        'name_format' => 'Y-m-d',
        'message_format' => '%label% - %date% - %message%'
    ))
));





$app->add(new \Slim\Extras\Middleware\CsrfGuard()); //Estudar

// GET route
$app->get(
    '/',
    function () {
        var_dump($GLOBALS);
    }
);

// POST route
$app->post(
    '/post',
    function () {
        echo 'This is a POST route';
    }
);

// PUT route
$app->put(
    '/put',
    function () {
        echo 'This is a PUT route';
    }
);

// PATCH route
$app->patch('/patch', function () {
    echo 'This is a PATCH route';
});

// DELETE route
$app->delete(
    '/delete',
    function () {
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
