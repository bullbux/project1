<?php
/**
 * Marketing pages
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
class marketing extends classIlluminz {
    /**
     * Page Layout
     *
     * @var     string  Common page layout for view
     * @access  public
     * @see admin_<method>()
     */
    var $pagelayout = 'admin';

    /**
     * Execute before executing any method of this class
     */
    function beforeFilter(){
        // Administrator
        if($this->params['prefix'] == 'admin'){            
            // If Super admin authentication fails
            if(!$this->Session->checkUserSession($this->Session, array(UserTypeConsts::ADMIN))){
                referer( array('class' => $this->params['class'], 'method' => $this->params['method'], 'pass' =>  $this->params['pass']) );
                redirect( array('class' => 'users', 'method' => 'admin_login') );
            }
        }
        // Add custom scripts
        if(in_array($this->params['method'], array('landlord_slider', 'renter_slider'))){
            $Include = new include_resource();
            $this->Content->addCss($Include->css("global.css"));
            $this->Content->addScript($Include->js("jquery.easing.1.2.js"));
            $this->Content->addScript($Include->js("slides.jquery.js"));
        }
    }

    /**
     * Marketing slider
     *
     * @param <type> $userType  Landlord / Renter
     * @param <type> $page
     * @param <type> $order_by
     * @param <type> $order
     */
    function admin_slider($userType = UserTypeConsts::LANDLORD, $page=1, $order_by = 'order_bit', $order='ASC'){
        $this->set('userType', $userType);
        $this->pageTitle = ($userType == UserTypeConsts::LANDLORD ? 'Landlords' : 'Renters') . ' Marketing Slider';
        $idString = "";
        $table = 'marketing_sliders';
        $list = array();
        if(isset($this->Request->request['actions'])) {
            if(isset($this->Request->request['checkboxArray'])) {
                foreach($this->Request->request['checkboxArray'] as $key => $value) {
                    if($idString == "") {
                        $idString .= $key;
                    }else {
                        $idString .= ", ".$key;
                    }
                }
                if($this->Request->request['actions'] == "Delete") {
                    $where  = "id IN (".$idString.")";
                    $this->Database->mysqlDelete($table, $where);
                }
            }
        }
        $where = "user_type = '$userType'";
        $pageData = $this->Database->paginatequery(array('id', 'filename', 'order_bit'), $table, $where, $order_by." ".$order, "", "", $page, LIMIT);
        $list['records'] = $this->Database->mysqlfetchobjects($pageData['recordset']);
        $list['pages']= $pageData['pages'];
        $list['page']= $page;
        $list['order_by'] = $order_by;
        $list['order'] = $order;
        $this->set('list', $list);
    }

    /**
     * Upload a new slide/image in marketing slider
     *
     * @param <type> $userType  Landlord / Renter
     */
    function admin_uploadSlide($userType = UserTypeConsts::LANDLORD){
        $this->set('userType', $userType);
        $this->pageTitle = 'Upload Image';
        if(isset($this->Request->request['upload'])){
            if(!$this->Request->request['filename']['error']){
                // create directory structure for uploads
                $dest = createDirectory(RESOURCES_ROOT, 'uploads', 'marketing_sliders');
                // Upload image
                $file = upload($this->Request->request['filename'], array('image/png', 'image/jpg', 'image/jpeg', 'image/gif', 'image/pjpeg'), $dest);
                if($file) {
                    $where = "user_type = '$userType'";
                    $result = $this->Database->mysqlSelect(array('order_bit'), 'marketing_sliders', $where, 'order_bit DESC', '', '', 1);
                    $row = $this->Database->mysqlfetchobject($result);
                    if($row)
                        $order = $row->order_bit + 1;
                    else
                        $order = 0;
                    $this->Database->mysqlInsert('marketing_sliders', array('filename', 'user_type', 'order_bit'), array($file, $userType, $order));
                    $this->Session->setFlash('<div class="flash-success">Image uploaded successfully</div>');
                    redirect(array('class'=>'marketing', 'method'=>'admin_slider', $userType));
                }else{
                    $this->Session->setFlash('<div class="flash-error">Oops! Image not uploaded</div>');
                }                
            }else{
                $this->errors['filename'] = 'Please upload an image';
            }
        }
    }

    /**
     * Delete a slide
     *
     * @param <type> $id
     */
    function admin_deleteSlide($id = 0, $userType = UserTypeConsts::LANDLORD, $page=1, $order_by = 'order_bit', $order='ASC'){
        $where = "id = '{$this->Database->escape($id)}'";
        $this->Database->mysqlDelete('marketing_sliders', $where);
        $this->Session->setFlash('<div class="flash-success">Image has been deleted successfully</div>');
        redirect(array('class'=>'marketing', 'method'=>'admin_slider', $userType, $page, $order_by, $order));
    }

    /**
     * Change slides order of displaying in the slider
     *
     * @param <type> $userType
     * @param <type> $page
     * @param <type> $order_by
     * @param <type> $order
     */
    function admin_changeOrder($userType, $id, $orderBit, $page=1, $order_by = 'order_bit', $order='ASC'){
        $userType = $this->Database->escape($userType);
        $where = "id = '{$this->Database->escape($id)}'";
        $where .= " AND user_type = '$userType'";
        $result = $this->Database->mysqlSelect(array('order_bit'), 'marketing_sliders', $where);
        $row = $this->Database->mysqlfetchobject($result);
        if($row) {
            $currentOrder = $row->order_bit;
            $where1 = "user_type = '$userType'";
            if($orderBit == 'dn') {
                $where1 .= " AND order_bit > '$currentOrder'";
                $result = $this->Database->mysqlSelect(array('id', 'order_bit'), 'marketing_sliders', $where1, 'order_bit ASC');
                $row = $this->Database->mysqlfetchobject($result);
                if($row) {
                    $this->Database->mysqlUpdate('marketing_sliders', array('order_bit'), array($row->order_bit), $where);
                    $where = "id = '{$row->id}'";
                    $this->Database->mysqlUpdate('marketing_sliders', array('order_bit'), array($currentOrder), $where);
                }
            }elseif($orderBit == 'up') {
                $where1 .= " AND order_bit < '$currentOrder'";
                $result = $this->Database->mysqlSelect(array('id', 'order_bit'), 'marketing_sliders', $where1, 'order_bit DESC');
                $row = $this->Database->mysqlfetchobject($result);
                if($row) {
                    $this->Database->mysqlUpdate('marketing_sliders', array('order_bit'), array($row->order_bit), $where);
                    $where = "id = '{$row->id}'";
                    $this->Database->mysqlUpdate('marketing_sliders', array('order_bit'), array($currentOrder), $where);
                }
            }
        }
        $this->Session->setFlash('id', $id);
        redirect(array('class'=>'marketing', 'method'=>'admin_slider', $userType, $page, $order_by, $order));
    }

    /**
     * Landlord marketing slider
     */
    function landlord_slider(){
        $this->pageTitle = 'Landlord Marketing Page';
        $this->pagelayout = 'front';
        $where = "user_type = " . UserTypeConsts::LANDLORD;
        $result = $this->Database->mysqlSelect(array('filename'), 'marketing_sliders', $where, 'order_bit ASC', '', '', '');
        $rows = $this->Database->mysqlfetchobjects($result);

        $this->set('slides', $rows);
    }

    /**
     * Renter marketing slider
     */
    function renter_slider(){
        $this->pageTitle = 'Renter Marketing Page';
        $this->pagelayout = 'front';
        $where = "user_type = " . UserTypeConsts::MEMBER;
        $result = $this->Database->mysqlSelect(array('filename'), 'marketing_sliders', $where, 'order_bit ASC', '', '', '');
        $rows = $this->Database->mysqlfetchobjects($result);

        $this->set('slides', $rows);
        $this->view = 'landlord_slider';
    }
}
?>