<?php
function pr($array) {
    echo "<pre>";
    print_r($array);
    echo "</pre>";
}

function debug($array) {
    echo "<pre>";
    print_r($array);
    echo "</pre>";

    die;
}

/**
 * The function deals with Language localization/translation
 *
 * @param string $string    Translation string
 * @param bool $return      Echo the translated string or return
 * @return string           Translated string
 */
if(!function_exists('__l')){
    function __l($string='', $return=false) {
        if($return){
            return $string;
        }else{
            print $string;
        }
    }
}

function displayQueries(){
    $queries = Database::$queries;
    echo '<div style="clear: both;"></div>';
    echo '<h2 style="background: #FFF; color:red; padding:5px; border-bottom:2px solid #CCC; margin:0; margin-top:20px;">SQL Queries</h2>';
    echo '<div style="background: #FFF">';
    if($queries){ 
		?>
<table cellspacing='10' style="font-size:16px; font-family:monospace;" width='100%' bgcolor="#FFFFFF">
			<tr>
                            <th align='left' width="3%" style="background: #FFF; color: #333">S.No.</th>
                            <th align='left' width="54%" style="background: #FFF; color: #333">QUERY</th>
                            <th align='left' width="20%" style="background: #FFF; color: #333">ERROR</th>
                            <th align='left' style="background: #FFF; color: #333">ROWS EFFECTED</th>
                            <th align='left' style="background: #FFF; color: #333">RESPONSE TIME</th>
			</tr>
		<?php
        foreach($queries as $key=>$q){
                        if($q['sql']){
			?>
			<tr>
                            <td align='left' class="input_field" valign="top" style="background: #FFF; color: #333"><?php echo $key + 1; ?></td>
                            <td align='left' class="input_field" valign="top" style="background: #FFF; color: #333"><?php echo $q['sql']; ?></td>
                            <td align='left' class="input_field" style='background: #FFF; color:red' valign="top"><?php echo $q['error']; ?></td>
                            <td align='left' class="input_field" valign="top" style="background: #FFF; color: #333"><?php echo $q['rows']; ?></td>
                            <td align='left' class="input_field" valign="top" style="background: #FFF; color: #333"><?php echo isset($q['time']) ? $q['time']: 0; ?> sec</td>
			</tr>
			<?php
        }}
		?>
		</table>
		<?php
    }
    echo '</div>';
}
	/*
	selResidential
		01 - Residential
		02 - Commercial
	selPackaging">
		00 - Customer Packaging
		01 - UPS Letter Envelope
		03 - UPS Tube
		21 - UPS Express Box
		24 - UPS Worldwide 25KG Box
		25 - UPS Worldwide 10KG Box
	selService
		1DM - Next Day Air Early AM
		1DA - Next Day Air
		1DP - Next Day Air Saver
		2DM - 2nd Day Air AM
		2DA - 2nd Day Air
		3DS - 3 Day Select
		GND - Ground
		STD - Canada Standard
		XPR - Worldwide Express
		XDM - Worldwide Express Plus
		XPD - Worldwide Expedited
		WXS - Worldwide Saver
	Sel Rate:
		Regular Daily Pickup - Daily Pickup service
		OP_WEB - Oncall Air Pickup Web (arrange on the web for UPS to pick up my packages)
		OP_PHONE - Oncall Air Pickup Phone (arrange by phone for UPS to pick up my packages)
		One+Time+Pickup - One Time Pickup
		Letter+Center - Drop-box Letter Center
		Customer Counter - Customer Counter
	*/

function getupsshipping($country,$city,$zip,$weight,$selRate,$selPackaging,$selResidential,$selService) {
    $Url = join("&", array("http://www.ups.com/using/services/rave/qcostcgi.cgi?accept_UPS_license_agreement=yes","10_action=3","13_product=".$selService,"14_origCountry=".urlencode(SHIP_FROM_COUNTRY),"15_origPostal=".urlencode(SHIP_FROM_ZIP),"origCity=".urlencode(SHIP_FROM_CITY),"19_destPostal=".urlencode($zip),"20_destCity=".urlencode($city),"22_destCountry=".urlencode($country),"23_weight=".$weight,"47_rateChart=".$selRate,"48_container=".$selPackaging,"49_residential=".$selResidential));
    $Resp = fopen($Url, "r");
    while(!feof($Resp)) {
        $Result = fgets($Resp, 500);
        $Result = explode("%", $Result);
        $Err = substr($Result[0], -1);
        switch($Err) {
            case 3:$ResCode = $Result[8];
                break;
            case 4:$ResCode = $Result[8];
                break;
            case 5:$ResCode = $Result[1];
                break;
            case 6:$ResCode = $Result[1];
                break;
        }
    }
    fclose($Resp);
    if(!$ResCode)
        $ResCode = "An error occured.";
    return $ResCode;

}

function redirect($args){
    global $prefix;
    $class = $method = $arguments= $url = $pfx = '';
    if(is_array($args)) {
        foreach($args as $key => $value) {
            if($key === 'class')
                $class = DS.$value;
            else if($key === 'method'){
                if(isset($prefix) && $prefix){
                    if(preg_match("/^(.*)_/", $value, $matches)){
                        if(in_array($matches[1], $prefix)){
                            $pfx = "/{$matches[1]}";
                            $value = preg_replace("/^{$matches[1]}_/i", '', $value);
                        }
                    }
                }
                $method = DS.$value;
            }else {
                $arguments .= DS.$value;
            }
        }

        $url = WWW_ROOT.$pfx.$class.$method.$arguments;
    }
    else
        $url = $args;
    if(isAjax()){
        if(isset($_GET['tbip']) && $_GET['tbip'])
            echo "<script> window.parent.document.getElementById('thickbox').style.display = 'none'; window.parent.location='$url';</script>";
        else
            echo "new function(){ref.pageTransistionShow('Redirecting...'); ref.autoCloseLightbox(); window.location='$url'}";
    }else{
        header("location: ".$url);
    }
    exit;
}

function ajaxRedirect($args) {
    global $prefix;
    $class = $method = $arguments= $url = $pfx = '';
    if(is_array($args)) {
        foreach($args as $key => $value) {
            if($key === 'class')
                $class = DS.$value;
            else if($key === 'method'){
                if(isset($prefix) && $prefix){
                    if(preg_match("/^(.*)_/", $value, $matches)){
                        if(in_array($matches[1], $prefix)){
                            $pfx = "/{$matches[1]}";
                            $value = preg_replace("/^{$matches[1]}_/i", '', $value);
                        }
                    }
                }
                $method = DS.$value;
            }else {
                $arguments .= DS.$value;
            }
        }

        $url = WWW_ROOT.$pfx.$class.$method.$arguments;
    }
    else
        $url = $args;

    echo "window.location='$url'";
    exit;
}

function referer($referer = '') {
    // Redirect to referer
    global $prefix;
    $pfx = '';
    $pass = '';
    if(empty($referer) && isset($_SESSION['_Referer'])) {
        $referer = $_SESSION['_Referer'];
        unset($_SESSION['_Referer']);
        header("location: ".$referer);
        exit;
    }elseif(!empty($referer)) {
        if(is_array($referer)) {
            $class = $method = $arguments= $url = '';
            foreach($referer as $key => $value) {
                if($key === 'class')
                    $class = DS.$value;
                else if($key === 'method'){
                    if(isset($prefix) && $prefix){
                        if(preg_match("/^(.*)_/", $value, $matches)){
                            if(in_array($matches[1], $prefix)){
                                $pfx = "/{$matches[1]}";
                                $value = preg_replace("/^{$matches[1]}_/i", '', $value);
                            }
                        }
                    }
                    $method = DS.$value;
                }else if($key === 'pass' && !empty($value)){
                    $pass = DS.implode('/', $value);
                }elseif(!empty($value)){
                    $arguments .= DS.$value;
                }
            }

            $url = WWW_ROOT.$pfx.$class.$method.$pass.$arguments;

        }else {
            $url = $referer;
        }
        if(isValidWebsiteURL($url))
            $_SESSION['_Referer'] = $url;

    }
}

function getReferer($referer = '') {
    // Redirect to referer
    if(empty($referer) && isset($_SESSION['_Referer'])) {
        $referer = $_SESSION['_Referer'];
        unset($_SESSION['_Referer']);
        return $referer;
    }

    return '';
}

function isValidWebsiteURL($url) {
    return preg_match('|^http(s)?://[a-z0-9-]+(.[a-z0-9-]+)*(:[0-9]+)?(/.*)?$|i', $url);
}

function upload($file, $type = array(), $dest = '', $notAllowedType = array()) {    
    if(is_array($file['error'])) {
        $fileName = array();
        foreach($file['error'] as $key=>$value) {
            if(!$file['error'][$key]) {
                if(!empty($dest)) {
                    if(empty($type)) {
                        if(!in_array($file['type'][$key], $notAllowedType)) {
                            $fileName[$key] = uniqid().'_'.preg_replace('/\s+|\'|\"|\\\/', '', $file['name'][$key]);

                            if(!move_uploaded_file($file['tmp_name'][$key], $dest.$fileName[$key])) {
                                unset($fileName[$key]);
                            }
                        }
                    }elseif(in_array($file['type'][$key], $type) && !in_array($file['type'][$key], $notAllowedType)) {
                        $fileName[$key] = uniqid().'_'.preg_replace('/\s+|\'|\"|\\\/', '', $file['name'][$key]);

                        if(!move_uploaded_file($file['tmp_name'][$key], $dest.$fileName[$key])) {
                            unset($fileName[$key]);
                        }
                    }                    
                }else {
                    return false;
                }
            }
        }
        return $fileName;
    }else {
        $fileName = '';
        if(!$file['error']) {
            if(!empty($dest)) {
                if(empty($type)) {
                    if(!in_array($file['type'], $notAllowedType)) {
                        $fileName = uniqid().'_'.preg_replace('/\s+|\'|\"|\\\/', '', $file['name']);

                        if(!move_uploaded_file($file['tmp_name'], $dest.$fileName)) {
                            return false;
                        }
                    }else {
                        return false;
                    }
                }elseif(in_array($file['type'], $type) && !in_array($file['type'], $notAllowedType)) {
                    $fileName = uniqid().'_'.preg_replace('/\s+|\'|\"|\\\/', '', $file['name']);

                    if(!move_uploaded_file($file['tmp_name'], $dest.$fileName)) {
                        return false;
                    }
                }
                return $fileName;
            }else {
                return false;
            }
        }
    }
    return true;
}

/**
 * Create Directory
 * @return String
 * @param varchar void
 */
function createDirectory() {
    $numArgs = func_num_args();
    $dir = func_get_arg(0);
    for($index=1; $index<$numArgs; $index++) {
        $dir .= func_get_arg($index).DS;
        if(!(file_exists($dir) && filetype($dir) == 'dir')) {
            mkdir($dir, 0777) or die("Error: Unable To Create Directory '$dir'");
        }
    }
    return $dir;
}

/**
 * Create Thumbnail(jpg/png)
 * @return
 * @param varchar $name
 * @param integer $new_w[optional]
 * @param integer $new_h[optional]
 */
function thumbNail($name, $saveImage='', $new_w = 50, $new_h = 50, $aspectRatio = true) {
    if(empty($saveImage))
        $saveImage = $name;
    $ext= strtolower(substr($name, strrpos($name, '.')));
  //  $system[1] = strtolower($system[1]);
    if (preg_match('/jpg|jpeg/',$ext)) {
        $src_img=imagecreatefromjpeg($name);
    }
    if (preg_match('/png/',$ext)) {
        $src_img=imagecreatefrompng($name);
    }
    $old_x=imageSX($src_img);
    $old_y=imageSY($src_img);
    if($aspectRatio) {
        if ($old_x > $old_y) {
            $thumb_w=$new_w;
            $thumb_h=$new_w*($old_y/$old_x);
        }
        if ($old_x < $old_y) {
            $thumb_w=$old_x*($new_w/$old_y);
            $thumb_h=$new_h;
        }
        if ($old_x == $old_y) {
            $thumb_w=$new_w;
            $thumb_h=$new_h;
        }
    }else {
        $thumb_w=$new_w;
        $thumb_h=$new_h;
    }

    // If no size given
    if(empty($new_w) && empty($new_h)) {
        $thumb_w=$old_x;
        $thumb_h=$old_y;
    }
    $dst_img=ImageCreateTrueColor($thumb_w,$thumb_h);
    imagecopyresampled($dst_img,$src_img,0,0,0,0,$thumb_w,$thumb_h,$old_x,$old_y);

    if (preg_match("/png/",$system[1])) {
        imagepng($dst_img,$saveImage);
    } else {
        imagejpeg($dst_img,$saveImage);
    }
    imagedestroy($dst_img);
    imagedestroy($src_img);
}

function import($class,$dir,$file) {
    include_once(LIB_ROOT.'/'.$dir.'/'.$file.'.php');
    $obj = new $class;
    return $obj;
}

function importLib($class,$dir,$file) {
    include_once(LIB_ROOT.'/'.$dir.'/'.$file.'.php');
}

function importClass($class, $instance=null){
    include_once(CLASS_ROOT.DS.$class.'.class.php');
    if($instance){
        $obj = $instance->getInstanceOf($class);
    }else{
        $obj = new $class(null);
    }
    return $obj;
}

function generatetooltipurl($url = array()) {
    $class = $method = $arguments= $option = '';
    if(is_array($url)) {
        foreach($url as $key => $value) {
            if($key === 'class') {
                $class = DS.$value;
            }
            else if($key === 'method')
                    $method = DS.$value;
                else if($key === 'method')
                        $method = DS.$value;
                    else {
                        if($value!='')
                            $arguments .= DS.$value;

                    }
        }
        return WWW_ROOT.$class.$method.$arguments;
    }
    return '';
}

/**
 * Download file.
 * @param	String	Actual file path.
 */
function download($path = '') {
// If requested file exists
    if (!empty($path) && file_exists($path)) {

        $file = substr($path, strrpos($path, '/') + 1);

        // Get extension of requested file
        $extension = strtolower(substr($file, strrpos($file, '.') + 1));
        // Determine correct MIME type
        switch($extension) {

            case "exe":     $type = "application/octet-stream";      break;

            case "txt":     $type = "text/plain";                    break;
            case "htm":     $type = "text/html";                   	 break;
            case "html":    $type = "text/html";                     break;
            case "php":     $type = "text/html";                     break;
            case "css":     $type = "text/css";                      break;
            case "js":      $type = "application/javascript";        break;
            case "json":    $type = "application/json";              break;
            case "xml":     $type = "application/xml";               break;

            // audio / videos
            case "wav":     $type = "audio/wav";                     break;
            case "wma":     $type = "audio/x-ms-wma";                break;
            case "wmv":     $type = "video/x-ms-wmv";                break;
            case "mov":     $type = "video/quicktime";               break;
            case "mp3":     $type = "audio/mpeg";                    break;
            case "mpg":     $type = "video/mpeg";                    break;
            case "mpeg":    $type = "video/mpeg";                    break;
            case "asf":     $type = "video/x-ms-asf";                break;
            case "avi":     $type = "video/x-msvideo";               break;
            case "flv":     $type = "video/x-flv";               break;

            case "zip":     $type = "application/x-zip-compressed";  break;
            case "rar":     $type = "encoding/x-compress";           break;

            // images
            case "png":     $type = "image/png"; 					 break;
            case "png":     $type = "image/x-png"; 					 break;
            case "jpe":     $type = "image/jpeg"; 					 break;
            case "jpg":     $type = "image/jpeg"; 					 break;
            case "jpeg":    $type = "image/jpeg"; 					 break;
            case "jpg":     $type = "image/pjpeg"; 					 break;
            case "gif":     $type = "image/gif"; 					 break;
            case "bmp":     $type = "image/bmp"; 					 break;
            case "ico":     $type = "image/vnd.microsoft.icon"; 	 break;

            default:        $type = "application/force-download";    break;

        }
        // Fix IE bug [0]
        $header_file = (strstr($_SERVER['HTTP_USER_AGENT'], 'MSIE')) ? preg_replace('/\./', '%2e', $file, substr_count($file, '.') - 1) : $file;
        // Prepare headers
        header("Pragma: public");
        header("Expires: 0");
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Cache-Control: public", false);
        header("Content-Description: File Transfer");
        header("Content-Type: " . $type);
        header("Accept-Ranges: bytes");
        header("Content-Disposition: attachment; filename=\"" . $header_file . "\";");
        header("Content-Transfer-Encoding: binary");
        header("Content-Length: " . filesize($path));
        // Send file for download
        if ($stream = fopen($path, 'rb')) {
            while(!feof($stream) && connection_status() == 0) {
            //reset time limit for big files
                set_time_limit(0);
                print(fread($stream,1024*8));
                flush();
            }
            fclose($stream);
        }
        return true;
    }else {
        return false;
    }
}

function email($from,$fromname,$to,$subject,$url=array(), $element='',$attachments=array(),$message=''){
        if($url || $element){            
            ob_start();
            if($element)
                include_once( VIEW_ROOT.DS.$element.'.itp' );
            else{
                $Content = new Content;                
                $Content->renderMail($url, $element);
            }
            $body = ob_get_contents();
            ob_end_clean();
        }else{
            $body=$message;
        }        
        if($body){
            $mail = import('PHPMailer','phpMailer','class.phpmailer');
            ///////////////////////////////
            $body             = preg_replace('/\\\\/','', $body); //Strip backslashes
            //$mail->IsSMTP();                           // tell the class to use SMTP
            $mail->SMTPAuth   = true;                  // enable SMTP authentication
            $mail->Port       = 25;                    // set the SMTP server port
            $mail->Host       = "174.121.216.39"; // SMTP server
            $mail->Username   = "mail@illuminz.com";     // SMTP server username
            $mail->Password   = "mail@2010";             // SMTP server password
            //$mail->IsSendmail();  // tell the class to use Sendmail

            $mail->From       = $from;
            $mail->FromName   = $fromname;

            if(is_array($to) && $to){
                foreach($to as $value) {
                    $mail->AddAddress($value);
                }
            }

            if(is_array($cc) && $cc){
                foreach($cc as $value) {
                    $mail->AddCC($value);
                }
            }

            if(is_array($bcc) && $bcc){
                foreach($bcc as $value) {
                    $mail->AddBCC($value);
                }
            }

            if($replyTo)
                $mail->AddReplyTo($replyTo, $replyToName);
            if($attachments){
                foreach($attachments as $attachment){
                    $path = ''; $name = '';
                    if(isset($attachment[0])){
                        $path = $attachment[0];
                    }
                    if(isset($attachment[1])){
                        $name = $attachment[1];
                    }
                    $mail->AddAttachment($path, $name);
                }
            }
            $mail->Subject  = $subject;
            $mail->AltBody    = "To view the message, please use an HTML compatible email viewer!"; // optional, comment out and test
            $mail->WordWrap   = 80; // set word wrap
            $mail->MsgHTML($body);
            $mail->IsHTML(true); // send as HTML
            $mail->Send();
            return;
    }
}

function phpmail($from,$fromname,$to,$subject,$contenturl, $cc = array(), $bcc = array(), $replyTo = '', $replyToName = '', $attachments = array()) {    
    $mail = import('PHPMailer','phpMailer','class.phpmailer');
    //$body             = file_get_contents($contenturl);

    //////////////////////////////////
    $strCookie = 'PHPSESSID=' . $_COOKIE['PHPSESSID'] . '; path=/';
    session_write_close();
    $ch = curl_init($contenturl);
    curl_setopt ($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
    curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt( $ch, CURLOPT_COOKIE, $strCookie );
    curl_setopt( $ch, CURLOPT_COOKIEJAR, $strCookie );
    curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt( $ch, CURLOPT_TIMEOUT, 10 );
    curl_setopt( $ch, CURLOPT_MAXREDIRS, 10 );
    $body = curl_exec($ch);
    curl_close($ch);
    $ch = "";
    ///////////////////////////////

    $body             = preg_replace('/\\\\/','', $body); //Strip backslashes
    //$mail->IsSMTP();                           // tell the class to use SMTP
    $mail->SMTPAuth   = true;                  // enable SMTP authentication
    $mail->Port       = 25;                    // set the SMTP server port
    $mail->Host       = "174.121.216.39"; // SMTP server
    $mail->Username   = "mail@illuminz.com";     // SMTP server username
    $mail->Password   = "mail@2010";            // SMTP server password
    //$mail->IsSendmail();  // tell the class to use Sendmail

    $mail->From       = $from;
    $mail->FromName   = $fromname;

    if(is_array($to) && $to){
        foreach($to as $value) {
            $mail->AddAddress($value);
        }
    }

    if(is_array($cc) && $cc){
        foreach($cc as $value) {
            $mail->AddCC($value);
        }
    }

    if(is_array($bcc) && $bcc){
        foreach($bcc as $value) {
            $mail->AddBCC($value);
        }
    }

    if($replyTo)
        $mail->AddReplyTo($replyTo, $replyToName);

    if($attachments){
        foreach($attachments as $attachment){
            $path = ''; $name = '';
            if(isset($attachment[0])){
                $path = $attachment[0];
            }
            if(isset($attachment[1])){
                $name = $attachment[1];
            }
            $mail->AddAttachment($path, $name);
        }
    }
    $mail->Subject  = $subject;
    $mail->AltBody    = "To view the message, please use an HTML compatible email viewer!"; // optional, comment out and test
    $mail->WordWrap   = 80; // set word wrap
    $mail->MsgHTML($body);
    $mail->IsHTML(true); // send as HTML
    $mail->Send();
    session_start();
    return;
}

function dateformat($dateStr = '', $format = DATEFORMAT) {
// If date string is not set, assign today's date.
    if(!$dateStr)
        $dateStr = date($format);
    elseif(preg_match('/0000-00-00/', $dateStr))
        return '';

    return date($format,strtotime($dateStr));
}
/*function to convert datepicker date ( dd/mm/yyyy ) to mysql datetime format */
function convertDatepickerDate($date=null) {
    if($date) {
        $date = explode("/",$date);
        $newDate = $date[2]."-".$date[1]."-".$date[0];
        return $newDate;
    }
    return '';
}

/*function to convert mysqlDefault date ( yyyy-mm-dd ) to datepicker datetime format ( dd/mm/yyyy ) */
function convertMysqlDate($date=null) {
    if($date) {
        $date = str_replace(" 00:00:00","",$date);
        $date = explode("-",$date);
        $newDate = $date[2]."/".$date[1]."/".$date[0];
        return $newDate;
    }
    return '';
}

function validEmailAddress($email) {
    $isValid = true;
    $atIndex = strrpos($email, "@");
    if (is_bool($atIndex) && !$atIndex) {
        $isValid = false;
    }else {
        $domain = substr($email, $atIndex+1);
        $local = substr($email, 0, $atIndex);
        $localLen = strlen($local);
        $domainLen = strlen($domain);
        if ($localLen < 1 || $localLen > 64) {
        // local part length exceeded
            $isValid = false;
        }else if ($domainLen < 1 || $domainLen > 255) {
            // domain part length exceeded
                $isValid = false;
            }
            else if ($local[0] == '.' || $local[$localLen-1] == '.') {
                // local part starts or ends with '.'
                    $isValid = false;
                }else if (preg_match('/\\.\\./', $local)) {
                    // local part has two consecutive dots
                        $isValid = false;
                    }else if (!preg_match('/^[A-Za-z0-9\\-\\.]+$/', $domain)) {
                        // character not valid in domain part
                            $isValid = false;
                        }else if (preg_match('/\\.\\./', $domain)) {
                            // domain part has two consecutive dots
                                $isValid = false;
                            }else if(!preg_match('/^(\\\\.|[A-Za-z0-9!#%&`_=\\/$\'*+?^{}|~.-])+$/',str_replace("\\\\","",$local))) {
                                // character not valid in local part unless
                                // local part is quoted
                                    if (!preg_match('/^"(\\\\"|[^"])+"$/',str_replace("\\\\","",$local))) {
                                        $isValid = false;
                                    }
                                }
               /*if ($isValid && !(checkdnsrr($domain,"MX") || checkdnsrr($domain,"A"))){
                       // domain not found in DNS
                       $isValid = false;
               }*/
    }
    return $isValid;
}

/**
 * Set a variable value globally
 * @global <type> $var
 * @param <type> $var
 * @param <type> $value
 */
function set($var, $value = ''){
    if(is_string($var)){
        global $$var;
        $$var = $value;
    }
}

function numberToCurrency($number, $decimal=0){
    $number = number_format($number, 2, '.', '');
    $numTmp = explode('.', $number);
    $number = intval($numTmp[0]);
    $len = strlen($number); //lenght of the no
    if($len>3){
        $num = substr($number,$len-3,3); //get the last 3 digits
        $number = sprintf('%d',$number/1000); //omit the last 3 digits already stored in $num
        while($number > 0) //loop the process - further get digits 2 by 2
        {
            $len = strlen($number);
            $num = substr($number,$len-2,2).",".$num;
            $number = sprintf('%d',$number/100);
        }
    }else{
        return sprintf('%10.2f',$number);;
    }

    if(isset($numTmp[1]) && $decimal)
        $num = $num .'.'. $numTmp[1];

    return $num;
}

/**
 * Substr with no broken word
 * @param <type> $str
 * @param <type> $start
 * @param <type> $end
 * @param <type> $next
 * @return <type>
 */
function sstr($str, $start, $end, $next = true){
    $str = strip_tags($str);
    $strLen = strlen($str);
    $tmpStr1 = substr($str, $start, $end);

    if($next){
        $tmpStr2 = substr($str, $end, $strLen);
        $tmpStr2 = preg_split('/ /', $tmpStr2);
        return ($tmpStr1.$tmpStr2[0]);
    }else{
        $tmpStr1 = substr($tmpStr1, 0, strrpos($tmpStr1, ' '));
        return $tmpStr1;
    }
}

/**
 * The letter l (lowercase L) and the number 1
 * have been removed, as they can be mistaken
 * for each other.
 */
function createRandomPassword($length = 5) {
    $chars = "abcdefghijkmnopqrstuvwxyz023456789@#$";
    srand((double)microtime()*1000000);
    $i = 0;
    $pass = '' ;
    while ($i < $length) {
        $num = rand() % 36;
        $tmp = substr($chars, $num, 1);
        $pass = $pass . $tmp;
        $i++;
    }
    return strtoupper($pass);
}

/**
 * The letter l (lowercase L) and the number 1
 * have been removed, as they can be mistaken
 * for each other.
 */
function createRandomNumericCode($length = 5) {
    $chars = "0123456789";
    srand((double)microtime()*1000000);
    $i = 0;
    $pass = '' ;
    while ($i < $length) {
        $num = rand() % 10;
        $tmp = substr($chars, $num, 1);
        $pass = $pass . $tmp;
        $i++;
    }
    return $pass;
}

/**
 * The letter l (lowercase L) and the number 1
 * have been removed, as they can be mistaken
 * for each other.
 */
function createRandomAlphaNumericCode($length = 5) {
    $chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZ023456789";
    srand((double)microtime()*1000000);
    $i = 0;
    $pass = '' ;
    while ($i < $length) {
        $num = rand() % 33;
        $tmp = substr($chars, $num, 1);
        $pass = $pass . $tmp;
        $i++;
    }
    return $pass;
}

function slug($slugvalue,$table,$class,$id=null,$no=0){
    $slugvalue = createSlug($slugvalue);
    $obj = importClass($class);
    if($id == null){
            $checkSlug = $obj->Database->mysqlSelect(array('slug'),$table,"slug = '".$slugvalue."'","","","","");
    }else{
            $checkSlug = $obj->Database->mysqlSelect(array('slug'),$table,"slug = '".$slugvalue."' and id != '".$id."'","","","","");
    }
    if($obj->Database->mysqlnumrows($checkSlug) > 0){
            $no = $no + 1;
            $slugvalue = $slugvalue.$no;
            if($id == null){
                    slug($slugvalue, $table, $class, $id, $no);
            }else{
                    slug($slugvalue, $table, $class, $id, $no);
            }
    }
    return $slugvalue;
}



function createSlug($name=null){
   if($name != null){
       $arraySymbols = array(",","\'", "/", "'", "`", "~", "!", "@", "#", "$", "%", "^", "&", "*", "(", ")", "_", "+", "=", "{", "}", "[", "]", "|", ":", ";", "<", ">", "?", ".", " ");
       $name = str_replace($arraySymbols,"-",$name);
     //  $name = preg_replace('/\-+/', '-', $name);
       $name = preg_replace('/\-$/', '', preg_replace('/\-+/', '-', $name));
       return strtolower($name);
   }
   return;
}


function slugToId($slugvalue,$table,$class){
    $obj = importclass($class);
    $checkSlug = $obj->Database->mysqlSelect(array('id'),$table,"slug = '".$obj->Database->escape($slugvalue)."'");
    if($obj->Database->mysqlnumrows($checkSlug) > 0){
            $row = $obj->Database->mysqlfetchobject($checkSlug);
            return $row->id;
    }
    return 0;
}

function getId($slugvalue, $table, $obj){
    $checkSlug = $obj->Database->mysqlSelect(array('id'),$table,"slug = '".$obj->Database->escape($slugvalue)."'");
    if($obj->Database->mysqlnumrows($checkSlug) > 0){
            $row = $obj->Database->mysqlfetchobject($checkSlug);
            return $row->id;
    }
    return 0;
}

function getDims($img = '', $newW = ''){
    $newH = 0;
    if($img){
        $img = preg_replace('/\s/', '%20', $img);
        $oldDims = getimagesize($img);
        $newH = ceil($newW/($oldDims[0]/$oldDims[1]));
    }

    return $newH;
}

function zerofill($num, $zerofill){
   while (strlen($num) < $zerofill){
       $num = "0" . $num;
    }
   return $num;
}

/* AJAX check  */
function isAjax(){    
    if(!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        return true;
    }elseif(isset($_GET['tbip']) && $_GET['tbip']){
        // Request from thickbox/iframe
        return true;
    }

    return false;
}

/**
 * Validate captcha
 */
function reCaptcha(){
    include_once(LIB_ROOT.'/recaptcha/recaptchalib.php');
    if ($_POST["recaptcha_response_field"]) {
        $resp = recaptcha_check_answer (RECAPTCHA_PRIVATE_KEY,
                                        $_SERVER["REMOTE_ADDR"],
                                        $_POST["recaptcha_challenge_field"],
                                        $_POST["recaptcha_response_field"]);

        if ($resp->is_valid) {
                return true;
        } else {
                return false;
        }
    }
}

function isIE(){
    if(preg_match('/MSIE/', $_SERVER['HTTP_USER_AGENT'])){
        return true;
    }

    return false;
}

/**
 * Convert time zone
 *
 * @param string $date_str  Date string
 * @param string $tz    New time zone i.e., Asia/Calcutta
 * @param string $date_format   Date format
 * @return date
 */
function convertTz ($date_str, $tz, $date_format = "r") {
  $time = strtotime($date_str);
  
  // Persist current time zone
  $tz_bak = date_default_timezone_get();
  
  // Set new time zone
  date_default_timezone_set($tz);
  
  $ret = date($date_format, $time);

  // Restore previous time zone
  date_default_timezone_set($tz_bak);
  
  return $ret;
}

/**
	* Function to validate name 
**/

function validateName($value=0){
	if($value){
		if(preg_match("/^[a-zA-Z\s+]/", $value)){
			return true;             
		}
	}
	return false;
}

/**
	* Function to validate phone (only digits)
**/

function validatePhone($value=0){
	if($value){
		if(preg_match("/^[0-9]/", $value)){
			return true;             
		}
	}
	return false;
}


/**
	* Function to validate phone (only digits)
**/

function validPassword($value=0){
	if($value){
		if(preg_match("/^[a-zA-Z0-9{6,15}]/", $value)){
			return true;             
		}
	}
	return false;
}
?>