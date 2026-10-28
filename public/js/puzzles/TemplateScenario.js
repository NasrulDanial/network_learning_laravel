/**
 * TemplateScenario.js
 * Phaser 3 Puzzle Archetype 6: Scenario-Based Decision & Situational Troubleshooting.
 */
class TemplateScenario extends Phaser.Scene {
    constructor() {
        super({ key: 'TemplateScenario' });
        this.selectedChoiceId = null;
        this.choiceButtons = [];
    }

    create() {
        const { width, height } = this.scale;
        document.getElementById('game-loader')?.remove();

        const data = window.activityData || {};
        const scenarioText = data.scenario_text || window.puzzleData?.instruction || 'An enterprise edge router has lost BGP peering with the upstream ISP. Select the immediate remediation strategy.';
        const choices = data.choices || [
            { id: 'choice_1', label: 'Verify Layer 1 physical link and ping upstream provider default gateway.' },
            { id: 'choice_2', label: 'Wipe all BGP routing tables and reboot the core switch.' },
            { id: 'choice_3', label: 'Assign a random public IP address to the management loopback.' }
        ];

        // ── Mission Directive Badge ──────────────────────────────────────
        this.add.text(width / 2, 28, 'OPERATIONAL INCIDENT SIMULATION', {
            font: 'bold 11px "Inter", monospace',
            fill: '#fb923c',
            letterSpacing: 2
        }).setOrigin(0.5);

        // ── Scenario Narrative Terminal Card ─────────────────────────────
        const terminalBox = this.add.container(width / 2, 95);
        const termBg = this.add.rectangle(0, 0, width * 0.88, 85, 0x0c1e36, 0.9)
            .setStrokeStyle(1, 0xf97316);

        const termHeader = this.add.text(-width * 0.42, -28, '> INCIDENT_BRIEFING:', {
            font: 'bold 11px monospace',
            fill: '#fdba74'
        }).setOrigin(0, 0.5);

        const narrative = this.add.text(0, 8, scenarioText, {
            font: '13px "Inter", sans-serif',
            fill: '#e2e8f0',
            align: 'center',
            wordWrap: { width: width * 0.82 }
        }).setOrigin(0.5);

        terminalBox.add([termBg, termHeader, narrative]);

        // ── Choices Grid ─────────────────────────────────────────────────
        const startY = 185;
        const spacing = 62;

        choices.forEach((choice, idx) => {
            const y = startY + (idx * spacing);
            const container = this.add.container(width / 2, y);

            const bg = this.add.rectangle(0, 0, width * 0.86, 50, 0x0f1f38, 0.85)
                .setStrokeStyle(1, 0x1e3a5f)
                .setInteractive({ useHandCursor: true });

            const badgeBg = this.add.circle(-width * 0.39, 0, 14, 0x1e293b);
            const badgeTxt = this.add.text(-width * 0.39, 0, String(idx + 1), {
                font: 'bold 12px "Inter", sans-serif',
                fill: '#fb923c'
            }).setOrigin(0.5);

            const label = this.add.text(-width * 0.34, 0, choice.label, {
                font: '13px "Inter", sans-serif',
                fill: '#cbd5e1',
                wordWrap: { width: width * 0.72 }
            }).setOrigin(0, 0.5);

            container.add([bg, badgeBg, badgeTxt, label]);

            bg.on('pointerover', () => {
                if (this.selectedChoiceId !== choice.id) {
                    bg.setFillStyle(0x1e293b, 0.95);
                    bg.setStrokeStyle(1, 0xfb923c);
                }
            });

            bg.on('pointerout', () => {
                if (this.selectedChoiceId !== choice.id) {
                    bg.setFillStyle(0x0f1f38, 0.85);
                    bg.setStrokeStyle(1, 0x1e3a5f);
                }
            });

            bg.on('pointerdown', () => {
                this.selectChoice(choice.id);
            });

            this.choiceButtons.push({ id: choice.id, bg, badgeBg });
        });

        // ── Submit Button ────────────────────────────────────────────────
        const submitY = height - 38;
        const submitBtn = this.add.container(width / 2, submitY);
        const btnBg = this.add.rectangle(0, 0, 250, 42, 0xc2410c)
            .setStrokeStyle(1, 0xfb923c)
            .setInteractive({ useHandCursor: true });

        const btnLabel = this.add.text(0, 0, '⚡ EXECUTE REMEDIATION', {
            font: 'bold 13px "Inter", sans-serif',
            fill: '#ffffff'
        }).setOrigin(0.5);

        submitBtn.add([btnBg, btnLabel]);

        btnBg.on('pointerover', () => btnBg.setFillStyle(0xea580c));
        btnBg.on('pointerout',  () => btnBg.setFillStyle(0xc2410c));
        btnBg.on('pointerdown', () => this.handleSubmit());

        this._submitBtnBg = btnBg;

        PuzzleUtils.ready();
    }

    selectChoice(choiceId) {
        this.selectedChoiceId = choiceId;

        this.choiceButtons.forEach(b => {
            if (b.id === choiceId) {
                b.bg.setFillStyle(0x431407, 1.0);
                b.bg.setStrokeStyle(2, 0xf97316);
                b.badgeBg.setFillStyle(0xea580c);
            } else {
                b.bg.setFillStyle(0x0f1f38, 0.85);
                b.bg.setStrokeStyle(1, 0x1e3a5f);
                b.badgeBg.setFillStyle(0x1e293b);
            }
        });
    }

    handleSubmit() {
        if (!this.selectedChoiceId) {
            PuzzleUtils.showToast('⚠️ Please select a course of action before executing.', 'warning');
            return;
        }

        if (this._isSubmitting) return;
        this._isSubmitting = true;

        if (this._submitBtnBg) {
            this._submitBtnBg.disableInteractive();
            this._submitBtnBg.setFillStyle(0x334155);
        }

        PuzzleUtils.submitResponse({
            choice_id: this.selectedChoiceId
        }, {
            onSuccess: () => {
                if (this._submitBtnBg) this._submitBtnBg.setFillStyle(0x059669);
            },
            onRetry: () => {
                this._isSubmitting = false;
                if (this._submitBtnBg) {
                    this._submitBtnBg.setInteractive({ useHandCursor: true });
                    this._submitBtnBg.setFillStyle(0x0284c7);
                }
            },
            onError: () => {
                this._isSubmitting = false;
                if (this._submitBtnBg) {
                    this._submitBtnBg.setInteractive({ useHandCursor: true });
                    this._submitBtnBg.setFillStyle(0x0284c7);
                }
            }
        });
    }
}

const config = {
    type: Phaser.AUTO,
    parent: 'phaser-game',
    width: 800,
    height: 500,
    backgroundColor: '#071426',
    scene: TemplateScenario,
    scale: {
        mode: Phaser.Scale.FIT,
        autoCenter: Phaser.Scale.CENTER_BOTH,
    },
};

// Exposed for debugging and automated QA (inspect scene state, simulate placements)
window.netlearnGame = new Phaser.Game(config);
