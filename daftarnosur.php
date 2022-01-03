<?php  
include ('koneksi.php');
session_start();  
  
if(!$_SESSION['nip'])  
{  
  
    header("Location: login.php");//redirect to login page to secure the welcome page without login access.  
}  
  
?> 
<!DOCTYPE html>
<html lang="en">

<head>
        <script>
            function setup() {
                document.getElementById('buttonid').addEventListener('click', openDialog);
                function openDialog() {
                    document.getElementById('fileid').click();
                }
                document.getElementById("fileid").onchange = function() {
				document.getElementById("form").submit();
				}
            }
        </script> 
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
	<?php
	$q_nosur = mysqli_query($con, "SELECT nosur.id, nosur.no, nosur.nip, nosur.file, nosur.tanggal, nosur.hal, user.nip, user.nama from nosur inner join user on nosur.nip=user.nip ORDER BY no DESC") or die(mysqli_connect_error());
	$row_nosur = mysqli_fetch_assoc($q_nosur);
	$run = mysqli_num_rows($q_nosur);
	$count = 1;
	?>
    <!-- Custom styles for this page -->
    <link href="vendor/datatables/dataTables.bootstrap4.min.css" rel="stylesheet">
</head>

<body id="page-top" onload="setup()">

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
			<li class="nav-item active">
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
                    <?php include('topbar.html');?>

                </nav>
                <!-- End of Topbar -->

                <!-- Begin Page Content -->
                <div class="container-fluid">

                    <!-- Page Heading -->
                    <h1 class="h3 mb-4 text-gray-800">Daftar Nomor Surat</h1>

					<div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>No.</th>
                                            <th>No. Surat</th>
                                            <th>Nama</th>
                                            <th>Tanggal</th>
                                            <th>Perihal</th>
                                            <th>File</th>
                                        </tr>
                                    </thead>
                                    <tfoot>
                                        <tr>
                                            <th>No.</th>
                                            <th>No. Surat</th>
                                            <th>Nama</th>
                                            <th>Tanggal</th>
                                            <th>Perihal</th>
                                            <th>File</th>
                                        </tr>
                                    </tfoot>
									<tbody>
									<?php do { ?>
                                        <tr>
                                            <td><?php echo $count; ?></td>
                                            <td><?php echo $row_nosur['no'];?></td>
                                            <td><?php echo $row_nosur['nama'];?></td>
                                            <td><?php echo $row_nosur['tanggal'];?></td>
                                            <td><?php echo $row_nosur['hal'];?> </td>
                                            <td>
												<form id="form" method="post" action="upload_berkas.php" enctype="multipart/form-data">
													<input type="file" id="fileid" name='file' hidden/>
													<input type="hidden" name='id' value="<?=$row_nosur['id']?>">
													<input class="btn btn-primary" id='buttonid' type='button' value='Upload' />
												</form>		
												<?php 
												if (empty($row_nosur['file']))
												{ ?>
													
												<?php
												} else
												{ ?>
													<button class="btn btn-success" onclick=" window.open('<?=$row_nosur['file']?>','_blank')">Lihat</button>	
												<?php }
												?>
																							
                                        </tr>
										<?php 
											$count++;
											} while ($row_nosur = mysqli_fetch_assoc($q_nosur)); 
										?>
									</tbody>
                                </table>
							</div>
					</div>
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
	
	<!-- Page level plugins -->
    <script src="vendor/datatables/jquery.dataTables.min.js"></script>
    <script src="vendor/datatables/dataTables.bootstrap4.min.js"></script>

    <!-- Page level custom scripts -->
    <script src="js/demo/datatables-demo.js"></script>

</body>

</html>