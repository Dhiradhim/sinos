<?php
session_start();  
include('koneksi.php');

//ekstensi file
$allowed_file_types = array('.pdf');
$id=$_POST['id'];
$file = $_FILES["file"]["name"];
$file_ext_kk = substr($file, strripos($file, '.'));
$akte_kelahiran = $_FILES["akte_kelahiran"]["name"];

if (in_array($file_ext_kk,$allowed_file_types))
{
	
							$file = $id."_file".$file_ext_kk;
							$db_file= "file/" . $file;
							move_uploaded_file($_FILES["file"]["tmp_name"], "file/" . $file);
							
							$query = "UPDATE nosur SET file='$db_file' WHERE id='$id'";
							$sql=mysqli_query($con, $query);
							echo "<script type='text/javascript'>alert('File berhasil diupload.');</script>";
							echo '<script>window.location.href="daftarnosur.php"</script>';
}
else
{
	// file type error
	$error="Format File yang diizinkan hanya: " . implode(', ',$allowed_file_types);
	echo "<script type='text/javascript'>alert('$error');</script>";
	echo '<script>window.history.back()</script>';		
}
?>