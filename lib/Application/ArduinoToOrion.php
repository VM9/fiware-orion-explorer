<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Application;

/**
 * Description of ArduinoToOrion
 *
 * @author Leonan S. Carvalho
 */
class ArduinoToOrion {

    public $_typemap;
    public $_Orion;
    public $_updateContext;
    public $_EntityType;
    public $_EntityId;

    public function __construct($entity) {

        $this->_EntityId = $entity["Id"];
        $this->_EntityType = $entity["Type"];

        unset($entity["Id"]);
        unset($entity["Type"]);


        $this->_typemap = array(
            "Ip" => "string",
            "Mac" => "string",
            "US_Sensor" => "int",
            "Led_White" => "int",
            "Buzzer" => "int"
        );


        $this->_Orion = new \Orion\ContextBroker('localhost');
        $this->_updateContext = new \Orion\Operations\updateContext();


        $this->_updateContext->addElement($this->_EntityId, $this->_EntityType);

        foreach ($entity as $attrName => $value) {
            if($attrName == 'position'){
                $value = explode(',', $value);   
                $this->_updateContext->addGeolocation($value[0], $value[1]);
            }else{
                $this->_updateContext->addAttrinbute($attrName, $this->getTypeByAttrName($attrName), $value);
            }
        }
    }

    public function execute() {
        $this->_updateContext->setAction("APPEND");

        $updateRequest = $this->_updateContext->getRequest();

        $res = json_decode($this->_Orion->updateContext($updateRequest));
        $responses = $res->contextResponses[0]->statusCode;


        \Application\Util::PrintJson($responses);
    }

    public function getTypeByAttrName($name) {
        if (array_key_exists($name, $this->_typemap)) {
            return $this->_typemap[$name];
        } else {
            return "string";
        }
    }

}
