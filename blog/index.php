<?php
date_default_timezone_set('PRC');
set_time_limit(0);
ini_set('display_errors' , 1 );
error_reporting(E_ERROR | E_WARNING | E_PARSE);

define('IN_TNY' ,true);

define('BASE_PATH' , dirname(__FILE__) . '/');

define('APP_PATH' , BASE_PATH . 'application/' );

define('COMMON_PATH' , BASE_PATH . '../common/');

define('FRAME_PATH' , BASE_PATH . '../_framework/' );

define('VIEW_PATH' , APP_PATH . 'view/');

require COMMON_PATH . 'config/const.php';

//存储全局变量
class G {
    public static $now;
    public static $view_data;
    public static $session;
    public static $kv_cache;
    public static $db;
    public static $config;
    public static $imported_file;
    public static $ctr_url;
    public static $base_url;
}

require FRAME_PATH . 'core.php';

G::$imported_file = array();
G::$now = time();
G::$config = array();

$env = getenv('ENV');
if($env) {
    if('dev' == $env) {
        import_config('devconfig.php');
    }
    elseif('procbb' == $env) {
        import_config('procbbconfig.php');
    }
}
else {
    import_config('config.php');
}

G::$ctr_url = preg_replace(
    '/\?.*/', 
    '', 
    preg_replace('/^\/?(' . G::$config['app_url_path'] . ')?\/?(index.php)?\/?/', '', $_SERVER['REQUEST_URI'])
);
if(empty(G::$ctr_url)) {
    G::$ctr_url = G::$config['default_controller'];
}

//路由重写
foreach(G::$config['route'] as $source => $outcome)
{
    G::$ctr_url = preg_replace($source, $outcome, G::$ctr_url);
}

$ctr_url_arr	= explode('/' , G::$ctr_url);
$is_ctr_found	= false;
$ctr_path		= APP_PATH . 'controller/';
foreach ($ctr_url_arr as $value) {
    if (file_exists($ctr_path . $value . '.php')) {
        $ctr_path .= $value . '.php';
        $is_ctr_found = true;
    }
    else {
        $ctr_path .= $value . '/';
    }
}
if (!$is_ctr_found) {
    if (file_exists($ctr_path . '/index.php')) {
        G::$ctr_url .= '/index';
        $ctr_path .= '/index.php';
        $is_ctr_found = true;
    }
}


if ( !$is_ctr_found) {
    show_error('找不到该页面');
}

require FRAME_PATH . 'class/mysql.php';

$db = new DB(
    G::$config['db']['default']['hostname'],
    G::$config['db']['default']['port'],
    G::$config['db']['default']['username'],
    G::$config['db']['default']['password'],
    G::$config['db']['default']['database']
);
G::$db = $db;

require FRAME_PATH . 'class/kvcache.php';

G::$kv_cache = new Kvcache(
    G::$config['db']['default']['redis_host'],
    G::$config['db']['default']['redis_port']
);

require FRAME_PATH . 'class/session.php';
G::$session = new Session(G::$config['session_prefix']);

G::$base_url = G::$config['base_url'];
G::$view_data = array('base_url' => G::$base_url);


//载入控制器
require $ctr_path;
