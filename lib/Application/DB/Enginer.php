<?php

namespace Application\DB;

use \PDO;

/**
 * Classe para conexao com o banco de dados
 * via acesso nativo do PHP/PDO.
 */
class Enginer {

    /**
     * Instãncia singleton
     * @var Enginer 
     */
    private static $instance;

    /**
     * Conexão com o banco de dados
     * @var PDO 
     */
    private static $connection;

    /**
     * Construtor privado da classe singleton
     */
    public function __construct($dbname, $host, $user, $pwd, $type = "mysql") {
        try {
            
            switch ($type) {
                case 'mysql':
                    self::$connection = new \PDO("mysql:dbname=" . $dbname . ";host=" . $host, $user, $pwd, array(\PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"));
                    break;
                case 'pgsql':
                    self::$connection = new \PDO("pgsql:dbname=" . $dbname . ";host=" . $host, $user, $pwd, array(\PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"));
                    break;
                case 'oracle':
                    self::$connection = new \PDO("oci:dbname=" . $dbname . ";host=" . $host, $user, $pwd, array(\PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"));
                    break;
                case 'sqlite':
                    $dbdir = '/srv/www/OrionExplorer/server/service/DB';
                    
                    if(!is_dir($dbdir)){
                        mkdir($dbdir);
                    }
                    self::$connection = new \PDO("sqlite:" . $dbdir . DIRECTORY_SEPARATOR .  $dbname . ".sqlite3");
                    break;
                case 'sqlitememory':
                    self::$connection = new \PDO("sqlite::memory:");
                    break;
            }


            self::$connection->setAttribute(\PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            self::$connection->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            self::$connection->setAttribute(\PDO::ATTR_EMULATE_PREPARES, false);
            self::$connection->setAttribute(\PDO::ATTR_PERSISTENT, true);
            
            
        } catch (Exception $e) {
            throw  \Exception($e);
        }
    }

    /**
     * Obtém a instancia da classe DB
     * @return type
     */
    public static function getInstance() {

        $config = \Application\Figuardian::getConfig();

        $dbname = $config->get("db.name");
        $host = $config->get("db.host");
        $user = $config->get("db.user");
        $pwd = $config->get("db.pwd");
        $driver = $config->get("db.driver");
        
        if (empty(self::$instance)) {
            self::$instance = new \Application\DB\Enginer($dbname, $host, $user, $pwd, $driver);
        }
        return self::$instance;
    }

    /**
     * Retorna a conexão PDO com o banco de dados 
     * @return PDO
     */
    public static function getConn() {
        self::getInstance();
        return self::$connection;
    }

    /**
     * Prepara a SQl para ser executada posteriormente
     * @param String $sql
     * @return PDOStatement stmt
     */
    public static function prepare($sql) {
        return self::getConn()->prepare($sql);
    }

    /**
     * Retorna o id da última consulta INSERT 
     * @return int
     */
    public static function lastInsertId() {
        return self::getConn()->lastInsertId();
    }

    /**
     * Inicia uma transação
     * @return bool
     */
    public static function beginTransaction() {
        return self::getConn()->beginTransaction();
    }

    /**
     * Comita uma transação
     * @return bool
     */
    public static function commit() {
        return self::getConn()->commit();
    }

    /**
     * Realiza um rollback na transação
     * @return bool
     */
    public static function rollBack() {
        return self::getConn()->rollBack();
    }

    /**
     * Formata uma data para o MySql (05/12/2012 para 2012-12-05)
     * @param type $date
     * @return type
     */
    public static function dateToMySql($date) {
        return implode("-", array_reverse(explode("/", $date)));
    }

    /**
     * Formata uma data do MySql (2012-12-05 para 05/12/2012)
     * @param type $date
     * @return type
     */
    public static function dateFromMySql($date) {
        return implode("/", array_reverse(explode("-", $date)));
    }

}
