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

$id=$_POST['id'];
$nip_surat=$_POST['nip'];
$no_urut=$_POST['no_urut'];
$kd= $_POST['kd'];
$kd1= $_POST['kd1'];
$kd2= $_POST['kd2'];
$sk= $_POST['sk'];
$no_surat2= $_POST['no_surat2x'];

if ($kd2 == "-")
{
	$kode = $kd.".".$kd1;
}
else
{
	$kode = $kd.".".$kd1.".".$kd2;
}

if ($sk == "1")
{
	$no = "W23-A1/".$no_urut."/".$kode."/SK".$no_surat2;
}
else
{
	$no = "W23-A1/".$no_urut."/".$kode."".$no_surat2;
}

$hal = $_POST['hal'];
// echo $no;
$query = "UPDATE nosur SET nip='$nip_surat', no='$no', hal='$hal' WHERE id='$id'";
// echo $query;
$sql=mysqli_query($con, $query);
echo '<script>window.location.href="daftarnosurall.php?page=1&count=1"</script>';
?>
