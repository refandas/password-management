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
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Reset Password for <b><?= $user['name'] ?? ''; ?></b></h5>
                </div>
                <div class="card-body">
                    <form action="<?= base_url('setting/setpassword/') . ($user['id_user'] ?? ''); ?>" method="POST" id="login" class="p-4">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="password" class="form-label">New Password</label>
                                <input class="form-control" type="password" placeholder="password" name="password" id="password" autocomplete="new-password">
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="password2" class="form-label">Re-type Password</label>
                                <input class="form-control" type="password" placeholder="password" name="password_check" id="password2" autocomplete="new-password">
                            </div>
                        </div>
                        <div class="mb-3" aria-live="polite">
                            <span id="message" class="fw-bold"></span>
                        </div>
                        <button class="btn btn-dark" type="submit">Set Password</button>
                    </form>
                </div>
                <!-- /.card-body -->
            </div>
            <!-- /.card -->
        </div>
        <!-- /.container-fluid -->
    </div>
    <!-- /.content -->
</div>
<!-- /.content-wrapper -->

<script src="<?= base_url('assets/js/password-match.js'); ?>"></script>
