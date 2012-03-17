<?php
/**
 * FAQs (Frequently Asked Questions) / Help Center
 *
 * Here, admin_<method> represents a task to be performed by the site administrator.
 * _<method> is a private member function.
 *
 * @version 1.0
 * @since 1.0
 * @access public
 * @extends  classIlluminz   Base controller class of framework
 *
 */
class faqs extends classIlluminz {
    /**
     * Page Layout
     *
     * @var     string  Common page layout for view
     * @access  public
     * @see admin_<method>()
     */
    var $pagelayout = 'front';

    /**
     * Execute before executing any method of this class
     */
    function beforeFilter(){
        // Administrator
        if($this->params['prefix'] == 'admin'){
            $this->pagelayout = 'admin';
            // If Super admin authentication fails
            if(!$this->Session->checkUserSession($this->Session, array(UserTypeConsts::ADMIN))){
                referer( array('class' => $this->params['class'], 'method' => $this->params['method'], 'pass' =>  $this->params['pass']) );
                redirect( array('class' => 'users', 'method' => 'admin_login') );
            }
        }        
    }

    /**
     * Help Center / FAQs page
     */
    function index(){
        $this->pageTitle = 'Help Center | FAQs';
        $customField = '';
        $orderby = '';
        $where = '1';
        if($this->Request->request['s']){
            $fulltext = "(MATCH(fc.category, f.question) AGAINST ('{$this->Request->request['s']}*' IN BOOLEAN MODE))";
            $customField = ', ' . $fulltext." AS score";
            $orderby = 'score,';
            $where = $fulltext;
        }
        $sql = "SELECT fc.id, fc.category, COUNT(f.id) AS total_faqs $customField FROM faq_categories AS fc
                INNER JOIN faqs AS f ON fc.id = f.faq_cat_id
                WHERE $where
                GROUP BY fc.id
                ORDER BY $orderby f.id DESC";        
        $result = $this->Database->mysqlQuery($sql);
        $rows = $this->Database->mysqlfetchobjects($result);
        $this->set('categories', $rows);
    }

    /**
     * List of all the faqs
     *
     * @param <type> $catId
     */
    function listfaqs($catId = 0){        
        $this->element = 'faqs/list_faqs';
        $where = "faq_cat_id = '{$this->Database->escape($catId)}'";
        $where .= " AND status = '1'";
        $orderby = 'id';
        $customField = 'id';
        if($this->Request->request['s']){
            $fulltext = "(MATCH(question) AGAINST ('{$this->Request->request['s']}*' IN BOOLEAN MODE))";
            $customField = $fulltext." AS score";
            $orderby = 'score';
            $where .= " AND $fulltext";
        }
        $result = $this->Database->mysqlSelect(array('question', 'answer', $customField), 'faqs', $where, $orderby.' DESC', '', '', '');
        $rows = $this->Database->mysqlfetchassoc($result);
        $this->set('faqs', $rows);
    }

    /**
     * List of all the FAQs
     */
    function admin_index($page=1,$order_by = 'id', $order='desc'){
        $this->pageTitle = 'FAQs';
        $idString = "";
        $list = array();
        $table  = "faqs";
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
                    $this->Session->setFlash('<div class="flash-success">FAQs have been activated successfully.</div>');
                    $this->Database->mysqlUpdate($table,$fields,$values,$where);
                }
                if($this->Request->request['actions'] == "De-Activate") {
                    $fields = array("status");
                    $values = array("0");
                    $this->Session->setFlash('<div class="flash-success">FAQs have been deactivated successfully.</div>');
                    $this->Database->mysqlUpdate($table,$fields,$values,$where);
                }
                if($this->Request->request['actions'] == "Delete") {
                    $this->Session->setFlash('<div class="flash-success">FAQs have been deleted successfully.</div>');
                    $this->Database->mysqlDelete($table,$where);
                }
            }
        }
        $fields = "*";
        $pageData = $this->Database->paginate(array('faqs', 'faq_categories'), array(array('faqCategories.faq_cat_id')), "", 'faqs.'.$order_by." ".$order, "", "", $page, LIMIT);
        $list['records'] = $this->Database->mysqlfetchassoc($pageData['recordset']);
        $list['pages']= $pageData['pages'];
        $list['page']= $page;
        $list['order_by'] = $order_by;
        $list['order'] = $order;
        $this->set('list', $list);        
    }

    /**
     * Add a new FAQ
     */
    function admin_add(){
        $this->pageTitle = 'Add new FAQ';
        $this->set('action', 'Add');
        if($this->Request->request['save']){
            if($this->_validate()){
                $date = date('Y-m-d H:i:s');
                $this->Database->mysqlInsert('faqs', array('faq_cat_id', 'question', 'answer', 'created', 'modified'), array($this->Request->request['faq_cat_id'], $this->Request->request['question'], $this->Request->request['answer'], $date, $date));
                $this->Session->setFlash('<div class="flash-success">FAQ has been saved successfully</div>');
                redirect(array('class'=>'faqs', 'method'=>'admin_index'));
            }
        }
        $this->set('categories', $this->getCategories());
    }

    /**
     * Add a new FAQ
     */
    function admin_edit($id = 0, $page=1, $order_by = 'id', $order='desc'){
        $this->pageTitle = 'Update FAQ';
        $this->view = 'admin_add';
        $this->set('action', 'Update');
        $where = "id = '{$this->Database->escape($id)}'";
        if($this->Request->request['save']){
            if($this->_validate()){
                $date = date('Y-m-d H:i:s');
                $this->Database->mysqlUpdate('faqs', array('faq_cat_id', 'question', 'answer', 'modified'), array($this->Request->request['faq_cat_id'], $this->Request->request['question'], $this->Request->request['answer'], $date), $where);
                $this->Session->setFlash('<div class="flash-success">FAQ has been updated successfully</div>');
                redirect(array('class'=>'faqs', 'method'=>'admin_index', $page, $order_by, $order));
            }
        }else{
            $result = $this->Database->mysqlSelect(array('faq_cat_id', 'question', 'answer'), 'faqs', $where);
            $rows = $this->Database->mysqlfetchassoc($result);
            if($rows){
                $this->Request->request = $rows[0];
            }
            $this->set('categories', $this->getCategories());
        }
    }

    /**
     * Delete a FAQ
     *
     * @param   $id
     */
    function admin_delete($id = 0, $page=1, $order_by = 'id', $order='desc'){
        $where = "id = '{$this->Database->escape($id)}'";
        $this->Database->mysqlDelete('faqs', $where);
        $this->Session->setFlash('<div class="flash-success">FAQ has been deleted successfully</div>');
        redirect(array('class'=>'faqs', 'method'=>'admin_index', $page, $order_by, $order));
    }

    /**
     * Change FAQ status
     *
     * @param <type> $id
     */
    function admin_toggleStatus($id = 0) {
        $Include = new include_resource();
        $where = "id = '{$this->Database->escape($id)}'";
        $result = $this->Database->mysqlSelect(array('status'),'faqs',$where);
        $row = $this->Database->mysqlfetchobject($result);
        if($row) {
            if($row->status) {
                $newStatus = 0;
                echo json_encode(array($Include->image('admin/inactive.png'), '<div class="flash-success">FAQ has been deactivated successfully.</div>'));
            }else {
                $newStatus = 1;
                echo json_encode(array($Include->image('admin/active.png'), '<div class="flash-success">FAQ has been activated successfully.</div>'));
            }
            $this->Database->mysqlUpdate('faqs', array('status'), array($newStatus), $where);
        }
        exit;
    }

    /**
     * List of all the FAQs categories
     */
    function admin_categories($page=1,$order_by = 'id', $order='desc'){
        $this->pageTitle = 'FAQ Categories';
        $idString = "";
        $list = array();
        $table  = "faq_categories";
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
                    $this->Session->setFlash('<div class="flash-success">Categories have been activated successfully.</div>');
                    $this->Database->mysqlUpdate($table,$fields,$values,$where);
                }
                if($this->Request->request['actions'] == "De-Activate") {
                    $fields = array("status");
                    $values = array("0");
                    $this->Session->setFlash('<div class="flash-success">Categories have been deactivated successfully.</div>');
                    $this->Database->mysqlUpdate($table,$fields,$values,$where);
                }
                if($this->Request->request['actions'] == "Delete") {
                    $this->Session->setFlash('<div class="flash-success">Categories have been deleted successfully.</div>');
                    $this->Database->mysqlDelete($table,$where);
                }
            }
        }
        $fields = "*";
        $this->Database->customFields(array('COUNT(faqs.id) AS total_faqs'));
        $pageData = $this->Database->paginate(array('faq_categories', 'left faqs'), array(array(), array('faqCategories.faq_cat_id')), "", 'faqCategories.'.$order_by." ".$order, "faqCategories.id", "", $page, LIMIT);
        $list['records'] = $this->Database->mysqlfetchassoc($pageData['recordset']);
        $list['pages']= $pageData['pages'];
        $list['page']= $page;
        $list['order_by'] = $order_by;
        $list['order'] = $order;
        $this->set('list', $list);
    }

    /**
     * Add a new FAQ category
     */
    function admin_addCategory(){
        $this->pageTitle = 'Add FAQ Category';
        $this->set('action', 'Add');
        if($this->Request->request['save']){
            if(trim($this->Request->request['category']) != ''){
                $date = date('Y-m-d H:i:s');
                $this->Database->mysqlInsert('faq_categories', array('category', 'created', 'modified'), array($this->Request->request['category'], $date, $date));
                $this->Session->setFlash('<div class="flash-success">Category has been saved successfully</div>');
                redirect(array('class'=>'faqs', 'method'=>'admin_categories'));
            }else{
                $this->errors['category'] = 'Category name can not be empty.';
            }
        }
    }

    /**
     * Update FAQ category name
     *
     * @param   $slug
     */
    function admin_editCategory($slug = '', $page=1,$order_by = 'id', $order='desc'){
        $this->pageTitle = 'Update FAQ Category';
        $this->view = 'admin_addCategory';
        $this->set('action', 'Update');
        $where = "slug = '{$this->Database->escape($slug)}'";
        if($this->Request->request['save']){
            if(trim($this->Request->request['category']) != ''){
                $date = date('Y-m-d H:i:s');                
                $this->Database->mysqlUpdate('faq_categories', array('category', 'modified'), array($this->Request->request['category'], $date), $where);
                $this->Session->setFlash('<div class="flash-success">Category has been updated successfully</div>');
                redirect(array('class'=>'faqs', 'method'=>'admin_categories', $page, $order_by, $order));
            }else{
                $this->errors['category'] = 'Category name can not be empty.';
            }
        }else{
            $result = $this->Database->mysqlSelect(array('category'), 'faq_categories', $where);
            $rows = $this->Database->mysqlfetchassoc($result);
            if($rows){
                $this->Request->request = $rows[0];
            }
        }
    }

    /**
     * Delete a FAQ category
     *
     * @param   $slug
     */
    function admin_deleteCategory($slug = '', $page=1, $order_by = 'id', $order='desc'){
        $where = "slug = '{$this->Database->escape($slug)}'";
        $this->Database->mysqlDelete('faq_categories', $where, true, 'faq_cat_id');
        $this->Session->setFlash('<div class="flash-success">Category has been deleted successfully</div>');
        redirect(array('class'=>'faqs', 'method'=>'admin_categories', $page, $order_by, $order));
    }

    /**
     * Change FAQ category status
     *
     * @param <type> $slug
     */
    function admin_toggleCategoryStatus($slug = '') {
        $Include = new include_resource();
        $where = "slug = '{$this->Database->escape($slug)}'";
        $result = $this->Database->mysqlSelect(array('status'),'faq_categories',$where);
        $row = $this->Database->mysqlfetchobject($result);
        if($row) {
            if($row->status) {
                $newStatus = 0;
                echo json_encode(array($Include->image('admin/inactive.png'), '<div class="flash-success">Category has been deactivated successfully.</div>'));
            }else {
                $newStatus = 1;
                echo json_encode(array($Include->image('admin/active.png'), '<div class="flash-success">Category has been activated successfully.</div>'));
            }
            $this->Database->mysqlUpdate('faq_categories', array('status'), array($newStatus), $where);
        }
        exit;
    }

    function getCategories($status = null){
        $where = '';
        if($status)
            $where = "status = '$status'";
        $result = $this->Database->mysqlSelect(array('category'),'faq_categories', $where);
        $rows = $this->Database->mysqlfetchlist($result);

        return $rows;
    }
 
    private function _validate(){
        if(trim($this->Request->request['question']) == ''){
            $this->errors['question'] = 'Question can not be empty.';
        }
        if(trim($this->Request->request['answer']) == ''){
            $this->errors['answer'] = 'Answer can not be empty.';
        }
        if(!($this->Request->request['faq_cat_id'])){
            $this->errors['faq_cat_id'] = 'Category can not be empty.';
        }

        if($this->errors)
            return false;

        return true;
    }
}
?>