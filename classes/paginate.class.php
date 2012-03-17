<?php
class paginate extends classIlluminz {
    var $pagecounter=array();
    function pagination($pages,$page,$link,$order_by="",$order="") {
        $html = "";
        $totalpages = $pages[1];
        if($totalpages > 0) {
            if(is_numeric($page)) {
                if($page <= 0)
                    $page = 1;
                elseif($page > $totalpages) {
                    $page = $totalpages;
                }
            }else {
                $page = 1;
            }            
            $previousLink	= $link;
            $nextLink = $link;
            $previousLink['param']	= $page - 1;
            $nextLink['param']	= $page + 1;
            if($order_by != "") {
                $previousLink['order_by']	= $order_by;
                $previousLink['order']		= $order;
                $nextLink['order_by']	= $order_by;
                $nextLink['order']		= $order;
            }
            $qsa = explode('?', $_SERVER['REQUEST_URI']);
            $nextLink['qsa'] = $previousLink['qsa'] = isset($qsa[1]) ? '?'.$qsa[1] : '';
            $this->pagecounter['previousLink'] = $previousLink;
            $this->pagecounter['pages'] = $pages[0];
            $this->pagecounter['nextLink'] = $nextLink;
            $this->pagecounter['orderby'] = $order_by;
            $this->pagecounter['order'] = $order;

            $this->pagecounter['page'] = $page;
            $this->pagecounter['link'] = $link;
            $this->pagecounter['totalpages']=$totalpages;
            $this->set('qsa', $nextLink['qsa']);
        }
    }

    function ajaxPagination($pages,$page,$link,$order_by="",$order="",$updateElement = '') {
        $html .= "";
        $totalpages = $pages[1];
        if($totalpages > 0) {
            if(is_numeric($page)) {
                if($page <= 0)
                    $page = 1;
                elseif($page > $totalpages) {
                    $page = $totalpages;
                }
            }else {
                $page = 1;
            }
            $previousLink	= $link;
            $nextLink		= $link;
            $previousLink['param']	= $page - 1;
            $nextLink['param']	= $page + 1;
            if($order_by != "") {
                $previousLink['order_by']	= $order_by;
                $previousLink['order']		= $order;
                $nextLink['order_by']	= $order_by;
                $nextLink['order']		= $order;
            }
            $qsa = explode('?', $_SERVER['REQUEST_URI']);
            $nextLink['qsa'] = $previousLink['qsa'] = isset($qsa[1]) ? '?'.$qsa[1] : '';
            $this->pagecounter['previousLink'] = $previousLink;
            $this->pagecounter['pages'] = $pages[0];
            $this->pagecounter['nextLink'] = $nextLink;
            $this->pagecounter['orderby'] = $order_by;
            $this->pagecounter['order'] = $order;
            $this->pagecounter['page'] = $page;
            $this->pagecounter['link'] = $link;
            $this->pagecounter['updateElement'] = $updateElement;
            $this->pagecounter['totalpages']=$totalpages;
            $this->set('qsa', $nextLink['qsa']);
        }
    }
}
?>