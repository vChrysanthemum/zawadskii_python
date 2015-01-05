<?php 
$helper_api = import_helper('api');

$q = explode(' ', $_REQUEST['q']);
if (count($q) < 2) {
    $helper_api->output('命令格式错误', ERR_CODE_ERR);
}
$q[1] = str_replace('<br>', '', $q[1]);

$ret = G::$kv_cache->kv_instance->mget(array($q[0], $q[1]));
if (is_array($ret)) {
    foreach($ret as &$v) {
        $v = str_replace("\n", '<br />', $v);
        $v = str_replace("\r", '<br />', $v);
    }
}
$helper_api->output($ret);
