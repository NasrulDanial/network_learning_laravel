/**
 * TemplateMatching.js
 * Phaser 3 Puzzle Archetype 2: Matching Protocols, Ports & Layer Definitions.
 *
 * Submits { pairs }. Each pair is then patched in as a cable, one at a time, with a spark
 * running along it: the first wrong pair (diagnostics.first_error, a left id) sparks red
 * and snaps; a fully correct set pulses green together.
 */
class TemplateMatching extends Phaser.Scene {
    constructor() {
        super({ key: 'TemplateMatching' });
        this.selectedLeft = null;
        this.pairs = {}; // leftId -> rightId
        this.leftNodes = {};
        this.rightNodes = {};
        this.lineGraphics = null;
    }

    create() {
        const { width, height } = this.scale;
        document.getElementById('game-loader')?.remove();

        const data = window.activityData || {};
        const leftItems = data.left_items || [
            { id: 'L_1', label: 'HTTP' },
            { id: 'L_2', label: 'HTTPS' },
            { id: 'L_3', label: 'DNS' },
            { id: 'L_4', label: 'SSH' }
        ];

        // Shuffle right items so matching isn't trivially aligned
        let rightItems = [...(data.right_items || [
            { id: 'R_1', label: 'Port 80 (Cleartext)' },
            { id: 'R_2', label: 'Port 443 (TLS Encrypted)' },
            { id: 'R_3', label: 'Port 53 (Name Resolution)' },
            { id: 'R_4', label: 'Port 22 (Secure Shell)' }
        ])];
        rightItems = Phaser.Utils.Array.Shuffle(rightItems);

        // Header
        this.add.text(width / 2, 35, window.puzzleData?.question_text || 'Protocol & Concept Association', {
            font: 'bold 18px "Inter", sans-serif',
            fill: '#ffffff'
        }).setOrigin(0.5);

        this.add.text(width / 2, 65, 'Click an item on the left, then click its corresponding match on the right.', {
            font: '13px "Inter", sans-serif',
            fill: '#94a3b8'
        }).setOrigin(0.5);

        this.lineGraphics = this.add.graphics();

        const startY = 120;
        const spacing = 65;
        const leftX = width * 0.28;
        const rightX = width * 0.72;

        // Left Column
        leftItems.forEach((item, idx) => {
            const y = startY + (idx * spacing);
            const container = this.add.container(leftX, y);
            const bg = this.add.rectangle(0, 0, 240, 48, 0x0f1f38)
                .setStrokeStyle(1, 0x1e3a5f)
                .setInteractive({ useHandCursor: true });

            const txt = this.add.text(0, 0, item.label, {
                font: 'bold 14px "Inter", sans-serif',
                fill: '#38bdf8',
                align: 'center'
            }).setOrigin(0.5);

            container.add([bg, txt]);

            bg.on('pointerdown', () => this.handleLeftClick(item.id));
            this.leftNodes[item.id] = { container, bg, txt, x: leftX + 120, y };
        });

        // Right Column
        rightItems.forEach((item, idx) => {
            const y = startY + (idx * spacing);
            const container = this.add.container(rightX, y);
            const bg = this.add.rectangle(0, 0, 250, 48, 0x0f1f38)
                .setStrokeStyle(1, 0x1e3a5f)
                .setInteractive({ useHandCursor: true });

            const txt = this.add.text(0, 0, item.label, {
                font: '13px "Inter", sans-serif',
                fill: '#e2e8f0',
                align: 'center',
                wordWrap: { width: 230 }
            }).setOrigin(0.5);

            container.add([bg, txt]);

            bg.on('pointerdown', () => this.handleRightClick(item.id));
            this.rightNodes[item.id] = { container, bg, txt, x: rightX - 125, y };
        });

        // Submit Button
        const submitY = Math.min(height - 40, startY + (Math.max(leftItems.length, rightItems.length) * spacing) + 30);
        const submitBtn = this.add.container(width / 2, submitY);
        const btnBg = this.add.rectangle(0, 0, 220, 42, 0x7c3aed)
            .setStrokeStyle(1, 0xa78bfa)
            .setInteractive({ useHandCursor: true });

        const btnLabel = this.add.text(0, 0, '🔗 TRANSMIT MAPPINGS', {
            font: 'bold 13px "Inter", sans-serif',
            fill: '#ffffff'
        }).setOrigin(0.5);

        submitBtn.add([btnBg, btnLabel]);

        btnBg.on('pointerover', () => btnBg.setFillStyle(0x8b5cf6));
        btnBg.on('pointerout',  () => btnBg.setFillStyle(0x7c3aed));
        btnBg.on('pointerdown', () => this.handleSubmit());

        this._submitBtnBg = btnBg;

        PuzzleUtils.ready();
        this.totalLeft = leftItems.length;
    }

    handleLeftClick(leftId) {
        if (this._executing) return;
        this.selectedLeft = leftId;
        Object.entries(this.leftNodes).forEach(([id, node]) => {
            if (id === leftId) {
                node.bg.setFillStyle(0x132a4d);
                node.bg.setStrokeStyle(2, 0x22d3ee);
            } else if (!this.pairs[id]) {
                node.bg.setFillStyle(0x0f1f38);
                node.bg.setStrokeStyle(1, 0x1e3a5f);
            }
        });
    }

    handleRightClick(rightId) {
        if (this._executing) return;
        if (!this.selectedLeft) {
            PuzzleUtils.showToast('ℹ️ Select a concept on the left first.', 'info', 2000);
            return;
        }

        // Remove any existing pair that used this rightId
        Object.keys(this.pairs).forEach(k => {
            if (this.pairs[k] === rightId) delete this.pairs[k];
        });

        // Assign pair
        this.pairs[this.selectedLeft] = rightId;

        // Reset left active selection
        if (this.leftNodes[this.selectedLeft]) {
            this.leftNodes[this.selectedLeft].bg.setFillStyle(0x0f2942);
            this.leftNodes[this.selectedLeft].bg.setStrokeStyle(1, 0x22d3ee);
        }
        this.selectedLeft = null;

        this.redrawLines();
    }

    redrawLines() {
        this.clearSnap();
        this.lineGraphics.clear();
        // Reset every node, then highlight the paired ones
        [...Object.values(this.leftNodes), ...Object.values(this.rightNodes)].forEach((n) => n.bg.setFillStyle(0x0f1f38).setStrokeStyle(1, 0x1e3a5f));
        this.lineGraphics.lineStyle(2, 0x22d3ee, 0.85);

        Object.entries(this.pairs).forEach(([lId, rId]) => {
            const lNode = this.leftNodes[lId];
            const rNode = this.rightNodes[rId];
            if (lNode && rNode) {
                this.lineGraphics.beginPath();
                this.lineGraphics.moveTo(lNode.x, lNode.y);
                this.lineGraphics.lineTo(rNode.x, rNode.y);
                this.lineGraphics.strokePath();

                // Highlight both endpoints
                lNode.bg.setFillStyle(0x0a334e);
                lNode.bg.setStrokeStyle(1, 0x38bdf8);
                rNode.bg.setFillStyle(0x0a334e);
                rNode.bg.setStrokeStyle(1, 0x38bdf8);
            }
        });
    }

    handleSubmit() {
        if (this._isSubmitting) return;

        const pairedCount = Object.keys(this.pairs).length;
        if (pairedCount < this.totalLeft) {
            PuzzleUtils.showToast(`⚠️ Please connect all ${this.totalLeft} pairs before submitting. (${pairedCount}/${this.totalLeft} mapped)`, 'warning');
            return;
        }

        this._isSubmitting = true;
        this._executing = true;
        if (this._submitBtnBg) this._submitBtnBg.disableInteractive().setFillStyle(0x334155);

        const unlock = () => {
            this._isSubmitting = false;
            this._executing = false;
            if (this._submitBtnBg) this._submitBtnBg.setInteractive({ useHandCursor: true }).setFillStyle(0x7c3aed);
        };

        PuzzleUtils.submitResponse({ pairs: this.pairs }, {
            onResult: (data, ok) => this.execute(data, ok),
            onSuccess: () => { if (this._submitBtnBg) this._submitBtnBg.setFillStyle(0x059669); },
            onRetry: unlock, // the snapped cable stays visible until the student re-patches
            onError: () => { unlock(); this.clearSnap(); this.redrawLines(); },
        });
    }

    // Cable between a left and right node as a cubic Bezier
    cableCurve(lNode, rNode) {
        const dx = (rNode.x - lNode.x) * 0.45;
        return new Phaser.Curves.CubicBezier(
            new Phaser.Math.Vector2(lNode.x, lNode.y),
            new Phaser.Math.Vector2(lNode.x + dx, lNode.y),
            new Phaser.Math.Vector2(rNode.x - dx, rNode.y),
            new Phaser.Math.Vector2(rNode.x, rNode.y),
        );
    }

    strokeCurves(curves, color, width = 3, alpha = 0.95) {
        this.lineGraphics.lineStyle(width, color, alpha);
        curves.forEach((c) => this.lineGraphics.strokePoints(c.getPoints(32), false, false));
    }

    // Draws the cable progressively with a spark at its tip, on top of the cables already patched
    patchCable(curve, color, done) {
        const fx = PuzzleUtils.fx;
        const spark = fx.packet(this, curve.p0.x, curve.p0.y, color);
        const pts = curve.getPoints(32);
        const drawTo = (t) => {
            this.lineGraphics.clear();
            this.strokeCurves(done, 0x22d3ee);
            this.lineGraphics.lineStyle(3, color, 0.95);
            this.lineGraphics.strokePoints(pts.filter((_, i) => i / 32 <= t).concat([curve.getPoint(t)]), false, false);
        };
        return new Promise((resolve) => {
            const state = { t: 0 };
            this.tweens.add({
                targets: state, t: 1, duration: fx.reduced() ? 1 : 520, ease: 'Sine.easeInOut',
                onUpdate: () => {
                    const p = curve.getPoint(state.t);
                    spark.c.setPosition(p.x, p.y);
                    spark.glow.setPosition(p.x, p.y);
                    drawTo(state.t);
                },
                onComplete: () => { drawTo(1); spark.destroy(); resolve(); },
            });
        });
    }

    // The two halves of a red cable fall apart and fade
    snapCable(curve) {
        const fx = PuzzleUtils.fx;
        const mid = curve.getPoint(0.5);
        const pts = curve.getPoints(32);
        this.clearSnap();
        this._snapped = [[0, 0.46, -1], [0.54, 1, 1]].map(([a, b, dir]) => {
            const g = this.add.graphics().setDepth(5);
            g.lineStyle(3, 0xef4444, 1).strokePoints(pts.filter((_, i) => i / 32 >= a && i / 32 <= b), false, false);
            this.tweens.add({ targets: g, y: 22, x: dir * 8, alpha: 0.25, duration: fx.reduced() ? 1 : 650, ease: 'Quad.easeIn' });
            return g;
        });
        fx.burst(this, mid.x, mid.y, 0xef4444, 18);
        return fx.wait(this, 450);
    }

    clearSnap() {
        if (this._snapped) this._snapped.forEach((g) => g.destroy());
        this._snapped = null;
    }

    paintNode(node, color, fill) {
        node.bg.setFillStyle(fill).setStrokeStyle(2, color);
    }

    async execute(data, ok) {
        const fx = PuzzleUtils.fx;
        const restoreSpeed = fx.enableSpeedUp(this);
        const firstError = data && data.diagnostics ? data.diagnostics.first_error : null;
        this.clearSnap();
        this.redrawLines(); // resets node colours from a previous attempt
        this.lineGraphics.clear();

        try {
            // Left column order (insertion order of leftNodes)
            const order = Object.keys(this.leftNodes).filter((id) => this.pairs[id]);
            const done = [];
            for (const lId of order) {
                const lNode = this.leftNodes[lId];
                const rNode = this.rightNodes[this.pairs[lId]];
                if (!lNode || !rNode) continue;
                const curve = this.cableCurve(lNode, rNode);
                const isBad = !ok && lId === firstError;
                if (window.NetFX) NetFX.sound(isBad ? 'error' : 'packet');
                await this.patchCable(curve, isBad ? 0xef4444 : 0x22d3ee, done);
                if (isBad) {
                    this.paintNode(lNode, 0xef4444, 0x3b0a0a);
                    this.paintNode(rNode, 0xef4444, 0x3b0a0a);
                    this.lineGraphics.clear();
                    this.strokeCurves(done, 0x22d3ee);
                    fx.shake(this);
                    await this.snapCable(curve);
                    return;
                }
                done.push(curve);
            }

            if (ok) {
                const pulse = { a: 1 };
                const paint = () => {
                    this.lineGraphics.clear();
                    this.strokeCurves(done, 0x22c55e, 3 + 2 * (1 - pulse.a), 0.5 + 0.5 * pulse.a);
                };
                order.forEach((lId) => {
                    this.paintNode(this.leftNodes[lId], 0x22c55e, 0x052e1a);
                    this.paintNode(this.rightNodes[this.pairs[lId]], 0x22c55e, 0x052e1a);
                });
                if (window.NetFX) NetFX.sound('linkup');
                await new Promise((r) => this.tweens.add({
                    targets: pulse, a: 0.2, duration: fx.reduced() ? 1 : 260, yoyo: true, repeat: 1,
                    onUpdate: paint, onComplete: () => { pulse.a = 1; paint(); r(); },
                }));
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
    scene: TemplateMatching,
    scale: {
        mode: Phaser.Scale.FIT,
        autoCenter: Phaser.Scale.CENTER_BOTH,
    },
};

// Exposed for debugging and automated QA (inspect scene state, simulate placements)
window.netlearnGame = new Phaser.Game(config);
