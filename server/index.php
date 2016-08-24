<?php

if (isset($_SERVER['HTTP_ORIGIN'])) {
    header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
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


include_once '../../vendor/autoload.php';


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

if (session_status() == PHP_SESSION_NONE) {
    Application\Security\SessionManager::sessionStart('orion');
}


//RestServer usando SlimFW
Slim\Slim::registerAutoloader();

$app = new \Slim\Slim(array(
    'mode' => 'production',
    'debug' => true,
//    'log.writer' => new \Slim\Extras\Log\DateTimeFileWriter(array(
//        'path' => dirname(__FILE__) . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . 'logs',
//        'name_format' => 'Y-m-d',
//        'message_format' => '%label% - %date% - %message%'
//            ))
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

function OrionInstance($id, &$Conn = false) {
    $Connections = new Application\DB\Models\sqlite\Connections();
    $Conn = $Connections->get($id);
    $ip = $Conn['ip'];
    $port = $Conn['port'];
    $context = $Conn['ngsi'];
    $user = $Conn['userid'];

    if (array_key_exists('Auth', $_SESSION) && array_key_exists('user', $_SESSION['Auth'])) {
        $session = $_SESSION['Auth']['user'];

        if ($session['id'] != $user) {
            header('HTTP/1.0 401 Forbbinden');
            exit;
        }
    } else {
        header('HTTP/1.0 401 Forbbinden');
        exit;
    }

    $Orion = new Orion\ContextBroker($ip, $port, $context);

    if (array_key_exists("headerkeyvalue", $Conn) && array_key_exists("headerkey", $Conn)) {
        if (!empty($Conn['headerkey']) && !empty($Conn['headerkeyvalue'])) {
            $Orion->setToken($Conn['headerkey'], $Conn['headerkeyvalue']);
        }
    }

    if ($Conn) {
        $info = $Orion->serverInfo();
        if ($info) {
            $info['status'] = "online";
        } else {
            $info['status'] = "offline";
        }
        $Conn['info'] = $info;
    }


    return $Orion;
}

$app->group('/orion', function () use($app) {

//    $Orion = new Orion\ContextBroker("130.206.83.29");

    $app->get('/:id/entityAttributes(/:type)', function($id, $type = false) {
        $Orion = OrionInstance($id, $Conn);
        $retorno = $Orion->getEntityAttributeView($type);
        $retorno['context'] = $Conn;
        \Application\Util::PrintJson($retorno);
        exit;
    });

    $app->get('/:id/entity(/:type)', function($id, $type = false) {
        $Orion = OrionInstance($id);
        $retorno = $Orion->getEntities($type);
        \Application\Util::PrintJson($retorno);
        exit;
    });

    $app->get('/check/:ip/:port', function($ip, $port) {
        try {
            $Orion = new Orion\ContextBroker($ip, $port);
            \Application\Util::PrintJson($Orion->checkStatus());
        } catch (Exception $exc) {
            echo $exc->getTraceAsString();
        }
    });

    $app->post('/check', function() {
        try {
            $params = json_decode(\Slim\Slim::getInstance()->request()->getBody());
//            var_dump($params->ip);exit;
            $Orion = new Orion\ContextBroker($params->ip, $params->port);
            \Application\Util::PrintJson($Orion->checkStatus());
        } catch (Exception $exc) {
            echo $exc->getTraceAsString();
        }
    });

    $app->get('/:id/server', function($id, $type = false) {
        $Orion = OrionInstance($id, $Conn);
        \Application\Util::PrintJson($Conn);
        exit;
    });

    $app->get('/:id/listSubscriptions', function($id) {
        $subscription = new \Application\DB\Models\sqlite\Subscriptions();

        $subs = $subscription->getByConn($id);

        $subscriptions = array();
        foreach ($subs as $key => $value) {
            $value['obj'] = json_decode($value['obj']);
            $subscriptions[$key] = $value;
        }
        \Application\Util::PrintJson($subscriptions);
        exit;
    });

    $app->post('/:id/subscribeContext', function($id) {
        $Orion = OrionInstance($id);
        $params = json_decode(\Slim\Slim::getInstance()->request()->getBody());

        //Creating basic SubscribeContext
        $subs = new Orion\Operations\subscribeContext($params->reference, $params->duration);

        //Creating entities
        if ($params->_data->allentities) { //All entities are checked, so do a patern
            $subs->addElement(".*", $params->entitytype, true);
        } else {
            foreach ($params->entities as $entity) {
                if ($entity->checked) {
                    $subs->addElement($entity->id, $params->entitytype, false); //Add entity
                }
            }
        }

        //Creating Attributes array
        foreach ($params->attributes as $attr) {
            if ($attr->checked) {
                $subs->addAttr($attr->name);
            }
        }

        $conditionValues = array();

        if ($params->notifyConditions == "ONCHANGE") {
            foreach ($params->conditionValues as $condVal) {
                if ($condVal->checked) {
                    array_push($conditionValues, $condVal->name); //Add checked attrs.
                }
            }
            $subs->notifyConditions("ONCHANGE", $conditionValues, $params->throttling);
        } else {
            array_push($conditionValues, $params->interval); //Ontime interval, only interval is needed
            $subs->notifyConditions("ONTIMEINTERVAL", $conditionValues);
        }

        //Is a new subscribe
        if ($params->isnew) {
            $reqBody = $subs->getRequest();

            $res = json_decode($Orion->subscribeContext($reqBody)); //Create postdata in json format(more easy than xml, just for less code)
            $response = array('orion' => $res); //Saving response to use as return to front-end

            if (isset($res->subscribeResponse->subscriptionId)) {
                $subscription = new \Application\DB\Models\sqlite\Subscriptions();
                //Building data to store
                $data = new stdClass();
                $data->subscriptionId = $res->subscribeResponse->subscriptionId;
                $data->duration = $res->subscribeResponse->duration;
                $data->obj = json_encode($reqBody);
                $data->idcon = $id;
                $data->created = time();

                $subscription->insert($data); //Storing the subscription data
                $response['db'] = $subscription->LastID();
            }
        } else { //So is a update operation
            $reqBody = $subs->getRequest($params->subscriptionId);
            //Removing objects that I can't send: https://forge.fi-ware.org/plugins/mediawiki/wiki/fiware/index.php/Publish/Subscribe_Broker_-_Orion_Context_Broker_-_User_and_Programmers_Guide#Updating_subscriptions
            unset($reqBody->reference); //cant update
            unset($reqBody->entities); //cant update
            unset($reqBody->attributes); //cant update
            //Send as convenicence operation:
            $url = "contextSubscriptions/" . $params->subscriptionId;
            $res = json_decode($Orion->conveniencePUT($url, $reqBody));

            //If Successfull Update stored information
            if (isset($res->subscribeResponse->subscriptionId)) {
                $subscription = new \Application\DB\Models\sqlite\Subscriptions();
                $storedSubs = $subscription->getBySubsID($params->subscriptionId);

                $obj = json_decode($storedSubs['obj']);

                $obj->notifyConditions = $reqBody->notifyConditions;
                $obj->duration = $reqBody->duration;

                if (isset($reqBody->throttling)) {
                    $obj->throttling = $reqBody->throttling;
                } elseif (isset($obj->throttling)) {
                    unset($obj->throttling);
                }

                $objid = $storedSubs['id'];
                unset($storedSubs['id']);
                $storedSubs['obj'] = json_encode($obj);

                $data = new stdClass();
                foreach ($storedSubs as $key => $value) {
                    $data->$key = $value;
                }
                $data->updated = time();
                $response['db'] = $subscription->update($data, $objid);
            }

            $response = array('orion' => $res);
        }

        \Application\Util::PrintJson($response);
    });

    $app->post('/:id/updateContext', function($id) {
        $Orion = OrionInstance($id);
        $entity = json_decode(\Slim\Slim::getInstance()->request()->getBody());

        $update = new Orion\Operations\updateContext();
        $update->addElement($entity->id, $entity->type);

        //Attributes
        foreach ($entity->attributes as $attr) {
            //Ungly Way to delete
            if (isset($attr->delete) && $attr->delete) {
                $Orion->convenienceDELETE("contextEntities/" . $entity->id . "/attributes/" . $attr->name);
            } else {
                $update->addAttrinbute($attr->name, $attr->type, $attr->value);
            }
        }
        if (isset($entity->geoloc)) {
            //Ungly Way to delete
            if (isset($entity->geoloc->delete) && $entity->geoloc->delete) {
                $Orion->convenienceDELETE("contextEntities/" . $entity->id . "/attributes/position");
            } else {
                $lat = $entity->geoloc->lat;
                $lng = $entity->geoloc->lng;
                if ((isset($lat) && isset($lng)) && ($lat != "" && $lng != "")) {
                    $update->addGeolocation($lat, $lng);
                }
            }
        }

        if ($entity->isnew) {
            $update->setAction("APPEND");
        } else {
            $update->setAction("APPEND");
//            $update->setAction("UPDATE");
            //TODO: -> Testar se adicionar um campo novo precisa de apend ou o update já serve.
            // Talvez verificar as alterações comparando o objeto gerado com o objeto existente
            // "/ngsi10/contextEntities/".$entity->id;
//        http://stackoverflow.com/questions/24431177/ge-orion-context-broker-when-we-make-an-update-of-the-entity-does-not-allow-us/24457710#24457710
        }


//        $Orion->convenienceDELETE("contextEntities/" . $entity->id . "/attributes/position");
        $OrionResponse = $Orion->updateContext($update->getRequest());
        $res = json_decode($OrionResponse);

//        $responses = array();
//        foreach ($res->contextResponses as $r) {
//            switch ($r->statusCode->code) {
//                case 200:
//                    break;
//                default:
//                    break;
//            }
//            $r->statusCode->code;
//            $responses[] = $r->statusCode;
//        }
        if (is_object($res)) {
            $responses = $res->contextResponses[0]->statusCode;
        } else {
            var_dump($res, $OrionResponse);
            throw new Exception($OrionResponse, 500, null);
            //var_dump($OrionResponse);//Error!
        }


        \Application\Util::PrintJson($responses);
    });

    $app->post('/:id/deleteEntity', function($id) {
        $Orion = OrionInstance($id);
        $params = json_decode(\Slim\Slim::getInstance()->request()->getBody());
        $response = json_decode($Orion->convenienceDELETE("contextEntities/" . $params->id));
//        var_dump($response);exit;
        \Application\Util::PrintJson($response);
    });

    $app->post('/:id/deleteSubscribe', function($id) {
        $Orion = OrionInstance($id);
        $params = json_decode(\Slim\Slim::getInstance()->request()->getBody());
        $response = json_decode($Orion->convenienceDELETE("contextSubscriptions/" . $params->subscriptionId));

        if (isset($response->statusCode)) {
            $subscription = new \Application\DB\Models\sqlite\Subscriptions();
            switch (intval($response->statusCode->code)) {
                case 200:
                    $subscription->delete($params->subscriptionId);
                    break;
                case 404: //Cleanup
                    $subs = $subscription->get($params->id);
                    if ($subs && count($subs) > 0) {
                        $subscription->delete($params->subscriptionId);
                        $response->statusCode->reasonPhrase = $response->statusCode->reasonPhrase . " has cleaned up from local storage.";
                    }
                    break;
                default:
                    break;
            }
        }

        \Application\Util::PrintJson($response->statusCode);
        exit;
    });

    $app->post('/:id/geoQueryContext', function($id) {
        $Orion = OrionInstance($id);
        $params = json_decode(\Slim\Slim::getInstance()->request()->getBody());
        $restriction = new \Orion\Context\queryRestriction();

        if ($params->type == "circle") {
            $restriction->createScope()->addCircle($params->center->latitude, $params->center->longitude, $params->radius, $params->inverted);
        } elseif ($params->type == "poly") {
            $restriction->createScope()->addPolygon($params->inverted);
            foreach ($params->vertices as $vertice) {
                $lat = (float) $vertice->latitude;
                $lng = (float) $vertice->longitude;
//                $restriction->addVertice(round($lat,10), round($lng,10)); //Prevent overflowing 
                $restriction->addVertice($lat, $lng); //Prevent overflowing 
            }
        }


        $query = new Orion\Operations\queryContext();
        if (is_array($params->entity)) {
            foreach ($params->entity as $entity) {
                $query->addElement(".*", $entity, true);
            }
        } else {
            $query->addElement(".*", $params->entity, true);
        }
        $query->addGeoRestriction($restriction);

//        echo json_encode($query->getRequest(), JSON_UNESCAPED_SLASHES);exit;
        $return = $Orion->queryContext($query->getRequest());
//        echo $return;exit;
        \Application\Util::PrintJson(json_decode($return));
//        exit;
    });

    $app->get('/:id/entityTypes(/:type)', function($id, $type = false) {
        $Orion = OrionInstance($id, $Conn);
        $retorno = $Orion->getEntityTypes($type);
        \Application\Util::PrintJson($retorno);
        exit;
    });

    $app->get('/:id/entityTypesData(/:type)', function($id, $type = false) {
        $Orion = OrionInstance($id, $Conn);
        $retorno = $Orion->getEntityAttributeView($type);
        $retorno['context'] = $Conn;
        \Application\Util::PrintJson($retorno);
        exit;
    });
});



$app->group('/OGW', function () use($app) {
    $app->any("/update", function () {

        try {
            $request = \Slim\Slim::getInstance()->request()->getBody();
            $entity = json_decode($request, true); //Array
            if ($entity !== null) {
                $ArduinoOrion = new Application\ArduinoToOrion($entity);
                $ArduinoOrion->execute();
            } else {
                $entity = $_REQUEST;

                if (array_key_exists('tilt', $entity)) {
                    unset($entity['tilt']);
                }

                $ArduinoOrion = new Application\ArduinoToOrion($entity);
                $ArduinoOrion->execute();
            }
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    });


    $app->any("/mobile", function () {

        try {
            if (isset($_REQUEST) && array_key_exists("Id", $_REQUEST) && array_key_exists("Type", $_REQUEST)) {
                $entity = $_REQUEST;

                $ArduinoOrion = new Application\ArduinoToOrion($entity);
                $ArduinoOrion->execute();
            }
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    });
});


$app->group('/service', function() use($app) {

//Get padrão: 
    /*
     * @param :model = À qual grupo de modelo que ele pertence: Cfg,User,Workspace,
     *  etc. É o nome exato da pasta.
     * @param :controller é à qual controller ele pertence, nome exato do arquivo
     * @param :action Refere-se à qual método ele está invocando.
     * @param :id   valor atribuido, em geral é o ID, para mais de 1 parâmetro utilizar o post. 
     */
    $app->get("/:model/:controller/:action(/:id)", function ($model, $controller, $action, $id = -1) {
        $fix = explode('?', $id);

        $id = $fix[0];
        $namespace = '\Application\DB\Models\\' . $model . '\\' . $controller;
        $classe = new $namespace();
        $retorno = $classe->$action($id);
        \Application\Util::PrintJson($retorno);
        exit;
    });


//Post padrão: 
    /*
     * @param :model = À qual grupo de modelo que ele pertence: Cfg,User,Workspace,
     *  etc. É o nome exato da pasta.
     * @param :controller é à qual controller ele pertence, nome exato do arquivo
     * @param :action Refere-se à qual método ele está invocando.
     * @param :id   valor atribuido, em geral é o ID, para mais de 1 parâmetro utilizar o post. 
     */
    $app->post("/:model/:controller/:action(/:id)", function ($model, $controller, $action, $id = false) {
        $fix = explode('?', $id);

        $id = $fix[0];

        $namespace = '\Application\DB\Models\\' . $model . '\\' . $controller;
        $request = \Slim\Slim::getInstance()->request()->getBody();
        $dados = json_decode($request);
        $classe = new $namespace();
        $retorno = $classe->$action($dados, $id);
        \Application\Util::PrintJson($retorno);
    });
});

/**
 * Step 4: Run the Slim application
 *
 * This method should be called last. This executes the Slim application
 * and returns the HTTP response to the HTTP client.
 */
$app->run();
