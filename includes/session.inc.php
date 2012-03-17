<?php
/**
 * Defines the interface for the SessionManager class
 *
 */
class session {

    private $sessionStr = '';
    private $sessionStrSize = '';

    public function __construct() {
        if(!session_id())
            session_start();
    }

    public function write($sessionName, $sessionValue) {
        $_SESSION[$sessionName] = $sessionValue;
    }

    public function writeArray($sessionName, $sessionValue, $sessionIndex = '') {
        if(empty($sessionIndex)) {
            $_SESSION[$sessionName][] = $sessionValue;
        }else {
            $_SESSION[$sessionName][$sessionIndex] = $sessionValue;
        }
    }

    public function read($sessionName, $where = '') {
        if($sessionName) {
            $tempSession = $_SESSION;
            $sess = explode('.', $sessionName);
            foreach($sess as $s) {
                if (isset($tempSession[$s])) {
                    $tempSession = $tempSession[$s];
                }else {
                    return false;
                }
            }
            if($where) {
                if($tempSession != $where)
                    return false;
            }
            return $tempSession;
        }else {
            return false;
        }
    }

    public function readAll() {
        if (isset($_SESSION)) {
            return $_SESSION;
        }else {
            return "";
        }
    }
    public function session_set($sessionName) {
        if (isset($_SESSION[$sessionName]) && $_SESSION[$sessionName] != "") {
            return true;
        }else {
            return false;
        }
    }

    public function delete($sessionName) {
        if($sessionName) {
            $this->sessionStr = explode('.', $sessionName);
            $this->sessionStrSize = count($this->sessionStr) - 1;
            if($this->sessionStrSize === 0)
                unset($_SESSION[$sessionName]);
            elseif(isset($this->sessionStr[0]))
                $_SESSION[$this->sessionStr[0]] = $this->_del($_SESSION[$this->sessionStr[0]]);
            return true;
        }else {
            return false;
        }
    }

    private function _del($sess) {
        $i = $this->sessionStr;
        $s = $this->sessionStrSize;
        static  $j = 1;
        if($j == $s) {
            if(isset($sess[$i[$j]]))
                unset($sess[$i[$j]]);
        }else {
            $sess[$i[$j]] = $this->_del($sess[$i[$j++]]);
        }
        return $sess;
    }

    public function destroy() {
        session_destroy();
        unset($_SESSION);
        return true;
    }

    public function sessionid() {
        return session_id();
    }
    public function checkSess($Session) {
        if($Session->read('User.id'))
            return true;
        else {
            redirect(array('class'=>'users','method'=>'login'));
            exit;
        }
    }
    public function checkAdminSess($Session) {
        global $userTypes;
        if($Session->read('User.id') && ($Session->read('User.user_type') == $userTypes['ADMIN']  || ($Session->read('User.user_type') == ADMINISTRATOR)))
            return true;
        else {
            redirect(array('class'=>'users','method'=>'login'));
            exit;
        }
    }

    public function checkSession($Session) {
        if($Session->read('User.id'))
            return true;
        else
            return false;
    }

    public function checkAdminSession($Session,$access_level=null) {
        if($Session->read('User.id') == 1) {
            return true;
        }else {
            $obj = importClass('database');
            $row = $obj->mysqlSelect(array("AL.access_level"),"access_levels AL, usertype_access_levels UAL","UAL.usertype_id = '".$Session->read('User.user_type')."' and AL.access_level = '".$access_level."'");
            if($obj->mysqlnumrows($row) > 0) {
                return true;
            }
        }
        return false;
    }


    public function checkAdminSessionCustom($Session) {
        global $userTypes;
        if($Session->read('User.id') && ($Session->read('User.user_type') == $userTypes['ADMIN'] || ($Session->read('User.user_type') == ADMINISTRATOR)))
            return true;
        else
            redirect(array('class'=>'users','method'=>'admin_login'));
    }


    public function checkUserSession($Session, $userType = '') {
        if($Session->read('User.id')) {
            if(is_array($userType) && in_array($Session->read('User.user_type'), $userType))
                return true;
            elseif(!is_array($userType) && $Session->read('User.user_type') == $userType) {
                return true;
            }
        }
        return false;
    }


    /**
     * Set flash message
     */
    public function setFlash() {
        $numArgs = func_num_args();
        $args = func_get_args();
        switch($numArgs) {
            case 1:
                $this->write('flashMsg', $args[0]);
                break;
            case 2:
                if(!empty($args[0]) && is_string($args[0])) {
                    $this->write($args[0], $args[1]);
                }
                break;
        }
    }

    /**
     * Display the flash message set in the session
     *
     */
    public function flash($showAtTop = true) {
        $flashMsg = '';
        $numArgs = func_num_args();
        $args = func_get_args();
        switch($numArgs) {
            case 1:
                $flashMsg = $this->read($args[0]);
                if($flashMsg)
                    $this->delete($args[0]);
                break;
            default:
                $flashMsg = $this->read('flashMsg');
                if($flashMsg)
                    $this->delete('flashMsg');
        }
        if(trim($flashMsg) != '') {
            $flashMsg = "<div class='flash-msg'>" . $flashMsg . "</div>";
        }
        if($showAtTop) {
            if(trim($flashMsg) != '') {
                $flashMsg = preg_replace('/"/', '\'', $flashMsg);
                $flashMsg = '<script type="text/javascript"> $().ready(function(){Event.flash("'.str_replace('"', "'", $flashMsg).'");}); </script>';
            }
        }
        echo $flashMsg;
    }

    /**
     * Get the flash message set in the session
     * @return String Flash message
     */
    public function getFlash() {
        $flashMsg = '';
        $numArgs = func_num_args();
        $args = func_get_args();

        switch($numArgs) {
            case 1:
                $flashMsg = $this->read($args[0]);
                if($flashMsg)
                    $this->delete($args[0]);
                break;
            default:
                $flashMsg = $this->read('flashMsg');
                if($flashMsg)
                    $this->delete('flashMsg');
        }

        return $flashMsg;
    }
} 
?>