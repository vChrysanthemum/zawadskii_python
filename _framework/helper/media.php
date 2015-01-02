<?php
Class helper_Media {
    public $err_msg = '';
    public $errno = '';
    public $helper_file = null;

    public function __construct() {
        $this->helper_file = import_helper('file');
        $this->reset();
    }

    public function reset() {
        $this->err_msg 	= '';
        $this->errno	= 0;
    }

    public function upload_to_tmp($file_name) {
        if(! isset($_FILES[ $file_name ])) {
            $this->err_msg = '文件名不存在';
            $this->errno = -1;
            return false;
        }

        if (! stristr('video/flv,video/wmv,video/avi,video/mp3,video/wma,video/rmvb,video/mp4,video/quicktime,video/mov,video/m4v,video/mpg,audio/mp3' , $_FILES[ $file_name ]['type'])) {
            $this->err_msg = '文件类型错误' . $_FILES[ $file_name ]['type'];
            $this->errno = -1;
            return false;
        }

        $this->helper_file->reset();
        $ret_file_name = $this->helper_file->upload_to_tmp('media', $file_name);
        if(false === $ret_file_name) {
            $this->err_msg = $this->helper_file->errmsg();
            $this->errno = $this->helper_file->errno();
            return false;
        }

        return $ret_file_name;
    }

    public function remove($file_name) {
        $this->helper_file->reset();
        return $this->helper_file->remove('media', $file_name);
    }

    public function move($tmp_file_name) {
        $this->helper_file->reset();
        return $this->helper_file->move('media', $tmp_file_name);
    }

    public function errmsg() {
        return $this->err_msg;
    }

    public function errno() {
        return $this->errno;
    }

    public function get_tmp_url($file_name) {
        return $this->helper_file->get_tmp_url('media', $file_name);
    }

    public function get_url($file_name) {
        return $this->helper_file->get_url('media', $file_name);
    }

    public function get_path($file_name) {
        return $this->helper_file->get_path('media', $file_name);
    }
}
