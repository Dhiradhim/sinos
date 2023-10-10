<?php
include ('koneksi.php');
session_start();  
  
if(!$_SESSION['nip'])  
{  
  
    header("Location: login.php");//redirect to login page to secure the welcome page without login access.  
}  


$nama=$_POST['nama'];
$nip=$_POST['nip'];
$pass1=md5($_POST['pass1']);
$pass2=md5($_POST['pass2']);
$jabatan=$_POST['jabatan'];
$aktif=$_POST['aktif'];
if ($aktif==0){
    $x_aktif='Aktif';
} else {
    $x_aktif='Nonaktif';
}

$q="SELECT jabatan FROM jabatan WHERE id=$jabatan";
$sql = mysqli_query ($con, $q);
$row = mysqli_fetch_assoc($sql);    
$x_jabatan = $row['jabatan'];

if ($pass1==$pass2){
    $query = "INSERT into user (nip,id_jabatan,nama,pass,aktif) values ('$nip', '$jabatan', '$nama', '$pass1', '$aktif')";
    $sql=mysqli_query($con, $query);
    echo '<script>alert("User sudah terdaftar dengan detail sebagai berikut\nNama: '.$nama.'\nNIP: '.$nip.'\nJabatan: '.$x_jabatan.'\nStatus: '.$x_aktif.'");</script>';
    echo '<script>window.location.href="daftaruser.php?page=1&count=1"</script>';
    // echo $jabatan;
    // echo $query;
} 
else {
    echo '<script>alert("Password yang anda masukkan tidak sama!");</script>';
    echo '<script>window.history.back()</script>';
}




// echo $sql_kode2;
// echo "<br>";
// echo $huruf;
// echo "<br>";
// echo $query;
?>
