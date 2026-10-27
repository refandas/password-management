<div class="container vertical-center">
    <div class="card auth-card">
        <div class="card-header">
            <h5 class="mb-0">Login</h5>
        </div>
        <div class="card-body">
            <form action="<?= base_url('auth/login'); ?>" method="POST" id="login" class="p-2">
                <?= session()->getFlashdata('success'); ?>
                <?= session()->getFlashdata('failed'); ?>
                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input class="form-control" type="text" placeholder="email" name="email" id="email" autocomplete="email">
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input class="form-control" type="password" placeholder="password" name="password" id="password" autocomplete="current-password">
                </div>
                <div class="mb-3">
                    <button class="btn btn-dark" type="submit">Login</button>
                </div>
                <div><a class="text-decoration-none" href="<?= base_url('auth/register'); ?>">Didn't have an account?</a></div>
                <div><a class="text-decoration-none" href="<?= base_url('auth/forgotpassword'); ?>">Forgot password?</a></div>
            </form>
        </div>
    </div>
</div>
