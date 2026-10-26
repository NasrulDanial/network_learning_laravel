/**
 * DeviceMapper.js
 * Phaser 3 Puzzle Archetype 5: Spatial Drag & Drop Device Placement.
 *
 * Data-driven: Reads window.activityData (draggables & drop_zones)
 * Server-authoritative: Submits { placements: { [dragId]: dropZoneId } }
 */
class DeviceMapper extends Phaser.Scene {
    constructor() {
        super({ key: 'DeviceMapper' });
        this.dropZones = {};
        this.devices   = {};
        this.placements = {};
    }

    create() {
        const { width, height } = this.scale;
        document.getElementById('game-loader')?.remove();

        const data = window.activityData || {};
        const dropZonesData = data.drop_zones && data.drop_zones.length > 0
            ? data.drop_zones
            : [
                { id: 'zone_1', label: 'Layer 3 — IP Routing' },
                { id: 'zone_2', label: 'Layer 2 — MAC Switching' },
                { id: 'zone_3', label: 'Layer 1 — Physical Bits' }
            ];

        const draggablesData = data.draggables && data.draggables.length > 0
            ? data.draggables
            : [
                { id: 'drag_1', label: 'Router' },
                { id: 'drag_2', label: 'Switch' },
                { id: 'drag_3', label: 'Hub' }
            ];

        this.totalDevices = draggablesData.length;

        // Title
        this.add.text(width / 2, 30, window.puzzleData?.question_text || 'Spatial Network Device Placement', {
            font: 'bold 18px "Inter", sans-serif',
            fill: '#ffffff'
        }).setOrigin(0.5);

        this.add.text(width / 2, 58, 'Drag devices from the staging bay above into the matching architecture zones below.', {
            font: '12px "Inter", sans-serif',
            fill: '#94a3b8'
        }).setOrigin(0.5);

        // ── Drop Zones (Bottom Grid) ─────────────────────────────────────
        const numZones = dropZonesData.length;
        const zoneWidth = Math.min(180, (width * 0.9) / numZones - 20);
        const zoneHeight = 150;
        const zoneY = height * 0.65;
        const totalZWidth = numZones * zoneWidth + (numZones - 1) * 20;
        const startZX = (width - totalZWidth) / 2 + (zoneWidth / 2);

        dropZonesData.forEach((zd, idx) => {
            const zx = startZX + idx * (zoneWidth + 20);
            const zone = this.add.zone(zx, zoneY, zoneWidth, zoneHeight).setRectangleDropZone(zoneWidth, zoneHeight);
            zone.setData('zoneId', zd.id);
            zone.setData('occupiedBy', null);

            const g = this.add.graphics();
            g.fillStyle(0x0c1e36, 0.75);
            g.fillRect(zx - (zoneWidth / 2), zoneY - (zoneHeight / 2), zoneWidth, zoneHeight);
            g.lineStyle(2, 0x0284c7, 0.8);
            g.strokeRect(zx - (zoneWidth / 2), zoneY - (zoneHeight / 2), zoneWidth, zoneHeight);

            this.add.text(zx, zoneY - (zoneHeight / 2) + 18, zd.label, {
                font: 'bold 12px "Inter", sans-serif',
                fill: '#38bdf8',
                align: 'center',
                wordWrap: { width: zoneWidth - 14 }
            }).setOrigin(0.5);

            this.dropZones[zd.id] = zone;
        });

        // ── Draggable Devices (Top Staging Bay) ───────────────────────────
        const numDrags = draggablesData.length;
        const dragWidth = 140;
        const dragHeight = 44;
        const totalDWidth = numDrags * dragWidth + (numDrags - 1) * 16;
        const startDX = (width - totalDWidth) / 2 + (dragWidth / 2);

        draggablesData.forEach((dd, idx) => {
            const dx = startDX + idx * (dragWidth + 16);
            const dy = 110;

            const container = this.add.container(dx, dy);
            const bg = this.add.rectangle(0, 0, dragWidth, dragHeight, 0x1e293b)
                .setStrokeStyle(1, 0x22d3ee);

            const label = this.add.text(0, 0, dd.label, {
                font: 'bold 13px "Inter", sans-serif',
                fill: '#ffffff',
                align: 'center',
                wordWrap: { width: dragWidth - 12 }
            }).setOrigin(0.5);

            container.add([bg, label]);
            container.setSize(dragWidth, dragHeight);
            container.setInteractive({ useHandCursor: true });
            this.input.setDraggable(container);

            container.setData('deviceId', dd.id);
            container.setData('currentZone', null);
            container.setData('homeX', dx);
            container.setData('homeY', dy);
            container._bg = bg;

            this.devices[dd.id] = container;
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
            const deviceId = gameObject.getData('deviceId');
            const targetZoneId = dropZone.getData('zoneId');

            // Evict occupant if occupied
            const occupiedById = dropZone.getData('occupiedBy');
            if (occupiedById && occupiedById !== deviceId) {
                const evicted = this.devices[occupiedById];
                if (evicted) {
                    evicted.x = evicted.getData('homeX');
                    evicted.y = evicted.getData('homeY');
                    evicted.setData('currentZone', null);
                    delete this.placements[occupiedById];
                    evicted._bg.setFillStyle(0x1e293b);
                    evicted._bg.setStrokeStyle(1, 0x22d3ee);
                }
            }

            // Clear previous zone occupiedBy
            const prevZone = gameObject.getData('currentZone');
            if (prevZone && this.dropZones[prevZone]) {
                this.dropZones[prevZone].setData('occupiedBy', null);
            }

            gameObject.x = dropZone.x;
            gameObject.y = dropZone.y + 15;
            gameObject.setData('currentZone', targetZoneId);
            dropZone.setData('occupiedBy', deviceId);
            this.placements[deviceId] = targetZoneId;

            gameObject._bg.setFillStyle(0x0a3c5a);
            gameObject._bg.setStrokeStyle(2, 0x38bdf8);
        });

        this.input.on('dragend', (pointer, gameObject, dropped) => {
            if (!dropped) {
                const deviceId = gameObject.getData('deviceId');
                const prevZone = gameObject.getData('currentZone');
                if (prevZone && this.dropZones[prevZone]) {
                    this.dropZones[prevZone].setData('occupiedBy', null);
                }
                gameObject.x = gameObject.getData('homeX');
                gameObject.y = gameObject.getData('homeY');
                gameObject.setData('currentZone', null);
                delete this.placements[deviceId];

                gameObject._bg.setFillStyle(0x1e293b);
                gameObject._bg.setStrokeStyle(1, 0x22d3ee);
            }
        });

        // ── Submit Button ────────────────────────────────────────────────
        const submitBtn = this.add.container(width / 2, height - 35);
        const btnBg = this.add.rectangle(0, 0, 240, 42, 0x0284c7)
            .setStrokeStyle(1, 0x38bdf8)
            .setInteractive({ useHandCursor: true });

        const btnLabel = this.add.text(0, 0, '🚀 DEPLOY TOPOLOGY', {
            font: 'bold 13px "Inter", sans-serif',
            fill: '#ffffff'
        }).setOrigin(0.5);

        submitBtn.add([btnBg, btnLabel]);

        btnBg.on('pointerover', () => btnBg.setFillStyle(0x0ea5e9));
        btnBg.on('pointerout',  () => btnBg.setFillStyle(0x0284c7));
        btnBg.on('pointerdown', () => this.handleSubmit());

        this._submitBtnBg = btnBg;
    }

    handleSubmit() {
        const placedCount = Object.keys(this.placements).length;
        if (placedCount < this.totalDevices) {
            PuzzleUtils.showToast(`⚠️ Please place all ${this.totalDevices} devices into zones. (${placedCount}/${this.totalDevices} placed)`, 'warning');
            return;
        }

        if (this._submitBtnBg) {
            this._submitBtnBg.disableInteractive();
            this._submitBtnBg.setFillStyle(0x334155);
        }

        PuzzleUtils.submitResponse({
            placements: this.placements
        });
    }
}

const config = {
    type: Phaser.AUTO,
    parent: 'phaser-game',
    width: 800,
    height: 500,
    backgroundColor: '#071426',
    scene: DeviceMapper,
    scale: {
        mode: Phaser.Scale.FIT,
        autoCenter: Phaser.Scale.CENTER_BOTH,
    },
};

new Phaser.Game(config);
