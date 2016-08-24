<?php

namespace Application\DB\Models\sqlite;

use Application\DB\Models\BaseTable;

/**
 * Description of Instances
 *
 * @author Leonan Carvalho
 */
class Subscriptions extends BaseTable {

    protected $_table = 'Subscriptions';
    protected $_primary = 'id';
    protected $_cols = array(
        "id" => "INTEGER",
        "created" => "INTEGER", //time();
        "updated" => "INTEGER", //time();
        "idcon" => "INTEGER",
        "subscriptionId" => "TEXT",
        "duration" => "TEXT",
        "obj" => "TEXT",
    );

    public function getBySubsID($id) {
        $sql = $this->select()->where("subscriptionId = '$id' ")->getSql();
        $data = $this->query($sql)->Fetch();
        if ($data) {
            return $data;
        } else {
            return array();
        }
    }

    public function getByConn($id) {
        $sql = $this->select()->where(" idcon = $id ")->getSql();
        $data = $this->query($sql)->FetchAll();
        if ($data) {
            return $data;
        } else {
            return array();
        }
    }

    public function delete($subscriptionId) {
        try {
            $sql = "DELETE FROM Subscriptions WHERE subscriptionId = '" . $subscriptionId . "'";

            $ret = $this->query($sql);
        } catch (Exception $exc) {
            header('Set-Cookie: errortrack="' . $exc->getMessage() . '"; path=/');
            throw new \Exception($exc->getMessage(), $exc->getCode(), $exc);
        }
    }

}
