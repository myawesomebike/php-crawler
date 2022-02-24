<?php

require_once('be.php');

echo '<pre>';

$con = connectToDB();
$sql = "SELECT * FROM `error_log` ORDER BY `timestamp` DESC";

$result = mysql_query($sql);
while($row = mysql_fetch_array($result)) {

	echo date('m/d/y h:m:s',$row['timestamp'])."\t".$row['crawl_url']."\t".$row['error_message']."\n";


}
?>