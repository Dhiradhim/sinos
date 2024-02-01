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
$kj=$_POST['kj'];
$kp=$_POST['kp'];
$ks=$_POST['ks'];
$kd1 = $_POST['kd1'];
$kd2 = $_POST['kd2'];
$kd3 = $_POST['kd3'];
$tanggal = $_POST['tanggal'];
$xtanggal=explode('-',$tanggal);
$tahun=$xtanggal[0];
$bul=$xtanggal[1];
$hal = $_POST['hal'];
$tujuan = $_POST['tujuan'];

$tgl_hari_ini =date('Y-m-d');
$tgl_kemarin = date('Y-m-d', strtotime("-1 day", strtotime(date('Y-m-d'))));

$sql_kode= "SELECT huruf, no_urut, tanggal as tgl FROM nosur WHERE tanggal<='$tgl_hari_ini' ORDER BY tanggal DESC, id DESC limit 1";
$query_kode= mysqli_query($con, $sql_kode);
$row_kode = mysqli_fetch_assoc($query_kode);
$no_urut = $row_kode['no_urut'];
$huruf = $row_kode['huruf'];
$xx = $row_kode['tgl'];

$sql_kode2= "SELECT huruf, no_urut, tanggal AS tgl FROM nosur WHERE tanggal<='$tanggal' AND YEAR(tanggal)=YEAR('$tanggal') ORDER BY tanggal DESC, id DESC LIMIT 1";
$query_kode2= mysqli_query($con, $sql_kode2);
$row_kode2 = mysqli_fetch_assoc($query_kode2);
$no_urut2 = $row_kode2['no_urut'];
$huruf2 = $row_kode2['huruf'];


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
    $sql_kode= "SELECT huruf, no_urut, tanggal AS tgl FROM nosur WHERE tanggal<='$tanggal' AND YEAR(tanggal)=YEAR('$tanggal') ORDER BY tanggal DESC, id DESC LIMIT 1";
    $query_kode= mysqli_query($con, $sql_kode);
    $row_kode = mysqli_fetch_assoc($query_kode);
    $no_urutx = $row_kode['no_urut'];
    $tglx = $row_kode['tgl'];
	
	// if ($tglx==$tanggal){
		// $no_urut = $no_urutx+1;
		// $huruf = "";
	// }
	// else 
	if (is_null($no_urutx)){
		$no_urut = "1";
		$huruf = "";
	}
	else {
		$no_urut = $no_urutx."A";
		$huruf='A';
	}
}

if ($kp == "-")
{
    
    if ($kd2 == "-")
    {
        $kode = $ks.$kd1;
    }
    else if ($kd3 == "-")
    {
        $kode = $ks.$kd1.".".$kd2;
    }
    else
    {
        $kode = $ks.$kd1.".".$kd2.".".$kd3;
    }
}
else
{
    if ($kd2 == "-")
    {
        $kode = $kp.".".$ks.$kd1;
    }
    else if ($kd3 == "-")
    {
        $kode = $kp.".".$ks.$kd1.".".$kd2;
    }
    else
    {
        $kode = $kp.".".$ks.$kd1.".".$kd2.".".$kd3;
    }
}

$bulan = getRomawi($bul);

$no = $no_urut."/".$kj.".W23-A1/".$kode."/".$bulan."/".$tahun;
$no_urut = preg_replace("/[^0-9]/", "", "$no_urut" );

$query = "INSERT into nosur (no,kode,no_urut,huruf,nip,kj,tanggal,hal,tujuan) values ('$no', '$ks', '$no_urut', '$huruf', '$nip_x', '$kj', '$tanggal', '$hal', '$tujuan')";
$sql=mysqli_query($con, $query);
echo '<script>alert("Berikut detail nomor surat\n\nNomor: '.$no.'\nTanggal Surat: '.$tanggal.'\nPerihal: '.$hal.'\nTujuan: '.$tujuan.'");</script>';
if ($nip=='admin'){
    echo '<script>window.location.href="daftarnosurall.php?tahun='.$tahun.'&page=1&count=1"</script>';
} else {
    echo '<script>window.location.href="daftarnosur.php?tahun='.$tahun.'&page=1&count=1"</script>';
}

// echo $sql_kode;
// echo "<br>";
// echo $sql_kode2;
// echo "<br>";
// echo $query;
// echo "<br>";
// echo $tglx;
// echo "<br>";
// echo $tanggal;
?>
