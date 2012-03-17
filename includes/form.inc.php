<?php
class form {
    var $messages = array();
    var $params = array();
    var $autoFields = array('value'=>'', 'class'=>'');
    var $fieldTerminals = array('div', 'span', 'p');
    var $Request;
    var $formId = 'form';
    var $queryStringParams = array();
    
    function __construct(&$Content = NULL) {
        if($Content) {
            $this->Request = $Content;
            $request = $Content->clas;
            $this->Request = $this->Request->$request->Request;
            if(isset($this->Request->request) && $this->Request->request)
                $this->Request->request = $this->_refineRequestFields($this->Request->request);
        }
        $this->messages = $Content->errors;
        $this->params = $Content->params;
    }

    public function create($fieldName, $options = array()) {
        global $prefix;
        $this->queryStringParams = array();
        $form = "";
        $stringAttributes = "";
        $class = $method = $arguments = '';
        $options = array_merge(array('id'=>'form_'.uniqid(), 'action'=>array()), $options);
        if(is_array($options)) {            
            foreach($options as $k => $value) {
                if(strtolower($k) == 'id')
                    $this->formId = $value;
                elseif($k == 'action' && is_array($value)) {
                    // If action is empty means Current page url
                    if(!$value){
                        $value = $this->params;
                    }
                    foreach($value as $key => $val) {
                        if($key === 'class')
                            $class = '/'.$val;
                        else{
                            if($key === 'method') {
                                if(isset($prefix) && $prefix) {
                                    if(preg_match("/^(.*)_/", $val, $matches)) {
                                        if(in_array($matches[1], $prefix)) {
                                            $pfx = "/{$matches[1]}";
                                            $val = preg_replace("/^{$matches[1]}_/i", '', $val);
                                        }
                                    }
                                }
                               // if($val != 'index')
                                    $method = '/'.$val;
                                //else
                                //    $method = '';
                            }else{
                                if($key === 'pass' && !empty($val)) {
                                    $pass = '/'.implode('/', $val);
                                }else{
                                    if($key === 'qs' && !empty($val)) {
                                        foreach($val as $f=>$v)
                                            $this->queryStringParams["$f"] = $v;
                                        //$arguments .= '?' . http_build_query($val);
                                    }elseif(!empty($val) && $key !== 'prefix'){
                                        $arguments .= '/'. $val;
                                    }
                                }
                            }
                        }
                    }

                    $value = WWW_ROOT.$pfx.$class.$method.$pass.$arguments;
                }                                                

                if(!$value) {
                    $value = '';
                }
                $stringAttributes .=" ".$k."='".$value."' ";
            }
        }
        $form .= "<form name = '".$fieldName."' ".$stringAttributes.">\n";
        return $form;
    }

    public function end($options = array()) {
        $html = '';
        foreach($this->queryStringParams as $f=>$v){
            if(is_array($v)){
                foreach($v as $ff=>$vv){
                    $html .= $this->input("$f" . "[$ff]" . "{$this->_qsa($vv)}", array('type'=>'hidden', 'value'=>$vv)) . "\n";
                }
            }else{
                $html .= $this->input("$f", array('type'=>'hidden', 'value'=>$v)) . "\n";
            }
        }
        $html .= "</form>";
        $html .= $this->_submitFormByAJAX($options);
        return $html;
    }

    private function _qsa($q = ''){
        $html = '';
        if($q){
            foreach($q as $f=>$v){
                if(is_array($q["$f"])){
                    $html .= "[$f]" . $this->_qsa($q["$f"]);
                }else{                    
                    $html .= "[$f]";
                }
            }
        }

        return $html;
    }

    private function _submitFormByAJAX($options = array()){
        $html = '';
        $ajaxMethod = 'POST';
        $asyn = 'true';
        $parameters = '';
        $dataType = '';
        $cache = 'false';
        $id = '';
        $confirm = '';
        $open = '""';
        $elementId = '""';
        $position = '';
        $reLoad = true;
        $onComplete = 'null';
        if(is_array($options) && !empty($options)) {
            foreach($options as $key => $value) {
                if($key === 'method') {
                    $ajaxMethod = $value;
                }
                if($key === 'asyn') {
                    $asyn = $value;
                }
                if($key === 'parameters') {
                    $parameters = $value;
                }
                if($key === 'dataType') {
                    $dataType = $value;
                }
                if($key === 'update') {
                    if(is_array($value)) {
                        $elementId = json_encode($value);
                    }else {
                        $elementId = '"'.$value.'"';
                    }
                }
                if($key === 'position') {
                    $position = $value;
                }
                if($key === 'cache') {
                    $cache = $value;
                }
                if($key === 'id') {
                    $id = $value;
                }
                if($key === 'confirm') {
                    $confirm = $value;
                }
                if($key === 'reLoad') {
                    $reLoad = $value;
                }
                if($key === 'onComplete') {
                    $onComplete = $value;
                }
                if($key === 'open' && isset($value['type'])) {
                    $open = json_encode($value);
                }
            }

            $html .= "\n"."<script language='javascript' type='text/javascript'>"."\n"." $('#$this->formId').submit(function(){ return Event.submitForm($('#$this->formId')[0], \"$ajaxMethod\", \"$asyn\", \"$parameters\", $elementId, \"$dataType\", \"$cache\", \"$confirm\", $open, \"$position\", \"$reLoad\", \"$onComplete\"); return false;});"."\n"."</script>";
        }
        return $html;
    }

    private function _submitInputFormByAJAX($options = array()){
        $html = '';
        $ajaxMethod = 'POST';
        $asyn = 'true';
        $parameters = '';
        $dataType = '';
        $cache = 'false';
        $id = '';
        $confirm = '';
        $open = '""';
        $elementId = '""';
        $position = '';
        $reLoad = 'false';
        $onComplete = 'null';
        if(is_array($options) && !empty($options)) {
            foreach($options as $key => $value) {
                if($key === 'method') {
                    $ajaxMethod = $value;
                }
                if($key === 'asyn') {
                    $asyn = $value;
                }
                if($key === 'parameters') {
                    $parameters = $value;
                }
                if($key === 'dataType') {
                    $dataType = $value;
                }
                if($key === 'update') {
                    if(is_array($value)) {
                        $elementId = json_encode($value);
                    }else {
                        $elementId = '"'.$value.'"';
                    }
                }
                if($key === 'position') {
                    $position = $value;
                }
                if($key === 'cache') {
                    $cache = $value;
                }
                if($key === 'id') {
                    $id = $value;
                }
                if($key === 'confirm') {
                    $confirm = $value;
                }
                if($key === 'reLoad') {
                    $reLoad = $value;
                }
                if($key === 'onComplete') {
                    $onComplete = $value;
                }
                if($key === 'open' && isset($value['type'])) {
                    $open = json_encode($value);
                }
            }

            if($elementId !== '""')
                $html .= "onClick='javascript: Event.submitForm(this.form, \"$ajaxMethod\", \"$asyn\", \"$parameters\", $elementId, \"$dataType\", \"$cache\", \"$confirm\", $open, \"$position\", \"$reLoad\", \"$onComplete\"); return false;'";
        }
        return $html;
    }

    public function input($name=null,$options = array()) {
        $html = '';
        $stringAttributes = "";
        $checkType = "";
        $error = $this->flash($name);
        $options = array_merge($this->autoFields, $options);
        $script = '';
        $loadAjax = '';
        $custom = '';
        $html = '';
        $terminal1 = '';
        $terminal2 = '';
        $fieldName = '';
        foreach($options as $key => $value) {
            if(strtolower($key) == 'type') {
                $checkType = strtolower($value);
            }
        }
        if(is_array($options) && !empty($options)) {
            foreach($options as $key => $value) {
                if($error) {
                    if(strtolower($key) == 'class')
                        $value .= ' field-error';
                }
                $temp = $this->_field($name);
                if($checkType == 'checkbox' && strtolower($key) == 'value' && empty($value)) {
                    $value = 1;
                }
                if($temp) {
                    if(($checkType != 'checkbox' && $checkType != 'radio') && strtolower($key) == 'value') {
                        $value = stripslashes($temp);
                    }

                    if($checkType == 'checkbox' && strtolower($key) == 'value') {
                        $stringAttributes .=" checked='checked' ";
                    }

                    if($checkType == 'radio' && strtolower($key) == 'value' && $temp == $value) {
                        $stringAttributes .=" checked='checked' ";
                    }
                }

                if(strtolower($key) == 'type' && (strtolower($value) == 'image' || strtolower($value) == 'submit')){
                    $loadAjax = '<span class="load-ajax"></span>';
                    $html = $this->_submitInputFormByAJAX($options);
                }

                if($checkType == 'checkbox' && strtolower($key) == 'checkall' && trim($value) != '') {
                    $key = 'onclick';
                    $value = 'javascript: Event.checkAll(this, "' . $value . '");';
                }

                if(strtolower($key) == 'custom' && is_array($value)){
                    $customKeys = array_keys($value);
                    $customValues = array_values($value);
                    foreach($customKeys as $key=>$customName)
                        $custom .= "<input type='hidden' disabled='disabled' name='$customName' value='{$customValues[$key]}' class='$customName'/>";
                }else
                    $stringAttributes .=" ".$key."='".str_replace('\'', '"', $value)."' ";

				if(in_array(strtolower($key), $this->fieldTerminals) && $option){
					$terminal1 = "<$key class='input-field'>";
					$terminal2 = "</$key>";
				}
            }
        }
        if(trim($name) != ''){
            $fieldName = "name='$name'";
        }
        return $terminal1. "<input $fieldName ".$stringAttributes." $html />" . $loadAjax . $custom . $error . $terminal2;
    }

    function select($fieldName = null, $options = array(), $selected = array(), $attributes = array(), $showEmpty = '', $showEmptyValue = '0') {
        $stringAttributes = "";
        $selectBox = "";
        $optionsString = "";
        $custom = '';
        $error = $this->flash($fieldName);
        $attributes = array_merge($this->autoFields, $attributes);

        if(is_array($attributes) && !empty($attributes)) {
            foreach($attributes as $key => $value) {

                if($error) {
                    if(strtolower($key) == 'class')
                        $value .= ' field-error';
                }

                if(strtolower($key) == 'custom' && is_array($value)){
                    $customKeys = array_keys($value);
                    $customValues = array_values($value);
                    foreach($customKeys as $k=>$customName)
                        $custom .= "<input type='hidden' disabled='disabled' name='$customName' value='{$customValues[$k]}' class='$customName'/>";
                }else
                    $stringAttributes .=" ".$key."='".str_replace('\'', '"', $value)."' ";
            }

        }
        $selectBox .= "<select name='".$fieldName."' ".$stringAttributes."  >";

        if(is_array($options)) {
            if($showEmpty != "") {
                $optionsString .= "<option value='$showEmptyValue' class='empty-field'>".$showEmpty."</option>";
            }
            $temp = $this->_field($fieldName);
            if($temp && in_array('multiple', $attributes)){
                $selected = $temp;
            }
            foreach($options as $key=>$value) {
                if((is_array($selected) && in_array($key,$selected)) || $temp == $key) {
                    $selectOption = " selected = 'selected' ";
                }else {
                    $selectOption = "";
                }

                $optionsString .= "<option value='".str_replace('\'', '"', $key)."' ".$selectOption." >".stripslashes($value)."</option>";
            }
        }
        $selectBox .= $optionsString;
        $selectBox .= "</select>" . $custom . $error;
        return $selectBox;
    }

    function textarea($fieldName, $options = array()) {
        $textArea = "";
        $stringAttributes = "";
        $textValue = null;
        $error = $this->flash($fieldName);
        $options = array_merge($this->autoFields, $options);
        if(is_array($options) && !empty($options)) {
            if(isset($options['value'])) {
                $textValue = $options['value'];
                unset($options['value']);
            }
            foreach($options as $key => $value) {
                if($error) {
                    if(strtolower($key) == 'class')
                        $value .= ' field-error';
                }

                $stringAttributes .=" ".$key."='".str_replace('\'', '"', $value)."' ";
            }
            $temp = $this->_field($fieldName);

            if($temp) {
                $textValue = stripslashes($temp);
            }
        }
        $textArea .= "<textarea name = '".$fieldName."' ".$stringAttributes.">";
        $textArea .= stripslashes($textValue);
        $textArea .= "</textarea>". $error;
        return $textArea;
    }

    function year($fieldName = null, $range = array(), $selected = array(), $attributes = array(), $showEmpty = '') {
        if(is_array($range) && (count($range) == 2)) {
            if($range[1] >= $range[0]){
                for($index = $range[0]; $index<=$range[1]; $index++) {
                    $options[$index] = $index;
                }
            }elseif($range[1] <= $range[0]){
                for($index = $range[0]; $index>=$range[1]; $index--) {
                    $options[$index] = $index;
                }
            }
            return $this->select($fieldName, $options, $selected, $attributes, $showEmpty);
        }
    }

    function number($fieldName = null, $range = array(), $selected = array(), $attributes = array(), $showEmpty = '') {
        if(is_array($range) && (count($range) == 2)) {
            if($range[1] >= $range[0]){
                for($index = $range[0]; $index<=$range[1]; $index++) {
                    $options[$index] = $index;
                }
            }elseif($range[1] <= $range[0]){
                for($index = $range[0]; $index>=$range[1]; $index--) {
                    $options[$index] = $index;
                }
            }
            return $this->select($fieldName, $options, $selected, $attributes, $showEmpty);
        }
    }

    function month($fieldName = null, $range = array(), $selected = array(), $attributes = array(), $showEmpty = '') {
        if(is_array($range) && (count($range) == 2) && ($range[1] >= $range[0])) {
            for($index = $range[0]; $index<=$range[1]; $index++) {
                $options[$index] =  sprintf("%02d", $index);
            }
            return $this->select($fieldName, $options, $selected, $attributes, $showEmpty);
        }
    }

    function float_number($fieldName = null, $range = array(), $selected = array(), $attributes = array(), $showEmpty = '') {
        if(is_array($range) && (count($range) == 2)) {
            if($range[1] >= $range[0]){
                for($index = $range[0],$j=$range[0]; $index<=$range[1]; $index+=0.5,$j++) {
                    if($index != 0.5)
					$options[$j] = $index;
                }
            }elseif($range[1] <= $range[0]){
                for($index = $range[0]; $index>=$range[1]; $index-= 0.5) {
                    $options[$index] = $index;
                }
            }
            return $this->select($fieldName, $options, $selected, $attributes, $showEmpty);
        }
    }	
	function bath_numtovalue($ind = '')	{
		$val = 0;
		if($ind) {
			for($index = 1,$j = 1; $index<=10; $index+=0.5,$j++) {
				if($j == $ind)
				$val = $index;
				continue;
			}		
		}
		return $val;
	}

    function day($fieldName = null, $range = array(), $selected = array(), $attributes = array(), $showEmpty = '') {
        if(is_array($range) && (count($range) == 2) && ($range[1] >= $range[0])) {
            for($index = $range[0]; $index<=$range[1]; $index++) {
                $options[$index] =  sprintf("%02d", $index);
            }
            return $this->select($fieldName, $options, $selected, $attributes, $showEmpty);
        }
    }

    function hour($fieldName = null, $range = array(), $min_gap = 60, $selected = array(), $attributes = array(), $showEmpty = '') {
        if(is_array($range) && (count($range) == 2) && ($range[1] >= $range[0])) {
            $range[1] = $range[1] * (60/$min_gap) + 1;
            for($index = $range[0]; $index<=$range[1]; $index++) {
                $tmp = $index * $min_gap;
                $options[$tmp] = preg_replace('/(.*)\.(.*)/e', 'sprintf("%02d", "$1").":".sprintf("%02d", "$2")', sprintf('%10.2f', ((int)($tmp/60) + ($tmp%60)/100)));
            }
            return $this->select($fieldName, $options, $selected, $attributes, $showEmpty);
        }
    }

    function ajaxFileUpload($name = null, $options = array(), $multiple = '', $position = 'INNER') {
        $stringAttributes = "";
        $addMore = '';
        $error = $this->flash($name);
        $options = array_merge($this->autoFields, $options);

        $updateValue = '';
        if(is_array($options) && !empty($options)) {

            foreach($options as $key => $value) {
                if($error) {
                    if(strtolower($key) == 'class')
                        $value .= ' field-error';
                }
                if(isset($this->Request->request[$name])) {
                    $updateValue = $this->Request->request[$name];
                }

                $stringAttributes .=" ".$key."='".str_replace('\'', '"', $value)."' ";
            }

        }
        $id = uniqid();
        if($multiple && ctype_digit("$multiple")) {

            $addMore = "<a href='' onclick='javascript: return Event.add(this, $multiple);'>Add</a>";
        }

        return "<input type='file' name= '".$name."' ".$stringAttributes."' onchange='javascript: return Event.upload(this, \"$position\");' /><input type='hidden' value='1' name='uid'> " . "<span id='$id"."_process'></span>" . $addMore . '<br/><span id="'.$id.'_update">'.$updateValue.'</span>'. $error;
    }

    function setMessages($messages) {
        if(is_array($messages)) {
            session::write('formMessages', $messages);
        }
    }

    function link($title,$args,$options=array()) {
        global $prefix;
        $class = $method = $arguments= $url = $qs = '';
        $option = '';
        $pfx = '';
        if(is_array($args)) {
            foreach($args as $key => $value) {
                if($key === 'class') {
                    $class = '/'.$value;
                }
                else if($key === 'method'){
                        if(isset($prefix) && $prefix){
                            if(preg_match("/^(.*)_/", $value, $matches)){
                                if(in_array($matches[1], $prefix)){
                                    $pfx = "/{$matches[1]}";
                                    $value = preg_replace("/^{$matches[1]}_/i", '', $value);
                                }
                            }
                        }
                        //if($value != 'index')
                            $method = '/'.$value;
                        //else
                        //    $method = '';
                    }else{
                        if($key === 'qs' && $value)
                            $qs .= '?' . http_build_query($value);
                        elseif($value) {
                            $arguments .= '/'.$value;
                        }
                    }
            }
            $href = WWW_ROOT.$pfx.$class.$method.$arguments.$qs;
        }else {
            if(trim($args) != '' && trim($args) != '#' && !preg_match('/^http:|https:|javascript|mailto:/i', $args)){
                $args = 'http://' . $args;
            }else{                
                preg_match('/(http[s]{0,1}:\/\/|mailto:)(.*)/i', trim($args), $match);                
                if(empty($match[2]))
                    $args = '#';
            }
            $href = $args;
        }
        if(is_array($options) && $options) {

            foreach($options as $key => $value) {
                if($key === 'confirm') {
                    $key = 'onclick';
                    $value = 'javascript: return confirm("'.$value.'");';
                }
                $option .= $key . "='" . preg_replace('/\'/', '"', $value) . "' ";
            }
        }

        return "<a href = '$href' $option>".$title."</a>";
    }

    function flash($name) {
        $html = '';
        if(isset($this->messages[$name])) {
            $html = " <span class='error-msg'>";
            $html .= $this->messages[$name];
            $html .= "</span>";
        }
        return $html;
    }

    public function ajaxLink($title,$args,$options=array()) {
        global $prefix;
        $class = $method = $arguments= $url = '';
        $ajaxMethod = 'POST';
        $asyn = 'true';
        $parameters = '';
        $dataType = '';
        $cache = 'false';
        $attrtitle = $id = '';
        $className = '';
        $confirm = '';
        $open = '""';
        $elementId = '""';
        $position = '';
        $pfx = '';
        $reLoad = 'false';
        $onComplete = 'null';
        if(is_array($args)) {
            foreach($args as $key => $value) {
                if($key === 'class') {
                    $class = '/'.$value;
                }
                else if($key === 'method'){
                        if(isset($prefix) && $prefix){
                            if(preg_match("/^(.*)_/", $value, $matches)){
                                if(in_array($matches[1], $prefix)){
                                    $pfx = "/{$matches[1]}";
                                    $value = preg_replace("/^{$matches[1]}_/i", '', $value);
                                }
                            }
                        }
                        //if($value != 'index')
                            $method = '/'.$value;
                      //  else
                       //     $method = '';
                    }else {
                        $value = urlencode($value);
                        if($arguments)
                            $arguments .= "/".$value;
                        else
                            $arguments = $value;
                    }
            }

            foreach($options as $key => $value) {
		$qsa = '';
                if($key === 'method') {
                    $ajaxMethod = $value;
                }
                if($key === 'asyn') {
                    $asyn = $value;
                }
                if($key === 'parameters') {
                    $parameters = $value;
                }
                if($key === 'dataType') {
                    $dataType = $value;
                }
                if($key === 'update') {
                    if(is_array($value)) {
                        $elementId = json_encode($value);
                    }else {
                        $elementId = '"'.$value.'"';
                    }
                }
                if($key === 'position') {
                    $position = $value;
                }
                if($key === 'reLoad') {
                    $reLoad = $value;
                }
                if($key === 'cache') {
                    $cache = $value;
                }
                if($key === 'id') {
                    $id = $value;
                }
                if($key === 'title') {
                    $attrtitle = $value;
                }
                if($key === 'class') {
                    $className = $value;
                }
                if($key === 'confirm') {
                    $confirm = $value;
                }
                if($key === 'onComplete') {
                    $onComplete = $value;
                }
				if($key === 'qsa' && is_array($value)) {
                    $qsa = '?' . http_build_query($value);
                }
                if($key === 'open' && isset($value['type'])) {
                    $open = json_encode($value);
                }
            }
            return "<a class = '$className' id = '$id' title='$attrtitle' href = ".WWW_ROOT.$pfx.$class.$method."/".$arguments.$qsa." onclick='javascript: return Event.link(this, \"$ajaxMethod\", \"$asyn\", \"$parameters\", $elementId, \"$dataType\", \"$cache\", \"$confirm\", $open, \"$position\", \"$reLoad\", \"$onComplete\");'>".$title."</a>";
        }
    }
    // To select date using calendar
    function datePicker($name,$options=array()) {
        $stringAttributes = '';
        $id=mktime();
        $selected = '';
        $startDate = '';
        $endDate = '';
        $error = $this->flash($name);
        $options = array_merge(array('value'=>'', 'class'=>'data-pick dp-applied'), $options);
        //pr($options);
        $flag = 0;
        foreach($options as $key=>$value) {
            if($flag == 0) {
                if($key == 'id') {
                    $id = $value;
                    $flag = 1;
                }else {
                    $id = preg_replace('/\./', '_', $name);
                }
            }
            if($key == 'selected' || $key == 'end' || $key == 'start') {
                $dat = (explode("/",$value));
                if($dat[0] && checkdate($dat[1], $dat[0], $dat[2])) {
                    switch($key) {
                        case 'selected':
                            $selected = $value;
                            break;

                        case 'end':
                            $endDate = $value;
                            break;

                        case 'start':
                            $startDate = $value;
                            break;
                    }
                }
            }else {
                if($error) {
                    if(strtolower($key) == 'class')
                        $value .= ' field-error';
                }
                $stringAttributes .=" ".$key."='".$value."' ";
            }
        }
        $temp = $this->_field($name);
        if($temp) {
            $selected = stripslashes($temp);
        }

        $html =  "<input type='text' name='".$name."' id='".$id."' readonly = 'readonly' value='".$selected."' $stringAttributes />".$error;
        $html .= "<script language='javascript' type='text/javascript'> jQuery(document).ready(function(){jQuery('#$id').datePicker({startDate: '$startDate', endDate: '$endDate'});}); </script>";
        return $html;
    }

    function captcha($name = 'captcha', $label = 'Please enter the verification code in the image above'){
        $captchaObj = WWW_ROOT.'/library/securimage/securimage_show.php?sid=';
        $refreshGif = '<img src="'.WWW_ROOT.'/library/securimage/images/refresh.gif'.'" alt="reload image" title="Reload Image" border=0>';
        $captcha = '<img src="'.$captchaObj.md5(uniqid(time())).'" id="image" align="absmiddle" border=0>';
        $captcha .= $this->link($refreshGif, '#', array('onclick'=>"document.getElementById('image').src = '$captchaObj' + Math.random(); return false;"));
        $captcha .= '<br style="clear: both;" /><br/>';
        $captcha .= $this->input($name, array('type'=>'text', 'class'=>'smallfield mandatory', 'title'=>'Validation code'));
        if($label){
            $captcha .= '<br/>';
            $captcha .= '<span style="color: red;">* '.$label.'</span>';
        }
        return $captcha;
    }

    function recaptcha($theme = 'red'){
        include_once(LIB_ROOT.'/recaptcha/recaptchalib.php');
        $error = $this->flash('recaptcha_response_field');
        echo '<script type="text/javascript">
             var RecaptchaOptions = {
                theme : "'.$theme.'"
             };
             window.onload = function(){
                document.getElementById("recaptcha_response_field").className = "mandatory";
                document.getElementById("recaptcha_response_field").setAttribute("title","Captcha");
            };
             </script>';
        return recaptcha_get_html(RECAPTCHA_PUBLIC_KEY, $error);
    }

    function _field($name) {
        if(trim($name) == '')
            return '';

        $temp = explode('[',$name);
        if(isset($this->queryStringParams["$name"]))
            unset($this->queryStringParams["$name"]);
        elseif($temp){
            if(isset($this->queryStringParams["$temp[0]"]))
                unset($this->queryStringParams["$temp[0]"]);
        }
        $requestArray = $this->Request->request;
        foreach($temp as $key){
            $key = str_replace(']', '', $key);
            if($key != ''){
                if(isset($requestArray[$key]))
                    $requestArray = $requestArray[$key];
                else
                    return '';
            }else
                return '';
        }

        return $requestArray;
    }

    function _refineRequestFields($request) {
        $newRequest = array();
        foreach($request as $key=>$value) {
            $field = explode('.', $key);
            $field = end($field);
            $newRequest[$field] = $value;
        }

        return $newRequest;
    }
}
?>