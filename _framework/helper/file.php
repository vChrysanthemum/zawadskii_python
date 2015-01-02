<?php
Class helper_File {
    public $err_msg = '';
    public $errno = '';

    public function __construct() {
        $this->reset();
    }

    public function reset() {
        $this->err_msg 	= '';
        $this->errno	= 0;
    }

    public function upload_to_tmp($bucket_name, $file_name) {
        if(! isset($_FILES[ $file_name ])) {
            $this->err_msg = '文件名不存在';
            $this->errno = -1;
            return false;
        }

        if(empty($_FILES[ $file_name ]['name'])) {
            $this->err_msg = '文件名为空';
            $this->errno = -1;
            return false;
        }

        if ($_FILES[ $file_name ]['error'] > 0) {
            $this->err_msg = '上传文件失败';
            $this->errno = -1;
            return false;
        }

        if ($_FILES[ $file_name ]['size'] > 314572800) {//大于300m
            $this->err_msg = '文件过大';
            $this->errno = -1;
            return false;
        }

        //获得文件扩展名
        $file_ext = pathinfo($_FILES[ $file_name ]['name'], PATHINFO_EXTENSION);

        //新文件名
        $ret_file_name = md5(rand(10000, 99999) . $_FILES[ $file_name ]['tmp_name']) . '.' . $file_ext;

        $ret_file_path = G::$config['static_file_dir'] . 'b/tmp/' . $bucket_name . '/' . $ret_file_name;

        if(false === move_uploaded_file($_FILES[ $file_name ]['tmp_name'], $ret_file_path)) {
            $this->err_msg = '移动文件失败';
            $this->errno = -1;
            return false;
        }
        @chmod($ret_file_path, 0777);

        return $ret_file_name;
    }

    public function remove($bucket_name, $file_name) {
        $file_name = trim($file_name);
        if(strlen($file_name) < 3) {
            return true;
        }
        $path = G::$config['static_file_dir'] . 'b/' . $bucket_name . '/' . $file_name;
        @unlink($path);
        return true;
    }

    public function move($bucket_name, $tmp_file_name) {
        if (strlen($tmp_file_name) == 0) {
            return '';
        }

        $dir_date_name = date('Y/m/');
        $ret_file_name = $dir_date_name . $tmp_file_name;

        $dir = G::$config['static_file_dir'] . 'b/' . $bucket_name . '/';
        $dir_date_path = $dir . $dir_date_name;
        if (! file_exists($dir_date_path)) {
            mkdir($dir_date_path);
            @chmod($dir_date_path, 0777);
        }

        $file_path		= $dir . $ret_file_name;
        $tmpfile_path	= G::$config['static_file_dir'] . 'b/tmp/' . $bucket_name . '/' . $tmp_file_name;

        system('mv ' . $tmpfile_path . ' ' . $file_path);
        @chmod($file_path, 0777);
        $this->err_msg	= '';
        $this->errno	= 0;

        return $ret_file_name;
    }

    public function errmsg() {
        return $this->err_msg;
    }

    public function errno() {
        return $this->errno;
    }

    public function get_tmp_url($bucket_name, $file_name) {
        return G::$config['static_file_host'] . 'b/tmp/' . $bucket_name . '/' . $file_name;
    }

    public function get_url($bucket_name, $file_name) {
        return G::$config['static_file_host'] . 'b/' . $bucket_name . '/' . $file_name;
    }

    public function get_path($bucket_name, $file_name) {
        if(strlen($file_name) < 15) return '';
        return G::$config['static_file_dir'] . 'b/' . $bucket_name . '/' . $file_name;
    }
}
