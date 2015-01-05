<?php

define('ERR_CODE_OK', 0);
define('ERR_CODE_ERR', -1);

Class helper_Api {

    public function output($data = '', $error_code = ERR_CODE_OK) { 
        header('Content-Type:application/json; charset=utf8');

        echo json_encode(array('errno' => $error_code, 'data' => $data));
        exit;
    }
}
