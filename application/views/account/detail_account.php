
<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6 offset-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="#">Home</a></li>
                        <li class="breadcrumb-item active">Dashboard v3</li>
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
                    <h3 class="card-title">DETAIL ACCOUNTS</h3>
                </div>

                <div class="card-body">
                    <form action="<?= base_url('account/update/') . $account['id_account']; ?>" method="POST">
                        <div class="form-group">
                            <label>Username</label>
                            <input type="text" class="form-control col-md-6" placeholder="username or email" name="username_email" value="<?= $account['username_email']; ?>">
                        </div>
                        <div class="form-group">
                            <label>Password</label>
                            <input type="text" class="form-control col-md-6" placeholder="password" name="password" value="<?= $account['password']; ?>">
                        </div>
                        <div class="form-group">
                            <button type="submit" class="btn btn-dark mr-1">Save</button>
                            <a href="<?= base_url('account/delete/') . $account['id_account']; ?>" class="btn btn-outline-danger ml-1">Delete</a>
                        </div>
                    </form>
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