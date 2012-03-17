<?php 
class settings extends classIlluminz {
    var $pagelayout = 'admin';
    var $records=array();

    public function usesettings() {
        $result = $this->Database->mysqlSelect(array('name', 'title', 'type', 'value'),'settings',"","","","","");
        $this->records = $this->Database->mysqlfetchobjects($result);
        foreach($this->records as $setting) {
            if(!defined($setting->name))
                define($setting->name,$setting->value);
        }
    }

    public function admin_index() {
        $this->pageTitle = 'System Settings';
        if($this->Session->checkAdminSession($this->Session)) {
            if($this->Request->request['update']) {
                foreach($this->Request->request as $key=>$value) {
                    $where = " name = '$key' ";
                    $this->Database->mysqlUpdate('settings', array('value'), array($value), $where);
                    $this->Session->setFlash('<div class="flash-success">Settings have been updated successfully.</div>');
                }
            }
            $result = $this->Database->mysqlSelect(array('name', 'title', 'type', 'value'),'settings',"","","","","");
            $this->records = $this->Database->mysqlfetchobjects($result);
        }else {
            referer(array('class'=>'settings','method'=>'admin_index'));
            redirect(array('class'=>'users','method'=>'admin_login'));
        }
    }

    /**
     * Paid user admin's dashboard settings page
     */
    function dashboard_index() {
        $this->pageTitle = 'Dashboard Settings';
        $this->pagelayout = 'dashboard';
    }

    /**
     * Turn on/off the Geotracking of the Worksite users
     */
    function geoTracking() {
        if($this->Session->checkUserSession($this->Session, PAIDUSER)) {
            $this->pagelayout = 'dashboard';
            $this->element = 'settings/geotracking';
            $key = 'toggle-geotracking-status';
            $gid = $this->Session->read('User.gid');
            $where = "name = '$key'";
            $where .= " AND gid = $gid";
            if($this->Request->request['save']) {
                $value = $this->Request->request['value'];
                $this->Database->mysqlInsert('hh_group_settings', array('name', 'value', 'gid'), array($key, $value, $gid), $where);
            }

            $result = $this->Database->mysqlSelect(array('value'),'hh_group_settings',$where,"","","","");
            $rows = $this->Database->mysqlfetchassoc($result);
            if($rows)
                $this->Request->request = $rows[0];
        }else {
            referer(array('class'=>'settings','method'=>'dashboard_index'));
            redirect(array('class'=>'users','method'=>'login'));
        }
    }

    /**
     * Fetch setting of paid user admin dashboard
     *
     * @param string    $key    Setting name
     * @return String   Setting value
     */
    function getSettings($key) {
        $gid = $this->Session->read('User.gid');
        $where = "name = '$key'";
        $where .= " AND gid = $gid";
        $result = $this->Database->mysqlSelect(array('value'),'hh_group_settings',$where,"","","","");
        $row = $this->Database->mysqlfetchobject($result);
        if($row)
            return $row->value;

        return null;
    }

    /**
     * Pass an associative array having format:
     * '<SOURCE CLASS>'=>array(
     '<TARGET CLASS>'=>array('<METHOD>','[...')
     )
     * @return Array
     */
    function modRewrite() {
        $args = array(
                /*   'pages'=>array(
                            'page'=>array('slug')
                        )     */
        );

        return $args;
    }

    /* backup the db OR just a table */
    function backupTables($host = DB_HOST, $user = DB_USER, $pass = DB_PASSWORD, $name = DB_NAME, $tables = '*') {

        $link = mysql_connect($host,$user,$pass);
        mysql_select_db($name,$link);

        //get all of the tables
        if($tables == '*') {
            $tables = array();
            $result = mysql_query('SHOW TABLES');
            while($row = mysql_fetch_row($result)) {
                $tables[] = $row[0];
            }
        }
        else {
            $tables = is_array($tables) ? $tables : explode(',',$tables);
        }

        //cycle through
        foreach($tables as $table) {
            $result = mysql_query('SELECT * FROM '.$table);
            $num_fields = mysql_num_fields($result);

            $return.= 'DROP TABLE '.$table.';';
            $row2 = mysql_fetch_row(mysql_query('SHOW CREATE TABLE '.$table));
            $return.= "\n\n".$row2[1].";\n\n";

            for ($i = 0; $i < $num_fields; $i++) {
                while($row = mysql_fetch_row($result)) {
                    $return.= 'INSERT INTO '.$table.' VALUES(';
                    for($j=0; $j<$num_fields; $j++) {
                        $row[$j] = addslashes($row[$j]);
                        $row[$j] = ereg_replace("\n","\\n",$row[$j]);
                        if (isset($row[$j])) { $return.= '"'.$row[$j].'"' ; } else { $return.= '""'; }
                        if ($j<($num_fields-1)) { $return.= ','; }
                    }
                    $return.= ");\n";
                }
            }
            $return.="\n\n\n";
        }

        //save file
        $handle = fopen('db-backup-'.time().'-'.(md5(implode(',',$tables))).'.sql','w+');
        fwrite($handle,$return);
        fclose($handle);
    }
}
?>