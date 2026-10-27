<div class="container vertical-center">
    <form action="<?= base_url('auth/create'); ?>" method="POST" id="login" class="mx-auto p-5">
        <div class="mb-3">
            <label>Name</label>
            <input class="form-control" type="text" placeholder="name" name="name">
        </div>
        <div class="mb-3">
            <label>Email</label>
            <input class="form-control" type="text" placeholder="email" name="email">
        </div>
        <div class="mb-3">
            <label>Password</label>
            <input class="form-control" type="password" placeholder="password" name="password" id="password">
        </div>
        <div class="mb-3">
            <label>Re-type Password</label>
            <input class="form-control" type="password" placeholder="password" name="password2" id="password2">
        </div>
        <div class="mb-3 form-check">
            <input type="checkbox" class="form-check-input" id="license-accepted">
            <label class="form-check-label" for="license-accepted">Accept license aggrement.</label>
        </div>
        <div class="mb-3">
          <span id="message"></span>
        </div>
        <div class="mb-3"><button class="btn btn-dark" type="submit" id="btn-submit" disabled>Sign Up</button></div>
        <div><a class="text-decoration-none" href="<?= base_url('auth/forgotpassword'); ?>">Forgot password?</a></div>
    </form>
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
