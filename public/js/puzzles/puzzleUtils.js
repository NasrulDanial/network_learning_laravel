/**
 * puzzleUtils.js
 * Network Simulation Telemetry & Packet Transmission Utilities.
 */
window.PuzzleUtils = {

    /* ------------------------------------------------------------------ */
    /* 1. Protocol Data Unit & Telemetry Transmission                      */
    /* ------------------------------------------------------------------ */
    submitResponse(payload, onSuccess) {
        if (window.isPreview) {
            const previewData = {
                status: 'completed',
                is_correct: true,
                score: window.puzzleData?.points || 100,
                earned_xp: 0,
                feedback: 'Lecturer Preview Mode: Payload formatted and validated successfully. In live student gameplay, this is evaluated server-side by PuzzleEvaluatorService.',
                clearance_status: 'Lecturer Sandbox (Testing Mode)'
            };
            if (typeof onSuccess === 'function') onSuccess(previewData, true);
            PuzzleUtils.showFeedback(previewData, true);
            PuzzleUtils.showToast('🧪 [PREVIEW MODE] Response registered. Zero attempts and Zero XP logged.', 'success', 4000);
            return;
        }

        PuzzleUtils.showToast(
            '📡 Validating simulation telemetry with central network...',
            'info',
            2000
        );

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
            if (response.status === 401) throw new Error('AUTH_SESSION_EXPIRED');
            if (response.status === 403) throw new Error('INSUFFICIENT_CLEARANCE');
            if (!response.ok) throw new Error('PACKET_TRANSMISSION_FAILED');
            return response.json();
        })
        .then(data => {
            const isCorrect = (data.status === 'completed' || data.is_correct === true);
            if (typeof onSuccess === 'function') onSuccess(data, isCorrect);
            PuzzleUtils.showFeedback(data, isCorrect);
            if (isCorrect) {
                PuzzleUtils.triggerSuccessOverlay(data.earned_xp ?? data.score, data.clearance_status ?? data.new_rank);
            }
        })
        .catch(err => {
            document.querySelectorAll('[data-puzzle-submit]').forEach(el => {
                el.style.pointerEvents = '';
                el.style.opacity = '';
            });

            if (err.message === 'AUTH_SESSION_EXPIRED') {
                PuzzleUtils.showToast('⚠️ Terminal Disconnected: Token expired. Please re-login.', 'warning', 8000);
            } else if (err.message === 'INSUFFICIENT_CLEARANCE') {
                PuzzleUtils.showToast('🔒 Access Denied: Insufficient clearance or unpublished activity.', 'error');
            } else {
                PuzzleUtils.showToast('❌ Packet transmission failed. Please try again.', 'error');
            }
        });
    },

    submitToBackend(isCorrect, onSuccess) {
        // Backwards compatibility helper
        const score = isCorrect ? (window.puzzleData?.points ?? 0) : 0;
        return this.submitResponse({ score: score, is_correct: isCorrect }, onSuccess);
    },

    /* ------------------------------------------------------------------ */
    /* 2. Terminal Diagnostic Feedback Display                             */
    /* ------------------------------------------------------------------ */
    showFeedback(data, isCorrect) {
        const panel = document.getElementById('feedback-panel');
        const text  = document.getElementById('feedback-text');
        if (!panel || !text) return;

        panel.dataset.state = isCorrect ? 'correct' : 'incorrect';
        panel.style.display = 'block';

        if (isCorrect) {
            text.innerHTML = `
                <div style="display: flex; align-items: flex-start; gap: 8px;">
                    <span style="color: #34d399; font-family: monospace; font-weight: 700;">[ROUTE_STATUS: OPTIMAL]</span>
                    <span>${data.feedback ?? 'All frame destinations verified.'} Awarded <strong style="color: #fde047;">+${data.earned_xp ?? 0} Network XP</strong>. Status: <strong style="color: #67e8f9;">${data.clearance_status ?? data.new_rank ?? 'Active Operative'}</strong>.</span>
                </div>
            `;
        } else {
            text.innerHTML = `
                <div style="display: flex; align-items: flex-start; gap: 8px;">
                    <span style="color: #fb7185; font-family: monospace; font-weight: 700;">[ROUTING_MISMATCH: PACKET_DROPPED]</span>
                    <span>${data.hint ? 'Diagnostic Trace: ' + data.hint : (data.feedback ?? 'Topology configuration incorrect. Inspect MAC/IP routing boundaries.')}</span>
                </div>
            `;
        }

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
    /* 4. Telemetry Verified HUD Overlay & Confetti                       */
    /* ------------------------------------------------------------------ */
    triggerSuccessOverlay(xpAmount, clearanceRank) {
        const banner = document.createElement('div');
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
                    TELEMETRY VERIFIED // MISSION CLEARED
                </div>
                <div style="font-size: 1.6rem; font-weight: 800; color: #fbbf24; text-shadow: 0 0 16px rgba(251, 191, 36, 0.5);">
                    +${xpAmount} Network XP Credited
                </div>
                ${clearanceRank ? `<div style="font-size: 0.82rem; color: rgba(255, 255, 255, 0.75); margin-top: 4px;">Clearance Level: <strong style="color: #22d3ee;">${clearanceRank}</strong></div>` : ''}
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
