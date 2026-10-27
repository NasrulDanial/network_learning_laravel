/**
 * TemplateSequencing.js
 * Phaser 3 Puzzle Archetype 3: Handshake, Encapsulation & Flow Sequencing.
 */
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
            { id: 'seq_3', label: 'Client confirms TCP ACK' }
        ];

        this.totalItems = items.length;

        // Title
        this.add.text(width / 2, 30, window.puzzleData?.question_text || 'Reconstruct Chronological Sequence', {
            font: 'bold 18px "Inter", sans-serif',
            fill: '#ffffff'
        }).setOrigin(0.5);

        this.add.text(width / 2, 58, 'Drag the scrambled operations from the right into the ordered slots on the left (Step 1 → N).', {
            font: '12px "Inter", sans-serif',
            fill: '#94a3b8'
        }).setOrigin(0.5);

        const startY = 100;
        const spacing = Math.min(65, (height - 180) / Math.max(items.length, 3));
        const slotWidth = 320;
        const blockWidth = 320;
        const cardHeight = Math.min(48, spacing - 8);

        // ── Drop Slots (Left Column) ─────────────────────────────────────
        items.forEach((item, idx) => {
            const y = startY + (idx * spacing);
            const slot = this.add.zone(width * 0.28, y, slotWidth, cardHeight).setRectangleDropZone(slotWidth, cardHeight);
            slot.setData('index', idx);
            slot.setData('occupiedBy', null);

            const g = this.add.graphics();
            g.lineStyle(1.5, 0x334155, 1);
            g.strokeRect(slot.x - (slotWidth / 2), slot.y - (cardHeight / 2), slotWidth, cardHeight);

            this.add.text(slot.x - (slotWidth / 2) + 12, slot.y, `Step ${idx + 1}`, {
                font: 'bold 12px "Inter", sans-serif',
                fill: '#64748b'
            }).setOrigin(0, 0.5);

            this.slots.push(slot);
        });

        // ── Draggable Blocks (Right Column - Shuffled) ───────────────────
        const scrambled = Phaser.Utils.Array.Shuffle([...items]);
        scrambled.forEach((item, idx) => {
            const blockX = width * 0.72;
            const blockY = startY + (idx * spacing);

            const block = this.add.container(blockX, blockY);
            const bg = this.add.rectangle(0, 0, blockWidth, cardHeight, 0x1e293b)
                .setStrokeStyle(1, 0x0284c7);

            const label = this.add.text(0, 0, item.label, {
                font: '13px "Inter", sans-serif',
                fill: '#e2e8f0',
                align: 'center',
                wordWrap: { width: blockWidth - 20 }
            }).setOrigin(0.5);

            block.add([bg, label]);
            block.setSize(blockWidth, cardHeight);
            block.setInteractive({ useHandCursor: true });
            this.input.setDraggable(block);

            block.setData('itemId', item.id);
            block.setData('currentSlot', null);
            block.setData('homeX', blockX);
            block.setData('homeY', blockY);
            block._bg = bg;

            this.blocks.push(block);
        });

        // ── Drag Events ──────────────────────────────────────────────────
        this.input.on('dragstart', (pointer, gameObject) => {
            this.children.bringToTop(gameObject);
            gameObject._bg.setFillStyle(0x0f2a4a);
            gameObject._bg.setStrokeStyle(2, 0x22d3ee);
        });

        this.input.on('drag', (pointer, gameObject, dragX, dragY) => {
            gameObject.x = dragX;
            gameObject.y = dragY;
        });

        this.input.on('drop', (pointer, gameObject, dropZone) => {
            // Check if slot occupied
            const occupiedById = dropZone.getData('occupiedBy');
            if (occupiedById && occupiedById !== gameObject.getData('itemId')) {
                const evicted = this.blocks.find(b => b.getData('itemId') === occupiedById);
                if (evicted) {
                    evicted.x = evicted.getData('homeX');
                    evicted.y = evicted.getData('homeY');
                    evicted.setData('currentSlot', null);
                    evicted._bg.setFillStyle(0x1e293b);
                    evicted._bg.setStrokeStyle(1, 0x0284c7);
                }
            }

            // Release old slot if previously in one
            const prevSlotIndex = gameObject.getData('currentSlot');
            if (prevSlotIndex !== null) {
                this.slots[prevSlotIndex].setData('occupiedBy', null);
            }

            // Snap to new slot
            gameObject.x = dropZone.x;
            gameObject.y = dropZone.y;
            gameObject.setData('currentSlot', dropZone.getData('index'));
            dropZone.setData('occupiedBy', gameObject.getData('itemId'));
            gameObject._bg.setFillStyle(0x0b3b5b);
            gameObject._bg.setStrokeStyle(1, 0x38bdf8);
        });

        this.input.on('dragend', (pointer, gameObject, dropped) => {
            if (!dropped) {
                const prevSlotIndex = gameObject.getData('currentSlot');
                if (prevSlotIndex !== null) {
                    this.slots[prevSlotIndex].setData('occupiedBy', null);
                }
                gameObject.x = gameObject.getData('homeX');
                gameObject.y = gameObject.getData('homeY');
                gameObject.setData('currentSlot', null);
                gameObject._bg.setFillStyle(0x1e293b);
                gameObject._bg.setStrokeStyle(1, 0x0284c7);
            }
        });

        // ── Submit Button ────────────────────────────────────────────────
        const submitY = height - 40;
        const submitBtn = this.add.container(width / 2, submitY);
        const btnBg = this.add.rectangle(0, 0, 240, 42, 0xca8a04)
            .setStrokeStyle(1, 0xfacc15)
            .setInteractive({ useHandCursor: true });

        const btnLabel = this.add.text(0, 0, '🔢 VERIFY SEQUENCE ORDER', {
            font: 'bold 13px "Inter", sans-serif',
            fill: '#ffffff'
        }).setOrigin(0.5);

        submitBtn.add([btnBg, btnLabel]);

        btnBg.on('pointerover', () => btnBg.setFillStyle(0xeab308));
        btnBg.on('pointerout',  () => btnBg.setFillStyle(0xca8a04));
        btnBg.on('pointerdown', () => this.handleSubmit());

        this._submitBtnBg = btnBg;
    }

    handleSubmit() {
        const unplaced = this.slots.filter(s => s.getData('occupiedBy') === null);
        if (unplaced.length > 0) {
            PuzzleUtils.showToast(`⚠️ Please place an operation in all ${this.totalItems} steps before verifying.`, 'warning');
            return;
        }

        if (this._isSubmitting) return;
        this._isSubmitting = true;

        const orderedIds = this.slots.map(s => s.getData('occupiedBy'));

        if (this._submitBtnBg) {
            this._submitBtnBg.disableInteractive();
            this._submitBtnBg.setFillStyle(0x334155);
        }

        PuzzleUtils.submitResponse({
            ordered_ids: orderedIds
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
    scene: TemplateSequencing,
    scale: {
        mode: Phaser.Scale.FIT,
        autoCenter: Phaser.Scale.CENTER_BOTH,
    },
};

new Phaser.Game(config);
