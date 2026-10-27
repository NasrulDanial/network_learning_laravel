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

        // Notify Chart.js or any other theme-sensitive components if active
        if (typeof window.onThemeChange === 'function') {
            window.onThemeChange(theme);
        }
    }

    // Determine initial theme: saved preference -> system preference -> dark fallback
    const savedTheme = localStorage.getItem('netlearn-theme');
    if (savedTheme) {
        applyTheme(savedTheme);
    } else if (window.matchMedia && window.matchMedia('(prefers-color-scheme: light)').matches) {
        applyTheme('light');
    } else {
        applyTheme('dark');
    }

    if (toggleBtn) {
        toggleBtn.addEventListener('click', () => {
            const current = html.getAttribute('data-theme') === 'light' ? 'light' : 'dark';
            const next    = current === 'light' ? 'dark' : 'light';
            localStorage.setItem('netlearn-theme', next);
            applyTheme(next);
        });
    }

    // Listen to OS theme changes only if user hasn't set an explicit preference
    if (window.matchMedia) {
        window.matchMedia('(prefers-color-scheme: light)').addEventListener('change', (e) => {
            if (!localStorage.getItem('netlearn-theme')) {
                applyTheme(e.matches ? 'light' : 'dark');
            }
        });
    }
});
