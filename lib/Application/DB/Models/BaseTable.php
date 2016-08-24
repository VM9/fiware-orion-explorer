<?php

namespace Application\DB\Models;

/**
 * Description of DB_Table
 *
 * @author Leonan Carvalho
 */
Abstract class BaseTable extends \Application\DB\MySQL {

    protected $_table;
    protected $_primary;

    //abstract public function getAll($filter = false);

    public function all($filter = false) {
        try {
            $WHERE = "";//"WHERE status = 1 ";
            $LIMIT = "";

            if (is_object($filter) && ($filter->dados != null)) {
                // Limite da pesquisa
                $LIMIT = " LIMIT " . $filter->dados->limite;

                //Filtros, condições da pesquisa.
                if (isset($filter->dados->filtro) && count($filter->dados->filtro) > 0) {
                    $advfiltro = new \Application\DB\FilterBuilder($filter->dados->filtro);
                    $WHERE = $advfiltro->getWhere();
//                    $HAVING = $advfiltro->getHaving();
                }
            }

            $sql = "SELECT * FROM $this->_table "
                    . $WHERE
                    . "ORDER BY $this->_primary "
                    . $LIMIT;

            $dados = $this->query($sql)->FetchAll();

            if ($dados) {
                return $dados;
            }
        } catch (Exception $exc) {
            throw new Exception($exc->getMessage(), $exc->getCode(), $exc);
        }
    }

    public function getTable() {
        return $this->_table;
    }

    public function getPrimary() {
        return $this->_primary;
    }

    public function get($id) {
        try {
            $sql = $this->select()->where(" id = $id ")->getSql();
            $data = $this->query($sql)->Fetch();
            if ($data) {
                return $data;
            }
        } catch (Exception $e) {
            throw new Exception($e->getMessage(), $e->getCode());
        }
    }

    public function update($data, $where, $auditoria = false, $tabela = false) {
        try {
            if (isset($data->dados)) { // caso venha direto (sem passar pela classe filho)
                if (!$where){
                    $where = " id = " . $data->id;
                }else{
                      $where = $this->_primary . " = " . $where;
                }
                
                $data = $data->dados;
            }
            
            $ret = parent::update($data, $where, $auditoria, $tabela);

            return true;
        } catch (Exception $exc) {
            throw new Exception("Erro ao atualizar ", $exc->getCode(), $exc);
        }
    }

    public function updateAll($data, $ids, $auditoria = false, $tabela = false, $primary = false) {
        try {
            $d = $data->dados->dados;
            $ids = $data->dados->id;
            $ret = parent::updateLote($d, $ids, $auditoria, $tabela, $primary);

            return \Application\Util::PrintJson($ret);
        } catch (Exception $exc) {
            throw new Exception("Erro ao atualizar registros ", $exc->getCode(), $exc);
        }
    }

    public function insert($data, $auditoria = false, $tabela = false) {
        try {
            if (!$data == null) {
                if (isset($data->dados)) { // caso venha direto (sem passar pela classe filho)                    
                    $data = $data->dados;
                }
                $ret = parent::insert($data, $auditoria, $tabela);
                return $ret;
            } else {
                //tratar erro
            }
        } catch (Exception $exc) {
            throw new Exception("Erro ao inserir ", $exc->getCode(), $exc);
        }
    }

    public function hardDelete($where, $obs = false, $auditoria = false, $tabela = false) {
        try {
            if (!$obs) {
                $obs = "";
            }
            $ret = parent::delete($where);

            return \Application\Util::PrintJson($ret);
        } catch (Exception $exc) {
            throw new Exception("Erro ao atualizar ", $exc->getCode(), $exc);
        }
    }

    public function updateStatus($data) {
        try {
            $this->atualizaStatus($data->dados);
        } catch (Exception $exc) {
            echo $exc->getTraceAsString();
        }
    }
    
    public function delete($id) {
         try {
             
             $where = $this->_primary . " = ".$id;
             
             
            parent::delete($where); //id = 1, id in(1,2,3,4,5), etc;
        } catch (Exception $exc) {
            throw new \Exception("Erro ao atualizar ", $exc->getCode(), $exc);
        }
    }
    

    public function remove($data) {
        try {
            $this->excluir($data->dados->id, $data->dados->obs);
        } catch (Exception $exc) {
            echo $exc->getTraceAsString();
        }
    }

    public function removeAll($data) {
        try {
            $this->excluir($data->dados->ids, $data->dados->obs);
        } catch (Exception $exc) {
            echo $exc->getTraceAsString();
        }
    }

}
