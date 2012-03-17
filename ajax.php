<?php 
$a= $_GET['a']; 
define('DB_ACCESS', true);
define('DB_USER', 'wwwmydr_apart');
define('DB_PASSWORD', 'wwwmydr_apart@12');
define('DB_HOST', 'localhost');
define('DB_NAME', 'wwwmydr_apartments');
$host = DB_HOST; 
$user = DB_USER; 
$pass = DB_PASSWORD; 
$name = DB_NAME;
        $link = mysql_connect($host,$user,$pass);
        mysql_select_db($name,$link); 
$result = mysql_query("SELECT id FROM city WHERE name='{$a}'");
while($data=mysql_fetch_object($result)) {
                $rows[]=$data;
            }
$a=$rows[0]->id;
$result = mysql_query("SELECT name FROM city WHERE parent_id='{$a}'");
while($data=mysql_fetch_object($result)) {
                $rows[]=$data;
            }
			foreach($rows as $row){?> 
			<option value="<?php echo $row->name; ?>"><?php echo $row->name; ?></option>
                <?php } ; ?>