<?php
Class helper_Html {
    public function pager($page, $count, $totalCount, $baseUrl, $data) {
        unset($data['page']);
        $url = U($baseUrl, $data);

        if($count == null) $count = $totalCount;

        $html = '<ul class="pagination">';
        if(0 == $totalCount || $count == 0) {
            $pageCount = 0;
        }
        else {
            $pageCount = ((int)($totalCount / $count)) + (($totalCount % $count == 0) ? 0 : 1);
        }
        if(strpos($url, '?')) {
            $html .= '<li><a href="' . $url . '&page=1">首页</a></li>';
            for($j = 1; $j <= $pageCount; $j++) {
                $html .= '
                    <li><a href="' . $url . '&page=' . $j . '">' . $j . '</a></li>
                    ';
            }
            $html .= '<li><a href="' . $url . '&page=' . $pageCount . '">尾页</a></li>';
        }
        else {
            $html .= '<li><a href="' . $url . '?page=1">首页</a></li>';
            for($j = 1; $j <= $pageCount; $j++) {
                $html .= '
                    <li><a href="' . $url . '?page=' . $j . '">' . $j . '</a></li>
                    ';
            }
            $html .= '<li><a href="' . $url . '?page=' . $pageCount . '">首页</a></li>';
        }
        $html .= '</ul>';

        return $html;
    }	
}
