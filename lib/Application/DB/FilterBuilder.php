<?php
namespace Application\DB;
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of FilterBuilder
 *
 * @author Leonan Carvalho
 */
class FilterBuilder {

    protected $_filtro;
    public $_query = "";
    private $_currentfilter;
    private $_datafields = array();

    /**
     * Constructor
     * @param  array $filtro
     */
    public function __construct($filtro, $apelidotabela = false, $tabela = false) {
        $this->_filtro = $filtro;
        $this->_apelido = ($apelidotabela) ? $apelidotabela : "";
        $this->_tabela = $tabela;

        foreach ($this->_filtro as $this->_currentfilter) {
            $this->left();
            $this->campo();
            $this->condicao();
            $this->valor();
            $this->right();
            $this->operador();
        }
//        exit;
        $this->empresa();
        $this->status(); //Verifica se tem o campo status entre os campos e adiciona o where padrão.
//        echo $this->_query;exit;
    }

    private function left() {
        for ($i = 0; $i < $this->_currentfilter->left; $i++) {
            $this->_query .= "(";
        }
    }

    private function campo() {
        /**
         *  Existe exceção quando é necessário passar o campo inteiro (já com apelido) como condição
         *  EX: UEmp.idempresa => UEmp é apelido de UsuarioEmpresas, que na verdade é uma
         *  tabela secundária (JOIN).
         */
        $excecao = strstr($this->_currentfilter->datafields, '.');
        
        if ($this->_apelido !== "" && $excecao == false) {
            $this->_query .= " " . $this->_apelido . "." . $this->_currentfilter->datafields;
        } else {
            $this->_query .= " " . $this->_currentfilter->datafields;
        }
        $this->_datafields[] = $this->_currentfilter->datafields;
    }

    private function condicao() {
        //Caso o campo seja status e o valor for zero é para trazer todos
        if ($this->_currentfilter->datafields === "status") {
            $valor = $this->FormatByType($this->_currentfilter->valor);
            if ($valor == 0) {
                $this->_currentfilter->condicao = ">=";
            }
        }


        $this->_query .= " " . $this->_currentfilter->condicao;
    }

    private function valor() {
        switch ($this->_currentfilter->condicao) {
            case 'BETWEEN':
                $this->_query .= " " . $this->FormatByType($this->_currentfilter->valor[0]) . " AND " . $this->FormatByType($this->_currentfilter->valor[1]);
                break;
            case 'IN':
                $in = "(";
                $c = 0;
                $t = count($this->_currentfilter->valor);
                $s = "";
                foreach ($this->_currentfilter->valor as $v) {
                    $s = ($c < ($t - 1)) ? "," : ""; //Não imprimir vírgula no último valor.
                    $in .= $this->FormatByType($v) . $s;
                    $c++;
                }
                $this->_query .= $in . ")"; // . $this->_currentfilter->valor;
                break;
            case 'LIKE':
                $this->_query .= " '" . $this->_currentfilter->valor . "'";
                break;
            default:
                $this->_query .= " " . $this->FormatByType($this->_currentfilter->valor);
                break;
        }
    }

    protected function FormatByType($valor) {
        if ($valor == "")
            return "''";
        else
            $valor;

        // Leitura do SUBTYPE
        switch ($this->_currentfilter->tipo) {
            case 'char':
                return "'" . $valor . "'";
            case 'int':
                return intval($valor);
            case 'decimal':
                return floatval($valor);
            case 'money':
                return Util::FormataDecimal($valor);
            case 'date':
                return "'" . Util::dateToMySql($valor) . "'";
            case 'fk':
                $valor = json_decode($valor);
                return $valor->id;
            case 'memo':
                return $valor;
            case 'cpf':
                return "'" . Util::retirarFormatos($valor) . "'";
            case 'cnpj':
                return "'" . Util::retirarFormatos($valor) . "'";
            case 'cep':
                return "'" . Util::retirarFormatos($valor) . "'";
            case 'ncm':
                return Util::retirarFormatos($valor);
            case 'bool':
                return intval($valor);
            default:
                return "'" . $valor . "'";
        }
    }

    private function right() {
        for ($i = 0; $i < $this->_currentfilter->right; $i++) {
            $this->_query .= ")";
        }
    }

    private function operador() {
        if (isset($this->_currentfilter->operador)) {
            $this->_query .= " " . $this->_currentfilter->operador . " ";
        } else {
            $this->_query .= " ";
        }
    }

    /**
     * Método de adição dos WHERE's na query.     
     * @return string
     */
    public function getWhere() {
        return "WHERE " . $this->_query;
    }

    public function getHaving() {
        return "HAVING " . $this->_query;
    }

    public function status() {
        if (!in_array("status", $this->_datafields)) {
            if ($this->_apelido) {
                $this->_query .= " AND (" . $this->_apelido . ".status = 1) ";
            } else {
                $this->_query .= " AND (status = 1) ";
            }
        }
    }

    public function empresa() {
        $idemp = false;
        
        if($this->_tabela){ // Se passou a TABELA é pq existe a necessidade de validacao da mesma
            $sis = new Sigem_DB();
            $colunas = $sis->getColumns($this->_tabela);
            foreach ($colunas as $key => $value){
                if($key == 'idempresa'){ // Verifica se existe algum campo 'idempresa' na tabela;
                    $idemp = true;
                }
            }
        }
        
        /* Se o campo 'idempresa' existir na tabela em questão e o mesmo não estiver no filtro
         * faz a inclusão dos mesmos no filtro
         */
        if (!in_array("idempresa", $this->_datafields)  && $idemp == true) {
            foreach ($_SESSION['usuario']['e'] as $empresas) {
                $emps[] = $empresas['idempresa'];
            }            
            $emps = implode(", ", $emps);
            
            if ($this->_apelido) {
                $this->_query .= " AND (" . $this->_apelido . ".idempresa IN (" . $emps . ")) ";
            } else {
                $this->_query .= " AND (idempresa IN (" . $emps . ")) ";
            }
        }
    }
}
