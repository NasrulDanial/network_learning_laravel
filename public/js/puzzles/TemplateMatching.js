/**
 * TemplateMatching.js
 * Phaser 3 Puzzle Archetype 2: Matching Protocols, Ports & Layer Definitions.
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
        this.lineGraphics.clear();
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

        if (this._submitBtnBg) {
            this._submitBtnBg.disableInteractive();
            this._submitBtnBg.setFillStyle(0x334155);
        }

        PuzzleUtils.submitResponse({
            pairs: this.pairs
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
    scene: TemplateMatching,
    scale: {
        mode: Phaser.Scale.FIT,
        autoCenter: Phaser.Scale.CENTER_BOTH,
    },
};

// Exposed for debugging and automated QA (inspect scene state, simulate placements)
window.netlearnGame = new Phaser.Game(config);
