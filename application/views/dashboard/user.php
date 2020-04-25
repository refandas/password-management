
    <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6 offset-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="<?php echo base_url('dashboard'); ?>">Home</a></li>
                            <li class="breadcrumb-item"><a href="<?php echo base_url('dashboard/users'); ?>">Users</a></li>
                            <li class="breadcrumb-item active"><?php echo $user['name']; ?></li>
                        </ol>
                    </div><!-- /.col -->
                </div><!-- /.row -->
            </div><!-- /.container-fluid -->
        </div>
        <!-- /.content-header -->

        <!-- Main content -->
        <div class="content">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-md-6">
                        <div class="card">
                            <h5 class="card-header"><?php echo $user['name']; ?></h5>
                            <div class="card-body">
                                <?php if ((int)$user['registered']) : ?>
                                    <p>Status akun aktif, klik tombol berikut untuk mengaktifkan</p>
                                    <a href="<?php echo base_url('auth/deactivateuser/') . $user['id_user']; ?>" class="btn btn-danger">Nonaktifkan User</a>
                                <?php else : ?>
                                    <p>Status akun nonaktif, klik tombol berikut untuk menonaktifkan</p>
                                    <a href="<?php echo base_url('auth/activateuser/') . $user['id_user']; ?>" class="btn btn-success">Aktifkan User</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- /.container-fluid -->
        </div>
        <!-- /.content -->
    </div>
    <!-- /.content-wrapper -->

    <!-- Control Sidebar -->
    <aside class="control-sidebar control-sidebar-dark">
        <!-- Control sidebar content goes here -->
    </aside>
    <!-- /.control-sidebar -->
