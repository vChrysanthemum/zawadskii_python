<?php if ( ! defined('IN_TNY'))	exit('Access Denied');
//session_start();

class Kvcache{
    public $kv_instance;
    public $host;
    public $port;
    public function __construct($host, $port)
    {
        //return true;
        $this->kv_instance = new Redis();
        $this->kv_instance->connect($host, $port);
        $this->kv_instance->setOption(Redis::OPT_SERIALIZER, Redis::SERIALIZER_PHP);

        $this->host = $host;
        $this->port = $port;
    }

    public function get($key)
    {
        //return $_SESSION['kv_instance'][$key];
        return $this->kv_instance->get(G::$config['redis_prefix'].'_'.$key);
    }

    public function set($key, $value, $refresh_time = 21600)
    {
        /*
        $_SESSION['kv_instance'][$key] = $value;
        return true;
         */
        if(! $refresh_time)
            return $this->kv_instance->set(G::$config['redis_prefix'].'_'.$key, $value);
        return $this->kv_instance->set(G::$config['redis_prefix'].'_'.$key, $value, $refresh_time);
    }

    public function delete($key)
    {
        return $this->kv_instance->delete(G::$config['redis_prefix'].'_'.$key);
    }
}
