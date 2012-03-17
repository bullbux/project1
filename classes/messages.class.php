<?php
/**
* Performs messages related all the tasks like add, delete, update etc.
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
class messages extends classIlluminz {

    var $pagelayout = 'front';
    var $order = array();
    var $loggedInUserId = 0;
    var $loggedInUserType = 0;

    /**
     * Execute before executing any method of this class
     */
    function beforeFilter(){
        // Landlord
        if($this->params['prefix'] == 'dashboard'){
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

        if($this->Session->checkSession($this->Session)){
            $this->loggedInUserId = $this->Session->read('User.id');
            $this->loggedInUserType = $this->Session->read('User.user_type');
        }
    }

    /**
     * Fetch all the messages list
     *
     * @param <type> $page
     * @param <type> $order_by
     * @param <type> $order
     */
    function dashboard_inbox($page=1, $order_by = 'userPropertyMessages.modified', $order='DESC'){
        $this->pageTitle = 'My messages';
        $userId = $this->Session->read('User.id');
        $where = "properties.user_id = '$userId'";
        $where .= " AND userPropertyMessages.root = '0'";
		$where .= " AND userPropertyMessages.is_private = '0'";
        $where .= " AND userPropertyMessages.delete_receiver_flag != '1'";
        $pageData = $this->Database->paginate(array('properties', 'user_property_messages', 'left pr_gallery'), array(array(), array('properties.pr_id'), array('properties.pr_id')), $where, $order_by." ".$order, 'userPropertyMessages.id', '', $page, LIMIT);
        $list['records'] = $this->Database->mysqlfetchassoc($pageData['recordset']);
        if($list['records']){
            foreach($list['records'] as $key=>$l){ 
                $r = zerofill($l['userPropertyMessages.id'],5);
                $result = $this->Database->mysqlQuery("SELECT upm.*, u.email FROM user_property_messages AS upm
                                            INNER JOIN users AS u
                                            ON u.id = upm.user_id
                                            WHERE upm.user_id != '$userId' AND disporder LIKE '$r%'
                                            ORDER BY id DESC
                                            LIMIT 1"
                );
                $rows = $this->Database->mysqlfetchassoc($result, 'id', $userId);
                $list['records'][$key]['msg'] = $rows[0];
            }
        }
        $list['pages']= $pageData['pages'];
        $list['page']= $page;
        $list['order'] = $order;
        $this->set('list', $list);

        // Count unread messages
        $totalRows = $this->_countUnreadMessages();
        $this->set('totalUnreadMessages', $totalRows);
        if($totalRows){
            $this->pageTitle .= ' &laquo; Inbox ('.$totalRows.')';
        }
    }
	
	 /**
     * Fetch all private messages
     *
     * @param <type> $page
     * @param <type> $order_by
     * @param <type> $order
     */
    function dashboard_private($page=1, $order_by = 'userPropertyMessages.modified', $order='DESC'){
        $this->pageTitle = 'My private messages';
        $userId = $this->Session->read('User.id');
        $where = "properties.user_id = '$userId'";
        $where .= " AND userPropertyMessages.root = '0'";
		$where .= " AND userPropertyMessages.is_private = '1'";
        $where .= " AND userPropertyMessages.delete_receiver_flag != '1'";
        $pageData = $this->Database->paginate(array('properties', 'user_property_messages', 'left pr_gallery'), array(array(), array('properties.pr_id'), array('properties.pr_id')), $where, $order_by." ".$order, 'userPropertyMessages.id', '', $page, LIMIT);
        $list['records'] = $this->Database->mysqlfetchassoc($pageData['recordset']);
		//var_dump($list['records']); exit;
        if($list['records']){
            foreach($list['records'] as $key=>$l){ 
                $r = zerofill($l['userPropertyMessages.id'],5);
                $result = $this->Database->mysqlQuery("SELECT upm.*, u.email FROM user_property_messages AS upm
                                            INNER JOIN users AS u
                                            ON u.id = upm.user_id
                                            WHERE upm.user_id != '$userId' AND disporder LIKE '$r%'
                                            ORDER BY id DESC
                                            LIMIT 1"
                );
                $rows = $this->Database->mysqlfetchassoc($result, 'id', $userId);
                $list['records'][$key]['msg'] = $rows[0];
            }
        }
        $list['pages']= $pageData['pages'];
        $list['page']= $page;
        $list['order'] = $order;
		//var_dump($list); exit;
        $this->set('list', $list);

        // Count unread messages
        $totalRows = $this->_countUnreadMessages();
        $this->set('totalUnreadMessages', $totalRows);
        if($totalRows){
            $this->pageTitle .= ' &laquo; Private Messages ('.$totalRows.')';
        }
    }
	
    function member_private($page=1, $order_by = 'userPropertyMessages.modified', $order='DESC'){
        $this->pageTitle = 'My messages';
        $userId = $this->loggedInUserId;
		//var_dump($userId); exit;
        $where = "userPropertyMessages.user_id = '$userId'";
        //$where .= " AND userPropertyMessages.root = '0'";
		$where .= " AND userPropertyMessages.is_private = '1'";
        $where .= " AND userPropertyMessages.delete_sender_flag != '1'";
        $pageData = $this->Database->paginate(array('properties', 'user_property_messages', 'left pr_gallery'), array(array(), array('properties.pr_id'), array('properties.pr_id')), $where, $order_by." ".$order, 'userPropertyMessages.id', '', $page, LIMIT);
        $list['records'] = $this->Database->mysqlfetchassoc($pageData['recordset']);
        if($list['records']){
            foreach($list['records'] as $key=>$l){
                $r = zerofill($l['userPropertyMessages.id'], 5);
                $result = $this->Database->mysqlQuery("SELECT upm.*, u.email FROM user_property_messages AS upm
                                            INNER JOIN users AS u
                                            ON u.id = upm.user_id
                                            WHERE upm.user_id != '$userId' AND disporder LIKE '$r%'
                                            ORDER BY id DESC
                                            LIMIT 1"
                );
                $rows = $this->Database->mysqlfetchassoc($result, 'id', $userId);
                if($rows)
                    $list['records'][$key]['msg'] = $rows[0];
                else
                    unset($list['records'][$key]);
            }
        }
        $list['pages']= $pageData['pages'];
        $list['page']= $page;
        $list['order'] = $order;
        $this->set('list', $list);

        // Count unread messages
        $totalRows = $this->_countUnreadReplies();
        $this->set('totalUnreadMessages', $totalRows);
        if($totalRows){
            $this->pageTitle .= ' &laquo; Inbox ('.$totalRows.')';
        }
        $this->view = 'member_private';
    }
	

    /**
     * Change the message status / delete message
     */
    function dashboard_toggleStatus(){
       if($this->Request->request['msg'] && $this->Request->request['action']){
           $userId = $this->Session->read('User.id');
           foreach($this->Request->request['msg'] as $key=>$id){
               $this->Request->request['msg'][$key] = $this->Database->alphaID($id, true, $userId);
           }
           $ids = implode("','", $this->Request->request['msg']);
           $where = "id IN ('$ids')";
           // Delete messages
           if($this->Request->request['action'] == 'delete'){
                if($this->loggedInUserType == UserTypeConsts::LANDLORD)
                    $flag = 'delete_receiver_flag';
                else
                    $flag = 'delete_sender_flag';
                
                $result = $this->Database->mysqlSelect(array('disporder'), 'user_property_messages', $where);
                $rows = $this->Database->mysqlfetchobjects($result);
                if($rows){
                    foreach($rows as $row){
                        $where = "disporder = SUBSTRING('$row->disporder',1, 5)";
                        $this->Database->mysqlUpdate('user_property_messages', array($flag, 'flag'), array(1, MessageStatusConsts::READ), $where);
                    }
                }                
                                
               // $this->Database->mysqlDelete('user_property_messages', $where);
           }elseif($this->Request->request['action'] == 'mark_read'){               
               // Change read status of messages
               $this->Database->mysqlUpdate('user_property_messages', array('flag'), array(MessageStatusConsts::READ), $where);
           }
           // Count remaining unread messages
           if($this->loggedInUserType == UserTypeConsts::LANDLORD)
                $totalRows = $this->_countUnreadMessages();
           else
                $totalRows = $this->_countUnreadReplies();
           echo $totalRows;
       }
       exit;
    }

    /**
     * Alert for all the new/unread messages in the inbox
     */
    function dashboard_alerts(){
        $this->element = 'messages/dashboard_alerts';
        // Count unread messages
        $totalRows = $this->_countUnreadMessages('0');
        $this->set('totalUnreadMessages', ($totalRows?$totalRows:0));
    }

    /**
     * Alert for all the new/unread messages in the inbox
     */
    function member_alerts(){
        $this->element = 'messages/dashboard_alerts';
        // Count unread messages
        $totalRows = $this->_countUnreadReplies();
        $this->set('totalUnreadMessages', $totalRows);
    }

    /**
     * Fetch all the messages list
     *
     * @param <type> $page
     * @param <type> $order_by
     * @param <type> $order
     */
    function member_inbox($page=1, $order_by = 'userPropertyMessages.modified', $order='DESC'){
        $this->pageTitle = 'My messages';
        $userId = $this->loggedInUserId;
        $where = "userPropertyMessages.user_id = '$userId'";
        $where .= " AND userPropertyMessages.root = '0'";
		$where .= " AND userPropertyMessages.is_private = '0'";
        $where .= " AND userPropertyMessages.delete_sender_flag != '1'";
        $pageData = $this->Database->paginate(array('properties', 'user_property_messages', 'left pr_gallery'), array(array(), array('properties.pr_id'), array('properties.pr_id')), $where, $order_by." ".$order, 'userPropertyMessages.id', '', $page, LIMIT);
        $list['records'] = $this->Database->mysqlfetchassoc($pageData['recordset']);
        if($list['records']){
            foreach($list['records'] as $key=>$l){
                $r = zerofill($l['userPropertyMessages.id'], 5);
                $result = $this->Database->mysqlQuery("SELECT upm.*, u.email FROM user_property_messages AS upm
                                            INNER JOIN users AS u
                                            ON u.id = upm.user_id
                                            WHERE upm.user_id != '$userId' AND disporder LIKE '$r%'
                                            ORDER BY id DESC
                                            LIMIT 1"
                );
                $rows = $this->Database->mysqlfetchassoc($result, 'id', $userId);
                if($rows)
                    $list['records'][$key]['msg'] = $rows[0];
                else
                    unset($list['records'][$key]);
            }
        }
        $list['pages']= $pageData['pages'];
        $list['page']= $page;
        $list['order'] = $order;
        $this->set('list', $list);

        // Count unread messages
        $totalRows = $this->_countUnreadReplies();
        $this->set('totalUnreadMessages', $totalRows);
        if($totalRows){
            $this->pageTitle .= ' &laquo; Inbox ('.$totalRows.')';
        }
        $this->view = 'dashboard_inbox';
    }

    /**
     * Change the message status / delete message
     */
    function member_toggleStatus(){
       $this->dashboard_toggleStatus();
    }

    /**
     * Send a message
     *
     * @param <type> $prId
     */
    function send($prId = 0, $is_private = 0){
        if($this->Session->checkSession($this->Session)){
            if(trim($this->Request->request['message']) == '' || $this->Request->request['message'] == 'Leave message here...' || $this->Request->request['message'] == 'Leave reply here...'){
                echo '<div class="error-msg">Message can not be empty.</div>';
                exit;
            }else{
                $userId = $this->Session->read('User.id');
                $date = date('Y-m-d H:i:s');
                $rootId = 0;
                if($this->Request->request['root'])
                    $rootId = $this->Database->alphaID($this->Database->escape($this->Request->request['root']), true, $this->loggedInUserId);
                $this->Database->mysqlInsert('user_property_messages', array('user_id', 'pr_id', 'message', 'created', 'modified', 'root', 'is_private'), array($userId, $prId, $this->Request->request['message'], $date, $date, $rootId, $is_private));
                $msgId = $this->Database->lastInsertId();
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
                
                $where = "userPropertyMessages.id = '$msgId'";
                $result = $this->Database->assoc(array('user_property_messages', 'users'), array(array('users.user_id')), $where);
                $rows = $this->Database->mysqlfetchassoc($result);                
                $this->element = 'messages/latest_messages_list';
                // Send reply mail to the user
                if($rootId && $this->Session->checkUserSession($this->Session, UserTypeConsts::LANDLORD)){
                    $result = $this->Database->mysqlQuery("SELECT email FROM users AS u
                                                            INNER JOIN user_property_messages AS upm
                                                            ON u.id = upm.user_id
                                                            WHERE upm.id = '$rootId'");
                    $user = $this->Database->mysqlfetchobject($result);  					
                    if($user){
                        $to = array($user->email);
                        $from = $this->Session->read('User.email');
                        $fromName = $this->Session->read('User.name');
                        $subject = 'Reply to your message';
                        $this->_reply($from, $fromName, $to, $subject, $prId, $this->Request->request['root']);
                    }
                    
                }else{ 
                    $result = $this->Database->mysqlQuery("SELECT email, preference_message FROM users AS u
                                                            INNER JOIN users_profiles AS up
                                                            ON u.id = up.user_id
                                                            INNER JOIN properties AS p
                                                            ON u.id = p.user_id
                                                            WHERE p.id = '{$this->Database->escape($prId)}'");
                    $user = $this->Database->mysqlfetchobject($result);
                    // Send new message alert to the landlord
                    if($user && $user->preference_message){
                        $to = array($user->email);
                        $from = SITE_EMAIL;
                        $fromName = SITE_NAME;                        
                        if($rootId){
                            $subject = 'Reply to your message';
                            $this->_reply($from, $fromName, $to, $subject, $prId, $this->Request->request['root']);
                        }else{
                            $subject = 'New message alert!';
                            $this->_message($from, $fromName, $to, $subject, $prId, $this->Database->alphaID($msgId, false, $this->loggedInUserId));
                        }
                    }
                }

                $this->set('messages', $rows);
            }
        }else{
            redirect( array('class' => 'users', 'method' => 'login') );
        }  
    }

    /**
     * Reply message alert
     *
     * @param <type> $from
     * @param <type> $fromName
     * @param <type> $to
     * @param <type> $subject
     * @param <type> $prId
     * @param <type> $msgId
     */
    private function _reply($from, $fromName, $to, $subject, $prId, $msgId){
        email(
            $from,
            $fromName,
            $to,
            $subject,
            array(
                'class'=>'messages',
                'method'=>'replyEmail',
                array(
                    'messageId'=>$msgId,
                    'prId'=>$prId
                )
            )
        );
    }

    /**
     * New message alert
     *
     * @param <type> $from
     * @param <type> $fromName
     * @param <type> $to
     * @param <type> $subject
     * @param <type> $prId
     * @param <type> $msgId
     */
    private function _message($from, $fromName, $to, $subject, $prId, $msgId){
        email(
            $from,
            $fromName,
            $to,
            $subject,
            array(
                'class'=>'messages',
                'method'=>'newMessageAlertEmail',
                array(
                    'messageId'=>$msgId,
                    'prId'=>$prId
                )
            )
        );
    }

    /**
     * Message reply email to user
     */
    function replyEmail(){        
        $this->pagelayout = 'ajax';
    }

    /**
     * New message alert email to landlord
     */
    function newMessageAlertEmail(){
        $this->pagelayout = 'ajax';
    }

    /**
     * Fetch all the latest messages list
     *
     * @param <type> $prId
     * @param <type> $limit
     */
    function latestMessagesList($prId = 0, $limit = ''){
        $where = "userPropertyMessages.pr_id = '{$this->Database->escape($prId)}'";
        $where .= " AND userPropertyMessages.is_private = '0'";
        $result = $this->Database->assoc(array('user_property_messages', 'users'), array(array('users.user_id')), $where, 'userPropertyMessages.id ASC', '', '', $limit);
        $rows = $this->Database->mysqlfetchassoc($result);
        $this->set('messages', $rows);
    }

    /**
     * Fetch single/thread message
     *
     * @param <type> $messageId
     */
    function getMessageThread($messageId = 0, $thread = true){
        $messageId = $this->Database->alphaID($this->Database->escape($messageId), true, $this->loggedInUserId);
        $where = "userPropertyMessages.id = '$messageId'";
        if($thread){
            $where = "id = ".$messageId;
            $result = $this->Database->mysqlSelect(array('disporder'),'user_property_messages',$where);
            $row = $this->Database->mysqlfetchobject($result);
            $orderIds = substr($row->disporder, 0, 5);
         //   $where = "userPropertyMessages.id IN ($orderIds)";
            $where = "userPropertyMessages.disporder LIKE '$orderIds%'";
        }            
        $result = $this->Database->assoc(array('user_property_messages', 'users'), array(array('users.user_id')), $where, 'userPropertyMessages.id ASC', 'userPropertyMessages.id', '', '');
        $rows = $this->Database->mysqlfetchassoc($result);
        $this->set('messages', $rows);
    }

    /**
     * Count total unread messages for the logged in user
     *
     * @return <type>
     */
    function _countUnreadMessages($is_private=1){
        $totalRows = 0;
        $userId = $this->Session->read('User.id');
	        $result = $this->Database->mysqlQuery("SELECT upm.id FROM user_property_messages AS upm
                                        INNER JOIN properties AS p
                                        ON p.id = upm.pr_id
                                        WHERE p.user_id = '$userId'                                        
                                        AND root = '0' and is_private = '".$is_private."'"
            );
        $rows = $this->Database->mysqlfetchassoc($result);
        if($rows){
	     $count=1;
            foreach($rows as $key=>$l){
                $r = zerofill($l['id'], 5);
		$sql =  "SELECT upm.*, u.email FROM user_property_messages AS upm
                                            INNER JOIN users AS u
                                            ON u.id = upm.user_id
                                            WHERE upm.user_id != '$userId' AND disporder LIKE '$r%' and upm.flag = '0'
                                            ORDER BY id DESC
                                            LIMIT 1";
		/*$sql = "SELECT id FROM user_property_messages
                                            WHERE user_id != '$userId'
                                            AND disporder LIKE '$r%'
                                            AND root != '0'
                                            AND flag = ". MessageStatusConsts::UNREAD."
                                            ORDER BY id DESC
                                            LIMIT 1";*/


                $result = $this->Database->mysqlQuery($sql);

		
                $row = $this->Database->mysqlfetchobject($result);
                if($row)
                    $totalRows++;
		$count++;
            }
        }

        return $totalRows;
        
    }
    
    /**
     * Count total unread messages for the logged in member
     *
     * @return <type>
     */
    function _countUnreadReplies(){              
        $totalRows = 0;
        $userId = $this->Session->read('User.id');
        $where = "user_id = '$userId'";
        $where .= " AND root = '0'";
        $result = $this->Database->mysqlSelect(array('id'), 'user_property_messages', $where, '', '', '', '');
        $rows = $this->Database->mysqlfetchassoc($result);
        if($rows){
            foreach($rows as $key=>$l){
                $r = zerofill($l['id'], 5);
                $result = $this->Database->mysqlQuery("SELECT id FROM user_property_messages
                                            WHERE user_id != '$userId'
                                            AND disporder LIKE '$r%'
                                            AND root != '0'
                                            AND flag = ". MessageStatusConsts::UNREAD."
                                            ORDER BY id DESC
                                            LIMIT 1"
                );
                $row = $this->Database->mysqlfetchobject($result);
                if($row)
                    $totalRows++;
            }
        }

        return $totalRows;
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
}
?>