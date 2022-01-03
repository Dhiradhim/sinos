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
            <li class="nav-item active">
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
                    <h1 class="h3 mb-4 text-gray-800">POLA KLASIFIKASI SURAT</h1>

                </div>
				
				    <div class="row">
					
                        <div class="col-lg-6">
                            <!-- Collapsable Card Example -->
                            <div class="card shadow mb-4">
                                <!-- Card Header - Accordion -->
                                <a href="#collapseCardExample1" class="d-block card-header py-3" data-toggle="collapse"
                                    role="button" aria-expanded="true" aria-controls="collapseCardExample1">
                                    <h6 class="m-0 font-weight-bold text-primary">OT. Organisasi dan Tata Laksana</h6>
                                </a>
                                <!-- Card Content - Collapse -->
                                <div class="collapse" id="collapseCardExample1">
                                    <div class="card-body">
										<strong>00. ORGANISASI</strong><br>
										Surat-surat yang berhubungan dengan pembentukan, perubahan organisasi, uraian pekerjaan dan pembahasannya mulai dari awal sampai akhir dan jalur pertanggung jawabannya.<br><br>
										<strong>01. TATA LAKSANA</strong><br>
										<strong>01.1 PERENCANAAN</strong><br>
										Surat-surat yang berhubungan dengan penyusunan perencanaan/program kerja oleh unit-unit kerja Mahkamah Agung secara keseluruhan, termasuk segala jenis pertemuan dalam rangka penentuan kebijaksanaan perencanaan.<br>
										<strong>01.2. LAPORAN</strong><br>
										Surat-surat yang berhubungan dengan laporan umum, monitoring, evaluasi dan unit kerja, baik laporan :<br>
										- bulanan,<br>
										- triwulan,<br>
										- semester, dan<br>
										- tahunan.<br>
										<strong>01.3. PENYUSUNAN PROSEDUR KERJA</strong><br>
										Surat-surat yang berkenaan dengan penyusunan sistim, prosedur, pedoman, petunjuk pelaksanaan, tata kerja dan hubungan kerja.<br>
										<strong>01.4. PENYUSUNAN PEMBAKUAN SARANA KERJA</strong><br>
										Surat-surat yang berhubungan dengan penyusunan pembakuan sarana kerja yakni penentuan kualitas dan kuantitas yang meliputi:<br>
										- ukuran,<br>
										- jenis,<br>
										- merek dan sebagainya.
                                    </div>
                                </div>
                            </div>

							<div class="card shadow mb-4">
                                <!-- Card Header - Accordion -->
                                <a href="#collapseCardExample2" class="d-block card-header py-3" data-toggle="collapse"
                                    role="button" aria-expanded="true" aria-controls="collapseCardExample2">
                                    <h6 class="m-0 font-weight-bold text-primary">HM. Kehumasan</h6>
                                </a>
                                <!-- Card Content - Collapse -->
                                <div class="collapse" id="collapseCardExample2">
                                    <div class="card-body">
										<strong>00. PENERANGAN</strong><br>
										Surat-surat yang berkenaan dengan segala kegiatan yang berkenaan dengan penerangan terhadap masyarakat tentang kegiatan Mahkamah Agung RI, termasuk di dalamnya:<br>
										- konperensi pers,<br>
										- pameran,<br>
										- wawancara,<br>
										- dan penerangan dalam media massa lainnya.<br><br>
										<strong>01. HUBUNGAN DAN KEPROTOKOLAN</strong><br>
										<strong>01.1. HUBUNGAN</strong><br>
										Surat-surat yang berkenaan dengan segala kegiatan intern Mahkamah Agung RI, dan antara Mahkamah Agung RI dengan pihak lain, baik dalam maupun luar negeri dalam bidang kehumasan, koordinasi, antara lain :<br>
										- bakohumas,<br>
										- hearing DPR,<br>
										- kelompok kerja (POKJA),<br>
										- dan organisasi-organisasi mass media.<br>
										<strong>01.2. KEPROTOKOLAN</strong><br>
										Surat-surat yang berkenaan dengan masalah keprotokolan, seperti:<br>
										- tamu-tamu pimpinan Mahkamah Agung RI, baik dalam maupun luar negeri.<br>
										- Kunjungan kerja pimpinan dan pejabat Mahkamah Agung RI,<br>
										- Upacara hari nasional, dan<br>
										- HUT Mahkamah Agung RI.<br><br>
										<strong>02. DOKUMENTASI, KEPUSTAKAAN DAN TEKNOLOGI INFORMASI</strong><br>
										<strong>02.1. DOKUMENTASI</strong><br>
										Surat-surat yang berkenaan dengan kegiatan yang berhubungan dengan penyediaan/ pengumpulan bahan/dokumentasi, termasuk penyebarannya.<br>
										<strong>02.2. KEPUSTAKAAN</strong><br>
										Surat-surat yang berkenaan dengan kegiatan yang berhubungan dengan penyediaan, pengumpulan, dan penataan bahan-bahan kepustakaan.<br>
										<strong>02.3. TEKNOLOGI INFORMASI</strong><br>
										Surat-surat yang berkenaan dengan kegiatan yang berhubungan dengan perencanaan, penyediaan, pemeliharaan, pengelolaan dan hal- hal lain yang berkaitan dengan teknologi informasi.

                                    </div>
                                </div>
                            </div>

                            <div class="card shadow mb-4">
                                <!-- Card Header - Accordion -->
                                <a href="#collapseCardExample3" class="d-block card-header py-3" data-toggle="collapse"
                                    role="button" aria-expanded="true" aria-controls="collapseCardExample3">
                                    <h6 class="m-0 font-weight-bold text-primary">KP. Kepegawaian</h6>
                                </a>
                                <!-- Card Content - Collapse -->
                                <div class="collapse" id="collapseCardExample3">
                                    <div class="card-body">
										<strong>00. PENGADAAN</strong><br>
										<strong>00.1. FORMASI</strong><br>
										Surat-surat yang berkenaan dengan perencanaan pengadaan pegawai, nota usul formasi, sampai dengan persetujuan termasuk di dalamnya besetting.<br>
										<strong>00.2. PENERIMAAN</strong><br>
										Surat-surat yang berkenaan dengan penerimaan pegawai baru, mulai dan pengumuman penerimaan, panggilan testing/psikotes/clearance test, sampai dengan pengumuman yang diterima, termasuk didalamnya pegawai honorer, seperti:<br>
										- Satpam,<br>
										- Petugas Pramusaji,<br>
										- Supir.<br>
										<strong>00.3. PENGANGKATAN</strong><br>
										Surat-surat yang berkenaan dengan seluruh proses pengangkatan, dan penempatan calon pegawai (CPNS) sampai dengan menjadi pegawai (PNS), mulai dari persyaratan, pemeriksaan kesehatan dan keteranganketerangan lainnya yang berhubungan dengan pengangkatan.<br>
										<br><strong>01. TATA USAHA KEPEGAWAIAN</strong><br>
										<strong>01.1. IZIN/DISPENSASI</strong><br>
										Surat-surat yang berkenaan dengan izin tidak masuk kerja atas permintaan yang diajukan oleh pegawai yang bersangkutan, maupun dispensasi yang diajukan oleh instansi lain termasuk tugas pada instansi lain baik tugas belajar maupun tugas di luar negeri bagi pegawai Mahkamah Agung RI.<br>
										<strong>01.2. KETERANGAN</strong><br>
										Surat-surat yang berkenaan dengan keterangan pegawai dan keluarganya, termasuk surat-surat yang berkaitan dengan NIP, KARPEG, KARSU/ KARSI dan data pegawai/pejabat.<br>
										<br><strong>02. PENILAIAN DAN HUKUMAN</strong><br>
										<strong>02.1 PENILAIAN</strong><br>
										Surat-surat yang berkenaan dengan penilaian pelaksanaan pekerjaan, disiplin pegawai, pemalsuan administrasi kepegawaian, rehabi litasi dan pemulihan nama baik.<br>
										<strong>02.2 HUKUMAN</strong><br>
										Surat-surat yang berkenaan dengan hukuman pegawai, meliputi:<br>
										- teguran tertulis,<br>
										- pernyataan tidak puas secara tertulis,<br>
										- penundaan kenaikan gaji berkala untuk paling lama 1 (satu) tahun.<br>
										- penurunan gaji sebesar 1 (satu) kali Kenaikan Gaji Berkala untuk paling lama 1 (satu) tahun.<br>
										- penundaan kenaikan pangkat untuk paling lama 1 (satu) tahun,<br>
										- penurunan pangkat pada pangkat yang setingkat lebih rendah untuk paling lama 1 (satu) tahun.<br>
										- Pembebasan dan jabatan,<br>
										- Pemberhentian dengan hormat tidak atas permintaan sendiri sebagai PNS/Tenaga Teknis/Tenaga Fungsional.<br>
										- Pemberhentian tidak dengan hormat sebagai PNS.<br>
										<br><strong>03. PEMBINAAN MENTAL</strong><br>
										Surat-surat yang berkenaan dengan pembinaan mental pegawai, termasukvdidalamnya pembinaan kerokhanian.<br>
										<br><strong>04. MUTASI</strong><br>
										<strong>04.1. KEPANGKATAN</strong><br>
										Surat-surat yang berkenaan dengan kenaikan pangkat/golongan, termasuk didalamnya ujian dinas, ujian penyesuaian ijazah, dan daftar urut kepangkatan.<br>
										<strong>04.2. KENAIKAN GAJI BERKALA</strong><br>
										Surat-surat yang berkenaan dengan kenaikan gaji berkala.<br>
										<strong>04.3. PENYESUAIAN MASA KERJA</strong><br>
										Surat-surat yang berkenaan dengan penyesuaian masa kerja untuk perubahan ruang gaji dan impassing.<br>
										<strong>04.4. PENYESUAIAN TUNJANGAN KELUARGA.</strong><br>
										Surat-surat yang berkenaan dengan penyesuaian tunjangan keluarga.<br>
										<strong>04.5. ALIH TUGAS</strong><br>
										Surat-surat yang berkenaan dengan alih tugas bagi para pelaksana/staf, perpindahan dalam rangka pemantapan tugas kerja termasuk mengenai fasilitasnya.<br>
										<strong>04.6. JABATAN STRUKTURAL/FUNGSIONAL</strong><br>
										Surat-surat yang berkenaan dengan pengangkatan dan pemberhentian dalam jabatan structural/ fungsional, termasuk tunjangan jabatan sewaktu penugasan atau pemberian kuasa untuk menjabat sementara.<br>
										<br><strong>05. KESEJAHTERAAN</strong><br>
										<strong>05.1. KESEHATAN</strong><br>
										Surat-surat yang berkenaan dengan penyelenggaraan kesehatan bagi pegawai, meliputi:<br>
										- asuransi kesehatan,<br>
										- general check up bagi pimpinan dan pejabat.<br>
										<strong>05.2. CUTI</strong><br>
										Surat-surat yang berkenaan dengan cuti pegawai, meliputi:<br>
										- cuti sakit,<br>
										- cuti hamil/bersalin, dan<br>
										- cuti diluar tanggungan negara.<br>
										<strong>05.3. REKREASI DAN OLAH RAGA</strong><br>
										Surat-surat yang berkenaan dengan rekreasi dan olah raga.<br>
										<strong>05.4. BANTUAN SOSIAL</strong><br>
										Surat-surat yang berkenaan dengan pemberian bantuan/tunjangan sosial kepada pegawai dan keluarga yang mengalami musibah, termasuk ucapan bela sungkawa.<br>
										<strong>05.5. KOPERASI</strong><br>
										Surat-surat yang berkenaan dengan organisasi koperasi termasuk didalamnya masalah pengurusan kebutuhan bahan pokok.<br>
										<strong>05.6. PERUMAHAN</strong><br>
										Surat-surat yang berkenaan dengan perumahan pegawai, pejabat structural/fungsional, pimpinan dan hakim agung.<br>
										<strong>05.7. ANTAR JEMPUT</strong><br>
										Surat-surat yang berkenaan dengan transportasi pegawai.<br>
										<strong>05.8. PENGHARGAAN</strong><br>
										Surat-surat yang berkenaan dengan penghargaan, tanda jasa, piagam, satya lencana, dan sejenisnya.<br>
										<br><strong>06. PEMUTUSAN HUBUNGAN KERJA</strong><br>
										Surat-surat yang berkenaan dengan pensiun pegawai, termasuk jaminan-jaminan asuransi karena berhenti atas permintaan sendiri, berhenti dengan hormat bukan karena hukuman, pindah/keluar dari MARI dan meninggal dunia.<br>
                                    </div>
                                </div>
                            </div>

                            <div class="card shadow mb-4">
                                <!-- Card Header - Accordion -->
                                <a href="#collapseCardExample4" class="d-block card-header py-3" data-toggle="collapse"
                                    role="button" aria-expanded="true" aria-controls="collapseCardExample4">
                                    <h6 class="m-0 font-weight-bold text-primary">KU. Keuangan</h6>
                                </a>
                                <!-- Card Content - Collapse -->
                                <div class="collapse" id="collapseCardExample4">
                                    <div class="card-body">
										<strong>00. AKUTANSI</strong><br>
										Surat-surat yang berkenaan dengan, penyiapan bahan pelaksanaan dan pembinaan pembukuan keuangan, serta penyusunan perhitungan anggaran.<br>
										<br><strong>01. PELAKSANAAN ANGGARAN</strong><br>
										Surat-surat yang berkenaan dengan penyiapan bahan bimbingan dalam pelaksanaan penggunaan anggaran dan pertanggung jawaban keuangan.<br>
										<br><strong>02. VERIFIKASI DAN TUNTUTAN GANTI RUGI</strong><br>
										Surat-surat yang berkenaan dengan penyiapan bahan pencatatan, penelitian,pembinaan, dan penyusunan laporan tentang verifikasi dan tuntutan ganti rugi.<br>
										<br><strong>03. PERBENDAHARAAN</strong><br>
										Surat-surat yang berkenaan dengan penyiapan bahan bimbingan dalam ketatausahaan perbendaharaan, penyelesaiaan masalah perbendaharaan, dan pelaksanaan pembinaan bendaharawan.<br>
										<br><strong>04. PENDAPATAN NEGARA</strong><br>
										<strong>04.1. PAJAK</strong><br>
										Surat-surat yang berkenaan dengan pendapatan negara dan hasil pajak yang meliputi:<br>
										-MPO (Menghitung Pajak Orang).<br>
										-PPN (Pajak Pendapatan Negara).<br>
										-Pajak Jasa.<br>
										-PPH (Pajak Pendapatan Penghasilan).<br>
										-PPN (Pajak Pertambahan Nilai).<br>
										-dan pajak lainnya.<br>
										<strong>04.2. BUKAN PAJAK</strong><br>
										Surat-surat yang berkenaan dengan pendapatan negara dan hasil bukan pajak yang meliputi penerimaan dan:<br>
										-biaya perkara.<br>
										-biaya salinan putusan.<br>
										-biaya sewa dari inventaris Negara.<br>
										-hasil penjualan barang-barang inventaris yang dihapus.<br>
										-dan penerimaan Negara bukan pajak lainnya.<br>
										<br><strong>05. PERBANKAN</strong><br>
										Surat-surat yang berkenaan dengan perbankan antara lain : pembukaan rekening, spesement tandatangan, valuta asing, rekening koran dan prodak perbankan lainnya<br>
										<br><strong>06. SUMBANGAN/BANTUAN</strong><br>
										Surat-surat yang berkenaan dengan permintaan, pemberian sumbangan/bantuan khusus diluar tugas pokok Mahkamah Agung, seperti:<br>
										- bencana alam.<br>
										- kebakaran.<br>
										- banjir.<br>
										- qurban,<br>
										- pekan Olah Raga.<br>
										- dan lain sebagainya.
                                    </div>
                                </div>
                            </div>
							
                            <div class="card shadow mb-4">
                                <!-- Card Header - Accordion -->
                                <a href="#collapseCardExample5" class="d-block card-header py-3" data-toggle="collapse"
                                    role="button" aria-expanded="true" aria-controls="collapseCardExample5">
                                    <h6 class="m-0 font-weight-bold text-primary">KS. Kesekretariatan</h6>
                                </a>
                                <!-- Card Content - Collapse -->
                                <div class="collapse" id="collapseCardExample5">
                                    <div class="card-body">
										<strong>00.KERUMAHTANGGAAN</strong><br>
										Surat-surat yang berkenaan dengan :<br>
										- penggunaan fasilitas.<br>
										- ketertiban dan keamanan.<br>
										- konsumsi.<br>
										- pakaian dinas.<br>
										- papan nama.<br>
										- stempel.<br>
										- lambang.<br>
										- alamat kantor dan pejabat.<br>
										- telekomunikasi, listrik, air<br>
										- dan lain sebagainya.

                                    </div>
                                </div>
                            </div>

                        </div>
						
                        <div class="col-lg-6">
                            <!-- Collapsable Card Example -->
                            <div class="card shadow mb-4">
                                <!-- Card Header - Accordion -->
                                <a href="#collapseCardExample6" class="d-block card-header py-3" data-toggle="collapse"
                                    role="button" aria-expanded="true" aria-controls="collapseCardExample6">
                                    <h6 class="m-0 font-weight-bold text-primary">PL. Perlengkapan</h6>
                                </a>
                                <!-- Card Content - Collapse -->
                                <div class="collapse" id="collapseCardExample6">
                                    <div class="card-body">
										<strong>01.GEDUNG DAN RUMAH DINAS</strong><br>
										Surat-surat yang berkenaan dengan perencanaa, pengadaan, pelelangan, pendistribusian, pemeliharaan dan penghapusan, antara lain :<br>
										- bangunan kantor,<br>
										- rumah dinas.<br>
										- mes.<br>
										- pos jaga.<br>
										- persetujuan gambar gedung.<br>
										- dan lain sebagainya.<br><br>
										<strong>02.TANAH</strong><br>
										Surat-surat yang berkenaan dengan perencanaa, pengadaan/ pelelangan, pemeliharaan, penghapusan dan tukar guling tanah.<br><br>
										<strong>03.ALAT KANTOR</strong><br>
										Surat-surat yang berkenaan dengan perencanaan, pengadaan, pelelangan, pendistribusian, pemeliharaan dan penghapusan, antara lain :<br>
										- ATK (Alat Tulis Kantor).<br>
										- formulir-formulir.<br>
										- dan lain-lain.<br><br>
										<strong>04.MESIN KANTOR/ALAT-ALAT ELEKTRONIK</strong><br>
										Surat-surat yang berkenaan dengan perencanaan, pengadaan, pelelangan, pendistribusian, pemeliharaan dan penghapusan, antara lain :<br>
										- AC<br>
										- Amplifier<br>
										- Foto copy<br>
										- Kamera<br>
										- Kalkulator/mesin hitung<br>
										- Mesin ketik<br>
										- Overhead proyector<br>
										- Proyektor film<br>
										- Laptop<br>
										- Komputer/PC<br>
										- radio<br>
										- slide<br>
										- mesin.stensil<br>
										- tape recorder<br>
										- Teleks<br>
										- video tape<br>
										- Infocus<br>
										- dan sebagainya. <br><br>
										<strong>05.PERABOT KANTOR</strong><br>
										Surat-surat yang berkenaan dengan perencanaan, pengadaan, pelelangan, pendistribusian, pemeliharaan dan penghapusan, antara lain :<br>
										- Kursi<br>
										- Meja<br>
										- Lemari<br>
										- Filing cabinet Rak<br>
										- Dan lain-lain yang sejenis.<br><br>
										<strong>06.KENDARAAN</strong><br>
										Surat-surat yang berkenaan dengan masalah kendaraan dari perencanaan, pengadaan, pelelangan, pendistribusian, pemeliharaan dan penghapusan.<br><br>
										<strong>07.INVENTARIS PERLENGKAPAN</strong><br>
										Surat-surat yang berkenaan dengan inventaris perlengkapan, laporan inventaris perlengkapan baik pusat maupun daerah.<br><br>
										<strong>08.PENAWARAN UMUM</strong><br>
										Surat-surat yang berkenaan dengan pelelangan dari mulai persiapan pelelangan, penyusunan RKS, pelaksanaan pelelangan dan pengumuman pemenang, serta hal-hal lain yang berkaitan dengan pelaksanaan pelelangan.<br><br>
										<strong>09.KETATAUSAHAAN</strong><br>
										Surat-surat yang berkenaan dengan korespondensi, kearsipan, penandatangan surat dan wewenangnya, cap dinas, dan lain sebagainya.<br>


                                    </div>
                                </div>
                            </div>
                            <!-- Collapsable Card Example -->
                            <div class="card shadow mb-4">
                                <!-- Card Header - Accordion -->
                                <a href="#collapseCardExample7" class="d-block card-header py-3" data-toggle="collapse"
                                    role="button" aria-expanded="true" aria-controls="collapseCardExample7">
                                    <h6 class="m-0 font-weight-bold text-primary">HK. Hukum</h6>
                                </a>
                                <!-- Card Content - Collapse -->
                                <div class="collapse" id="collapseCardExample7">
                                    <div class="card-body">
										<strong>00. PERATURAN PERUNDANG-UNDANGAN</strong><br>
										Surat-surat yang berkenaan dengan proses penyusunan peraturan perundangundangan produk Mahkamah Agung, dari konsep/draf sampai selesai, maupun produk peraturan perundang-undangan yang diterima baik intern Mahkamah Agung maupun dari instansi lainnya.<br>
										<strong>00.1 Undang-undang, termasuk PERPU.<br>
										00.2 Peraturan Pemerintah.<br>
										00.3 Keputusan Presiden, Instruksi Presiden, Penetapan Presiden.<br>
										00.4 Peraturan Ketua Mahkamah Agung.<br>
										00.5 Keputusan Mahkamah Agung, Instruksi Mahkamah Agung.<br>
										00.6 Keputusan Pejabat Eselon I.<br>
										00.7 Surat Edaran Pejabat Eselon I.<br>
										00.8 Peraturan Pengadilan Tingkat Banding dan Tingkat Pertama<br>
										00.9 Peraturan PEMDA Tk. I, dan PEMDA Tk. II.</strong><br><br>
										<strong>01. PIDANA</strong><br>
										Surat-surat yang berkenaan dengan penyelesaiaan perkara pidana, baik pidana kejahatan maupun pidana pelanggaran.<br><br>
										<strong>02. PERDATA</strong><br>
										Surat-surat yangberkenaan dengan penyelesaian perkara perdata, baik gugatan maupun permohonan.<br><br>
										<strong>03. PERDATA NIAGA</strong><br>
										Surat-surat yang berkenaan dengan penyelesaian perkara perdata niaga.<br><br>
										<strong>04. PIDANA MILITER</strong><br>
										Surat-surat yang berkenaan dengan penyelesaian perkara pidana militer.<br><br>
										<strong>05. PERDATA AGAMA</strong><br>
										Surat-surat yang berkenaan dengan penyelesaian perkara perdata agama.<br><br>
										<strong>06. TATA USAHA NEGARA</strong><br>
										Surat-surat yang berkenaan dengan penyelesaian perkara tata usaha Negara.<br><br>
										<strong>07. PIDANA KHUSUS</strong><br>
										Surat-surat yang berkenaan dengan penyelesaian perkara pidana khusus.

                                    </div>
                                </div>
                            </div>

                            <div class="card shadow mb-4">
                                <!-- Card Header - Accordion -->
                                <a href="#collapseCardExample8" class="d-block card-header py-3" data-toggle="collapse"
                                    role="button" aria-expanded="true" aria-controls="collapseCardExample8">
                                    <h6 class="m-0 font-weight-bold text-primary">PP. Pendidikan dan Pelatihan</h6>
                                </a>
                                <!-- Card Content - Collapse -->
                                <div class="collapse" id="collapseCardExample8">
                                    <div class="card-body">
										<strong>00. PENDIDIKAN DAN PELATIHAN TEKNIS</strong><br>
										<strong>00.1 HAKIM</strong><br>
										Surat-surat yang berkenaan dengan perencanaan, pelaksanaan, dan evaluasi penyelenggaraan pendidikan dan pelatihan hakim.<br>
										<strong>00.2 PANITERA</strong><br>
										Surat-surat yang berkenaan dengan perencanaan, pelaksanaan, dan evaluasi penyelenggaraan pendidikan dan pelatihan panitera.<br>
										<strong>00.3 JURUSITA</strong><br>
										Surat-surat yang berkenaan dengan perencanaan, pelaksanaan, dan evaluasi penyelenggaraan pendidikan dan pelatihanjurusita.<br>
										<strong>00.4 TEKNIS LAINNYA</strong><br>
										Surat-surat yang berkenaan dengan perencanaan, pelaksanaan, dan evaluasi penyelenggaraan pendidikan dan pelatihan teknis lainnya.<br><br>
										<strong>01. PENDIDIKAN DAN LATIHAN MANAJEMEN</strong><br>
										<strong>01.1 PENJENJANGAN</strong><br>
										Surat-surat yang berkenaan dengan pendidikan penjenjangan, antara lain :<br>
										- Diklatpim Tingkat IV,<br>
										- Diklatpim Tingkat III,<br>
										- Diklatpim Tinggkat II,<br>
										- Diklatpim Tingkat I,<br>
										- LEMHANAS <br>
										mulai dari perencanaan, pelaksanaan, dan evaluasi.<br>
										<strong>01.2 KEPANGKATAN</strong><br>
										Surat-surat yang berkenaan dengan pendidikan kepangkatan, antara lain :<br>
										- Pra Jabatan,<br>
										- SISCATUR (Kursus Calon Pengatur),<br>
										- SUSCATA (Kursus Calon Penata),<br>
										- SUSCABIN (Kursus Calon Pembina),<br>
										mulai dari perencanaan, pelaksanaan, dan evaluasi.<br>
										<strong>01.3 LATIHAN/KURSUS/PENATARAN MANAJEMEN</strong><br>
										Surat-surat yang berkenaan dengan latihan tenaga administrasi, kursus, dan penataran, di bidang manajemen atau lainnya, baik dalam maupun luar negeri, mulai dari perencanaan, pelaksanaan, dan evaluasi.<br>

                                    </div>
                                </div>
                            </div>
							
							<div class="card shadow mb-4">
                                <!-- Card Header - Accordion -->
                                <a href="#collapseCardExample9" class="d-block card-header py-3" data-toggle="collapse"
                                    role="button" aria-expanded="true" aria-controls="collapseCardExample9">
                                    <h6 class="m-0 font-weight-bold text-primary">PB. Penelitian dan Pengembangan</h6>
                                </a>
                                <!-- Card Content - Collapse -->
                                <div class="collapse" id="collapseCardExample9">
                                    <div class="card-body">
										<strong>00 PENELITIAN HUKUM</strong><br>
										Surat-surat yang berkenaan dengan penelitian dan pengembangan hukum, sejak dari perencanaan, perizinan, pelaksanaan, sampai dengan pelaporan hasil penelitian.<br>
										<strong>01 PENELITIAN PERADILAN</strong><br>
										Surat-surat yang berkenaan dengan penelitian dan pengembangan peradilan, sejak dari perencanaan, perizinan, pelaksanaan, sampai dengan pelaporan hasil penelitian.<br>
										<strong>02 PENGEMBANGAN PENELITIAN</strong><br>
										Surat-surat yang berkenaan dengan masalah-masalah pengembangan penelitian dan perencanaan, pelaksanaan sampai dengan pelaporan.<br>

                                    </div>
                                </div>
                            </div>
							
							<div class="card shadow mb-4">
                                <!-- Card Header - Accordion -->
                                <a href="#collapseCardExample10" class="d-block card-header py-3" data-toggle="collapse"
                                    role="button" aria-expanded="true" aria-controls="collapseCardExample10">
                                    <h6 class="m-0 font-weight-bold text-primary">PS. Pengawasan</h6>
                                </a>
                                <!-- Card Content - Collapse -->
                                <div class="collapse" id="collapseCardExample10">
									<div class="card-body">	
										<strong>03 PENYELENGGARAAN PERADILAN</strong><br>
										Surat-surat yang berkenaan dengan pengawasan atas penyelenggaraan pelaksanaan peradilan.<br>
										<strong>04 ADMINISTRASI PERADILAN</strong><br>
										Surat-surat yang berkenaan dengan pengawasan atas pengelolaan administrasi peradilan, baik administrasi perkara dan administrasi umum.<br>
										<strong>05 PENANGANAN PENGADUAN MASYARAKAT</strong><br>
										Surat-surat yang berkenaan dengan pengawasan atas pengaduan masyarakat terhadap perilaku aparat peradilan dan pelayanan publik oleh lembaga peradilan<br>

                                    </div>
                                </div>
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

</body>

</html>