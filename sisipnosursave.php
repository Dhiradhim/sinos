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

$nip_x=$_POST['nip'];
$nip=$_SESSION['nip'];
$kd = $_POST['kd'];
$kd1 = $_POST['kd1'];
$kd2 = $_POST['kd2'];
$tanggal = $_POST['tanggal'];
$xtanggal=explode('-',$tanggal);
$tahun=$xtanggal[0];
$bul=$xtanggal[1];
$hal = $_POST['hal'];
$sk = $_POST['sk'];

$tgl_hari_ini =date('Y-m-d');
$tgl_kemarin = date('Y-m-d', strtotime("-1 day", strtotime(date('Y-m-d'))));

$sql_kode= "SELECT huruf, no_urut, tanggal as tgl FROM nosur WHERE tanggal<='$tgl_hari_ini' ORDER BY no_urut DESC, id DESC limit 1";
$query_kode= mysqli_query($con, $sql_kode);
$row_kode = mysqli_fetch_assoc($query_kode);
$no_urut = $row_kode['no_urut'];
$huruf = $row_kode['huruf'];
$xx = $row_kode['tgl'];

$sql_kode2= "SELECT huruf, no_urut, tanggal as tgl FROM nosur WHERE tanggal<='$tanggal' ORDER BY no_urut DESC, id DESC limit 1";
$query_kode2= mysqli_query($con, $sql_kode2);
$row_kode2 = mysqli_fetch_assoc($query_kode2);
$no_urut2 = $row_kode2['no_urut'];
$huruf2 = $row_kode2['huruf'];
// echo $sql_kode2;
// echo $sql_kode;
// echo $xx;

if ($xx==$tanggal){
    $no_urut = $no_urut+1;
}

else if (preg_match("/[A-Z]/", $huruf2))
{
	$huruf = substr($huruf2, -1);
	$huruf = chr(ord($huruf) + 1);
    $no_urut=$no_urut2.$huruf;
}
else
{
    $sql_kode= "SELECT no_urut, tanggal as tgl FROM nosur WHERE tanggal<='$tanggal' ORDER BY no_urut DESC, id DESC limit 1";
    $query_kode= mysqli_query($con, $sql_kode);
    $row_kode = mysqli_fetch_assoc($query_kode);
    $no_urut = $row_kode['no_urut'];
	$no_urut = $no_urut."A";
    $huruf='A';
}

if ($kd2 == "-")
{
	$kode = $kd.".".$kd1;
}
else 
{
	$kode = $kd.".".$kd1.".".$kd2;
}

$bulan = getRomawi($bul);

if ($sk == "1")
{
	$no = "W23-A1/".$no_urut."/".$kode."/SK/".$bulan."/".$tahun;
}
else
{
	$no = "W23-A1/".$no_urut."/".$kode."/".$bulan."/".$tahun;
}
$query = "INSERT into nosur (no,no_urut,huruf,nip,tanggal,hal) values ('$no', '$no_urut', '$huruf', '$nip_x', '$tanggal', '$hal')";
$sql=mysqli_query($con, $query);
if ($nip=='admin'){
    echo '<script>window.location.href="daftarnosurall.php?page=1&count=1"</script>';
} else {
    echo '<script>window.location.href="daftarnosur.php?page=1&count=1"</script>';
}
// echo $sql_kode2;
// echo "<br>";
// echo $huruf;
// echo "<br>";
// echo $query;
?>
