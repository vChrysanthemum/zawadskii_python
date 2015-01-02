<?php if ( ! defined('IN_TNY'))	exit('Access Denied');

class DB {
    public $_db;
    public $trans_count;
    public $tablename_prefix;

    public static $db_map = array();

    function __construct($hostname, $port, $username, $password, $database) {
        $this->_db = null;
        $this->trans_count = 0;
        $this->connect($hostname, $port, $username, $password, $database);
        $this->_db->query('SET character_set_connection=utf8, character_set_results=utf8, character_set_client=binary');
    }

    public function insert_id() {
        return $this->_db->insert_id;
    }

    public function delete($tablename, $where) {
        $sql_where = array();
        foreach($where as $key => $value)
        {
            if(is_array($value)) {
                $str_where[] = '`' . $key . '` IN (' . implode(',', $value) . ')';
            }
            else {
                $sql_where[] = '`' . $key . '` = ' . $this->escape($value); 
            }
        }

        if(count($sql_where) > 0)
        {
            $sql_where = ' WHERE ' . join(' AND ', $sql_where);
        }
        else
        {
            $sql_where = '';
        }

        $sql = '
            DELETE FROM  `' . $tablename . '`  
            ' . $sql_where
            ;

        return $this->_db->query($sql);
    }



    public function select_count($tablename, $where) {
        $sql_where = array();
        foreach($where as $key => $value)
        {
            $sql_where[]	= '`' . $key . '` = ' . $this->escape($value); 
        }

        if(count($sql_where) > 0)
        {
            $sql_where = ' WHERE ' . join(' AND ', $sql_where);
        }
        else
        {
            $sql_where = '';
        }

        $sql = '
            SELECT count(1) AS count FROM ' . $tablename . ' 
            ' . $sql_where;
        $ret = $this->_db->query($sql)->fetch_assoc();
        return (int)$ret['count'];
    }

    public function where($where) {
        if(is_array($where) && count($where) > 0) {
            $sql_where = array();
            foreach($where as $key => $value)
            {
                if(is_array($value)) {
                    if(isset($value['symbol'])) {
                        $sql_where[]	= '`' . $key . '` ' . $value['symbol'] . ' ' . $this->escape($value['data']); 
                    }
                    else {
                        foreach($value as  &$v2) {
                            $v2 = $this->escape($v2);
                        }
                        $sql_where[] = '`' . $key . '` IN (' . implode(',', $value) . ')' ;
                    }
                }
                else {
                    $sql_where[]	= '`' . $key . '` = ' . $this->escape($value); 
                }
            }

            if(count($sql_where) > 0)
            {
                $sql_where = ' WHERE ' . join(' AND ', $sql_where);
            }
            else
            {
                $sql_where = '';
            }

            return $sql_where;
        }
        else {
            return '';
        }
    }

    public function select($tablename, $where, $field, $limit = 1, $offset = 0, $order_by = null) {
        $limit = (int)$limit;
        $offset= (int)$offset;

        if (is_array($field)) $field = implode(',', $field);

        $sql = '
            SELECT ' . $field . ' FROM  `' . $tablename . '` 
            ' . $this->where($where)
            ;
        if($order_by) {
            $sql .= '
                ORDER BY ' . $order_by . ' 
                ';
        }
        if($limit > 0) {
            $sql .= ' 
                LIMIT ' . $limit . ' OFFSET ' . $offset;
        }

        return $this->query($sql);
    }

    public function update($tablename, $data, $where) {
        $str_data	= array();
        foreach($data as $k => $v) {
            $str_data[] = '`' . $k . '` = ' . $this->escape($v);
        }

        $str_where = array();
        foreach($where as $k => $v) {
            if(is_array($v)) {
                $str_where[] = '`' . $k . '` IN (' . implode(',', $v) . ')';
            }
            else {
                $str_where[] = '`' . $k . '` = ' . $this->escape($v);
            }
        }

        $tablename = $this->tablename_prefix . $tablename;
        $sql = '
            UPDATE ' . $tablename . '
            SET ' . implode(',', $str_data) . ' 
            WHERE ' . implode(' AND ', $str_where) . '
            ';
        return $this->_db->query($sql);
    }

    public function create($tablename, $data) {
        $str_data	= array();
        $str_key	= array();
        foreach($data as $k => $v)
        {
            $str_data[] = $this->escape($v);
            $str_key[]	= '`' . $k . '`';
        }

        $tablename = $this->tablename_prefix . $tablename;
        $sql = '
            INSERT INTO	`' . $tablename . '`
            (' . implode(',', $str_key) . ') 
            VALUES
            (' . implode(',', $str_data) . ');
        ';
        $query = $this->_db->query($sql);

        return $this->_db->insert_id;
    }

    public function trans_begin() {
        $this->_db->query('begin');
        $this->trans_count += 1;
    }

    public function trans_commit() {
        $this->trans_count -= 1;
        if(0 == $this->trans_count)
        {
            $this->_db->query('commit');
        }
    }

    public function trans_rollback() {
        $this->_db->query('rollback');
        $this->trans_count = 0;
    }

    public function query($sql) {
        return $this->_db->query($sql);
    }

    public function escape($str, $is_have_qm = true) {
        $data_security_string=array(
            'iframe' => '&#105;frame' ,
            'script' => '&#115cript'
        );
        foreach ($data_security_string as $key => $value) {
            $str = str_replace($key , $value , $str);
        }
        $str = mysqli_real_escape_string($this->_db, $str);

        if(true == $is_have_qm)
        {
            return '\'' . $str . '\'';
        }
        else 
        {
            return $str;
        }
    }

    public function connect($hostname, $port, $username, $password, $database) {
        $this->_db = @new mysqli(
            $hostname . ':' . $port,
            $username,
            $password,
            $database
        );
        $this->_db->set_charset("utf8");

        if(mysqli_connect_errno()) {
            show_error('无法连接到数据库，错误信息: ' . mysqli_connect_error());                                                                          
        }
    }

    public function fetch($query) {
        return $query->fetch_assoc();
    }

    public function fetch_array($query) {
        $ret = array();
        while($row = $query->fetch_assoc()) {
            $ret[] = $row;
        }
        return $ret;
    }
}
