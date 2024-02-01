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
$tahun = date('Y');
$xno = mysqli_query($con, "SELECT no_urut FROM nosur WHERE year(tanggal)='$tahun' ORDER BY no_urut DESC LIMIT 1");
$hasil = mysqli_fetch_assoc($xno);

$nip=$_SESSION['nip'];
$nip_x=$_POST['nip'];
$kj=$_POST['kj'];
$kp=$_POST['kp'];
$ks=$_POST['ks'];
$no_urut = $hasil['no_urut']+1;
$kd1= $_POST['kd1'];
$kd2= $_POST['kd2'];
$kd3= $_POST['kd3'];

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

$bul = date('n');
$bulan = getRomawi($bul);
$tahun = date('Y');

$no = $no_urut."/".$kj.".W23-A1/".$kode."/".$bulan."/".$tahun;


$hal = $_POST['hal'];
$tanggal = date('Y-m-d');
// echo $no;
$query = "INSERT into nosur (no,kode,no_urut,nip,kj,tanggal,hal) values ('$no', '$ks', '$no_urut', '$nip_x', '$kj', '$tanggal', '$hal')";
// echo $query;
$sql=mysqli_query($con, $query);

echo '<script>alert("Berikut detail nomor surat\n\nNomor: '.$no.'\nTanggal Surat: '.$tanggal.'\nPerihal: '.$hal.'");</script>';
if ($nip=='admin'){
    echo '<script>window.location.href="daftarnosurall.php?tahun='.$tahun.'&page=1&count=1"</script>';
} else {
    echo '<script>window.location.href="daftarnosur.php?tahun='.$tahun.'&page=1&count=1"</script>';
}
?>
