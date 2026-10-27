<div class="container vertical-center">
    <div class="card auth-card">
        <div class="card-header">
            <h5 class="mb-0">Reset Password for <b><?= $user['name']; ?></b></h5>
        </div>
        <div class="card-body">
            <form action="<?= base_url('auth/setpassword/') . ($token ?? ''); ?>" method="POST" id="login" class="p-2">
                <div class="mb-3">
                    <label for="password" class="form-label">New Password</label>
                    <input class="form-control" type="password" placeholder="password" name="password" id="password" autocomplete="new-password">
                </div>
                <div class="mb-3">
                    <label for="password2" class="form-label">Re-type Password</label>
                    <input class="form-control" type="password" placeholder="password" name="password_check" id="password2" autocomplete="new-password">
                </div>
                <div class="mb-3" aria-live="polite">
                    <span id="message" class="fw-bold"></span>
                </div>
                <div class="mb-3"><button class="btn btn-dark" type="submit">Set Password</button></div>
                <div><a class="text-decoration-none" href="<?= base_url('auth/register'); ?>">Didn't have an account?</a></div>
            </form>
        </div>
    </div>
</div>
<script src="<?= base_url('assets/js/password-match.js'); ?>"></script>
