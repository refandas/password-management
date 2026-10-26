<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6 offset-6">
                    <ol class="breadcrumb float-sm-end">
                        <li class="breadcrumb-item"><a href="<?php echo base_url('dashboard'); ?>">Home</a></li>
                        <li class="breadcrumb-item"><a href="<?php echo base_url('Setting'); ?>">Setting</a></li>
                        <li class="breadcrumb-item active">Change Password</li>
                    </ol>
                </div><!-- /.col -->
            </div><!-- /.row -->
        </div><!-- /.container-fluid -->
    </div>
    <!-- /.content-header -->

    <!-- Main content -->
    <div class="content">
        <div class="container-fluid">
            <div>
                <form action="<?= base_url('setting/setpassword/') . ($user['id_user'] ?? ''); ?>" method="POST" id="login" class="mx-auto p-5">
                    <h5 class="mb-3">Reset Password for <b><?= $user['name'] ?? ''; ?></b></h5>
                    <div class="mb-3">
                        <label>New Password</label>
                        <input class="form-control col-md-5" type="password" placeholder="password" name="password" id="password">
                    </div>
                    <div class="mb-3">
                        <label>Re-type Password</label>
                        <input class="form-control col-md-5" type="password" placeholder="password" name="password_check" id="password2">
                    </div>
                    <div class="mb-3">
                        <span id="message"></span>
                    </div>
                    <div class="mb-3"><button class="btn btn-dark" type="submit">Set Password</button></div>
                </form>
                <!-- /.card -->
            </div>
            <!-- /.row -->
        </div>
        <!-- /.container-fluid -->
    </div>
    <!-- /.content -->
</div>
<!-- /.content-wrapper -->

<script src="<?= base_url('assets/js/password-match.js'); ?>"></script>
