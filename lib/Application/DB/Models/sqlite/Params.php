<?php
namespace Application\DB\Models\sqlite;
use Application\DB\Models\BaseTable;
/**
 * Description of Params
 *
 * @author Leonan Carvalho
 */
class Params extends BaseTable {
    
    protected $_table = 'params';
    protected $_primary = 'id';
    protected $_cols = array(
        "id" => "INTEGER",
        "key" => "TEXT",
        "value" => "TEXT",
        "iduser" => "TEXT",
    );
}
