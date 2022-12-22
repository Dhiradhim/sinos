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
                    <h1 class="h3 mb-4 text-gray-800">Sisip Nomor Surat</h1>
					<form class="user" action="sisipnosursave.php" method="post">
					<div class="form-group row">
					<?php 
						$nip=$_SESSION['nip'];
						if ($nip=="admin")
						{
                        $q_nama1="SELECT nip,nama FROM user WHERE aktif=0";
                        $nama1 = mysqli_query ($con, $q_nama1);
                        $row_nama1 = mysqli_fetch_assoc($nama1);
					?>						
                        <div class="col-sm-3 mb-sm-0">
                            <select class="form-control" required="required" id="nip" name="nip">
								<option value="" hidden>Pilih</option>
								<?php
                                do {
                                ?>
                                <option value="<?=$row_nama1['nip']?>"><?=$row_nama1['nama'];?></option>
                                <?php 
                                } while ($row_nama1 = mysqli_fetch_assoc($nama1));?>
							</select>
                        </div>
					<?php 
					} else {
						?>
						<input type="hidden" class="form-control" id="nip" name="nip" placeholder="Perihal" value="<?=$nip;?>" >
					<?php
					}
					?>
                    <div>
                        <input type="date" class="form-control" id="tanggal" name="tanggal" placeholder="Tanggal" required="required" > 
                    </div>
                        <div class="col-sm-2 mb-sm-0">
                            <select class="form-control" required="required" id="kd" name="kd">
								<option value="" hidden>Kode Surat</option>
								<option value="OT">OT</option>
								<option value="HM">HM</option>
								<option value="KP">KP</option>
								<option value="KU">KU</option>
								<option value="KS">KS</option>
								<option value="PL">PL</option>
								<option value="HK">HK</option>
								<option value="PP">PP</option>
								<option value="PB">PB</option>
								<option value="PS">PS</option>
							</select>
                        </div>
                        <div class="col-sm-1 mb-sm-0">
                            <select class="form-control" required="required" id="kd1" name="kd1">
								<option value="" hidden>-</option>
								<option value="00">00</option>
								<option value="01">01</option>
								<option value="02">02</option>
								<option value="03">03</option>
								<option value="04">04</option>
								<option value="05">05</option>
								<option value="06">06</option>
								<option value="07">07</option>
								<option value="08">08</option>
								<option value="09">09</option>
							</select>
                        </div>
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
                        <!-- <div class="col-sm-2 mb-sm-0">
                            <select class="form-control" id="sk" name="sk">
								<option value="0">Bukan SK</option>
								<option value="1">SK</option>
							</select>
                        </div> -->
						<div class="form-check">
                            <input class="form-check-input" id="flexCheckDefault" name="sk" type="checkbox" value="1">
                            <label class="form-check-label" for="flexCheckDefault">SK</label>
                        </div>
						
                    </div>
					<div class="form-group">
						<div>
                            <input type="text" class="form-control" id="hal" name="hal" placeholder="Perihal" required="required" >
                        </div>
                    </div>
					
					<div class="form-group">
                        <div>
                          <button class="btn btn-danger" type="button" onclick="window.history.back()">Kembali</button>
						  <button class="btn btn-primary" type="reset">Ulang</button>
                          <button type="submit" class="btn btn-success">Simpan</button>
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