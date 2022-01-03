<?php
session_start();  
include('koneksi.php');
$nip=$_SESSION['nip'];
$x=mysqli_query($con, "SELECT pass FROM user WHERE nip='$nip'") or die(mysqli_connect_error());
$xx=mysqli_fetch_assoc($x);
if(isset($_POST['simpan'])){
$old=md5($_POST['old']);
$new=md5($_POST['new']);
$rep=md5($_POST['rep']);

if ($old !== $xx['pass'])
{
	echo '<script type="text/javascript">alert("Password Lama Salah");</script>';
	echo '<script>window.location.href="changepassword.php"</script>';
}else if ($new !== $rep)
{
	echo '<script type="text/javascript">alert("Password Baru Tidak Cocok");</script>';
	echo '<script>window.location.href="changepassword.php"</script>';
}else{
	$query = "UPDATE user SET pass='$new' WHERE nip='$nip'";
	$update = mysqli_query($con, $query);
	echo '<script type="text/javascript">alert("Password Berhasil Diganti");</script>';
	echo '<script>window.location.href="logout.php"</script>';
}}?>