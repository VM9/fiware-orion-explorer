<?php

namespace Application\DB\Models\sqlite;

use Application\DB\Models\BaseTable;

/**
 * Description of Connections
 *
 * @author Leonan Carvalho
 */
class Connections extends BaseTable {

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
        $id = (is_object($_SESSION['Auth']['user']))? $_SESSION['Auth']['user']->id : $_SESSION['Auth']['user']['id'];

        $sql = "SELECT * FROM $this->_table "
                . " WHERE userid = " . $id
                . " ORDER BY $this->_primary ";

        $Connections = $this->query($sql)->FetchAll();


        foreach ($Connections as $i => $Conn) {
            $ip = $Conn['ip'];
            $port = $Conn['port'];
            $context = $Conn['ngsi'];
            $Orion = new \Orion\ContextBroker($ip, $port, $context);

            if (array_key_exists("headerkeyvalue", $Conn) && array_key_exists("headerkey", $Conn)) {
                if (!empty($Conn['headerkey']) && !empty($Conn['headerkeyvalue'])) {
                    $Orion->setToken($Conn['headerkey'], $Conn['headerkeyvalue']);
                }
            }

            $info = $Orion->serverInfo();
            if ($info) {
                $info['status'] = "online";
            } else {
                $info['status'] = "offline";
            }
            $Connections[$i]['info'] = $info;
        }

        return $Connections;
    }

    public function allbutcon($filter = false) {
        try {

            $id = $_SESSION['Auth']['user']['id'];

            $sql = "SELECT * FROM $this->_table "
                    . " WHERE userid = " . $id
                    . " ORDER BY $this->_primary ";

            $dados = $this->query($sql)->FetchAll();

            if ($dados) {
                return $dados;
            }
        } catch (Exception $ex) {
            var_dump($ex);
        }
    }

    public function insert($dados, $auditoria = false, $tabela = false) {

        $id = (is_object($_SESSION['Auth']['user']))? $_SESSION['Auth']['user']->id : $_SESSION['Auth']['user']['id'];

//        $id = $_SESSION['Auth']['user']['id'];
        if (isset($dados->dados)) { // caso venha direto (sem passar pela classe filho)                    
            $dados->dados->userid = $id;
        } else {
            $dados->userid = $id;
        }

        return parent::insert($dados);
    }

    /*
     * External: (http://orion.example.com:1026/version)
     * - version 
     * - uptime
     * http://orion.fi-guardian.com:1026/statistics
     */
}
