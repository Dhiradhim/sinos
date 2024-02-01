<?php  
include ('koneksi.php');
session_start();  
$nip=$_SESSION['nip'];
if(!$_SESSION['nip'])  
{  
  
    header("Location: login.php");//redirect to login page to secure the welcome page without login access.  
}  
  
?> 
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">

    <title>SINOS - Sistem Informasi Nomor Surat</title>

    <!-- Custom fonts for this template-->
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link
        href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i"
        rel="stylesheet">

    <!-- Custom styles for this template-->
    <link href="css/sb-admin-2.min.css" rel="stylesheet">

</head>

<body id="page-top">

    <!-- Page Wrapper -->
    <div id="wrapper">

        <!-- Sidebar -->
		<?php include('sidebar.html');?>
        <!-- End of Sidebar -->

        <!-- Content Wrapper -->
        <div id="content-wrapper" class="d-flex flex-column">

            <!-- Main Content -->
            <div id="content">

                <!-- Topbar -->
                <nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow">

                    <!-- Sidebar Toggle (Topbar) -->
                    <button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle mr-3">
                        <i class="fa fa-bars"></i>
                    </button>

                    <!-- Topbar Navbar -->
					<ul class="navbar-nav ml-auto">
					<?php
					$nip=$_SESSION['nip'];
					$q_nama="SELECT nama FROM user WHERE nip='$nip'";
					$nama = mysqli_query ($con, $q_nama);
					$row_nama = mysqli_fetch_assoc($nama);
					?>
                        <!-- Nav Item - User Information -->
                        <li class="nav-item dropdown no-arrow">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button"
                                data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <span class="mr-2 d-none d-lg-inline text-gray-600 small"><?=$row_nama['nama'];?></span>
                            </a>
                            <!-- Dropdown - User Information -->
                            <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in"
                                aria-labelledby="userDropdown">
                                <a class="dropdown-item" href="changepassword.php">
                                    <i class="fas fa-user fa-sm fa-fw mr-2 text-gray-400"></i>
                                    Ganti Password
                                </a>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item" href="#" data-toggle="modal" data-target="#logoutModal">
                                    <i class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-gray-400"></i>
                                    Logout
                                </a>
                            </div>
                        </li>

                    </ul>

                </nav>
                <!-- End of Topbar -->

                <!-- Begin Page Content -->
                <div class="container-fluid">

                    <!-- Page Heading -->
                    <h1 class="h3 mb-4 text-gray-800">Edit Nomor Surat</h1>
					<form class="user" action="editnosursave.php" method="post">
					<div class="form-group row">
					<?php 
						$id=$_GET['id'];
                        $q_nama1="SELECT user.nama as nama, nosur.* FROM nosur left join user on nosur.nip=user.nip WHERE nosur.id='$id'";
                        $nama1 = mysqli_query ($con, $q_nama1);
                        $row_nama1 = mysqli_fetch_assoc($nama1);
                        foreach($row_nama1 as $key=>$value) {$$key=$value;}
                        $no_surat = explode('/',$no);
                        $kode = explode('.',$no_surat[2]);
                        if (mb_strlen($kode[1])<2)
                        {
                            $ks=substr($kode[0],0,2);
                            $ks1=substr($kode[0],2,1);  
                            $kode2=$kode[1];
                            $kode3=$kode[2];
                        }
                        else 
                        {
                        $ks=substr($kode[1],0,2);
                        $ks1=substr($kode[1],2,1);  
                        $kode2=$kode[2];
                        $kode3=$kode[3];
                        }
                        $nip=$_SESSION['nip'];

                        $q_nama2="SELECT nip,nama FROM user WHERE aktif=0 ORDER BY id_jabatan";
                        $nama2 = mysqli_query ($con, $q_nama2);
                        $row_nama2 = mysqli_fetch_assoc($nama2);

                        $q_nama3="SELECT user.id,nama,kode FROM user LEFT JOIN jabatan ON user.id_jabatan=jabatan.id WHERE aktif=0 AND NOT kode='-' ORDER BY jabatan.id";
                        $nama3 = mysqli_query ($con, $q_nama3);
                        $row_nama3 = mysqli_fetch_assoc($nama3);
					?>						
                        <div class="col-sm-3 mb-sm-0">
                            <input type="hidden" readonly class="form-control" id="id" name="id" value="<?=$id;?>" >
                            <input type="hidden" readonly class="form-control" id="no_urut" name="no_urut" value="<?=$no_urut;?>" >
                            <input type="hidden" readonly class="form-control" id="huruf" name="huruf" value="<?=$huruf;?>" >
                            <input type="hidden" readonly class="form-control" id="tanggal" name="tanggal" value="<?=$tanggal;?>" >
                            <?php if ($nip=='admin'){ ?>
                            <select class="form-control" required="required" id="nip" name="nip">
								<?php
                                do {
                                ?>
                                <option value="<?=$row_nama2['nip']?>" <?php if ($row_nama2['nip']==$row_nama1['nip']){ echo 'selected'; } ?>><?=$row_nama2['nama'];?></option>
                                <?php 
                                } while ($row_nama2 = mysqli_fetch_assoc($nama2));?>
							</select>
                            <?php } else { ?>
                            <input type="hidden" readonly class="form-control" id="nip" name="nip" value="<?=$row_nama1['nip'];?>" >                                
                            <input type="text" readonly class="form-control" id="nipx" name="nipx" value="<?=$row_nama1['nama'];?>" >                                
                                <?php } ?> 
                        </div>
                        <div class="col-sm-3 mb-sm-0">
                        <select class="form-control" required="required" id="kj" name="kj">
								<?php
                                do {
                                ?>
                                <option value="<?=$row_nama3['kode']?>" <?php if ($row_nama1['kj']==$row_nama3['kode']){ echo 'selected'; } ?>><?=$row_nama3['nama'];?> [<?=$row_nama3['kode'];?>]</option>
                                <?php 
                                } while ($row_nama3 = mysqli_fetch_assoc($nama3));?>
							</select>	
                        </div>			
                            </div>
                            <div class="form-group row">
                        <div class="col-sm-2 mb-sm-0">
                            <input type="text" readonly class="form-control" id="no_surat1" name="no_surat1" value="<?=$no_surat[0];?>" >
                        </div>
                        <div class="col-sm-3 mb-sm-0">							
                            <select class="form-control" required="required" id="kp" name="kp">
								<option value="" hidden>Kode Penetapan</option>
								<option value="-" selected>Surat Lainnya [-]</option>
								<option value="SK" <?php if ($kode[0]=='SK'){ echo "selected";}?>>Surat Keputusan [SK]</option>
								<option value="SP" <?php if ($kode[0]=='SP'){ echo "selected";}?>>Surat Perintah [SP]</option>
								<option value="ST" <?php if ($kode[0]=='ST'){ echo "selected";}?>>Surat Tugas [ST]</option>
								<option value="SKET" <?php if ($kode[0]=='SKET'){ echo "selected";}?>>Surat Keterangan [SKET]</option>
								<option value="PENG" <?php if ($kode[0]=='PENG'){ echo "selected";}?>>Pengumuman [PENG]</option>
								<option value="UND" <?php if ($kode[0]=='UND'){ echo "selected";}?>>Undangan [UND]</option>
							</select>
                        </div>
                        <div class="col-sm-2 mb-sm-0">							
                            <select class="form-control" required="required" id="ks" name="ks">
								<option value="" hidden>Kode Surat</option>
								<option value="HK" <?php if ($ks=='HK'){ echo "selected";}?>>Hukum [HK]</option>
								<option value="HM" <?php if ($ks=='HM'){ echo "selected";}?>>Humas dan Protokol [HM]</option>
								<option value="KA" <?php if ($ks=='KA'){ echo "selected";}?>>Kearsipan [KA]</option>
								<option value="KP" <?php if ($ks=='KP'){ echo "selected";}?>>Kepegawaian [KP]</option>
								<option value="PL" <?php if ($ks=='PL'){ echo "selected";}?>>Perlengkapan [PL]</option>
								<option value="PS" <?php if ($ks=='PS'){ echo "selected";}?>>Perpustakaan [PS]</option>
								<option value="PW" <?php if ($ks=='PW'){ echo "selected";}?>>Pengawasan [PW]</option>
								<option value="RT" <?php if ($ks=='RT'){ echo "selected";}?>>Rumah Tangga [RT]</option>
								<option value="TI" <?php if ($ks=='TI'){ echo "selected";}?>>Teknologi Informasi [TI]</option>
								<option value="DL" <?php if ($ks=='DL'){ echo "selected";}?>>Pendidikan dan Pelatihan [DL]</option>
								<option value="RA" <?php if ($ks=='RA'){ echo "selected";}?>>Perencanaan Anggaran [RA]</option>
								<option value="KU" <?php if ($ks=='KU'){ echo "selected";}?>>Keuangan [KU]</option>
								<option value="OT" <?php if ($ks=='OT'){ echo "selected";}?>>Organisasi Tatalaksana [OT]</option>
							</select>
                        </div>
                        <div class="col-sm-1 mb-sm-0">
                            <select class="form-control" required="required" id="kd1" name="kd1">
								<option value="" hidden>-</option>
								<option value="0" <?php if ($ks1=='0'){ echo "selected";}?>>0</option>
								<option value="1" <?php if ($ks1=='1'){ echo "selected";}?>>1</option>
								<option value="2" <?php if ($ks1=='2'){ echo "selected";}?>>2</option>
								<option value="3" <?php if ($ks1=='3'){ echo "selected";}?>>3</option>
								<option value="4" <?php if ($ks1=='4'){ echo "selected";}?>>4</option>
								<option value="5" <?php if ($ks1=='5'){ echo "selected";}?>>5</option>
								<option value="6" <?php if ($ks1=='6'){ echo "selected";}?>>6</option>
								<option value="7" <?php if ($ks1=='7'){ echo "selected";}?>>7</option>
								<option value="8" <?php if ($ks1=='8'){ echo "selected";}?>>8</option>
								<option value="9" <?php if ($ks1=='9'){ echo "selected";}?>>9</option>
							</select>
                        </div>
                        <?php if (!empty($kode[2])){ ?>
                        <div class="col-sm-1 mb-sm-0">
                            <select class="form-control" id="kd2" name="kd2">
								<option value="-">-</option>
								<option value="1" <?php if ($kode2=='1'){ echo "selected";}?>>1</option>
								<option value="2" <?php if ($kode2=='2'){ echo "selected";}?>>2</option>
								<option value="3" <?php if ($kode2=='3'){ echo "selected";}?>>3</option>
								<option value="4" <?php if ($kode2=='4'){ echo "selected";}?>>4</option>
								<option value="5" <?php if ($kode2=='5'){ echo "selected";}?>>5</option>
								<option value="6" <?php if ($kode2=='6'){ echo "selected";}?>>6</option>
								<option value="7" <?php if ($kode2=='7'){ echo "selected";}?>>7</option>
								<option value="8" <?php if ($kode2=='8'){ echo "selected";}?>>8</option>
								<option value="9" <?php if ($kode2=='9'){ echo "selected";}?>>9</option>
							</select>
                        </div>
                        <?php } else {?>
                        <div class="col-sm-1 mb-sm-0">
                            <select class="form-control" id="kd2" name="kd2">
								<option value="-">-</option>
								<option value="1">1</option>
								<option value="2">2</option>
								<option value="3">3</option>
								<option value="4">4</option>
								<option value="5">5</option>
								<option value="6">6</option>
								<option value="7">7</option>
								<option value="8">8</option>
								<option value="9">9</option>
							</select>
                        </div>
                        <?php }?>
                        <?php if (!empty($kode[2])){ ?>
                        <div class="col-sm-1 mb-sm-0">
                            <select class="form-control" id="kd3" name="kd3">
								<option value="-">-</option>
								<option value="1" <?php if ($kode3=='1'){ echo "selected";}?>>1</option>
								<option value="2" <?php if ($kode3=='2'){ echo "selected";}?>>2</option>
								<option value="3" <?php if ($kode3=='3'){ echo "selected";}?>>3</option>
								<option value="4" <?php if ($kode3=='4'){ echo "selected";}?>>4</option>
								<option value="5" <?php if ($kode3=='5'){ echo "selected";}?>>5</option>
								<option value="6" <?php if ($kode3=='6'){ echo "selected";}?>>6</option>
								<option value="7" <?php if ($kode3=='7'){ echo "selected";}?>>7</option>
								<option value="8" <?php if ($kode3=='8'){ echo "selected";}?>>8</option>
								<option value="9" <?php if ($kode3=='9'){ echo "selected";}?>>9</option>
							</select>
                        </div>
                        <?php } else {?>
                        <div class="col-sm-1 mb-sm-0">
                            <select class="form-control" id="kd3" name="kd3">
								<option value="-">-</option>
								<option value="1">1</option>
								<option value="2">2</option>
								<option value="3">3</option>
								<option value="4">4</option>
								<option value="5">5</option>
								<option value="6">6</option>
								<option value="7">7</option>
								<option value="8">8</option>
								<option value="9">9</option>
							</select>
                        </div>
                        <?php }?>			
                        <div class="col-sm-2 mb-sm-0">
                        </div>
						<!-- <div class="form-check">
                            <input class="form-check-input" id="flexCheckDefault" <?php if ($no_surat[3]=='SK'){ echo 'checked'; } ?> name="sk" type="checkbox" value="1">
                            <label class="form-check-label" for="flexCheckDefault">SK</label>
                        </div> -->
						
                    </div>
					<div class="form-group">
						<div>
                            <input type="text" class="form-control" id="hal" name="hal" placeholder="Perihal" required="required" value="<?=$hal?>">
                        </div>
                    </div>
					<div class="form-group">
						<div>
                            <input type="text" class="form-control" id="tujuan" name="tujuan" placeholder="Perihal" required="required" value="<?=$tujuan?>">
                        </div>
                    </div>
					
					<div class="form-group">
                        <div>
                          <button class="btn btn-danger" type="button" onclick="window.history.back()">Kembali</button>
                          <button type="submit" class="btn btn-success">Simpan</button>
                        </div>
                    </div>
					
					</form>
                </div>
                <!-- /.container-fluid -->

            </div>
            <!-- End of Main Content -->

            <!-- Footer -->
			<footer class="sticky-footer bg-white">
                <div class="container my-auto">
                    <div class="copyright text-center my-auto">
                        <span>Copyright &copy; Pengadilan Agama Kupang <script>document.write(new Date().getFullYear())</script></span>
                    </div>
                </div>
            </footer>
            <!-- End of Footer -->

        </div>
        <!-- End of Content Wrapper -->

    </div>
    <!-- End of Page Wrapper -->

    <!-- Scroll to Top Button-->
    <a class="scroll-to-top rounded" href="#page-top">
        <i class="fas fa-angle-up"></i>
    </a>

    <!-- Logout Modal-->
	<?php include('logout-modal.html');?>

    <!-- Bootstrap core JavaScript-->
    <script src="vendor/jquery/jquery.min.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

    <!-- Core plugin JavaScript-->
    <script src="vendor/jquery-easing/jquery.easing.min.js"></script>

    <!-- Custom scripts for all pages-->
    <script src="js/sb-admin-2.min.js"></script>

</body>

</html>