
<div class="container vertical-center">
    <form action="<?= base_url('auth/login'); ?>" method="POST" id="login" class="mx-auto p-5">
        <?= $this->session->flashdata('success'); ?>
        <?= $this->session->flashdata('failed'); ?>
        <div class="form-group"><input class="form-control" type="text" placeholder="email" name="email"></div>
        <div class="form-group"><input class="form-control" type="password" placeholder="password" name="password"></div>
        <div class="form-group"><button class="btn btn-dark" type="submit">Login</button></div>
        <div><a class="text-decoration-none" href="<?= base_url('auth/register'); ?>">Didn't have an account?</a></div>
        <div><a class="text-decoration-none" href="<?= base_url('auth/forgotpassword'); ?>">Forgot password?</a></div>
    </form>
</div>
