<?php 
$helper_api = import_helper('api');

$q = explode(' ', $_REQUEST['q']);
$q[1] = str_replace('<br>', '', $q[1]);

$ret = G::$kv_cache->kv_instance->mget(array($q[0], $q[1]));
if (is_array($ret)) {
    foreach($ret as &$v) {
        $v = str_replace("\n", '<br />', $v);
        $v = str_replace("\r", '<br />', $v);
    }
}
$helper_api->output($ret);
