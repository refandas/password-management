<!-- Top navbar: carries the offcanvas sidebar toggle (#32). -->
<nav class="navbar navbar-dark bg-dark sticky-top">
    <div class="container-fluid">
        <button class="navbar-toggler" type="button" data-bs-target="#appSidebar" aria-controls="appSidebar" aria-label="Toggle sidebar" aria-expanded="true">
            <span class="navbar-toggler-icon"></span>
        </button>
        <a class="navbar-brand" href="<?= base_url('dashboard'); ?>">Password Management</a>
        <span class="navbar-text d-none d-sm-inline"><i class="fas fa-user me-1"></i><?= session('name'); ?></span>
    </div>
</nav>
<!-- Shell row: opened here, closed by main_footer. -->
<div class="app-shell">
