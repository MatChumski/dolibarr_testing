<style type="text/css">
	.item:hover {
		cursor: pointer;
	}
</style>

<?php

include("db.php");

$search_term = $_POST['city'];

$sql = "SELECT * FROM llx_product WHERE label LIKE '%{$search_term}%'";
$result = mysqli_query($conn, $sql) or die("SQL Query Failed.");

$output = "<ul>";

	if(mysqli_num_rows($result) > 0){  
		while($row = mysqli_fetch_assoc($result)){
			$output .= "<li class='item'>{$row['label']}</li>";
		}
  }else{  
  	$output .= "<li>NO existe producto </li>";
  } 
$output .= "</ul>";

echo $output;

?>
