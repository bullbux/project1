<?php
define('MOD_REWRITE', 0);

define('DS', DIRECTORY_SEPARATOR);

if(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'])
    define('PROTOCOL', 'https://');
else
    define('PROTOCOL', 'http://');
define('HTTP_HOST', $_SERVER['HTTP_HOST']);
define('DOCUMENT_ROOT', $_SERVER['DOCUMENT_ROOT']);

if(defined('ROOT_DIR_NAME') && trim(ROOT_DIR_NAME) != '/'){
	define('ROOT_DIR', '/' . ROOT_DIR_NAME . '/');
        define('ROOT_DIR_DS', ROOT_DIR);
	define('WWW_ROOT', PROTOCOL . HTTP_HOST);
}
else{
	define('ROOT_DIR', '');
        define('ROOT_DIR_DS', '/');
	define('WWW_ROOT', PROTOCOL . HTTP_HOST);
}

define('CLASS_ROOT', DOCUMENT_ROOT . ROOT_DIR_DS . "classes");
define('VIEW_ROOT', DOCUMENT_ROOT . ROOT_DIR_DS . "views");
define('RESOURCES_ROOT', DOCUMENT_ROOT . ROOT_DIR_DS . "resources/");
define('LIB_ROOT', DOCUMENT_ROOT . ROOT_DIR_DS ."library/");
define('ELEMENT_ROOT', VIEW_ROOT . "/elements");
define('LAYOUT_ROOT', DOCUMENT_ROOT . ROOT_DIR_DS . "layouts");
define('DATEFORMAT', 'd-M-Y');
/* PROXY SETTINGS */
define('USE_PROXY', FALSE);
define('PROXY_HOST', '127.0.0.1');
define('PROXY_PORT', '808');
define('VERSION', '3.0');
?>