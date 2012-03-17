<?php
/**
 * Performs a user related all the tasks like login, add, delete, update etc.
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
class users extends classIlluminz {
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
        if($this->params['prefix'] == 'admin' && $this->params['method'] != 'admin_login'){
            $this->pagelayout = 'admin';
            // If Super admin authentication fails
            if(!$this->Session->checkUserSession($this->Session, array(UserTypeConsts::ADMIN))){
                referer( array('class' => $this->params['class'], 'method' => $this->params['method'], 'pass' =>  $this->params['pass']) );
                redirect( array('class' => 'users', 'method' => 'admin_login') );
            }
        }
        // Landlord
        if($this->params['prefix'] == 'dashboard'){
            $this->pagelayout = 'front';
            // If landlord authentication fails
            if(!$this->Session->checkUserSession($this->Session, array(UserTypeConsts::LANDLORD))){
                referer( array('class' => $this->params['class'], 'method' => $this->params['method'], 'pass' =>  $this->params['pass']) );
                redirect( array('class' => 'users', 'method' => 'login') );
            }
        }
        // Member/Renter
        if($this->params['prefix'] == 'member'){
            $this->pagelayout = 'front';
            // If member authentication fails
            if(!$this->Session->checkUserSession($this->Session, array(UserTypeConsts::MEMBER))){
                referer( array('class' => $this->params['class'], 'method' => $this->params['method'], 'pass' =>  $this->params['pass']) );
                redirect( array('class' => 'users', 'method' => 'login') );
            }
        }
        // Allow signup if no user is already logged in
        if(in_array($this->params['method'], array('renter_signup', 'landlord_signup', 'signupWithFb')) && $this->Session->checkSession($this->Session)){
            redirect( WWW_ROOT );
        }
    }

    function admin_login() {
        $this->pagelayout = 'admin_login';
        $this->pageTitle = 'Admin Login'; 
        $this->_login(); 
    }

    /**
     * Site User Login
     *
     * @access public
     * @see _login()
     */
    function login() {
        if(isAjax())
            $this->element = 'users/ajaxlogin';
        else
            $this->element = 'users/login';
        $this->pageTitle = 'Login';
        $this->_login(false);
    }
	
	function admin_roles($id=0) {
	If(isset($_POST['role'])){
	$id = $_POST['user_id'];
	$role = $_POST['role'];
	 $where .= "id = '$id'";
	  $this->Database->mysqlUpdate('users', array('user_type'), array($role), $where);
		  $this->Session->setFlash("<div class='flash-success'>User role is updated successfully....</div>");
	redirect(array('class'=>'users', 'method'=>'admin_manage'));
	}
	$result = mysql_query("SELECT user_type FROM users WHERE id='{$id}'");
	$data=mysql_fetch_object($result);
	$list=$data->user_type;
	$this->element = 'users/roles';
	$this->set('id', $id);
	$this->set('list', $list);
    }
	
	function admin_details($id=0){
	        $where = "users.id = '$id'";
        $result = $this->Database->assoc(array('users', 'left users_profiles'), array(array(), array('users.user_id')), $where);
        $rows = $this->Database->mysqlfetchassoc($result);
		$this->view = 'admin_details';
		$this->set('details', $rows);
		//var_dump($rows); exit;		
	}

    /**
     * Creates a user/admin login session
     *
     * @param boolean $adminLogin   Check usertype to validate a user login
     * @access private
     */
    private function _login($adminLogin = true) {
        $redirection = '';
        $record = array();
        if(!$this->Session->checkSession($this->Session)) {
            if($this->Request->request['submit']) {
                if($this->_validateLogin($adminLogin)) {
                    $where = "username = '{$this->Database->escape(strtolower($this->Request->request['username']))}'";
                    $where .= " AND password = '".md5($this->Request->request['password'])."'";
                    $where .= " AND status = ".UserStatusConsts::ACTIVE;
                    $result = $this->Database->mysqlSelect(array('id', 'user_type', 'username', 'email', 'last_login_time', 'current_login_time', 'status'), 'users', $where);
                    $rows = $this->Database->mysqlfetchassoc($result);
                    if($rows) {
                        $record = $rows[0];
                        $where = "user_id = " . $record['id'];
                        $result = $this->Database->mysqlSelect(array('name'), 'users_profiles', $where);
                        $row = $this->Database->mysqlfetchobject($result);
                        if($row) {
                            $record['name'] = $row->name;
                        }
                        // Create logged in user session
                        $this->Session->write('User',$record);

                        // Remember user on the system if he/she has checked "Remember Me" checkbox
                        if($this->Request->request['rememberMe']) {
                            $this->_rememberMe($record['id']);
                        }

                        // Update last login time
                        $where = "id = " . $record['id'];
                        $this->Database->mysqlUpdate('users', array('last_login_time', 'current_login_time', 'is_loggedin'), array($record['current_login_time'], date('Y-m-d H:i:s'), ACTIVE), $where);

                        // Set redirection
                        $referer = getReferer();
                        if($referer){
                            $redirectTo = $referer;
                        }else{
                            if($record['user_type'] == UserTypeConsts::ADMIN)
                                $redirection = '/admin/properties';
                            elseif($record['user_type'] == UserTypeConsts::LANDLORD)
                                $redirection = '/dashboard/properties';
                            elseif($record['user_type'] == UserTypeConsts::MEMBER)
                                $redirection = '';
                            else
                                $redirectTo = isset($this->Request->request['redirect']) && $this->Request->request['redirect'] ? $this->Request->request['redirect'] : '';
                        }
                        if($redirectTo)
                            redirect($redirectTo);
                        else
                            redirect(WWW_ROOT.$redirection);
                    }else {
                        $this->Session->setFlash('<div class="flash-error">You are not authorized for this action.</div>');
                        redirect(WWW_ROOT);
                    }
                }
            }
        }else {
            $this->Session->setFlash('<div class="flash-error">You are not authorized for this action.</div>');
            redirect(WWW_ROOT);
        }
    }

    private function _rememberMe($userId) {
        $userId = base64_encode($userId);
        //Set a new cookie
        setcookie("rememberMeCookie", $userId, time()+(3600*24), '/');  /* expire in 24 hours */
    }

    /**
     * Check if system already remember user session
     */
    function checkRememberMe() {
        if(!$this->Session->checkSession($this->Session)) {
            //Read remember me cookie
            if(isset($_COOKIE["rememberMeCookie"]) && $_COOKIE["rememberMeCookie"]) {
                $userId = base64_decode($_COOKIE["rememberMeCookie"]);
                $where = "id = '{$this->Database->escape($userId)}'";
                $result = $this->Database->mysqlSelect(array('id', 'user_type', 'username', 'email', 'last_login_time', 'current_login_time', 'status'), 'users', $where);
                $row = $this->Database->mysqlfetchassoc($result);
                if($row) {
                    $record = $rows[0];
                    $where = "user_id = " . $record['id'];
                    $result = $this->Database->mysqlSelect(array('name'), 'users_profiles', $where);
                    $row = $this->Database->mysqlfetchobject($result);
                    if($row) {
                        $record['name'] = $row->name;
                    }
                    // Set user session
                    $this->Session->write('User',$record);
                }
            }
        }
    }

    /**
     * Update Superadmin profile
     */
    function admin_profile() {
        $this->pagelayout = 'admin';
        $this->pageTitle = 'Admin Profile';
        $where = "id = '{$this->Session->read('User.id')}'";
        if($this->Request->request['submit']) {
            if($this->_validateSuperAdminProfile()) {
                if(trim($this->Request->request['username']) != '') {
                    $this->Database->mysqlUpdate('users', array('username'), array($this->Request->request['username']), $where);
                    // Update user session
                    $this->Session->writeArray('User', $this->Request->request['username'], 'username');
                }
                if(trim($this->Request->request['password']) != '') {
                    $this->Database->mysqlUpdate('users', array('password'), array(md5($this->Request->request['password'])), $where);
                }

                $this->Session->setFlash('<div class="flash-success">Your Profile has been updated successfully.</div>');
            }
        }else {
            $result = $this->Database->mysqlSelect(array('username'),'users',$where);
            $rows = $this->Database->mysqlfetchassoc($result);
            if($rows) {
                $this->Request->request = $rows[0];
            }
        }
    }

    /**
     * Forgot password
     */
    public function forgotPassword() {
        $this->pagelayout = 'front';
        if(isset($this->Request->request['send'])) {
            $rows = $this->_validateForgotPassword();
            if($rows) {
                // Generate a new random password.
                $newPassword = createRandomPassword(12);                
                $changePasswordKey = md5(uniqid());
                // Update user password;
                $where = 'id = ' . $rows[0]['users.id'];
                $this->Database->mysqlUpdate('users', array('forgot_pwd', 'forgot_pwd_key'), array(md5($newPassword), $changePasswordKey), $where);               
                $to = array($rows[0]['users.email']);
                $from = SITE_EMAIL;
                $fromName = SITE_NAME;
                $subject = "Forgot Password";
                // Send recover lost passord email
                email(
                    $from,
                    $fromName,
                    $to,
                    $subject,
                    array(
                        'class'=>'users',
                        'method'=>'forgotPasswordEmail',
                        array(
                            'name'=>$rows[0]['usersProfiles.name'],
                            'new_password'=>$newPassword,
                            'change_password_key'=>$changePasswordKey
                        )
                    )
                );
                $this->Session->setFlash('A new password has been sent to your registered email-id');
            }
        }
    }

    /**
     * Send forgot password email to user
     */
    function forgotPasswordEmail() {
        $this->element = 'emails/forgot_password_email';
    }

    /**
     * Change forgot password
     * 
     * @param <type> $key
     */
    function changePassword($key = 0) {
        $this->pagelayout = 'front';
        if($key) {
            $where = "forgot_pwd_key = '{$this->Database->escape($key)}'";
            $result = $this->Database->mysqlSelect(array('id', 'username', 'forgot_pwd', 'user_type', 'email', 'current_login_time'), 'users', $where);
            $row = $this->Database->mysqlfetchobject($result);
            if($row) {
                $created = date('Y-m-d H:i:s');
                $where = "id = " . $row->id;
                $this->Database->mysqlUpdate('users', array('password', 'last_login_time', 'current_login_time'), array($row->forgot_pwd, $row->current_login_time, $created), $where);
                $where = "user_id = " . $row->id;
                $result = $this->Database->mysqlSelect('users_profiles',array('name', 'created','modified'), $where);
                $profile = $this->Database->mysqlfetchobject($result);
                // Create a user session with the site                
                $record = array();
                $record['id'] = $row->id;
                $record['user_type'] = $row->user_type;
                $record['username'] = $row->username;
                $record['name'] = $profile->name;
                $record['email'] = $row->email;
                $record['last_login_time'] = $created;
                $record['current_login_time'] = $created;
                $this->Session->write('User', $record);

                $this->Session->setFlash('<div class="flash-success">Your password has been changed successfully</div>');

                if($row->user_type == UserTypeConsts::LANDLORD)
                    redirect(array('class'=>'properties', 'method'=>'dashboard_index'));
                elseif($row->user_type == UserTypeConsts::ADMIN)
                    redirect(array('class'=>'properties', 'method'=>'admin_index'));
            }else {
                $this->Session->setFlash('<div class="flash-error">Oops! Invalid key.</div>');
            }
        }

        redirect(WWW_ROOT);
    }

    /**
     * Validate forgot password form fields
     * 
     * @return <type>
     */
    private function _validateForgotPassword() {
        if(trim($this->Request->request['identity']) == '') {
            $this->errors['identity'] = 'Email-Id/Username can not be empty.';
        }else {
            $identity = $this->Database->escape($this->Request->request['identity']);
            $where = "users.status = '1'";
            $where .= " AND (users.email = '$identity'";
            $where .= " OR users.username = '$identity')";
            $result = $this->Database->assoc(array('users', 'left users_profiles'), array(array(), array('users.user_id')), $where);
            $rows = $this->Database->mysqlfetchassoc($result);

            if($rows) {
                return $rows;
            }

            $this->errors['identity'] = 'Email-Id/Username does not exist.';
        }

        return false;
    }

    /**
     * Login form validations
     */
    private function _validateLogin($adminLogin = true) {
        if(trim($this->Request->request['username'] == '')) {
            $this->errors['username'] = 'Username can not be empty.';
        }else {
            $where = " username = '{$this->Database->escape($this->Request->request['username'])}' AND ";
            $where .= " password = '" . md5($this->Database->escape($this->Request->request['password'])) . "'";
            // if($adminLogin) {
                // $where .= " AND user_type = " . UserTypeConsts::ADMIN;
            // }else {
                // $where .= " AND user_type != " . UserTypeConsts::ADMIN;
            // }
            $result = $this->Database->mysqlSelect(array('id', 'status', 'user_type'),'users', $where);
            $row = $this->Database->mysqlfetchobject($result);
			//var_dump($row); exit;

            if(!$row) {
                $this->errors['username'] = 'Username/Password is not valid.';
            }elseif($row->status == INACTIVE) {
                $Form = new form();
                $this->Session->setFlash('<div class="flash-error">Your account has been suspended temporarily. <u>' . $Form->link('Contact us', array('class'=>'users', 'method'=>'contactUs'), array('title'=>'Contact us')) . '</u> if you believe this has happened incorrectly.</div>');
                return false;
            }
        }

        if(trim($this->Request->request['password'] == '')) {
            $this->errors['password'] = 'Password can not be empty.';
        }

        if(empty($this->errors))
            return true;

        return false;
    }

    /**
     * Admin logout
     *
     * @access public
     */
    function admin_logout() {
    // Sign out user from DB
        $where = "id = " . $this->Session->read('User.id');
        $this->Database->mysqlUpdate('users', array('is_loggedin'), array(INACTIVE), $where);
        $this->Session->destroy();
        //Delete previous cookie
        setcookie ("rememberMeCookie", "", time() - 3600);
        //include_once(DOCUMENT_ROOT.ROOT_DIR.'blog/wp-includes/plugin.php');
        //include_once(DOCUMENT_ROOT.ROOT_DIR.'blog/wp-includes/pluggable.php');
        //wp_logout();
        redirect(WWW_ROOT."/admin/users/login");
    }

    /**
     * User logout
     *
     * @access public
     */
    function logout() {
    // Sign out user from DB
        $where = "id = " . $this->Session->read('User.id');
        $this->Database->mysqlUpdate('users', array('is_loggedin'), array(INACTIVE), $where);
        $this->Session->destroy();
        //Delete previous cookie
        setcookie ("rememberMeCookie", "", time() - 3600);
        redirect(WWW_ROOT);
    }

    /**
     * Manage Users
     * @param <type> $page
     * @param <type> $order_by
     * @param <type> $order
     */
    function admin_manage($x=1, $page=1, $order_by = 'id', $order='DESC') {
        if($this->Session->checkAdminSession($this->Session,MANAGE_USERS)) {
            $this->pageTitle = "Users Management";
            $idString = "";
            $table = 'users';
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
                    if($this->Request->request['actions'] == "Activate") {
                        $fields = array("status");
                        $values = array(ACTIVE);
                        $where  = "id IN (".$idString.")";
                        $this->Database->mysqlUpdate($table,$fields,$values,$where);
                    }
                    if($this->Request->request['actions'] == "De-Activate") {
                        $fields = array("status");
                        $values = array(INACTIVE);
                        $where  = "id IN (".$idString.")";
                        $this->Database->mysqlUpdate($table,$fields,$values,$where);
                    }
                }
            }
            $pageData = $this->Database->paginatequery(array('*'), $table, $where, $order_by." ".$order, "", "", $page, LIMIT);
            $list['records'] = $this->Database->mysqlfetchobjects($pageData['recordset']);
            $list['pages']= $pageData['pages'];
            $list['page']= $page;
            $list['order_by'] = $order_by;
            $list['order'] = $order;
            $this->set('list', $list);
        }else {
            referer(array('class'=>'users', 'method'=>'admin_manage', $userType, $page, $order_by, $order));
            redirect(array('class'=>'users', 'method'=>'admin_login'));
        }
    }

    /**
     * Manage User Roles
     * @param <type> $page
     * @param <type> $order_by
     * @param <type> $order
     */
    function admin_manageUserTypes($page=1, $order_by = 'id', $order='DESC') {
        if($this->Session->checkAdminSession($this->Session)) {
            $idString = "";
            $table = 'usertypes';
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
                    if($this->Request->request['actions'] == "Activate") {
                        $fields = array("status");
                        $values = array(ACTIVE);
                        $where  = "id IN (".$idString.")";
                        $this->Database->mysqlUpdate($table,$fields,$values,$where);
                    }
                    if($this->Request->request['actions'] == "De-Activate") {
                        $fields = array("status");
                        $values = array(INACTIVE);
                        $where  = "id IN (".$idString.")";
                        $this->Database->mysqlUpdate($table,$fields,$values,$where);
                    }
                }
            }
            $pageData = $this->Database->paginatequery(array('*'), $table, $where, $order_by." ".$order, "", "", $page, LIMIT);
            $list['records'] = $this->Database->mysqlfetchobjects($pageData['recordset']);
            $list['pages']= $pageData['pages'];
            $list['page']= $page;
            $list['order_by'] = $order_by;
            $list['order'] = $order;
            $this->set('list', $list);
        }else {
            referer(array('class'=>'users', 'method'=>'admin_manage', $userType, $page, $order_by, $order));
            redirect(array('class'=>'users', 'method'=>'admin_login'));
        }
    }

    /**
     * Add a Usertype
     */
    function admin_addUserType() {
        $this->pageTitle = 'Add Usertype';
        if($this->Session->checkAdminSession($this->Session)) {
            if($this->Request->request['submit']) {
                if($this->_validateUserType()) {
                    $date = date("Y-m-d h:i:s");
                    $this->Database->mysqlInsert("usertypes",array("usertype","created","modified"),array(trim($this->Request->request['usertype']),$date,$date));
                    $this->Session->setFlash('<div class="flash-success">Usertype has been created successfully.</div>');
                    redirect(array('class'=>'users', 'method'=>'admin_manageUserTypes'));
                }
            }
        }else {
            referer(array('class'=>'users', 'method'=>'admin_addUserType'));
            redirect(array('class'=>'users', 'method'=>'admin_login'));
        }
    }

    /**
     * Edit Usertype
     */
    function admin_editUserType($id = 0, $page=1, $order_by = 'id', $order='ASC') {
        $this->pageTitle = 'Edit Usertype';
        if($this->Session->checkAdminSession($this->Session)) {
            if($this->Request->request['submit']) {
                if($this->_validateUserType($id)) {
                    $date = date("Y-m-d h:i:s");
                    $this->Database->mysqlUpdate("usertypes",array("usertype","modified"),array(trim($this->Request->request['usertype']),$date));
                    $this->Session->setFlash('<div class="flash-success">Usertype has been updated successfully.</div>');
                    redirect(array('class'=>'users', 'method'=>'admin_manageUserTypes',$page, $order_by, $order));
                }
            }else {
                $row = $this->Database->mysqlSelect("*","usertypes","id = '".$id."'");
                $rowData = $this->Database->mysqlfetchobject($row);
                $this->Request->request['usertype'] = $rowData->usertype;
                $this->Request->request['usertype_id'] = $id;
            }
        }else {
            referer(array('class'=>'users', 'method'=>'admin_addUserType', $page, $order_by, $order));
            redirect(array('class'=>'users', 'method'=>'admin_login'));
        }
    }

    /**
     * Check if a Usertype is available before submitting the form
     */
    function checkUserTypeAvailability($usertype = 0,$id = 0) {
        $this->pagelayout = 'ajax';
        $usertype = trim($usertype);
        if($usertype) {
            $where = "usertype = '" . $this->Database->escape($usertype) . "'";
            if($id) {
                $where .= " AND id != '" . $this->Session->read('User.id') . "'";
            }
            $result = $this->Database->mysqlSelect(array('id'),'usertypes', $where);
            $row = $this->Database->mysqlfetchobject($result);
            $this->set('result', $row);
            $this->set('flag1', true);
        }
    }

    /**
     * Validate a Usertype before adding or editing
     */
    function _validateUserType($id = 0) {
        if($id) {
            $result = $this->Database->mysqlSelect(array('id'),'usertypes', "usertype = '".trim($this->Request->request)."' and id != '".$id."'");
            $row = $this->Database->mysqlfetchobject($result);
            if($this->Database->mysqlnumrows($row) > 0) {
                return false;
            }
        }else {
            $result = $this->Database->mysqlSelect(array('id'),'usertypes', "usertype = '".trim($this->Request->request)."'");
            $row = $this->Database->mysqlfetchobject($result);
            if($this->Database->mysqlnumrows($row) > 0) {
                return false;
            }
        }
        return true;
    }

    /**
     * Activate / Deactivate usertype
      
     */
    function admin_toggleUserTypeStatus($id = 0) {
        if($this->Session->checkAdminSession($this->Session)) {
            if($id) {
                $Include = new include_resource();
                $where = "id = '{$this->Database->escape($id)}'";
                $result = $this->Database->mysqlSelect(array('status'),'usertypes',$where);
                $row = $this->Database->mysqlfetchobject($result);
                if($row) {
                    if($row->status) {
                        $newStatus = INACTIVE;
                        echo json_encode(array($Include->image('admin/inactive.png'), '<div class="flash-success">Usertype has been deactivated successfully.</div>'));
                    }else {
                        $newStatus = ACTIVE;
                        echo json_encode(array($Include->image('admin/active.png'), '<div class="flash-success">Usertype has been activated successfully.</div>'));
                    }
                    $this->Database->mysqlUpdate('usertypes', array('status'), array($newStatus), $where);
                }
            }
        }else {
            referer(array('class'=>'users','method'=>'admin_manageUserTypes'));
            redirect(array('class'=>'users','method'=>'admin_login'));
        }
        exit;
    }

    /**
     * Manage usertype roles
     *
     */
    function admin_setUsertypeAccessLevels($usertypeId = 0, $page=1, $order_by = 'id', $order='desc') {
        if($usertypeId) {
            if($this->Request->request['submit']) {
                if(isset($this->Request->request['access_level'])) {
                    foreach($this->Request->request['access_level'] as $key=>$value) {
                        $dataArray[$key][] = $usertypeId;
                        $dataArray[$key][] = $key;
                    }
                    $this->Database->mysqlDelete("usertype_access_levels","usertype_id = '".$usertypeId."'");
                    $this->Database->mysqlInsertAll("usertype_access_levels",array('usertype_id','access_level_id'),$dataArray);
                }
                $this->Session->setFlash('<div class="flash-error">Usertype Access-levels successfully updated.</div>');
                redirect(array('class'=>'users', 'method'=>'admin_manageUserTypes', $page, $order_by, $order));
            }
            $row = $this->Database->mysqlSelect("*","usertypes","id = '".$usertypeId."'");
            $rowData = $this->Database->mysqlfetchobject($row);
            $this->set('usertype',$rowData);
            $row = $this->Database->mysqlSelect("*","access_levels");
            $rowData = $this->Database->mysqlfetchobjects($row);
            $this->set('rowData',$rowData);
            $row = $this->Database->mysqlSelect("*","usertype_access_levels","usertype_id = '".$usertypeId."'");
            $rowData = $this->Database->mysqlfetchobjects($row);
            if($rowData) {
                foreach($rowData as $key=>$value) {
                    $this->Request->request["access_level"][$value->access_level_id] = $value->access_level_id;
                }
            }
        }
    }


    /**
     * Delete usertype
     *
     */
    function admin_deleteUsertype($usertypeId = 0, $page=1, $order_by = 'id', $order='desc') {
        $where = "id = '{$this->Database->escape($usertypeId)}'";
        $this->Database->mysqlDelete('usertypes', $where);
        $this->Session->setFlash('<div class="flash-success">Usertype has been deleted successfully.</div>');
        redirect(array('class'=>'users', 'method'=>'admin_manageUserTypes', $userType, $page, $order_by, $order));
    }
	
	function admin_delete($usertypeId = 0, $id= 0, $page=1, $order_by = 'id', $order='desc') {
        $where = "id = '{$this->Database->escape($id)}'";
        $this->Database->mysqlDelete('users', $where);
        $this->Session->setFlash('<div class="flash-success">User has been deleted successfully.</div>');
        redirect(array('class'=>'users', 'method'=>'admin_manage'));
    }

    /**
     * Landlord account settings
     */
    function dashboard_account(){
        $this->pageTitle = 'Account settings';
        $userId = $this->Session->read('User.id');        
        // Save loggedin user's account information
        if($this->Request->request['update']){
            if($this->Request->request['profile_settings']){
                if($this->_validateAccountProfileSettings() && trim($this->Request->request['new_email']) != ''){
                    $where = "id = '$userId'";
                    $this->Database->mysqlUpdate('users', array('email', 'modified'), array($this->Request->request['new_email'], date('Y-m-d H:i:s')), $where);
                }
            }else{
                if($this->Request->request['password_settings']){
                    if($this->_validateAccountPasswordSettings() && trim($this->Request->request['new_password']) != ''){
                        $where = "id = '$userId'";
                        $this->Database->mysqlUpdate('users', array('password', 'modified'), array(md5($this->Request->request['new_password']), date('Y-m-d H:i:s')), $where);
                    }
                }else{
                    if($this->Request->request['contact_settings']){
                        if($this->_validateAccountContactSettings()){
                            $where = "user_id = '$userId'";
                            $phone = $this->Request->request['phone1'] . '-' . $this->Request->request['phone2'] . '-' . $this->Request->request['phone3'];
                            $this->Database->mysqlUpdate('users_profiles', array('contact_email', 'website', 'phone'), array($this->Request->request['contact_email'], $this->Request->request['website'], $phone), $where);
                        }
                    }else{
                        if($this->Request->request['preferences_settings']){                          
                            $where = "user_id = '$userId'";
                            $this->Database->mysqlUpdate('users_profiles', array('preference_message', 'preference_expire', 'preference_updates'), array($this->Request->request['preference_message'], $this->Request->request['preference_expire'], $this->Request->request['preference_updates']), $where);
                        }
                    }
                }
            }

            if($this->errors)
                $this->Session->setFlash("<div class='flash-error'>Oops! Wrong or Insufficient information entered.</div>");
            else
                $this->Session->setFlash("<div class='flash-success'>Your account has been updated successfully.</div>");
        }
        // Fetch loggedin user's account information
        $where = "users.id = '$userId'";
        $result = $this->Database->assoc(array('users', 'left users_profiles'), array(array(), array('users.user_id')), $where);
        $rows = $this->Database->mysqlfetchassoc($result);
        if($rows){
            unset($rows[0]['users.password']);
            $this->Request->request = $rows[0];
            $phone = explode('-', $this->Request->request['usersProfiles.phone']);
            if($phone){
                $this->Request->request['phone1'] = isset($phone[0]) ? $phone[0] : '';
                $this->Request->request['phone2'] = isset($phone[1]) ? $phone[1] : '';
                $this->Request->request['phone3'] = isset($phone[2]) ? $phone[2] : '';
            }
        }
    }

    /**
     * Member/Renter account settings
     */
    function member_account(){
        $this->dashboard_account();
        $this->view = 'dashboard_account';
    }

    /**
     * Facebook login api
     *
     * @param <type> $accessToken
     */
    function loginWithFb($accessToken){
        $redirect = WWW_ROOT;
        if(!$this->Session->checkSession($this->Session)){
            if($accessToken){
                $graph_url = "https://graph.facebook.com/me?access_token=" . $accessToken;
                $fbUrl = @file_get_contents($graph_url);
                $user = json_decode($fbUrl);
                if($user && !isset($user->error)){
                    $created = date('Y-m-d H:i:s');
                    // Verify if fb logged in user already registred with the site or not?
                    $where = "email = '{$this->Database->escape($user->email)}'";
                    $result = $this->Database->mysqlSelect(array('id'), 'users', $where);
                    $row = $this->Database->mysqlfetchobject($result);
                    if(!$row){
                        $username = $user->email;
                        $password = createRandomPassword(12);
                        $this->Database->mysqlInsert('users',array('username', 'password', 'email', 'user_type', 'created', 'modified', 'activated_date', 'current_login_time', 'last_login_time', 'status'),array($username, md5($password),$user->email, UserTypeConsts::MEMBER, $created, $created, $created, $created, $created, '1'));
                        $userId = $this->Database->lastInsertId();
                        $this->Database->mysqlInsert('users_profiles',array('user_id', 'name', 'created','modified'),array($userId, $user->name,$created,$created));
                        // Send login credential to the user by email
                        $to = array($user->email);
                        $from = SITE_EMAIL;
                        $fromName = SITE_NAME;
                        $subject = 'Your '.SITE_NAME.' account credentials';
                        email(
                            $from,
                            $fromName,
                            $to,
                            $subject,
                            array(
                                'class'=>'users',
                                'method'=>'successfulSignedUpEmail',
                                array(
                                    'username'=>$username,
                                    'password'=>$password
                                     )
                            )
                        );
                        $this->Session->setFlash("<div class='flash-success'>Signed up successfully. Please check your email.</div>");
                        $redirect = array('class'=>'users', 'method'=>'member_account');
                    }else{
                        $userId = $row->id;
                        $redirect = $this->Request->request['redirect'];
                    }
                    // Create user session with the site
                    $record = array();
                    $record['id'] = $userId;
                    $record['user_type'] = UserTypeConsts::MEMBER;
                    $record['name'] = $user->name;
                    $record['email'] = $user->email;
                    $record['last_login_time'] = $created;
                    $record['current_login_time'] = $created;
                    $this->Session->write('User',$record);
                }else{
                    $this->Session->setFlash("<div class='flash-error'>Oops! Login failed. {$user->error->message}</div>");
                }
            }
        }
        redirect($redirect);
        die;
    }

    /**
     * Sign up with facebook
     */
    function signupWithFb(){
        $this->element = 'users/register_with_fb';
        if(isset($this->Request->request['signed_request'])){
            $newUserInfo = $this->_parseSignedRequest($this->Request->request['signed_request'], FACEBOOK_SECRET);
            if($newUserInfo['registration']['email']){
                if(!validEmailAddress($newUserInfo['registration']['email'])) {
                    $this->errors['email'] = "Email is not valid.";
                }else{
                    $where = "email = '{$this->Database->escape($newUserInfo['registration']['email'])}'";
                    $result = $this->Database->mysqlSelect(array('id'), 'users', $where);
                    $row = $this->Database->mysqlfetchobject($result);
                    if($row){
                        $this->errors['email'] = "Email already exists.";
                    }
                }
                if(!$this->errors){
                    $created = date('Y-m-d H:i:s');
                    $username = $newUserInfo['registration']['email'];
                    $password = createRandomPassword(12);
                    $this->Database->mysqlInsert('users',array('username', 'password', 'email', 'user_type', 'created', 'modified', 'activated_date', 'current_login_time', 'last_login_time', 'status'),array($username, md5($password),$newUserInfo['registration']['email'], UserTypeConsts::MEMBER, $created, $created, $created, $created, $created, UserStatusConsts::ACTIVE));
                    $userId = $this->Database->lastInsertId();
                    $this->Database->mysqlInsert('users_profiles',array('user_id', 'name', 'created','modified'),array($userId, $newUserInfo['registration']['name'],$created,$created));
                    // Create new registered user session with the site
                    $record = array();
                    $record['id'] = $userId;
                    $record['user_type'] = UserTypeConsts::MEMBER;
                    $record['name'] = $newUserInfo['registration']['name'];
                    $record['email'] = $newUserInfo['registration']['email'];
                    $record['last_login_time'] = $created;
                    $record['current_login_time'] = $created;
                    $this->Session->write('User',$record);
                    // Send login credential to the user by email
                    $to = array($record['email']);
                    $from = SITE_EMAIL;
                    $fromName = SITE_NAME;
                    $subject = 'Your '.SITE_NAME.' account credentials';
                    email(
                        $from,
                        $fromName,
                        $to,
                        $subject,
                        array(
                            'class'=>'users',
                            'method'=>'successfulSignedUpEmail',
                            array(
                                'username'=>$username,
                                'password'=>$password
                                 )
                        )
                    );
                    $this->Session->setFlash("<div class='flash-success'>Signed up successfully. Please check your email.</div>");
                    redirect(array('class'=>'users', 'method'=>'member_account'));
                }
            }
            if($this->errors){
                $this->Session->setFlash("<div class='flash-error'>Oops! Registration failed. {$this->errors['email']}</div>");
                redirect(WWW_ROOT);
            }
        }        
    }

    /**
     * Signup as a Renter/Lessee
     */
    function renter_signup(){
        $this->pageTitle = 'Renter Signup';
        if($this->Request->request['signup']) {
            if($this->_validateUserRegistration()) {
                $created = date('Y-m-d H:i:s');
                $password = createRandomPassword(12);
                $activationKey = md5($this->Request->request['email']);
                $this->Database->mysqlInsert('users',array('username', 'password', 'email', 'user_type', 'created', 'modified', 'activation_key', 'newsletter_subscrib'),array($this->Request->request['username'], md5($password),$this->Request->request['email'], UserTypeConsts::MEMBER, $created, $created, $activationKey, $this->Request->request['newsletter_subscrib']));
                $userId = $this->Database->lastInsertId();
                $name = $this->Request->request['firstname'] .' '. $this->Request->request['lastname'];
                $this->Database->mysqlInsert('users_profiles',array('user_id', 'name', 'zipcode', 'created','modified'),array($userId, $name, $this->Request->request['zipcode'], $created, $created));
                $this->Session->setFlash('<div class="flash-success">Registration Successful. <br/>Please check your email to activate your account.</div>');

                // Send an email to user.
                $activationLink = '<a href="'.WWW_ROOT.'/users/activation/'.$activationKey.'" title="Account activation link">'.WWW_ROOT.'/users/activation/'.$activationKey.'</a>';
                $emails = importClass('emailTemplates');
                $emails->sendEmail(array('from'=>SITE_EMAIL, 'fromName'=>SITE_NAME, 'to'=>array($this->Request->request['email']), 'fields'=>array('name'=>$name, 'link'=>$activationLink, 'username'=>$this->Request->request['username'], 'password'=>$password)), EmailTemplatesConsts::ACTIVATION_EMAIL);
                redirect(WWW_ROOT);
            }
        }
    }

    /**
     * Signup as a Landlord
     */
    function landlord_signup(){
        $this->pageTitle = 'Landlord Signup';                
        $this->view = 'renter_signup';
		if($this->Request->request['signup']) {
            if($this->_validateUserRegistration()) {
                $created = date('Y-m-d H:i:s');
                $password = createRandomPassword(12);
                $activationKey = md5($this->Request->request['email']);
                $this->Database->mysqlInsert('users',array('username', 'password', 'email', 'user_type', 'created', 'modified', 'activation_key', 'newsletter_subscrib'),array($this->Request->request['username'], md5($password),$this->Request->request['email'], UserTypeConsts::LANDLORD, $created, $created, $activationKey, $this->Request->request['newsletter_subscrib']));
                $userId = $this->Database->lastInsertId();
                $name = $this->Request->request['firstname'] .' '. $this->Request->request['lastname'];
                $this->Database->mysqlInsert('users_profiles',array('user_id', 'name', 'zipcode', 'created','modified'),array($userId, $name, $this->Request->request['zipcode'], $created, $created));
                $this->Session->setFlash('<div class="flash-success">Registration Successful. <br/>Please check your email to activate your account.</div>');

                // Send an email to user.
                $activationLink = '<a href="'.WWW_ROOT.'/users/activation/'.$activationKey.'" title="Account activation link">'.WWW_ROOT.'/users/activation/'.$activationKey.'</a>';
                $emails = importClass('emailTemplates');
                $emails->sendEmail(array('from'=>SITE_EMAIL, 'fromName'=>SITE_NAME, 'to'=>array($this->Request->request['email']), 'fields'=>array('name'=>$name, 'link'=>$activationLink, 'username'=>$this->Request->request['username'], 'password'=>$password)), EmailTemplatesConsts::ACTIVATION_EMAIL);
                redirect(WWW_ROOT);
            }
        }
    }

    /**
     * New signed up user login credentails email
     */
    function successfulSignedUpEmail(){
        $this->element = 'emails/successful_signed_email';
        $this->pagelayout = 'ajax';
    }

    /**
     * Activate User account
     * 
     * @param <type> $key
     */
    function activation($key = 0) {
        if($key) {
            $where = "activation_key = '{$this->Database->escape($key)}'";
            $result = $this->Database->mysqlSelect(array('*'), 'users', $where);
            $rows = $this->Database->mysqlfetchassoc($result);
            if($rows) {
                if($rows[0]['status'] == UserStatusConsts::ACTIVE) {
                    $this->Session->setFlash('Your account is already activated.');
                }else {
                    $date = date('Y-m-d H:i:s');
                    $this->Database->mysqlUpdate('users', array('status', 'activated_date', 'last_login_time', 'current_login_time', 'activation_key'), array(UserStatusConsts::ACTIVE, $date, $date, $date, ''), $where);
                    $where = 'user_id = ' . $rows[0]['id'];
                    $result = $this->Database->mysqlSelect(array('name'), 'users_profiles', $where);
                    $rows2 = $this->Database->mysqlfetchassoc($result);
                    // Create new registered user session with the site
                    $record = array();
                    $record['id'] = $rows[0]['id'];
                    $record['user_type'] = $rows[0]['user_type'];
                    $record['name'] = $rows2[0]['name'];
                    $record['email'] = $rows[0]['email'];
                    $record['last_login_time'] = $date;
                    $record['current_login_time'] = $date;
                    $this->Session->write('User',$record);
                    
                    $this->Session->setFlash('Account Activation Successful.');
                    /*if($record['user_type'] == UserTypeConsts::MEMBER)
                        redirect(array('class'=>'users', 'method'=>'member_account'));
                    else
                        redirect(array('class'=>'users', 'method'=>'dashboard_account'));*/
					//redirect(WWW_ROOT.'tourId=RenterTour');	
                    if($record['user_type'] == UserTypeConsts::MEMBER)
                        redirect(WWW_ROOT.'?tourId=RenterTour');
                    else
                        redirect(array('class'=>'properties', 'method'=>'dashboard_index', '?tourId=LandlordTour'));
                }
            }else {
                $this->Session->setFlash('<div class="flash-error">Oops! Invalid activation key.</div>');
            }
        }
        redirect(WWW_ROOT);
    }
	
	/**
	* get logged user info
	*/
	function getloggeduserinfo(){
		$userId = $this->Session->read('User.id');
		$where = "user_id = " . $this->Session->read('User.id');
		//var_dump($where); exit;
	    $result = $this->Database->mysqlSelect(array('name','contact_email', 'website', 'phone'), 'users_profiles', $where, '', '', '', '');
		$row = $this->Database->mysqlfetchobject($result);
		$phone= $row->phone;
		$name= $row->name;
		$email= $row->contact_email;
		$name = explode(' ', $name);
		if($name){
		$first_name= isset($name[0]) ? $name[0] : '';
		$last_name = isset($name[1]) ? $name[1] : '';
		}
		if($email){
		$this->set('email',$email);
		}
        if($phone){
			$this->set('phone', $phone);
            }
		$this->set('userId',$userId);	
		$this->set('first_name',$first_name);
		$this->set('last_name',$last_name);
	$this->element = 'users/contactus';
	}

    /**
     * Contact us
     */
    function contactUs(){
        $this->element = 'users/contactus';
        if(trim($this->Request->request['contactEmail']) != ''){
            if($this->_validateContactUsForm()){
			//make a registration link for the unsignup user who send contactus email

			if($this->Session->read('User.id')){
			$userId = $this->Session->read('User.id');
			}else{
			    $created = date('Y-m-d H:i:s');
                $password = createRandomPassword(12);
                $activationKey = md5($this->Request->request['email']);
                $this->Database->mysqlInsert('users',array('username', 'password', 'email', 'user_type', 'created', 'modified', 'activation_key', 'newsletter_subscrib'),array($this->Request->request['first_name']. '.'.$this->Request->request['last_name'], md5($password),$this->Request->request['email'], UserTypeConsts::MEMBER, $created, $created, $activationKey, 0));
                $userId = $this->Database->lastInsertId();
                $name = $this->Request->request['first_name'] .' '. $this->Request->request['last_name'];
                $this->Database->mysqlInsert('users_profiles',array('user_id', 'name', 'zipcode', 'created','modified'),array($userId, $name, 0, $created, $created));
				// Send an email to user.
                $activationLink = '<a href="'.WWW_ROOT.'/users/activation/'.$activationKey.'" title="Account activation link">'.WWW_ROOT.'/users/activation/'.$activationKey.'</a>';
                $emails = importClass('emailTemplates');
                $emails->sendEmail(array('from'=>SITE_EMAIL, 'fromName'=>SITE_NAME, 'to'=>array($this->Request->request['email']), 'fields'=>array('name'=>$name, 'link'=>$activationLink, 'username'=>$this->Request->request['first_name'].'.'. $this->Request->request['last_name'], 'password'=>$password)), EmailTemplatesConsts::ACTIVATION_EMAIL);
			}
			// end of new code	registration link 
			
			// start insert into messages table new message
                $date = date('Y-m-d H:i:s');
                $rootId = 0;
			// selecting $prId by slug
			
				$where = "slug = '{$this->Request->request['slug']}'";
				$result = $this->Database->mysqlSelect(array('id'), 'properties', $where);
				$prId = $this->Database->mysqlfetchobjects($result);
				$prId = $prId[0]->id;				
                $this->Database->mysqlInsert('user_property_messages', array('user_id', 'pr_id', 'message', 'created', 'modified', 'root', 'is_private'), array($userId, $prId, $this->Request->request['message'], $date, $date, $rootId, 1));
                $msgId = $this->Database->lastInsertId();
				$order = array();
                $this->getorder($msgId);
                $disporder = array_reverse($this->order);
                // Reactivate thread if there is any reply in it.
                if($disporder){
                    $where = "id = '{$disporder[0]}'";
                    $this->Database->mysqlUpdate('user_property_messages', array('delete_sender_flag', 'delete_receiver_flag'), array(0, 0), $where);
                }
                $disporder = implode('.', $disporder);
                $disporder = $disporder.($disporder ? '.' : '').zerofill($msgId,5);
                $this->Database->mysqlUpdate('user_property_messages',array('disporder'),array($disporder),'id = '.$msgId);
				
				//end insert messages
				
			$firstname=array($this->Request->request['first_name'], $this->Request->request['last_name']);
                $form = new form();
                $link = $form->link(WWW_ROOT . '/properties/details/' . $this->Request->request['slug'], array('class'=>'properties', 'method'=>'details', $this->Request->request['slug']), array('title'=>'Click here to view link details'));
                $from = SITE_EMAIL;
                $fromname = SITE_NAME;
                $to = array($this->Request->request['contactEmail']);
                $subject = 'Contact Email';
                email(
                    $from,
                    $fromName,
                    $to,
                    $subject,
                    array(
                        'class'=>'users',
                        'method'=>'contactUsEmail',
                        array(
                            'userInfo'=>$this->Request->request,
                            'prLink'=>$link,
							'email'=>$this->Request->request['email'],
							'first_name'=>implode(" ", $firstname),
							'phone'=>$this->Request->request['phone'],
							'message'=>$this->Request->request['message']
                             )
                    )
                );
                $this->Session->setFlash("<div class='flash-success'>Email has been sent successfully.</div>");
                redirect(array('class'=>'properties', 'method'=>'details', $this->Request->request['slug']));
            }
        }else{
            $this->Session->setFlash("<div class='flash-error'>Oops! Invalid Action</div>");
        }
    }
	
	/* display order field*/
    private function getorder($postid) {
        $where = "id = ".$postid;
        $result = $this->Database->mysqlSelect(array('root'),'user_property_messages',$where);
        $post = $this->Database->mysqlfetchobject($result);
        if($post->root!=0) {
            $this->order[] = zerofill($post->root,5);
            $this->getorder($post->root);
        }
    }

    /**
     * Contact us Email
     */
    function contactUsEmail(){
        $this->element = 'emails/contact_us_email';
        $this->pagelayout = 'ajax';
    }

    private function _validateContactUsForm(){
		If(!$this->Session->read('User.id')){
        if(trim($this->Request->request['first_name'] == '')) {
            $this->errors['first_name'] = 'First name can not be empty.';
        }

        if(trim($this->Request->request['email'] == '')) {
            $this->errors['email'] = 'Email can not be empty.';
        }else{
            if(!validEmailAddress(trim($this->Request->request['email']))){
                $this->errors['email'] = 'Email is not valid.';
            }
        }
		}

        if(trim($this->Request->request['message'] == '')) {
            $this->errors['message'] = 'Message can not be empty.';
        }

        if(empty($this->errors))
            return true;

        return false;
    }

    /**
     * Parse signup with facebook plugin response
     *
     * @param <type> $signed_request
     * @param <type> $secret
     * @return <type>
     */
    private function _parseSignedRequest($signed_request, $secret) {
        list($encoded_sig, $payload) = explode('.', $signed_request, 2);

        // decode the data
        $sig = $this->_base64_url_decode($encoded_sig);
        $data = json_decode($this->_base64_url_decode($payload), true);

        if (strtoupper($data['algorithm']) !== 'HMAC-SHA256') {
            error_log('Unknown algorithm. Expected HMAC-SHA256');
            return null;
        }

        // check sig
        $expected_sig = hash_hmac('sha256', $payload, $secret, $raw = true);
        if ($sig !== $expected_sig) {
            error_log('Bad Signed JSON signature!');
            return null;
        }

        return $data;
    }

    private function _base64_url_decode($input) {
        return base64_decode(strtr($input, '-_', '+/'));
    }

    /**
     * Validate account profile settings
     */
    function _validateAccountProfileSettings() {
        if(trim($this->Request->request['new_email']) != '') {
            if(!validEmailAddress($this->Request->request['new_email'])) {
                $this->errors['new_email'] = "Email is not valid.";
            }else{
                $where = "email = '{$this->Database->escape($this->Request->request['new_email'])}'";
                $where .= " AND id != '{$this->Session->read('User.id')}'";
                $result = $this->Database->mysqlSelect(array('id'), 'users', $where);
                $row = $this->Database->mysqlfetchobject($result);
                if($row){
                    $this->errors['new_email'] = "Email already exists.";
                }else{
                    if($this->Request->request['confirm_new_email'] != $this->Request->request['new_email']) {
                        $this->errors['confirm_new_email'] = "Email does not match.";
                    }
                }
            }            
        }        

        if($this->errors) {
            return false;
        }
        return true;
    }

    /**
     * Validate account password settings
     */
    function _validateAccountPasswordSettings() {
        $newPwd = trim($this->Request->request['new_password']);
		//var_dump($newPwd); exit;
        if($newPwd != '') {
            if(strlen($newPwd) < 6) {
                $this->errors['new_password'] = "New password must be alleast 6 characters long.";
            }
            if(trim($this->Request->request['current_password']) == '') {
                $this->errors['current_password'] = "Current password can not be empty.";
            }
            if($this->Request->request['confirm_new_password'] == $this->Request->request['new_password']) {
                $this->errors['confirm_new_password'] = "Password does not match.";
            }
        }        

        if($this->errors) {
            return false;
        }
        return true;
    }
    
    /**
     * Validate account contact settings
     */
    function _validateAccountContactSettings() {
        if(trim($this->Request->request['contact_email']) != '') {
            if(!validEmailAddress($this->Request->request['contact_email'])) {
                $this->errors['contact_email'] = "Contact email is not valid.";
            }else{
                if($this->Request->request['confirm_contact_email'] != $this->Request->request['contact_email']) {
                    $this->errors['confirm_contact_email'] = "Contact email does not match.";
                }
            }
        }

        if($this->errors) {
            return false;
        }
        return true;
    }

    /**
     * Validate User Registration
     **/

    function _validateUserRegistration() {
        if(!isset($this->Request->request['is_agree'])) {
            $this->errors['is_agree_temp'] = "Please accept site agreement before proceeding.";
        }
        if(trim($this->Request->request['firstname']) == "") {
            $this->errors['firstname'] = "Please enter your firstname.";
        }
        $username = trim($this->Request->request['username']);
        if($username == "") {
            $this->errors['username'] = "Please enter your username.";
        }else {
            if(!(strlen($username) >= 6 && preg_match('/^[a-z\_][0-9a-z]*/i', $username))){
                $this->errors['username'] = "Username must be at least 6 alphanumeric characters.";
            }else{
                $where = "username = '{$this->Database->escape($this->Request->request['username'])}'";
                $result = $this->Database->mysqlSelect(array('id'), 'users', $where);
                $row = $this->Database->mysqlfetchobject($result);
                if($row){
                    $this->errors['username'] = "Username already exists.";
                }
            }
        }
        if(trim($this->Request->request['email']) == "") {
            $this->errors['email'] = "Please enter your email address.";
        }else {
            if(!validEmailAddress($this->Request->request['email'])) {
                $this->errors['email'] = "Please enter a valid email address.";
            }else{
                $where = "email = '{$this->Database->escape($this->Request->request['email'])}'";
                $result = $this->Database->mysqlSelect(array('id'), 'users', $where);
                $row = $this->Database->mysqlfetchobject($result);
                if($row){
                    $this->errors['email'] = "Email already exists.";
                }
            }
        }        

        if(count($this->errors) > 0) {
            return false;
        }
        return true;
    }

    private function _validateSuperAdminProfile() {
        $userId = $this->Session->read('User.id');

        if(trim($this->Request->request['username']) != '') {
            $where = "username = '{$this->Database->escape($this->Request->request['username'])}'";
            $where .= " AND id != '$userId'";
            $result = $this->Database->mysqlSelect(array('id'),'hh_users', $where);
            $row = $this->Database->mysqlfetchobject($result);
            if($row) {
                $this->errors['username'] = 'Username already exists.';
            }
        }

        if(trim($this->Request->request['password']) != '') {            
            $len = strlen($this->Request->request['password']);
            if($len < 6) {
                $this->errors['password'] = 'Password must be atleast 6 characters long.';
            }elseif($this->Request->request['password'] !== $this->Request->request['cpassword'])
                $this->errors['cpassword'] = 'Confirm password does not match.';
        }

        if(empty($this->errors))
            return true;

        return false;
    }
}
?>