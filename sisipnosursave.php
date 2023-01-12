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

$tgl_kemarin    =date('Y-m-d', strtotime("-1 day", strtotime(date('Y-m-d'))));

$query_kode= mysqli_query($con, "SELECT huruf, no_urut, tanggal FROM nosur WHERE tanggal<='$tanggal' ORDER BY no_urut DESC limit 1");
$row_kode = mysqli_fetch_assoc($query_kode);
$no_urut = $row_kode['no_urut'];
$huruf = $row_kode['huruf'];
$tanggal_x = $row_kode['tanggal'];

if ($tanggal_x == $tgl_kemarin){
    $no_urut = $no_urut+1;
}

else if (preg_match("/[A-Z]/", $huruf))
{
	$huruf = substr($huruf, -1);
	$huruf = chr(ord($huruf) + 1);
}
else
{
	$no_urut = $no_urut."A";
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
	$no = "W23-A1/".$no_urut.$huruf."/".$kode."/SK/".$bulan."/".$tahun;
}
else
{
	$no = "W23-A1/".$no_urut.$huruf."/".$kode."/".$bulan."/".$tahun;
}

// echo $no;
// echo '<br>';
// echo $tanggal;
$query = "INSERT into nosur (no,no_urut,huruf,nip,tanggal,hal) values ('$no', '$no_urut', '$huruf', '$nip_x', '$tanggal', '$hal')";
$sql=mysqli_query($con, $query);
// echo $query;
if ($nip=='admin'){
    echo '<script>window.location.href="daftarnosurall.php?page=1&count=1"</script>';
} else {
    echo '<script>window.location.href="daftarnosur.php?page=1&count=1"</script>';
}
?>
