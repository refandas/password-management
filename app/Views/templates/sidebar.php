<!-- Main sidebar (#32): an offcanvas drawer below lg, a static dark rail
     at lg and up — the AdminLTE layout kept on a live stack. -->
<aside class="offcanvas-lg offcanvas-start app-sidebar" id="appSidebar" aria-label="Main menu">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title"><?= session('name'); ?></h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" data-bs-target="#appSidebar" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body">
        <nav>
            <ul class="nav nav-pills flex-column">
                <li class="nav-item">
                    <a href="<?= base_url('dashboard'); ?>" class="nav-link">
                        <i class="fas fa-tachometer-alt me-2"></i>
                        <p>Dashboard</p>
                    </a>
                </li>
                <?php if (session('access_level') == 1) : ?>
                <li class="nav-item">
                    <a href="<?= base_url('dashboard/users'); ?>" class="nav-link">
                        <i class="fas fa-user-friends me-2"></i>
                        <p>USER</p>
                    </a>
                </li>
                <?php endif; ?>
                <?php if (session('access_level') == 2) : ?>
                <li class="nav-item">
                    <a href="<?= base_url('account/index'); ?>" class="nav-link">
                        <i class="fas fa-window-restore me-2"></i>
                        <p>ACCOUNT</p>
                    </a>
                </li>
                <?php endif; ?>
                <li class="nav-item">
                    <a href="<?= base_url('setting'); ?>" class="nav-link">
                        <i class="fas fa-cogs me-2"></i>
                        <p>Setting</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?= base_url('dashboard/logout'); ?>" class="nav-link">
                        <i class="fas fa-sign-out-alt me-2"></i>
                        <p>Log Out</p>
                    </a>
                </li>
            </ul>
        </nav>
    </div>
</aside>
