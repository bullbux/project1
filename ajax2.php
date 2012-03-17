<?php


if (isset($_GET['a']) && trim($_GET['a']))
	$a = trim($_GET['a']);
else
	exit();

require_once ('constants.php');

$rows = array();
$db = mysql_connect(DB_HOST, DB_USER, DB_PASSWORD);
mysql_select_db(DB_NAME, $db);

$result = mysql_query("SELECT name FROM city WHERE parent_id = (SELECT id FROM city WHERE name='{$a}')");
while ($data = mysql_fetch_object($result)) {
	$rows[] = $data;
}
$i = 0;
foreach ($rows as $row) {
?>
	<div class="city_check">
		<label><input type="checkbox" name="hood[]" value="<?php echo $row->name; ?>"> <?php echo $row->name; ?></label>
	</div>
<?php
	if ($i >= 3) {
		echo '<div class="clear"></div>';
		$i = 0;
	} else
		$i++;
}

mysql_close($db);