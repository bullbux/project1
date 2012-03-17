<?php 
class emailTemplates extends classIlluminz {
    var $errors = array();
    var $success = array();
    var $pagelayout = 'admin';
    var $pageTitle;
    var $element;

    /**
     * Manage email templates
     */
    function admin_index(){
        $this->pageTitle = 'Email Templates Management';
        if($this->Session->checkAdminSession($this->Session)) {
            if($this->Request->request['send']){
                $where = "id = '{$this->Database->escape($this->Request->request['id'])}'";
                if(!isset($this->Request->request['update'])){                    
                    $result = $this->Database->mysqlSelect(array('*'), 'emails_templates', $where);
                    $rows = $this->Database->mysqlfetchassoc($result);
                    if($rows){
                        $this->Request->request = $rows[0];
                    }
                    $this->set('id', $rows[0]['id']);
                }else{
                    if($this->_validate()){
                        $result = $this->Database->mysqlUpdate('emails_templates', array('subject', 'content', 'signature'), array($this->Request->request['subject'], $this->Request->request['content'], $this->Request->request['signature']), $where);
                        $this->Session->setFlash('<div class="flash-success">Email Template has been updated successfully.</div>');
                    }
                    $this->set('id', $this->Request->request['id']);
                }
            }
        }else {
            referer(array('class'=>'emailTemplates', 'method'=>'admin_index'));
            redirect(array('class'=>'users','method'=>'admin_login'));
        }
    }

    function listTemplates(){
        $tpls = array();
        $result = $this->Database->mysqlSelect(array('id', 'email'), 'emails_templates', '', '', '', '', '');
        $rows = $this->Database->mysqlfetchobjects($result);
        if($rows){
            foreach($rows as $row){
                $tpls[$row->id] = $row->email;
            }
        }
        return $tpls;
    }

    function listKeywords($tplId = 0){
        $where = "template_id = '{$this->Database->escape($tplId)}'";
        $result = $this->Database->mysqlSelect(array('*'), 'emails_keywords', $where, '', '', '', '');
        $rows = $this->Database->mysqlfetchobjects($result);
        return $rows;
    }

    function preview($id = 0){
        if($this->Session->checkAdminSession($this->Session)) {
            $this->pagelayout = 'admin';
            $this->pageTitle = 'Email Preview';
            $this->element = 'emails/email_template_preview';
            $this->set('emailPreview', '<center>No Preview Available</center>');
            if($id){
                $where = "id = '{$this->Database->escape($id)}'";
                $result = $this->Database->mysqlSelect(array('id', 'content', 'subject', 'signature'), 'emails_templates', $where);
                $row = $this->Database->mysqlfetchobject($result);
                if($row){
                    $contents = $this->replaceKeywords($row->id, $row->content);
                    $this->set('emailContent', $contents);
                    $this->set('emailSubject', $row->subject);
                    $this->set('emailSignature', $row->signature);
                }
            }
        }else {
            referer(array('class'=>'emailTemplates', 'method'=>'admin_index'));
            redirect(array('class'=>'users','method'=>'admin_login'));
        }
    }

    function replaceKeywords($id = 0, $contents = ''){
        if($id){
            // Fetch email keywords
            $where = "template_id = '{$this->Database->escape($id)}'";
            $result = $this->Database->mysqlSelect(array('*'), 'emails_keywords', $where, '', '', '', '');
            $keywords = $this->Database->mysqlfetchobjects($result);

            if($contents && $keywords){
                foreach($keywords as $key){
                    $contents = preg_replace("/{{($key->keyword)}}/", "$key->example", $contents);
                }               
            }            
        }

        return $contents;
    }

    function replaceKeywordFields($id = 0, $fields = '', $contents = ''){
        if($id){
            // Fetch email keywords
            $where = "template_id = '{$this->Database->escape($id)}'";
            $result = $this->Database->mysqlSelect(array('*'), 'emails_keywords', $where, '', '', '', '');
            $keywords = $this->Database->mysqlfetchobjects($result);
            if($contents && $keywords){
                $tempFields = array();
                if(is_object($fields)){                    
                    foreach($fields as $k=>$field){
                        $tempFields[$k] = $field;
                    }
                }elseif(is_array($fields)){
                    $tempFields = $fields;
                }
                foreach($keywords as $key){
                    $value = $tempFields[$key->field_name];
                    if($value){
                        if(preg_match("/([0-9]{4}-[0-9]{2}-[0-9]{2})/i", $value)){
                            $value = dateformat($value, 'd/m/Y');
                        }
                        $contents = preg_replace("/{{($key->keyword)}}/", "$value", $contents);                        
                    }else{
                        $contents = preg_replace("/{{($key->keyword)}}/", "", $contents);
                    }
                }
            }
        }
        return $contents;
    }

    function sendEmail($info = array(), $templateId = ''){
         if($templateId){            
            $where = "id = '{$this->Database->escape($templateId)}'";
            $result = $this->Database->mysqlSelect(array('subject'), 'emails_templates', $where);
            $row = $this->Database->mysqlfetchobject($result);
            if($row){
                $this->Session->write('emailContents', $info['fields']);
                $emailUrl = WWW_ROOT.'/emailTemplates/sendEmailTemplate/'.$templateId;
                phpmail($info['from'], $info['fromName'], $info['to'], $row->subject, $emailUrl);
            }
         }
    }

    function sendEmailTemplate($templateId = 0) {
        $info = $this->Session->read('emailContents');
        if($info) {            
            $where = "id = '{$this->Database->escape($templateId)}'";
            $result = $this->Database->mysqlSelect(array('id', 'content', 'subject', 'signature'), 'emails_templates', $where);
            $row = $this->Database->mysqlfetchobject($result);
            if($row){
                $contents = $this->replaceKeywordFields($templateId, $info, $row->content);
                $this->set('emailContent', $contents);
                $this->set('emailSubject', $row->subject);
                $this->set('emailSignature', $row->signature);
                $this->Session->delete('emailContents');
             }
            
            $this->pagelayout = 'email_template';
        }
    }

    private function _validate(){
        if(!$this->Request->request['id'])
            $this->errors['id'] = 'Email can not be empty.';

        if(trim($this->Request->request['subject']) == '')
            $this->errors['subject'] = 'Subject can not be empty.';

        if(trim($this->Request->request['content']) == '')
            $this->errors['content'] = 'Content can not be empty.';

        if(trim($this->Request->request['signature']) == '')
            $this->errors['signature'] = 'Signature can not be empty.';

        if(empty($this->errors))
            return true;

        return false;
    }
}
?>