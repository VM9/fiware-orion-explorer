<?php

include_once './vendor/autoload.php';


$teste = new \Orion\NGSIAPIv2("ngsi.vm9it.com");
var_dump($teste->checkStatus());

Application\Util::GenerateTemplate("Teste", "Foo");