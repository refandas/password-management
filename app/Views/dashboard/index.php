<!-- Ticket #9 stub: the gated landing page proves the session keys. The full
     AdminLTE dashboard (navbar, sidebar, user panel) ports with the layout
     ticket. Legacy markup: <h2>HELLO {strtoupper(name)}</h2> -->
<div class="content-wrapper">
    <div class="content">
        <div class="container-fluid">
            <div class="card">
                <div class="card-header border-0">
                    <h2>HELLO <?= strtoupper(session('name')); ?></h2>
                </div>
            </div>
        </div>
    </div>
</div>
