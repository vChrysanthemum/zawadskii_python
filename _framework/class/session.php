<?php if ( ! defined('IN_TNY'))	exit('Access Denied');
session_start();

class Session {
    const MEM_PREFIX_DEVICE_KEY = 'device_';
    public $kv_instance;
    public $session_prefix;
    public $userdata;
    public $glb_data;
    public $refresh_time;
    public function __construct($session_prefix, $refresh_time = 21600, $kv_instance = null) {
        $this->glb_data = &$_SESSION;
        $this->userdata = &$_SESSION[$session_prefix];
        if(!isset($this->glb_data['remoteip'])) {
            $this->glb_data['remoteip'] = $_SERVER['REMOTE_ADDR'];
        }
        else {
            if($_SERVER['REMOTE_ADDR'] != $this->glb_data['remoteip']) {
                session_unset();
                session_destroy();
            }
        }
        return;
    }

    public function flush_data() {
        return true;
    }

    public function __destruct() {
        $this->flush_data();
    }
}
