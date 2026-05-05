<?php
//$con=mysqli_connect("localhost","root","pakupang123!");
//mysqli_select_db ($con, "sinos");
$con=mysqli_connect("localhost","root","","sinos");

if (mysqli_connect_errno()) {
  echo "Failed to connect to MySQL: " . mysqli_connect_error();
  exit;
}
?>
