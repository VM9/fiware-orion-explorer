<?php
namespace Application\DB\Models\sqlite;
use Application\DB\Models\BaseTableLite;
/**
 * Description of Connections
 *
 * @author Leonan Carvalho
 */
class Connections extends BaseTableLite {
    
    protected $_table = 'Connections';
    protected $_primary = 'id';
    protected $_cols = array(
        "id" => "INTEGER",
        "ip" => "TEXT",
        "port" => "TEXT",
        "name" => "TEXT",
        "ngsi" => "TEXT",
        "userid" => "INTEGER"
    );
    
     public function all($filter = false) {
         $Connections = parent::all($filter);
         
         foreach ($Connections as $i => $conn) {        
             $ip = $conn['ip'];
             $port = $conn['port'];
             $context = $conn['ngsi'];
             $Orion = new \Orion\ContextBroker($ip, $port, $context);
             $info = $Orion->serverInfo();
             if($info){
                 $info['status'] = "online";
             }else{
                 $info['status'] = "offline";
             }
             $Connections[$i]['info'] = $info;
         }
         
         return $Connections;
    }
    
    public function allbutcon ($filter = false){
        return parent::all($filter);
    }


    /*
     * External: (http://orion.example.com:1026/version)
     * - version 
     * - uptime
     * http://orion.fi-guardian.com:1026/statistics
     */
}
