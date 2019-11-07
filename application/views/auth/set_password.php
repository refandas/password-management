
<div class="container vertical-center">
    <form action="<?= base_url('auth/setpassword/') . $user['id_user']; ?>" method="POST" id="login" class="mx-auto p-5">
        <h5 class="mb-3">Reset Password for <b><?= $user['name']; ?></b></h5>
        <div class="form-group">
            <label>New Password</label>
            <input class="form-control" type="password" placeholder="password" name="password" id="password">
        </div>
        <div class="form-group">
            <label>Re-type Password</label>
            <input class="form-control" type="password" placeholder="password" name="password_check" id="password2">
        </div>
        <div class="form-group">
          <span id="message"></span>
        </div>
        <div class="form-group"><button class="btn btn-dark" type="submit">Set Password</button></div>
        <div><a class="text-decoration-none" href="<?= base_url('auth/register'); ?>">Didn't have an account?</a></div>
    </form>
</div>

<script type="text/javascript">
  $(document).ready(function () {
      $('#password, #password2').on('keyup', function () {
          if ($('#password').val() == $('#password2').val()) {
              $('#message').html('Same password').css('color', 'green');
          }
          else {
              $('#message').html('Different password').css('color', 'red');
          }
      })
  });
</script>