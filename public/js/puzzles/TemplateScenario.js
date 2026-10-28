/**
 * TemplateScenario.js
 * Phaser 3 Puzzle Archetype 6: Scenario-based decision (troubleshooting).
 *
 * Submits { choice_id }. The chosen action is then "executed" on a small Network
 * Operations Center panel: the right action brings every segment up; a wrong one takes
 * more segments down and prints the lecturer's consequence for that choice.
 */
class TemplateScenario extends Phaser.Scene {
    constructor() {
        super({ key: 'TemplateScenario' });
        this.selectedChoiceId = null;
        this.choiceButtons = [];
        this.statusLights = [];
    }

    create() {
        const { width, height } = this.scale;
        document.getElementById('game-loader')?.remove();

        const data = window.activityData || {};
        const scenarioText = data.scenario_text || window.puzzleData?.instruction || 'An enterprise edge router has lost its upstream link. Select the immediate remediation.';
        const choices = data.choices && data.choices.length ? data.choices : [
            { id: 'choice_1', label: 'Verify the physical link and ping the upstream gateway.' },
            { id: 'choice_2', label: 'Wipe all routing tables and reboot the core switch.' },
            { id: 'choice_3', label: 'Assign a random public IP address to the loopback.' },
        ];
        this.choicesData = choices;
        const lightsData = data.status_lights && data.status_lights.length ? data.status_lights.slice(0, 5) : [
            { id: 'internet', label: 'Internet', initial: 'up' },
            { id: 'core', label: 'Core Switch', initial: 'up' },
            { id: 'lab', label: 'Lab Block B', initial: 'down' },
            { id: 'servers', label: 'Server Farm', initial: 'up' },
        ];

        // ── NOC status panel ────────────────────────────────────────────
        const panelW = width * 0.88;
        this.add.rectangle(width / 2, 34, panelW, 46, 0x0b1626, 0.95).setStrokeStyle(1, 0x1e3a5f);
        this.add.text(width / 2 - panelW / 2 + 12, 34, 'NOC', { font: 'bold 11px monospace', fill: '#64748b' }).setOrigin(0, 0.5);
        const slotW = (panelW - 60) / lightsData.length;
        lightsData.forEach((l, idx) => {
            const x = width / 2 - panelW / 2 + 60 + slotW * idx + 14;
            const dot = this.add.circle(x, 34, 7, 0x22c55e);
            const label = this.add.text(x + 14, 34, l.label, { font: '12px "Inter", sans-serif', fill: '#cbd5e1' }).setOrigin(0, 0.5);
            const light = { dot, label, initial: l.initial === 'down' ? 'down' : 'up', blink: null };
            this.statusLights.push(light);
        });
        this.resetLights();

        // ── Incident briefing ───────────────────────────────────────────
        this.add.rectangle(width / 2, 104, width * 0.88, 74, 0x0c1e36, 0.9).setStrokeStyle(1, 0xf97316);
        this.add.text(width / 2 - width * 0.42, 78, '> INCIDENT', { font: 'bold 11px monospace', fill: '#fdba74' }).setOrigin(0, 0.5);
        this.add.text(width / 2, 110, scenarioText, {
            font: '13px "Inter", sans-serif', fill: '#e2e8f0', align: 'center', wordWrap: { width: width * 0.82 },
        }).setOrigin(0.5);

        // ── Choices ─────────────────────────────────────────────────────
        const startY = 172;
        const spacing = Math.min(56, 200 / Math.max(choices.length, 1));
        choices.forEach((choice, idx) => {
            const y = startY + idx * spacing;
            const container = this.add.container(width / 2, y).setAlpha(0);
            const bg = this.add.rectangle(0, 0, width * 0.86, 46, 0x0f1f38, 0.85).setStrokeStyle(1, 0x1e3a5f).setInteractive({ useHandCursor: true });
            const badgeBg = this.add.circle(-width * 0.39, 0, 13, 0x1e293b);
            const badgeTxt = this.add.text(-width * 0.39, 0, String(idx + 1), { font: 'bold 12px "Inter", sans-serif', fill: '#fb923c' }).setOrigin(0.5);
            const label = this.add.text(-width * 0.34, 0, choice.label, { font: '13px "Inter", sans-serif', fill: '#cbd5e1', wordWrap: { width: width * 0.72 } }).setOrigin(0, 0.5);
            container.add([bg, badgeBg, badgeTxt, label]);

            bg.on('pointerover', () => {
                if (this._executing || this.selectedChoiceId === choice.id) return;
                bg.setFillStyle(0x1e293b, 0.95).setStrokeStyle(1, 0xfb923c);
            });
            bg.on('pointerout', () => {
                if (this.selectedChoiceId === choice.id) return;
                bg.setFillStyle(0x0f1f38, 0.85).setStrokeStyle(1, 0x1e3a5f);
            });
            bg.on('pointerdown', () => { if (!this._executing) this.selectChoice(choice.id); });

            this.choiceButtons.push({ id: choice.id, bg, badgeBg, container, label: choice.label });
            this.tweens.add({ targets: container, alpha: 1, duration: 260, delay: idx * 70 });
        });

        // ── Submit ──────────────────────────────────────────────────────
        const submitBtn = this.add.container(width / 2, height - 30);
        const btnBg = this.add.rectangle(0, 0, 250, 40, 0xc2410c).setStrokeStyle(1, 0xfb923c).setInteractive({ useHandCursor: true });
        const btnLabel = this.add.text(0, 0, '⚡ EXECUTE ACTION', { font: 'bold 13px "Inter", sans-serif', fill: '#ffffff' }).setOrigin(0.5);
        submitBtn.add([btnBg, btnLabel]);
        btnBg.on('pointerover', () => btnBg.setFillStyle(0xea580c));
        btnBg.on('pointerout', () => btnBg.setFillStyle(this._isSubmitting ? 0x334155 : 0xc2410c));
        btnBg.on('pointerdown', () => this.handleSubmit());
        this._submitBtnBg = btnBg;

        PuzzleUtils.ready();
    }

    // ── Status lights ───────────────────────────────────────────────────
    setLight(light, state) {
        if (light.blink) { light.blink.stop(); light.blink = null; light.dot.setAlpha(1); }
        light.state = state;
        light.dot.setFillStyle(state === 'up' ? 0x22c55e : 0xef4444);
        if (state === 'down' && !PuzzleUtils.fx.reduced()) {
            light.blink = this.tweens.add({ targets: light.dot, alpha: 0.25, duration: 420, yoyo: true, repeat: -1 });
        }
    }

    resetLights() {
        this.statusLights.forEach((l) => this.setLight(l, l.initial));
    }

    selectChoice(choiceId) {
        if (this.selectedChoiceId !== choiceId) this.resetLights();
        this.selectedChoiceId = choiceId;
        if (window.NetFX) NetFX.sound('click');
        this.choiceButtons.forEach((b) => {
            const on = b.id === choiceId;
            b.bg.setFillStyle(on ? 0x431407 : 0x0f1f38, on ? 1 : 0.85).setStrokeStyle(on ? 2 : 1, on ? 0xf97316 : 0x1e3a5f);
            b.badgeBg.setFillStyle(on ? 0xea580c : 0x1e293b);
        });
    }

    handleSubmit() {
        if (!this.selectedChoiceId) {
            PuzzleUtils.showToast('⚠️ Select a course of action before executing.', 'warning');
            return;
        }
        if (this._isSubmitting) return;
        this._isSubmitting = true;
        this._executing = true;
        this._submitBtnBg.disableInteractive().setFillStyle(0x334155);

        const unlock = () => {
            this._isSubmitting = false;
            this._executing = false;
            this._submitBtnBg.setInteractive({ useHandCursor: true }).setFillStyle(0xc2410c);
        };

        PuzzleUtils.submitResponse({ choice_id: this.selectedChoiceId }, {
            onResult: (data, ok) => this.execute(data, ok),
            onSuccess: () => this._submitBtnBg.setFillStyle(0x059669),
            onRetry: unlock, // the damage stays visible until a different action is chosen
            onError: unlock,
        });
    }

    async execute(data, ok) {
        const fx = PuzzleUtils.fx;
        const { width, height } = this.scale;
        const restoreSpeed = fx.enableSpeedUp(this);
        const chosen = this.choiceButtons.find((b) => b.id === this.selectedChoiceId);
        this.resetLights();

        if (this.term) this.term.destroy();
        if (this.bar) { this.bar.destroy(); this.barBg.destroy(); }
        this.term = PuzzleUtils.terminal(this, 20, height - 112, width - 40, 58);

        try {
            await this.term.log(`Executing: ${chosen ? chosen.label : 'selected action'}`, '#e2e8f0');

            // Progress bar while the action "runs"
            this.barBg = this.add.rectangle(width / 2, height - 122, width - 40, 6, 0x1e293b).setDepth(42);
            this.bar = this.add.rectangle(20, height - 122, 1, 6, ok ? 0x22d3ee : 0xf59e0b).setOrigin(0, 0.5).setDepth(43);
            await new Promise((r) => this.tweens.add({ targets: this.bar, width: width - 40, duration: fx.reduced() ? 1 : 1200, ease: 'Sine.easeInOut', onComplete: r }));

            if (ok) {
                for (const light of this.statusLights) {
                    if (light.state !== 'up') {
                        this.setLight(light, 'up');
                        if (window.NetFX) NetFX.sound('linkup');
                        fx.burst(this, light.dot.x, light.dot.y, 0x22c55e, 14);
                        await fx.wait(this, 260);
                    }
                }
                this.statusLights.forEach((l) => this.setLight(l, 'up'));
                await this.term.log('Incident resolved. All segments reporting UP.', '#4ade80');
            } else {
                // A wrong action takes more of the network down
                const upLights = this.statusLights.filter((l) => l.state === 'up').slice(0, 2);
                for (const light of upLights) {
                    this.setLight(light, 'down');
                    if (window.NetFX) NetFX.sound('error');
                    await fx.wait(this, 240);
                }
                fx.flashCam(this);
                fx.shake(this);
                const consequence = (data && (data.consequence || data.feedback)) || 'The action made the outage worse.';
                await this.term.log(consequence, '#fca5a5');
            }
        } finally {
            restoreSpeed();
        }
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
