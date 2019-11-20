
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
                    <h3 class="card-title">ADD ACCOUNTS</h3>
                </div>

                <div class="card-body">
                    <form action="<?= base_url('account/add'); ?>" method="POST">
                        <div class="form-group">
                            <label>Username</label>
                            <input type="text" class="form-control col-md-6" placeholder="username or email" name="username_email">
                        </div>
                        <div class="form-group">
                            <label>Password</label>
                            <input type="text" class="form-control col-md-6" placeholder="password" name="password">
                        </div>
                        <div class="form-group">
                            <label>Account Media</label>
                            <select name="media" class="form-control col-md-3">
                                <?php foreach ($account_media as $media) : ?>
                                <option value="<?= $media['id_media']; ?>"><?= $media['name']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>


                        <button class="btn btn-outline-dark" type="submit">Add New</button>
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