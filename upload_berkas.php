
<?php
session_start();  
include('koneksi.php');

$nip=$_SESSION['nip'];
//ekstensi file
$allowed_file_types = array('.pdf');
$id=$_POST['id_surat'];
$sql = "SELECT no,no_urut,huruf FROM nosur WHERE id='$id'";
$q_no_urut = mysqli_query($con, $sql) or die(mysqli_connect_error());
$row_no_urut = mysqli_fetch_assoc($q_no_urut);
$run = mysqli_num_rows($q_no_urut);
$no = $row_no_urut['no'];
$no_urut = $row_no_urut['no_urut'];
$huruf = $row_no_urut['huruf'];
$file = $_FILES["file"]["name"];
$tahun = date("Y");
$file_ext_kk = substr($file, strripos($file, '.'));

// echo $no_urut;
// echo $sql;
if (in_array($file_ext_kk,$allowed_file_types))
{
							if (is_null($huruf)){
								$file = $tahun."_".$no_urut."_file".$file_ext_kk; 
							} else {
								$file = $tahun."_".$no_urut.$huruf."_file".$file_ext_kk;
							}
							$db_file= "file/" . $file;
							move_uploaded_file($_FILES["file"]["tmp_name"], "file/" . $file);
							
							$query = "UPDATE nosur SET file='$db_file' WHERE id='$id'";
							// echo $query;
							$sql=mysqli_query($con, $query);
							echo "<script type='text/javascript'>alert('File berhasil diupload.');</script>";
							if ($nip=='admin'){
								echo '<script>window.location.href="index.php"</script>';
							} else {
								echo '<script>window.location.href="index.php"</script>';
							}
}
else
{
	// file type error
	$error="Format File yang diizinkan hanya: " . implode(', ',$allowed_file_types);
	echo "<script type='text/javascript'>alert('$error');</script>";
	echo '<script>window.history.back()</script>';		
}
?>