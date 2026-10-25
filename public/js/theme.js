document.addEventListener('DOMContentLoaded', () => {
    const html       = document.documentElement;
    const toggleBtn  = document.getElementById('themeToggleBtn');
    const toggleIcon = document.getElementById('themeToggleIcon');
    const toggleLbl  = document.getElementById('themeToggleLabel');

    function applyTheme(theme) {
        if (theme === 'light') {
            html.setAttribute('data-theme', 'light');
            if (toggleIcon) toggleIcon.textContent = '🌙';
            if (toggleLbl)  toggleLbl.textContent  = 'Dark Mode';
        } else {
            html.removeAttribute('data-theme');
            if (toggleIcon) toggleIcon.textContent = '☀️';
            if (toggleLbl)  toggleLbl.textContent  = 'Light Mode';
        }
    }

    // Apply saved theme on load
    applyTheme(localStorage.getItem('netlearn-theme') || 'dark');

    if (toggleBtn) {
        toggleBtn.addEventListener('click', () => {
            const current = html.getAttribute('data-theme') === 'light' ? 'light' : 'dark';
            const next    = current === 'light' ? 'dark' : 'light';
            localStorage.setItem('netlearn-theme', next);
            applyTheme(next);
        });
    }
});
