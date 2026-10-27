// #32: the navbar hamburger drives the sidebar in both modes — no
// data-bs-toggle on the button, so Bootstrap's delegated offcanvas
// handler never fights the rail toggle at lg+.
document.addEventListener('DOMContentLoaded', function () {
    var toggler = document.querySelector('.navbar-toggler[data-bs-target="#appSidebar"]');
    var sidebar = document.getElementById('appSidebar');
    if (!toggler || !sidebar) {
        return;
    }
    toggler.addEventListener('click', function () {
        if (window.innerWidth < 992) {
            window.bootstrap.Offcanvas.getOrCreateInstance(sidebar).show();
            return;
        }
        var hidden = sidebar.classList.toggle('d-lg-none');
        toggler.setAttribute('aria-expanded', hidden ? 'false' : 'true');
    });
});
