
<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6 offset-6">
                    <ol class="breadcrumb float-sm-right">
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
                <form action="<?= base_url('setting/setpassword/') . $user['id_user']; ?>" method="POST" id="login" class="mx-auto p-5">
                    <h5 class="mb-3">Reset Password for <b><?= $user['name']; ?></b></h5>
                    <div class="form-group">
                        <label>New Password</label>
                        <input class="form-control col-md-5" type="password" placeholder="password" name="password" id="password">
                    </div>
                    <div class="form-group">
                        <label>Re-type Password</label>
                        <input class="form-control col-md-5" type="password" placeholder="password" name="password_check" id="password2">
                    </div>
                    <div class="form-group">
                        <span id="message"></span>
                    </div>
                    <div class="form-group"><button class="btn btn-dark" type="submit">Set Password</button></div>
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

<!-- Control Sidebar -->
<aside class="control-sidebar control-sidebar-dark">
    <!-- Control sidebar content goes here -->
</aside>
<!-- /.control-sidebar -->

<script type="text/javascript">
    $(document).ready(function () {
        $('#password, #password2').on('keyup', function () {
            if ($('#password').val() == $('#password2').val()) {
                $('#message').html('Same password').css('color', 'green');
            }
            else {
                $('#message').html('Different password').css('color', 'red');
            }
        })
    });
</script>

