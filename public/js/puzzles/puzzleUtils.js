/**
 * puzzleUtils.js
 * Network Simulation Telemetry & Packet Transmission Utilities.
 */
window.PuzzleUtils = {

    /* ------------------------------------------------------------------ */
    /* 1. Protocol Data Unit & Telemetry Transmission                      */
    /* ------------------------------------------------------------------ */
    submitToBackend(isCorrect, onSuccess) {
        const score = isCorrect ? (puzzleData.points ?? 0) : 0;

        // Visual feedback during transmission
        PuzzleUtils.showToast(
            '📡 Serializing Protocol Data Units & Transmitting Telemetry...',
            'info',
            2000
        );

        // Lock button during transmission
        document.querySelectorAll('[data-puzzle-submit]').forEach(el => {
            el.style.pointerEvents = 'none';
            el.style.opacity = '0.5';
        });

        fetch(submitUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.head.querySelector('meta[name="csrf-token"]')?.content ?? csrfToken,
            },
            body: JSON.stringify({ score, is_correct: isCorrect }),
        })
        .then(response => {
            if (response.status === 401) throw new Error('AUTH_SESSION_EXPIRED');
            if (response.status === 403) throw new Error('INSUFFICIENT_CLEARANCE');
            if (!response.ok)            throw new Error('PACKET_TRANSMISSION_FAILED');
            return response.json();
        })
        .then(data => {
            if (typeof onSuccess === 'function') onSuccess(data, isCorrect);
            PuzzleUtils.showFeedback(data, isCorrect);
            if (isCorrect) {
                PuzzleUtils.triggerSuccessOverlay(data.earned_xp ?? score, data.clearance_status ?? data.new_rank);
            }
        })
        .catch(err => {
            document.querySelectorAll('[data-puzzle-submit]').forEach(el => {
                el.style.pointerEvents = '';
                el.style.opacity = '';
            });

            if (err.message === 'AUTH_SESSION_EXPIRED') {
                PuzzleUtils.showToast(
                    '⚠️ Terminal Disconnected: Cryptographic token expired. Re-authenticate with central directory.',
                    'warning',
                    8000
                );
            } else if (err.message === 'INSUFFICIENT_CLEARANCE') {
                PuzzleUtils.showToast(
                    '🔒 Security Violation: Access Denied. Insufficient Clearance Level for Student simulation node.',
                    'error'
                );
            } else {
                PuzzleUtils.showToast(
                    '❌ Network Link Failure: Packet loss detected. Transmission halted by host gateway.',
                    'error'
                );
            }
        });
    },

    /* ------------------------------------------------------------------ */
    /* 2. Terminal Diagnostic Feedback Display                             */
    /* ------------------------------------------------------------------ */
    showFeedback(data, isCorrect) {
        const panel = document.getElementById('feedback-panel');
        const text  = document.getElementById('feedback-text');
        if (!panel || !text) return;

        panel.dataset.state = isCorrect ? 'correct' : 'incorrect';
        panel.classList.remove('hidden');

        if (isCorrect) {
            text.innerHTML = `
                <div class="flex items-start gap-2">
                    <span class="text-emerald-400 font-mono font-bold">[ROUTE_STATUS: OPTIMAL]</span>
                    <span>${data.feedback ?? 'All frame destinations verified.'} Awarded <strong>+${data.earned_xp ?? 0} Network XP</strong>. Status: <strong class="text-cyan-300">${data.clearance_status ?? data.new_rank ?? 'Active Operative'}</strong>.</span>
                </div>
            `;
        } else {
            text.innerHTML = `
                <div class="flex items-start gap-2">
                    <span class="text-rose-400 font-mono font-bold">[ROUTING_MISMATCH: PACKET_DROPPED]</span>
                    <span>${data.hint ? 'Diagnostic Trace: ' + data.hint : 'Topology configuration incorrect. Inspect MAC/IP routing boundaries.'}</span>
                </div>
            `;
        }

        panel.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    },

    /* ------------------------------------------------------------------ */
    /* 3. Terminal Toast Notification                                     */
    /* ------------------------------------------------------------------ */
    showToast(message, type = 'info', duration = 5000) {
        const colours = {
            info:    'bg-slate-900 border border-cyan-500/50 text-cyan-200 shadow-[0_0_20px_rgba(6,182,212,0.3)]',
            success: 'bg-slate-900 border border-emerald-500/50 text-emerald-200 shadow-[0_0_20px_rgba(16,185,129,0.3)]',
            warning: 'bg-slate-900 border border-amber-500/50 text-amber-200 shadow-[0_0_20px_rgba(245,158,11,0.3)]',
            error:   'bg-slate-900 border border-rose-500/50 text-rose-200 shadow-[0_0_20px_rgba(244,63,94,0.3)]',
        };

        const toast = document.createElement('div');
        toast.setAttribute('role', 'alert');
        toast.className = [
            'fixed bottom-6 right-6 z-[9999] flex items-start gap-3',
            'max-w-md px-5 py-3 rounded-xl backdrop-blur-md',
            'text-xs font-mono leading-relaxed',
            colours[type] ?? colours.info,
            'translate-y-4 opacity-0',
            'transition-all duration-300 ease-out',
        ].join(' ');
        toast.textContent = message;

        document.body.appendChild(toast);

        requestAnimationFrame(() => {
            requestAnimationFrame(() => {
                toast.classList.remove('translate-y-4', 'opacity-0');
            });
        });

        setTimeout(() => {
            toast.classList.add('translate-y-4', 'opacity-0');
            toast.addEventListener('transitionend', () => toast.remove(), { once: true });
        }, duration);
    },

    /* ------------------------------------------------------------------ */
    /* 4. Telemetry Verified HUD Overlay & Confetti                       */
    /* ------------------------------------------------------------------ */
    triggerSuccessOverlay(xpAmount, clearanceRank) {
        const banner = document.createElement('div');
        banner.className = 'fixed inset-x-0 top-0 z-[9998] flex items-center justify-center pointer-events-none';
        banner.innerHTML = `
            <div id="xp-banner"
                 class="mt-6 px-8 py-4 rounded-xl shadow-[0_0_35px_rgba(34,211,238,0.5)]
                        border border-cyan-400/50 bg-slate-950/95 backdrop-blur-md
                        text-cyan-300 font-mono text-center
                        opacity-0 scale-90 transition-all duration-500">
                <div class="text-[11px] uppercase tracking-widest text-cyan-400 mb-1 flex items-center justify-center gap-1.5">
                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-ping"></span>
                    TELEMETRY VERIFIED // MISSION CLEARED
                </div>
                <div class="text-2xl font-black text-amber-400 drop-shadow-[0_0_12px_rgba(251,191,36,0.7)]">
                    +${xpAmount} Network XP Credited
                </div>
                ${clearanceRank ? `<div class="text-xs text-slate-300 mt-1 font-sans">Clearance Level: <strong class="text-cyan-400 font-semibold">${clearanceRank}</strong></div>` : ''}
            </div>`;
        document.body.appendChild(banner);

        requestAnimationFrame(() => {
            requestAnimationFrame(() => {
                const el = document.getElementById('xp-banner');
                if (el) el.classList.remove('opacity-0', 'scale-90');
            });
        });

        setTimeout(() => {
            const el = document.getElementById('xp-banner');
            if (el) {
                el.classList.add('opacity-0', 'scale-90');
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
