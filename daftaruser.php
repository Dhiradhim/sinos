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

        $q_nosur = mysqli_query($con, "SELECT u.nip,u.nama,u.id,u.aktif,j.jabatan from user u left join jabatan j on u.id_jabatan=j.id WHERE NOT j.id='1' ORDER BY u.aktif asc, j.id asc") or die(mysqli_connect_error());
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
                    <h1 class="h3 mb-4 text-gray-800">Daftar User</h1>

					<div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>No.</th>
                                            <th>Nama</th>
                                            <th>NIP</th>
                                            <th>Jabatan</th>
                                            <th>Aktif</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tfoot>
                                        <tr>
                                            <th>No.</th>
                                            <th>Nama</th>
                                            <th>NIP</th>
                                            <th>Jabatan</th>
                                            <th>Aktif</th>
                                            <th>Action</th>
                                        </tr>
                                    </tfoot>
									<tbody>
									<?php do { ?>
                                        <tr 
                                        <?php if ($row_nosur['aktif']=='0') 
                                            {
                                                $aktif='Aktif';
                                            }
                                            else 
                                            { 
                                                echo 'style="background-color:#f7c6c6"';
                                                $aktif= 'Nonaktif';
                                            } ?>>
                                            <td><?php echo $count;?>
                                            <td><?php echo $row_nosur['nama'];?></td>
                                            <td><?php echo $row_nosur['nip'];?></td>
                                            <td><?php echo $row_nosur['jabatan'];?> </td>
                                            <td><?php echo $aktif;?> </td>
                                            <td>
                                                <a href="#" class="btn btn-info btn-circle btn-sm" data-bs-toggle="tooltip" data-bs-placement="top" title="Edit Data">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="hapus_user.php?id=<?=$row_nosur['id']?>" type="button" class="btn btn-danger btn-circle btn-sm" data-bs-toggle="tooltip" data-bs-placement="top" title="Delete Data" onclick="return confirm('Anda yakin akan menghapus Data ini?')">
                                                    <i class="fas fa-times"></i>
                                                </a>
                                                
                                            </td>							
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
