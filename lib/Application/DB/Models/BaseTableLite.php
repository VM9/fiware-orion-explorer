<?php

namespace Application\DB\Models;

/**
 *
 * @author Leonan Carvalho
 */
Abstract class BaseTableLite extends \Application\DB\SqLite {

    protected $_table;
    protected $_primary;
    protected $_cols;

    //abstract public function getAll($filter = false);


    public function all($filter = false) {
        try {
            $WHERE = "";
            $LIMIT = "";

            if (isset($filter) && is_object($filter) && $filter->limite != null) {
                // Limite da pesquisa
                $LIMIT = " LIMIT " . $filter->limite;

                //Filtros, condições da pesquisa.
                if (isset($filter->filtro) && count($filter->filtro) > 0) {
                    $advfiltro = new FilterBuilder($filter->filtro);
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
            } else {
                return array();
            }
        } catch (Exception $exc) {
            throw new \Exception($exc->getMessage(), $exc->getCode(), $exc);
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

    public function update($data, $id, $auditoria = false, $tabela = false) {
        try {
//            var_dump($data);
//            var_dump($id);
            if (!$data == null) {
                $where = "id = " . $id;
                if (isset($data->dados)) {
                    $ret = parent::update($data->dados, $where, $auditoria, $tabela);
                } else {
                    $ret = parent::update($data, $where, $auditoria, $tabela);
                }
                return $ret;
            } else {
                //tratar erro
            }
        } catch (Exception $exc) {
            throw new \Exception("Erro ao atualizar ", $exc->getCode(), $exc);
        }
    }

    public function updateAll($campos, $ids, $auditoria = false, $tabela = false, $primary = false) {
        try {
            $ret = parent::updateLote($data, $where, $auditoria, $tabela, $primary);

            return \Application\Util::PrintJson($ret);
        } catch (Exception $exc) {
            throw new \Exception("Erro ao atualizar registros ", $exc->getCode(), $exc);
        }
    }

    public function insert($data, $auditoria = false, $tabela = false) {
        try {
            if (!$data == null) {                
                if (isset($data->dados)) {
                    $ret = parent::insert($data->dados, $auditoria, $tabela);
                } else {
                    $ret = parent::insert($data, $auditoria, $tabela);
                }
                return $ret;
            } else {
                
            }
        } catch (Exception $exc) {
            throw new \Exception("Erro ao inserir ", $exc->getCode(), $exc);
        }
    }

    public function delete($id) {
        try {
            parent::delete($id); //id = 1, id in(1,2,3,4,5), etc;
        } catch (Exception $exc) {
            throw new \Exception("Erro ao atualizar ", $exc->getCode(), $exc);
        }
    }

}
