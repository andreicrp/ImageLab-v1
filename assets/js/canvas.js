/**
 * ImageLab Fabric.js Canvas Controller (Phase 3)
 * Orchestrates direct canvas object transformations, zoom, overlays, and crops.
 */
const ImageLabCanvas = {
    canvas: null,
    activeImage: null,
    cropRect: null,
    overlayGuides: [],
    zoomLevel: 1.0,
    aspectRatioLocked: true,

    /**
     * Initialize Fabric.js Canvas to fill workspace container
     */
    init(canvasId) {
        const outer = document.querySelector('.editor-canvas-outer');
        const width = outer ? outer.clientWidth - 2 : 700;
        const height = outer ? outer.clientHeight - 2 : 450;

        this.canvas = new fabric.Canvas(canvasId, {
            width: width,
            height: height,
            backgroundColor: 'transparent',
            preserveObjectStacking: true
        });

        // Dynamic resize listener to fill workspace
        window.addEventListener('resize', () => {
            if (this.canvas && this.canvas.wrapperEl) {
                const newOuter = document.querySelector('.editor-canvas-outer');
                if (newOuter && newOuter.clientWidth > 0 && newOuter.clientHeight > 0) {
                    this.canvas.setDimensions({
                        width: newOuter.clientWidth - 2,
                        height: newOuter.clientHeight - 2
                    });
                    this.canvas.renderAll();
                }
            }
        });

        this.bindEvents();
    },

    bindEvents() {
        // Bind Mouse Wheel Zoom
        this.canvas.on('mouse:wheel', (opt) => {
            const delta = opt.e.deltaY;
            let zoom = this.canvas.getZoom();
            zoom *= 0.999 ** delta;
            if (zoom > 20) zoom = 20;
            if (zoom < 0.01) zoom = 0.01;
            
            this.setZoom(zoom);
            opt.e.preventDefault();
            opt.e.stopPropagation();
        });

        // Trigger updates in properties panel when objects are scaled or transformed
        this.canvas.on('object:scaling', () => this.syncPropertiesUI());
        this.canvas.on('object:moving', () => this.syncPropertiesUI());
        this.canvas.on('object:rotating', () => this.syncPropertiesUI());

        // Track history states on modification
        this.canvas.on('object:modified', () => {
            this.saveHistoryState();
        });
    },

    /**
     * Load an image onto the Fabric.js canvas
     * @param {string} url Image dataURL or source file URL
     */
    loadImage(url) {
        this.clearCanvas();

        fabric.Image.fromURL(url, (img) => {
            this.activeImage = img;
            
            // Set handles configuration settings
            img.set({
                borderColor: '#3B9FD4',
                cornerColor: '#ffffff',
                cornerStrokeColor: '#3B9FD4',
                cornerSize: 10,
                transparentCorners: false,
                cornerStyle: 'circle'
            });

            // Scale image to fit within canvas borders
            const scaleX = (this.canvas.width * 0.8) / img.width;
            const scaleY = (this.canvas.height * 0.8) / img.height;
            const finalScale = Math.min(scaleX, scaleY);

            img.scale(finalScale);
            this.canvas.add(img);
            this.canvas.centerObject(img);
            this.canvas.setActiveObject(img);
            this.canvas.renderAll();

            this.syncPropertiesUI();
            this.saveHistoryState();
        }, { crossOrigin: 'anonymous' });
    },

    /**
     * Clear the canvas workspace
     */
    clearCanvas() {
        this.canvas.clear();
        this.activeImage = null;
        this.cropRect = null;
        this.overlayGuides = [];
        this.zoomLevel = 1.0;
        this.canvas.setZoom(1.0);
        this.canvas.setViewportTransform([1, 0, 0, 1, 0, 0]);
        this.syncPropertiesUI();
    },

    /**
     * Adjust zoom level
     * @param {number} value Zoom scale multiplier
     */
    setZoom(value) {
        this.zoomLevel = value;
        // Center zoom relative to canvas midpoint
        const center = this.canvas.getCenter();
        this.canvas.zoomToPoint({ x: center.left, y: center.top }, value);
        
        // Sync zoom label in bottom UI
        const zoomLbl = document.getElementById('lbl-editor-zoom');
        if (zoomLbl) zoomLbl.textContent = Math.round(value * 100) + '%';
    },

    zoomIn() {
        this.setZoom(Math.min(this.zoomLevel + 0.1, 5.0));
    },

    zoomOut() {
        this.setZoom(Math.max(this.zoomLevel - 0.1, 0.1));
    },

    zoomFit() {
        if (!this.activeImage) {
            this.setZoom(1.0);
            return;
        }
        
        // Compute bounding fits
        const scaleX = this.canvas.width / (this.activeImage.width * this.activeImage.scaleX);
        const scaleY = this.canvas.height / (this.activeImage.height * this.activeImage.scaleY);
        const fitZoom = Math.min(scaleX, scaleY) * 0.9;
        
        this.canvas.setViewportTransform([1, 0, 0, 1, 0, 0]);
        this.setZoom(fitZoom);
    },

    zoom100() {
        this.canvas.setViewportTransform([1, 0, 0, 1, 0, 0]);
        this.setZoom(1.0);
    },

    /**
     * Transformations: Rotate Left and Right
     */
    rotateLeft() {
        if (!this.activeImage) return;
        const currentAngle = this.activeImage.angle || 0;
        this.activeImage.rotate((currentAngle - 90 + 360) % 360);
        this.canvas.centerObject(this.activeImage);
        this.canvas.renderAll();
        this.syncPropertiesUI();
        this.saveHistoryState();
    },

    rotateRight() {
        if (!this.activeImage) return;
        const currentAngle = this.activeImage.angle || 0;
        this.activeImage.rotate((currentAngle + 90) % 360);
        this.canvas.centerObject(this.activeImage);
        this.canvas.renderAll();
        this.syncPropertiesUI();
        this.saveHistoryState();
    },

    rotateCustom(angle) {
        if (!this.activeImage) return;
        this.activeImage.rotate(angle);
        this.canvas.renderAll();
        this.syncPropertiesUI();
    },

    /**
     * Transformations: Flips
     */
    flipHorizontal() {
        if (!this.activeImage) return;
        this.activeImage.set('flipX', !this.activeImage.flipX);
        this.canvas.renderAll();
        this.saveHistoryState();
    },

    flipVertical() {
        if (!this.activeImage) return;
        this.activeImage.set('flipY', !this.activeImage.flipY);
        this.canvas.renderAll();
        this.saveHistoryState();
    },

    resetTransforms() {
        if (!this.activeImage) return;
        this.activeImage.set({
            angle: 0,
            scaleX: 1,
            scaleY: 1,
            flipX: false,
            flipY: false
        });
        const slider = document.getElementById('slider-editor-rotate');
        if (slider) slider.value = 0;
        const sliderLbl = document.getElementById('lbl-editor-rotate');
        if (sliderLbl) sliderLbl.textContent = '0°';

        this.canvas.centerObject(this.activeImage);
        this.canvas.renderAll();
        this.syncPropertiesUI();
        this.saveHistoryState();
    },

    /**
     * Crop Overlay Tool: Initialize Crop Mode
     * @param {string} aspectName Aspect ratio description (free, 1:1, 4:5, 16:9, 9:16)
     */
    initCrop(aspectName) {
        if (!this.activeImage) return;

        // Remove existing cropRect if present
        if (this.cropRect) {
            this.canvas.remove(this.cropRect);
        }

        let width = 200;
        let height = 200;

        switch (aspectName) {
            case '1_1':
                width = height = 200;
                break;
            case '4_5':
                width = 160;
                height = 200;
                break;
            case '16_9':
                width = 320;
                height = 180;
                break;
            case '9_16':
                width = 180;
                height = 320;
                break;
            default: // free crop
                width = 250;
                height = 200;
                break;
        }

        // Draw crop area frame
        this.cropRect = new fabric.Rect({
            left: (this.canvas.width - width) / 2,
            top: (this.canvas.height - height) / 2,
            width: width,
            height: height,
            fill: 'transparent',
            stroke: '#ffffff',
            strokeWidth: 2,
            strokeDashArray: [6, 4],
            cornerColor: '#ffffff',
            cornerStrokeColor: '#080C10',
            cornerSize: 10,
            transparentCorners: false,
            borderColor: 'transparent',
            hasRotatingPoint: false,
            lockUniScaling: (aspectName !== 'free') // Lock scaling aspect
        });

        this.canvas.add(this.cropRect);
        this.canvas.setActiveObject(this.cropRect);
        this.canvas.renderAll();
    },

    /**
     * Execute Crop
     */
    applyCrop() {
        if (!this.activeImage || !this.cropRect) return;

        // Hide crop guide
        this.cropRect.set({ visible: false });
        this.canvas.renderAll();

        // Get coordinates of the crop bounding box
        const left = this.cropRect.left;
        const top = this.cropRect.top;
        const w = this.cropRect.width * this.cropRect.scaleX;
        const h = this.cropRect.height * this.cropRect.scaleY;

        // Export dataURL representing cropped bounds
        const dataUrl = this.canvas.toDataURL({
            left: left,
            top: top,
            width: w,
            height: h
        });

        // Replace canvas object with cropped image snapshot
        this.loadImage(dataUrl);
        this.cropRect = null;
    },

    cancelCrop() {
        if (this.cropRect) {
            this.canvas.remove(this.cropRect);
            this.cropRect = null;
            this.canvas.setActiveObject(this.activeImage);
            this.canvas.renderAll();
        }
    },

    /**
     * Non-destructive overlay platform bounds
     */
    toggleOverlayGuide(platform, isVisible) {
        // Clear previous platform guide
        this.overlayGuides.forEach(g => this.canvas.remove(g));
        this.overlayGuides = [];

        if (!isVisible) {
            this.canvas.renderAll();
            return;
        }

        let width = 300;
        let height = 300;
        let name = "Guide Overlay";

        switch (platform) {
            case 'instagram_post':
                width = height = 300;
                name = "Instagram Post (1:1)";
                break;
            case 'instagram_story':
                width = 170;
                height = 300;
                name = "Instagram Story (9:16)";
                break;
            case 'facebook_post':
                width = 320;
                height = 170;
                name = "Facebook Post (1200x630)";
                break;
            case 'youtube_thumbnail':
                width = 320;
                height = 180;
                name = "YouTube Thumbnail (16:9)";
                break;
        }

        // Draw guide overlay rectangle
        const guide = new fabric.Rect({
            left: (this.canvas.width - width) / 2,
            top: (this.canvas.height - height) / 2,
            width: width,
            height: height,
            fill: 'rgba(59, 159, 212, 0.05)',
            stroke: '#3B9FD4',
            strokeWidth: 1.5,
            strokeDashArray: [5, 5],
            selectable: false,
            evented: false
        });

        // Add text descriptor
        const guideText = new fabric.Text(name, {
            left: (this.canvas.width - width) / 2,
            top: ((this.canvas.height - height) / 2) - 20,
            fontSize: 11,
            fontFamily: 'Outfit',
            fill: '#3B9FD4',
            selectable: false,
            evented: false
        });

        this.overlayGuides.push(guide, guideText);
        this.canvas.add(guide, guideText);
        this.canvas.renderAll();
    },

    /**
     * Synchronize and populate details inside Right properties Panel
     */
    syncPropertiesUI() {
        const wInput = document.getElementById('prop-editor-width');
        const hInput = document.getElementById('prop-editor-height');
        const scaleVal = document.getElementById('lbl-editor-scale');

        if (!this.activeImage) {
            if (wInput) wInput.value = 0;
            if (hInput) hInput.value = 0;
            if (scaleVal) scaleVal.textContent = '100%';
            return;
        }

        const width = Math.round(this.activeImage.width * this.activeImage.scaleX);
        const height = Math.round(this.activeImage.height * this.activeImage.scaleY);
        const scale = Math.round(this.activeImage.scaleX * 100);

        if (wInput) wInput.value = width;
        if (hInput) hInput.value = height;
        if (scaleVal) scaleVal.textContent = scale + '%';
    },

    /**
     * Update active Fabric.js dimensions from properties panel
     */
    updateActiveSize(w, h) {
        if (!this.activeImage) return;

        if (this.aspectRatioLocked) {
            const ratio = this.activeImage.width / this.activeImage.height;
            if (w !== null) {
                h = Math.round(w / ratio);
            } else if (h !== null) {
                w = Math.round(h * ratio);
            }
        }

        if (w !== null) {
            this.activeImage.set('scaleX', w / this.activeImage.width);
        }
        if (h !== null) {
            this.activeImage.set('scaleY', h / this.activeImage.height);
        }

        this.canvas.renderAll();
        this.syncPropertiesUI();
        this.saveHistoryState();
    },

    /**
     * Capture current canvas serialize snapshot to push on History Stack
     */
    saveHistoryState() {
        const snapshot = JSON.stringify(this.canvas.toJSON());
        EditorHistory.push(snapshot);
    },

    /**
     * Restore serialized JSON string onto Fabric.js canvas
     */
    restoreHistoryState(serializedJSON) {
        if (!serializedJSON) return;

        this.canvas.loadFromJSON(serializedJSON, () => {
            this.canvas.renderAll();
            
            // Re-bind active image reference
            const objects = this.canvas.getObjects();
            this.activeImage = objects.find(obj => obj.type === 'image') || null;
            this.cropRect = objects.find(obj => obj.type === 'rect' && obj.strokeDashArray) || null;

            this.syncPropertiesUI();
            EditorHistory.isOperating = false;
        });
    }
};
