// =====================================================
// Small JavaScript helpers for Lendwise
// =====================================================

// 1. Open / close the sidebar on small screens
var menuButton = document.getElementById('menuToggle');
var sidebar = document.getElementById('sidebar');

if (menuButton && sidebar) {
    menuButton.addEventListener('click', function () {
        sidebar.classList.toggle('open');
    });
}

// 2. Show / hide password on the login page
var toggleButton = document.getElementById('togglePassword');
var passwordInput = document.getElementById('password');

if (toggleButton && passwordInput) {
    toggleButton.addEventListener('click', function () {
        var icon = toggleButton.querySelector('i');
        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            icon.className = 'bi bi-eye-slash';
        } else {
            passwordInput.type = 'password';
            icon.className = 'bi bi-eye';
        }
    });
}

// 3. Make green "success" messages disappear after 4 seconds
var successAlerts = document.querySelectorAll('.alert-success');
successAlerts.forEach(function (alertBox) {
    setTimeout(function () {
        alertBox.style.display = 'none';
    }, 4000);
});
