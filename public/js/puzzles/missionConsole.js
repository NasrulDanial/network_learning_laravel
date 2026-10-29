/**
 * missionConsole.js
 * The "Operator status" panel beside the puzzle board: mission health, XP at stake, the hint and
 * a short event log. It only REACTS to what the server says (submit response, hint response); it
 * never decides correctness or XP.
 *
 * Mission health (numbers come from the server, see LinkHealthService): ONE bar for the whole
 * mission run, split in three segments that are the mission's three stars. A wrong answer does
 * damage scaled by how wrong it was and by repeat misses; solving a task does not refill the bar,
 * so it carries over until the mission ends; it never reaches zero ("safe mode"). The stars left
 * when the last task is solved are the mission's rating.
 * Hint: costs XP the moment it is revealed (unless free) and the cost is shown on the spot,
 * both here and, inside a Mission Run, on the run HUD (puzzle:hint message).
 */
window.MissionConsole = (function () {
    'use strict';

    const $ = (id) => document.getElementById(id);
    const reduced = () => !!(window.NetFX && NetFX.reduced);
    const sound = (name) => { if (window.NetFX) NetFX.sound(name); };

    const st = {
        max: 0, used: 0, tryWrong: 0, tryHint: false, hasMission: false,
        points: 100, penalty: 10, penaltyPaid: false, solved: false, freeAfter: 2,
        hp: 100, xp: 0, ready: false, preview: false,
    };
    const SAFE_FLOOR = 10;

    // ── Rules mirrored from the server (display only) ───────────────────
    // The three segments of the bar are the three stars: 67-100 = 3, 34-66 = 2, below = 1
    const starsFor = (hp) => (hp >= 67 ? 3 : (hp >= 34 ? 2 : 1));
    const inSafeMode = () => st.hp <= SAFE_FLOOR;

    function stake() {
        return st.solved ? 0 : Math.max(0, st.points - (st.penaltyPaid ? st.penalty : 0));
    }

    // ── Small DOM helpers ───────────────────────────────────────────────
    function floatText(host, text, cls) {
        if (!host || reduced()) return;
        const f = document.createElement('span');
        f.className = 'mc-float ' + (cls || '');
        f.textContent = text;
        f.setAttribute('aria-hidden', 'true');
        host.appendChild(f);
        setTimeout(() => f.remove(), 1200);
    }

    function pulse(el, cls) {
        if (!el) return;
        el.classList.remove(cls);
        void el.offsetWidth; // restart the CSS animation
        el.classList.add(cls);
        setTimeout(() => el.classList.remove(cls), 800);
    }

    function log(text, kind) {
        const list = $('mc-log');
        if (!list) return;
        const li = document.createElement('li');
        li.className = 'mc-log-line' + (kind ? ' is-' + kind : '');
        li.textContent = text;
        list.prepend(li);
        while (list.children.length > 4) list.lastElementChild.remove();
    }

    // ── Health ──────────────────────────────────────────────────────────
    function renderNotches() {
        const box = $('mc-bar-notches');
        if (!box) return;
        const n = 3; // one segment per star
        box.replaceChildren();
        for (let i = 1; i < n; i++) {
            const tick = document.createElement('i');
            tick.style.left = `${(i / n) * 100}%`;
            box.appendChild(tick);
        }
    }

    function healthSub() {
        const stars = starsFor(st.hp);
        let text;
        if (inSafeMode()) text = 'Safe mode: you can still finish, but only for ★.';
        else if (st.hp >= 100) text = 'Full health: finish with no damage for ★★★.';
        else text = `${'★'.repeat(stars)} if you finish now. Damage carries over to the next task.`;
        if (st.max > 0) {
            const left = Math.max(0, st.max - st.used);
            text += left === 0 ? ' No attempts left on this task.' : ` ${left} of ${st.max} attempts left on this task.`;
        }
        return text;
    }

    function setHp(next, { animate = false, damage = 0 } = {}) {
        const prev = st.hp;
        st.hp = next;
        const fill = $('mc-bar-fill');
        const ghost = $('mc-bar-ghost');
        const bar = $('mc-bar');
        const num = $('mc-hp-num');
        const card = $('mc-health');
        if (!fill || !num) return;

        fill.style.width = next + '%';
        // The ghost trails behind so the lost chunk is visible for a moment
        if (animate && next < prev && !reduced()) setTimeout(() => { ghost.style.width = next + '%'; }, 520);
        else ghost.style.width = next + '%';

        bar.setAttribute('aria-valuenow', String(next));
        bar.classList.toggle('is-ok', next > 60);
        bar.classList.toggle('is-warn', next <= 60 && next > 30);
        bar.classList.toggle('is-crit', next <= 30);
        card.classList.toggle('is-down', next === 0);

        if (animate && window.NetFX) NetFX.countUp(num, prev, next, 600);
        else num.textContent = next;

        const heavy = (damage || prev - next) >= 30;
        card.classList.toggle('is-safe', inSafeMode());
        const badge = $('mc-safe');
        if (badge) badge.hidden = !inSafeMode();

        if (animate && next < prev) {
            pulse(card, heavy ? 'is-hit-heavy' : 'is-hit');
            floatText(card, `−${damage || prev - next}`, 'mc-float--red');
        } else if (animate && next > prev) {
            pulse(card, 'is-heal');
            floatText(card, `+${next - prev}`, 'mc-float--green');
        }
        renderStars(animate);
        $('mc-health-sub').textContent = healthSub();
    }

    // ── Stars: the three segments of the bar ────────────────────────────
    function renderStars(animate, { final = false } = {}) {
        const box = $('mc-stars');
        if (!box) return;
        const lit = starsFor(st.hp);
        box.querySelectorAll('i').forEach((star, i) => {
            const on = i < lit;
            const was = star.classList.contains('is-lit');
            star.classList.toggle('is-lit', on);
            if (animate && !reduced()) {
                if (was && !on) pulse(star, 'is-cracked'); // a segment ran dry: its star breaks
                if (final && on) {
                    star.style.animationDelay = `${i * 140}ms`;
                    pulse(star, 'is-pop');
                }
            }
        });
        box.setAttribute('aria-label', final ? `${lit} of 3 stars earned` : `${lit} of 3 stars at this health`);
    }

    // ── XP at stake ─────────────────────────────────────────────────────
    function renderXp(animate) {
        const num = $('mc-xp-num');
        const title = $('mc-xp-title');
        const sub = $('mc-xp-sub');
        if (!num) return;
        const next = stake();
        const prev = st.xp;
        st.xp = next;

        if (st.solved && !animate) {
            title.textContent = 'XP already earned';
            sub.textContent = 'Replays give stars, not more XP.';
        } else if (st.penaltyPaid) {
            sub.textContent = `Hint cost applied: −${st.penalty} XP.`;
        }

        if (animate && window.NetFX) NetFX.countUp(num, prev, next, 700);
        else num.textContent = next;
    }

    // ── Public: result of an answer ─────────────────────────────────────
    function onResult(data, correct) {
        if (!data) return;
        if (correct) {
            st.solved = true;
            const xpCard = $('mc-xp');
            if (!st.preview) {
                const earned = data.earned_xp || 0;
                $('mc-xp-title').textContent = 'XP earned';
                $('mc-xp-sub').textContent = earned > 0 ? 'Added to your total.' : 'Already earned before.';
                if (window.NetFX) NetFX.countUp($('mc-xp-num'), st.xp, earned, 800);
                else $('mc-xp-num').textContent = earned;
                if (earned > 0) floatText(xpCard, `+${earned}`, 'mc-float--green');
                pulse(xpCard, 'is-gain');
            }
            // A puzzle without a mission has its own bar, refilled by the server on a solve
            if (typeof data.link_health === 'number' && data.link_health !== st.hp) setHp(data.link_health, { animate: true });
            log(st.preview ? '> Preview: correct answer.' : `> Link up. ${data.earned_xp || 0} XP earned.`, 'ok');
            if (data.run_complete) {
                renderStars(true, { final: true });
                $('mc-health')?.classList.add('is-clear');
                log(`> Mission complete: ${data.run_stars} of 3 stars.`, 'ok');
            } else if (st.hasMission && !st.preview) {
                log(`> Health carries over: ${st.hp}/100.`);
            }
            return;
        }

        const before = st.hp;
        if (st.preview) {
            st.tryWrong += 1;
            st.used += 1;
        } else {
            st.used = typeof data.attempts_count === 'number' ? data.attempts_count : st.used + 1;
            st.tryWrong = typeof data.try_attempts === 'number' ? data.try_attempts : st.tryWrong + 1;
            if (typeof data.try_hint_used === 'boolean') st.tryHint = data.try_hint_used;
        }
        if (typeof data.max_attempts === 'number') st.max = data.max_attempts;

        // The server did the maths (severity, repeat-miss escalation, safe-mode floor)
        const next = typeof data.link_health === 'number' ? data.link_health : Math.max(SAFE_FLOOR, before - 24);
        const dealt = typeof data.damage === 'number' ? data.damage : before - next;
        const wasSafe = inSafeMode();
        const starsBefore = starsFor(before);
        setHp(next, { animate: true, damage: dealt });

        const diag = data.diagnostics;
        const how = diag && diag.total > 1 ? ` (${diag.correct_count}/${diag.total} correct)` : '';
        const repeat = data.damage_multiplier > 1 ? `, repeat miss x${data.damage_multiplier}` : '';
        if (next === 0) log('> LINK DOWN: out of attempts.', 'bad');
        else if (dealt === 0) log(`> Wrong answer${how}: already in safe mode, no further damage.`, 'bad');
        else log(`> Wrong answer${how}: link health −${dealt}${repeat}.`, 'bad');
        if (starsFor(next) < starsBefore) log(`> A star was lost: ${starsFor(next)} left.`, 'bad');
        if (inSafeMode() && !wasSafe) {
            log('> Safe mode: retries stay unlimited.', 'bad');
            if (window.NetFX) NetFX.flash('rgba(239,68,68,.18)', 500);
        }
        if (data.hint_free) unlockFreeHint();
    }

    // ── Hint ────────────────────────────────────────────────────────────
    function unlockFreeHint() {
        const btn = $('hint-button');
        if (!btn || btn.dataset.free === 'true') return;
        btn.dataset.free = 'true';
        $('hint-button-label').textContent = 'Reveal free hint';
        const cost = $('hint-cost');
        if (cost) cost.hidden = true;
        const state = $('mc-hint-state');
        if (state) state.textContent = 'FREE';
        pulse($('mc-hint'), 'is-gain');
        log('> Hint unlocked for free.', 'ok');
    }

    function hideConfirm() {
        const box = $('hint-confirm');
        if (box) box.hidden = true;
    }

    function applyCharge(penalty) {
        // Shown on the spot: panel card first, the run HUD through the parent message
        st.penaltyPaid = true;
        st.penalty = penalty || st.penalty;
        st.tryHint = true;
        const card = $('mc-xp');
        renderXp(true);
        pulse(card, 'is-loss');
        floatText(card, `−${st.penalty} XP`, 'mc-float--red');
        sound('drop');
        log(`> Hint purchased: −${st.penalty} XP.`, 'bad');
    }

    function initHint() {
        const btn = $('hint-button');
        const confirmBox = $('hint-confirm');
        const out = $('hint-text');
        if (!btn || !out) return;

        const done = (text, charged, penalty) => {
            hideConfirm();
            btn.hidden = true;
            $('hint-lead')?.setAttribute('hidden', '');
            out.hidden = false;
            out.classList.add('is-revealed');
            const state = $('mc-hint-state');
            if (state) state.textContent = charged ? `−${penalty} XP paid` : (st.penaltyPaid ? 'Paid' : 'Free');
            window.NetFX ? NetFX.typeText(out, text, 60) : (out.textContent = text);
            if (charged) applyCharge(penalty);
            else log('> Hint revealed.', 'ok');
            PuzzleUtils.postToParent({ type: 'puzzle:hint', charged: !!charged, penalty: charged ? penalty : 0 });
        };

        const reveal = () => {
            if (window.isPreview) return done(window.previewHint || '', false, 0); // sandbox: free, never saved
            const csrf = document.head.querySelector('meta[name="csrf-token"]')?.content ?? '';
            btn.disabled = true;
            fetch(window.hintUrl, { method: 'POST', headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' } })
                .then((r) => r.ok ? r.json() : r.json().catch(() => ({})).then((j) => { throw new Error(j.message || 'Hint unavailable.'); }))
                .then((d) => done(d.hint, !!d.charged, d.penalty || st.penalty))
                .catch((e) => { btn.disabled = false; PuzzleUtils.showToast(`⚠️ ${e.message}`, 'warning', 5000); });
        };

        btn.addEventListener('click', () => {
            sound('click');
            if (btn.dataset.free === 'true' || !confirmBox) return reveal();
            confirmBox.hidden = false;
            confirmBox.querySelector('[data-hint-yes]')?.focus();
        });
        confirmBox?.querySelector('[data-hint-yes]')?.addEventListener('click', reveal);
        confirmBox?.querySelector('[data-hint-no]')?.addEventListener('click', () => { hideConfirm(); btn.focus(); });
    }

    // ── Init ────────────────────────────────────────────────────────────
    function init() {
        const root = $('mission-console');
        if (!root || st.ready) return;
        st.ready = true;
        const d = root.dataset;
        st.max = Number(d.max) || 0;
        st.used = Number(d.used) || 0;
        st.tryWrong = Number(d.tryWrong) || 0;
        st.tryHint = d.tryHint === '1';
        st.points = Number(d.points) || 100;
        st.penalty = Number(d.penalty) || 10;
        st.penaltyPaid = d.penaltyPaid === '1';
        st.solved = d.solved === '1';
        st.freeAfter = Number(d.freeAfter) || 2;
        st.preview = window.isPreview === true;
        st.xp = stake();

        renderNotches();
        st.hasMission = d.mission === '1';
        st.hp = Number.isFinite(Number(d.health)) ? Number(d.health) : 100;
        setHp(st.hp);
        $('mc-bar-ghost').style.width = st.hp + '%';
        renderXp(false);

        const state = $('mc-hint-state');
        if (state && $('hint-button')) {
            if ($('hint-button').dataset.free === 'true') state.textContent = st.preview ? 'FREE (preview)' : 'FREE';
        } else if (state) {
            state.textContent = st.penaltyPaid ? `−${st.penalty} XP paid` : 'Revealed';
        }
        log(st.preview ? '> Preview sandbox online. Nothing is saved.' : '> Link online. Solve the task to bring it up.');
        initHint();
    }

    document.addEventListener('DOMContentLoaded', init);

    // A preview stores nothing, so the page sends its own health and miss count with each answer
    function previewHeaders() {
        return { 'X-Preview-Health': String(st.hp), 'X-Preview-Misses': String(st.tryWrong + 1) };
    }

    return { init, onResult, hideConfirm, previewHeaders, state: st };
})();
