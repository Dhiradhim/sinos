<?php
$servername = "192.168.1.2";
$database = "pakp_baru";
$username = "root";
$password = "peradilan";
$con = mysqli_connect($servername, $username, $password, $database);

$sql = "SELECT vp.nomor_perkara, vp.jenis_perkara_nama, js.tanggal_sidang, js.agenda, vp.majelis_hakim_text, vp.panitera_pengganti_text, vp.jurusita_text
			 FROM perkara_jadwal_sidang js
			 JOIN v_perkara vp ON js.perkara_id=vp.perkara_id
			 WHERE tanggal_sidang=CURDATE()";
$query = mysqli_query($con,$sql);
$row = mysqli_fetch_assoc($query);

if (is_null($row['nomor_perkara'])){
	echo 'TIDAK ADA JADWAL SIDANG UNTUK BESOK';
} else {

//NO HAPE PENERIMA
$target='085727163818';

//ISI PESAN CUSTOM
$pesan1='';
do{
if (str_contains($row['majelis_hakim_text'],'</br>')){
	$hakim=explode('</br>',$row['majelis_hakim_text']);
	$hakim_text=$hakim[0] . "\r\n" . $hakim[1] . "\r\n" . $hakim[2]; 
}
else {
	$hakim_text=$row['majelis_hakim_text'];
}
$pesan1 = $pesan1 . "Perkara Nomor: " . $row['nomor_perkara'] . "\r\nKlasifikasi Perkara: " . $row['jenis_perkara_nama'] . "\r\nAgenda Sidang: " . $row['agenda'] . "\r\n" . $hakim_text . "\r\n" . $row['panitera_pengganti_text'] . "\r\n" . $row['jurusita_text'] . "\r\n\r\n";
$tanggal_sidang=$row['tanggal_sidang'];
} while ($row = mysqli_fetch_assoc($query));
$pesan='> *Jadwal Sidang Tanggal ' . tanggal($tanggal_sidang) . "*\r\n\r\n" . $pesan1;

// echo $pesan;
$curl = curl_init();
curl_setopt_array($curl, array(
  CURLOPT_URL => 'https://api.fonnte.com/send',
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => '',
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 0,
  CURLOPT_FOLLOWLOCATION => true,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => 'POST',
  CURLOPT_POSTFIELDS => array(
'target' => $target,
'message' => $pesan, 
'countryCode' => '62',
),
  CURLOPT_HTTPHEADER => array(
    'Authorization: M3kLErAv-T6djxx8QMiK' //change TOKEN to your actual token
  ),
));

$response = curl_exec($curl);

curl_close($curl);
echo $response;
}
function tanggal($date) { 
        $BulanIndo = array("Januari", "Februari", "Maret",
                           "April", "Mei", "Juni",
                           "Juli", "Agustus", "September",
                           "Oktober", "November", "Desember");
     
        $tahun = substr($date, 0, 4); 
        $bulan = substr($date, 5, 2); 
        $tgl   = substr($date, 8, 2); 
         
        $result = $tgl . " " . $BulanIndo[(int)$bulan-1] . " ". $tahun;
        return($result);
}    
?>
