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
                collapseBtn.setAttribute('title', 'Expand sidebar');
                collapseBtn.setAttribute('aria-label', 'Expand sidebar');
            }
        } else if (collapseBtn) {
            collapseBtn.setAttribute('aria-expanded', 'true');
            collapseBtn.setAttribute('title', 'Collapse sidebar');
            collapseBtn.setAttribute('aria-label', 'Collapse sidebar');
        }

        // initSidebar() runs again after in-app page transitions; bind handlers only once
        if (sidebar.dataset.handlersBound === 'true') return;
        sidebar.dataset.handlersBound = 'true';

        if (collapseBtn) {
            collapseBtn.addEventListener('click', () => {
                // On phones/tablets the sidebar is a drawer: this button acts as its close control
                if (window.innerWidth <= 1024) {
                    closeDrawer(true);
                    return;
                }
                sidebar.classList.toggle('collapsed');
                const state = sidebar.classList.contains('collapsed');
                localStorage.setItem('netlearn_sidebar_collapsed', state);
                collapseBtn.setAttribute('aria-expanded', (!state).toString());
                collapseBtn.setAttribute('title', state ? 'Expand sidebar' : 'Collapse sidebar');
                collapseBtn.setAttribute('aria-label', state ? 'Expand sidebar' : 'Collapse sidebar');
            });
        }

        function openDrawer() {
            sidebar.classList.add('open');
            if (overlay) overlay.classList.add('active');
            document.body.style.overflow = 'hidden';
            if (mobileMenuBtn) {
                mobileMenuBtn.setAttribute('aria-expanded', 'true');
                mobileMenuBtn.setAttribute('aria-label', 'Close menu');
            }
            if (collapseBtn) collapseBtn.setAttribute('aria-label', 'Close menu');
            // Move focus into the drawer so keyboard users land on the navigation
            const firstLink = sidebar.querySelector('.sidebar-nav a, a, button');
            if (firstLink) setTimeout(() => firstLink.focus(), 50);
        }

        function closeDrawer(returnFocus) {
            if (!sidebar.classList.contains('open')) return;
            sidebar.classList.remove('open');
            if (overlay) overlay.classList.remove('active');
            document.body.style.overflow = '';
            if (mobileMenuBtn) {
                mobileMenuBtn.setAttribute('aria-expanded', 'false');
                mobileMenuBtn.setAttribute('aria-label', 'Open menu');
                if (returnFocus) mobileMenuBtn.focus();
            }
            if (collapseBtn) {
                collapseBtn.setAttribute('aria-label', sidebar.classList.contains('collapsed') ? 'Expand sidebar' : 'Collapse sidebar');
            }
        }

        if (mobileMenuBtn) {
            mobileMenuBtn.addEventListener('click', () => {
                sidebar.classList.contains('open') ? closeDrawer(true) : openDrawer();
            });
        }

        if (overlay) {
            overlay.addEventListener('click', () => closeDrawer(true));
        }

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') closeDrawer(true);
        });

        // Following a navigation link on a phone should not leave the drawer covering the page
        sidebar.addEventListener('click', (e) => {
            if (e.target.closest('a') && window.innerWidth <= 1024) closeDrawer(false);
        });

        // Crossing the 1024px breakpoint: desktop keeps the saved collapse preference,
        // the mobile drawer always shows full labels and never keeps a stale scroll lock.
        window.addEventListener('resize', () => {
            if (window.innerWidth > 1024) {
                closeDrawer(false);
                const wantsCollapsed = localStorage.getItem('netlearn_sidebar_collapsed') === 'true';
                sidebar.classList.toggle('collapsed', wantsCollapsed);
            } else {
                sidebar.classList.remove('collapsed');
            }
        });
    }

    // ── 1b. Header account menu (avatar → profile, password, log out) ──
    function initAccountMenu() {
        const root = document.querySelector('[data-account-menu]');
        if (!root || root.dataset.bound === 'true') return;
        root.dataset.bound = 'true';

        const trigger = root.querySelector('.account-trigger');
        const menu = root.querySelector('.account-dropdown');
        const items = () => [...menu.querySelectorAll('.account-item')];

        function open(focusFirst) {
            menu.hidden = false;
            trigger.setAttribute('aria-expanded', 'true');
            if (focusFirst) items()[0]?.focus();
        }
        function close(returnFocus) {
            if (menu.hidden) return;
            menu.hidden = true;
            trigger.setAttribute('aria-expanded', 'false');
            if (returnFocus) trigger.focus();
        }

        trigger.addEventListener('click', () => (menu.hidden ? open(false) : close(false)));
        trigger.addEventListener('keydown', (e) => {
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                open(true);
            }
        });

        menu.addEventListener('keydown', (e) => {
            const list = items();
            const index = list.indexOf(document.activeElement);
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                list[(index + 1) % list.length].focus();
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                list[(index - 1 + list.length) % list.length].focus();
            } else if (e.key === 'Tab') {
                close(false);
            }
        });

        document.addEventListener('click', (e) => {
            if (!root.contains(e.target)) close(false);
        });
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') close(true);
        });
    }

    // ── 1c. Sound toggle (NetFX mute, remembered across pages) ──
    function initSoundToggle() {
        const btn = document.getElementById('soundToggle');
        if (!btn || !window.NetFX || btn.dataset.bound === 'true') return;
        btn.dataset.bound = 'true';
        const render = () => {
            const muted = window.NetFX.muted;
            btn.setAttribute('aria-pressed', muted ? 'true' : 'false');
            btn.title = muted ? 'Unmute sound effects' : 'Mute sound effects';
            btn.querySelector('i').className = 'fa-solid ' + (muted ? 'fa-volume-xmark' : 'fa-volume-high');
        };
        btn.addEventListener('click', () => {
            window.NetFX.setMuted(!window.NetFX.muted);
            render();
            window.NetFX.sound('click');
        });
        document.addEventListener('netfx:mute', render);
        render();
    }

    // ── 2. Page transitions ──
    // Full page loads only. The old fetch-and-swap navigation replaced just .content-body, so
    // page scripts (Phaser templates, puzzle editor, filters) never ran. The cross-fade now
    // comes from the CSS @view-transition rule in netlearn-polish.css.

    // Dialogs must live at <body> level: .main-content uses backdrop-filter, which makes
    // position:fixed children position against the panel instead of the screen.
    function hoistOverlays() {
        document.querySelectorAll('.nl-modal-backdrop, .quiz-slideover-backdrop').forEach((el) => {
            if (el.parentElement !== document.body) document.body.appendChild(el);
        });
    }

    // ── 3. Bottom Slide-up Sheet Modal for Tasks / Puzzles ──
    // options.center opens it as a centred dialog on desktop (phones keep the bottom sheet).
    let sheetReturnFocus = null;
    window.openSheetModal = function (title, badge, url, isIframe = true, options = {}) {
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
                if (e.key === 'Escape' && backdrop.classList.contains('active')) closeSheetModal();
            });
        }

        backdrop.querySelector('.sheet-modal').classList.toggle('sheet-modal--center', !!options.center);
        sheetReturnFocus = options.returnFocus || document.activeElement;
        document.body.style.overflow = 'hidden';

        document.getElementById('sheetTitle').textContent = title;
        document.getElementById('sheetBadge').textContent = badge || 'SIMULATION';

        const body = document.getElementById('sheetBody');
        if (isIframe) {
            // Build the frame with DOM APIs: title/url can contain lecturer-authored text
            const frame = document.createElement('iframe');
            frame.className = 'sheet-frame';
            frame.title = title;
            frame.src = url;
            body.replaceChildren(frame);
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
            document.getElementById('sheetCloseBtn')?.focus({ preventScroll: true });
        });
    };

    window.closeSheetModal = function () {
        const backdrop = document.getElementById('sheetModalBackdrop');
        if (backdrop) {
            backdrop.classList.remove('active');
            document.body.style.overflow = '';
            if (sheetReturnFocus && document.contains(sheetReturnFocus)) sheetReturnFocus.focus();
            sheetReturnFocus = null;
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
        window.openSheetModal(title, badge, url, isIframe, { center: this.getAttribute('data-sheet-center') === 'true', returnFocus: this });
    }

    // ── 4. Gamified Quiz Stepper ──
    // With data-check-url, each answer is checked (and locked) on the server as soon as it is
    // picked and comes back as ACK (correct) or RST (wrong) with the explanation. The student
    // moves forward only (Continue, then Finish on the last question). The server counts lives:
    // when they run out the attempt ends (LINK DOWN). Leaving mid-attempt asks first and records
    // a failed attempt. Without JS the radios and the Finish button still submit the plain form.
    window.initQuizStepper = function () {
        const stepper = document.getElementById('quizStepper');
        if (!stepper || stepper.dataset.stepperReady) return;
        stepper.dataset.stepperReady = '1';

        const form = document.getElementById('quizChallengeForm');
        const slides = stepper.querySelectorAll('.quiz-slide');
        const dots = stepper.querySelectorAll('.stepper-node-dot');
        const submitBtn = document.getElementById('stepperSubmitBtn');
        const counterCurrent = document.getElementById('stepperCurrentNum');
        const integrityBar = document.getElementById('stepperIntegrityBar');

        const checkUrl = stepper.dataset.checkUrl;
        const abandonUrl = stepper.dataset.abandonUrl;
        const checkMode = !!checkUrl && !!window.fetch;
        const csrf = document.querySelector('meta[name="csrf-token"]')?.content
            || stepper.querySelector('input[name="_token"]')?.value || '';
        const reduced = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        const lives = Number(stepper.dataset.lives) || 3;
        let livesLeft = stepper.dataset.livesLeft !== undefined ? Number(stepper.dataset.livesLeft) : lives;
        let terminated = stepper.dataset.terminated === '1';
        let finishing = false;
        const verdicts = {}; // slide index -> verdict
        let streak = 0;

        let currentIdx = 0;
        const total = slides.length;

        const questionIdOf = (slide) => {
            const radio = slide.querySelector('input[type="radio"]');
            const m = radio && radio.name.match(/answers\[(\d+)\]/);
            return m ? Number(m[1]) : null;
        };
        const isLocked = (idx) => !!verdicts[idx];
        const answeredCount = () => Object.keys(verdicts).length;
        const allLocked = () => answeredCount() === total;
        // Options are shuffled, so the letter a student sees is the option's position
        const labelFor = (slide, key) => slide.querySelector(`.gamified-option[data-option="${key}"]`)?.dataset.label || key;

        function updateStep(newIdx) {
            if (newIdx < 0 || newIdx >= total) return;
            currentIdx = newIdx;

            slides.forEach((slide, idx) => {
                slide.classList.toggle('active', idx === currentIdx);
            });

            dots.forEach((dot, idx) => {
                dot.classList.toggle('current', idx === currentIdx);
                if (!checkMode) dot.classList.toggle('completed', idx < currentIdx);
            });

            if (counterCurrent) counterCurrent.textContent = currentIdx + 1;
            if (integrityBar) {
                const pct = Math.round(((currentIdx + 1) / total) * 100);
                integrityBar.style.width = pct + '%';
            }

            // With checking, Finish lives in the last question's explanation box
            if (submitBtn) submitBtn.hidden = checkMode || currentIdx !== total - 1;
            renderSummary();
        }

        // ── Check mode helpers ──
        const hud = document.getElementById('quizHud');
        const live = document.getElementById('quizLive');
        if (checkMode && hud) hud.hidden = false;

        function renderHud() {
            const sent = answeredCount();
            const acks = Object.values(verdicts).filter((v) => v.correct).length;
            const set = (id, v) => { const el = document.getElementById(id); if (el) el.textContent = v; };
            set('quizStreak', 'x' + streak);
            set('quizAckCount', acks);
            set('quizAnsweredCount', sent);
            const health = document.getElementById('quizHealth');
            if (health) {
                health.querySelectorAll('i').forEach((h, i) => h.classList.toggle('is-lost', i >= livesLeft));
                health.setAttribute('aria-label', `Lives ${livesLeft} of ${lives}`);
            }
        }

        function renderSummary() {
            const box = document.getElementById('quizSummary');
            if (!box || !checkMode) return;
            if (!allLocked() || currentIdx !== total - 1) { box.hidden = true; return; }
            const acks = Object.values(verdicts).filter((v) => v.correct).length;
            box.hidden = false;
            box.innerHTML = `<i class="fa-solid fa-satellite-dish" aria-hidden="true"></i> Transmission summary: <strong>${acks}/${total} ACK</strong>`
                + (acks === total ? ' <span class="quiz-flawless">Flawless</span>' : '');
        }

        // A packet travels from the option to the server icon and returns ACK or RST
        function flyPacket(option, ok) {
            const server = document.getElementById('quizServer');
            if (reduced || !server || !option.animate) return Promise.resolve();
            const a = option.querySelector('.option-key-badge').getBoundingClientRect();
            const b = server.getBoundingClientRect();
            const pkt = document.createElement('span');
            pkt.className = 'quiz-packet ' + (ok ? 'is-ack' : 'is-rst');
            pkt.textContent = ok ? 'ACK' : 'RST';
            document.body.appendChild(pkt);
            const from = `translate(${a.left + a.width / 2}px, ${a.top + a.height / 2}px)`;
            const to = `translate(${b.left + b.width / 2}px, ${b.top + b.height / 2}px)`;
            const anim = pkt.animate([
                { transform: from + ' scale(.6)', opacity: 0 },
                { transform: to + ' scale(.8)', opacity: 1, offset: 0.45 },
                { transform: from + ' scale(1)', opacity: 1, offset: 0.9 },
                { transform: from + ' scale(1)', opacity: 0 },
            ], { duration: 900, easing: 'ease-in-out' });
            // Background tabs can pause animations, so never let the verdict wait longer than this
            const timeout = new Promise((r) => setTimeout(r, 1200));
            return Promise.race([anim.finished.catch(() => {}), timeout]).then(() => pkt.remove());
        }

        function applyVerdict(idx, v, animate) {
            const slide = slides[idx];
            verdicts[idx] = v;
            slide.classList.add('is-locked');
            slide.querySelectorAll('.gamified-option').forEach((opt) => {
                const key = opt.dataset.option;
                const radio = opt.querySelector('input[type="radio"]');
                if (radio) radio.checked = key === v.answer; // server's locked answer wins
                opt.classList.toggle('selected', key === v.answer);
                opt.classList.toggle('is-ack', key === v.answer && v.correct);
                opt.classList.toggle('is-rst', key === v.answer && !v.correct);
                opt.classList.toggle('is-correct', key === v.correct_answer && !v.correct);
                opt.setAttribute('aria-disabled', 'true');
                if (animate && key === v.answer && !v.correct && !reduced) {
                    opt.classList.add('is-shaking');
                    setTimeout(() => opt.classList.remove('is-shaking'), 450);
                }
            });
            if (dots[idx]) {
                dots[idx].classList.add(v.correct ? 'is-ack' : 'is-rst');
                dots[idx].classList.remove('completed');
            }

            const box = slide.querySelector('.quiz-explain');
            if (box) {
                box.hidden = false;
                box.classList.toggle('is-ack', v.correct);
                box.classList.toggle('is-rst', !v.correct);
                box.querySelector('.quiz-explain-head').textContent = v.correct
                    ? 'ACK: correct'
                    : `RST: the correct answer is ${labelFor(slide, v.correct_answer)}`;
                const text = v.explanation || (v.correct ? 'Nice work.' : 'Review this one before the next attempt.');
                const p = box.querySelector('.quiz-explain-text');
                if (animate && window.NetFX && NetFX.typeText) NetFX.typeText(p, text, 90);
                else p.textContent = text;
                const cont = box.querySelector('.quiz-continue');
                if (cont) {
                    cont.hidden = terminated;
                    if (animate && !terminated) cont.focus({ preventScroll: true });
                }
            }
        }

        function recomputeStreak() {
            streak = 0;
            for (let i = 0; i < total; i++) {
                if (!verdicts[i]) continue;
                streak = verdicts[i].correct ? streak + 1 : 0;
            }
        }

        // ── Dialogs ──
        const openDialog = (d) => { if (!d) return; if (d.showModal) { if (!d.open) d.showModal(); } else d.setAttribute('open', ''); };
        const closeDialog = (d) => { if (!d) return; if (d.close) d.close(); else d.removeAttribute('open'); };
        const linkDown = document.getElementById('quizLinkDown');
        const leaveDialog = document.getElementById('quizLeaveDialog');

        function finish() {
            finishing = true;
            window.removeEventListener('beforeunload', onBeforeUnload);
            // form.submit() skips the radios' "required": after LINK DOWN some stay unanswered
            if (form) form.submit();
        }

        function showLinkDown(animate) {
            terminated = true;
            slides.forEach((s) => s.classList.add('is-locked'));
            stepper.querySelectorAll('.quiz-continue').forEach((b) => { b.hidden = true; });
            const correct = Object.values(verdicts).filter((v) => v.correct).length;
            const stats = document.getElementById('linkDownStats');
            if (stats) stats.textContent = `You answered ${answeredCount()} of ${total}, ${correct} correct.`;
            if (animate && window.NetFX) {
                NetFX.sound('drop');
                if (NetFX.flash) NetFX.flash('rgba(239,68,68,.3)');
            }
            if (live) live.textContent = 'Link down. You are out of lives, so passing is no longer possible.';
            openDialog(linkDown);
            document.getElementById('linkDownResult')?.focus();
        }

        async function checkAnswer(idx, option) {
            const slide = slides[idx];
            if (terminated || isLocked(idx) || slide.classList.contains('is-checking')) return;
            const qid = questionIdOf(slide);
            slide.classList.add('is-checking');
            slide.querySelectorAll('.gamified-option').forEach((opt) => opt.classList.toggle('selected', opt === option));
            if (window.NetFX) NetFX.sound('packet');

            try {
                const res = await fetch(checkUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
                    body: JSON.stringify({ question_id: qid, answer: option.dataset.option }),
                });
                if (res.status === 409) { showLinkDown(true); return; }
                if (!res.ok) throw new Error('HTTP ' + res.status);
                const v = await res.json();
                await flyPacket(option, v.correct);
                if (typeof v.lives_left === 'number') livesLeft = v.lives_left; // the server counts lives
                terminated = !!v.terminated;
                applyVerdict(idx, v, true);
                recomputeStreak();
                renderHud();
                if (window.NetFX) {
                    NetFX.sound(v.correct ? 'success' : 'error');
                    if (!v.correct) NetFX.vibrate && NetFX.vibrate(80);
                }
                if (live) live.textContent = v.correct
                    ? `Question ${idx + 1}: correct.`
                    : `Question ${idx + 1}: wrong. The correct answer is ${labelFor(slide, v.correct_answer)}. ${livesLeft} ${livesLeft === 1 ? 'life' : 'lives'} left.`;
                updateStep(currentIdx);
                if (terminated) showLinkDown(true);
            } catch (e) {
                slide.querySelectorAll('.gamified-option').forEach((opt) => opt.classList.remove('selected'));
                if (live) live.textContent = 'Could not check that answer. Please try again.';
            } finally {
                slide.classList.remove('is-checking');
            }
        }

        // ── Leaving mid-attempt ──
        const attemptInProgress = () => checkMode && answeredCount() > 0 && !finishing;
        function onBeforeUnload(e) {
            if (!attemptInProgress()) return;
            e.preventDefault();
            e.returnValue = ''; // the browser shows its own wording
        }

        let pendingHref = null;
        function interceptLinks(e) {
            const link = e.target.closest && e.target.closest('a[href]');
            if (!link || !attemptInProgress()) return;
            if (form && form.contains(link)) return;
            if (link.target === '_blank' || e.ctrlKey || e.metaKey || e.shiftKey || link.getAttribute('href').startsWith('#')) return;
            e.preventDefault();
            e.stopPropagation();
            pendingHref = link.href;
            openDialog(leaveDialog);
            document.getElementById('leaveStay')?.focus();
        }

        if (checkMode) {
            window.addEventListener('beforeunload', onBeforeUnload);
            document.addEventListener('click', interceptLinks, true);

            document.getElementById('leaveStay')?.addEventListener('click', () => { pendingHref = null; closeDialog(leaveDialog); });
            document.getElementById('leaveConfirm')?.addEventListener('click', async (e) => {
                e.currentTarget.disabled = true;
                finishing = true;
                window.removeEventListener('beforeunload', onBeforeUnload);
                let target = pendingHref;
                try {
                    const res = await fetch(abandonUrl, {
                        method: 'POST',
                        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
                    });
                    const data = await res.json().catch(() => ({}));
                    target = target || data.redirect;
                } catch (err) { /* leave anyway: the server clears the attempt on the next visit */ }
                window.location.href = target || '/student/quiz';
            });
            document.getElementById('linkDownResult')?.addEventListener('click', finish);
            // Esc must not dismiss LINK DOWN: the only way on is the result
            linkDown?.addEventListener('cancel', (e) => e.preventDefault());

            stepper.querySelectorAll('.quiz-continue').forEach((btn) => {
                btn.addEventListener('click', () => {
                    if (currentIdx === total - 1) finish();
                    else updateStep(currentIdx + 1);
                    slides[currentIdx]?.querySelector('.question-title')?.focus?.();
                });
            });

            let restored = {};
            try { restored = JSON.parse(stepper.dataset.checked || '{}') || {}; } catch (e) { restored = {}; }
            slides.forEach((slide, idx) => {
                const v = restored[questionIdOf(slide)];
                if (v) applyVerdict(idx, v, false);
            });
            recomputeStreak();
            renderHud();
        }

        // Option selection
        stepper.querySelectorAll('.gamified-option').forEach(option => {
            option.addEventListener('click', function (e) {
                const radio = this.querySelector('input[type="radio"]');
                if (!radio) return;
                const parentCard = this.closest('.quiz-slide');
                const idx = Array.prototype.indexOf.call(slides, parentCard);

                if (checkMode) {
                    e.preventDefault(); // the server decides what gets locked in
                    checkAnswer(idx, this);
                    return;
                }

                radio.checked = true;
                if (parentCard) {
                    parentCard.querySelectorAll('.gamified-option').forEach(opt => opt.classList.remove('selected'));
                }
                this.classList.add('selected');

                // Mark dot completed
                if (dots[currentIdx]) dots[currentIdx].classList.add('completed');
            });
        });

        // Keyboard: A–D answer by the letter shown, Enter continues (forward only)
        document.addEventListener('keydown', (e) => {
            if (!document.getElementById('quizStepper')) return;
            if (e.target.closest && e.target.closest('input[type="text"], textarea, select, dialog')) return;
            const activeSlide = slides[currentIdx];
            if (!activeSlide) return;

            const key = e.key.toUpperCase();
            if (['A', 'B', 'C', 'D'].includes(key) && !e.ctrlKey && !e.metaKey && !e.altKey) {
                const targetOption = activeSlide.querySelector(`.gamified-option[data-label="${key}"]`)
                    || activeSlide.querySelector(`.gamified-option[data-option="${key}"]`);
                if (targetOption) targetOption.click();
            } else if (e.key === 'Enter' && checkMode && e.target.tagName !== 'BUTTON' && e.target.tagName !== 'A') {
                const cont = activeSlide.querySelector('.quiz-explain:not([hidden]) .quiz-continue:not([hidden])');
                if (cont) { e.preventDefault(); cont.click(); }
            }
        });

        // Resume at the first unanswered question
        const resumeAt = checkMode ? [...Array(total).keys()].find((i) => !isLocked(i)) : 0;
        updateStep(resumeAt === undefined ? total - 1 : resumeAt);
        if (checkMode && terminated) showLinkDown(false);
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

            // Read colours from the active theme so labels stay legible in light and dark mode
            const rootStyle = getComputedStyle(document.documentElement);
            const isLight = document.documentElement.getAttribute('data-theme') === 'light';
            const textColor = rootStyle.getPropertyValue('--nl-text-secondary').trim() || 'rgba(255, 255, 255, 0.75)';
            const gridColor = isLight ? 'rgba(15, 23, 42, 0.08)' : 'rgba(255, 255, 255, 0.06)';
            const surfaceColor = isLight ? '#FFFFFF' : 'rgba(13, 33, 56, 0.95)';
            const barColor = isLight ? 'rgba(14, 116, 144, 0.75)' : 'rgba(34, 211, 238, 0.65)';

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
                            borderColor: surfaceColor,
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
                                    color: textColor,
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
                // Horizontal bars: long topic names stay readable, 0–100% axis is fixed
                const shorten = (label) => label.length > 34 ? label.slice(0, 33) + '…' : label;
                const valueLabels = {
                    id: 'valueLabels',
                    afterDatasetsDraw(chart) {
                        const { ctx } = chart;
                        ctx.save();
                        ctx.font = '600 11px Inter, sans-serif';
                        ctx.fillStyle = textColor;
                        ctx.textBaseline = 'middle';
                        chart.getDatasetMeta(0).data.forEach((bar, i) => {
                            const value = chart.data.datasets[0].data[i];
                            ctx.fillText(value + '%', bar.x + 6, bar.y);
                        });
                        ctx.restore();
                    },
                };

                topicChartInstance = new Chart(topicCanvas, {
                    type: 'bar',
                    data: {
                        labels: data.topicLabels.map(shorten),
                        datasets: [{
                            label: 'Average quiz score',
                            data: data.topicAverages,
                            backgroundColor: barColor,
                            hoverBackgroundColor: isLight ? '#155E75' : '#22d3ee',
                            borderRadius: 6,
                            barThickness: 18,
                        }]
                    },
                    options: {
                        indexAxis: 'y',
                        responsive: true,
                        maintainAspectRatio: false,
                        layout: { padding: { right: 36 } },
                        scales: {
                            x: {
                                beginAtZero: true,
                                max: 100,
                                ticks: {
                                    color: textColor,
                                    font: { family: 'Inter', size: 10 },
                                    stepSize: 25,
                                    callback: (v) => v + '%'
                                },
                                grid: { color: gridColor }
                            },
                            y: {
                                ticks: {
                                    color: textColor,
                                    font: { family: 'Inter', size: 11 },
                                    autoSkip: false
                                },
                                grid: { display: false }
                            }
                        },
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                callbacks: {
                                    title: (items) => data.topicLabels[items[0].dataIndex],
                                    label: (item) => {
                                        const attempts = (data.topicAttempts || [])[item.dataIndex] || 0;
                                        return `Average ${item.raw}% across ${attempts} attempt${attempts === 1 ? '' : 's'}`;
                                    }
                                }
                            }
                        }
                    },
                    plugins: [valueLabels]
                });
            }
        } catch (err) {
            console.error('Failed to initialize lecturer charts:', err);
        }
    };


    // Redraw theme-dependent charts when the user switches light/dark mode
    const previousThemeHook = window.onThemeChange;
    window.onThemeChange = function (theme) {
        if (typeof previousThemeHook === 'function') previousThemeHook(theme);
        initLecturerCharts();
    };

    document.addEventListener('DOMContentLoaded', () => {
        initSidebar();
        initAccountMenu();
        initSoundToggle();
        hoistOverlays();
        initSheetTriggers();
        initQuizStepper();
        initFinder();
        initLecturerCharts();
    });

})();
