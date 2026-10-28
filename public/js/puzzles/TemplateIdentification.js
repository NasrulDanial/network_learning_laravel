/**
 * TemplateIdentification.js
 * Phaser 3 Puzzle Archetype 1: Identification & Hardware Recognition.
 *
 * Submits { selected_option }. A scanner beam then sweeps the chosen card: a match shows a
 * device signature card; a wrong pick is stamped SIGNATURE MISMATCH until another is chosen.
 */
class TemplateIdentification extends Phaser.Scene {
    constructor() {
        super({ key: 'TemplateIdentification' });
        this.selectedOptionId = null;
        this.optionButtons = [];
    }

    create() {
        const { width, height } = this.scale;
        document.getElementById('game-loader')?.remove();

        const data = window.activityData || {};
        const options = data.options || [
            { id: 'opt_1', label: 'Router' },
            { id: 'opt_2', label: 'Switch' },
            { id: 'opt_3', label: 'Hub' },
            { id: 'opt_4', label: 'Firewall' }
        ];

        // ── Title / Prompt Card ──────────────────────────────────────────
        const promptText = data.prompt || window.puzzleData?.question_text || 'Identify the correct networking component:';
        this.add.text(width / 2, 45, promptText, {
            font: 'bold 18px "Inter", "Segoe UI", sans-serif',
            fill: '#ffffff',
            align: 'center',
            wordWrap: { width: width * 0.88 }
        }).setOrigin(0.5);

        // ── Instruction Subtitle ─────────────────────────────────────────
        const sub = window.puzzleData?.instruction || 'Select the hardware component or concept that matches the specification.';
        this.add.text(width / 2, 85, sub, {
            font: '13px "Inter", "Segoe UI", sans-serif',
            fill: '#94a3b8',
            align: 'center',
            wordWrap: { width: width * 0.88 }
        }).setOrigin(0.5);

        // ── Options List Container ───────────────────────────────────────
        const startY = 135;
        const spacing = 58;

        options.forEach((opt, idx) => {
            const y = startY + (idx * spacing);
            const container = this.add.container(width / 2, y);

            // Card background (glassmorphic dark box)
            const bg = this.add.rectangle(0, 0, width * 0.72, 48, 0x0f1f38, 0.8)
                .setStrokeStyle(1, 0x1e3a5f)
                .setInteractive({ useHandCursor: true });

            // Radio badge / index indicator
            const badgeBg = this.add.circle(-width * 0.32, 0, 14, 0x1e293b);
            const badgeTxt = this.add.text(-width * 0.32, 0, String.fromCharCode(65 + idx), {
                font: 'bold 12px "Inter", sans-serif',
                fill: '#22d3ee'
            }).setOrigin(0.5);

            // Option text label
            const label = this.add.text(-width * 0.27, 0, opt.label, {
                font: '15px "Inter", sans-serif',
                fill: '#e2e8f0',
                wordWrap: { width: width * 0.58 }
            }).setOrigin(0, 0.5);

            container.add([bg, badgeBg, badgeTxt, label]);

            // Interaction
            bg.on('pointerover', () => {
                if (!this._executing && this.selectedOptionId !== opt.id) {
                    bg.setFillStyle(0x132a4d, 0.95);
                    bg.setStrokeStyle(1, 0x38bdf8);
                }
            });

            bg.on('pointerout', () => {
                if (this.selectedOptionId !== opt.id) {
                    bg.setFillStyle(0x0f1f38, 0.8);
                    bg.setStrokeStyle(1, 0x1e3a5f);
                }
            });

            bg.on('pointerdown', () => {
                if (!this._executing) this.selectOption(opt.id, container, bg, badgeBg);
            });

            this.optionButtons.push({ id: opt.id, container, bg, badgeBg, label: opt.label });
        });

        // ── Submit Button ────────────────────────────────────────────────
        const submitY = Math.min(height - 45, startY + (options.length * spacing) + 35);
        const submitBtn = this.add.container(width / 2, submitY);
        const btnBg = this.add.rectangle(0, 0, 240, 44, 0x0284c7)
            .setStrokeStyle(1, 0x38bdf8)
            .setInteractive({ useHandCursor: true });

        const btnLabel = this.add.text(0, 0, '⚡ VERIFY IDENTIFICATION', {
            font: 'bold 14px "Inter", sans-serif',
            fill: '#ffffff',
            letterSpacing: 1
        }).setOrigin(0.5);

        submitBtn.add([btnBg, btnLabel]);

        btnBg.on('pointerover', () => btnBg.setFillStyle(0x0ea5e9));
        btnBg.on('pointerout',  () => btnBg.setFillStyle(0x0284c7));
        btnBg.on('pointerdown', () => {
            btnBg.setScale(0.97);
            this.handleSubmit();
        });
        btnBg.on('pointerup',   () => btnBg.setScale(1.0));

        this._submitBtn = submitBtn;
        this._btnBg = btnBg;

        PuzzleUtils.ready();
    }

    selectOption(optId, container, bg, badgeBg) {
        if (this.selectedOptionId !== optId) this.clearVerdict();
        this.selectedOptionId = optId;
        if (window.NetFX) NetFX.sound('click');

        this.optionButtons.forEach(btn => {
            if (btn.id === optId) {
                btn.bg.setFillStyle(0x083344, 1.0);
                btn.bg.setStrokeStyle(2, 0x22d3ee);
                btn.badgeBg.setFillStyle(0x0891b2);
            } else {
                btn.bg.setFillStyle(0x0f1f38, 0.8);
                btn.bg.setStrokeStyle(1, 0x1e3a5f);
                btn.badgeBg.setFillStyle(0x1e293b);
            }
        });
    }

    handleSubmit() {
        if (this._isSubmitting) return;

        if (!this.selectedOptionId) {
            PuzzleUtils.showToast('⚠️ Please select an option before transmitting telemetry.', 'warning');
            return;
        }

        this._isSubmitting = true;
        this._executing = true;
        if (this._btnBg) this._btnBg.disableInteractive().setFillStyle(0x334155);

        const unlock = () => {
            this._isSubmitting = false;
            this._executing = false;
            if (this._btnBg) this._btnBg.setInteractive({ useHandCursor: true }).setFillStyle(0x0284c7);
        };

        PuzzleUtils.submitResponse({ selected_option: this.selectedOptionId }, {
            onResult: (data, ok) => this.execute(data, ok),
            onSuccess: () => { if (this._btnBg) this._btnBg.setFillStyle(0x059669); },
            onRetry: unlock, // the mismatch stamp stays until a different option is chosen
            onError: () => { unlock(); this.clearVerdict(); },
        });
    }

    clearVerdict() {
        if (this._verdict) this._verdict.forEach((o) => o.destroy());
        this._verdict = null;
    }

    // A beam sweeps the chosen card from left to right
    scan(btn) {
        const fx = PuzzleUtils.fx;
        const w = btn.bg.width;
        const x0 = btn.container.x - w / 2;
        const y = btn.container.y;
        const glow = this.add.rectangle(x0, y, 26, 54, 0x22d3ee, 0.18).setDepth(30);
        const beam = this.add.rectangle(x0, y, 3, 56, 0x67e8f9, 1).setDepth(31);
        if (window.NetFX) NetFX.sound('tick');
        return new Promise((resolve) => this.tweens.add({
            targets: [beam, glow], x: x0 + w, duration: fx.reduced() ? 1 : 700, ease: 'Sine.easeInOut', yoyo: !fx.reduced(),
            onComplete: () => { beam.destroy(); glow.destroy(); resolve(); },
        }));
    }

    async execute(data, ok) {
        const fx = PuzzleUtils.fx;
        const { width } = this.scale;
        const restoreSpeed = fx.enableSpeedUp(this);
        const btn = this.optionButtons.find((b) => b.id === this.selectedOptionId);
        this.clearVerdict();
        if (!btn) { restoreSpeed(); return; }

        try {
            await this.scan(btn);
            const { x, y } = btn.container;

            if (ok) {
                btn.bg.setFillStyle(0x052e1a, 1).setStrokeStyle(2, 0x22c55e);
                btn.badgeBg.setFillStyle(0x16a34a);
                if (window.NetFX) NetFX.sound('success');
                fx.burst(this, x, y, 0x22c55e, 20);

                // Device signature card slides in beside the matched option
                const cardX = Math.min(width - 110, x + btn.bg.width / 2 - 100);
                const card = this.add.container(cardX, y - 44).setDepth(40).setAlpha(0);
                const cbg = this.add.rectangle(0, 0, 200, 40, 0x022c22, 0.97).setStrokeStyle(1, 0x22c55e);
                const head = this.add.text(-90, -9, 'DEVICE SIGNATURE', { font: 'bold 10px monospace', fill: '#4ade80' }).setOrigin(0, 0.5);
                const body = this.add.text(-90, 8, `✓ ${btn.label}`.slice(0, 30), { font: '12px "Inter", sans-serif', fill: '#e2e8f0' }).setOrigin(0, 0.5);
                card.add([cbg, head, body]);
                this._verdict = [card];
                await new Promise((r) => this.tweens.add({ targets: card, alpha: 1, y: y - 40, duration: fx.reduced() ? 1 : 260, onComplete: r }));
            } else {
                btn.bg.setFillStyle(0x3b0a0a, 1).setStrokeStyle(2, 0xef4444);
                btn.badgeBg.setFillStyle(0xb91c1c);
                if (window.NetFX) NetFX.sound('error');

                // Rotated rubber stamp over the card
                const stamp = this.add.container(x + btn.bg.width * 0.18, y).setDepth(40).setAngle(-8);
                const sbg = this.add.rectangle(0, 0, 214, 30, 0x000000, 0).setStrokeStyle(3, 0xef4444);
                const stxt = this.add.text(0, 0, 'SIGNATURE MISMATCH', { font: 'bold 14px monospace', fill: '#f87171' }).setOrigin(0.5);
                stamp.add([sbg, stxt]);
                this._verdict = [stamp];
                if (!fx.reduced()) {
                    stamp.setScale(1.8).setAlpha(0);
                    await new Promise((r) => this.tweens.add({ targets: stamp, scale: 1, alpha: 1, duration: 180, ease: 'Back.easeOut', onComplete: r }));
                }
                fx.shake(this);
                await fx.wait(this, 300);
            }
        } finally {
            restoreSpeed();
        }
    }
}

// Phaser Bootstrapping
const config = {
    type: Phaser.AUTO,
    parent: 'phaser-game',
    width: 800,
    height: 500,
    backgroundColor: '#071426',
    scene: TemplateIdentification,
    scale: {
        mode: Phaser.Scale.FIT,
        autoCenter: Phaser.Scale.CENTER_BOTH,
    },
};

// Exposed for debugging and automated QA (inspect scene state, simulate placements)
window.netlearnGame = new Phaser.Game(config);
