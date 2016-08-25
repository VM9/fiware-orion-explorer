<?php

namespace Application;

/**
 * Description of Util
 *
 * @author Leonan Carvalho
 */
class Util {

    /**
     * Formata data e hora para o MySql (05/12/2012 para 2012-12-05)
     * @param string $date
     * @return type
     * @author miqueias.ferreira
     */
    public static function dateTimeToMySql($date) {
        $d = DateTime::createFromFormat("d/m/Y H:i:s", $date);
        return $d->format('Y-m-d H:i:s');
    }

    /**
     * Formata data para o MySql (05/12/2012 para 2012-12-05)
     * @param string $date
     * @return type
     * @author miqueias.ferreira
     */
    public static function dateToMySql($date) {
        $date = implode("-", array_reverse(explode("/", $date)));
        return $date;
    }

    /**
     * Formata uma data do MySql (2012-12-05 para 05/12/2012)
     * @param string $date
     * @return type
     * @author miqueias.ferreira
     */
    public static function dateFromMySql($date) {
        $date = implode("/", array_reverse(explode("-", $date)));
        return $date;
    }

    /**
     * Criptografia two-way.
     * Gera uma string criptografada de arrays ou strings
     * @param mixed $dados
     * @return type
     * @author miqueias.ferreira
     */
    public static function Criptografar($dados) {
        $CONFIG = \Application\Figuardian::getConfig();

        $dados = is_array($dados) ? serialize($dados) : $dados;
        $dados = base64_encode(base64_encode($dados . "21JL0SC23" . $CONFIG->get("sys.salt")));
        return $dados;
    }

    /**
     * Decriptografia two-way.
     * Gera uma string criptografada de arrays ou strings
     * @param mixed $dados
     * @return type
     * @author miqueias.ferreira
     */
    public static function Decriptografar($dados) {
        $dados = base64_decode(base64_decode($dados));
        $valor = explode('21JL0SC23', $dados);
//        $dados = $valor[0];
//        $dados = is_array($dados) ? serialize($dados) : $dados;
//        return $dados;
        $dados = @unserialize($valor[0]);
        if ($valor[0] === 'b:0;' || $dados !== false) {
            return $dados;
        } else {
            return $valor[0];
        }
    }

    public static function PrintJson($obj, $header = true) {
        if ($header) {
            header("Content-Type: application/json");
        }

        echo json_encode($obj);
    }

    /**
     * Gera página través de um template reutilizável
     *
     * @param  string   $title  Titulo
     * @param  string   $body   Conteúdo
     * @return string
     */
    public static function GenerateTemplate($title, $body) {
        return sprintf("<html><head><title>%s</title><style>body{margin:0;padding:30px;font:12px/1.5 Helvetica,Arial,Verdana,sans-serif;}h1{margin:0;font-size:48px;font-weight:normal;line-height:48px;}strong{display:inline-block;width:65px;}</style></head><body><h1>%s</h1>%s</body></html>", $title, $title, $body);
    }

    public static function getBaseurl($includeHost = true) {
        if (PHP_SAPI !== "cli") {
            //Constrói a base da aplicação dinamicamente
            $requestscheme = (isset($_SERVER['REQUEST_SCHEME'])) ? $_SERVER['REQUEST_SCHEME'] . "://" : "http" . "://";
            $requesthost = $_SERVER['HTTP_HOST'];
            $self_php = str_replace('index.php', '', $_SERVER['PHP_SELF']);
            if(!$includeHost){
                return ($self_php == "//")? "/" : $self_php;
            }
            if ($self_php == "//") {
                $baseurl = $requestscheme . $requesthost . '/';
            } else {
                $baseurl = $requestscheme . $requesthost . $self_php;
            }

            $url_part = array_reverse(explode('backend', $baseurl));
            $baseurl = end($url_part);
        }
        
        return $baseurl;
    }

}
