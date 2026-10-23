<!-- Ticket #9 stub: the Users management page ports with ticket #13. The
     flash card below is ported early so the ticket #10 activate/deactivate
     flashes render on their redirect target exactly as legacy. -->
<div class="content-wrapper">
    <div class="content">
        <div class="container-fluid">
            <div class="card">
                <?php if (session()->getFlashdata('success') or session()->getFlashdata('failed')) : ?>
                <div class="card-body">
                    <?php echo session()->getFlashdata('success'); ?>
                    <?php echo session()->getFlashdata('failed'); ?>
                </div>
                <?php endif; ?>
            </div>
            <h1>Users</h1>
        </div>
    </div>
</div>
