<div class="container vertical-center">
    <form action="<?= base_url('auth/setpassword/') . $user['id_user']; ?>" method="POST" id="login" class="mx-auto p-5">
        <h5 class="mb-3">Reset Password for <b><?= $user['name']; ?></b></h5>
        <div class="mb-3">
            <label>New Password</label>
            <input class="form-control" type="password" placeholder="password" name="password" id="password">
        </div>
        <div class="mb-3">
            <label>Re-type Password</label>
            <input class="form-control" type="password" placeholder="password" name="password_check" id="password2">
        </div>
        <div class="mb-3">
          <span id="message"></span>
        </div>
        <div class="mb-3"><button class="btn btn-dark" type="submit">Set Password</button></div>
        <div><a class="text-decoration-none" href="<?= base_url('auth/register'); ?>">Didn't have an account?</a></div>
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
});

</script>
