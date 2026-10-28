/**
 * NetFX — shared game-feel helpers: synthesised sound (Web Audio, no files),
 * screen shake/flash, vibration, typed text and count-up.
 * Every effect respects the mute setting and prefers-reduced-motion.
 */
(function () {
    'use strict';

    const motionQuery = window.matchMedia ? window.matchMedia('(prefers-reduced-motion: reduce)') : null;

    const NetFX = {
        muted: false,
        reduced: motionQuery ? motionQuery.matches : false,
        _ctx: null,

        init() {
            let stored = null;
            try { stored = localStorage.getItem('netlearn-mute'); } catch (e) { /* storage blocked */ }
            this.muted = stored === null ? window.NETLEARN_SOUND_DEFAULT === false : stored === '1';

            if (motionQuery && motionQuery.addEventListener) {
                motionQuery.addEventListener('change', (e) => { this.reduced = e.matches; });
            }

            // Browsers only allow audio after a user gesture: unlock on the first one
            const unlock = () => {
                try { this._audio().resume(); } catch (e) { /* no audio support */ }
                window.removeEventListener('pointerdown', unlock, true);
                window.removeEventListener('keydown', unlock, true);
            };
            window.addEventListener('pointerdown', unlock, true);
            window.addEventListener('keydown', unlock, true);
        },

        setMuted(value) {
            this.muted = !!value;
            try { localStorage.setItem('netlearn-mute', this.muted ? '1' : '0'); } catch (e) { /* storage blocked */ }
            document.dispatchEvent(new CustomEvent('netfx:mute', { detail: { muted: this.muted } }));
        },

        // ── Sound ───────────────────────────────────────────────────────
        _audio() {
            if (!this._ctx) {
                const Ctx = window.AudioContext || window.webkitAudioContext;
                if (!Ctx) return null;
                this._ctx = new Ctx();
            }
            return this._ctx;
        },

        tone(freq, dur = 0.08, type = 'square', vol = 0.05, delay = 0) {
            if (this.muted) return;
            const ctx = this._audio();
            if (!ctx || ctx.state === 'suspended') return;
            const t = ctx.currentTime + delay;
            const o = ctx.createOscillator();
            const g = ctx.createGain();
            o.type = type;
            o.frequency.setValueAtTime(freq, t);
            g.gain.setValueAtTime(Math.min(vol, 0.06), t);
            g.gain.exponentialRampToValueAtTime(0.0001, t + dur);
            o.connect(g).connect(ctx.destination);
            o.start(t);
            o.stop(t + dur + 0.02);
        },

        sound(name, step = 0) {
            const s = {
                click: () => this.tone(900, 0.03, 'square', 0.03),
                type: () => this.tone(1400 + Math.random() * 200, 0.015, 'square', 0.015),
                success: () => {
                    const up = step * 100;
                    this.tone(660 + up, 0.1, 'triangle', 0.06);
                    this.tone(880 + up, 0.1, 'triangle', 0.06, 0.1);
                    this.tone(1320 + up, 0.18, 'triangle', 0.06, 0.2);
                },
                error: () => { this.tone(180, 0.18, 'sawtooth', 0.05); this.tone(140, 0.22, 'sawtooth', 0.05, 0.12); },
                boot: () => [220, 330, 440, 660].forEach((f, i) => this.tone(f, 0.12, 'sine', 0.05, i * 0.09)),
                linkup: () => { this.tone(520, 0.07, 'sine', 0.05); this.tone(1040, 0.12, 'sine', 0.05, 0.07); },
                packet: () => this.tone(1200, 0.04, 'sine', 0.03),
                drop: () => this.tone(90, 0.3, 'sawtooth', 0.06),
                tick: () => this.tone(700, 0.05, 'sine', 0.04),
            };
            (s[name] || (() => {}))();
        },

        // ── Screen effects ──────────────────────────────────────────────
        shake(el = document.body, ms = 300) {
            if (this.reduced || !el || !el.animate) return;
            el.animate([
                { transform: 'translateX(0)' }, { transform: 'translateX(-8px)' }, { transform: 'translateX(8px)' },
                { transform: 'translateX(-5px)' }, { transform: 'translateX(0)' },
            ], { duration: ms });
        },

        flash(color = 'rgba(239,68,68,.25)', ms = 350) {
            if (this.reduced) return;
            const d = document.createElement('div');
            d.setAttribute('aria-hidden', 'true');
            d.style.cssText = `position:fixed;inset:0;background:${color};pointer-events:none;z-index:9999`;
            document.body.appendChild(d);
            const anim = d.animate([{ opacity: 1 }, { opacity: 0 }], { duration: ms });
            anim.onfinish = () => d.remove();
        },

        vibrate(ms = 80) {
            if (navigator.vibrate && window.matchMedia && window.matchMedia('(pointer: coarse)').matches) {
                navigator.vibrate(ms);
            }
        },

        // ── Text ────────────────────────────────────────────────────────
        /** Types text into el; resolves when done. Clicking the element finishes instantly. */
        typeText(el, text, cps = 45) {
            return new Promise((resolve) => {
                if (!el) return resolve();
                if (this.reduced) { el.textContent = text; return resolve(); }
                let i = 0;
                let done = false;
                const finish = () => {
                    if (done) return;
                    done = true;
                    el.textContent = text;
                    el.removeEventListener('click', finish);
                    resolve();
                };
                el.addEventListener('click', finish);
                const step = () => {
                    if (done) return;
                    el.textContent = text.slice(0, ++i);
                    if (i % 2 === 0) this.sound('type');
                    i < text.length ? setTimeout(step, 1000 / cps) : finish();
                };
                step();
            });
        },

        countUp(el, from, to, ms = 900) {
            return new Promise((resolve) => {
                if (!el) return resolve();
                if (this.reduced || from === to) { el.textContent = to; return resolve(); }
                const start = performance.now();
                const tick = (now) => {
                    const p = Math.min(1, (now - start) / ms);
                    el.textContent = Math.round(from + (to - from) * (1 - Math.pow(1 - p, 3)));
                    p < 1 ? requestAnimationFrame(tick) : resolve();
                };
                requestAnimationFrame(tick);
            });
        },

        wait(ms) {
            return new Promise((r) => setTimeout(r, this.reduced ? Math.min(ms, 50) : ms));
        },
    };

    NetFX.init();
    window.NetFX = NetFX;
})();
