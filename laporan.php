<?php  
include ('koneksi.php');
session_start();  
$nip=$_SESSION['nip'];
if(!$_SESSION['nip'])  
{  
  
    header("Location: login.php");//redirect to login page to secure the welcome page without login access.  
}  
$tahun = date('Y');
$q1="SELECT count(*) as count FROM nosur where nip='$nip' AND file='1' AND NOT hal='Belum Diambil' AND YEAR(tanggal)='$tahun'";
$jumlah1 = mysqli_query ($con, $q1);
$jumlah = mysqli_fetch_assoc($jumlah1);
if ($jumlah['count']>0)
{
    echo '<script>alert("Mohon upload softcopy nomor surat sebelumnya!");</script>';
    echo '<script>window.location.href="daftarnosur.php?tahun='.$tahun.'&page=1&count=1"</script>';
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
                    <h1 class="h3 mb-4 text-gray-800">Cetak Laporan</h1>
					<form class="user" action="cetak_laporan.php" method="post">
					<div class="form-group row">					
                        <div class="col-sm-2 mb-sm-0">
                            <select class="form-control" required="required" id="bulan" name="bulan">
								<option value="" hidden>Bulan</option>
                                <option value="00">Semua Bulan</option>
                                <option value="01">Januari</option>
                                <option value="02">Februari</option>
                                <option value="03">Maret</option>
                                <option value="04">April</option>
                                <option value="05">Mei</option>
                                <option value="06">Juni</option>
                                <option value="07">Juli</option>
                                <option value="08">Agustus</option>
                                <option value="09">September</option>
                                <option value="10">Oktober</option>
                                <option value="11">November</option>
                                <option value="12">Desember</option>
							</select>
                        </div>
                        <div class="col-sm-2 mb-sm-0">
                            <select class="form-control" required="required" id="tahun" name="tahun">
								<option value="-" hidden>Tahun</option>
								<option value="2024">2024</option>
								<option value="2023">2023</option>
							</select>
                        </div>
                        <div class="col-sm-3 mb-sm-0">							
                            <select class="form-control" required="required" id="kode" name="kode">
								<option value="" hidden>Kode Surat</option>
								<option value="all">Semua Kode</option>
								<option value="HK">Hukum [HK]</option>
								<option value="HM">Humas dan Protokol [HM]</option>
								<option value="KA">Kearsipan [KA]</option>
								<option value="KP">Kepegawaian [KP]</option>
								<option value="PL">Perlengkapan [PL]</option>
								<option value="PS">Perpustakaan [PS]</option>
								<option value="PW">Pengawasan [PW]</option>
								<option value="RT">Rumah Tangga [RT]</option>
								<option value="TI">Teknologi Informasi [TI]</option>
								<option value="DL">Pendidikan dan Pelatihan [DL]</option>
								<option value="RA">Perencanaan Anggaran [RA]</option>
								<option value="KU">Keuangan [KU]</option>
								<option value="OT">Organisasi Tatalaksana [OT]</option>
							</select>
                        </div>
					</div>
					<div class="form-group">
                        <div>
                          <button class="btn btn-danger" type="button" onclick="window.history.back()">Kembali</button>
						  <button class="btn btn-primary" type="reset">Ulang</button>
                          <button type="submit" class="btn btn-success">Proses</button>
                        </div>
                    </div>
					
					</form>
                </div>
				</div>
                <!-- /.container-fluid -->

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