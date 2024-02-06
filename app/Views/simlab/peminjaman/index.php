<?= $this->include('simlab/simlab_partial/dashboard/header') ?>
<?= $this->include('simlab/simlab_partial/dashboard/top_menu') ?>
<?= $this->include('simlab/simlab_partial/dashboard/side_menu') ?>

<main role="main" class="main-content">
    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-md-12">
                <!-- Breadcrumbs -->
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h2 class="page-title">Halaman
                            <?= $title ?>
                        </h2>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right text-sm">
                            <li class="breadcrumb-item"><a href="<?= base_url('simlab') ?>">Dashboard</a></li>
                            <li class="breadcrumb-item active">Data Peminjaman</li>
                        </ol>
                    </div>
                </div>

                <div class="row my-4">
                    <!-- Small table -->
                    <!-- Small table -->
                    <div class="col-md-4">
                        <div class="card shadow mb-4">
                            <div class="card-body text-center">
                                <div class="avatar avatar-lg mt-4">
                                    <a href="">
                                        <i class="fe fe-sidebar" style="font-size: 4em;"></i>
                                    </a>
                                </div>
                                <div class="card-text my-2">
                                    <strong class="card-title my-0">Data Pengajuan Peminjaman</strong>
                                </div>
                            </div> <!-- ./card-text -->
                            <div class="card-footer">
                                <div class="row align-items-center justify-content-center ">
                                    <div class="col-6 text-center">
                                        <a
                                            href="<?= base_url('simlab/peminjaman/data-pengajuan-peminjaman/alat-laboratorium') ?>">
                                            <small><span class="bg-success mr-1"></span>Alat <br>
                                                Laboratorium</small></a>
                                    </div>
                                    <div class="col-6 text-center">
                                        <a
                                            href="<?= base_url('simlab/peminjaman/data-pengajuan-peminjaman/ruang-laboratorium') ?>">
                                            <small><span class="bg-success mr-1"></span>Ruang Laboratorium</small></a>
                                    </div>
                                </div>
                            </div> <!-- /.card-footer -->
                        </div> <!-- .card -->
                    </div> <!-- .col-md-->
                    <div class="col-md-4">
                        <div class="card shadow mb-4">
                            <div class="card-body text-center">
                                <div class="avatar avatar-lg mt-4">
                                    <a href="">
                                        <i class="fe fe-sidebar" style="font-size: 4em;"></i>
                                    </a>
                                </div>
                                <div class="card-text my-2">
                                    <strong class="card-title my-0">Data Peminjaman</strong>
                                </div>
                            </div> <!-- ./card-text -->
                            <div class="card-footer">
                                <div class="row align-items-center justify-content-center ">
                                    <div class="col-6 text-center">
                                        <a href="<?= base_url('simlab/peminjaman/data-peminjaman/alat-laboratorium') ?>">
                                            <small><span class="bg-success mr-1"></span>Alat <br>
                                                Laboratorium</small></a>
                                    </div>
                                    <div class="col-6 text-center">
                                        <a href="<?= base_url('simlab/peminjaman/data-peminjaman/ruang-laboratorium') ?>">
                                            <small><span class="bg-success mr-1"></span>Ruang Laboratorium</small></a>
                                    </div>
                                </div>
                            </div> <!-- /.card-footer -->
                        </div> <!-- .card -->
                    </div> <!-- .col-md-->


                    <div class="col-md-4">
                        <div class="card shadow mb-4">
                            <div class="card-body text-center">
                                <div class="avatar avatar-lg mt-4">
                                    <a href="">
                                        <i class="fe fe-sidebar" style="font-size: 4em;"></i>
                                    </a>
                                </div>
                                <div class="card-text my-2">
                                    <strong class="card-title my-0">Riwayat Peminjaman</strong>
                                </div>
                            </div> <!-- ./card-text -->
                            <div class="card-footer">
                                <div class="row align-items-center justify-content-center ">
                                    <div class="col-6 text-center">
                                        <a
                                            href="<?= base_url('simlab/peminjaman/riwayat-peminjaman/alat-laboratorium') ?>">
                                            <small><span class="bg-success mr-1"></span>Alat <br>
                                                Laboratorium</small></a>
                                    </div>
                                    <div class="col-6 text-center">
                                        <a
                                            href="<?= base_url('simlab/peminjaman/riwayat-peminjaman/ruang-laboratorium') ?>">
                                            <small><span class="bg-success mr-1"></span>Ruang Laboratorium</small></a>
                                    </div>
                                </div>
                            </div> <!-- /.card-footer -->

                        </div> <!-- .card -->
                    </div> <!-- .col-md-->


                </div> <!-- end section -->
            </div> <!-- .col-12 -->
        </div> <!-- .row -->
    </div> <!-- .container-fluid -->

</main>

<?= $this->include('simlab/simlab_partial/dashboard/footer') ?>