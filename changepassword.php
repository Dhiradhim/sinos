<!DOCTYPE html>
<html lang="en">

<head>
<?php include('head.html');?>
</head>

<body id="page-top">

    <!-- Page Wrapper -->
    <div id="wrapper">

        <!-- Sidebar -->
		<ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion" id="accordionSidebar">

            <!-- Sidebar - Brand -->
            <a class="sidebar-brand d-flex align-items-center justify-content-center">
                <div class="sidebar-brand-text mx-3">SINOS</div>
            </a>
					   <!-- Divider -->
            <hr class="sidebar-divider">
			<li class="nav-item">
                <a class="nav-link" href="index.php">
                    <span>Beranda</span></a>
            </li>
         
		   <!-- Divider -->
            <hr class="sidebar-divider">

            <!-- Heading -->
            <div class="sidebar-heading">
                Aplikasi
            </div>

            <!-- Nav Item - Pages Collapse Menu -->
            <li class="nav-item">
                <a class="nav-link" href="ambilnosur.php">
                    <span>Ambil Nomor Surat</span></a>
            </li>
			<li class="nav-item">
                <a class="nav-link" href="daftarnosur.php">
                    <span>Daftar Nomor Surat</span></a>
            </li>
            <!-- Divider -->
            <hr class="sidebar-divider">

            <!-- Heading -->
            <div class="sidebar-heading">
                Panduan
            </div>

            <!-- Nav Item - Charts -->
            <li class="nav-item">
                <a class="nav-link" href="pola.php">
                    <span>Pola Klasifikasi Surat</span></a>
            </li>

            <!-- Nav Item - Tables -->
            <li class="nav-item">
                <a class="nav-link" href="panduan.php">
                    <span>Panduan Penggunaan</span></a>
            </li>

            <!-- Divider -->
            <hr class="sidebar-divider d-none d-md-block">

            <!-- Sidebar Toggler (Sidebar) -->
            <div class="text-center d-none d-md-inline">
                <button class="rounded-circle border-0" id="sidebarToggle"></button>
            </div>

        </ul>
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
					<?php include ('topbar.html');?>

                </nav>
                <!-- End of Topbar -->

                <!-- Begin Page Content -->
                <div class="container-fluid">

                    <!-- Page Heading -->
                    <h1 class="h3 mb-4 text-gray-800">Ganti Passsword</h1>
					<form class="user" action="changepw_save.php" method="post">
					<div class="form-group">
						<div class="col-sm-4 mb-sm-0">
                            <input type="password" class="form-control" id="old" name="old" placeholder="Password Lama" required="required" >
                        </div>
					</div>
					<div class="form-group">
						<div class="col-sm-4 mb-sm-0">
                            <input type="password" class="form-control" id="new" name="new" placeholder="Password Baru" required="required" >
                        </div>
					</div>
					<div class="form-group">
						<div class="col-sm-4 mb-sm-0">
                            <input type="password" class="form-control" id="rep" name="rep" placeholder="Ulang Password Baru" required="required" >
                        </div>
					</div>
					
					<div class="form-group">
                        <div class="col-sm-4 mb-sm-0">
                          <button class="btn btn-danger" type="button" onclick="window.history.back()">Kembali</button>
						  <button class="btn btn-primary" type="reset">Ulang</button>
                          <button type="submit" class="btn btn-success" name="simpan" id="simpan" value="simpan">Simpan</button>
                        </div>
                    </div>
					
					</form>
                </div>
                <!-- /.container-fluid -->

            </div>
            <!-- End of Main Content -->

            <!-- Footer -->
			<?php include('footer.html');?>
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