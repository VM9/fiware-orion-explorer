<?php

if (isset($_SERVER['HTTP_ORIGIN'])) {
    $origin = (array_key_exists('HTTP_ORIGIN', $_SERVER))? $_SERVER['HTTP_ORIGIN'] :
        ((array_key_exists("HTTPS", $_SERVER) && $_SERVER["HTTPS"] == "on")? "https" : "http" ) . "://" . $_SERVER['HTTP_HOST'];
    header("Access-Control-Allow-Origin: {$origin}");
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Max-Age: 86400');    // cache for 1 day
}
// Access-Control headers are received during OPTIONS requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {

    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']))
        header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");

    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']))
        header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");
}



date_default_timezone_set('America/Sao_Paulo');

set_time_limit(0);
session_cache_limiter('private');
session_cache_expire(160);

//set_include_path(dirname(__FILE__) . '/../_lib' . PATH_SEPARATOR .
//        dirname(__FILE__) . '/../_modules' . PATH_SEPARATOR .
//        get_include_path());


include_once '../vendor/autoload.php';


//RestServer usando SlimFW
Slim\Slim::registerAutoloader();

$app = new \Slim\Slim(array(
    'mode' => 'production',
    'debug' => false
        ));


//$app->add(new Application\CsrfProtection($config->get('sys.csfrkey'), $config->get('sys.salt'))); 
//Captura todas as exceptions
$app->error(function(\Exception $e = null) use ($app) {
    $erro = array('status' => 'error',
        'text' => $e->getMessage(),
        "code" => $e->getCode(),
        "message" => $e->getPrevious());
    \Application\Util::PrintJson($erro);
});

/**
 * 
 * @param type $ServerAddress
 * @param type $port
 * @param type $headers
 * @return \Orion\NGSIAPIv2
 */
function OrionInstance($ServerAddress, $port, $headers = []) {
    $Orion = new Orion\NGSIAPIv2($ServerAddress, $port, "application/json");
    
    if(count($headers) > 0){
        foreach ($headers as $header) {
            if($header->value != null){
                $Orion->setHeader($header->name, $header->value);
            }
        }
    }

    return $Orion;
}

$app->group('/orion', function () use($app) {

        $params = json_decode($app->request()->getBody());
        if($_SERVER['HTTP_HOST'] == 'orionexplorer.vm9it.com'){
            $rgx = "/(^127\.)|(^192\.168\.)|(^10\.)|(^172\.1[6-9]\.)|(^172\.2[0-9]\.)|(^172\.3[0-1]\.)|(localhost|localdomain|.local$)|(^::1$)|(^[fF][cCdD])/";
            if (preg_match($rgx, (string) $params->hostname, $matches)) {
                throw new \Exception('Sorry but we can give you access to our orion local instance, maybe a next time.');
            }
        }
        $Orion = OrionInstance($params->hostname, $params->port, $params->headers);


    $app->any('/entities(/:type)', function($type = false) use($Orion) {
        $retorno = $Orion->getEntities($type);
        \Application\Util::PrintJson($retorno->get());
    });
    
    $app->any('/geoentities(/:type)', function($type = false) use($Orion) {
        $retorno = $Orion->getEntities($type);
        \Application\Util::PrintJson($retorno->toGeoJson());
    });
    
    $app->any('/subscription(/:type)', function($type = false) use($Orion) {
        $Subscriptions = new Orion\Context\SubscriptionEntity($Orion);
        $retorno =  $Subscriptions->getContext();
        \Application\Util::PrintJson($retorno->get());
    });

    $app->post('/check', function()  use($Orion) {
        \Application\Util::PrintJson($Orion->serverInfo());
    });
    
    $app->post('/types', function()  use($Orion) {
        \Application\Util::PrintJson($Orion->getEntityTypes()->get());
    });

   
});


$app->run();
