/**
 * DeviceMapper.js
 * Phaser 3 drag-and-drop puzzle: match networking devices to their OSI layers.
 *
 * Fixes applied (v2):
 *  - Phaser ScaleManager added (FIT + CENTER_BOTH) — fully responsive
 *  - submitToBackend delegated to PuzzleUtils (error handling, toasts, confetti)
 *  - Drop zone exclusivity: evicts previous occupant on new drop
 *  - Guard: cannot submit unless all devices are placed
 *  - Loading spinner removed on scene create()
 */
class DeviceMapper extends Phaser.Scene {
    constructor() {
        super({ key: 'DeviceMapper' });
        this.dropZones = {};
        this.devices   = {};
    }

    preload() {
        // Placeholder — swap with real assets when available
        // this.load.image('router', '/assets/router.png');
    }

    create() {
        const { width, height } = this.scale;

        // Remove loading spinner now that the scene is ready
        document.getElementById('game-loader')?.remove();

        // ── Drop Zones (OSI Layers) ──────────────────────────────────────
        this.createDropZone('Layer 3 — IP Routing',        width * 0.25, height * 0.70, 'router');
        this.createDropZone('Layer 2 — MAC Switching',     width * 0.50, height * 0.70, 'switch');
        this.createDropZone('Layer 1 — Electrical Signals',width * 0.75, height * 0.70, 'hub');

        // ── Draggable Devices ────────────────────────────────────────────
        this.createDraggableDevice('router', 'Router', width * 0.25, height * 0.20);
        this.createDraggableDevice('switch', 'Switch', width * 0.50, height * 0.20);
        this.createDraggableDevice('hub',    'Hub',    width * 0.75, height * 0.20);

        // ── Drag Events ──────────────────────────────────────────────────
        this.input.on('dragstart', (pointer, gameObject) => {
            this.children.bringToTop(gameObject);
            gameObject.setTint(0xdddddd);
        });

        this.input.on('drag', (pointer, gameObject, dragX, dragY) => {
            gameObject.x = dragX;
            gameObject.y = dragY;
        });

        this.input.on('drop', (pointer, gameObject, dropZone) => {
            const targetDevice = dropZone.getData('targetDevice');

            // Evict any device already in this zone
            const occupiedById = dropZone.getData('occupiedBy');
            if (occupiedById && occupiedById !== gameObject.getData('id')) {
                const evicted = this.devices[occupiedById];
                if (evicted) {
                    evicted.x = evicted.input.dragStartX;
                    evicted.y = evicted.input.dragStartY;
                    if (evicted._label) {
                        evicted._label.x = evicted.input.dragStartX;
                        evicted._label.y = evicted.input.dragStartY;
                    }
                    evicted.setData('currentZone', null);
                }
            }

            // Clear old zone occupation if device was previously placed
            const prevZone = gameObject.getData('currentZone');
            if (prevZone && this.dropZones[prevZone]) {
                this.dropZones[prevZone].setData('occupiedBy', null);
            }

            gameObject.x = dropZone.x;
            gameObject.y = dropZone.y;
            if (gameObject._label) {
                gameObject._label.x = dropZone.x;
                gameObject._label.y = dropZone.y;
            }
            gameObject.clearTint();
            gameObject.setData('currentZone', targetDevice);
            dropZone.setData('occupiedBy', gameObject.getData('id'));
        });

        this.input.on('dragend', (pointer, gameObject, dropped) => {
            if (!dropped) {
                // Clear old zone record
                const prevZone = gameObject.getData('currentZone');
                if (prevZone && this.dropZones[prevZone]) {
                    this.dropZones[prevZone].setData('occupiedBy', null);
                }
                gameObject.x = gameObject.input.dragStartX;
                gameObject.y = gameObject.input.dragStartY;
                if (gameObject._label) {
                    gameObject._label.x = gameObject.input.dragStartX;
                    gameObject._label.y = gameObject.input.dragStartY;
                }
                gameObject.setData('currentZone', null);
            }
            gameObject.clearTint();
        });

        // ── Submit Button ────────────────────────────────────────────────
        const submitBtn = this.add.text(width / 2, height * 0.90, '🚀 Submit Mapping', {
            font: '22px Arial',
            fill: '#ffffff',
            backgroundColor: '#2563eb',
            padding: { x: 20, y: 10 },
        }).setOrigin(0.5).setInteractive({ useHandCursor: true });

        // Mark for PuzzleUtils to disable on submit
        submitBtn.node = null; // Phaser text — handled via scene reference instead
        this._submitBtn = submitBtn;

        submitBtn.on('pointerover',  () => submitBtn.setTint(0xadd8ff));
        submitBtn.on('pointerout',   () => submitBtn.clearTint());
        submitBtn.on('pointerdown',  () => { submitBtn.setScale(0.95); this.evaluateAnswer(); });
        submitBtn.on('pointerup',    () => submitBtn.setScale(1));
    }

    // ── Scene Helpers ────────────────────────────────────────────────────

    createDropZone(label, x, y, targetDevice) {
        const zone = this.add.zone(x, y, 150, 150).setRectangleDropZone(150, 150);
        zone.setData('targetDevice', targetDevice);
        zone.setData('occupiedBy', null);

        const g = this.add.graphics();
        g.lineStyle(2, 0x3b82f6, 1);
        g.strokeRect(x - 75, y - 75, 150, 150);

        this.add.text(x, y - 90, label, {
            font: '14px Arial',
            fill: '#1e293b',
            align: 'center',
            wordWrap: { width: 140 },
        }).setOrigin(0.5);

        this.dropZones[targetDevice] = zone;
    }

    createDraggableDevice(id, name, x, y) {
        const device = this.add.rectangle(x, y, 110, 50, 0x10b981).setInteractive({ useHandCursor: true });
        const label  = this.add.text(x, y, name, { font: '16px Arial', fill: '#ffffff' }).setOrigin(0.5);

        device.setData('id', id);
        device.setData('currentZone', null);
        device._label = label;

        this.input.setDraggable(device);

        // Keep label pinned during drag
        device.on('drag', (pointer, dragX, dragY) => {
            label.x = dragX;
            label.y = dragY;
        });

        this.devices[id] = device;
    }

    // ── Evaluation ───────────────────────────────────────────────────────

    evaluateAnswer() {
        // Guard: all devices must be placed
        const unplaced = Object.values(this.devices).filter(d => d.getData('currentZone') === null);
        if (unplaced.length > 0) {
            PuzzleUtils.showToast(
                `⚠️ Place all ${Object.keys(this.devices).length} devices before submitting. (${Object.keys(this.devices).length - unplaced.length} placed)`,
                'warning'
            );
            return;
        }

        let isCorrect = true;
        for (const [id, device] of Object.entries(this.devices)) {
            if (device.getData('currentZone') !== id) {
                isCorrect = false;
                break;
            }
        }

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
    height: 500,
    backgroundColor: '#eff6ff',
    scene: DeviceMapper,
    scale: {
        mode: Phaser.Scale.FIT,
        autoCenter: Phaser.Scale.CENTER_BOTH,
    },
};

const game = new Phaser.Game(config);
