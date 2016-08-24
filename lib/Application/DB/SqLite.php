<?php

namespace Application\DB;

/**
 * Description of ClasseBase
 *
 * @author Leonan Carvalho <j.leonancarvalho@gmail.com>
 */
class SqLite extends \Application\DB\Enginer {

    private $dbname;
    private $host;
    private $user;
    private $pwd;
    protected $_db;
    protected $_table;
    protected $_primary;
    protected $_cols;
    protected $_schema;
    private $_stmt;
    private $_sql = "";
    private $_where = array();
    private $_join = array();
    protected $_group = false;
    protected $_order = false;
    protected $_limit = false;

    public function __construct() {
        $config = \Application\Figuardian::getConfig();

        $this->dbname = $config->get("db.name");
        $this->host = $config->get("db.host");
        $this->user = $config->get("db.user");
        $this->pwd = $config->get("db.pwd");

        $this->_db = $this->getDbEnginer();


        //Create Table
//         $this->getDB()->prepare("DROP TABLE $this->_table ")->execute();
        $createtable = "CREATE TABLE IF NOT EXISTS $this->_table ( ";
        $first = true;
        foreach ($this->_cols as $col => $type) {

            if ($first) {
                $first = false;
            } else {
                $createtable .=",";
            }

            if ($col == $this->_primary) {
                $createtable .= "$col $type PRIMARY KEY AUTOINCREMENT";
            } else {
                $createtable .= "$col $type";
            }
        }
        $createtable .=" );";
        $this->getDB()->prepare($createtable)->execute();

        $this->_schema = $this->getSchema();
        $this->_cols = $this->getColumns();
//       var_dump($this->_cols);
    }

    private function getDbEnginer() {
        return new \Application\DB\Enginer($this->dbname, $this->host, $this->user, $this->pwd, 'sqlite');
    }

    public function getDB() {
        if (empty($this->_db)) {
            $this->_db = $this->getDbEnginer();
        }
        return $this->_db;
    }

    public function getSchema() {
        $sql = 'SELECT "main"     AS TABLE_CATALOG,
               "sqlite"   AS TABLE_SCHEMA,
               tbl_name   AS TABLE_NAME,
               sql
               FROM sqlite_master 
               WHERE type = "table" 
               AND tbl_name = "' . $this->_table . '"';

        $dados = $this->query($sql);
        return $this->FetchAll($dados);
    }

    public function getColumns($table = false) {
        if ($table) {
            $this->_table = $table;
        }

        $sql = "PRAGMA table_info(" . $this->_table . ")";

        $colunas = $this->query($sql)->FetchAll();

        $obj = new \stdClass();
        foreach ($colunas as $value) {
            $obj->$value['name'] = $value['dflt_value'];
        }
        return $obj;
    }

    public function query($sql, $dados = null) {

        $this->_stmt = $this->getDB()->prepare($sql);
        try {
            $this->_stmt->execute($dados);
            //Zero as variáveis de apoio
            $this->_join = array();
            $this->_orderby = false;
            $this->_limit = false;
            $this->_where = array();
            $this->_sql = "";
        } catch (Exception $exc) {
            throw new \Exception($exc->getMessage(), $exc->getCode(), $exc);
        }

        return $this;
    }

    public function Fetch() {
        return $this->_stmt->fetch();
    }

    public function FetchAll() {
        return $this->_stmt->fetchAll();
    }

    public function select($campos = '*', $from = false) {
        if (!$from) {
            $from = $this->_table;
        }

        $this->_sql = "SELECT " . $campos . " FROM " . $from . " ";
        return $this;
    }

    public function where($condicao) {
        $this->_where[] = " " . $condicao . " ";

        return $this;
    }

    public function groupby($group) {
        $this->_group = $group;
        return $this;
    }

    public function orderby($order) {
        $this->_order = $order;
        return $this;
    }

    /**
     * 
     * @param type $tabela Ex: "Tabela AS T"
     * @param type $on EX: "T.id = B.id"
     * @param type $tipo Por padrão INNER
     */
    public function join($tabela, $on, $tipo = "INNER") {
        $this->_join[] = " " . $tipo . " JOIN " . $tabela . " ON " . $on;

        return $this;
    }

    public function getSql() {
        if (count($this->_join) > 0) {
            foreach ($this->_join as $joins) {
                $this->_sql = $this->_sql . $joins;
            }
        }
        if (count($this->_where) > 0) {
            $this->_sql = $this->_sql . " WHERE ";
            foreach ($this->_where as $condicoes) {
                $this->_sql = $this->_sql . $condicoes;
            }
        }
        if ($this->_group) {
            $this->_sql = $this->_sql . " GROUP BY " . $this->_group;
        }
        if ($this->_order) {
            $this->_sql = $this->_sql . " ORDER BY " . $this->_order;
        }
        if ($this->_limit) {
            $this->_sql = $this->_sql . " LIMIT " . $this->_limit;
        }
        return $this->_sql;
    }

    public function insert($dados, $auditoria = true, $tabela = false) {
        try {

            if (!$tabela) {
                $tabela = $this->_table;
            }

            $sql = "INSERT INTO " . $tabela;

            //Inclui dados dados padrões de auditoria
            if ($auditoria) {
                $dataatual = date("Y-m-d H:i:s");
                $usuario = \Application\Util::GetUsuarioLogado();
                $dados->datainc = $dataatual;
                $dados->dataativ = $dataatual;
                $dados->idusuarioinc = $usuario['id'];
                $dados->idusuarioativ = $usuario['id'];
            }

            $arraydados = $this->sanitize($dados);



            $campos = "(";
            $v = " VALUES (";
            $valores = array();
            $total = count($arraydados);
            $count = 1;

            foreach ($arraydados as $key => $value) {
                if ($count < $total || $count > 0 && $count != $total) {
                    $s = ",";
                } else {
                    $s = "";
                }
                $campos .= $key . $s;
                $v .= "?" . $s;
                $valores[] = $value;

                $count++;
            }

            $campos .= ")";
            $v .= ")";

            $sql .= $campos . $v;
//            var_dump($sql);
//            var_dump($valores);
//            exit;
            $ret = $this->query($sql, $valores);
            return $ret->lastInsertId();
        } catch (Exception $exc) {
//            var_dump($e);exit;
            header('Set-Cookie: errortrack="' . $exc->getMessage() . '"; path=/');
            throw new \Exception($exc->getMessage(), $exc->getCode(), $exc);
        }
    }

    public function update($dados, $where, $auditoria = true, $tabela = false) {
        try {

            if (!$tabela) {
                $tabela = $this->_table;
            }

            $sql = "UPDATE " . $tabela . " SET ";


            //Inclui dados dados padrões de auditoria
            if ($auditoria) {
                $dataatual = date("Y-m-d H:i:s");
                $usuario = \Application\Util::GetUsuarioLogado();
//                $dados->dataalt = $dataatual; //Automático, controlado pelo DB
                unset($dados->dataalt); // Retira o dado array para receber a data do DB
                $dados->idusuarioalt = $usuario['id'];

                //Verifica se tem troca de status
                if (isset($dados->status)) {
                    switch (intval($dados->status)) {
                        case 1:
                            //Ativo
                            break;
                        case 2:
                            //Inativo
                            $dados->datainativ = $dataatual;
                            $dados->idusuarioinativ = $usuario['id'];
                            break;
                        case 3:
                            //Excluido 
                            //Inativo
                            $dados->dataexc = $dataatual;
                            $dados->idusuarioexc = $usuario['id'];
                            break;
                    }
                }
            }

            $arraydados = $this->sanitize($dados);

            $campos = "";
            $valores = array();
            $total = count($arraydados);
            $count = 1;

            foreach ($arraydados as $key => $value) {
                if ($count < $total || $count > 0 && $count != $total) {
                    $s = ",";
                } else {
                    $s = "";
                }
                $campos .= "`" . $key . "` = ?" . $s;

                $valores[] = $value;

                $count++;
            }

            $sql .= $campos;

            $sql .= " WHERE " . $where;
            //UPDATE funcionarios_t SET `status` = 3,`obsstatus` = "teste",`idusuarioalt` = 1,`dataexc` = '2014-03-07 10:23:18',`idusuarioexc` = 1 WHERE fun_id_INT = 34063
//            $sql = "UPDATE funcionarios_t SET `status` = 3, `obsstatus` = \"teste\",`idusuarioalt` = 1,`dataexc` = '2014-03-07 10:23:18',`idusuarioexc` = 1  WHERE fun_id_INT = 34063";
//            var_dump($sql);exit;
            $this->query($sql, $valores);
            return $this->lastInsertId();
        } catch (Exception $exc) {
            header('Set-Cookie: errortrack="' . $exc->getMessage() . '"; path=/');
            throw new \Exception($exc->getMessage(), $exc->getCode(), $exc);
        }
    }

    public function updateLote($campos, $ids, $auditoria = true, $tabela = false, $primary = false) {
        try {
            if (!$tabela) {
                $tabela = $this->_table;
            }

            if (!$primary) {
                $primary = $this->_primary;
            }

            //Inclui dados dados padrões de auditoria
            if ($auditoria) {
                $dataatual = date("Y-m-d H:i:s");
                $usuario = \Application\Util::GetUsuarioLogado();
//                $dados->dataalt = $dataatual; //Automático, controlado pelo DB

                $campos[] = "idusuarioalt = " . $usuario['id'];

                if (isset($campos->status)) {
                    switch (intval($campos->status)) {
                        case 1:
                            //Ativo
                            break;
                        case 2:
                            //Inativo
                            $campos[] = "datainativ = '" . $dataatual . "'";
                            $campos[] = "idusuarioinativ = " . $usuario['id'];
                            break;
                        case 3:
                            //Excluido 
                            //Inativo
                            $campos[] = "dataexc = '" . $dataatual . "'";
                            $campos[] = "idusuarioexc = " . $usuario['id'];
                            break;
                    }
                }
            }

            $ids = implode(",", $ids);
            $campos = implode(",", $campos);

            $sql = "UPDATE " . $tabela . " SET " . $campos . " WHERE " . $primary . " IN (" . $ids . ")";
//            echo $sql; exit;
            $ret = $this->query($sql);
        } catch (Exception $exc) {
            header('Set-Cookie: errortrack="' . $exc->getMessage() . '"; path=/');
            throw new \Exception($exc->getMessage(), $exc->getCode(), $exc);
        }
    }

///////////////////////////////////////////////////////////////////////////
//  MÉTODOS GENÉRICOS:  
///////////////////////////////////////////////////////////////////////////
    public function count() {
        $sql = "SELECT COUNT(*) AS Total FROM " . $this->_table;

        $dados = $this->query($sql)->fetch();

        if ($dados) {
            return $dados;
        }
    }

    public function LastID() {
        return $this->_db->lastInsertId();
    }

    public function buscarPorId($id) {
        try {
            $sql = $this->select()->where(" id = $id ")->getSql();
            $data = $this->query($sql)->Fetch();
            if ($data) {
                return $data;
            }
        } catch (Exception $exc) {
            header('Set-Cookie: errortrack="' . $exc->getMessage() . '"; path=/');
            throw new \Exception($exc->getMessage(), $exc->getCode(), $exc);
        }
    }

    public function delete($where) {
        try {

            //Excluir em lote
            if (is_array($where)) {
                $id = implode(",", $where);
                $where = $this->_primary . " IN ($id)";
            }

            //Excluir simples
            if (is_numeric($where)) {
                $where = $this->_primary . " = $where";
            }

            $sql = "DELETE FROM " . $this->_table . " WHERE " . $where;

            $ret = $this->query($sql);
        } catch (Exception $exc) {
            header('Set-Cookie: errortrack="' . $exc->getMessage() . '"; path=/');
            throw new \Exception($exc->getMessage(), $exc->getCode(), $exc);
        }
    }

    protected function sanitize($dados) {
        $dados = get_object_vars($dados);
        $colunas = get_object_vars($this->getColumns());

        return array_intersect_key($dados, $colunas);
    }

}
