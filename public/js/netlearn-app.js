/**
 * NetLearn Native macOS-Inspired Frontend Experience Engine
 * Features:
 * - Native View Transitions API for seamless SPA cross-fades
 * - Floating Frosted Collapsible Sidebar with persisted state
 * - Bottom Slide-up Sheet Modals for immersive task/puzzle interaction
 * - Gamified Quiz Stepper with tactile micro-interactions & keyboard shortcuts
 * - Lecturer 3-Pane Finder interactions & inline contextual popovers
 */

(function () {
    'use strict';

    // ── 1. Floating Collapsible Sidebar ──
    function initSidebar() {
        const sidebar = document.getElementById('sidebar');
        const collapseBtn = document.getElementById('sidebarCollapseBtn');
        const mobileMenuBtn = document.getElementById('mobileMenuBtn');
        const overlay = document.getElementById('sidebarOverlay');

        if (!sidebar) return;

        // Restore collapsed state
        const isCollapsed = localStorage.getItem('netlearn_sidebar_collapsed') === 'true';
        if (isCollapsed && window.innerWidth > 1024) {
            sidebar.classList.add('collapsed');
            if (collapseBtn) {
                collapseBtn.setAttribute('aria-expanded', 'false');
                collapseBtn.setAttribute('title', 'Expand Sidebar');
            }
        } else if (collapseBtn) {
            collapseBtn.setAttribute('aria-expanded', 'true');
            collapseBtn.setAttribute('title', 'Collapse Sidebar');
        }

        if (collapseBtn) {
            collapseBtn.addEventListener('click', () => {
                sidebar.classList.toggle('collapsed');
                const state = sidebar.classList.contains('collapsed');
                localStorage.setItem('netlearn_sidebar_collapsed', state);
                collapseBtn.setAttribute('aria-expanded', (!state).toString());
                collapseBtn.setAttribute('title', state ? 'Expand Sidebar' : 'Collapse Sidebar');
            });
        }

        if (mobileMenuBtn) {
            mobileMenuBtn.addEventListener('click', () => {
                const isOpen = sidebar.classList.toggle('open');
                if (overlay) overlay.classList.toggle('active', isOpen);
                mobileMenuBtn.setAttribute('aria-expanded', isOpen.toString());
            });
        }

        if (overlay) {
            overlay.addEventListener('click', () => {
                sidebar.classList.remove('open');
                overlay.classList.remove('active');
                if (mobileMenuBtn) mobileMenuBtn.setAttribute('aria-expanded', 'false');
            });
        }

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && sidebar.classList.contains('open')) {
                sidebar.classList.remove('open');
                if (overlay) overlay.classList.remove('active');
                if (mobileMenuBtn) mobileMenuBtn.setAttribute('aria-expanded', 'false');
            }
        });
    }

    // ── 2. Native View Transitions SPA Cross-fade ──
    function initViewTransitions() {
        if (!document.startViewTransition) return;

        document.addEventListener('click', (e) => {
            const link = e.target.closest('a');
            if (!link) return;

            const href = link.getAttribute('href');
            if (!href || href.startsWith('#') || href.startsWith('javascript:') || link.target === '_blank') return;

            const targetUrl = new URL(link.href, window.location.origin);
            if (targetUrl.origin !== window.location.origin) return;

            // Don't intercept logout or external downloads
            if (href.includes('/logout') || link.hasAttribute('download')) return;

            // If link requests sheet modal, do not navigate
            if (link.hasAttribute('data-sheet-url')) return;

            e.preventDefault();

            fetch(targetUrl.href)
                .then(response => {
                    if (!response.ok) throw new Error('Network error');
                    return response.text();
                })
                .then(html => {
                    document.startViewTransition(() => {
                        const parser = new DOMParser();
                        const doc = parser.parseFromString(html, 'text/html');

                        // Update page title
                        document.title = doc.title;

                        // Swap content body
                        const currentBody = document.querySelector('.content-body');
                        const newBody = doc.querySelector('.content-body');
                        if (currentBody && newBody) {
                            currentBody.innerHTML = newBody.innerHTML;
                        }

                        // Update header title if present
                        const currentHeaderTitle = document.querySelector('.header-title');
                        const newHeaderTitle = doc.querySelector('.header-title');
                        if (currentHeaderTitle && newHeaderTitle) {
                            currentHeaderTitle.innerHTML = newHeaderTitle.innerHTML;
                        }

                        // Update active state in sidebar
                        const currentNav = document.querySelector('.sidebar-nav');
                        const newNav = doc.querySelector('.sidebar-nav');
                        if (currentNav && newNav) {
                            currentNav.innerHTML = newNav.innerHTML;
                        }

                        window.history.pushState({}, '', targetUrl.href);

                        // Re-initialize dynamic view components
                        initPageComponents();
                    });
                })
                .catch(() => {
                    window.location.href = targetUrl.href;
                });
        });

        window.addEventListener('popstate', () => {
            window.location.reload();
        });
    }

    // ── 3. Bottom Slide-up Sheet Modal for Tasks / Puzzles ──
    window.openSheetModal = function (title, badge, url, isIframe = true) {
        let backdrop = document.getElementById('sheetModalBackdrop');
        if (!backdrop) {
            backdrop = document.createElement('div');
            backdrop.id = 'sheetModalBackdrop';
            backdrop.className = 'sheet-backdrop';
            backdrop.innerHTML = `
                <div class="sheet-modal" role="dialog" aria-modal="true">
                    <div class="sheet-drag-handle"></div>
                    <div class="sheet-header">
                        <div class="sheet-title-group">
                            <span class="sheet-badge" id="sheetBadge">MISSION</span>
                            <h3 id="sheetTitle" style="font-size: 1.1rem; font-weight: 700; margin: 0;">Task Preview</h3>
                        </div>
                        <button class="sheet-close-btn" id="sheetCloseBtn" aria-label="Close Sheet">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                    <div class="sheet-body" id="sheetBody"></div>
                </div>
            `;
            document.body.appendChild(backdrop);

            backdrop.querySelector('#sheetCloseBtn').addEventListener('click', closeSheetModal);
            backdrop.addEventListener('click', (e) => {
                if (e.target === backdrop) closeSheetModal();
            });
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') closeSheetModal();
            });
        }

        document.getElementById('sheetTitle').textContent = title;
        document.getElementById('sheetBadge').textContent = badge || 'SIMULATION';

        const body = document.getElementById('sheetBody');
        if (isIframe) {
            body.innerHTML = `<iframe src="${url}" class="sheet-frame" title="${title}"></iframe>`;
        } else {
            body.innerHTML = `<div style="display:flex;align-items:center;justify-content:center;height:100%;"><div class="loading-spinner"></div></div>`;
            fetch(url)
                .then(res => res.text())
                .then(html => {
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');
                    const content = doc.querySelector('.content-body') || doc.body;
                    body.innerHTML = content.innerHTML;
                    initQuizStepper();
                })
                .catch(err => {
                    body.innerHTML = `<p style="color:#ef4444;text-align:center;">Failed to load mission content.</p>`;
                });
        }

        requestAnimationFrame(() => {
            backdrop.classList.add('active');
        });
    };

    window.closeSheetModal = function () {
        const backdrop = document.getElementById('sheetModalBackdrop');
        if (backdrop) {
            backdrop.classList.remove('active');
            setTimeout(() => {
                const body = document.getElementById('sheetBody');
                if (body) body.innerHTML = '';
            }, 450);
        }
    };

    // Attach sheet triggers
    function initSheetTriggers() {
        document.querySelectorAll('[data-sheet-url]').forEach(trigger => {
            trigger.removeEventListener('click', handleSheetClick);
            trigger.addEventListener('click', handleSheetClick);
        });
    }

    function handleSheetClick(e) {
        e.preventDefault();
        const url = this.getAttribute('data-sheet-url');
        const title = this.getAttribute('data-sheet-title') || 'Interactive Mission';
        const badge = this.getAttribute('data-sheet-badge') || 'SIMULATION';
        const isIframe = this.getAttribute('data-sheet-iframe') !== 'false';
        window.openSheetModal(title, badge, url, isIframe);
    }

    // ── 4. Gamified Quiz Stepper ──
    window.initQuizStepper = function () {
        const stepper = document.getElementById('quizStepper');
        if (!stepper) return;

        const slides = stepper.querySelectorAll('.quiz-slide');
        const dots = stepper.querySelectorAll('.stepper-node-dot');
        const prevBtn = document.getElementById('stepperPrevBtn');
        const nextBtn = document.getElementById('stepperNextBtn');
        const submitBtn = document.getElementById('stepperSubmitBtn');
        const counterCurrent = document.getElementById('stepperCurrentNum');
        const integrityBar = document.getElementById('stepperIntegrityBar');

        let currentIdx = 0;
        const total = slides.length;

        function updateStep(newIdx) {
            if (newIdx < 0 || newIdx >= total) return;
            currentIdx = newIdx;

            slides.forEach((slide, idx) => {
                slide.classList.toggle('active', idx === currentIdx);
            });

            dots.forEach((dot, idx) => {
                dot.classList.toggle('current', idx === currentIdx);
                dot.classList.toggle('completed', idx < currentIdx);
            });

            if (counterCurrent) counterCurrent.textContent = currentIdx + 1;
            if (integrityBar) {
                const pct = Math.round(((currentIdx + 1) / total) * 100);
                integrityBar.style.width = pct + '%';
            }

            if (prevBtn) prevBtn.style.display = currentIdx > 0 ? 'inline-flex' : 'none';
            if (nextBtn) nextBtn.style.display = currentIdx < total - 1 ? 'inline-flex' : 'none';
            if (submitBtn) submitBtn.style.display = currentIdx === total - 1 ? 'inline-flex' : 'none';
        }

        if (prevBtn) {
            prevBtn.addEventListener('click', (e) => {
                e.preventDefault();
                updateStep(currentIdx - 1);
            });
        }

        if (nextBtn) {
            nextBtn.addEventListener('click', (e) => {
                e.preventDefault();
                updateStep(currentIdx + 1);
            });
        }

        // Dot navigation
        dots.forEach((dot, idx) => {
            dot.addEventListener('click', () => updateStep(idx));
        });

        // Option selection
        stepper.querySelectorAll('.gamified-option').forEach(option => {
            option.addEventListener('click', function () {
                const radio = this.querySelector('input[type="radio"]');
                if (!radio) return;
                radio.checked = true;

                const parentCard = this.closest('.quiz-slide');
                if (parentCard) {
                    parentCard.querySelectorAll('.gamified-option').forEach(opt => opt.classList.remove('selected'));
                }
                this.classList.add('selected');

                // Mark dot completed
                if (dots[currentIdx]) dots[currentIdx].classList.add('completed');
            });
        });

        // Keyboard shortcuts (A, B, C, D and arrows)
        document.addEventListener('keydown', (e) => {
            if (!document.getElementById('quizStepper')) return;
            const activeSlide = slides[currentIdx];
            if (!activeSlide) return;

            const key = e.key.toUpperCase();
            if (['A', 'B', 'C', 'D'].includes(key)) {
                const targetOption = activeSlide.querySelector(`.gamified-option[data-option="${key}"]`);
                if (targetOption) targetOption.click();
            } else if (e.key === 'ArrowRight' && currentIdx < total - 1) {
                updateStep(currentIdx + 1);
            } else if (e.key === 'ArrowLeft' && currentIdx > 0) {
                updateStep(currentIdx - 1);
            }
        });

        updateStep(0);
    };

    // ── 5. Lecturer 3-Pane Finder Interaction ──
    window.initFinder = function () {
        const masterItems = document.querySelectorAll('.finder-list-item');
        if (!masterItems.length) return;

        masterItems.forEach(item => {
            item.addEventListener('click', function () {
                masterItems.forEach(i => i.classList.remove('active'));
                this.classList.add('active');

                // If detail targets exist
                const detailId = this.getAttribute('data-target-detail');
                if (detailId) {
                    document.querySelectorAll('.finder-detail-view').forEach(view => {
                        view.style.display = 'none';
                    });
                    const target = document.getElementById(detailId);
                    if (target) target.style.display = 'block';
                }
            });
        });

        // Finder search filter
        const searchInput = document.getElementById('finderSearchInput');
        if (searchInput) {
            searchInput.addEventListener('input', function () {
                const query = this.value.toLowerCase().trim();
                masterItems.forEach(item => {
                    const text = item.textContent.toLowerCase();
                    item.style.display = text.includes(query) ? 'flex' : 'none';
                });
            });
        }
    };

    // ── 6. Lecturer Analytics Charts Initializer ──
    let passFailChartInstance = null;
    let topicChartInstance = null;

    window.initLecturerCharts = function () {
        const dataScript = document.getElementById('lecturerChartData');
        const passFailCanvas = document.getElementById('passFailChart');
        const topicCanvas = document.getElementById('topicPerformanceChart');

        if (!dataScript || (!passFailCanvas && !topicCanvas)) return;
        if (typeof Chart === 'undefined') {
            console.warn('Chart.js not loaded yet');
            return;
        }

        try {
            const data = JSON.parse(dataScript.textContent);

            if (passFailChartInstance) {
                passFailChartInstance.destroy();
                passFailChartInstance = null;
            }
            if (topicChartInstance) {
                topicChartInstance.destroy();
                topicChartInstance = null;
            }

            if (passFailCanvas) {
                const hasPassFail = data.passFailValues && data.passFailValues.some(v => v > 0);
                passFailChartInstance = new Chart(passFailCanvas, {
                    type: 'doughnut',
                    data: {
                        labels: hasPassFail ? data.passFailLabels : ['No Attempts'],
                        datasets: [{
                            data: hasPassFail ? data.passFailValues : [1],
                            backgroundColor: hasPassFail ? ['#10b981', '#ef4444'] : ['rgba(255,255,255,0.08)'],
                            borderColor: 'rgba(13, 33, 56, 0.95)',
                            borderWidth: 3,
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: {
                                    color: 'rgba(255, 255, 255, 0.75)',
                                    font: { family: 'Inter', size: 11 },
                                    boxWidth: 12,
                                    padding: 10
                                }
                            }
                        }
                    }
                });
            }

            if (topicCanvas) {
                topicChartInstance = new Chart(topicCanvas, {
                    type: 'bar',
                    data: {
                        labels: data.topicLabels,
                        datasets: [{
                            label: 'Convergence %',
                            data: data.topicAverages,
                            backgroundColor: 'rgba(34, 211, 238, 0.65)',
                            hoverBackgroundColor: '#22d3ee',
                            borderRadius: 6,
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            x: {
                                ticks: {
                                    color: 'rgba(255, 255, 255, 0.55)',
                                    font: { family: 'Inter', size: 10 },
                                    maxRotation: 20,
                                    minRotation: 0
                                },
                                grid: { display: false }
                            },
                            y: {
                                beginAtZero: true,
                                max: 100,
                                ticks: {
                                    color: 'rgba(255, 255, 255, 0.55)',
                                    font: { family: 'Inter', size: 10 },
                                    stepSize: 25
                                },
                                grid: { color: 'rgba(255, 255, 255, 0.06)' }
                            }
                        },
                        plugins: {
                            legend: { display: false }
                        }
                    }
                });
            }
        } catch (err) {
            console.error('Failed to initialize lecturer charts:', err);
        }
    };

    // Master Initializer
    function initPageComponents() {
        initSidebar();
        initSheetTriggers();
        initQuizStepper();
        initFinder();
        initLecturerCharts();
    }

    document.addEventListener('DOMContentLoaded', () => {
        initSidebar();
        initViewTransitions();
        initSheetTriggers();
        initQuizStepper();
        initFinder();
        initLecturerCharts();
    });

})();
