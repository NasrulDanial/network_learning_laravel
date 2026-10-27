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

        PuzzleUtils.showToast('Checking your answer...', 'info', 2000);

        this._inFlight = true;

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
                    .then(j => { throw Object.assign(new Error(j.message || 'This activity cannot be submitted right now.'), { userFacing: true }); });
            }
            if (!response.ok) throw new Error('SERVER_ERROR');
            return response.json();
        })
        .then(data => {
            this._inFlight = false;
            const isCorrect = (data.status === 'completed' || data.is_correct === true);

            // Trigger safe DOM feedback
            PuzzleUtils.showFeedback(data, isCorrect);

            if (isCorrect) {
                if (typeof callbacks === 'function') {
                    callbacks(data, true);
                } else if (callbacks && typeof callbacks.onSuccess === 'function') {
                    callbacks.onSuccess(data);
                }
                PuzzleUtils.triggerSuccessOverlay(data);
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

            if (err.userFacing) {
                PuzzleUtils.showToast(`⚠️ ${err.message}`, 'warning', 7000);
            } else {
                PuzzleUtils.showToast('❌ Your answer could not be sent. Check your connection and try again.', 'error', 6000);
            }
        });
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
};
