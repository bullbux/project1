<?php
class Content {
    public $clas;
    public $method;
    public $layout;
    public $view;
    public $pageTitle='';
    public $metaKeywords='';
    public $metaDescription='';
    public $errors;
    public $element;
    public $param;
    public $params = array();
    public $prefix = null;
    public $data = 2;
    private $script = '';
    private $beforeFilterMethod = 'beforeFilter';

    public function element($file) {
        $this->element = ELEMENT_ROOT.'/'.$file.'.itp';
    }
    public function __construct() {
        $this->clas = DEFAULT_CLASS;
        $this->method = DEFAULT_METHOD;
        $this->layout = DEFAULT_LAYOUT;
        $this->view = '';
        $this->pageTitle = '';
        $this->metaDescription = '';
        $this->metaKeywords = '';
        $this->errors = array();
    }
    public function urlParse($url) {
        global $prefix;
        if(defined('DB_ACCESS') && DB_ACCESS)
            $this->loadSettings();            
        $tempurl = explode('?', $url);
        $url = $tempurl[0];
        $url = str_replace(ROOT_DIR, '', $url);
        $params = array();
        $errorMessage = array();
        $message = array();
        $class  = '';
        $method = '';
        $index = 0;
        $pfx = false;
        if($url == '') {
            if(isset($prefix) && $prefix){
                if(preg_match("/^(.*)_/", $this->method, $matches)){
                    if(in_array($matches[1], $prefix)){
                        $pfx = "/{$matches[1]}";
                        $this->clas = $pfx .'/'. $this->clas;
                        $this->method = preg_replace("/^{$matches[1]}_/i", '', $this->method);
                    }
                }
            }

            $url = $this->clas . '/' . $this->method;
        }
        $urlArray = explode('/', $url);
        foreach($urlArray as $key => $value) {
            if($value !== '') {
                $paramArray[] = $value;
            }
        }
        if(count($paramArray) > 0) {
            if(!isset($paramArray[1])) {
                $paramArray[1] = DEFAULT_METHOD;
            }
            foreach($paramArray as $key => $value) {
                if(isset($prefix) && $prefix) {                    
                    if($key == 0) {
                        $prefixKey = array_keys($prefix, strtolower($value));
                        if($prefixKey){
                            $index = 1;
                            $pfx = true;
                            $this->prefix = $prefix[$prefixKey[0]];
                        }
                    }
                }
                if($key == $index) {
                    $class = $value;
                }else if($key == ($index + 1)) {
                        if(trim($value) == '')
                            $value = 'index';
                        if($pfx) {
                            $value = $prefix[$prefixKey[0]]. '_' . $value;
                        }
                        $method = $value;
                    }elseif($key != 0) {
                        $params[] = $value;
                    }
            }

            if(trim($method) == '') {
                $method = 'index';
                if($prefixKey){
                    $method = $prefix[$prefixKey[0]]. '_' . $method;
                }                
            }

            $this->params['class'] = $class;
            $this->params['method'] = $method;
            $this->params['pass'] = $params;
            if(isset($_SERVER['QUERY_STRING'])){
                parse_str($_SERVER['QUERY_STRING'], $this->params['qs']);
                unset($this->params['qs']['url']);
            }
            $this->params['prefix'] = $this->prefix;
        } 
        if(file_exists(CLASS_ROOT.DS.$class.'.class.php')) {
            include_once(CLASS_ROOT.DS.$class.'.class.php');
            //$method = preg_replace('/[A-Z]/e', 'strtolower("_$0")', $str);
            $this->view = VIEW_ROOT.DS.$class.DS.$method.'.itp';
            $this->clas = $class;
            $this->method = $method;            
            if(class_exists($class)) {
                $$class = new $class($this);
                // Execute beforeFilter Method (if exists) in the current executing class
                call_user_func_array(array(&$$class, $this->beforeFilterMethod), null);
                if($method != '') {
                    if(method_exists($$class,$method)) {
                        if($params)
                            $this->param = $params[0];
                        call_user_func_array(array(&$$class, $method), $params);
                        
                        $this->$class = $$class;                        
                        if(property_exists($$class, 'errors') && $$class->errors)
                            $this->errors =  $$class->errors;
                        if(property_exists($$class, 'pageTitle') && trim($$class->pageTitle) != '')
                            $this->pageTitle =  $$class->pageTitle;
                        if(property_exists($$class, 'metaDescription') && trim($$class->metaDescription) != '')
                            $this->metaDescription =  $$class->metaDescription;
                        if(property_exists($$class, 'metaKeywords') && trim($$class->metaKeywords) != '')
                            $this->metaKeywords =  $$class->metaKeywords;
                        if(property_exists($$class, 'pagelayout') && trim($$class->pagelayout) != '')
                            $this->layout =  $$class->pagelayout;
                        if(property_exists($$class, 'element') && trim($$class->element) != ''){
                            // $this->layout = 'ajax';
                             $this->view = VIEW_ROOT.DS.'elements'.DS.trim($$class->element).'.itp';
                        }
                        if(property_exists($$class, 'view') && trim($$class->view) != ''){
                             $this->view = VIEW_ROOT.DS.$this->params['class'].DS.trim($$class->view).'.itp';
                        }
                        if(isAjax())
                            $this->layout = 'ajax';
                        if(!file_exists(LAYOUT_ROOT.DS.$this->layout.'.itp')) {
                            $message[] = 'The file '.LAYOUT_ROOT.DS.$this->layout.'.itp class.php could not be found.';
                        }
                    }else {
                        $message[] = 'The method '.$method.' is missing.';
                    }

                }else {
                    if(method_exists($$class,'index')) {
                        $method = 'index';
                    }else {
                        $message[] = 'The method index is missing in class '.$class;
                    }
                }
            }else {
                $message[] = 'The class '.$class.' is missing.';
            }
        }else {
            $message[] = 'The file '.CLASS_ROOT.DS.$class.'.class.php could not be found.';
        }
        if(count($message) > 0) {
            if(Configure::read('debug') == INACTIVE && file_exists(CLASS_ROOT.DS.'errors.class.php')) {
                include_once(CLASS_ROOT.DS.'errors.class.php');
                if(class_exists('errors')){
                    $obj = new errors;
                    if(method_exists($obj, 'error404')){ 
                        call_user_func_array(array(&$obj, 'error404'), array());
                        $this->view = VIEW_ROOT.DS.'errors'.DS.'error404.itp';
                        if(property_exists($obj, 'pageTitle') && trim($obj->pageTitle) != '')
                            $this->pageTitle =  $obj->pageTitle;
                        if(property_exists($obj, 'pagelayout') && trim($obj->pagelayout) != '')
                            $this->layout =  $obj->pagelayout;
                        if(isAjax() || (isset($_GET['tbip']) && $_GET['tbip']))
                            $this->layout = 'ajax';
                    }else{
                        debug($message);
                    }
                }else{
                    debug($message);
                }
            }else{
                debug($message);
            }
        }
    }

    private function loadSettings() {
        if(file_exists(CLASS_ROOT.DS.'settings.class.php')) {
            include_once(CLASS_ROOT.DS.'settings.class.php');
            if(class_exists('settings')) {
                $settings = new settings;
                if(method_exists($settings, 'usesettings'))
                    $settings->usesettings();
            }
        }
    }

    public function render($url = array(), $element = '',$flag=false) {        
        global $prefix;
        $class = $method ="";
        $urlArray = $url;
        $index = 0;
        $pfx = false;
        foreach($urlArray as $key => $value) {
            $paramArray[] = $value;
        }
        if(count($paramArray) > 0) {
            if(!isset($paramArray[1])) {
                $paramArray[1] = DEFAULT_METHOD;
            }
            foreach($paramArray as $key => $value) {
                if(isset($prefix) && $prefix) {
                    if($key == 0) {
                        $prefixKey = array_keys($prefix, strtolower($value));
                        if($prefixKey){
                            $index = 1;
                            $pfx = true;
                        }
                    }
                }
                if($key == $index) {
                    $class = $value;
                }else if($key == ($index + 1)) {
                        if(trim($value) == '')
                            $value = 'index';
                        if($pfx) {
                            $value = $prefix[$prefixKey[0]] . '_' . $value;
                        }
                        $method = $value;
                    }elseif($key != 0) {
                        $params[] = $value;
                    }
            }

            if(trim($method) == '') {
                $method = 'index';
                if($prefixKey){
                    $method = $prefix[$prefixKey[0]]. '_' . $method;
                }  
            }
        }
        if($method == '')
            $method = 'index';
        include_once(CLASS_ROOT.DS.$class.'.class.php');
        if(class_exists($class)) {            
            $$class = new $class;
            // Execute beforeFilter Method (if exists) in the current executing class
            call_user_func_array(array(&$$class, $this->beforeFilterMethod), null);
            if($method != '') {                
                if(method_exists($$class,$method)) {
                    if($this->clas == $class && $this->$class)
                        call_user_func_array(array($this->$class, $method), $params);
                    else {
                        call_user_func_array(array($$class, $method), $params);
                        $this->$class = $$class;
                    }
                    $Form = new form($this);
                    $this->layout =  'ajax';                    
                    if(!empty($element)) {
                        $method = $element;
                        $class = 'elements';
                    }elseif(property_exists($this->$class, 'element') && trim($this->$class->element) != ''){ 
                         $method = trim($this->$class->element);
                         $class = 'elements';                         
                    }
                   
                    if(!file_exists(VIEW_ROOT.DS.$class.DS.$method.'.itp')) {
                        $message[] = 'The file '.VIEW_ROOT.DS.$class.DS.$method.'.itp class.php could not be found.';
                    }
                    else { 
                        $this->element = VIEW_ROOT.DS.$class.DS.$method.'.itp';
                    }                    
                }else {
                    $message[] = 'The method '.$method.' is missing.';
                }

            }else {
                if(method_exists($$class,'index')) {
                    $method = 'index';
                }else {
                    $message[] = 'The method index is missing in class '.$class;
                }
            }
        }
    }

    /**
     * Render mail layout
     *
     * @global array $prefix
     * @param string $url
     * @param string $element
     * @param boolean $flag
     */
    public function renderMail($url = array(), $element = '',$flag=false) {
        global $prefix;
        $class = $method ="";
        $urlArray = $url;
        $index = 0;
        $pfx = false;
        global $data;
        foreach($urlArray as $key => $value) {
            $paramArray[] = $value;
        }
        if(count($paramArray) > 0) {
            if(!isset($paramArray[1])) {
                $paramArray[1] = DEFAULT_METHOD;
            }
            foreach($paramArray as $key => $value) {
                if(isset($prefix) && $prefix) {
                    if($key == 0) {
                        $prefixKey = array_keys($prefix, strtolower($value));
                        if($prefixKey){
                            $index = 1;
                            $pfx = true;
                        }
                    }
                }
                if($key == $index) {
                    $class = $value;
                }else if($key == ($index + 1)) {
                    if(trim($value) == '')
                        $value = 'index';
                    if($pfx) {
                        $value = $prefix[$prefixKey[0]] . '_' . $value;
                    }
                    $method = $value;
                }elseif($key != 0) {
                    $data = $value;
                }
            }

            if(trim($method) == '') {
                $method = 'index';
                if($prefixKey){
                    $method = $prefix[$prefixKey[0]]. '_' . $method;
                }
            }
        }
        if($method == '')
            $method = 'index';
        include_once(CLASS_ROOT.DS.$class.'.class.php');
        if(class_exists($class)) {
            $$class = new $class;
            if($method != '') {
                if(method_exists($$class,$method)) {
                    $this->$class = new $class;
                    call_user_func_array(array($this->$class, $method), array());
                    $Form = new form($this);
                    $this->layout =  'ajax';

                    if(property_exists($this->$class, 'pagelayout') && trim($obj->pagelayout) != '')
                            $this->layout =  $this->$class->pagelayout;
                            
                    if(!empty($element)) {
                        $method = $element;
                        $class = 'elements';
                    }elseif(property_exists($this->$class, 'element') && trim($this->$class->element) != ''){
                         $method = trim($this->$class->element);
                         $class = 'elements';
                    }                    
                    if(!file_exists(VIEW_ROOT.DS.$class.DS.$method.'.itp')) {
                        $message[] = 'The file '.VIEW_ROOT.DS.$class.DS.$method.'.itp class.php could not be found.';
                    }
                    else {
                        global $Include;
                        $this->element = VIEW_ROOT.DS.$class.DS.$method.'.itp';
                        require_once($this->element);
                    }
                }else {
                    $message[] = 'The method '.$method.' is missing.';
                }

            }else {
                if(method_exists($$class,'index')) {
                    $method = 'index';
                }else {
                    $message[] = 'The method index is missing in class '.$class;
                }
            }
        }
    }

    /**
     * Add custom script
     *
     * @param <type> $script URL/Inline script
     */
    function addScript($script=''){
        if($script){
            if(preg_match('/^http:/', $script)){
                $this->script .= "<script type='text/javascript' language='javascript' src='$script'></script>\n";
            }else{
                $this->script .= $script . "\n";
            }            
        }
    }
    /**
     * Add custom CSS
     *
     * @param <type> $css URL/Inline css
     */
    function addCss($css=''){
        if($css){
            if(preg_match('/^http:/', $css)){
                $this->script .= "<style type='text/css' href='$css'></style>\n";
            }else{
                $this->script .= $css . "\n";
            }
        }
    }

    /**
     * Add scripts in the <head></head> tag
     */
    function scripts(){
        return $this->script;
    }
}
?>