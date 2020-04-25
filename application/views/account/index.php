
<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6 offset-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?php echo base_url('dashboard'); ?>">Home</a></li>
                        <li class="breadcrumb-item active">Account</li>
                    </ol>
                </div><!-- /.col -->
            </div><!-- /.row -->
        </div><!-- /.container-fluid -->
    </div>
    <!-- /.content-header -->

    <!-- Main content -->
    <div class="content">
        <div class="container-fluid">
            <div class="card">
                <div class="card-header border-0">
                    <h3 class="card-title">ACCOUNTS</h3>
                </div>

                <!-- Card for account -->
                <div class="card-body">
                    <?= $this->session->flashdata('success'); ?>
                    <a href="<?= base_url('account/add_account'); ?>" class="btn btn-outline-dark mb-3">Add Account</a>
                    <div class="row">
                        <?php foreach ($accounts as $account) : ?>
                        <div class="col-sm-4">
                            <div class="card">
                                <h5 class="card-header"><i class="<?= $account['logo']; ?> mr-2"></i><?= $account['name']; ?></h5>
                                <div class="card-body">
                                    <label><i class="fas fa-user mr-2"></i><?= $account['username_email']; ?></label><br>
                                    <label><i class="fas fa-unlock-alt mr-2"></i><?= $account['password']; ?></label><br>
                                    <a href="<?= base_url('account/detail_account/') . $account['id_account']; ?>" class="btn btn-outline-dark">Action</a>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <!-- /.card -->
                </div>
                <!-- /.col-md-6 -->
            </div>
            <!-- /.row -->
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