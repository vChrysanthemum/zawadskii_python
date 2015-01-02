<?php if ( ! defined('IN_TNY'))	exit('Access Denied');
//session_start();

class Cache_memcached{
	public $memcached;
	public $host;
	public $port;
	public function __construct($host, $port)
	{
		//return true;
		$this->memcached = new Memcached(); 
		$this->memcached->add_server($host, $port); 
		$this->memcached->set_option(Memcached::OPT_COMPRESSION, false); 

		$this->host = $host;
		$this->port = $port;
	}

	public function get($key)
	{
		//return $_SESSION['memcached'][$key];
		return $this->memcached->get($key);
	}

	public function set($key, $value, $refresh_time = 21600)
	{
		/*
		$_SESSION['memcached'][$key] = $value;
		return true;
		 */
		return $this->memcached->set($key, $value, $refresh_time);
	}

	public function delete($key)
	{
		return $this->memcached->delete($key);
	}
}
