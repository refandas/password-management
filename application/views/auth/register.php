
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

<script type="text/javascript">
    $(document).ready(function() {
        // Check is first and second password is match or not
        $('#password, #password2').on('keyup', function () {
            if($('#password').val() == $('#password2').val()) {
                $('#message').html('Same password').css('color', 'green');
            }
            else {
                $('#message').html('Different password').css('color', 'red');
            }
        });

        // Check that license agreement is checked or not
        $('#license-accepted').click(function (event) {
            if ($(this).prop('checked') == true ) {
                $('#btn-submit').prop('disabled', false);
            } else {
                $('#btn-submit').prop('disabled', true);
            }
        });
    });
</script>