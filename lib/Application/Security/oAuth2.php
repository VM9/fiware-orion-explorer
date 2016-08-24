<?php

namespace Application\Security;

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of oAuth2
 *
 * @author Leonan S. Carvalho
 */
class oAuth2 {

    public $CurlHeaders;
    public $ResponseCode;
    private $_AuthorizeUrl = "https://account.lab.fiware.org/oauth2/authorize";
    private $_AccessTokenUrl = "https://account.lab.fiware.org/oauth2/token";
    private $_CredentialsUrl = "https://account.lab.fiware.org/user";

    public function __construct() {
        $this->CurlHeaders = array();
        $this->ResponseCode = 0;
    }

    public function RequestAccessCode($client_id, $redirect_url) {
        return($this->_AuthorizeUrl . "?client_id=" . $client_id . "&response_type=code&redirect_uri=" . $redirect_url);
    }

//Obtem o Access Token através do auth code
    public function GetAccessToken($client_id, $client_secret, $auth_code) {
        $params = array(
            'client_id' => $client_id,
            'client_secret' => $client_secret,
//            'grant_type' => 'client_credentials', 
            'grant_type' => 'authorization_code',
            'code' => $auth_code,
            'redirect_uri' => 'http://orionexplorer.com/server/auth/v1/oAuth2/authorize');




        $AuthBasic = base64_encode($client_id . ':' . $client_secret);


        $curl = curl_init($this->_AccessTokenUrl);
        curl_setopt($curl, CURLOPT_HEADER, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_HEADER, array(
            'Authorization: Basic ' . $AuthBasic,
            'Content-Type: application/x-www-form-urlencoded'
        ));
        
//        curl_setopt($curl, CURLOPT_USERPWD, $client_id . ":" . $client_secret);

        curl_setopt($curl, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
        
        curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
//        curl_setopt($curl, CURLOPT_USERPWD, $AuthBasic);
        curl_setopt($curl, CURLOPT_USERPWD, $client_id . ":" . $client_secret);
        

//        curl_setopt($curl, CURLOPT_HTTPAUTH, $AuthBasic);
//        curl_setopt($curl, CURLOPT_USERPWD, $AuthBasic);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

        $postData = http_build_query($params);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $postData);


        $response = curl_exec($curl);

        $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $header_size = curl_getinfo($curl, CURLINFO_HEADER_SIZE);

        if ($status != 200) {
            throw new \Exception("Error: call to URL $this->_AccessTokenUrl failed with status $status, response $response, curl_error "
            . curl_error($curl) . ", curl_errno " . curl_errno($curl) . "\n");
        }
        curl_close($curl);



        $header = substr($response, 0, $header_size);
        $body = substr($response, $header_size);

//        echo "<pre>";
//        var_dump($header);
//        var_dump($body);
//        var_dump(json_decode($body));
//        echo "</pre>";
//        exit;
        return json_decode($body);
    }

    public function GetCredentials($access_token) {

        $endpoint = $this->_CredentialsUrl . "?access_token=" . $access_token;

        $curl = curl_init($endpoint);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
        
        $response = curl_exec($curl);
        $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
//        $header_size = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
//        $header = substr($response, 0, $header_size);


        if ($status != 200) {
            throw new \Exception("Error: call to URL $endpoint failed with status $status, response $response, curl_error "
            . curl_error($curl) . ", curl_errno " . curl_errno($curl) . "\n");
        }
        curl_close($curl);
        
//        echo "<pre>";
////        var_dump($status);
////        var_dump($header_size);
//        var_dump($response);
//////        var_dump(json_decode($body));
//        echo "</pre>";
//        exit;
        return json_decode($response);
    }

    private function InitCurl($url) {
        $r = null;

        if (($r = @curl_init($url)) == false) {
            header("HTTP/1.1 500", true, 500);
            die("Cannot initialize cUrl session. Is cUrl enabled for your PHP installation?");
        }

        curl_setopt($r, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($r, CURLOPT_ENCODING, 1);

        return($r);
    }

    public function ExecRequest($url, $access_token, $get_params) {

        $full_url = http_build_query($url, $get_params);

        $r = $this->InitCurl($url);

        curl_setopt($r, CURLOPT_HTTPHEADER, array(
            "Authorization: Basic " . base64_encode($access_token)
        ));

        $response = curl_exec($r);
        if ($response == false) {
            die("curl_exec() failed. Error: " . curl_error($r));
        }

        return json_decode($response);
    }

}
