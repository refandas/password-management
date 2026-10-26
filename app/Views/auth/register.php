<div class="container vertical-center">
    <form action="<?= base_url('auth/create'); ?>" method="POST" id="login" class="mx-auto p-5">
        <div class="form-group">
            <label>Name</label>
            <input class="form-control" type="text" placeholder="name" name="name">
        </div>
        <div class="form-group">
            <label>Email</label>
            <input class="form-control" type="text" placeholder="email" name="email">
        </div>
        <div class="form-group">
            <label>Password</label>
            <input class="form-control" type="password" placeholder="password" name="password" id="password">
        </div>
        <div class="form-group">
            <label>Re-type Password</label>
            <input class="form-control" type="password" placeholder="password" name="password2" id="password2">
        </div>
        <div class="form-group form-check">
            <input type="checkbox" class="form-check-input" id="license-accepted">
            <label class="form-check-label" for="exampleCheck1">Accept license aggrement.</label>
        </div>
        <div class="form-group">
          <span id="message"></span>
        </div>
        <div class="form-group"><button class="btn btn-dark" type="submit" id="btn-submit" disabled>Sign Up</button></div>
        <div><a class="text-decoration-none" href="<?= base_url('auth/forgotpassword'); ?>">Forgot password?</a></div>
    </form>
</div>

<script>
// #32: vanilla JS replaces the legacy $(...)-based checks.
document.addEventListener('DOMContentLoaded', function () {
    var password = document.getElementById('password');
    var password2 = document.getElementById('password2');
    var message = document.getElementById('message');

    function checkMatch() {
        var same = password.value === password2.value;
        message.textContent = same ? 'Same password' : 'Different password';
        message.style.color = same ? 'green' : 'red';
    }

    password.addEventListener('keyup', checkMatch);
    password2.addEventListener('keyup', checkMatch);
    var license = document.getElementById('license-accepted');
    var submit = document.getElementById('btn-submit');

    license.addEventListener('change', function () {
        submit.disabled = !license.checked;
    });
});

</script>
