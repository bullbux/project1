<?php
# This is Index!!!!!
ob_start();
include_once(dirname(__FILE__).'/includes/include.php');
if(!Configure::read('debug'))
{
    // Stop all error reportings on the user screen
    ini_set('display_errors', 'Off');
    error_reporting(~E_ALL);
}
$url = isset($_REQUEST['url']) ? $_REQUEST['url'] : '';
$Content->urlParse($url);
include_once(dirname(__FILE__).'/includes/form.inc.php');
$Form = new form($Content);
$session = new Session;
include_once(dirname(__FILE__).'/layouts/'.$Content->layout.'.itp');
if(Configure::read('debug') && DB_ACCESS)
{
    displayQueries();
}
ob_flush();
?>