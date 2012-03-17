<?php
class database {
    private $userName = '';
    private $host = '';
    private $password = '';
    private $database = '';
    private $connection = null;
    private $transaction = false;
    private $transactionQueries = '';
    public  $limit = 10;
    private $insertedId = 0;
    private $cFields = array();
    private $statement = 'SELECT';
    private $startTime = 0;
    private $endTime = 0;
    private $idEncryptionKey = null;
    public static $queries = array();
    public static $queryIndex = 0;

    public function __construct() {
        $this->userName = DB_USER;
        $this->password = DB_PASSWORD;
        $this->host = DB_HOST;
        $this->database = DB_NAME;
        $this->connection = mysql_connect($this->host,$this->userName,$this->password);
        $success = mysql_select_db($this->database,$this->connection);
        if(defined('ID_ENCRYPTION_KEY'))
            $this->idEncryptionKey = ID_ENCRYPTION_KEY;
        return $success;
    }

    public function mysqlRawquery($sql) {
        $resultSet = mysql_query($sql);
        if(Configure::read('debug')) {
            switch($this->statement) {
                case 'INSERT':
                case 'DELETE':
                case 'UPDATE':
                case 'REPLACE':
                    $this->startTime = $this->microtime_float();
            }
            self::$queries[self::$queryIndex]['sql'] = $sql;
            if($resultSet === false) {
                self::$queries[self::$queryIndex]['error'] = mysql_error($this->connection);
                self::$queries[self::$queryIndex]['rows'] = '0';
                self::$queryIndex++;
            }
            else {
                self::$queries[self::$queryIndex]['error'] = '';
                switch($this->statement) {
                    case 'SELECT':
                        self::$queries[self::$queryIndex]['rows'] = mysql_num_rows($resultSet);
                        //self::$queryIndex++;
                        break;
                    case 'INSERT':
                    case 'DELETE':
                    case 'UPDATE':
                    case 'REPLACE':
                        $this->endTime = $this->microtime_float();
                        self::$queries[self::$queryIndex]['time'] = sprintf('%0.4f', ($this->endTime - $this->startTime));
                        self::$queries[self::$queryIndex]['rows'] = mysql_affected_rows($this->connection);
                        self::$queryIndex++;
                }
            }

        //pr(self::$queries);
        //echo self::$queryIndex.'--';
        }
        return $resultSet;
    }

    function mysqlQuery($sql) {
        $this->statement = '';
        return $this->mysqlRawquery($sql);
    }

    public function mysqlSelect($fields, $tables, $where = "", $order_by = "", $group_by = "", $having = "", $limit = "") {
        $fields = array_merge($fields, array('id'));
        if(is_array($fields))
            $fields=$this->updatefields($fields);
        $sql = "SELECT $fields FROM $tables ";
        if (!empty($where))
            $sql.=" WHERE $where ";
        if (!empty($group_by))
            $sql.=" GROUP BY $group_by ";
        if (!empty($order_by))
            $sql.=" ORDER BY $order_by ";
        if (!empty($having))
            $sql.=" HAVING $having ";
        if (!empty($limit))
            $sql.=" LIMIT $limit ";
        if($this->transaction)
            $this->transactionQueries .= $sql . '; ';
        else
            return $this->mysqlRawquery($sql);
    }

    //Function to create pagination query
    //_____________________________________________________________

    public function paginatequery($fields, $tables, $where = "", $order_by = "", $group_by = "", $having = "", $page = 1, $limit=null, $sql="" ) {

        if(is_array($fields))
            $fields=$this->updatefields($fields);
        if(empty($sql)) {
            $sql = "SELECT $fields FROM $tables ";
            if (!empty($where))
                $sql.=" WHERE $where ";
            if (!empty($group_by))
                $sql.=" GROUP BY $group_by ";
            if (!empty($having))
                $sql.=" HAVING $having ";
            if (!empty($order_by))
                $sql.=" ORDER BY $order_by ";
        }

        $totalrecords=$this->mysqlnumrows($this->mysqlRawquery($sql));
        if($limit == null) {
            $limit = $totalrecords;
        }
        $totalpages=ceil($totalrecords/$limit);
        if($page > $totalpages) {
            $page = $totalpages;
        }
        $start = abs($limit * ($page-1));
        $pages=$this->create_pagination_array($totalpages,$totalrecords,$page);
        $sql.=" LIMIT ".$start.",". $limit;
		//var_dump($sql); exit;
        $data=array('recordset'=> $this->mysqlRawquery($sql),'pages'=>array($pages, $totalpages),'totalrecords'=>$totalrecords,'totalpages'=>$totalpages);
        return $data;
    }

    function slug($slugvalue, $table, $id=null, $no=0) {
        $tmp='';
        $slugvalue = createSlug($slugvalue);
        if(!$tmp)
            $tmp = $slugvalue;
        if($id == null) {
            $checkSlug = $this->mysqlSelect(array('slug'),$table,"slug LIKE '".$slugvalue."%'","","","","");
        }else {
            $checkSlug = $this->mysqlSelect(array('slug'),$table,"slug LIKE '".$slugvalue."%' and id != '".$id."'","","","","");
        }
        if($this->mysqlnumrows($checkSlug) > 0) {
            $no = $no + 1;
            $tmp = $tmp.$this->mysqlnumrows($checkSlug);
            $slugvalue = $tmp;

        }
        return $tmp;
    }

    function createUniqueSlug($table, $fields, $values, $id = null) {
        global $slugs;
        $slug_exists = false;
        $this->statement = 'ALTER';
        if(isset($slugs)) {
            if(array_key_exists($table, $slugs) && trim($slugs[$table]) != '') {
                $sql = 'SHOW FIELDS FROM '.$table;
                $filds = $this->mysqlfetchobjects($this->mysqlRawquery($sql));
                if($filds) {
                    foreach($filds as $fild) {
                        if($fild->Field == 'slug') {
                            $slug_exists = true;
                            break;
                        }
                    }
                    // If <slug> field does not exist in the target table, insert a slug field into the table
                    if(!$slug_exists) {
                        $this->mysqlRawquery("ALTER TABLE `$table` ADD `slug` VARCHAR( 255 ) NOT NULL");
                    }
                    $fieldIndex = array_keys($fields, $slugs[$table]);
                    $slugValue = '';
                    if($fieldIndex) {
                        $slugValue = $this->slug($values[$fieldIndex[0]], $table, $id);

                        // Update field array with slug element
                        if(!in_array('slug', $fields)) {
                            $fields[] = 'slug';
                            $values[] = $slugValue;
                        }
                    }
                }
            }
        }

        return array('fields'=>$fields, 'values'=>$values);
    }

    //Function to perform insertion
    //______________________________________________________________
    public function mysqlInsert($table, $fields, $values, $where = '') {
        $res = $this->createUniqueSlug($table, $fields, $values);
        $fields = $res['fields'];
        $values = $res['values'];
        $this->insertedId = 0;
        $this->statement = 'INSERT';
        $sql = "INSERT INTO `$table` ";
        $temp1 = implode("`,`", $fields);
        $values = $this->beforeInsert($values);
        $temp2 = implode("','", $values);
        $sql .= "(`$temp1`) VALUES ('$temp2')";
        if($where) {
            $result = $this->mysqlSelect(array('id'), $table, $where);
            $row = $this->mysqlfetchobject($result);
            if($row) {
                $this->insertedId = $row->id;
                $this->mysqlUpdate($table, $fields, $values, " id = '{$row->id}'");
            }else {
                if($this->transaction)
                    $this->transactionQueries .= $sql . '; ';
                else
                    return $this->mysqlRawquery($sql);
            }
        }else {
            if($this->transaction)
                $this->transactionQueries .= $sql . '; ';
            else
                return $this->mysqlRawquery($sql);
        }
    }

    public function mysqlInsertAll($table, $fields, $values) {
        $this->statement = 'INSERT';
        $sql = "INSERT INTO `$table` ";
        $temp1 = implode("`,`", $fields);
        $sql .= "(`$temp1`) VALUES ";
        $size = count($values) - 1;
        $index = 0;
        foreach($values as $key=>$value) {
            $value = $this->beforeInsert($value);
            $temp2 = implode("','", $value);

            if($size == $index++)
                $delm = ' ';
            else
                $delm = ', ';

            $sql .= "('$temp2')" . $delm;
        }
        if($this->transaction)
            $this->transactionQueries .= $sql . '; ';
        else
            return $this->mysqlRawquery($sql);
    }

    //Function to perform Updation
    //______________________________________________________________

    public function mysqlUpdate($table, $fields, $values, $where=null) {
        if ($where != "") {
            $result = $this->mysqlSelect(array('id'), $table, $where);
            $row = $this->mysqlfetchobject($result);
            if($row->id) {
                $res = $this->createUniqueSlug($table, $fields, $values, $row->id);
                $fields = $res['fields'];
                $values = $res['values'];
            }
            $this->statement = 'UPDATE';
            $values = $this->beforeInsert($values);
            $sql = "UPDATE `$table` SET ";
            $index = 0;
            foreach($values as $key=>$value) {
                $sql .= "`".$fields[$index]."`='".$value."'".(($index==count($fields)-1)?"":" , ");
                $index++;
            }
            $sql .= " WHERE ($where)";
            if($this->transaction)
                $this->transactionQueries .= $sql . '; ';
            else
                return $this->mysqlRawquery($sql);

        }
    }

    //Function to perform deletion
    //______________________________________________________________
    public function mysqlDelete($table, $where, $deleteRefInt = false, $fkey = null, $exclude = array()) {
        $this->statement = 'DELETE';
        $key = 0;
        $rows = array();
        if ($where != "") {
        // If to remove Referential Integrity
            if($deleteRefInt) {
                $result = $this->mysqlSelect(array('id'), $table, $where);
                $row = $this->mysqlfetchobject($result);
                if($row) {
                    $key = $row->id;
                }
            }
            $sql = "DELETE FROM `$table` WHERE ($where)";
            if($this->transaction)
                $this->transactionQueries .= $sql . '; ';
            else {
                $this->mysqlRawquery($sql);
                // If to remove Referential Integrity
                if($key && $fkey) {
                    $sql = "SELECT TABLE_NAME
                            FROM INFORMATION_SCHEMA.COLUMNS
                            WHERE column_name LIKE '$fkey'";
                    $result = $this->mysqlRawquery($sql);
                    while($data=mysql_fetch_array($result)) {
                        $rows[]=$data['TABLE_NAME'];
                    }
                    if($rows) {
                        $tables = array_diff($rows, $exclude);
                        if($tables) {
                            $where = "$fkey = '$key'";
                            foreach($tables as $table) {
                                $sql = "DELETE FROM `$table` WHERE ($where)";
                                $this->mysqlRawquery($sql);
                            }
                        }
                    }
                }
                return;
            }
        }
    }

    //Function to count result rows
    //______________________________________________________________

    public function mysqlnumrows($recordset) {
        if($recordset !== false) {
            return mysql_num_rows($recordset);
        }
        return 0;
    }

    //Function to fetch rows
    //______________________________________________________________

    public function mysqlfetchrows($recordset) {
        $rows=array();
        if($recordset !== false) {
            $this->startTime = $this->microtime_float();
            while($data=mysql_fetch_array($recordset)) {
                $rows[]=$data;
            }
            $this->endTime = $this->microtime_float();
            self::$queries[self::$queryIndex]['time'] = sprintf('%0.4f', ($this->endTime - $this->startTime));
            self::$queryIndex++;

            $rows = $this->beforeDisplay($rows);
        }
        return $rows;
    }

    public function mysqlfetchobject($recordset) {
        $rows=array();
        if($recordset !== false) {

            $this->startTime = $this->microtime_float();
            $data=mysql_fetch_object($recordset);
            $this->endTime = $this->microtime_float();
            self::$queries[self::$queryIndex]['time'] = sprintf('%0.4f', ($this->endTime - $this->startTime));
            self::$queryIndex++;
            if($data)
                $rows[]=$data;
            $rows = $this->beforeDisplayObject($rows);
            return $data;
        }
        return $rows;
    }

    public function mysqlfetchobjects($recordset) {
        $rows=array();
        if($recordset !== false) {
            $this->startTime = $this->microtime_float();
            while($data=mysql_fetch_object($recordset)) {
                $rows[]=$data;
            }
            $this->endTime = $this->microtime_float();
            self::$queries[self::$queryIndex]['time'] = sprintf('%0.4f', ($this->endTime - $this->startTime));
            self::$queryIndex++;
            $rows = $this->beforeDisplayObject($rows);
        }
        return $rows;
    }

    public function mysqlfetchlist($recordset, $key='id', $contiue = false) {
        $rows=array();
        if($recordset !== false) {
            $this->startTime = $this->microtime_float();
            while($data=mysql_fetch_assoc($recordset)) {
                $id = $data[$key];
                unset($data['id']);
                unset($data[$key]);
                if(!$contiue)
                    $rows[$id] = end($data);
                else
                    $rows[] = end($data);
            }
            $this->endTime = $this->microtime_float();
            self::$queries[self::$queryIndex]['time'] = sprintf('%0.4f', ($this->endTime - $this->startTime));
            self::$queryIndex++;
            $rows = $this->beforeDisplay($rows);
        }
        return $rows;
    }

    public function mysqlfetchassoc($recordset, $encryptedDBKey = null, $salt = '') {
        $rows=array();
        if($recordset !== false) {
            $this->startTime = $this->microtime_float();
            while($data=mysql_fetch_assoc($recordset)) {
                // To encrypt a field value
                if($encryptedDBKey)
                    $data[$encryptedDBKey] = $this->alphaID($data[$encryptedDBKey], false, $salt);
                $rows[]=$data;
            }
            $this->endTime = $this->microtime_float();
            self::$queries[self::$queryIndex]['time'] = sprintf('%0.4f', ($this->endTime - $this->startTime));
            self::$queryIndex++;
            $rows = $this->beforeDisplay($rows);
        }
        return $rows;
    }

    public function escape($field) {
        if(!isset($field)) {
            return '';
        }
        return mysql_real_escape_string($field);
	   //return $field;
    }

    //Private Functions
    //______________________________________________________________

    //Function to update fields
    //______________________________________________________________

    private function updatefields($fields) {
        foreach($fields as $value) {
            $temp[] = $value;
        }
        return implode(',',$temp);
    }
    //Function to create pagination array
    //______________________________________________________________

    private function create_pagination_array($totalpages,$totalrecords,$page) {
        $start=$end=0;
        if($totalpages < $page) {
            $page = $totalpages;
        }
        if($page > 8) {
            if($totalpages > $page+4) {
                $end = $page+4;
                $start = $page-4;
            }else {
                $end = $totalpages;
                $start=$page-(4 + (4-($totalpages - $page)));
            }
        }else {
            $start=1;
            if($page+(9-$page) > $totalpages) {
                $end=$totalpages;
            }else {
                $end=$page+(9-$page);
            }
        }
        $data=array();
        for($i=$start;$i<=$end;$i++) {
            $data[]=$i;
        }

        return $data;
    }
    //______________________________________________________________
    function lastInsertId() {
        if($this->insertedId)
            return $this->insertedId;

        return mysql_insert_id();
    }

    function beforeInsert($values) {
        foreach($values as $key => $value) {
            if(is_array($value)) {
                $values[$key] = $this->beforeInsert($values[$key]);// recursion for the array values
            }else {
                $value = stripslashes($value);
                $values[$key] = addslashes(trim($value));//additon of the slashes to the added DB values for special symbols
            }
        }

        return $values;
    }

    function beforeDisplay($values) {
        if(count($values)>0) {
            foreach($values as $key => $value) {
                if(is_array($value)) {
                    $values[$key] = $this->beforeDisplay($values[$key]);
                }else {
                    $values[$key] = stripslashes($value);
                }
            }
        }

        return $values;
    }

    function beforeDisplayObject($values) {
        if(count($values)>0) {
            foreach($values as $key => $value) {
                foreach($value as $key1 => $val) {
                    if(is_array($val)) {
                        $values[$key]->$key1 = $this->beforeDisplay($values[$key]->$key1);
                    }else {
                        $values[$key]->$key1 = stripslashes($val);
                    }

                }
            }
        }

        return $values;
    }

               /**************** TRANSACTIONS ******************/
    function begin() {
        $this->transactionQueries .= ' BEGIN; ';
        $this->transaction = true;
    }

    function commit() {
        $this->transactionQueries .= ' COMMIT; ';
        $this->transaction = false;
        return $this->mysqlRawquery($this->transactionQueries);
    }

    function rollback() {
        $this->transactionQueries .= ' ROLLBACK; ';
        $this->transaction = false;
        return $this->mysqlRawquery($this->transactionQueries);
    }

    function customFields($fields = array()) {
        if(is_array($fields))
            $this->cFields = $fields;
    }

    /**
     * Association
     */
    function assoc($tables, $fkeys, $where = '1', $order_by = '', $group_by = '', $having = '', $limit = LIMIT, $returnQuery = false) {
        $joins = array();
        $assoc = array();
        $tmpTables = array();
        $keys = array();
        $aliass = array();
        $tmpFkeys = array();
        $als = '';
        foreach($tables as $table) {
            $table = explode(' ',$table);		
            if(strtoupper($table[0]) == 'RIGHT' || strtoupper($table[0]) == 'LEFT') {
                $tmpTables[] = $table[1];
                $joins[] = strtoupper(trim($table[0]));
            }
            else {
                if(isset($table[1]) && (strtoupper($table[1]) == 'RIGHT' || strtoupper($table[1]) == 'LEFT')) {
                    $joins[] = strtoupper(trim($table[1]));
                }else {
                    $joins[] = 'INNER';
                }
                $tmpTables[] = $table[0];
            }
        }
        foreach($fkeys as $fk) {
            $tmpFkeys = array_merge($tmpFkeys, $fk);
			
        }
        foreach($tmpFkeys as $fk) {
            $table = explode('.',$fk);
            $tmpFkeys[] = $table[0];
        }

        $fields = $this->assocParseTableFields($tmpTables);
        $count = count($fields);
        $index = 1;
        foreach($fields as $key=>$field) {
            if($index++ == $count)
                $delm = ' ';
            else
                $delm = ', ';
            $als .= $field[0] . $delm;
            // Primary Keys
            $keys[$this->toAlias($tmpTables[$key])] = $field[$this->toAlias($tmpTables[$key])];
        }

        if($tmpTables) {
            $sql = '';
            $flag = false;
            foreach($tmpTables as $key=>$table) {

                if(!$key) {
                    $sql = $als . "\n";
                    $sql .=  'FROM ' . $table . ' AS ' . $this->toAlias($table) . "\n";
                    $aliass[] = $this->toAlias($table);
                    if(!empty($fkeys[$key])) {
                        $flag = true;
                    }
                }
                if(!empty($fkeys[$key])) {
                    foreach($fkeys[$key] as $k=>$fkey) {
                        $conj =  ' AND ';
                        if(!$flag) {
                            $flag = true;
                            $sql .= $joins[$key] . ' JOIN ';
                            $sql .= $table . ' AS ' . $this->toAlias($table);
                            $aliass[] = $this->toAlias($table);
                            $conj =  ' ON ';
                        }
                        else {
                            if(!in_array($this->toAlias($fkey), $aliass)) {
                                $sql .= $joins[$key] . ' JOIN ';
                                $sql .= $this->aliasTo($fkey) . ' AS ' . $this->toAlias($fkey);
                                $aliass[] = $this->toAlias($fkey);
                                $conj =  ' ON ';
                            }elseif(!in_array($this->toAlias($table), $aliass)) {
                                $sql .= $joins[$key] . ' JOIN ';
                                $sql .= $table . ' AS ' . $this->toAlias($table);
                                $aliass[] = $this->toAlias($table);
                                $conj =  ' ON ';
                            }
                        }

                        $sql .= $conj . $this->toAlias($fkey) . '.' . $keys[$this->toAlias($fkey)] . ' = ' . $this->toAlias($table) . '.' . $this->fKey($fkey) . "\n";
                    }
                }
            }
        }
        // pr($aliass);
        if (!empty($where)) {
            $sql .= " WHERE $where " . "\n";
        }
        if (!empty($group_by))
            $sql .= " GROUP BY `$group_by` " . "\n";
        if (!empty($having))
            $sql .= " HAVING $having " . "\n";
        if (!empty($order_by)) {
            $order_by = explode(',', $order_by);
            $sql .= " ORDER BY";
            $c = count($order_by) - 1;
            foreach($order_by as $key=>$o) {
                if($c == $key) {
                    $delm = ' ';
                }else {
                    $delm = ', ';
                }
                $o = trim($o);
                $ord =  explode(' ', $o);
                if(count($ord) > 1)
                    $sql .= " `$ord[0]` " . $ord[1] . $delm;
                else
                    $sql .= ' '.$ord[0];
            }
            $sql .= "\n";
        }
        if (!empty($limit))
            $sql .= " LIMIT $limit " . "\n";

        if($this->cFields) {
            $index = 1;
            $count = count($this->cFields);
            $tempStr = '';
            foreach($this->cFields as $key=>$field) {
                $tempStr .= $field . ', ';
            }
            unset($this->cFields);
            $sql = $tempStr . $sql;
        }
        $sql = 'SELECT ' . $sql;

        if($returnQuery)
            return $sql;
        return $this->mysqlRawquery($sql);
    }

    private function assocParseTableFields($tables) {
        $fieldString = array();      
        foreach($tables as $key=>$table) {
            $fieldAlias = preg_replace('/\_([a-z])/e', "strtoupper('$1')", $table);
            $sql = 'SHOW FIELDS FROM '.$table;
            $fields = $this->mysqlfetchobjects($this->mysqlRawquery($sql));
            if($fields) {
                $index = 1;
                $tempStr = '';
                $fieldIndex = '';
                $count = count($fields);
                foreach($fields as $field) {
                    if($index++ == $count)
                        $delm = ' ';
                    else
                        $delm = ', ';

                    $tempStr .= $fieldAlias . '.' . $field->Field . ' AS ' . '`' . $fieldAlias . '.' . $field->Field . '`' . $delm;

                    if($field->Key == 'PRI')
                        $fieldIndex = $field->Field;
                }                
                $fieldString[$key][] = $tempStr;
                $fieldString[$key][$this->toAlias($table)] = $fieldIndex;
            }
        }

        return $fieldString;
    }

    private function toAlias($str) {
        $str = explode('.', $str);
        return preg_replace('/\_([a-z])/e', "strtoupper('$1')", $str[0]); // carUsers
    }

    private function aliasTo($str) {
        $str = explode('.', $str);
        return preg_replace('/([A-Z])/e', "strtolower('_$1')", $str[0]);  // car_users
    }

    private function fKey($fkey) {
        $fkey = explode('.', $fkey);
        return $fkey[1];
    }

    public function __call($method, $args = array()) {
        $tables = array();
        $keys = array();
        $fkeys = array();
        $where = '1';
        $order_by = '';
        $group_by = '';
        $having = '';
        $page = 1;
        $limit = LIMIT;

        if($method === 'paginate') {
            if(isset($args[0]))
                $tables = $args[0];
            if(isset($args[1]))
                $fkeys = $args[1];
            if(isset($args[2]))
                $where = $args[2];
            if(isset($args[3]))
                $order_by = $args[3];
            if(isset($args[4]))
                $group_by = $args[4];
            if(isset($args[5]))
                $having = $args[5];
            if(isset($args[6]))
                $page = $args[6];
            if(isset($args[7]))
                $limit = $args[7];

            $sql = $this->assoc($tables, $fkeys, $where, $order_by, $group_by, $having , '', true);
            return $this->paginatequery('', '', '', '', '', '', $page, $limit, $sql) ;
        }
    }

    private function microtime_float() {
        list($usec, $sec) = explode(" ", microtime());
        $sec = $sec - strtotime('NOW');
        return ((float)$usec + (float)$sec);
    }

    public function getdbtables() {
        $result = mysql_list_tables($this->database);
        $tables = $this->mysqlfetchobjects($result);
        $dbtables = array();
        foreach($tables as $table) {
            $dbtables[$table->Tables_in_illuminz_HH] = $table->Tables_in_illuminz_HH;
        }
        return $dbtables;
    }

    public function gettablefields($table) {
        $result = mysql_list_fields($this->database, $table);
        $columns = mysql_num_fields($result);
        $dbfields = array();
        for ($i = 0; $i < $columns; $i++) {
            $dbfields[mysql_field_name($result, $i)] = mysql_field_name($result, $i);
        }
        return $dbfields;
    }

    /**
     * Translates a number to a short alhanumeric version
     *
     * Translated any number up to 9007199254740992
     * to a shorter version in letters e.g.:
     * 9007199254740989 --> PpQXn7COf
     *
     * specifiying the second argument true, it will
     * translate back e.g.:
     * PpQXn7COf --> 9007199254740989
     *
     * @param mixed   $in    String or long input to translate
     * @param boolean $to_num  Reverses translation when true
     * @param mixed   $pad_up  Number or boolean padds the result up to a specified length
     * @param string  $passKey Supplying a password makes it harder to calculate the original ID
     *
     * @return mixed string or long
     */
    public function alphaID($in, $to_num = false, $salt = '', $pad_up = 7) {
        $passKey = $this->idEncryptionKey . ':' . $salt;
        $index = "0123456789";
        if ($passKey !== null) {
        // Although this function's purpose is to just make the
        // ID short - and not so much secure,
        // with this patch by Simon Franz (http://blog.snaky.org/)
        // you can optionally supply a password to make it harder
        // to calculate the corresponding numeric ID

            for ($n = 0; $n<strlen($index); $n++) {
                $i[] = substr( $index,$n ,1);
            }

            $passhash = hash('sha256',$passKey);
            $passhash = (strlen($passhash) < strlen($index))
                ? hash('sha512',$passKey)
                : $passhash;

            for ($n=0; $n < strlen($index); $n++) {
                $p[] =  substr($passhash, $n ,1);
            }

            array_multisort($p,  SORT_DESC, $i);
            $index = implode($i);
        }

        $base  = strlen($index);

        if ($to_num) {
        // Digital number  <<--  alphabet letter code
            $in  = strrev($in);
            $out = 0;
            $len = strlen($in) - 1;
            for ($t = 0; $t <= $len; $t++) {
                $bcpow = bcpow($base, $len - $t);
                $out   = $out + strpos($index, substr($in, $t, 1)) * $bcpow;
            }

            if (is_numeric($pad_up)) {
                $pad_up--;
                if ($pad_up > 0) {
                    $out -= pow($base, $pad_up);
                }
            }
            $out = sprintf('%F', $out);
            $out = substr($out, 0, strpos($out, '.'));
        } else {
        // Digital number  -->>  alphabet letter code
            if (is_numeric($pad_up)) {
                $pad_up--;
                if ($pad_up > 0) {
                    $in += pow($base, $pad_up);
                }
            }

            $out = "";
            for ($t = floor(log($in, $base)); $t >= 0; $t--) {
                $bcp = bcpow($base, $t);
                $a   = floor($in / $bcp) % $base;
                $out = $out . substr($index, $a, 1);
                $in  = $in - ($a * $bcp);
            }
            $out = strrev($out); // reverse
        }

        return $out;
    }
}
?>