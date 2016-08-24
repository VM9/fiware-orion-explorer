<?php
namespace Application\DB\Models\sqlite;
use Application\DB\Models\BaseTableLite;
/**
 * Description of Connections
 *
 * @author Leonan Carvalho
 */
class Users extends BaseTableLite {
    
    protected $_table = 'Users';
    protected $_primary = 'id';
    protected $_cols = array(
        "id" => "INTEGER",
        "cod"=>    "TEXT",
        "name" => "TEXT",
        "email" => "TEXT",
        "pwd" => "TEXT",
        "organization" => "TEXT"
    );
    
    
    public function getByCode($cod) {
        try {
            $sql = $this->select()->where(" cod = '$cod' ")->getSql();
            $data = $this->query($sql)->Fetch();
            if ($data) {
                return $data;
            }
        } catch (Exception $e) {
            throw new Exception($e->getMessage(), $e->getCode());
        }
    }
     
}
