<!-- Main Sidebar Container -->
<aside class="main-sidebar sidebar-dark-primary elevation-4">

    <!-- Sidebar -->
    <div class="sidebar">
        <!-- Sidebar user panel (optional) -->
        <div class="user-panel mt-3 pb-3 mb-3 d-flex">
            <div class="info">
                <a href="#" class="d-block"><?= $this->session->userdata('name'); ?></a>
            </div>
        </div>

        <!-- Sidebar Menu -->
        <nav class="mt-2">
            <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
                <!-- Add icons to the links using the .nav-icon class
                     with font-awesome or any other icon font library -->
                <li class="nav-item has-treeview">
                    <a href="<?= base_url('dashboard'); ?>" class="nav-link">
                        <i class="nav-icon fas fa-tachometer-alt"></i>
                        <p>Dashboard</p>
                    </a>
                </li>

                <?php if ($this->session->userdata('access_level') == 1) : ?>
                <li class="nav-item mt-1">
                    <a href="<?= base_url('dashboard/users'); ?>" class="nav-link">
                        <i class="nav-icon fas fa-user-friends"></i>
                        <p>USER</p>
                    </a>
                </li>
                <?php endif; ?>

                <?php if ($this->session->userdata('access_level') == 2) : ?>
                <li class="nav-item mt-4">
                    <a href="<?= base_url('account/index'); ?>" class="nav-link">
                        <i class="nav-icon fas fa-window-restore"></i>
                        <p>ACCOUNT</p>
                    </a>
                </li>
                <?php endif; ?>

                <li class="nav-item has-treeview mt-4">
                    <a href="#" class="nav-link">
                        <i class="nav-icon fas fa-cogs"></i>
                        <p>Setting</p>
                    </a>
                </li>
                <li class="nav-item has-treeview">
                    <a href="<?= base_url('dashboard/logout'); ?>" class="nav-link">
                        <i class="nav-icon fas fa-sign-out-alt"></i>
                        <p>Log Out</p>
                        </i>
                    </a>
                </li>
            </ul>
        </nav>
        <!-- /.sidebar-menu -->
    </div>
    <!-- /.sidebar -->
</aside>