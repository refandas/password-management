<!-- #9's gated stub, now wearing the full shell: #35 moved it from
     the bare page() sandwich to layoutPage(), matching every other
     authenticated page. Legacy markup: <h2>HELLO {strtoupper(name)}</h2> -->
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
