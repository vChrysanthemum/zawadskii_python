<?php
Class helper_Image {
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

    public function validate_type($type) {
        if (! stristr('image/gif,image/pjpeg,image/png,image/jpg,image/jpeg' , $type)) {
            return false;
        }

        return true;
    }

    public function upload_to_tmp($file_name) {
        if(! isset($_FILES[ $file_name ])) {
            $this->err_msg = '文件名不存在';
            $this->errno = -1;
            return false;
        }

        if (! $this->validate_type($_FILES[ $file_name ]['type'])) {
            $this->err_msg = '文件类型错误';
            $this->errno = -1;
            return false;
        }

        $this->helper_file->reset();
        $ret_file_name = $this->helper_file->upload_to_tmp('image', $file_name);
        if(false === $ret_file_name) {
            $this->err_msg = $this->helper_file->errmsg();
            $this->errno = $this->helper_file->errno();
            return false;
        }

        return $ret_file_name;
    }

    public function remove($file_name) {
        $this->helper_file->reset();
        return $this->helper_file->remove('image', $file_name);
    }

    public function move($tmp_file_name) {
        $this->helper_file->reset();
        return $this->helper_file->move('image', $tmp_file_name);
    }

    public function errmsg() {
        return $this->err_msg;
    }

    public function errno() {
        return $this->errno;
    }

    public function get_tmp_url($file_name) {
        return $this->helper_file->get_tmp_url('image', $file_name);
    }

    public function get_url($file_name) {
        return $this->helper_file->get_url('image', $file_name);
    }

    public function get_path($file_name) {
        return $this->helper_file->get_path('image', $file_name);
    }

    /*
     * 压缩图片大小
     */
    public function compress($source_img_name , $newwidth=300, $newheight=300) {
        $source_img = $this->get_path($source_img_name);
        $source_img_ext = pathinfo($source_img, PATHINFO_EXTENSION);
        if ($source_img_ext) {
            $target_img_name = str_replace('.'.$source_img_ext, '', $source_img_name);
            $target_img_name = $target_img_name . '_'.$newwidth.'_'.$newheight . '.' . $source_img_ext;
        }
        else {
            $target_img_name = $source_img_name .  '_'.$newwidth.'_'.$newheight;
        }
        $target_img = $this->get_path($target_img_name);
        @system('rm ' . $target_img);
        list($srcwidth , $srcheight) = getimagesize($source_img);
        $percent = 1;

        if ($srcwidth >= $newwidth && $srcwidth >= $srcheight) {
            $newheight	= $srcheight / ( $srcwidth / $newwidth );
        }
        else if($srcwidth < $newwidth && $srcheight >= $newheight && $srcheight >= $newwidth) {
            $newwidth	= $srcwidth / ( $srcheight / $newheight );
        }
        else {
            system('cp ' . $source_img . ' ' . $target_img);
            return $target_img_name;
        }

        $thumb = imagecreatetruecolor($newwidth , $newheight);
        switch ( $source_img_ext ) {
        case 'gif' :
            $source = imagecreatefromgif($source_img);
            imagecopyresized($thumb , $source , 0 , 0 , 0 , 0 , $newwidth , $newheight , $srcwidth , $srcheight);
            imagegif($thumb , $target_img);
            break;
        case 'png' :
            $source = imagecreatefrompng($source_img);
            imagecopyresized($thumb , $source , 0 , 0 , 0 , 0 , $newwidth , $newheight , $srcwidth , $srcheight);
            imagepng($thumb , $target_img);
            break;
        case 'jpg' :
            $source = imagecreatefromjpeg($source_img);
            imagecopyresized($thumb , $source , 0 , 0 , 0 , 0 , $newwidth , $newheight , $srcwidth , $srcheight);
            imagejpeg($thumb , $target_img);
            break;
        case 'jpeg' :
            $source = imagecreatefromjpeg($source_img);
            imagecopyresized($thumb , $source , 0 , 0 , 0 , 0 , $newwidth , $newheight , $srcwidth , $srcheight);
            imagejpeg($thumb , $target_img);
            break;
        default :
            return false;
            break;
        }

        return $target_img_name;
    }
}
