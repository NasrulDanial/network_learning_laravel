/**
 * TemplateClassification.js
 * Phaser 3 Puzzle Archetype 4: Multi-Bin Categorization & Sorting.
 */
class TemplateClassification extends Phaser.Scene {
    constructor() {
        super({ key: 'TemplateClassification' });
        this.bins = [];
        this.itemTokens = [];
        this.placements = {}; // itemId -> categoryId
    }

    create() {
        const { width, height } = this.scale;
        document.getElementById('game-loader')?.remove();

        const data = window.activityData || {};
        const categories = data.categories || [
            { id: 'cat_1', label: 'Layer 2 - Data Link' },
            { id: 'cat_2', label: 'Layer 3 - Network' },
            { id: 'cat_3', label: 'Layer 4 - Transport' }
        ];

        const items = data.items || [
            { id: 'item_1', label: 'Switch' },
            { id: 'item_2', label: 'Router' },
            { id: 'item_3', label: 'TCP Segment' },
            { id: 'item_4', label: 'MAC Frame' }
        ];

        this.totalItems = items.length;

        // Title
        this.add.text(width / 2, 28, window.puzzleData?.question_text || 'Categorize Protocols & Networking Hardware', {
            font: 'bold 18px "Inter", sans-serif',
            fill: '#ffffff'
        }).setOrigin(0.5);

        this.add.text(width / 2, 52, 'Drag each item into its correct operational category bucket below.', {
            font: '12px "Inter", sans-serif',
            fill: '#94a3b8'
        }).setOrigin(0.5);

        // ── Category Bins (Bottom Area) ──────────────────────────────────
        const binCount = categories.length;
        const binWidth = Math.min(220, (width * 0.92) / binCount - 16);
        const binHeight = 160;
        const binY = height * 0.65;
        const totalBinsWidth = binCount * binWidth + (binCount - 1) * 16;
        const binStartX = (width - totalBinsWidth) / 2 + (binWidth / 2);

        categories.forEach((cat, idx) => {
            const bx = binStartX + idx * (binWidth + 16);
            const zone = this.add.zone(bx, binY, binWidth, binHeight).setRectangleDropZone(binWidth, binHeight);
            zone.setData('categoryId', cat.id);
            zone.setData('binWidth', binWidth);
            zone.setData('binHeight', binHeight);
            this.binZones = this.binZones || {};
            this.binZones[cat.id] = zone;

            const g = this.add.graphics();
            zone._g = g;
            this.paintBin(zone, 0x10b981, 0.7);

            this.add.text(bx, binY - (binHeight / 2) + 18, cat.label, {
                font: 'bold 12px "Inter", sans-serif',
                fill: '#34d399',
                align: 'center',
                wordWrap: { width: binWidth - 14 }
            }).setOrigin(0.5);

            this.bins.push(zone);
        });

        // ── Item Tokens (Top Staging Grid) ───────────────────────────────
        const cols = Math.min(items.length, 4);
        const tokenWidth = 160;
        const tokenHeight = 44;
        const gridStartX = (width - (cols * (tokenWidth + 12))) / 2 + (tokenWidth / 2);

        items.forEach((item, idx) => {
            const col = idx % cols;
            const row = Math.floor(idx / cols);
            const tx = gridStartX + col * (tokenWidth + 12);
            const ty = 95 + row * 52;

            const container = this.add.container(tx, ty);
            const bg = this.add.rectangle(0, 0, tokenWidth, tokenHeight, 0x1e293b)
                .setStrokeStyle(1, 0x38bdf8);

            const txt = this.add.text(0, 0, item.label, {
                font: '12px "Inter", sans-serif',
                fill: '#e2e8f0',
                align: 'center',
                wordWrap: { width: tokenWidth - 16 }
            }).setOrigin(0.5);

            container.add([bg, txt]);
            container.setSize(tokenWidth, tokenHeight);
            container.setInteractive({ useHandCursor: true });
            this.input.setDraggable(container);

            container.setData('itemId', item.id);
            container.setData('homeX', tx);
            container.setData('homeY', ty);
            container._bg = bg;

            this.itemTokens.push(container);
        });

        // ── Drag & Drop Events ───────────────────────────────────────────
        this.input.on('dragstart', (pointer, gameObject) => {
            if (this._executing) return;
            this.clearMarks();
            if (window.NetFX) NetFX.sound('click');
            this.children.bringToTop(gameObject);
            gameObject.setScale(1);
            gameObject._bg.setFillStyle(0x0f2a4a);
            gameObject._bg.setStrokeStyle(2, 0x22d3ee);
        });

        this.input.on('drag', (pointer, gameObject, dragX, dragY) => {
            if (this._executing) return;
            gameObject.x = dragX;
            gameObject.y = dragY;
        });

        this.input.on('drop', (pointer, gameObject, dropZone) => {
            if (this._executing) return;
            const itemId = gameObject.getData('itemId');
            const catId = dropZone.getData('categoryId');

            const previousCat = this.placements[itemId];
            this.placements[itemId] = catId;
            gameObject._bg.setFillStyle(0x064e3b);
            gameObject._bg.setStrokeStyle(1, 0x34d399);
            // Lay placed items out in a tidy grid so every one stays readable
            this.layoutBin(catId);
            if (previousCat && previousCat !== catId) this.layoutBin(previousCat);
        });

        this.input.on('dragend', (pointer, gameObject, dropped) => {
            if (!dropped) {
                const itemId = gameObject.getData('itemId');
                const previousCat = this.placements[itemId];
                delete this.placements[itemId];
                gameObject.setScale(1);
                gameObject.x = gameObject.getData('homeX');
                gameObject.y = gameObject.getData('homeY');
                if (previousCat) this.layoutBin(previousCat);
                gameObject._bg.setFillStyle(0x1e293b);
                gameObject._bg.setStrokeStyle(1, 0x38bdf8);
            }
        });

        // ── Submit Button ────────────────────────────────────────────────
        const submitBtn = this.add.container(width / 2, height - 35);
        const btnBg = this.add.rectangle(0, 0, 230, 40, 0x059669)
            .setStrokeStyle(1, 0x34d399)
            .setInteractive({ useHandCursor: true });

        const btnLabel = this.add.text(0, 0, '📂 SUBMIT CLASSIFICATION', {
            font: 'bold 13px "Inter", sans-serif',
            fill: '#ffffff'
        }).setOrigin(0.5);

        submitBtn.add([btnBg, btnLabel]);

        btnBg.on('pointerover', () => btnBg.setFillStyle(0x10b981));
        btnBg.on('pointerout',  () => btnBg.setFillStyle(0x059669));
        btnBg.on('pointerdown', () => this.handleSubmit());

        this._submitBtnBg = btnBg;

        PuzzleUtils.ready();
    }

    /** Arrange every token placed in a bin as a compact grid below the bin title. */
    layoutBin(catId) {
        const zone = this.binZones && this.binZones[catId];
        if (!zone) return;

        const gap = 6;
        const binW = zone.getData('binWidth');
        const binH = zone.getData('binHeight');
        const top = zone.y - binH / 2 + 38;
        const tokens = this.itemTokens.filter(t => this.placements[t.getData('itemId')] === catId);

        // Shrink tokens when a bin gets crowded so they always stay inside it
        let scale = 0.62, tokenW, tokenH, cols;
        for (; scale >= 0.4; scale -= 0.04) {
            tokenW = 160 * scale;
            tokenH = 44 * scale;
            cols = Math.max(1, Math.floor((binW - gap) / (tokenW + gap)));
            const rows = Math.ceil(tokens.length / cols);
            if (rows * (tokenH + gap) <= binH - 44) break;
        }
        tokens.forEach((token, i) => {
            const col = i % cols;
            const row = Math.floor(i / cols);
            const rowWidth = Math.min(cols, tokens.length - row * cols) * (tokenW + gap) - gap;
            token.setScale(scale);
            token.x = zone.x - rowWidth / 2 + tokenW / 2 + col * (tokenW + gap);
            token.y = top + tokenH / 2 + row * (tokenH + gap);
        });
    }

    paintBin(zone, color, alpha = 0.7, fill = 0x0c1e36) {
        const w = zone.getData('binWidth');
        const h = zone.getData('binHeight');
        zone._g.clear();
        zone._g.fillStyle(fill, 0.7);
        zone._g.fillRect(zone.x - w / 2, zone.y - h / 2, w, h);
        zone._g.lineStyle(2, color, alpha);
        zone._g.strokeRect(zone.x - w / 2, zone.y - h / 2, w, h);
    }

    clearMarks() {
        Object.values(this.binZones || {}).forEach((z) => {
            this.paintBin(z, 0x10b981, 0.7);
            if (z._count) { z._count.destroy(); z._count = null; }
        });
        if (this._misrouted) { this._misrouted.destroy(); this._misrouted = null; }
        this.itemTokens.forEach((t) => {
            if (this.placements[t.getData('itemId')]) t._bg.setFillStyle(0x064e3b).setStrokeStyle(1, 0x34d399);
        });
    }

    setLocked(locked) {
        this._executing = locked;
        this.itemTokens.forEach((t) => (locked ? t.disableInteractive() : t.setInteractive({ useHandCursor: true })));
    }

    handleSubmit() {
        const placedCount = Object.keys(this.placements).length;
        if (placedCount < this.totalItems) {
            PuzzleUtils.showToast(`⚠️ Sort all ${this.totalItems} items first. (${placedCount}/${this.totalItems} placed)`, 'warning');
            return;
        }
        if (this._isSubmitting) return;
        this._isSubmitting = true;
        this._submitBtnBg.disableInteractive().setFillStyle(0x334155);
        this.setLocked(true);

        const unlock = () => {
            this._isSubmitting = false;
            this.setLocked(false);
            this._submitBtnBg.setInteractive({ useHandCursor: true }).setFillStyle(0x059669);
        };

        PuzzleUtils.submitResponse({ placements: this.placements }, {
            onResult: (data, ok) => this.execute(data, ok),
            onSuccess: () => this._submitBtnBg.setFillStyle(0x059669),
            onRetry: unlock,
            onError: unlock,
        });
    }

    /** Scan the sorted items one by one; reject the first misrouted item or light every bin. */
    async execute(data, ok) {
        const fx = PuzzleUtils.fx;
        const restoreSpeed = fx.enableSpeedUp(this);
        this.clearMarks();
        const failId = ok ? null : (data && data.diagnostics ? data.diagnostics.first_error : null);
        // Natural id order (item_1, item_2…) mirrors the authored order the server checks in
        const order = [...this.itemTokens].sort((a, b) => String(a.getData('itemId')).localeCompare(String(b.getData('itemId')), undefined, { numeric: true }));

        try {
            for (const token of order) {
                const id = token.getData('itemId');
                if (window.NetFX) NetFX.sound('tick');
                await new Promise((r) => this.tweens.add({ targets: token, scaleX: token.scaleX * 1.12, scaleY: token.scaleY * 1.12, yoyo: true, duration: fx.reduced() ? 1 : 110, onComplete: r }));

                if (id === failId) {
                    token._bg.setFillStyle(0x3b0d12).setStrokeStyle(2, 0xef4444);
                    const homeY = token.y;
                    await new Promise((r) => this.tweens.add({ targets: token, y: homeY - 26, duration: fx.reduced() ? 1 : 160, yoyo: true, ease: 'Quad.easeOut', onComplete: r }));
                    fx.shake(this);
                    if (window.NetFX) NetFX.sound('drop');
                    this._misrouted = this.add.text(token.x, token.y - 22, 'Misrouted', {
                        font: 'bold 11px "Inter", sans-serif', fill: '#fecaca', backgroundColor: '#7f1d1d', padding: { x: 6, y: 2 },
                    }).setOrigin(0.5).setDepth(30);
                    return;
                }
                // Scanned: neutral highlight only (other items' correctness is not revealed)
                token._bg.setStrokeStyle(2, ok ? 0x22c55e : 0x22d3ee);
                await fx.wait(this, 40);
            }

            if (ok) {
                Object.values(this.binZones).forEach((z) => {
                    this.paintBin(z, 0x22c55e, 1, 0x052e1a);
                    const count = Object.values(this.placements).filter((c) => c === z.getData('categoryId')).length;
                    z._count = this.add.text(z.x + z.getData('binWidth') / 2 - 8, z.y - z.getData('binHeight') / 2 + 8, `✓ ${count}`, {
                        font: 'bold 12px "Inter", sans-serif', fill: '#4ade80',
                    }).setOrigin(1, 0).setDepth(30);
                    fx.burst(this, z.x, z.y, 0x22c55e, 12);
                });
                if (window.NetFX) NetFX.sound('linkup');
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
    scene: TemplateClassification,
    scale: {
        mode: Phaser.Scale.FIT,
        autoCenter: Phaser.Scale.CENTER_BOTH,
    },
};

// Exposed for debugging and automated QA (inspect scene state, simulate placements)
window.netlearnGame = new Phaser.Game(config);
