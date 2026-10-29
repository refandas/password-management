<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6 offset-6">
                    <ol class="breadcrumb float-sm-end">
                        <li class="breadcrumb-item"><a href="<?php echo base_url('dashboard'); ?>">Home</a></li>
                        <li class="breadcrumb-item"><a href="<?php echo base_url('dashboard/users'); ?>">Users</a></li>
                        <li class="breadcrumb-item active"><?php echo $user['name'] ?? ''; ?></li>
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
                        <h5 class="card-header"><?php echo $user['name'] ?? ''; ?></h5>
                        <div class="card-body">
                            <?php if ((int)($user['registered'] ?? 0)) : ?>
                            <p>This user is active. Click the button below to deactivate.</p>
                            <a href="<?php echo base_url('auth/deactivateuser/') . ($user['id_user'] ?? ''); ?>" class="btn btn-danger">Deactivate User</a>
                            <?php else : ?>
                            <p>This user is inactive. Click the button below to activate.</p>
                            <a href="<?php echo base_url('auth/activateuser/') . ($user['id_user'] ?? ''); ?>" class="btn btn-success">Activate User</a>
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
