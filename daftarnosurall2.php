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

    <style>
        label.actual{
            background-color: #0356fc;
            color: white;
            padding: 0.3rem;
            border-radius: 0.5rem;
            cursor: pointer;
            }
    </style>

	<?php
    $nip=$_SESSION['nip'];
	$year=$_GET['tahun'];
        $q_nosur = mysqli_query($con, "SELECT nosur.id, nosur.no, nosur.nip, nosur.file, nosur.tanggal, nosur.hal, nosur.tujuan, user.nip, user.nama from nosur inner join user on nosur.nip=user.nip WHERE file='1' and YEAR(nosur.tanggal)=$year ORDER BY nosur.id ASC") or die(mysqli_connect_error());
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
                    <?php include('topbar.html');?>

                </nav>
                <!-- End of Topbar -->

                <!-- Begin Page Content -->
                <div class="container-fluid">

                    <!-- Page Heading -->
                    <h1 class="h3 mb-4 text-gray-800">Daftar Seluruh Nomor Surat <br><strong>[Belum Upload File]</strong></h1>

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
                                            <th>Tujuan</th>
                                            <th>File</th>
                                            <?php if($nip=='admin'){ ?>
                                            <th>Action</th>
                                            <?php } ?>
                                        </tr>
                                    </thead>
                                    <tfoot>
                                        <tr>
                                            <th>No.</th>
                                            <th>No. Surat</th>
                                            <th>Nama</th>
                                            <th>Tanggal</th>
                                            <th>Perihal</th>
                                            <th>Tujuan</th>
                                            <th>File</th>
                                            <?php if($nip=='admin'){ ?>
                                            <th>Action</th>
                                            <?php } ?>
                                        </tr>
                                    </tfoot>
									<tbody>
									<?php do { ?>
                                        <tr <?php if($row_nosur['file']==1) { ?>style="background-color:#f7c6c6" <?php } ?>>
                                            <td><?php echo $count;?>
                                            <td><?php echo $row_nosur['no'];?></td>
                                            <td><?php echo $row_nosur['nama'];?></td>
                                            <td><?php echo $row_nosur['tanggal'];?></td>
                                            <td><?php echo $row_nosur['hal'];?> </td>
                                            <td><?php echo $row_nosur['tujuan'];?> </td>
                                            <td width="8%">
                                            <?php if($nip=='admin'){ ?>
                                                <a href="upload_data.php?id=<?=$row_nosur['id']?>" class="btn btn-primary btn-circle btn-sm" data-bs-toggle="tooltip" data-bs-placement="top" title="Upload File" type="button">
                                                        <i class="fas fa-upload"></i>
                                                </a>
                                                <?php }
												 if($row_nosur['file']==1) { ?>
                                                    <button class="btn btn-warning btn-circle btn-sm" data-bs-toggle="tooltip" data-bs-placement="top" title="Belum ada File" type="button">
                                                        <i class="fas fa-exclamation"></i>
                                                    </button>
												 <?php } 
												 else if (empty($row_nosur['file']))
												 { ?>
													
												<?php
												} else
												{ ?>
												<button class="btn btn-success btn-circle btn-sm" data-bs-toggle="tooltip" data-bs-placement="top" title="Lihat File" onclick=" window.open('<?=$row_nosur['file']?>','_blank')">
                                                    <i class="fas fa-eye"></i>
                                                </button>	
												<?php }
												?>
											</td>
                                            <?php if($nip=='admin'){ ?>
                                            <td>
                                                <a href="editnosur.php?id=<?=$row_nosur['id']?>" class="btn btn-info btn-circle btn-sm" data-bs-toggle="tooltip" data-bs-placement="top" title="Edit Data" onclick="#">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="hapus_data.php?id=<?=$row_nosur['id']?>" type="button" class="btn btn-danger btn-circle btn-sm" data-bs-toggle="tooltip" data-bs-placement="top" title="Delete Data" onclick="return confirm('Anda yakin akan menghapus Data ini?')">
                                                    <i class="fas fa-times"></i>
                                                </a>
                                            </td>	
                                            <?php } ?>								
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