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
                        $nip=$_SESSION['nip'];

                        $q_nama2="SELECT nip,nama FROM user WHERE aktif=0";
                        $nama2 = mysqli_query ($con, $q_nama2);
                        $row_nama2 = mysqli_fetch_assoc($nama2);
					?>						
                        <div class="col-sm-3 mb-sm-0">
                            <input type="hidden" readonly class="form-control" id="id" name="id" value="<?=$id;?>" >
                            <input type="hidden" readonly class="form-control" id="no_urut" name="no_urut" value="<?=$no_urut;?>" >
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
                        <div class="col-sm-2 mb-sm-0">
                            <input type="text" readonly class="form-control" id="no_surat1" name="no_surat1" placeholder="Perihal" value="<?=$no_surat[0];?>/<?=$no_surat[1];?>/" >
                        </div>
                        <div class="col-sm-2 mb-sm-0">							
                            <select class="form-control" required="required" id="kd" name="kd">
								<option value="" hidden>Kode Surat</option>
								<option value="OT" <?php if ($kode[0]=='OT'){ echo "selected";}?>>OT</option>
								<option value="HM" <?php if ($kode[0]=='HM'){ echo "selected";}?>>HM</option>
								<option value="KP" <?php if ($kode[0]=='KP'){ echo "selected";}?>>KP</option>
								<option value="KU" <?php if ($kode[0]=='KU'){ echo "selected";}?>>KU</option>
								<option value="KS" <?php if ($kode[0]=='KS'){ echo "selected";}?>>KS</option>
								<option value="PL" <?php if ($kode[0]=='PL'){ echo "selected";}?>>PL</option>
								<option value="HK" <?php if ($kode[0]=='HK'){ echo "selected";}?>>HK</option>
								<option value="PP" <?php if ($kode[0]=='PP'){ echo "selected";}?>>PP</option>
								<option value="PB" <?php if ($kode[0]=='PB'){ echo "selected";}?>>PB</option>
								<option value="PS" <?php if ($kode[0]=='PS'){ echo "selected";}?>>PS</option>
							</select>
                        </div>
                        <div class="col-sm-1 mb-sm-0">
                            <select class="form-control" required="required" id="kd1" name="kd1">
								<option value="" hidden>-</option>
								<option value="00" <?php if ($kode[1]=='00'){ echo "selected";}?>>00</option>
								<option value="01" <?php if ($kode[1]=='01'){ echo "selected";}?>>01</option>
								<option value="02" <?php if ($kode[1]=='02'){ echo "selected";}?>>02</option>
								<option value="03" <?php if ($kode[1]=='03'){ echo "selected";}?>>03</option>
								<option value="04" <?php if ($kode[1]=='04'){ echo "selected";}?>>04</option>
								<option value="05" <?php if ($kode[1]=='05'){ echo "selected";}?>>05</option>
								<option value="06" <?php if ($kode[1]=='06'){ echo "selected";}?>>06</option>
								<option value="07" <?php if ($kode[1]=='07'){ echo "selected";}?>>07</option>
								<option value="08" <?php if ($kode[1]=='08'){ echo "selected";}?>>08</option>
								<option value="09" <?php if ($kode[1]=='09'){ echo "selected";}?>>09</option>
							</select>
                        </div>
                        <?php if (!empty($kode[2])){ ?>
                        <div class="col-sm-1 mb-sm-0">
                            <select class="form-control" id="kd2" name="kd2">
								<option value="-">-</option>
								<option value="1" <?php if ($kode[2]=='1'){ echo "selected";}?>>1</option>
								<option value="2" <?php if ($kode[2]=='2'){ echo "selected";}?>>2</option>
								<option value="3" <?php if ($kode[2]=='3'){ echo "selected";}?>>3</option>
								<option value="4" <?php if ($kode[2]=='4'){ echo "selected";}?>>4</option>
								<option value="5" <?php if ($kode[2]=='5'){ echo "selected";}?>>5</option>
								<option value="6" <?php if ($kode[2]=='6'){ echo "selected";}?>>6</option>
								<option value="7" <?php if ($kode[2]=='7'){ echo "selected";}?>>7</option>
								<option value="8" <?php if ($kode[2]=='8'){ echo "selected";}?>>8</option>
								<option value="9" <?php if ($kode[2]=='9'){ echo "selected";}?>>9</option>
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
                        <div class="col-sm-2 mb-sm-0">
                            <?php if ($no_surat[3]=='SK'){?>
                                <input type="hidden" readonly class="form-control" id="no_surat2" name="no_surat2x"  value="/<?=$no_surat[4];?>/<?=$no_surat[5];?>" >
                                <input type="text" readonly class="form-control" id="no_surat2x" name="no_surat2"  value="/<?=$no_surat[3];?>/<?=$no_surat[4];?>/<?=$no_surat[5];?>" >
                            <?php } else { ?>
                                <input type="text" readonly class="form-control" id="no_surat2" name="no_surat2x"  value="/<?=$no_surat[3];?>/<?=$no_surat[4];?>" >
                                <?php } ?> 
                        </div>
						<div class="form-check">
                            <input class="form-check-input" id="flexCheckDefault" <?php if ($no_surat[3]=='SK'){ echo 'checked'; } ?> name="sk" type="checkbox" value="1">
                            <label class="form-check-label" for="flexCheckDefault">SK</label>
                        </div>
						
                    </div>
					<div class="form-group">
						<div>
                            <input type="text" class="form-control" id="hal" name="hal" placeholder="Perihal" required="required" value="<?=$hal?>">
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