<?php
include 'koneksi.php';
$nama_dokumen='Daftar Surat Keluar';
$cbulan=$_POST['bulan'];
$ctahun=$_POST['tahun'];
$kodex=$_POST['kode'];
// $tglcetak=$_POST['tgl_cetak'];
// $tgl_cetak=tahunduluan($tglcetak);
// ob_start();
?> 

<html>
	<head>
		<link rel="shortcut icon" type="image/png" href="images/logo_sikes.png">
		<meta charset="UTF-8">
		<title>Agenda Surat Keluar</title>
		<style>
		table{margin-left:40px; border-collapse:collapse; border:1px solid #999;}
		table,td,th{border-collapse:collapse; border:1px solid #999;}
		tr,th{padding: 5px;text-align: center; font-size:17px; font-weight: bold;}
		tr,td{padding: 13px 10px;text-align: left;}
		h1{text-align: center; color: #000; font-size:14px; font-weight: bold;}
		th{background-color: #95a5a6; padding: 10px;color: #000}
		</style>
	</head>
	<body>
	<div>
	<table width="1000" style="border:0px;">
	<?php 
	if ($kodex=='all'){
	if ($cbulan=='00'){
	$sql = mysqli_query($con, "SELECT * FROM nosur where year(tanggal) = '$ctahun' AND NOT hal='Belum Diambil'");
	} else {
	$sql = mysqli_query($con, "SELECT * FROM nosur where month(tanggal)='$cbulan' and year(tanggal) = '$ctahun' AND NOT hal='Belum Diambil'");
	}
	} else
	{
	if ($cbulan=='00'){
	$sql = mysqli_query($con, "SELECT * FROM nosur where year(tanggal) = '$ctahun' AND NOT hal='Belum Diambil' AND kode='$kodex'");
	} else {
	$sql = mysqli_query($con, "SELECT * FROM nosur where month(tanggal)='$cbulan' and year(tanggal) = '$ctahun' AND NOT hal='Belum Diambil' AND kode='$kodex'");
	}
	}
	while ($tampilkan = mysqli_fetch_array($sql)) { 
			$id = $tampilkan['id'];
			$no_surat = $tampilkan['no'];
			$hal = $tampilkan['hal'];
			$tanggal = $tampilkan['tanggal'];
			$filex = $tampilkan['file'];
			if ($filex=='1'){
				$file='Belum Ada';
			} else{
				$file='Ada';
			}
		if ($cbulan == "00")
		{       $namabulan='';
		}
		else if ($cbulan == "01")
		{       $namabulan='Januari';
		}
		else if ($cbulan == "02")
		{       $namabulan='Februari';
		}
		else if ($cbulan == "03")
		{       $namabulan='Maret';
		}
		else if ($cbulan == "04")
		{       $namabulan='April';
		}
		else if ($cbulan == "05")
		{       $namabulan='Mei';
		}
		else if ($cbulan == "06")
		{       $namabulan='Juni';
		}
		else if ($cbulan == "07")
		{       $namabulan='Juli';
		}
		else if ($cbulan == "08")
		{       $namabulan='Agustus';
		}
		else if ($cbulan == "09")
		{       $namabulan='September';
		}
		else if ($cbulan == "10")
		{       $namabulan='Oktober';
		}
		else if ($cbulan == "11")
		{       $namabulan='Nopember';
		}
		else if ($cbulan == "12")
		{       $namabulan='Desember';
		}
	}
	?>
		  <tr>
			<td style="border:0px; padding:10px;"></td>
	      </tr>
		  <tr>
		    <td colspan = "3" style="text-align: center; font-size: 18px; font-weight: bold; border:0px; padding:0px;">DAFTAR SURAT KELUAR</td>
		  </tr>
		  <tr>
		    <td colspan = "3" style="text-align: center; font-size: 16px; font-weight: bold; border:0px; padding:0px;">Periode : &nbsp;<?php echo $namabulan;?>&nbsp;<?php echo $ctahun;?></td>
		  </tr>
		  <hr>
		  <tr>
			<td style="border:0px; padding:5px;"></td>
	      </tr>
		  <tr>
		    <td style="border:0px; font-size: 16px; padding:5px;"></td>
		  </tr>
	  </table>
	  </div>
	  <div>
	  <table width="1000" style="border:1px;">
		<thead>
				<tr>
				<th width="10" align="center">No.</th>
				<th width="60" align="center">No Surat</th>
				<th width="300" align="center">Perihal</th>
				<th width="50" align="center">Tanggal Surat</th>
				<th width="170">File</th>
				</tr>
			</thead>

			<tbody>
			<?php 
			$cbulan=$_POST['bulan'];
			$ctahun=$_POST['tahun'];
			
				if ($kodex=='all'){
				if ($cbulan=='00'){
				$sql = mysqli_query($con, "SELECT * FROM nosur where year(tanggal) = '$ctahun' AND NOT hal='Belum Diambil'");
				} else {
				$sql = mysqli_query($con, "SELECT * FROM nosur where month(tanggal)='$cbulan' and year(tanggal) = '$ctahun' AND NOT hal='Belum Diambil'");
				}
				} else
				{
				if ($cbulan=='00'){
				$sql = mysqli_query($con, "SELECT * FROM nosur where year(tanggal) = '$ctahun' AND NOT hal='Belum Diambil' AND kode='$kodex'");
				} else {
				$sql = mysqli_query($con, "SELECT * FROM nosur where month(tanggal)='$cbulan' and year(tanggal) = '$ctahun' AND NOT hal='Belum Diambil' AND kode='$kodex'");
				}
				}
			$no=1;
			while ($tampilkan = mysqli_fetch_array($sql)) { 
			$id = $tampilkan['id'];
			$no_surat = $tampilkan['no'];
			$hal = $tampilkan['hal'];
			$tanggal = $tampilkan['tanggal'];
			$filex = $tampilkan['file'];
			if ($filex=='1'){
				$file='Belum Ada';
			} else{
				$file='Ada';
			}
			?>

				<tr>
					<td align="center"><?php echo $no++; ?></td>
					<td><?php echo $no_surat; ?></td>
					<td><?php echo $hal; ?></td>
					<td align="center"><?php echo date('d-m-Y', strtotime($tanggal)); ?></td>
					<td><?php echo $file; ?></td>
				<?php
				}
				?>
				</tr>
				<?php 
				$cbulan=$_POST['bulan'];
				$ctahun=$_POST['tahun'];
				if ($kodex=='all'){
					$sql = mysqli_query($con, "SELECT * FROM nosur where month(tanggal)='$cbulan' and year(tanggal) = '$ctahun'");
				} else{
					$sql = mysqli_query($con, "SELECT * FROM nosur where month(tanggal)='$cbulan' and year(tanggal) = '$ctahun' AND kode='$kodex'");
				}
				while ($tampilkan = mysqli_fetch_array($sql)) { 
				$sbi = mysqli_num_rows($sql);
				}
				?>
				<?php 
				$cbulan=$_POST['bulan'];
				$ctahun=$_POST['tahun'];
				$awal = '01';
				if ($kodex=='all'){
					$sql = mysqli_query($con, "SELECT * FROM nosur where month(tanggal)='$cbulan' and year(tanggal) = '$ctahun'");
				} else{
					$sql = mysqli_query($con, "SELECT * FROM nosur where month(tanggal)='$cbulan' and year(tanggal) = '$ctahun' AND kode='$kodex'");
				}
				while ($tampilkan = mysqli_fetch_array($sql)) { 
				$sds = mysqli_num_rows($sql);
				}
				?>
				<tr>
				<th colspan = "4">Jumlah Surat pada &nbsp;<?php echo $namabulan;?>&nbsp;<?php echo $ctahun;?>&nbsp;sebanyak <?php echo $sbi; ?> Surat</th>
				<th colspan = "5">Jumlah Surat s.d &nbsp;<?php echo $namabulan;?>&nbsp;<?php echo $ctahun;?>&nbsp;sebanyak <?php echo $sds; ?> surat</th>
				</tr>
				<tr>
					<td style="border:0px; font-size: 16px; padding:30px;"></td>
				</tr>
			</tbody>	
	  </table>
	  </div>
	  <div>
	  <?php if ($kodex=='all'){?>
	  <table width="1000" style="border:1px;">
		<thead>
				<tr>
				<th width="1000" align="center" colspan = "20" style="background-color: #FFFFFF;">Rekapitulasi Surat Masuk Berdasarkan Kode Klasifikasi Bulan &nbsp;<?php echo $namabulan;?>&nbsp;<?php echo $ctahun;?></th>
				</tr>
				<tr>
				<th width="20" align="center" colspan = "2" style="background-color: #FFFFFF;">OT</th>
				<th width="20" align="center" colspan = "2" style="background-color: #FFFFFF;">HM</th>
				<th width="20" align="center" colspan = "2" style="background-color: #FFFFFF;">KP</th>
				<th width="20" align="center" colspan = "2" style="background-color: #FFFFFF;">KU</th>
				<th width="20" align="center" colspan = "2" style="background-color: #FFFFFF;">KS</th> 
				<th width="20" align="center" colspan = "2" style="background-color: #FFFFFF;">PL</th> 
				<th width="20" align="center" colspan = "2" style="background-color: #FFFFFF;">HK</th>
				<th width="20" align="center" colspan = "2" style="background-color: #FFFFFF;">PP</th>
				<th width="20" align="center" colspan = "2" style="background-color: #FFFFFF;">PB</th>
				<th width="20" align="center" colspan = "2" style="background-color: #FFFFFF;">PS</th>
				</tr>
				<tr>
				<th width="20" align="center" style="background-color: #FFFFFF; font-weight: normal;"><?php echo substr($namabulan,0,3);?></th>
				<th width="20" align="center" style="background-color: #FFFFFF; font-weight: normal;">s.d. <?php echo substr($namabulan,0,3);?></th>
				<th width="20" align="center" style="background-color: #FFFFFF; font-weight: normal;"><?php echo substr($namabulan,0,3);?></th>
				<th width="20" align="center" style="background-color: #FFFFFF; font-weight: normal;">s.d. <?php echo substr($namabulan,0,3);?></th>
				<th width="20" align="center" style="background-color: #FFFFFF; font-weight: normal;"><?php echo substr($namabulan,0,3);?></th>
				<th width="20" align="center" style="background-color: #FFFFFF; font-weight: normal;">s.d. <?php echo substr($namabulan,0,3);?></th>
				<th width="20" align="center" style="background-color: #FFFFFF; font-weight: normal;"><?php echo substr($namabulan,0,3);?></th>
				<th width="20" align="center" style="background-color: #FFFFFF; font-weight: normal;">s.d. <?php echo substr($namabulan,0,3);?></th>
				<th width="20" align="center" style="background-color: #FFFFFF; font-weight: normal;"><?php echo substr($namabulan,0,3);?></th>
				<th width="20" align="center" style="background-color: #FFFFFF; font-weight: normal;">s.d. <?php echo substr($namabulan,0,3);?></th>
				<th width="20" align="center" style="background-color: #FFFFFF; font-weight: normal;"><?php echo substr($namabulan,0,3);?></th>
				<th width="20" align="center" style="background-color: #FFFFFF; font-weight: normal;">s.d. <?php echo substr($namabulan,0,3);?></th>
				<th width="20" align="center" style="background-color: #FFFFFF; font-weight: normal;"><?php echo substr($namabulan,0,3);?></th>
				<th width="20" align="center" style="background-color: #FFFFFF; font-weight: normal;">s.d. <?php echo substr($namabulan,0,3);?></th>
				<th width="20" align="center" style="background-color: #FFFFFF; font-weight: normal;"><?php echo substr($namabulan,0,3);?></th>
				<th width="20" align="center" style="background-color: #FFFFFF; font-weight: normal;">s.d. <?php echo substr($namabulan,0,3);?></th>
				<th width="20" align="center" style="background-color: #FFFFFF; font-weight: normal;"><?php echo substr($namabulan,0,3);?></th>
				<th width="20" align="center" style="background-color: #FFFFFF; font-weight: normal;">s.d. <?php echo substr($namabulan,0,3);?></th>
				<th width="20" align="center" style="background-color: #FFFFFF; font-weight: normal;"><?php echo substr($namabulan,0,3);?></th>
				<th width="20" align="center" style="background-color: #FFFFFF; font-weight: normal;">s.d. <?php echo substr($namabulan,0,3);?></th>
				</tr>
			</thead>
			<tbody>
			<?php 
			$cbulan=$_POST['bulan'];
			$ctahun=$_POST['tahun'];
			
			$sql = mysqli_query($con, "SELECT * FROM nosur where month(tanggal)='$cbulan' and year(tanggal) = '$ctahun' and LEFT (kode,2) = 'OT'");
			while ($tampilkan = mysqli_fetch_array($sql)) { 
			$ot = mysqli_num_rows($sql);
			}
			if ($ot == 0) {
				$ot = '0';
			}
			?>
			<?php 
			$cbulan=$_POST['bulan'];
			$ctahun=$_POST['tahun'];
			
			$sql = mysqli_query($con, "SELECT * FROM nosur where month(tanggal)='$cbulan' and year(tanggal) = '$ctahun' and LEFT (kode,2) = 'HM'");
			while ($tampilkan = mysqli_fetch_array($sql)) { 
			$hm = mysqli_num_rows($sql);
			}
			if ($hm == 0) {
				$hm = '0';
			}
			?>
			<?php 
			$cbulan=$_POST['bulan'];
			$ctahun=$_POST['tahun'];
			
			$sql = mysqli_query($con, "SELECT * FROM nosur where month(tanggal)='$cbulan' and year(tanggal) = '$ctahun' and LEFT (kode,2) = 'KP'");
			while ($tampilkan = mysqli_fetch_array($sql)) { 
			$kp = mysqli_num_rows($sql);
			}
			if ($kp == 0) {
				$kp = '0';
			}
			?>
			<?php 
			$cbulan=$_POST['bulan'];
			$ctahun=$_POST['tahun'];
			
			$sql = mysqli_query($con, "SELECT * FROM nosur where month(tanggal)='$cbulan' and year(tanggal) = '$ctahun' and LEFT (kode,2) = 'KU'");
			while ($tampilkan = mysqli_fetch_array($sql)) { 
			$ku = mysqli_num_rows($sql);
			}
			if ($ku == 0) {
				$ku = '0';
			}
			?>
			<?php 
			$cbulan=$_POST['bulan'];
			$ctahun=$_POST['tahun'];
			
			$sql = mysqli_query($con, "SELECT * FROM nosur where month(tanggal)='$cbulan' and year(tanggal) = '$ctahun' and LEFT (kode,2) = 'KS'");
			while ($tampilkan = mysqli_fetch_array($sql)) { 
			$ks = mysqli_num_rows($sql);
			}
			if ($ks == 0) {
				$ks = '0';
			}
			?>
			<?php 
			$cbulan=$_POST['bulan'];
			$ctahun=$_POST['tahun'];
			
			$sql = mysqli_query($con, "SELECT * FROM nosur where month(tanggal)='$cbulan' and year(tanggal) = '$ctahun' and LEFT (kode,2) = 'PL'");
			while ($tampilkan = mysqli_fetch_array($sql)) { 
			$pl = mysqli_num_rows($sql);
			}
			if ($pl == 0) {
				$pl = '0';
			}
			?>
			<?php 
			$cbulan=$_POST['bulan'];
			$ctahun=$_POST['tahun'];
			
			$sql = mysqli_query($con, "SELECT * FROM nosur where month(tanggal)='$cbulan' and year(tanggal) = '$ctahun' and LEFT (kode,2) = 'HK'");
			while ($tampilkan = mysqli_fetch_array($sql)) { 
			$hk = mysqli_num_rows($sql);
			}
			if ($hk == 0) {
				$hk = '0';
			}
			?>
			<?php 
			$cbulan=$_POST['bulan'];
			$ctahun=$_POST['tahun'];
			
			$sql = mysqli_query($con, "SELECT * FROM nosur where month(tanggal)='$cbulan' and year(tanggal) = '$ctahun' and LEFT (kode,2) = 'PP'");
			while ($tampilkan = mysqli_fetch_array($sql)) { 
			$pp = mysqli_num_rows($sql);
			}
			if ($pp == 0) {
				$pp = '0';
			}
			?>
			<?php 
			$cbulan=$_POST['bulan'];
			$ctahun=$_POST['tahun'];
			
			$sql = mysqli_query($con, "SELECT * FROM nosur where month(tanggal)='$cbulan' and year(tanggal) = '$ctahun' and LEFT (kode,2) = 'PB'");
			while ($tampilkan = mysqli_fetch_array($sql)) { 
			$pb = mysqli_num_rows($sql);
			}
			if ($pb == 0) {
				$pb = '0';
			}
			?>
			<?php 
			$cbulan=$_POST['bulan'];
			$ctahun=$_POST['tahun'];
			
			$sql = mysqli_query($con, "SELECT * FROM nosur where month(tanggal)='$cbulan' and year(tanggal) = '$ctahun' and LEFT (kode,2) = 'PS'");
			while ($tampilkan = mysqli_fetch_array($sql)) { 
			$ps = mysqli_num_rows($sql);
			}
			if ($ps == 0) {
				$ps = '0';
			}
			?>
						<?php 
			$cbulan=$_POST['bulan'];
			$ctahun=$_POST['tahun'];
			$awal = '01';
			
			$sql = mysqli_query($con, "SELECT * FROM nosur WHERE month(tanggal)BETWEEN '$awal' AND '$cbulan' and year(tanggal) = '$ctahun' and LEFT (kode,2) = 'OT'");
			while ($tampilkan = mysqli_fetch_array($sql)) { 
			$sdot = mysqli_num_rows($sql);
			}
			if ($sdot == 0) {
				$sdot = '0';
			}
			?>
			<?php 
			$cbulan=$_POST['bulan'];
			$ctahun=$_POST['tahun'];
			$awal = '01';
			
			$sql = mysqli_query($con, "SELECT * FROM nosur WHERE month(tanggal)BETWEEN '$awal' AND '$cbulan' and year(tanggal) = '$ctahun' and LEFT (kode,2) = 'HM'");
			while ($tampilkan = mysqli_fetch_array($sql)) { 
			$sdhm = mysqli_num_rows($sql);
			}
			if ($sdhm == 0) {
				$sdhm = '0';
			}
			?>
			<?php 
			$cbulan=$_POST['bulan'];
			$ctahun=$_POST['tahun'];
			$awal = '01';
			
			$sql = mysqli_query($con, "SELECT * FROM nosur WHERE month(tanggal)BETWEEN '$awal' AND '$cbulan' and year(tanggal) = '$ctahun' and LEFT (kode,2) = 'KP'");
			while ($tampilkan = mysqli_fetch_array($sql)) { 
			$sdkp = mysqli_num_rows($sql);
			}
			if ($sdkp == 0) {
				$sdkp = '0';
			}
			?>
			<?php 
			$cbulan=$_POST['bulan'];
			$ctahun=$_POST['tahun'];
			$awal = '01';
			
			$sql = mysqli_query($con, "SELECT * FROM nosur WHERE month(tanggal)BETWEEN '$awal' AND '$cbulan' and year(tanggal) = '$ctahun' and LEFT (kode,2) = 'KU'");
			while ($tampilkan = mysqli_fetch_array($sql)) { 
			$sdku = mysqli_num_rows($sql);
			}
			if ($sdku == 0) {
				$sdku = '0';
			}
			?>
			<?php 
			$cbulan=$_POST['bulan'];
			$ctahun=$_POST['tahun'];
			$awal = '01';
			
			$sql = mysqli_query($con, "SELECT * FROM nosur WHERE month(tanggal)BETWEEN '$awal' AND '$cbulan' and year(tanggal) = '$ctahun' and LEFT (kode,2) = 'KS'");
			while ($tampilkan = mysqli_fetch_array($sql)) { 
			$sdks = mysqli_num_rows($sql);
			}
			if ($sdks == 0) {
				$sdks = '0';
			}
			?>
			<?php 
			$cbulan=$_POST['bulan'];
			$ctahun=$_POST['tahun'];
			$awal = '01';
			
			$sql = mysqli_query($con, "SELECT * FROM nosur WHERE month(tanggal)BETWEEN '$awal' AND '$cbulan' and year(tanggal) = '$ctahun' and LEFT (kode,2) = 'PL'");
			while ($tampilkan = mysqli_fetch_array($sql)) { 
			$sdpl = mysqli_num_rows($sql);
			}
			if ($sdpl == 0) {
				$sdpl = '0';
			}
			?>
			<?php 
			$cbulan=$_POST['bulan'];
			$ctahun=$_POST['tahun'];
			$awal = '01';
			
			$sql = mysqli_query($con, "SELECT * FROM nosur WHERE month(tanggal)BETWEEN '$awal' AND '$cbulan' and year(tanggal) = '$ctahun' and LEFT (kode,2) = 'HK'");
			while ($tampilkan = mysqli_fetch_array($sql)) { 
			$sdhk = mysqli_num_rows($sql);
			}
			if ($sdhk == 0) {
				$sdhk = '0';
			}
			?>
			<?php 
			$cbulan=$_POST['bulan'];
			$ctahun=$_POST['tahun'];
			$awal = '01';
			
			$sql = mysqli_query($con, "SELECT * FROM nosur WHERE month(tanggal)BETWEEN '$awal' AND '$cbulan' and year(tanggal) = '$ctahun' and LEFT (kode,2) = 'PP'");
			while ($tampilkan = mysqli_fetch_array($sql)) { 
			$sdpp = mysqli_num_rows($sql);
			}
			if ($sdpp == 0) {
				$sdpp = '0';
			}
			?>
			<?php 
			$cbulan=$_POST['bulan'];
			$ctahun=$_POST['tahun'];
			$awal = '01';
			
			$sql = mysqli_query($con, "SELECT * FROM nosur WHERE month(tanggal)BETWEEN '$awal' AND '$cbulan' and year(tanggal) = '$ctahun' and LEFT (kode,2) = 'PB'");
			while ($tampilkan = mysqli_fetch_array($sql)) { 
			$sdpb = mysqli_num_rows($sql);
			}
			if ($sdpb == 0) {
				$sdpb = '0';
			}
			?>
			<?php 
			$cbulan=$_POST['bulan'];
			$ctahun=$_POST['tahun'];
			$awal = '01';
			
			$sql = mysqli_query($con, "SELECT * FROM nosur WHERE month(tanggal)BETWEEN '$awal' AND '$cbulan' and year(tanggal) = '$ctahun' and LEFT (kode,2) = 'PS'");
			while ($tampilkan = mysqli_fetch_array($sql)) { 
			$sdps = mysqli_num_rows($sql);
			}
			if ($sdps == 0) {
				$sdps = '0';
			}
			?>
				<tr>
					<td align="center"><?php echo $ot; ?></td>
					<td align="center"><?php echo $sdot; ?></td>
					<td align="center"><?php echo $hm; ?></td>
					<td align="center"><?php echo $sdhm; ?></td>
					<td align="center"><?php echo $kp; ?></td>
					<td align="center"><?php echo $sdkp; ?></td>
					<td align="center"><?php echo $ku; ?></td>
					<td align="center"><?php echo $sdku; ?></td>
					<td align="center"><?php echo $ks; ?></td>
					<td align="center"><?php echo $sdks; ?></td>
					<td align="center"><?php echo $pl; ?></td>
					<td align="center"><?php echo $sdpl; ?></td>
					<td align="center"><?php echo $hk; ?></td>
					<td align="center"><?php echo $sdhk; ?></td>
					<td align="center"><?php echo $pp; ?></td>
					<td align="center"><?php echo $sdpp; ?></td>
					<td align="center"><?php echo $pb; ?></td>
					<td align="center"><?php echo $sdpb; ?></td>
					<td align="center"><?php echo $ps; ?></td>
					<td align="center"><?php echo $sdps; ?></td>
				</tr>
				<tr>
					<td style="border:0px; font-size: 16px; padding:10px;"></td>
				</tr>
			</tbody>	
	  </table>
	  <?php }
	  else
	  {
		  
	  }
	  ?>
	  </div>
	  		<?php 
			$sql = "SELECT * FROM pegawai WHERE jabatan = 'Sekretaris'";
			$tampil = mysqli_query($GLOBALS["___mysqli_ston"], $sql);
			while ($tampilkan = mysqli_fetch_array($tampil)) { 
			$namakpa = $tampilkan['nama'];
			$nipkpa = $tampilkan['nip'];
			}
			?>
			<?php 
			$sql = "SELECT * FROM pegawai WHERE jabatan = 'Kepala Sub Bagian Umum dan Keuangan'";
			$tampil = mysqli_query($GLOBALS["___mysqli_ston"], $sql);
			while ($tampilkan = mysqli_fetch_array($tampil)) { 
			$namabpg = $tampilkan['nama'];
			$nipbpg = $tampilkan['nip'];
			}
			?>
			<?php 
			// Tampilkan data dari Database
			$sql = "SELECT * FROM satker WHERE id_satker = 1";
			$tampil = mysqli_query($GLOBALS["___mysqli_ston"], $sql);
			while ($tampilkan = mysqli_fetch_array($tampil)) { 

			$nama_satker = $tampilkan['nama'];
			$kota_satker = $tampilkan['kota'];
			}
			?>
	  <table width="1000" style="border:1px;">
				<tr>
				  <td width="320" style="font-size:18px;border:0px;border-top:0px; padding:0px; text-align: left;">Mengetahui,</td>
				  <td width="380" style="font-size:18px;border:0px;border-top:0px; padding:0px; text-align: left;"></td>
				  <td width="300" style="font-size:18px;border:0px;border-top:0px; padding:0px; text-align: left;"><?php echo $kota_satker;?>, &nbsp;<?php echo indonesia2Tgl($tgl_cetak);?></td>
				</tr>
				<tr>
				  <td width="320" style="font-size:18px;border:0px;border-top:0px; padding:0px; text-align: left;">Sekretaris</td>
				  <td width="380" style="font-size:18px;border:0px;border-top:0px; padding:0px; text-align: left;"></td>
				  <td width="300" style="font-size:18px;border:0px;border-top:0px; padding:0px; text-align: left;">Kepala Sub Bagian Umum dan Keuangan</td>
				</tr>
				<tr>
					<td style="border:0px; font-size: 26px; padding:40px;"></td>
				</tr>
				<tr>
				  <td width="320" style="font-size:18px;border:0px;border-top:0px; padding:0px; text-align: left;"><?php echo $namakpa;?></td>
				  <td width="380" style="font-size:18px;border:0px;border-top:0px; padding:0px; text-align: left;"></td>
				  <td width="400" style="font-size:18px;border:0px;border-top:0px; padding:0px; text-align: left;"><?php echo $namabpg;?></td>
				</tr>
				<tr>
				  <td width="320" style="font-size:18px;border:0px;border-top:0px; padding:0px; text-align: left;">NIP. <?php echo $nipkpa;?></td>
				  <td width="380" style="font-size:18px;border:0px;border-top:0px; padding:0px; text-align: left;"></td>
				  <td width="400" style="font-size:18px;border:0px;border-top:0px; padding:0px; text-align: left;">NIP. <?php echo $nipbpg;?></td>
				</tr>
	  </table>
	  </div>
	</body>
</html>

<?php
// $html = ob_get_contents();
// ob_end_clean();
// $mpdf->SetFooter($sitari.$nama_dokumen.$namabulan.$s.$ctahun.' Hal. {PAGENO}','O');
// $mpdf->WriteHTML(utf8_encode($html));
// $mpdf->Output($nama_dokumen.$namabulan.' '.$ctahun.".pdf" ,'I');
// exit;
?>