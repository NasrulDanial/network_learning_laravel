/**
 * DeviceMapper.js
 * Phaser 3 Puzzle Archetype 5: Drag & Drop device placement on a network path.
 *
 * Data-driven: reads window.activityData (draggables & drop_zones).
 * Server-authoritative: submits { placements: { [dragId]: dropZoneId } }.
 * The answer is then "executed": a ping packet travels PC → zones → server and is
 * dropped at the first zone the server reports as wrong (diagnostics.first_error).
 */
class DeviceMapper extends Phaser.Scene {
    constructor() {
        super({ key: 'DeviceMapper' });
        this.dropZones = {};
        this.zoneOrder = [];
        this.devices = {};
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
                { id: 'zone_3', label: 'Layer 1 — Physical Bits' },
            ];
        const draggablesData = data.draggables && data.draggables.length > 0
            ? data.draggables
            : [
                { id: 'drag_1', label: 'Router' },
                { id: 'drag_2', label: 'Switch' },
                { id: 'drag_3', label: 'Hub' },
            ];

        this.totalDevices = draggablesData.length;
        this.zonesData = dropZonesData;

        this.add.text(width / 2, 24, window.puzzleData?.question_text || 'Network Device Placement', {
            font: 'bold 17px "Inter", sans-serif', fill: '#ffffff',
        }).setOrigin(0.5);
        this.add.text(width / 2, 46, 'Drag each device onto its slot on the network path, then run the connectivity test.', {
            font: '12px "Inter", sans-serif', fill: '#94a3b8',
        }).setOrigin(0.5);

        // ── Network path: PC ── zones ── Server ──────────────────────────
        const pathY = 222;
        const numZones = dropZonesData.length;
        const left = 130;
        const right = width - 130;
        const gap = 16;
        const zoneWidth = Math.min(150, ((right - left) - (numZones - 1) * gap) / Math.max(1, numZones));
        const zoneHeight = 112;
        const totalZWidth = numZones * zoneWidth + (numZones - 1) * gap;
        const startZX = (width - totalZWidth) / 2 + zoneWidth / 2;

        this.pcPos = { x: 62, y: pathY };
        this.serverPos = { x: width - 62, y: pathY };

        this.cables = this.add.graphics().setDepth(1);
        this.zoneCenters = dropZonesData.map((zd, idx) => ({ x: startZX + idx * (zoneWidth + gap), y: pathY }));
        this.drawCables(0x1e3a5f);

        this.drawPc(this.pcPos.x, this.pcPos.y);
        this.drawServer(this.serverPos.x, this.serverPos.y);

        dropZonesData.forEach((zd, idx) => {
            const { x: zx } = this.zoneCenters[idx];
            const zone = this.add.zone(zx, pathY, zoneWidth, zoneHeight).setRectangleDropZone(zoneWidth, zoneHeight);
            zone.setData('zoneId', zd.id);
            zone.setData('occupiedBy', null);

            const g = this.add.graphics().setDepth(2);
            zone._frame = g;
            zone._w = zoneWidth;
            zone._h = zoneHeight;
            this.paintZone(zone, 0x0284c7);

            this.add.text(zx, pathY - zoneHeight / 2 + 16, zd.label, {
                font: 'bold 11px "Inter", sans-serif', fill: '#38bdf8', align: 'center',
                wordWrap: { width: zoneWidth - 12 },
            }).setOrigin(0.5).setDepth(3);

            zone._label = zd.label;
            this.dropZones[zd.id] = zone;
            this.zoneOrder.push(zd.id);
        });

        // ── Staging bay ─────────────────────────────────────────────────
        const dragWidth = Math.min(140, Math.max(96, zoneWidth - 10));
        const dragHeight = 38;
        const totalDWidth = draggablesData.length * dragWidth + (draggablesData.length - 1) * 14;
        const startDX = (width - totalDWidth) / 2 + dragWidth / 2;

        draggablesData.forEach((dd, idx) => {
            const dx = startDX + idx * (dragWidth + 14);
            const dy = 96;

            const container = this.add.container(dx, dy - 40).setDepth(10).setAlpha(0);
            const bg = this.add.rectangle(0, 0, dragWidth, dragHeight, 0x1e293b).setStrokeStyle(1, 0x22d3ee);
            const label = this.add.text(0, 0, dd.label, {
                font: 'bold 12px "Inter", sans-serif', fill: '#ffffff', align: 'center',
                wordWrap: { width: dragWidth - 10 },
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

            // Entry: devices drop into the bay one after another
            this.tweens.add({ targets: container, y: dy, alpha: 1, duration: 320, delay: idx * 70, ease: 'Back.easeOut' });
        });

        // ── Drag events ─────────────────────────────────────────────────
        this.input.on('dragstart', (pointer, gameObject) => {
            if (this._executing) return;
            this.children.bringToTop(gameObject);
            gameObject.setScale(1.08);
            gameObject._bg.setFillStyle(0x0f2a4a);
            gameObject._bg.setStrokeStyle(2, 0x22d3ee);
            this.clearZoneMarks();
            if (window.NetFX) NetFX.sound('click');
        });

        this.input.on('drag', (pointer, gameObject, dragX, dragY) => {
            if (this._executing) return;
            gameObject.x = dragX;
            gameObject.y = dragY;
        });

        this.input.on('drop', (pointer, gameObject, dropZone) => {
            if (this._executing) return;
            const deviceId = gameObject.getData('deviceId');
            const targetZoneId = dropZone.getData('zoneId');

            // One device per slot: the previous occupant returns to the bay
            const occupiedById = dropZone.getData('occupiedBy');
            if (occupiedById && occupiedById !== deviceId) {
                this.sendHome(this.devices[occupiedById]);
            }

            const prevZone = gameObject.getData('currentZone');
            if (prevZone && this.dropZones[prevZone]) this.dropZones[prevZone].setData('occupiedBy', null);

            gameObject.setScale(1);
            gameObject.x = dropZone.x;
            gameObject.y = dropZone.y + 16;
            gameObject.setData('currentZone', targetZoneId);
            dropZone.setData('occupiedBy', deviceId);
            this.placements[deviceId] = targetZoneId;

            gameObject._bg.setFillStyle(0x0a3c5a);
            gameObject._bg.setStrokeStyle(2, 0x38bdf8);
        });

        this.input.on('dragend', (pointer, gameObject, dropped) => {
            gameObject.setScale(1);
            if (!dropped) this.sendHome(gameObject);
        });

        // ── Submit ──────────────────────────────────────────────────────
        const submitBtn = this.add.container(width / 2, height - 30).setDepth(20);
        const btnBg = this.add.rectangle(0, 0, 250, 40, 0x0284c7).setStrokeStyle(1, 0x38bdf8).setInteractive({ useHandCursor: true });
        const btnLabel = this.add.text(0, 0, '▶ RUN CONNECTIVITY TEST', { font: 'bold 13px "Inter", sans-serif', fill: '#ffffff' }).setOrigin(0.5);
        submitBtn.add([btnBg, btnLabel]);
        btnBg.on('pointerover', () => btnBg.setFillStyle(0x0ea5e9));
        btnBg.on('pointerout', () => btnBg.setFillStyle(this._isSubmitting ? 0x334155 : 0x0284c7));
        btnBg.on('pointerdown', () => this.handleSubmit());
        this._submitBtnBg = btnBg;

        PuzzleUtils.ready();
    }

    // ── Drawing helpers ─────────────────────────────────────────────────
    drawCables(color) {
        const pts = [this.pcPos, ...this.zoneCenters, this.serverPos];
        this.cables.clear();
        this.cables.lineStyle(3, color, 1);
        for (let i = 0; i < pts.length - 1; i++) {
            this.cables.lineBetween(pts[i].x, pts[i].y, pts[i + 1].x, pts[i + 1].y);
        }
    }

    paintZone(zone, color, fill = 0x0c1e36) {
        const g = zone._frame;
        g.clear();
        g.fillStyle(fill, 0.85);
        g.fillRoundedRect(zone.x - zone._w / 2, zone.y - zone._h / 2, zone._w, zone._h, 10);
        g.lineStyle(2, color, 0.9);
        g.strokeRoundedRect(zone.x - zone._w / 2, zone.y - zone._h / 2, zone._w, zone._h, 10);
    }

    clearZoneMarks() {
        Object.values(this.dropZones).forEach((z) => this.paintZone(z, 0x0284c7));
    }

    drawPc(x, y) {
        const g = this.add.graphics().setDepth(3);
        g.fillStyle(0x0f172a, 1).fillRoundedRect(x - 26, y - 24, 52, 36, 4);
        g.lineStyle(2, 0x38bdf8, 1).strokeRoundedRect(x - 26, y - 24, 52, 36, 4);
        g.fillStyle(0x0ea5e9, 0.35).fillRect(x - 21, y - 19, 42, 26);
        g.fillStyle(0x38bdf8, 1).fillRect(x - 4, y + 12, 8, 7).fillRect(x - 14, y + 19, 28, 3);
        this.add.text(x, y + 34, 'PC-A', { font: 'bold 11px "Inter", sans-serif', fill: '#94a3b8' }).setOrigin(0.5).setDepth(3);
    }

    drawServer(x, y) {
        const g = this.add.graphics().setDepth(3);
        [-22, -6, 10].forEach((dy) => {
            g.fillStyle(0x0f172a, 1).fillRoundedRect(x - 24, y + dy, 48, 14, 3);
            g.lineStyle(1.5, 0x38bdf8, 1).strokeRoundedRect(x - 24, y + dy, 48, 14, 3);
            g.fillStyle(0x22c55e, 1).fillCircle(x + 16, y + dy + 7, 2.5);
        });
        this.serverGfx = g;
        this.add.text(x, y + 38, 'SRV-1', { font: 'bold 11px "Inter", sans-serif', fill: '#94a3b8' }).setOrigin(0.5).setDepth(3);
    }

    sendHome(device) {
        if (!device) return;
        const id = device.getData('deviceId');
        const prevZone = device.getData('currentZone');
        if (prevZone && this.dropZones[prevZone] && this.dropZones[prevZone].getData('occupiedBy') === id) {
            this.dropZones[prevZone].setData('occupiedBy', null);
        }
        device.setScale(1);
        device.x = device.getData('homeX');
        device.y = device.getData('homeY');
        device.setData('currentZone', null);
        delete this.placements[id];
        device._bg.setFillStyle(0x1e293b);
        device._bg.setStrokeStyle(1, 0x22d3ee);
    }

    setLocked(locked) {
        this._executing = locked;
        Object.values(this.devices).forEach((d) => (locked ? d.disableInteractive() : d.setInteractive({ useHandCursor: true })));
    }

    // ── Submit & execute ────────────────────────────────────────────────
    handleSubmit() {
        const placedCount = Object.keys(this.placements).length;
        if (placedCount < this.totalDevices) {
            PuzzleUtils.showToast(`⚠️ Place all ${this.totalDevices} devices first. (${placedCount}/${this.totalDevices} placed)`, 'warning');
            return;
        }
        if (this._isSubmitting) return;
        this._isSubmitting = true;
        this._submitBtnBg.disableInteractive().setFillStyle(0x334155);
        this.setLocked(true);

        const unlock = () => {
            this._isSubmitting = false;
            this.setLocked(false);
            this._submitBtnBg.setInteractive({ useHandCursor: true }).setFillStyle(0x0284c7);
        };

        PuzzleUtils.submitResponse({ placements: this.placements }, {
            onResult: (data, ok) => this.execute(data, ok),
            onSuccess: () => this._submitBtnBg.setFillStyle(0x059669),
            onRetry: unlock,
            onError: unlock,
        });
    }

    async execute(data, ok) {
        const fx = PuzzleUtils.fx;
        const restoreSpeed = fx.enableSpeedUp(this);
        this.clearZoneMarks();
        this.drawCables(0x1e3a5f);

        if (this.term) this.term.destroy();
        this.term = PuzzleUtils.terminal(this, 20, 300, this.scale.width - 40, 122);

        try {
            await this.term.log('C:\\> ping 192.168.1.10', '#e2e8f0');

            const points = [this.pcPos, ...this.zoneCenters, this.serverPos];
            let stopAt = null;
            if (!ok) {
                const failedZone = data && data.diagnostics ? data.diagnostics.first_error : null;
                const zoneIndex = this.zoneOrder.indexOf(failedZone);
                // points[0] is the PC, so zone i sits at index i + 1
                stopAt = zoneIndex >= 0 ? zoneIndex + 1 : this.zoneCenters.length;
            }

            const pkt = fx.packet(this, this.pcPos.x, this.pcPos.y, ok ? 0x22d3ee : 0xf59e0b);
            const reached = await fx.runPath(this, pkt, points, { stepMs: 320, stopAt });

            if (ok) {
                pkt.destroy();
                this.drawCables(0x22c55e);
                Object.values(this.dropZones).forEach((z) => this.paintZone(z, 0x22c55e, 0x052e1a));
                if (window.NetFX) NetFX.sound('linkup');
                fx.burst(this, this.serverPos.x, this.serverPos.y, 0x22c55e, 30);
                for (let i = 1; i <= 4; i++) {
                    await this.term.log(`Reply from 192.168.1.10: bytes=32 time=${2 + i}ms TTL=64`, '#a7f3d0', { instant: true });
                    await fx.wait(this, 110);
                }
                await this.term.log('Ping statistics: Sent = 4, Received = 4, Lost = 0 (0% loss)', '#4ade80');
            } else {
                const zoneId = this.zoneOrder[reached - 1];
                const zone = this.dropZones[zoneId];
                const at = points[reached] || this.serverPos;
                fx.burst(this, at.x, at.y, 0xef4444, 26);
                fx.shake(this);
                if (window.NetFX) NetFX.sound('drop');
                pkt.destroy();
                if (zone) {
                    for (let i = 0; i < 3; i++) {
                        this.paintZone(zone, 0xef4444, 0x3b0d12);
                        await fx.wait(this, 120);
                        this.paintZone(zone, 0x0284c7);
                        await fx.wait(this, 90);
                    }
                    // Keep the failed slot outlined until the student moves a device
                    this.paintZone(zone, 0xef4444, 0x3b0d12);
                }
                await this.term.log('Request timed out.', '#fca5a5', { instant: true });
                await fx.wait(this, 140);
                await this.term.log('Request timed out.', '#fca5a5', { instant: true });
                await this.term.log(zone ? `Packet dropped at: ${zone._label}` : 'Destination host unreachable.', '#fca5a5');
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
    scene: DeviceMapper,
    scale: {
        mode: Phaser.Scale.FIT,
        autoCenter: Phaser.Scale.CENTER_BOTH,
    },
};

// Exposed for debugging and automated QA (inspect scene state, simulate placements)
window.netlearnGame = new Phaser.Game(config);
