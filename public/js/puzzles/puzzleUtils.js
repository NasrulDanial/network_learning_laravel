/**
 * puzzleUtils.js
 * Shared submit, feedback and notification helpers for the Phaser puzzle templates.
 */
window.PuzzleUtils = {

    /* ------------------------------------------------------------------ */
    /* 1. Submit an answer to the server                                   */
    /* ------------------------------------------------------------------ */
    _inFlight: false,

    submitResponse(payload, callbacks) {
        if (this._inFlight) {
            PuzzleUtils.showToast('⏳ Your answer is still being checked. Please wait...', 'info', 2000);
            return;
        }

        this._inFlight = true;
        PuzzleUtils.hideHintPanel();

        document.querySelectorAll('[data-puzzle-submit]').forEach(el => {
            el.style.pointerEvents = 'none';
            el.style.opacity = '0.5';
        });

        const csrf = document.head.querySelector('meta[name="csrf-token"]')?.content ?? (window.csrfToken || '');

        fetch(window.submitUrl || submitUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf,
                'Accept': 'application/json',
            },
            body: JSON.stringify(payload),
        })
        .then(response => {
            if (response.status === 401 || response.status === 419) {
                throw Object.assign(new Error('Your session has expired. Please refresh the page and log in again.'), { userFacing: true });
            }
            if (response.status === 403 || response.status === 422) {
                // The server explains exactly why (locked mission, attempt limit, incomplete answer...)
                return response.json()
                    .catch(() => ({}))
                    .then(j => { throw Object.assign(new Error(j.message || 'This activity cannot be submitted right now.'), { userFacing: true, status: response.status }); });
            }
            if (!response.ok) throw new Error('SERVER_ERROR');
            return response.json();
        })
        .then(async data => {
            const isCorrect = (data.status === 'completed' || data.is_correct === true);

            // Let the template "execute" the answer first; feedback appears once it finishes
            if (callbacks && typeof callbacks.onResult === 'function') {
                try {
                    await callbacks.onResult(data, isCorrect);
                } catch (e) {
                    console.warn('Puzzle result animation failed:', e);
                }
            }

            this._inFlight = false;
            PuzzleUtils.afterResult(data, isCorrect);
            PuzzleUtils.updateHealth(data);
            PuzzleUtils.notifyParent(data, isCorrect);
            PuzzleUtils.showFeedback(data, isCorrect);

            if (isCorrect) {
                if (typeof callbacks === 'function') {
                    callbacks(data, true);
                } else if (callbacks && typeof callbacks.onSuccess === 'function') {
                    callbacks.onSuccess(data);
                }
                // In a mission run the parent page celebrates instead
                if (!PuzzleUtils.inRun()) PuzzleUtils.triggerSuccessOverlay(data);
            } else {
                // Incorrect attempt: re-enable UI elements and invoke retry callback
                document.querySelectorAll('[data-puzzle-submit]').forEach(el => {
                    el.style.pointerEvents = '';
                    el.style.opacity = '';
                });

                if (typeof callbacks === 'function') {
                    callbacks(data, false);
                } else if (callbacks && typeof callbacks.onRetry === 'function') {
                    callbacks.onRetry(data);
                }
            }
        })
        .catch(err => {
            this._inFlight = false;

            document.querySelectorAll('[data-puzzle-submit]').forEach(el => {
                el.style.pointerEvents = '';
                el.style.opacity = '';
            });

            // Always hand control back to the template so its Submit button is usable again
            if (typeof callbacks === 'function') {
                callbacks(null, false);
            } else if (callbacks && typeof callbacks.onError === 'function') {
                callbacks.onError(err);
            }

            if (err.userFacing && err.status === 403) {
                PuzzleUtils.postToParent({ type: 'puzzle:locked', message: err.message });
            }

            if (err.userFacing) {
                PuzzleUtils.showToast(`⚠️ ${err.message}`, 'warning', 7000);
            } else {
                PuzzleUtils.showToast('❌ Your answer could not be sent. Check your connection and try again.', 'error', 6000);
            }
        });
    },

    /** Sound, shake and vibration once the server has judged the answer. */
    afterResult(data, isCorrect) {
        if (!window.NetFX) return;
        if (isCorrect) {
            NetFX.sound('success');
        } else {
            NetFX.sound('error');
            NetFX.shake(document.getElementById('phaser-game'));
            NetFX.vibrate(80);
        }
    },

    /* ------------------------------------------------------------------ */
    /* 1b. Mission Run integration (page inside the run iframe)           */
    /* ------------------------------------------------------------------ */
    inRun() {
        return window.inRun === true && window.self !== window.top;
    },

    postToParent(message) {
        if (!this.inRun()) return;
        window.parent.postMessage(Object.assign({
            source: 'netlearn',
            puzzleId: window.puzzleData ? window.puzzleData.puzzle_id : null,
        }, message), window.location.origin);
    },

    notifyParent(data, isCorrect) {
        this.postToParent({
            type: 'puzzle:result',
            correct: isCorrect,
            earnedXp: data.earned_xp || 0,
            missionCompleted: !!data.mission_completed,
            missionXpReward: data.mission_xp_reward || 0,
            attemptsCount: data.attempts_count || 0,
            maxAttempts: data.max_attempts || 0,
            hintsUsed: data.hints_used || 0,
            stars: data.stars || 0,
            rank: data.clearance_rank || null,
            newBadges: data.new_badges || [],
            unlockedNextMission: data.unlocked_next_mission || null,
        });
    },

    /** Templates call this at the end of create() so the run can hide its loader. */
    ready() {
        this.postToParent({ type: 'puzzle:ready' });
        document.dispatchEvent(new CustomEvent('puzzle:ready'));
    },

    /* ------------------------------------------------------------------ */
    /* 2. Terminal Diagnostic Feedback Display (Safe DOM Rendering)       */
    /* ------------------------------------------------------------------ */
    showFeedback(data, isCorrect) {
        const panel = document.getElementById('feedback-panel');
        const text  = document.getElementById('feedback-text');
        if (!panel || !text) return;

        panel.dataset.state = isCorrect ? 'correct' : 'incorrect';
        panel.style.display = 'block';

        // Clear existing children safely without innerHTML
        text.replaceChildren();

        const row = document.createElement('div');
        row.style.cssText = 'display: flex; align-items: flex-start; gap: 8px; flex-wrap: wrap; line-height: 1.5;';

        const tag = document.createElement('span');
        tag.style.fontFamily = 'monospace';
        tag.style.fontWeight = '700';

        const content = document.createElement('span');

        if (isCorrect) {
            tag.style.color = '#34d399';
            tag.textContent = '✓ Correct!';

            content.appendChild(document.createTextNode((data.feedback || 'Well done.') + ' '));

            const xp = parseInt(data.earned_xp, 10) || 0;
            const xpStrong = document.createElement('strong');
            xpStrong.style.color = '#fde047';
            if (window.isPreview === true) {
                xpStrong.textContent = 'Preview only: no XP or attempts recorded.';
            } else if (xp > 0) {
                xpStrong.textContent = `You earned +${xp} XP.`;
            } else {
                xpStrong.textContent = 'XP for this activity was already earned.';
            }
            content.appendChild(xpStrong);

            if (window.isPreview !== true && data.clearance_rank) {
                content.appendChild(document.createTextNode(' Rank: '));
                const rankStrong = document.createElement('strong');
                rankStrong.style.color = '#67e8f9';
                rankStrong.textContent = data.clearance_rank;
                content.appendChild(rankStrong);
            }
        } else {
            tag.style.color = '#fb7185';
            tag.textContent = '✗ Not quite';

            const feedbackText = data.feedback || 'Some answers are not right yet. Review them and try again.';
            content.appendChild(document.createTextNode(feedbackText));

            if (data.hint) {
                const hintDiv = document.createElement('div');
                hintDiv.style.cssText = 'color: #cbd5e1; font-size: 0.85em; font-style: italic; margin-top: 4px;';
                hintDiv.textContent = `Hint: ${data.hint}`;
                content.appendChild(hintDiv);
            }
        }

        row.appendChild(tag);
        row.appendChild(content);
        text.appendChild(row);

        panel.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    },

    /* ------------------------------------------------------------------ */
    /* 3. Terminal Toast Notification                                     */
    /* ------------------------------------------------------------------ */
    showToast(message, type = 'info', duration = 5000) {
        const themeStyles = {
            info: {
                bg: 'rgba(7, 20, 38, 0.95)',
                border: '1px solid rgba(34, 211, 238, 0.4)',
                color: '#67e8f9',
                shadow: '0 8px 30px rgba(0, 0, 0, 0.5), 0 0 20px rgba(34, 211, 238, 0.25)'
            },
            success: {
                bg: 'rgba(6, 40, 30, 0.95)',
                border: '1px solid rgba(52, 211, 153, 0.5)',
                color: '#6ee7b7',
                shadow: '0 8px 30px rgba(0, 0, 0, 0.5), 0 0 20px rgba(16, 185, 129, 0.25)'
            },
            warning: {
                bg: 'rgba(40, 30, 10, 0.95)',
                border: '1px solid rgba(245, 158, 11, 0.5)',
                color: '#fde047',
                shadow: '0 8px 30px rgba(0, 0, 0, 0.5), 0 0 20px rgba(245, 158, 11, 0.25)'
            },
            error: {
                bg: 'rgba(50, 10, 20, 0.95)',
                border: '1px solid rgba(244, 63, 94, 0.5)',
                color: '#fda4af',
                shadow: '0 8px 30px rgba(0, 0, 0, 0.5), 0 0 20px rgba(244, 63, 94, 0.25)'
            }
        };

        const theme = themeStyles[type] || themeStyles.info;
        const toast = document.createElement('div');
        toast.setAttribute('role', 'alert');
        toast.style.cssText = `
            position: fixed;
            bottom: 24px;
            right: 24px;
            z-index: 9999;
            max-width: 420px;
            padding: 12px 18px;
            border-radius: 12px;
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            font-size: 0.84rem;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            line-height: 1.5;
            background: ${theme.bg};
            border: ${theme.border};
            color: ${theme.color};
            box-shadow: ${theme.shadow};
            transform: translateY(16px);
            opacity: 0;
            transition: all 0.35s cubic-bezier(0.25, 1, 0.5, 1);
        `;
        toast.textContent = message;

        document.body.appendChild(toast);

        requestAnimationFrame(() => {
            requestAnimationFrame(() => {
                toast.style.transform = 'translateY(0)';
                toast.style.opacity = '1';
            });
        });

        setTimeout(() => {
            toast.style.transform = 'translateY(16px)';
            toast.style.opacity = '0';
            toast.addEventListener('transitionend', () => toast.remove(), { once: true });
        }, duration);
    },

    /* ------------------------------------------------------------------ */
    /* 4. Success banner                                                   */
    /* ------------------------------------------------------------------ */
    triggerSuccessOverlay(data) {
        // Escape server values before they go into markup
        const esc = (v) => String(v).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
        const isPreview = window.isPreview === true;
        const puzzleXp = parseInt(data.earned_xp, 10) || 0;
        const missionXp = data.mission_completed ? (parseInt(data.mission_xp_reward, 10) || 0) : 0;
        const totalXp = puzzleXp + missionXp;

        const heading = isPreview
            ? 'Preview: correct answer'
            : (data.mission_completed && missionXp > 0 ? 'Mission complete!' : 'Activity solved');
        const xpLine = isPreview
            ? 'Preview only: no XP or attempts recorded'
            : (totalXp > 0 ? `+${esc(totalXp)} XP earned` : 'Solved again: XP for this activity was already earned');
        const rank = !isPreview && data.clearance_rank ? data.clearance_rank : null;
        const nextMission = !isPreview && data.unlocked_next_mission ? data.unlocked_next_mission : null;

        const banner = document.createElement('div');
        banner.setAttribute('role', 'status');
        banner.setAttribute('aria-live', 'polite');
        banner.style.cssText = 'position: fixed; inset: 0 0 auto 0; top: 0; z-index: 9998; display: flex; align-items: center; justify-content: center; pointer-events: none;';
        banner.innerHTML = `
            <div id="xp-banner"
                 style="margin-top: 24px; padding: 18px 32px; border-radius: 16px;
                        background: rgba(7, 18, 34, 0.95); backdrop-filter: blur(20px);
                        -webkit-backdrop-filter: blur(20px);
                        border: 1px solid rgba(34, 211, 238, 0.5);
                        box-shadow: 0 16px 40px rgba(0, 0, 0, 0.6), 0 0 35px rgba(34, 211, 238, 0.35);
                        text-align: center; font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
                        opacity: 0; transform: scale(0.9);
                        transition: all 0.4s cubic-bezier(0.25, 1, 0.5, 1);">
                <div style="font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.12em; color: #22d3ee; margin-bottom: 4px; display: flex; align-items: center; justify-content: center; gap: 8px; font-weight: 700;">
                    <span style="width: 8px; height: 8px; border-radius: 50%; background: #22c55e; box-shadow: 0 0 8px #22c55e;"></span>
                    ${esc(heading)}
                </div>
                <div style="font-size: ${totalXp > 0 && !isPreview ? '1.6rem' : '1.05rem'}; font-weight: 800; color: #fbbf24; text-shadow: 0 0 16px rgba(251, 191, 36, 0.5);">
                    ${xpLine}
                </div>
                ${rank ? `<div style="font-size: 0.82rem; color: rgba(255, 255, 255, 0.8); margin-top: 4px;">Rank: <strong style="color: #22d3ee;">${esc(rank)}</strong></div>` : ''}
                ${nextMission ? `<div style="font-size: 0.82rem; color: rgba(255, 255, 255, 0.8); margin-top: 4px;">Unlocked: <strong style="color: #4ade80;">${esc(nextMission)}</strong></div>` : ''}
            </div>`;
        document.body.appendChild(banner);

        requestAnimationFrame(() => {
            requestAnimationFrame(() => {
                const el = document.getElementById('xp-banner');
                if (el) {
                    el.style.opacity = '1';
                    el.style.transform = 'scale(1)';
                }
            });
        });

        setTimeout(() => {
            const el = document.getElementById('xp-banner');
            if (el) {
                el.style.opacity = '0';
                el.style.transform = 'scale(0.9)';
                el.addEventListener('transitionend', () => banner.remove(), { once: true });
            }
        }, 3200);

        PuzzleUtils._spawnConfetti(60);
    },

    _spawnConfetti(count) {
        const colours = ['#22d3ee', '#38bdf8', '#818cf8', '#34d399', '#facc15'];
        const container = document.createElement('div');
        container.style.cssText = 'position:fixed;inset:0;pointer-events:none;overflow:hidden;z-index:9997;';
        document.body.appendChild(container);

        for (let i = 0; i < count; i++) {
            const el = document.createElement('div');
            const size   = 6 + Math.random() * 8;
            const colour = colours[Math.floor(Math.random() * colours.length)];
            const startX = Math.random() * 100;
            const delay  = Math.random() * 500;
            const dur    = 1200 + Math.random() * 1000;
            const spin   = Math.random() > 0.5 ? 360 : -360;

            el.style.cssText = `
                position:absolute;
                width:${size}px; height:${size * 0.4}px;
                background:${colour};
                border-radius:2px;
                top:-20px; left:${startX}%;
                animation: confetti-fall ${dur}ms ${delay}ms ease-in forwards;
                --spin:${spin}deg;
            `;
            container.appendChild(el);
        }

        if (!document.getElementById('confetti-style')) {
            const style = document.createElement('style');
            style.id = 'confetti-style';
            style.textContent = `
                @keyframes confetti-fall {
                    0%   { transform: translateY(0) rotate(0deg); opacity: 1; }
                    80%  { opacity: 1; }
                    100% { transform: translateY(105vh) rotate(var(--spin,360deg)); opacity: 0; }
                }
            `;
            document.head.appendChild(style);
        }

        setTimeout(() => container.remove(), 3000);
    },

    /* ------------------------------------------------------------------ */
    /* 5. Link health (remaining attempts) and paid hint                  */
    /* ------------------------------------------------------------------ */
    renderHealth(maxAttempts, used) {
        const box = document.getElementById('link-health');
        if (!box) return;
        box.replaceChildren();
        const label = document.createElement('span');
        label.className = 'health-label';
        label.textContent = 'Link health';
        box.appendChild(label);

        if (!maxAttempts) {
            const inf = document.createElement('span');
            inf.className = 'health-infinite';
            inf.textContent = '∞ link redundancy';
            box.appendChild(inf);
            box.setAttribute('aria-label', 'Unlimited attempts');
            return;
        }

        const left = Math.max(0, maxAttempts - used);
        for (let i = 0; i < maxAttempts; i++) {
            const seg = document.createElement('span');
            seg.className = 'health-seg' + (i < left ? '' : ' is-lost');
            box.appendChild(seg);
        }
        const count = document.createElement('span');
        count.className = 'health-count';
        count.textContent = `${left}/${maxAttempts}`;
        box.appendChild(count);
        box.setAttribute('aria-label', `${left} of ${maxAttempts} attempts left`);
    },

    updateHealth(data) {
        if (typeof data.max_attempts === 'undefined') return;
        const before = document.querySelectorAll('#link-health .health-seg:not(.is-lost)').length;
        this.renderHealth(data.max_attempts, data.attempts_count || 0);
        const segs = document.querySelectorAll('#link-health .health-seg');
        const after = document.querySelectorAll('#link-health .health-seg:not(.is-lost)').length;
        if (after < before && segs[after]) {
            segs[after].classList.add('is-breaking');   // short-circuit animation on the segment just lost
        }
    },

    hideHintPanel() {
        const confirmBox = document.getElementById('hint-confirm');
        if (confirmBox) confirmBox.hidden = true;
    },

    initHint() {
        const btn = document.getElementById('hint-button');
        const confirmBox = document.getElementById('hint-confirm');
        const out = document.getElementById('hint-text');
        if (!btn || !out) return;

        const reveal = () => {
            if (window.isPreview) {
                // Lecturer preview: hint is free and never touches student progress
                if (confirmBox) confirmBox.hidden = true;
                out.hidden = false;
                btn.hidden = true;
                window.NetFX ? NetFX.typeText(out, `Hint: ${window.previewHint || ''}`) : (out.textContent = `Hint: ${window.previewHint || ''}`);
                return;
            }
            const csrf = document.head.querySelector('meta[name="csrf-token"]')?.content ?? '';
            btn.disabled = true;
            fetch(window.hintUrl, { method: 'POST', headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' } })
                .then(r => r.ok ? r.json() : r.json().catch(() => ({})).then(j => { throw new Error(j.message || 'Hint unavailable.'); }))
                .then(d => {
                    if (confirmBox) confirmBox.hidden = true;
                    out.hidden = false;
                    btn.hidden = true;
                    const note = d.charged ? ` (−${d.penalty} XP applied to this activity)` : '';
                    window.NetFX ? NetFX.typeText(out, `Hint: ${d.hint}${note}`) : (out.textContent = `Hint: ${d.hint}${note}`);
                })
                .catch(e => { btn.disabled = false; PuzzleUtils.showToast(`⚠️ ${e.message}`, 'warning', 5000); });
        };

        btn.addEventListener('click', () => {
            if (window.NetFX) NetFX.sound('click');
            if (btn.dataset.free === 'true' || !confirmBox) return reveal();
            confirmBox.hidden = false;
            confirmBox.querySelector('[data-hint-yes]')?.focus();
        });
        confirmBox?.querySelector('[data-hint-yes]')?.addEventListener('click', reveal);
        confirmBox?.querySelector('[data-hint-no]')?.addEventListener('click', () => { confirmBox.hidden = true; btn.focus(); });
    },

    /* ------------------------------------------------------------------ */
    /* 6. Phaser FX (Phaser 3.55 particle API — keep the CDN pinned)      */
    /* ------------------------------------------------------------------ */
    fx: {
        speed: 1,

        /** Click/tap anywhere on the canvas while an answer is executing = 4x speed. */
        enableSpeedUp(scene) {
            this.speed = 1;
            const fast = () => { this.speed = 4; scene.tweens.timeScale = 4; scene.time.timeScale = 4; };
            scene.input.once('pointerdown', fast);
            return () => { scene.input.off('pointerdown', fast); this.speed = 1; scene.tweens.timeScale = 1; scene.time.timeScale = 1; };
        },

        reduced() { return !!(window.NetFX && NetFX.reduced); },

        ensureTextures(scene) {
            if (scene.textures.exists('nl-spark')) return;
            const g = scene.add.graphics();
            g.fillStyle(0xffffff, 1).fillCircle(4, 4, 4);
            g.generateTexture('nl-spark', 8, 8);
            g.destroy();
        },

        burst(scene, x, y, tint = 0x22d3ee, qty = 24) {
            if (this.reduced()) return;
            this.ensureTextures(scene);
            const p = scene.add.particles('nl-spark').setDepth(60);
            const e = p.createEmitter({
                speed: { min: 80, max: 260 }, scale: { start: 0.8, end: 0 },
                lifespan: 600, tint, blendMode: 'ADD', on: false,
            });
            e.explode(qty, x, y);
            scene.time.delayedCall(800, () => p.destroy());
        },

        shake(scene) { if (!this.reduced()) scene.cameras.main.shake(220, 0.008); },

        flashCam(scene, r = 239, g = 68, b = 68) { if (!this.reduced()) scene.cameras.main.flash(250, r, g, b); },

        packet(scene, x, y, color = 0x22d3ee) {
            const glow = scene.add.circle(x, y, 14, color, 0.25).setDepth(49);
            const c = scene.add.circle(x, y, 7, color).setDepth(50);
            return { c, glow, destroy() { c.destroy(); glow.destroy(); } };
        },

        /** Move a packet through points; stops after index stopAt (null = whole path). Resolves the last index reached. */
        runPath(scene, pkt, points, { stepMs = 380, stopAt = null } = {}) {
            return new Promise((resolve) => {
                let i = 0;
                const next = () => {
                    if (i >= points.length || (stopAt !== null && i > stopAt)) return resolve(i - 1);
                    const pt = points[i++];
                    if (window.NetFX) NetFX.sound('packet');
                    scene.tweens.add({
                        targets: [pkt.c, pkt.glow], x: pt.x, y: pt.y,
                        duration: this.reduced() ? 1 : stepMs, ease: 'Sine.easeInOut', onComplete: next,
                    });
                };
                next();
            });
        },

        wait(scene, ms) {
            return new Promise((r) => scene.time.delayedCall(this.reduced() ? 1 : ms, r));
        },
    },

    /**
     * Typed monospace log drawn on the canvas. .log(text, color) resolves when typed.
     * Keeps the newest 5 lines.
     */
    terminal(scene, x, y, w, h) {
        const bg = scene.add.rectangle(x, y, w, h, 0x020617, 0.92).setOrigin(0).setStrokeStyle(1, 0x1e3a5f).setDepth(40);
        const lines = [];
        const maxLines = Math.max(1, Math.floor((h - 12) / 18));
        const reduced = () => !!(window.NetFX && NetFX.reduced);

        return {
            log(text, color = '#a7f3d0') {
                return new Promise((resolve) => {
                    if (lines.length >= maxLines) {
                        lines.shift().destroy();
                        lines.forEach((l) => { l.y -= 18; });
                    }
                    const t = scene.add.text(x + 10, y + 8 + lines.length * 18, '', {
                        fontFamily: 'Consolas, "Courier New", monospace', fontSize: '12px', color,
                    }).setDepth(41);
                    lines.push(t);
                    if (reduced()) { t.setText(text); return resolve(); }
                    let i = 0;
                    const ev = scene.time.addEvent({
                        delay: 18, repeat: text.length - 1,
                        callback: () => {
                            t.setText(text.slice(0, ++i));
                            if (i >= text.length) { ev.remove(); resolve(); }
                        },
                    });
                    if (!text.length) resolve();
                });
            },
            clear() { lines.splice(0).forEach((l) => l.destroy()); },
            destroy() { this.clear(); bg.destroy(); },
        };
    },
};

document.addEventListener('DOMContentLoaded', () => {
    PuzzleUtils.renderHealth(window.puzzleData?.max_attempts || 0, window.puzzleData?.attempts_used || 0);
    PuzzleUtils.initHint();
});
