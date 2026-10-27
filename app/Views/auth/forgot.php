<div class="container vertical-center">
    <div class="card auth-card">
        <div class="card-header">
            <h5 class="mb-0">Forgot Password</h5>
        </div>
        <div class="card-body">
            <form action="<?= base_url('auth/resetpassword'); ?>" method="POST" id="login" class="p-2">
                <?= session()->getFlashdata('failed'); ?>
                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input class="form-control" type="text" placeholder="email" name="email" id="email" autocomplete="email">
                </div>
                <div class="mb-3">
                    <button class="btn btn-dark" type="submit">Check Account</button>
                </div>
                <div><a class="text-decoration-none" href="<?= base_url('auth/register'); ?>">Didn't have an account?</a></div>
            </form>
        </div>
    </div>
</div>
