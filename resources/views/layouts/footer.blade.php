        </main>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const mobileMenuBtn = document.getElementById('mobileMenuBtn');
    const sidebar = document.getElementById('sidebar');
    const sidebarClose = document.getElementById('sidebarClose');
    const sidebarOverlay = document.getElementById('sidebarOverlay');

    function openSidebar() {
        if (!sidebar || !sidebarOverlay || !mobileMenuBtn) {
            return;
        }

        sidebar.classList.add('open');
        sidebarOverlay.classList.add('active');
        mobileMenuBtn.setAttribute('aria-expanded', 'true');
        document.body.classList.add('sidebar-open');

        if (sidebarClose) {
            sidebarClose.focus();
        }
    }

    function closeSidebar() {
        if (!sidebar || !sidebarOverlay || !mobileMenuBtn) {
            return;
        }

        sidebar.classList.remove('open');
        sidebarOverlay.classList.remove('active');
        mobileMenuBtn.setAttribute('aria-expanded', 'false');
        document.body.classList.remove('sidebar-open');

        mobileMenuBtn.focus();
    }

    if (mobileMenuBtn && sidebar) {
        mobileMenuBtn.addEventListener('click', () => {
            sidebar.classList.contains('open')
                ? closeSidebar()
                : openSidebar();
        });
    }

    if (sidebarClose) {
        sidebarClose.addEventListener('click', closeSidebar);
    }

    if (sidebarOverlay) {
        sidebarOverlay.addEventListener('click', closeSidebar);
    }

    document.addEventListener('keydown', (event) => {
        if (
            event.key === 'Escape' &&
            sidebar &&
            sidebar.classList.contains('open')
        ) {
            closeSidebar();
        }
    });

    document.querySelectorAll('.nav-item').forEach((link) => {
        link.addEventListener('click', () => {
            if (window.innerWidth <= 860) {
                closeSidebar();
            }
        });
    });
});
</script>