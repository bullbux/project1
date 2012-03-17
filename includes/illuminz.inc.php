<?php 
if(defined('DB_ACCESS') && DB_ACCESS)
    include_once(dirname(__FILE__).'/database.inc.php');
    
include_once(dirname(__FILE__).'/session.inc.php');
include_once(dirname(__FILE__).'/request.inc.php');
include_once(dirname(__FILE__).'/message.inc.php');
include_once(dirname(__FILE__).'/form.inc.php');

class classIlluminz {

    private $str = '';
    public $Database;
    public $Session;
    public $Request;
    public $Message;
    public $params;
    /**
     * Page Layout
     *
     * @var     string  Common page layout for admin panel
     * @access  public
     * @see admin_<method>()
     */
    var $pagelayout = null;

    /**
     * Page Title
     *
     * @var     string  Page title
     * @access  public
     */
    var $pageTitle = '';

    /**
     * Page Meta Description
     *
     * @var     string  Page Meta Description
     * @access  public
     */
    var $metaDescription = '';

    /**
     * Page Meta Keywords
     *
     * @var     string  Page Meta Keywords
     * @access  public
     */
    var $metaKeywords = '';

    /**
     * Form field error messages
     *
     * @var     array  Contains form field error messages
     * @access  public
     */
    var $errors = array();

    /**
     * Element to be rendered in view file
     *
     * @var     string  Element file's relative path
     * @access  public
     */
    var $element = '';

    var $Content = null;

    public function __construct($Content) {
        //$this->Settings = new settings;
        if(defined('DB_ACCESS') && DB_ACCESS)
            $this->Database = new database;
        $this->Session = new session;
        $this->Request = new request;
        $this->Message = new message;
        $this->Content = $Content;
        $this->params = $Content->params;
    }

    /**
     * Execute before executing any method of this class
     *
     * Derived class may override this method
     */
    function beforeFilter(){
    }

    /**
     * Get instance of a class
     *
     * @param <type> $class
     * @return class
     */
    function getInstanceOf($class){
        return new $class($this->params);
    }

    /**
     * Mod Rewriting
     */
    public function cleanUrl(){
        $root = DOCUMENT_ROOT . DS . ROOT_DIR_NAME;
		//var_dump($root); exit;
        $htaccess = $root . DS . '.htaccess';

        if(defined('MOD_REWRITE') && MOD_REWRITE){
            if(file_exists($htaccess)){
                // Check write mode of the .htaccess file.
                if (is_writable($htaccess)) {
                    // Open the .htaccess file in write mode.
                    if (!$handle = fopen($htaccess, 'w')) {
                         echo "Cannot open file ($htaccess)";
                         exit;
                    }
                    $contents = $this->htaccessContents();
                    // Write $contents to our opened file.
                    if (fwrite($handle, $contents) === FALSE) {
                        echo "Cannot write to file ($htaccess)";
                        exit;
                    }

                    echo "Mod rewriting has been done successfully. <a href='" . WWW_ROOT . "'>Click here</a> to proceed...";
                    fclose($handle);
                    // Disable mode rewriting
                    $this->disableModRewrite();
                }else{
                    echo "The file $htaccess is not writable.";
                    exit;
                }
            }else{
                echo $htaccess. "does not exist.";
                exit;
            }

            exit;
        }else{
             echo "This task has been blocked.";
             exit;
        }
    }

    private function htaccessContents(){
        $this->str .= "<IfModule mod_rewrite.c>" . "\n";
        $this->str .= "RewriteEngine On" . "\n";
        $this->str .= "RewriteCond %{REQUEST_FILENAME} !-d" . "\n";
        $this->str .= "RewriteCond %{REQUEST_FILENAME} !-f" . "\n";        
        $this->customUrls();
        $this->str .= "RewriteRule ^(.*)$ index.php?url=$1 [QSA,L]" . "\n";
        $this->str .= "</IfModule>";

        return $this->str;
    }

    private function customUrls(){
        $data = $this->modRewrite();

        if($data){
            foreach($data as $key=>$value){
               foreach($value as $k=>$v){
                   $this->str .= "########## $k --> $key ######################" . "\n";
                   $this->str .= "RewriteRule ^$k/(.*)$ index.php?url=$key/$1 [NC,L]" . "\n";
                   $this->str .= "##################################################" . "\n";
               }
            }
        }
    }

    private function disableModRewrite(){
        $str = '';
        $includes = DOCUMENT_ROOT . DS . ROOT_DIR_NAME . DS . 'includes';
        $define = $includes . DS . 'define.php';

        $file = file($define, FILE_SKIP_EMPTY_LINES|FILE_IGNORE_NEW_LINES);
        
        if ($file === false) {
            echo "Cannot read to file ($define)";
            exit;
        }

        if($file){
            foreach($file as $key=>$value){
                if(strpos($value, 'MOD_REWRITE')){
                    $str .= "define('MOD_REWRITE', 0);" . "\n";
                }else{
                    $str .= $file[$key] . "\n";
                }
            }

            if (!$handle = fopen($define, 'w')) {
                 echo "Cannot open file ($define)";
                 exit;
            }

            // Write $str to opened file.
            if (fwrite($handle, $str) === FALSE) {
                echo "Cannot write to file ($define)";
                exit;
            }
        }
    }

    /**
     * Set a variable value globally
     * 
     * @global <type> $var
     * @param <type> $var
     * @param <type> $value
     */
    function set($var, $value = '', $isArray = false) {
        if(is_string($var)) {
            global $$var;
            if(!$isArray)
                $$var = $value;
            else {
                if(!isset($$var) || empty($$var))
                    $$var = array($value);
                else
                    array_push($$var, $value);
            }                
        }
    }

    function email($Content, $url=array()){
        if($url){
        //    $Content = new Content;
           // $Content->renderMail($url);
            pr($Content);
        }
    }
}
?>