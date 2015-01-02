<?php if ( ! defined('IN_TNY'))	exit('Access Denied');
Class cache_Base {
    public function __construct() {
    }

    public function get($data_id) {
        $key = $this->prefix_key . $data_id;
        $data = G::$kv_cache->get($key);
        if(!$data) {
            $data = G::$db->fetch($this->model_instance->select(array($this->model_instance->primary_key => $data_id), '*'));
            $data = $this->format($this->model_instance->format($data));
            if(!$data) {
                return false;
            }

            G::$kv_cache->set($key, $data);
        }

        return $data;
    }

    public function del($data_id) {
        return G::$kv_cache->delete($this->prefix_key . $data_id);
    }

    public function format($data) {
        return $data;
    }
}

Class model_Base {
    public $db = null;
    public function __construct() {
        $this->db = G::$db;
    }
    public function select_count($where) {		
        return $this->db->select_count($this->table_name, $where);
    }

    public function delete($where) {
        return $this->db->delete($this->table_name, $where);
    }

    public function select_max($column, $where = array()) {
        $sql = 'SELECT max('.$column.') AS max FROM ' . $this->table_name . $this->db->where($where);
        $ret = $this->db->fetch($this->db->query($sql));
        return $ret['max'];
    }

    public function select($where, $return_field, $limit = 1, $offset = 0, $order_by = null) {
        return $this->db->select($this->table_name, $where, $return_field, $limit, $offset, $order_by);
    }

    public function update($data, $where) {	
        $data['updated_at'] = G::$now;
        return $this->db->update($this->table_name, $data, $where);
    }

    public function create($data, $return_field = null) {
        $data['updated_at'] = G::$now;
        $data['created_at'] = G::$now;

        return $this->db->create($this->table_name, $data, $return_field);
    }

    public function get($data_id) {
        return $this->select(array($this->primary_key=>(int)$data_id))->fetch_assoc();
    }

    public function incr_count($column, $data_id) {
        $data_id = (int)$data_id;
        $sql = '
            UPDATE ' . $this->table_name . ' SET ' . $column . ' = ' . $column . ' + 1 WHERE ' . $this->primary_key . ' = ' . $data_id . '
            ';
        return $this->db->query($sql);
    }

    public function desc_count($column, $data_id) {
        $data_id = (int)$data_id;
        $sql = '
            UPDATE ' . $this->table_name . ' SET ' . $column . ' = ' . $column . ' - 1 WHERE ' . $this->primary_key . ' = ' . $data_id . '
            ';
        return $this->db->query($sql);
    }

    public function format($data) {
        return $data;
    }
}


function import_cache($name, $is_new = false) {
    $instance_path = 'cache/' . strtolower($name) . '.php';
    $instance_name = 'cache_' . $name;
    $is_found = false;
    $dirpath = APP_PATH . $instance_path;
    if (! isset(G::$imported_file[$instance_path])) {
        if(file_exists($dirpath)) {
            $is_found = true;
        }
    }
    else {
        $is_found = true;
    }

    if (! $is_found) {
        $dirpath = COMMON_PATH . $instance_path;
        if(! isset(G::$imported_file[$instance_path])) {
            if(file_exists($dirpath)) {
                $is_found = true;
            }
        }
    }
    else {
        $is_found = true;
    }

    if (!$is_found) return null;

    if (isset(G::$imported_file[$instance_path])) {
        if ($is_new) {
            return new $instance_name();
        }
        else {
            return G::$imported_file[$instance_path];
        }
    }
    else {
        require $dirpath;
        G::$imported_file[$instance_path] = new $instance_name();
        return G::$imported_file[$instance_path];
    }
}


function import_serv($name, $is_new = false) {
    $instance_path = 'serv/' . strtolower($name) . '.php';
    $instance_name = 'serv_' . $name;
    $is_found = false;
    $dirpath = APP_PATH . $instance_path;
    if (! isset(G::$imported_file[$instance_path])) {
        if(file_exists($dirpath)) {
            $is_found = true;
        }
    }
    else {
        $is_found = true;
    }

    if (! $is_found) {
        $dirpath = COMMON_PATH . $instance_path;
        if(! isset(G::$imported_file[$instance_path])) {
            if(file_exists($dirpath)) {
                $is_found = true;
            }
        }
    }
    else {
        $is_found = true;
    }

    if (!$is_found) return null;

    if (isset(G::$imported_file[$instance_path])) {
        if ($is_new) {
            return new $instance_name();
        }
        else {
            return G::$imported_file[$instance_path];
        }
    }
    else {
        require $dirpath;
        G::$imported_file[$instance_path] = new $instance_name();
        return G::$imported_file[$instance_path];
    }
}



function import_model($name, $is_new = false) {
    $instance_path = 'model/' . strtolower($name) . '.php';
    $instance_name = 'model_' . $name;
    $is_found = false;
    $dirpath = APP_PATH . $instance_path;
    if (! isset(G::$imported_file[$instance_path])) {
        if(file_exists($dirpath)) {
            $is_found = true;
        }
    }
    else {
        $is_found = true;
    }

    if (! $is_found) {
        $dirpath = COMMON_PATH . $instance_path;
        if(! isset(G::$imported_file[$instance_path])) {
            if(file_exists($dirpath)) {
                $is_found = true;
            }
        }
    }
    else {
        $is_found = true;
    }

    if (!$is_found) return null;

    if (isset(G::$imported_file[$instance_path])) {
        if ($is_new) {
            return new $instance_name();
        }
        else {
            return G::$imported_file[$instance_path];
        }
    }
    else {
        require $dirpath;
        G::$imported_file[$instance_path] = new $instance_name();
        return G::$imported_file[$instance_path];
    }
}


function import_helper($name, $is_new = false) {
    $instance_path = 'helper/' . strtolower($name) . '.php';
    $instance_name = 'helper_' . $name;
    $is_found = false;
    $dirpath = APP_PATH . $instance_path;
    if (! isset(G::$imported_file[$instance_path])) {
        if(file_exists($dirpath)) {
            $is_found = true;
        }
    }
    else {
        $is_found = true;
    }

    if (! $is_found) {
        $dirpath = COMMON_PATH . $instance_path;
        if(! isset(G::$imported_file[$instance_path])) {
            if(file_exists($dirpath)) {
                $is_found = true;
            }
        }
    }
    else {
        $is_found = true;
    }


    if (! $is_found) {
        $dirpath = FRAME_PATH. $instance_path;
        if(! isset(G::$imported_file[$instance_path])) {
            if(file_exists($dirpath)) {
                $is_found = true;
            }
        }
    }
    else {
        $is_found = true;
    }


    if (!$is_found) return null;

    if (isset(G::$imported_file[$instance_path])) {
        if ($is_new) {
            return new $instance_name();
        }
        else {
            return G::$imported_file[$instance_path];
        }
    }
    else {
        require $dirpath;
        G::$imported_file[$instance_path] = new $instance_name();
        return G::$imported_file[$instance_path];
    }
}


function import_config($path) {
    $dirpath = APP_PATH. 'config/' . $path;
    if(! isset(G::$imported_file[$dirpath])) {
        if(file_exists($dirpath)) {
            require $dirpath;
            if(isset($config)) G::$config += $config;
            G::$imported_file[$dirpath] = true;
        }
    }

    $dirpath = COMMON_PATH. 'config/' . $path;
    if(! isset(G::$imported_file[$dirpath])) {
        if(file_exists($dirpath)) {
            require $dirpath;
            if(isset($config)) G::$config += $config;
            G::$imported_file[$dirpath] = true;
        }
    }

    $dirpath = FRAME_PATH. 'config/' . $path;
    if(! isset(G::$imported_file[$dirpath])) {
        if(file_exists($dirpath)) {
            require $dirpath;
            if(isset($config)) G::$config += $config;
            G::$imported_file[$dirpath] = true;
        }
    }

    return true;
}

function is_email($email) {
    if (!preg_match("/^[a-z0-9]+([._\-]*[a-z0-9])*@([-a-z0-9]*[a-z0-9]+.){2,63}[a-z0-9]+$/i", $email)) {
        return false;
    }
    else {
        return true;
    }
}

//静态文件机制
function SU($url) {
    return G::$config['static_file_host'] . $url;
}

//拼接Url
function U($url, $paramer = null) {
    $url = G::$base_url  . $url;
    if(is_array($paramer)) {
        $getdata = array();
        foreach($paramer as $k => $v) {
            $getdata[] = $k . '=' . $v;
        }
        $getdata = join('&', $getdata);
        $url .= '?' . $getdata;
    }
    elseif(is_string($paramer)) {
        $url .= '?' . $paramer;
    }
    return $url;
}

function load_view($path , $view_data=null) {
    header('Content-Type:text/html;charset=utf-8');

    if (is_array($view_data)) {
        foreach ($view_data as $view_key => $view_value) {

            if (is_numeric($view_key)) continue;

            $$view_key = $view_value;
        }
    }

    require VIEW_PATH . $path . '.html';
}

function load_file($file_path)
{
    static $required_files = array();
    if(! isset($required_files[$file_path]) )
    {
        $required_files[$file_path] = true;
        require $file_path;
    }
}

function base_url ($url='') {

    return G::$config['base_url'] . $url;

}

function site_url ($url='' ) {

    if (G::$config['rewrite_if'])
        return G::$config['base_url'] . $url;

    else return G::$config['base_url'] . G::$config['index_page'] . '/' . $url;

}

function cache_php ($path , $view_data) {
    ob_end_clean();

    ob_start();

    load_view($path , $view_data);

    file_put_contents(BASE_PATH . 'data/cache/' . $path . '.php' , ob_get_contents());

    ob_end_clean();
    return true;
}

function show_error ($msg , $title=null , $url=null) {
    if (null == $title)
        $title = '错误信息';

    if(!G::$view_data) G::$view_data = array();
    G::$view_data += array('msg' => $msg , 'title' => $title , 'url' => $url);

    load_view(G::$config['error_route'] , G::$view_data);

    exit();
}

function show_404 () {

    load_view(G::$config['404_route']);

    exit();
}

function show_msg ($msg , $url=null , $head='提示' , $title='提示', $is_redirect = true) {

    G::$view_data += array(
        'msg' => $msg ,
        'url' => $url ,
        'head' => $head ,
        'title' => $title,
        'is_redirect' => $is_redirect
    );

    load_view(G::$config['msg_html_route'] , G::$view_data);

    exit();
}

function postv($name, $default_value = null) {
    if(isset($_POST[$name])) {
        return trim($_POST[$name]);
    }
    else {
        return $default_value;
    }
}

function getv($name, $default_value = null) {
    if(isset($_GET[$name])) {
        return trim($_GET[$name]);
    }
    else {
        return $default_value;
    }
}

function requestv($name, $default_value = null) {
    if(isset($_REQUEST[$name])) {
        return trim($_REQUEST[$name]);
    }
    else {
        return $default_value;
    }
}

function redirect ($url) {
    header('Location: ' . $url);
    exit(0);
}

function array_to_excel ($arr , $title='sheet1' , $filename='excel.xls' , $if_return=false , $encoding='UTF-8') {

    $lines = '';

    foreach ($arr as $line) {

        $cells = '';

        foreach ($line as $k => $v) {

            $type = 'String';
            if (is_numeric($v))	$type = 'Number';

            $v = htmlentities($v , ENT_COMPAT , $encoding);

            $cells .= "<Cell><Data ss:Type=\"$type\">" . $v . "</Data></Cell>\n";
        }

        $lines .= "<Row>\n" . $cells . "</Row>\n";
    }


    if ($if_return) {

        return $lines;

    }
    else{

        header("Content-Type: application/vnd.ms-excel; charset=" . $encoding);
        header("Content-Disposition: inline; filename=\"" . $filename . "\"");
        $header = "<?xml version=\"1.0\" encoding=\"%s\"?\>\n
            <Workbook 
            xmlns=\"urn:schemas-microsoft-com:office:spreadsheet\" 
            xmlns:x=\"urn:schemas-microsoft-com:office:excel\" 
            xmlns:ss=\"urn:schemas-microsoft-com:office:spreadsheet\" 
            xmlns:html=\"http://www.w3.org/TR/REC-html40\">";

        echo stripslashes(sprintf($header, $encoding));

        echo "\n<Worksheet ss:Name=\"" . $title . "\">\n<Table>\n";

        echo $lines;

        echo "</Table>\n</Worksheet>\n";
        echo '</Workbook>';

    }

}

function init_page_box($current_page, $page_size, $total_nums, $url) {
    $current_page = (int)$current_page;
    if($current_page <= 0) $current_page = 1;

    $page_size = (int) $page_size;
    if($page_size <= 0)
    {
        $page_size = 20;
    }
    return array(
        'current_page'	=> $current_page,
        'url'			=> $url,
        'total_page'	=> (int)($total_nums / $page_size) + ($total_nums % $page_size == 0 ? 0 : 1),
        'page_size'		=> $page_size
    );
}

function page ($page , $perpage_count , $count) {

    $perpage_count =(($perpage_count > 0) and($perpage_count < 300)) ?
        (int)$perpage_count :
        20;

    if ( ! ($page > 0))	$page = 1;

    $page_count = (int)($count / $perpage_count) + (($count % $perpage_count == 0) ? 0 : 1);

    if ($page > $page_count )	$page = 1;

    $start =($page - 1) * $perpage_count;

    return array('page_count' => $page_count , 'start' => $start);
}

function array_to_xml ($arr , $if_return = false) {

    if ( ! $if_return) {
        header('Content-Type: text/xml');
        echo '<xml version="1.0" encoding="UTF-8" >';
        echo '<root>';
    }

    $str = '';

    function echo_xml ($xml_value , &$str , $xml_value_key=NULL) {
        if (is_array($xml_value)) {
            foreach ($xml_value as $key => $value) {
                if (is_array($value)) {
                    if (is_numeric($key)) {
                        $str .= '<'.$xml_value_key.'>';
                        echo_xml($value , $str);
                        $str .= '</'.$xml_value_key.'>';
                    }else{

                        echo_xml($value , $str , $key);
                    }
                }elseif (is_numeric($key)) {

                    continue;
                }else{

                    $str .= '<'.$key.'>'.$value.'</'.$key.'>';
                }
            }
        }
    }
    echo_xml($arr , $str);

    if ($if_return)	return $str;
    else
        echo $str . '</root>';

    return true;
}

function download_website ($url) {
    $ch = curl_init($url);  // 初始化，返回一个handler
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);  // 设置选项，有返回值
    //curl_setopt($ch, CURLOPT_REFERER, 'http://www.google.cn/');  // 设置选项，来源页，这意味着可以伪造referer达到不可告人的目的
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.2; SV1; .NET CLR 1.1.4322)');  // 设置选项，浏览器信息
    $raw = curl_exec($ch);  // 执行
    curl_close($ch);  // 关闭handler
    return $raw;  // 输出结果
}

function download_file ($url , $path) {
    $path_all = '';
    $path_arr = explode('/' , $path);
    array_pop($path_arr);
    foreach ($path_arr as $v) {
        $path_all .= $v . '/';
        if (! file_exists($path_all))
            mkdir($path_all , 0777);
    }

    @ob_end_clean();
    @ob_start();
    //将文件读入到缓冲。
    readfile($url);
    //得到缓冲区的内容并且赋值给变量$img。
    $img = ob_get_contents();
    //关闭并清空缓冲.
    @ob_end_clean();
    //在本地创建新文件$filename，并将读入内容存入其中。
    $file = @fopen($path , 'a');
    fwrite($file , $img);
    fclose($file);

    return true;
}

/*   网站验证码程序
 *   运行环境： PHP5.0.18 下调试通过
 *   需要 gd2 图形库支持（PHP.INI中 php_gd2.dll开启）
 *   文件名: showimg.php
 *   作者：  17php.com
 *   Date:   2007.03
 *   技术支持： www.17php.com
 */
function init_yan_zImage($session_name) {
    //随机生成一个4位数的数字验证码
    $num = '';
    for($i=0 ; $i<4 ; $i++) {
        $num .= rand(0,9);
    }
    //4位验证码也可以用rand(1000,9999)直接生成
    //将生成的验证码写入session，备验证页面使用
    G::$session->userdata['yz'][ $session_name ]	= array(
        'value'		=> $num ,
        'if_used'	=> false
    );
    G::$session->flush_data();
    //创建图片，定义颜色值
    Header('Content-type: image/PNG');
    srand((double)microtime()*1000000);
    $im		= imagecreate(60,20);
    $black	= Image_color_allocate($im, 0,0,0);
    $gray	= Image_color_allocate($im, 200,200,200);
    imagefill($im , 0 , 0 , $gray);

    //随机绘制两条虚线，起干扰作用
    $style = array($black , $black , $black , $black , $black , $gray , $gray , $gray , $gray , $gray);
    imagesetstyle($im, $style);
    $y1 = rand(0,20);
    $y2 = rand(0,20);
    $y3 = rand(0,20);
    $y4 = rand(0,20);
    imageline($im , 0 , $y1 , 60 , $y3 , IMG_COLOR_STYLED);
    imageline($im , 0 , $y2 , 60 , $y4 , IMG_COLOR_STYLED);

    //在画布上随机生成大量黑点，起干扰作用;
    for($i=0 ; $i<80 ; $i++) {
        imagesetpixel($im, rand(0,60), rand(0,20), $black);
    }
    //将四个数字随机显示在画布上,字符的水平间距和位置都按一定波动范围随机生成
    $strx = rand(3,8);
    for($i=0 ; $i<4 ; $i++) {
        $strpos = rand(1,6);
        imagestring($im,5,$strx,$strpos, substr($num,$i,1), $black);
        $strx	+=rand(8,12);
    }

    Image_pNG($im);
    Image_destroy($im);
}

//text/html,application/pdf,application/rtf,application/msword,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-powerpoint,application/vnd.openxmlformats-officedocument.presentationml.presentation,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/x-zip-compressed,application/x-zip-compressed,video/x-ms-wmv,audio/mpeg,video/mp4,text/plain

function upload_img ($img_name , $file_path = null) {
    if ($_FILES[ $img_name ]['error'] > 0) {
        throw new excel('上传错误');
    }

    if (! stristr('image/gif,image/pjpeg,image/png,image/jpg,image/jpeg' , $_FILES[ $img_name ]['type'])) {
        throw new excel('文件类型错误');
    }

    if ($_FILES[ $img_name ]['size'] > 314572800) {//大于300m
        throw new excel('文件过大');
    }

    if (!$file_path) {
        $file_path = $_FILES['attachment']['name'];
    }

    $month = date('Ym');
    move_uploaded_file($_FILES[ $img_name ]['tmp_name'] , $file_path);

    return true;
}

function img_compress (&$target_img , $source_img , $newwidth=222 , $newheight=222) {
    list($srcwidth , $srcheight) = getimagesize($source_img);
    $percent = 1;

    if ($srcwidth >= $newwidth && $srcwidth >= $srcheight) {
        $newheight	= $srcheight / ( $srcwidth / $newwidth );
    }
    else if($srcwidth < $newwidth && $srcheight >= $newheight && $srcheight >= $newwidth) {
        $newwidth	= $srcwidth / ( $srcheight / $newheight );
    }
    else {
        $target_img = $source_img;
        return true;
    }

    $thumb = imagecreatetruecolor($newwidth , $newheight);
    switch ( substr(strrchr($source_img , '.') , 1) ) {
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
        throw new Exception('非法文件');
        break;
    }

    return true;
}
