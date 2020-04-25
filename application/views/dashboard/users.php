
    <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6 offset-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="<?php echo base_url('dashboard'); ?>">Home</a></li>
                            <li class="breadcrumb-item active">Users</li>
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
                    <?php if ($this->session->flashdata('success') or $this->session->flashdata('failed')) : ?>
                    <div class="card-body">
						<?php echo $this->session->flashdata('success'); ?>
						<?php echo $this->session->flashdata('failed'); ?>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="card">
                            <h5 class="card-header">User Active</h5>
                            <div class="card-body">
                                <table class="table table-hover">
                                    <thead>
                                    <tr>
                                        <th scope="col">#</th>
                                        <th scope="col">User</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <?php $count = 1; ?>
                                    <?php foreach ($users as $user) : ?>
                                        <?php if ((int)$user['registered'] and $user['access_level'] != 1) : ?>
                                    <tr>
                                        <th><?php echo $count; ?></th>
                                        <td><a href="<?php echo base_url('dashboard/user/') . $user['id_user']; ?>"><?php echo $user['name']; ?></a></td>
                                    </tr>
                                        <?php $count++; ?>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <h5 class="card-header">User Not Active</h5>
                            <div class="card-body">
                                <table class="table table-hover">
                                    <thead>
                                    <tr>
                                        <th scope="col">#</th>
                                        <th scope="col">User</th>
                                    </tr>
                                    </thead>
                                    <tbody>
									<?php $count = 1; ?>
									<?php foreach ($users as $user) : ?>
										<?php if (!(int)$user['registered'] and $user['access_level'] != 1) : ?>
                                            <tr>
                                                <th><?php echo $count; ?></th>
                                                <td><a href="<?php echo base_url('dashboard/user/') . $user['id_user']; ?>"><?php echo $user['name']; ?></a></td>
                                            </tr>
											<?php $count++; ?>
										<?php endif; ?>
									<?php endforeach; ?>
                                    </tbody>
                                </table>
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
