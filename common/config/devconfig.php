<?php if ( ! defined('IN_TNY')) exit('Access Denied');

$config['db']['default']['hostname'] = '127.0.0.1';
$config['db']['default']['port'] = 3306;
$config['db']['default']['username'] = 'root';
$config['db']['default']['password'] = 'paswd';
$config['db']['default']['database'] = 'mysql';
$config['db']['default']['redis_host'] = '127.0.0.1';
$config['db']['default']['redis_port'] = 10000;
$config['db']['default']['dbprefix'] = '';
$config['db']['default']['pconnect'] = TRUE;
$config['db']['default']['db_debug'] = TRUE;
$config['db']['default']['char_set'] = 'utf8';
$config['db']['default']['dbcollat'] = 'utf8_general_ci';

$config['session_prefix'] = 'site';

$config['security_key'] = 'site';

$config['app_url_path']	= '';
$config['host']			= 'http://ioctl.cc/';
$config['base_url']		= $config['host'] . $config['app_url_path'];

$config['index_page'] = 'index.php';

$config['default_controller'] = 'portal';

$config['rewrite_if'] = true;

$config['404_route'] = '404';

$config['msg_html_route'] = 'msg';

$config['error_route'] = 'error';

$config['route'] = array(
	//'/^([^\/]*)\/?$/'	=> '$1/portal/default',
	//'/([^\/]+)(.*)/'	=> '$2/_sd/$1'
);

$config['redis_prefix'] = 'ioctl';
$config['redis_version'] = 1;
$config['redis_version_course'] = 2;
$config['redis_version_lesson'] = 1;
$config['redis_version_teacher'] = 1;
$config['redis_version_user'] = 1;
$config['redis_version_device'] = 1;
$config['redis_version_order_course'] = 3;

$config['static_file_dir'] = '/usr/www/ioctl/static/';
$config['static_file_host'] = 'http://st.ioctl.cc/';
