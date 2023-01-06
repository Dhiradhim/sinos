<?php
include ('koneksi.php');
session_start();  
  
if(!$_SESSION['nip'])  
{  
  
    header("Location: login.php");//redirect to login page to secure the welcome page without login access.  
}  

function getRomawi($bln){
                switch ($bln){
                    case 1: 
                        return "I";
                        break;
                    case 2:
                        return "II";
                        break;
                    case 3:
                        return "III";
                        break;
                    case 4:
                        return "IV";
                        break;
                    case 5:
                        return "V";
                        break;
                    case 6:
                        return "VI";
                        break;
                    case 7:
                        return "VII";
                        break;
                    case 8:
                        return "VIII";
                        break;
                    case 9:
                        return "IX";
                        break;
                    case 10:
                        return "X";
                        break;
                    case 11:
                        return "XI";
                        break;
                    case 12:
                        return "XII";
                        break;
                }
}

$xno = mysqli_query($con, "SELECT no_urut FROM nosur ORDER BY no_urut DESC LIMIT 1");
$hasil = mysqli_fetch_assoc($xno);

$nip=$_SESSION['nip'];
$nip_x=$_POST['nip'];
$no_urut = $hasil['no_urut']+1;
$kd= $_POST['kd'];
$kd1= $_POST['kd1'];
$kd2= $_POST['kd2'];
$sk= $_POST['sk'];

if ($kd2 == "-")
{
	$kode = $kd.".".$kd1;
}
else
{
	$kode = $kd.".".$kd1.".".$kd2;
}

$bul = date('n');
$bulan = getRomawi($bul);
$tahun = date('Y');

if ($sk == "1")
{
	$no = "W23-A1/".$no_urut."/".$kode."/SK/".$bulan."/".$tahun;
}
else
{
	$no = "W23-A1/".$no_urut."/".$kode."/".$bulan."/".$tahun;
}

$hal = $_POST['hal'];
$tanggal = date('Y-m-d');
// echo $no;
$query = "INSERT into nosur (no,no_urut,nip,tanggal,hal) values ('$no', '$no_urut', '$nip_x', '$tanggal', '$hal')";
$sql=mysqli_query($con, $query);

if ($nip=='admin'){
echo '<script>window.location.href="daftarnosurall.php?page=1&count=1"</script>';
}
else {
    echo '<script>window.location.href="daftarnosur.php?page=1&count=1"</script>';
}
?>
