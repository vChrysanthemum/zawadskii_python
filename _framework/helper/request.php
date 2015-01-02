<?php
Class helper_Request {
    public function filter_page_count($page, $count) {
        $page = (int)$page;
        $count = (int)$count;

        if($page <= 0) $page = 1;
        if($count <= 0 || $count > 200) $count = 20;
        return array($page, $count);
    }

    public function filter_offset_count($offset, $count) {
        $offset = (int)$offset;
        $count = (int)$count;

        if($offset < 0) $offset = 0;
        if($count <= 0 || $count > 200) $count = 20;
        return array($offset, $count);
    }

    public function explode_ids($data_ids) {
        if(!$data_ids) return array();

        $data_ids = explode(',', $data_ids);
        foreach($data_ids as $k => $v) {
            $v = (int)$v;
            if($v <= 0) unset($data_ids[$k]);
            $data_ids[$k] = $v;
        }

        return $data_ids;
    }

    public function filter_has_next($datas, $count) {
        if(count($datas) > $count) {
            array_pop($datas);
            return array($datas, 1);
        }
        else {
            return array($datas, 0);
        }
    }
}
