// #32: shared vanilla password-match feedback for the three password
// forms (register, set_password, change_password). Field contract:
// #password, #password2, #message.
document.addEventListener('DOMContentLoaded', function () {
    var password = document.getElementById('password');
    var password2 = document.getElementById('password2');
    var message = document.getElementById('message');
    if (!password || !password2 || !message) {
        return;
    }
    function checkMatch() {
        var same = password.value === password2.value;
        message.textContent = same ? 'Same password' : 'Different password';
        message.style.color = same ? 'green' : 'red';
    }
    password.addEventListener('keyup', checkMatch);
    password2.addEventListener('keyup', checkMatch);
});
