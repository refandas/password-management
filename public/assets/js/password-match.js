// #32: shared vanilla password-match feedback for the three password
// forms (register, set_password, change_password). Field contract:
// #password, #password2, #message. The `input` event covers typing,
// paste and autofill; feedback uses Bootstrap text utilities.
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
        message.classList.toggle('text-success', same);
        message.classList.toggle('text-danger', !same);
    }
    password.addEventListener('input', checkMatch);
    password2.addEventListener('input', checkMatch);
});
