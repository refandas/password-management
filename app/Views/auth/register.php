<div class="container vertical-center">
    <div class="card auth-card">
        <div class="card-header">
            <h5 class="mb-0">Register Now!</h5>
        </div>
        <div class="card-body">
            <form action="<?= base_url('auth/create'); ?>" method="POST" id="login" class="p-2">
                <div class="mb-3">
                    <label for="name" class="form-label">Name</label>
                    <input class="form-control" type="text" placeholder="name" name="name" id="name" autocomplete="name">
                </div>
                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input class="form-control" type="text" placeholder="email" name="email" id="email" autocomplete="email">
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input class="form-control" type="password" placeholder="password" name="password" id="password" autocomplete="new-password">
                </div>
                <div class="mb-3">
                    <label for="password2" class="form-label">Re-type Password</label>
                    <input class="form-control" type="password" placeholder="password" name="password2" id="password2" autocomplete="new-password">
                </div>
                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" id="license-accepted">
                    <label class="form-check-label" for="license-accepted">Accept license aggrement.</label>
                </div>
                <div class="mb-3" aria-live="polite">
                    <span id="message" class="fw-bold"></span>
                </div>
                <div class="mb-3"><button class="btn btn-dark" type="submit" id="btn-submit" disabled>Sign Up</button></div>
                <div><a class="text-decoration-none" href="<?= base_url('auth/forgotpassword'); ?>">Forgot password?</a></div>
            </form>
        </div>
    </div>
</div>
<script src="<?= base_url('assets/js/password-match.js'); ?>"></script>
<script>
// #32: the license gate is page-specific; kept inline.
document.addEventListener('DOMContentLoaded', function () {
    var license = document.getElementById('license-accepted');
    var submit = document.getElementById('btn-submit');
    if (!license || !submit) {
        return;
    }
    license.addEventListener('change', function () {
        submit.disabled = !license.checked;
    });
});
</script>
