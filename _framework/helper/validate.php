<?php
Class helper_Validate {
    public $rule_arr	= array();
    public $res		    = array();
    public $err_code	= 0;
    public $err_msg	    = null;

    public function init(&$res) {
        $this->rule_arr	= array();
        $this->res		= &$res;
        $this->err_code = 0;
        $this->err_msg  = null;
        return true;
    }

    public function add_rule($type, $column, $error_msg, $data = null, $default_data = null, $errno = -1) {
        $this->rule_arr[] = array(
            'type'			=> $type,
            'column'		=> $column,
            'data'			=> $data,
            'default_data'	=> $default_data,
            'error_msg'		=> $error_msg,
            'errno'			=> $errno
        );
        return true;
    }

    public function go() {
        foreach ($this->rule_arr as $rule) {
            if(!isset($this->res[ $rule['column'] ])) {
                $this->res[ $rule['column'] ] = $rule['default_data'];
            }

            $err = true;
            switch ($rule['type']) {
            case 'is_in':
                if (in_array($this->res[ $rule['column'] ], $rule['data'])) {
                    $err = false;
                    continue;
                }
                break;
            case 'min':
                if(is_array($this->res[ $rule['column'] ])) {
                    if(count($this->res[ $rule['column'] ]) >= $rule['data']) {
                        $err = false;
                        continue;
                    }
                }
                elseif ($this->res[ $rule['column'] ] >= $rule['data']) {
                    $err = false;
                    continue;
                }
                break;
            case 'max':
                if(is_array($this->res[ $rule['column'] ])) {
                    if(count($this->res[ $rule['column'] ]) <= $rule['data']) {
                        $err = false;
                        continue;
                    }
                }
                elseif ($this->res[ $rule['column'] ] <= $rule['data']) {
                    $err = false;
                    continue;
                }
                break;
            case 'not_empty':
                if (! empty($this->res[ $rule['column'] ])) {
                    $err = false;
                    continue;
                }
                break;
            case 'strlen_min':
                if (strlen($this->res[ $rule['column'] ]) >= $rule['data']) {
                    $err = false;
                    continue;
                }
                break;
            case 'strlen_max':
                if (strlen($this->res[ $rule['column'] ]) <= $rule['data']) {
                    $err = false;
                    continue;
                }
                break;
            }

            if(true == $err) {
                $this->err_msg	= $rule['error_msg'];
                $this->err_code	= $rule['errno'];
                return false;
            }

        }

        return true;
    }

    public function err_msg() {
        return $this->err_msg;
    }

    public function err_code() {
        return $this->err_code;
    }
}
