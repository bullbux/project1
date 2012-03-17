<?php
class pages extends classIlluminz {
    var $pagelayout = 'admin';
    var $page=array();
    var $pageTitle;
    var $metaKeywords = '';
    var $metaDescription = '';
    var $success = array();
    var $errors = array();
    var $pages = array();

    public function admin_manage($page=1,$order_by = 'id', $order='desc') {
        $this->pageTitle = 'Static Pages Management';
        if($this->Session->checkAdminSession($this->Session,MANAGE_SITE_PAGES)) {
            $idString = "";
            $table  = "pages";
            if($this->Request->request['actions']) {
                if(isset($this->Request->request['checkboxArray'])) {
                    foreach($this->Request->request['checkboxArray'] as $key => $value) {
                        if($idString == "") {
                            $idString .= $key;
                        }else {
                            $idString .= ", ".$key;
                        }
                    }
                    $where  = "id in (".$idString.")";
                    if($this->Request->request['actions'] == "Activate") {
                        $fields = array("status");
                        $values = array("1");
                        $this->Session->setFlash('<div class="flash-success">Pages have been activated successfully.</div>');
                        $this->Database->mysqlUpdate($table,$fields,$values,$where);
                    }
                    if($this->Request->request['actions'] == "De-Activate") {
                        $fields = array("status");
                        $values = array("0");
                        $this->Session->setFlash('<div class="flash-success">Pages have been deactivated successfully.</div>');
                        $this->Database->mysqlUpdate($table,$fields,$values,$where);
                    }
                    if($this->Request->request['actions'] == "Delete") {
                        $this->Session->setFlash('<div class="flash-success">Pages have been deleted successfully.</div>');
                        $this->Database->mysqlDelete($table,$where);
                    }
                }
            }
            $fields = "*";
            $pageData = $this->Database->paginatequery($fields, $table, "", $order_by." ".$order, "", "", $page, LIMIT);
            $this->page['records'] = $this->Database->mysqlfetchobjects($pageData['recordset']);
            $this->page['pages']= $pageData['pages'];
            $this->page['page']= $page;
            $this->page['order_by'] = $order_by;
            $this->page['order'] = $order;
        }else {
            referer(array('class'=>'pages', 'method'=>'admin_manage', $page, $order_by, $order));
            redirect(array('class'=>'users', 'method'=>'admin_login'));
        }
    }

    public function admin_add() {
        if($this->Session->checkAdminSession($this->Session,MANAGE_SITE_PAGES)) {
            $this->pageTitle = 'New Static Page';
            if(isset($this->Request->request['submit'])) {
                if($this->_validatePage()) {
                    $now	= date("Y-m-d H:i:s");
                    $table	= "pages";
                    $fields = array("pageTitle","pageKeywords","pageDescription","pageBody","created","modified", 'showTitle','parent_id');
                    $values = array($this->Request->request['pageTitle'], $this->Request->request['pageKeywords'], $this->Request->request['pageDescription'], $this->Request->request['pageBody'], $now, $now, $this->Request->request['showTitle'],$this->Request->request['parent_id']);
                    $this->Database->mysqlInsert($table,$fields,$values);
                    $lastId = $this->Database->lastInsertId();
                    $this->getorder($lastId);
                    $this->order = array_reverse($this->order);
                    $this->order[] = zerofill($lastId,5);

                    $disporder = implode('.',$this->order);
                    $this->Database->mysqlRawquery("update pages set disporder = '".$disporder."' where id = ".$lastId);
                    $this->Session->setFlash('<div class="flash-success">Page has been saved successfully.</div>');
                    redirect(array("class"=>"pages","method"=>"admin_manage"));
                }
            }
        }else {
            referer(array('class'=>'pages', 'method'=>'admin_add'));
            redirect(array('class'=>'users', 'method'=>'admin_login'));
        }
        $this->set('pageList', $this->getPagess());
    }

    private function getorder($pageid) {
        $where = "id = ".$pageid;
        $result = $this->Database->mysqlSelect(array('parent_id'),'pages',$where);
        $page = $this->Database->mysqlfetchobject($result);
        if($page->parent_id!=0) {
            $this->order[] = zerofill($page->parent_id,5);
            $this->getorder($page->parent_id);
        }
    }

    public function admin_edit($slug='', $page="1",$order_by="id",$order="desc") {
        if($this->Session->checkAdminSession($this->Session,MANAGE_SITE_PAGES)) {
            $this->pageTitle = 'Edit a Static Page';
            $where = "slug = '{$this->Database->escape($slug)}'";
            if(isset($this->Request->request['submit'])) {
                if($this->_validatePage()) {
                    $now	= date("Y-m-d H:i:s");
                    $table	= "pages";
                    $fields = array("pageTitle","pageKeywords","pageDescription","pageBody", "modified", 'showTitle','parent_id');
                    $values = array($this->Request->request['pageTitle'], $this->Request->request['pageKeywords'], $this->Request->request['pageDescription'], $this->Request->request['pageBody'], $now, $this->Request->request['showTitle'],$this->Request->request['parent_id']);
                    $this->Database->mysqlUpdate($table,$fields,$values,$where);
                    $result = $this->Database->mysqlSelect('id',$table,$where);
                    $row = $this->Database->mysqlfetchobject($result);
                    $this->getorder($row->id);
                    $this->order = array_reverse($this->order);
                    $disporder = implode('.',$this->order);
                    $this->Database->mysqlRawquery("update pages set disporder = '".$disporder."' where id = ".$lastId);
                    $this->Session->setFlash('<div class="flash-success">Page has been updated successfully.</div>');
                    redirect(array("class"=>"pages","method"=>"admin_manage", $page, $order_by, $order));
                }
            }else {
                $result = $this->Database->mysqlSelect("*", 'pages', $where);
                $rows = $this->Database->mysqlfetchassoc($result);
                if($rows) {
                    $this->Request->request = $rows[0];
                    $this->set("selected_page",$rows[0]['parent_id']);
                }

            }
        }else {
            referer(array('class'=>'pages', 'method'=>'admin_edit', $slug, $page, $order_by, $order));
            redirect(array('class'=>'users', 'method'=>'admin_login'));
        }
        $this->set('pageList', $this->getPagess());
    }


    function getPagess($status = '') {
        $pages = array();
        if($status)
            $where = "status = '".ACTIVE."'";
        else
            $where = "";
        $result = $this->Database->mysqlSelect(array('*'),'pages',$where,"disporder ASC","","","");
        $pagess = $this->Database->mysqlfetchobjects($result);
        foreach($pagess as $page) {
            $level = array();
            $level = explode('.',$page->disporder);
            $space="";
            foreach($level as $value) {
                if(count($level)>1)
                    $space = $space."- ";
            }
            $pages[$page->id] = $space.$page->pageTitle;
        }
        return $pages;
    }

    function admin_toggleStatus($slug = 0) {
        if($this->Session->checkAdminSession($this->Session,MANAGE_SITE_PAGES)) {
            if($slug) {
                $Include = new include_resource();
                $where = "slug = '{$this->Database->escape($slug)}'";
                $result = $this->Database->mysqlSelect(array('status'), 'pages', $where);
                $row = $this->Database->mysqlfetchobject($result);
                if($row) {
                    if($row->status) {
                        $newStatus = INACTIVE;
                        echo json_encode(array($Include->image('admin/inactive.png', array('border'=>0, 'title'=>'Inactive', 'alt'=>'inactive')), '<div class="flash-success">Page has been deactivated successfully.</div>'));
                    }else {
                        $newStatus = ACTIVE;
                        echo json_encode(array($Include->image('admin/active.png', array('border'=>0, 'title'=>'Active', 'alt'=>'active')), '<div class="flash-success">Page has been activated successfully.</div>'));
                    }
                    $this->Database->mysqlUpdate('pages', array('status'), array($newStatus), $where);
                }else {
                    echo json_encode(array($Include->image('inactive.gif', array('border'=>0, 'title'=>'Inactive', 'alt'=>'inactive')), '<div class="flash-error">Sorry, The status of this page can not be changed.</div>'));
                }
            }
        }
        exit;
    }

    function admin_delete($slug='',$page="1",$order_by="id",$order="desc") {
        if($this->Session->checkAdminSession($this->Session,MANAGE_SITE_PAGES)) {
            if($slug) {
                $where = "slug = '{$this->Database->escape($slug)}'";
                $table  = "pages";
                $this->Database->mysqlDelete($table,$where);
            }
            redirect(array("class"=>"pages","method"=>"admin_manage",$page,$order_by,$order));
        }else {
            referer(array('class'=>'pages', 'method'=>'admin_delete', $slug, $page, $order_by, $order));
            redirect(array('class'=>'users', 'method'=>'admin_login'));
        }
    }


    function getpages() {
        $condition = "status = '".ACTIVE."'";
        $result = $this->Database->mysqlSelect(array('pageTitle','slug'),'pages',$condition);
        $pag = $this->Database->mysqlfetchobjects($result);
        $this->pages = $pag;
    }

    function slug($slug) {
        $this->pagelayout = 'front';
        $condition = "status = '".ACTIVE."' and slug = '".$slug."'";
        $result = $this->Database->mysqlSelect(array('*'),'pages',$condition);
        $page = $this->Database->mysqlfetchobject($result);
        if($page) {
            $this->set('page', $page);
            $this->pageTitle = $page->pageTitle;
            $this->metaKeywords = $page->pageKeywords;
            $this->metaDescription = $page->pageDescription;
        }else {
            redirect(array('class'=>'errors', 'methed'=>'notFound404'));
        }
    }

    function index() {
        $this->pagelayout = 'front';
    }

    private function _validatePage() {
        if(trim($this->Request->request['pageTitle']) == "" ) {
            $this->errors['pageTitle'] = "* Please enter the page title.";
        }

        if(empty($this->errors))
            return true;

        return false;
    }


}
?>