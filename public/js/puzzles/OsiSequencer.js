/**
 * OsiSequencer.js
 * Phaser 3 sequencing puzzle: arrange OSI layers in correct encapsulation order.
 *
 * Fixes applied (v2):
 *  - Phaser ScaleManager added (FIT + CENTER_BOTH) — fully responsive
 *  - submitToBackend delegated to PuzzleUtils (error handling, toasts, confetti)
 *  - Guard: cannot submit unless all 7 blocks are placed in slots
 *  - Duplicate-slot protection: each slot holds only one block
 *  - Loading spinner removed on scene create()
 */
class OsiSequencer extends Phaser.Scene {
    constructor() {
        super({ key: 'OsiSequencer' });
        this.slots  = [];
        this.blocks = [];
        // Correct top-to-bottom order for Encapsulation
        this.correctOrder = [
            'Application', 'Presentation', 'Session',
            'Transport', 'Network', 'Data Link', 'Physical',
        ];
    }

    create() {
        const { width, height } = this.scale;

        // Remove loading spinner now that the scene is ready
        document.getElementById('game-loader')?.remove();

        this.add.text(width / 2, 36, 'Arrange layers in correct Encapsulation order (Top → Bottom)', {
            font: '17px Arial',
            fill: '#1e293b',
            wordWrap: { width: width * 0.9 },
            align: 'center',
        }).setOrigin(0.5);

        // ── Target Slots (left column) ───────────────────────────────────
        for (let i = 0; i < 7; i++) {
            const slotY = 100 + i * 56;
            const slot  = this.add.zone(width * 0.30, slotY, 240, 46).setRectangleDropZone(240, 46);
            slot.setData('index', i);
            slot.setData('occupiedBy', null); // track which block is here

            const g = this.add.graphics();
            g.lineStyle(2, 0x94a3b8, 1);
            g.strokeRect(slot.x - 120, slot.y - 23, 240, 46);
            this.add.text(slot.x - 115, slot.y, `Layer ${7 - i}`, {
                font: '14px Arial',
                fill: '#64748b',
            }).setOrigin(0, 0.5);

            this.slots.push(slot);
        }

        // ── Draggable Blocks (right column, scrambled) ───────────────────
        const scrambled = Phaser.Utils.Array.Shuffle([...this.correctOrder]);
        scrambled.forEach((layerName, index) => {
            const blockX = width * 0.72;
            const blockY = 100 + index * 56;

            const block  = this.add.container(blockX, blockY);
            const bg     = this.add.rectangle(0, 0, 220, 42, 0x3b82f6);
            const label  = this.add.text(0, 0, layerName, {
                font: '15px Arial',
                fill: '#ffffff',
            }).setOrigin(0.5);

            block.add([bg, label]);
            block.setSize(220, 42);
            block.setInteractive({ useHandCursor: true });
            this.input.setDraggable(block);

            block.setData('layerName', layerName);
            block.setData('currentSlot', null);
            block.setData('homeX', blockX);
            block.setData('homeY', blockY);

            this.blocks.push(block);
        });

        // ── Drag & Drop Events ───────────────────────────────────────────
        this.input.on('dragstart', (pointer, gameObject) => {
            this.children.bringToTop(gameObject);
        });

        this.input.on('drag', (pointer, gameObject, dragX, dragY) => {
            gameObject.x = dragX;
            gameObject.y = dragY;
        });

        this.input.on('drop', (pointer, gameObject, dropZone) => {
            const slotIndex = dropZone.getData('index');

            // Evict any block already in this slot
            const occupiedBy = dropZone.getData('occupiedBy');
            if (occupiedBy !== null && occupiedBy !== gameObject) {
                occupiedBy.x = occupiedBy.getData('homeX');
                occupiedBy.y = occupiedBy.getData('homeY');
                occupiedBy.setData('currentSlot', null);
                dropZone.setData('occupiedBy', null);
            }

            // Clear the block's previous slot record
            const prevSlotIdx = gameObject.getData('currentSlot');
            if (prevSlotIdx !== null && this.slots[prevSlotIdx]) {
                this.slots[prevSlotIdx].setData('occupiedBy', null);
            }

            gameObject.x = dropZone.x;
            gameObject.y = dropZone.y;
            gameObject.setData('currentSlot', slotIndex);
            dropZone.setData('occupiedBy', gameObject);
        });

        this.input.on('dragend', (pointer, gameObject, dropped) => {
            if (!dropped) {
                // Clear the slot this block was occupying
                const prevSlotIdx = gameObject.getData('currentSlot');
                if (prevSlotIdx !== null && this.slots[prevSlotIdx]) {
                    this.slots[prevSlotIdx].setData('occupiedBy', null);
                }
                gameObject.x = gameObject.getData('homeX');
                gameObject.y = gameObject.getData('homeY');
                gameObject.setData('currentSlot', null);
            }
        });

        // ── Submit Button ────────────────────────────────────────────────
        const submitBtn = this.add.text(width / 2, height - 40, '🚀 Check Sequence', {
            font: '20px Arial',
            fill: '#ffffff',
            backgroundColor: '#10b981',
            padding: { x: 20, y: 10 },
        }).setOrigin(0.5).setInteractive({ useHandCursor: true });

        this._submitBtn = submitBtn;

        submitBtn.on('pointerover',  () => submitBtn.setTint(0xadffd8));
        submitBtn.on('pointerout',   () => submitBtn.clearTint());
        submitBtn.on('pointerdown',  () => { submitBtn.setScale(0.95); this.evaluateSequence(); });
        submitBtn.on('pointerup',    () => submitBtn.setScale(1));
    }

    // ── Evaluation ───────────────────────────────────────────────────────

    evaluateSequence() {
        // Guard: all 7 blocks must be placed
        const placedCount = this.blocks.filter(b => b.getData('currentSlot') !== null).length;
        if (placedCount < 7) {
            PuzzleUtils.showToast(
                `⚠️ Place all 7 layers before submitting. (${placedCount}/7 placed)`,
                'warning'
            );
            return;
        }

        // Build arrangement array
        const arrangement = new Array(7).fill(null);
        this.blocks.forEach(block => {
            const slotIdx = block.getData('currentSlot');
            if (slotIdx !== null) {
                arrangement[slotIdx] = block.getData('layerName');
            }
        });

        const isCorrect = arrangement.every((name, i) => name === this.correctOrder[i]);

        // Disable submit after first valid attempt
        if (this._submitBtn) {
            this._submitBtn.disableInteractive();
            this._submitBtn.setTint(0x888888);
        }

        PuzzleUtils.submitToBackend(isCorrect);
    }
}

// ── Phaser Config (responsive via ScaleManager) ──────────────────────────────
const config = {
    type: Phaser.AUTO,
    parent: 'phaser-game',
    width: 800,
    height: 520,
    backgroundColor: '#f8fafc',
    scene: OsiSequencer,
    scale: {
        mode: Phaser.Scale.FIT,
        autoCenter: Phaser.Scale.CENTER_BOTH,
    },
};

const game = new Phaser.Game(config);
