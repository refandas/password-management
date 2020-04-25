
<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6 offset-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?php echo base_url('dashboard'); ?>">Home</a></li>
                        <li class="breadcrumb-item active">Setting</li>
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
                    <?= $this->session->flashdata('success'); ?>
                    <h3 class="card-title">YOUR ACCOUNT</h3>
                </div>

                <div class="card-body">
                    <!-- Form for edit profile -->
                    <form action="<?= base_url('setting/update/') . $user['id_user']; ?>" method="POST">
                        <div class="row">
                            <div class="form-group col-md-6">
                                <label>Your name</label>
                                <input type="text" value="<?= $user['name']; ?>" class="form-control" name="name">
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group col-md-6">
                                <label>Your username or email</label>
                                <input type="email" value="<?= $user['email']; ?>" class="form-control" name="email">
                            </div>
                        </div>
                        <div class="form-group col-md-2">
                            <button type="submit" class="btn btn-outline-dark form-control">Update Profile</button>
                        </div>
                    </form>
                    <!-- /.form -->
                    <hr>

                  <!-- Change password -->
                    <div class="row">
                        <div class="form-group col-md-2">
                            <label>Your password</label>
                            <a href="<?= base_url('setting/password/') . $user['id_user']; ?>" class="btn btn-outline-dark form-control">Change Password</a>
                        </div>
                    </div>
                    <!-- /.row -->
                    <!-- Deactivate account -->
                    <div class="row">
                        <div class="form-group col-md-6">
                            <label>Deactivate account</label>
                            <p>With clicking Deactivate button, your account will not activated for a period of time until you activate it again.</p>
                            <a href="<?= base_url('setting/deactivate/') . $user['id_user']; ?>" class="btn btn-outline-dark form-control col-md-3">Deactivate</a>
                        </div>
                    </div>
                    <!-- /.row -->
                    <!-- Delete account -->
                    <div class="row">
                        <div class="form-group col-md-6">
                            <label>Delete account</label>
                            <p>Deleting account will <span style="color: red">destroy</span> all of your account, so you <span style="color: red">cannot repair</span> your account again. If you want to break for a time, you can use deactivate rather than delete.</p>
                            <a href="" class="btn btn-outline-danger form-control col-md-4" data-toggle="modal" data-target="#deleteModal">Delete Account</a>
                        </div>
                    </div>
                    <!-- /.row -->
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

<!-- Button trigger modal -->
<button type="button" class="btn btn-primary" data-toggle="modal" data-target="#exampleModalCenter">
  Launch demo modal
</button>

<!-- Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenterTitle" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="exampleModalCenterTitle" style="color: red">Delete Account?</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <p>If you decide to delete, you account <span style="color: red">will destroy</span> and cannot to be repaired.</p>
        <p>If you think that you will break in a short time, it will beter for you to deactivate account rather than delete.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary" data-dismiss="modal">Cancel</button>
        <a href="<?= base_url('setting/delete/') . $user['id_user']; ?>" class="btn btn-outline-danger">Delete</a>
      </div>
    </div>
  </div>
</div>
<!-- /.modal -->
