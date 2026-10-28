/**
 * TemplateIdentification.js
 * Phaser 3 Puzzle Archetype 1: Identification & Hardware Recognition.
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
                if (this.selectedOptionId !== opt.id) {
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
                this.selectOption(opt.id, container, bg, badgeBg);
            });

            this.optionButtons.push({ id: opt.id, container, bg, badgeBg });
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
    }

    selectOption(optId, container, bg, badgeBg) {
        this.selectedOptionId = optId;

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

        if (this._btnBg) {
            this._btnBg.disableInteractive();
            this._btnBg.setFillStyle(0x334155);
        }

        PuzzleUtils.submitResponse({
            selected_option: this.selectedOptionId
        }, {
            onSuccess: () => {
                if (this._btnBg) this._btnBg.setFillStyle(0x059669);
            },
            onRetry: () => {
                this._isSubmitting = false;
                if (this._btnBg) {
                    this._btnBg.setInteractive({ useHandCursor: true });
                    this._btnBg.setFillStyle(0x0284c7);
                }
            },
            onError: () => {
                this._isSubmitting = false;
                if (this._btnBg) {
                    this._btnBg.setInteractive({ useHandCursor: true });
                    this._btnBg.setFillStyle(0x0284c7);
                }
            }
        });
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
