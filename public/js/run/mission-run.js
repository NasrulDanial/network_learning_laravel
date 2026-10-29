/**
 * mission-run.js — Mission Run state machine.
 * BOOT → TICKET → PLAY (iframe per task) → HOP → … → DEBRIEF
 *
 * The server stays the judge: tasks submit to /puzzles/{id}/submit inside the iframe and
 * post their result here. This page only displays it (XP, stars and progress come from the server).
 */
(function () {
    'use strict';

    const cfg = window.runConfig;
    if (!cfg) return;

    const S = { BOOT: 'boot', TICKET: 'ticket', PLAY: 'play', HOP: 'hop', DEBRIEF: 'debrief' };
    const qs = (sel) => document.querySelector(sel);
    const fx = window.NetFX;
    const wait = (ms) => fx.wait(ms);

    const DEVICE = {
        pc: { glyph: '', name: 'Workstation PC-A' },
        switch: { glyph: '', name: 'Switch SW1' },
        router: { glyph: '', name: 'Router R1' },
        firewall: { glyph: '', name: 'Firewall FW1' },
        server: { glyph: '', name: 'Server SRV-1' },
    };
    const deviceName = (d) => (DEVICE[d] || DEVICE.pc).name;

    const root = qs('#run-root');
    const frame = qs('#run-frame');

    function announce(text) {
        const live = qs('#run-live');
        live.textContent = '';
        setTimeout(() => { live.textContent = text; }, 30);
    }

    function setState(state) {
        root.dataset.state = state;
        // Move focus to the new screen's heading so keyboard and screen-reader users follow along
        const heading = qs(`#scr-${state} h1`);
        if (heading) {
            heading.setAttribute('tabindex', '-1');
            heading.focus({ preventScroll: true });
        }
    }

    // ── HUD ──────────────────────────────────────────────────────────────
    const hud = {
        init() {
            qs('#hud-ticket').textContent = cfg.ticketCode;
            qs('#hud-title').textContent = cfg.title;
            this.drawMap();
            this.renderMute();
            qs('#hud-mute').addEventListener('click', () => {
                fx.setMuted(!fx.muted);
                this.renderMute();
                fx.sound('click');
            });
        },

        renderMute() {
            const btn = qs('#hud-mute');
            btn.setAttribute('aria-pressed', fx.muted ? 'true' : 'false');
            btn.title = fx.muted ? 'Unmute sound effects' : 'Mute sound effects';
            btn.querySelector('i').className = 'fa-solid ' + (fx.muted ? 'fa-volume-xmark' : 'fa-volume-high');
        },

        drawMap() {
            const svg = qs('#hud-map');
            const n = cfg.puzzles.length;
            const step = 74;
            const w = Math.max(160, 40 + (n - 1) * step + 40);
            svg.setAttribute('viewBox', `0 0 ${w} 58`);
            svg.innerHTML = '';
            const ns = 'http://www.w3.org/2000/svg';
            const x = (i) => 40 + i * step;

            for (let i = 0; i < n - 1; i++) {
                const line = document.createElementNS(ns, 'line');
                line.setAttribute('x1', x(i) + 15); line.setAttribute('y1', 20);
                line.setAttribute('x2', x(i + 1) - 15); line.setAttribute('y2', 20);
                line.setAttribute('class', 'map-link' + (cfg.puzzles[i].completed ? ' is-up' : ''));
                line.id = `map-link-${i}`;
                svg.appendChild(line);
            }

            cfg.puzzles.forEach((p, i) => {
                const g = document.createElementNS(ns, 'g');
                g.setAttribute('class', 'map-node' + (p.completed ? ' is-done' : ''));
                g.id = `map-node-${i}`;
                const c = document.createElementNS(ns, 'circle');
                c.setAttribute('cx', x(i)); c.setAttribute('cy', 20); c.setAttribute('r', 15);
                const icon = document.createElementNS(ns, 'text');
                icon.setAttribute('x', x(i)); icon.setAttribute('y', 24.5);
                icon.setAttribute('class', 'map-icon');
                icon.textContent = (DEVICE[p.device] || DEVICE.pc).glyph;
                const label = document.createElementNS(ns, 'text');
                label.setAttribute('x', x(i)); label.setAttribute('y', 52);
                label.setAttribute('class', 'map-label');
                label.textContent = `Task ${i + 1}`;
                const title = document.createElementNS(ns, 'title');
                title.textContent = `${deviceName(p.device)}: ${p.title}`;
                g.append(title, c, icon, label);
                svg.appendChild(g);
            });
            svg.setAttribute('aria-label', `Mission network map: ${n} tasks`);
        },

        setCurrent(i) {
            document.querySelectorAll('.map-node').forEach((el, idx) => el.classList.toggle('is-current', idx === i));
        },

        linkUp(i) {
            const node = qs(`#map-node-${i}`);
            if (node) { node.classList.add('is-done'); node.classList.remove('is-current'); }
            const link = qs(`#map-link-${i}`);
            if (!link) return;
            link.classList.add('is-up');
            if (fx.reduced) return;
            // A packet runs along the newly lit link
            const ns = 'http://www.w3.org/2000/svg';
            const dot = document.createElementNS(ns, 'circle');
            dot.setAttribute('r', 4);
            dot.setAttribute('class', 'map-packet');
            dot.setAttribute('cy', 20);
            const x1 = +link.getAttribute('x1');
            const x2 = +link.getAttribute('x2');
            dot.setAttribute('cx', x1);
            link.parentNode.appendChild(dot);
            dot.animate([{ cx: x1 }, { cx: x2 }].map((k) => ({ transform: `translateX(${k.cx - x1}px)` })), { duration: 600, easing: 'ease-in-out' })
                .onfinish = () => dot.remove();
        },

        health(max, used, broke) {
            const box = qs('#hud-health');
            box.hidden = false;
            box.innerHTML = '';
            if (!max) {
                box.innerHTML = '<i class="fa-solid fa-infinity" aria-hidden="true"></i><span>links</span>';
                box.setAttribute('aria-label', 'Unlimited attempts');
                return;
            }
            const left = Math.max(0, max - used);
            for (let k = 0; k < max; k++) {
                const seg = document.createElement('span');
                seg.className = 'seg' + (k < left ? '' : ' lost') + (broke && k === left ? ' breaking' : '');
                box.appendChild(seg);
            }
            box.setAttribute('aria-label', `${left} of ${max} attempts left on this task`);
        },

        streak(n) {
            const el = qs('#hud-streak');
            el.querySelector('span').textContent = `x${n}`;
            el.classList.toggle('is-hot', n >= 2);
        },

        xp(value) {
            const el = qs('#hud-xp');
            fx.countUp(el, Number(el.textContent) || 0, value, 600);
        },
    };

    // ── Run ──────────────────────────────────────────────────────────────
    const run = {
        i: Math.min(cfg.startIndex || 0, cfg.puzzles.length - 1),
        xp: 0,
        streak: 0,
        bestStreak: 0,
        firstTry: 0,
        played: 0,
        tries: {},
        startedAt: null,
        badges: [],
        ttlTimer: null,
        frameReady: false,

        async boot() {
            setState(S.BOOT);
            const key = `netlearn-booted-${cfg.missionId}`;
            let booted = false;
            try { booted = sessionStorage.getItem(key) === '1'; } catch (e) { /* storage blocked */ }

            if (!booted && !this.skipBoot) {
                const lines = [
                    '> Initialising NetLearn lab environment...',
                    `> Loading topology: ${cfg.title}`,
                    '> Connecting to Lab-R1 ... link up ✓',
                    `> Operator authenticated: ${cfg.studentName}`,
                ];
                const out = qs('#boot-lines');
                for (const line of lines) {
                    if (this.skipBoot) break;
                    const span = document.createElement('span');
                    out.appendChild(span);
                    await fx.typeText(span, line, 70);
                    out.appendChild(document.createTextNode('\n'));
                    await wait(120);
                }
                try { sessionStorage.setItem(key, '1'); } catch (e) { /* storage blocked */ }
            }

            if (!this.skipBoot) await this.countdown();
            this.ticket();
        },

        async countdown() {
            const el = qs('#countdown');
            fx.sound('boot');
            for (const n of [3, 2, 1]) {
                if (this.skipBoot) break;
                el.textContent = n;
                el.classList.remove('pop');
                void el.offsetWidth;
                el.classList.add('pop');
                fx.sound('tick');
                await wait(520);
            }
            el.textContent = '';
        },

        async ticket() {
            if (this.ticketShown) return;
            this.ticketShown = true;
            setState(S.TICKET);
            qs('#ticket-code').textContent = `Ticket ${cfg.ticketCode}`;
            qs('#ticket-heading').textContent = cfg.title;
            qs('#narrator-name').textContent = cfg.narrator;

            const list = qs('#ticket-tasks');
            list.innerHTML = '';
            cfg.puzzles.forEach((p) => {
                const li = document.createElement('li');
                li.className = p.completed ? 'is-done' : '';
                li.textContent = p.title;
                if (p.completed) {
                    const ok = document.createElement('span');
                    ok.className = 'task-done';
                    ok.textContent = ' ✓ done';
                    li.appendChild(ok);
                }
                list.appendChild(li);
            });

            const start = qs('#ticket-start');
            const resuming = cfg.puzzles.some((p) => p.completed) && !cfg.puzzles.every((p) => p.completed);
            start.innerHTML = `<i class="fa-solid fa-play" aria-hidden="true"></i> ${resuming ? 'Continue mission' : (cfg.puzzles.every((p) => p.completed) ? 'Replay mission' : 'Start mission')}`;
            await fx.typeText(qs('#ticket-text'), cfg.briefing, 55);
            start.focus();
        },

        play() {
            setState(S.PLAY);
            if (!this.startedAt) this.startedAt = Date.now();
            const p = cfg.puzzles[this.i];
            hud.setCurrent(this.i);
            hud.health(p.maxAttempts, p.attemptsUsed, false);
            this.frameReady = false;
            qs('#frame-loader').hidden = false;
            frame.src = p.url;
            announce(`Task ${this.i + 1} of ${cfg.puzzles.length}: ${p.title}`);
            this.startTtl();
        },

        frameLoaded() {
            this.frameReady = true;
            qs('#frame-loader').hidden = true;
        },

        startTtl() {
            clearInterval(this.ttlTimer);
            const el = qs('#hud-ttl');
            if (!cfg.ttl) { el.hidden = true; return; }
            el.hidden = false;
            let left = cfg.ttl;
            this.ttlExpired = false;
            const render = () => { el.textContent = `TTL ${left}s`; el.classList.toggle('is-low', left <= 10); };
            render();
            // The TTL never fails a task; it only affects the "fast response" note on the debrief
            this.ttlTimer = setInterval(() => {
                left = Math.max(0, left - 1);
                render();
                if (left === 0) { this.ttlExpired = true; clearInterval(this.ttlTimer); }
            }, 1000);
        },

        onResult(msg) {
            const p = cfg.puzzles[this.i];
            if (!p || Number(msg.puzzleId) !== Number(p.id)) return;

            this.tries[this.i] = (this.tries[this.i] || 0) + 1;
            p.attemptsUsed = msg.attemptsCount || p.attemptsUsed + 1;

            if (!msg.correct) {
                // XP is only earned by solving, so a wrong answer never adds any
                this.streak = 0;
                hud.streak(0);
                hud.health(p.maxAttempts, p.attemptsUsed, true);
                fx.flash('rgba(239,68,68,.18)');
                announce('Not quite. Check the feedback and try again.');
                return;
            }

            clearInterval(this.ttlTimer);
            this.played++;
            if (this.tries[this.i] === 1) {
                this.firstTry++;
                this.streak++;
            } else {
                this.streak = 0;
            }
            this.bestStreak = Math.max(this.bestStreak, this.streak);
            this.xp += (msg.earnedXp || 0) + (msg.missionCompleted ? (msg.missionXpReward || 0) : 0);
            (msg.newBadges || []).forEach((b) => { if (!this.badges.includes(b)) this.badges.push(b); });
            p.completed = true;
            p.stars = Math.max(p.stars || 0, msg.stars || 0);

            hud.streak(this.streak);
            hud.xp(this.xp);
            announce(`Correct. Link up. ${msg.earnedXp || 0} XP earned.`);
            setTimeout(() => this.hop(), fx.reduced ? 300 : 1400);
        },

        async hop() {
            hud.linkUp(this.i);
            if (this.i >= cfg.puzzles.length - 1) return this.debrief();
            this.i++;
            setState(S.HOP);
            const next = cfg.puzzles[this.i];
            qs('#hop-text').textContent = `Link up! Next hop: ${deviceName(next.device)}`;
            qs('#hop-sub').textContent = `Route learned. Forwarding to task ${this.i + 1}: ${next.title}`;
            fx.sound('linkup');
            await wait(1200);
            this.play();
        },

        skipLocked() {
            qs('#locked-modal').hidden = true;
            if (this.i >= cfg.puzzles.length - 1) return this.debrief();
            this.i++;
            this.play();
        },

        async debrief() {
            clearInterval(this.ttlTimer);
            frame.src = 'about:blank';
            setState(S.DEBRIEF);

            let sum = null;
            try {
                const res = await fetch(cfg.summaryUrl, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
                if (res.ok) sum = await res.json();
            } catch (e) { /* show what we have */ }
            sum = sum || { completed: false, stars: 0, hintsUsed: 0, rank: cfg.rankAtStart, rankProgressPct: 0, nextMission: null, ticketCode: cfg.ticketCode, debriefText: cfg.debriefText };

            const stamp = qs('#debrief-stamp');
            stamp.textContent = sum.completed ? `TICKET ${sum.ticketCode} — RESOLVED` : `TICKET ${sum.ticketCode} — IN PROGRESS`;
            stamp.classList.toggle('is-open', !sum.completed);
            stamp.classList.add('slam');
            fx.sound(sum.completed ? 'success' : 'error');
            qs('#debrief-heading').textContent = cfg.title;
            await wait(400);
            await fx.typeText(qs('#debrief-text'), sum.completed ? sum.debriefText : 'Some tasks still need work. Replay to finish the ticket.', 70);

            // Stars light up one by one with rising pitch
            const starsBox = qs('#debrief-stars');
            starsBox.innerHTML = '';
            starsBox.setAttribute('aria-label', `${sum.stars} of 3 stars`);
            const stars = [0, 1, 2].map(() => {
                const s = document.createElement('i');
                s.className = 'fa-solid fa-star';
                s.setAttribute('aria-hidden', 'true');
                starsBox.appendChild(s);
                return s;
            });
            for (let k = 0; k < sum.stars; k++) {
                await wait(380);
                stars[k].classList.add('lit');
                fx.sound('success', k);
            }

            const secs = Math.round((Date.now() - (this.startedAt || Date.now())) / 1000);
            qs('#stat-time').textContent = `${String(Math.floor(secs / 60)).padStart(2, '0')}:${String(secs % 60).padStart(2, '0')}`;
            qs('#stat-accuracy').textContent = this.played ? `${Math.round((this.firstTry / this.played) * 100)}% (${this.firstTry}/${this.played})` : '—';
            qs('#stat-streak').textContent = this.bestStreak ? `${this.bestStreak} in a row` : '—';
            qs('#stat-hints').textContent = String(sum.hintsUsed || 0);
            if (this.played && this.firstTry === this.played && !sum.hintsUsed) {
                qs('#debrief-text').textContent += ' Flawless run: every task solved on the first try.';
            }

            await wait(300);
            await fx.countUp(qs('#debrief-xp'), 0, this.xp, 900);
            qs('#debrief-rank').textContent = sum.rank;
            qs('#debrief-rank-pct').textContent = `${sum.rankProgressPct}% to next rank`;
            requestAnimationFrame(() => { qs('#debrief-rank-bar').style.width = `${sum.rankProgressPct}%`; });

            if (sum.rank && sum.rank !== cfg.rankAtStart) {
                const promo = qs('#debrief-promoted');
                promo.hidden = false;
                promo.textContent = `PROMOTED: ${sum.rank}`;
                fx.flash('rgba(250,204,21,.18)', 500);
                fx.sound('success', 3);
            }

            const badges = qs('#debrief-badges');
            badges.innerHTML = '';
            this.badges.forEach((name, k) => {
                const card = document.createElement('div');
                card.className = 'badge-card';
                card.style.animationDelay = `${k * 120}ms`;
                card.innerHTML = '<i class="fa-solid fa-award" aria-hidden="true"></i><span></span>';
                card.querySelector('span').textContent = `New badge: ${name}`;
                badges.appendChild(card);
            });

            const actions = qs('#debrief-actions');
            actions.innerHTML = '';
            const addBtn = (label, href, cls) => {
                const a = document.createElement('a');
                a.className = `run-cta ${cls || ''}`;
                a.href = href;
                a.textContent = label;
                actions.appendChild(a);
                return a;
            };
            let primary = null;
            if (sum.nextMission) primary = addBtn(`Next mission → ${sum.nextMission.title}`, sum.nextMission.runUrl, '');
            if (sum.stars < 3 || !sum.completed) {
                const replay = addBtn(sum.completed ? 'Replay for ★★★' : 'Replay mission', cfg.restartUrl, sum.nextMission ? 'run-cta--ghost' : '');
                primary = primary || replay;
            }
            const back = addBtn('Back to topic', cfg.topicUrl, 'run-cta--ghost');
            (primary || back).focus();

            announce(`${stamp.textContent}. ${sum.stars} of 3 stars. ${this.xp} XP earned in this run.`);
        },
    };

    // ── Wiring ───────────────────────────────────────────────────────────
    window.addEventListener('message', (e) => {
        if (e.origin !== window.location.origin) return;
        if (e.source !== frame.contentWindow) return;
        const m = e.data;
        if (!m || m.source !== 'netlearn') return;
        if (m.type === 'puzzle:ready') run.frameLoaded();
        if (m.type === 'puzzle:result') run.onResult(m);
        if (m.type === 'puzzle:locked') {
            qs('#locked-message').textContent = m.message || 'You have used all attempts on this task.';
            qs('#locked-modal').hidden = false;
            qs('#locked-skip').focus();
        }
    });

    // Fallback if a task page never reports ready (e.g. a script error): reveal it after load
    frame.addEventListener('load', () => {
        if (frame.src === 'about:blank') return;
        setTimeout(() => { if (!run.frameReady) run.frameLoaded(); }, 1500);
    });

    qs('#boot-skip').addEventListener('click', () => { run.skipBoot = true; run.ticket(); });
    qs('#ticket-start').addEventListener('click', () => {
        try { fx._audio() && fx._audio().resume(); } catch (e) { /* no audio */ }
        fx.sound('click');
        run.play();
    });

    const exitModal = qs('#exit-modal');
    qs('#exit-leave').href = cfg.topicUrl;
    qs('#locked-review').href = cfg.topicUrl;
    qs('#hud-exit').addEventListener('click', () => { exitModal.hidden = false; qs('#exit-stay').focus(); });
    qs('#exit-stay').addEventListener('click', () => { exitModal.hidden = true; qs('#hud-exit').focus(); });
    qs('#locked-skip').addEventListener('click', () => run.skipLocked());
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && !exitModal.hidden) { exitModal.hidden = true; qs('#hud-exit').focus(); }
    });

    hud.init();
    run.boot();

    // Exposed for QA/debugging
    window.netlearnRun = run;
})();
