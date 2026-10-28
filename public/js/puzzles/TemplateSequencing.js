/**
 * TemplateSequencing.js
 * Phaser 3 Puzzle Archetype 3: Handshake, Encapsulation & Flow Sequencing.
 *
 * Submits { ordered_ids: [...] }. The answer is then "executed": each step, in the
 * student's order, flies as a message between two hosts; the run breaks at the first
 * position the server reports as wrong (diagnostics.first_error).
 */
const SEQ_THEMES = {
    tcp: { a: 'Client', b: 'Server', ok: 'State: ESTABLISHED', fail: 'Unexpected segment — connection reset (RST)' },
    dhcp: { a: 'Client', b: 'DHCP server', ok: 'Lease acquired: 192.168.1.42/24', fail: 'Out-of-order DHCP message — lease refused' },
    encapsulation: { a: 'Sender', b: 'Receiver', ok: 'Frame delivered and decoded', fail: 'Encapsulation order broken — frame discarded' },
    generic: { a: 'Host A', b: 'Host B', ok: 'Process complete', fail: 'Step out of order — process aborted' },
};

class TemplateSequencing extends Phaser.Scene {
    constructor() {
        super({ key: 'TemplateSequencing' });
        this.slots = [];
        this.blocks = [];
    }

    create() {
        const { width, height } = this.scale;
        document.getElementById('game-loader')?.remove();

        const data = window.activityData || {};
        const items = data.items || [
            { id: 'seq_1', label: 'Client sends TCP SYN' },
            { id: 'seq_2', label: 'Server replies TCP SYN-ACK' },
            { id: 'seq_3', label: 'Client confirms TCP ACK' },
        ];
        this.theme = SEQ_THEMES[data.theme] || SEQ_THEMES.generic;
        this.totalItems = items.length;

        this.add.text(width / 2, 26, window.puzzleData?.question_text || 'Reconstruct the sequence', {
            font: 'bold 17px "Inter", sans-serif', fill: '#ffffff',
        }).setOrigin(0.5);
        this.add.text(width / 2, 50, 'Drag the steps from the right into the numbered slots on the left (Step 1 → N).', {
            font: '12px "Inter", sans-serif', fill: '#94a3b8',
        }).setOrigin(0.5);

        const startY = 112;
        const spacing = Math.min(62, (height - 200) / Math.max(items.length, 3));
        this.slotWidth = 330;
        const blockWidth = 330;
        const cardHeight = Math.min(46, spacing - 8);
        this.rowY = items.map((_, idx) => startY + idx * spacing);

        // ── Slots (left column) ─────────────────────────────────────────
        items.forEach((item, idx) => {
            const y = this.rowY[idx];
            const slot = this.add.zone(200, y, this.slotWidth, cardHeight).setRectangleDropZone(this.slotWidth, cardHeight);
            slot.setData('index', idx);
            slot.setData('occupiedBy', null);

            const g = this.add.graphics();
            g.lineStyle(1.5, 0x334155, 1);
            g.strokeRoundedRect(slot.x - this.slotWidth / 2, slot.y - cardHeight / 2, this.slotWidth, cardHeight, 8);
            this.add.text(slot.x - this.slotWidth / 2 + 10, slot.y, `Step ${idx + 1}`, {
                font: 'bold 11px "Inter", sans-serif', fill: '#64748b',
            }).setOrigin(0, 0.5);
            this.slots.push(slot);
        });

        // ── Hosts and lanes (right side; used when the answer runs) ─────
        this.hostA = { x: 478, label: this.theme.a };
        this.hostB = { x: 712, label: this.theme.b };
        this.laneGfx = this.add.graphics().setDepth(0);
        this.hostGfx = this.add.graphics().setDepth(1);
        this.drawHosts(0x334155);
        [this.hostA, this.hostB].forEach((h) => {
            this.add.text(h.x, startY - 42, h.label, { font: 'bold 11px "Inter", sans-serif', fill: '#cbd5e1' }).setOrigin(0.5).setDepth(2);
        });

        // ── Draggable blocks (start over the lane area, shuffled) ───────
        const scrambled = Phaser.Utils.Array.Shuffle([...items]);
        scrambled.forEach((item, idx) => {
            const blockX = 605;
            const blockY = this.rowY[idx];
            const block = this.add.container(blockX + 40, blockY).setDepth(10).setAlpha(0);
            const bg = this.add.rectangle(0, 0, blockWidth, cardHeight, 0x1e293b).setStrokeStyle(1, 0x0284c7);
            const label = this.add.text(0, 0, item.label, {
                font: '12px "Inter", sans-serif', fill: '#e2e8f0', align: 'center', wordWrap: { width: blockWidth - 20 },
            }).setOrigin(0.5);

            block.add([bg, label]);
            block.setSize(blockWidth, cardHeight);
            block.setInteractive({ useHandCursor: true });
            this.input.setDraggable(block);
            block.setData('itemId', item.id);
            block.setData('label', item.label);
            block.setData('currentSlot', null);
            block.setData('homeX', blockX);
            block.setData('homeY', blockY);
            block._bg = bg;
            this.blocks.push(block);

            this.tweens.add({ targets: block, x: blockX, alpha: 1, duration: 300, delay: idx * 60, ease: 'Cubic.easeOut' });
        });

        // ── Drag events ─────────────────────────────────────────────────
        this.input.on('dragstart', (pointer, gameObject) => {
            if (this._executing) return;
            this.children.bringToTop(gameObject);
            gameObject.setScale(1.04);
            gameObject._bg.setFillStyle(0x0f2a4a).setStrokeStyle(2, 0x22d3ee);
            this.clearMarks();
            if (window.NetFX) NetFX.sound('click');
        });

        this.input.on('drag', (pointer, gameObject, dragX, dragY) => {
            if (this._executing) return;
            gameObject.x = dragX;
            gameObject.y = dragY;
        });

        this.input.on('drop', (pointer, gameObject, dropZone) => {
            if (this._executing) return;
            const occupiedById = dropZone.getData('occupiedBy');
            if (occupiedById && occupiedById !== gameObject.getData('itemId')) {
                this.sendHome(this.blocks.find((b) => b.getData('itemId') === occupiedById));
            }
            const prevSlotIndex = gameObject.getData('currentSlot');
            if (prevSlotIndex !== null) this.slots[prevSlotIndex].setData('occupiedBy', null);

            gameObject.setScale(1);
            gameObject.x = dropZone.x;
            gameObject.y = dropZone.y;
            gameObject.setData('currentSlot', dropZone.getData('index'));
            dropZone.setData('occupiedBy', gameObject.getData('itemId'));
            gameObject._bg.setFillStyle(0x0b3b5b).setStrokeStyle(1, 0x38bdf8);
        });

        this.input.on('dragend', (pointer, gameObject, dropped) => {
            gameObject.setScale(1);
            if (!dropped) this.sendHome(gameObject);
        });

        // ── Submit ──────────────────────────────────────────────────────
        const submitBtn = this.add.container(width / 2, height - 30).setDepth(20);
        const btnBg = this.add.rectangle(0, 0, 240, 40, 0xca8a04).setStrokeStyle(1, 0xfacc15).setInteractive({ useHandCursor: true });
        const btnLabel = this.add.text(0, 0, '▶ RUN THE SEQUENCE', { font: 'bold 13px "Inter", sans-serif', fill: '#ffffff' }).setOrigin(0.5);
        submitBtn.add([btnBg, btnLabel]);
        btnBg.on('pointerover', () => btnBg.setFillStyle(0xeab308));
        btnBg.on('pointerout', () => btnBg.setFillStyle(this._isSubmitting ? 0x334155 : 0xca8a04));
        btnBg.on('pointerdown', () => this.handleSubmit());
        this._submitBtnBg = btnBg;

        PuzzleUtils.ready();
    }

    drawHosts(color) {
        const top = this.rowY[0] - 22;
        const bottom = this.rowY[this.rowY.length - 1] + 22;
        this.laneGfx.clear();
        this.laneGfx.lineStyle(1, 0x1e3a5f, 1);
        [this.hostA, this.hostB].forEach((h) => this.laneGfx.lineBetween(h.x, top, h.x, bottom));
        this.hostGfx.clear();
        [this.hostA, this.hostB].forEach((h) => {
            this.hostGfx.fillStyle(0x0f172a, 1).fillRoundedRect(h.x - 48, top - 32, 96, 24, 6);
            this.hostGfx.lineStyle(2, color, 1).strokeRoundedRect(h.x - 48, top - 32, 96, 24, 6);
        });
    }

    sendHome(block) {
        if (!block) return;
        const prevSlotIndex = block.getData('currentSlot');
        if (prevSlotIndex !== null && this.slots[prevSlotIndex].getData('occupiedBy') === block.getData('itemId')) {
            this.slots[prevSlotIndex].setData('occupiedBy', null);
        }
        block.setScale(1);
        block.x = block.getData('homeX');
        block.y = block.getData('homeY');
        block.setData('currentSlot', null);
        block._bg.setFillStyle(0x1e293b).setStrokeStyle(1, 0x0284c7);
    }

    clearMarks() {
        this.blocks.forEach((b) => {
            if (b.getData('currentSlot') !== null) b._bg.setFillStyle(0x0b3b5b).setStrokeStyle(1, 0x38bdf8);
        });
        this.drawHosts(0x334155);
    }

    setLocked(locked) {
        this._executing = locked;
        this.blocks.forEach((b) => (locked ? b.disableInteractive() : b.setInteractive({ useHandCursor: true })));
    }

    handleSubmit() {
        const unplaced = this.slots.filter((s) => s.getData('occupiedBy') === null);
        if (unplaced.length > 0) {
            PuzzleUtils.showToast(`⚠️ Place a step in all ${this.totalItems} slots first.`, 'warning');
            return;
        }
        if (this._isSubmitting) return;
        this._isSubmitting = true;
        this._submitBtnBg.disableInteractive().setFillStyle(0x334155);
        this.setLocked(true);

        const unlock = () => {
            this._isSubmitting = false;
            this.setLocked(false);
            this._submitBtnBg.setInteractive({ useHandCursor: true }).setFillStyle(0xca8a04);
        };

        PuzzleUtils.submitResponse({ ordered_ids: this.slots.map((s) => s.getData('occupiedBy')) }, {
            onResult: (data, ok) => this.execute(data, ok),
            onSuccess: () => this._submitBtnBg.setFillStyle(0x059669),
            onRetry: unlock,
            onError: unlock,
        });
    }

    blockInSlot(i) {
        const id = this.slots[i].getData('occupiedBy');
        return this.blocks.find((b) => b.getData('itemId') === id);
    }

    async execute(data, ok) {
        const fx = PuzzleUtils.fx;
        const restoreSpeed = fx.enableSpeedUp(this);
        const failAt = ok ? null : (data && data.diagnostics && data.diagnostics.first_error !== null ? Number(data.diagnostics.first_error) : this.slots.length - 1);
        this.clearMarks();

        if (this.term) this.term.destroy();
        this.term = PuzzleUtils.terminal(this, 20, this.scale.height - 104, this.scale.width - 40, 52);

        try {
            for (let i = 0; i < this.slots.length; i++) {
                const block = this.blockInSlot(i);
                const y = this.rowY[i];
                const fromA = i % 2 === 0;
                const from = fromA ? this.hostA : this.hostB;
                const to = fromA ? this.hostB : this.hostA;
                block._bg.setStrokeStyle(2, 0x22d3ee);

                const text = (block.getData('label') || '').slice(0, 26);
                const msg = this.add.container(from.x, y).setDepth(30);
                const pill = this.add.rectangle(0, 0, 150, 22, 0x0e7490, 0.95).setStrokeStyle(1, 0x67e8f9);
                const label = this.add.text(0, 0, text, { font: 'bold 10px "Inter", sans-serif', fill: '#ecfeff' }).setOrigin(0.5);
                msg.add([pill, label]);
                if (window.NetFX) NetFX.sound('packet');

                if (i === failAt) {
                    // Wrong step: the message is rejected halfway and bounces back
                    await new Promise((r) => this.tweens.add({ targets: msg, x: (from.x + to.x) / 2, duration: fx.reduced() ? 1 : 360, ease: 'Sine.easeOut', onComplete: r }));
                    pill.setFillStyle(0x991b1b).setStrokeStyle(1, 0xfca5a5);
                    block._bg.setFillStyle(0x3b0d12).setStrokeStyle(2, 0xef4444);
                    fx.shake(this);
                    if (window.NetFX) NetFX.sound('drop');
                    await new Promise((r) => this.tweens.add({ targets: msg, x: from.x, alpha: 0, duration: fx.reduced() ? 1 : 320, ease: 'Back.easeIn', onComplete: r }));
                    msg.destroy();
                    await this.term.log(`Step ${i + 1}: ${this.theme.fail}`, '#fca5a5');
                    return;
                }

                await new Promise((r) => this.tweens.add({ targets: msg, x: to.x, duration: fx.reduced() ? 1 : 420, ease: 'Sine.easeInOut', onComplete: r }));
                block._bg.setFillStyle(0x052e1a).setStrokeStyle(1, 0x22c55e);
                this.tweens.add({ targets: msg, alpha: 0, duration: 200, onComplete: () => msg.destroy() });
            }

            this.drawHosts(0x22c55e);
            if (window.NetFX) NetFX.sound('linkup');
            fx.burst(this, this.hostA.x, this.rowY[0] - 42, 0x22c55e, 16);
            fx.burst(this, this.hostB.x, this.rowY[0] - 42, 0x22c55e, 16);
            await this.term.log(this.theme.ok, '#4ade80');
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
    scene: TemplateSequencing,
    scale: {
        mode: Phaser.Scale.FIT,
        autoCenter: Phaser.Scale.CENTER_BOTH,
    },
};

// Exposed for debugging and automated QA (inspect scene state, simulate placements)
window.netlearnGame = new Phaser.Game(config);
