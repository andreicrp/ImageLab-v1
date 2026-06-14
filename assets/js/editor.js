/**
 * ImageLab Editor Manager (Phase 3)
 * Handles UI interactions, keyboard shortcuts, and workspace session snapshots.
 */
const ImageLabEditor = {
    currentFileId: null,

    init() {
        // Initialize Fabric.js instance
        ImageLabCanvas.init('editor-canvas');

        this.bindControls();
        this.bindKeyboardShortcuts();
    },

    bindControls() {
        // Drag-and-drop / select upload for Editor Workspace
        const editorUploader = document.getElementById('file-uploader-editor');
        if (editorUploader) {
            editorUploader.addEventListener('change', (e) => {
                const file = e.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = (event) => {
                        ImageLabCanvas.loadImage(event.target.result);
                        this.currentFileId = 'editor_' + Date.now();
                        ImageLab.showAlert('editor-alert-box', 'Image loaded successfully into canvas.', 'success');
                    };
                    reader.readAsDataURL(file);
                }
            });
        }

        // Live Dimension Updates (Width / Height inputs)
        const wInput = document.getElementById('prop-editor-width');
        const hInput = document.getElementById('prop-editor-height');
        
        if (wInput) {
            wInput.addEventListener('change', (e) => {
                const val = parseInt(e.target.value);
                if (val > 0) ImageLabCanvas.updateActiveSize(val, null);
            });
        }
        if (hInput) {
            hInput.addEventListener('change', (e) => {
                const val = parseInt(e.target.value);
                if (val > 0) ImageLabCanvas.updateActiveSize(null, val);
            });
        }

        // Lock ratio checkbox
        const ratioCheck = document.getElementById('prop-editor-ratio');
        if (ratioCheck) {
            ratioCheck.addEventListener('change', (e) => {
                ImageLabCanvas.aspectRatioLocked = e.target.checked;
            });
        }

        // Custom Angle Rotation Slider
        const rotateSlider = document.getElementById('slider-editor-rotate');
        const rotateLbl = document.getElementById('lbl-editor-rotate');
        if (rotateSlider) {
            rotateSlider.addEventListener('input', (e) => {
                const val = parseInt(e.target.value);
                if (rotateLbl) rotateLbl.textContent = val + '°';
                ImageLabCanvas.rotateCustom(val);
            });
            rotateSlider.addEventListener('change', () => {
                ImageLabCanvas.saveHistoryState();
            });
        }

        // Rotation Buttons
        const btnRotLeft = document.getElementById('btn-editor-rot-left');
        if (btnRotLeft) btnRotLeft.addEventListener('click', () => ImageLabCanvas.rotateLeft());

        const btnRotRight = document.getElementById('btn-editor-rot-right');
        if (btnRotRight) btnRotRight.addEventListener('click', () => ImageLabCanvas.rotateRight());

        // Flips
        const btnFlipH = document.getElementById('btn-editor-flip-h');
        if (btnFlipH) btnFlipH.addEventListener('click', () => ImageLabCanvas.flipHorizontal());

        const btnFlipV = document.getElementById('btn-editor-flip-v');
        if (btnFlipV) btnFlipV.addEventListener('click', () => ImageLabCanvas.flipVertical());

        const btnReset = document.getElementById('btn-editor-reset');
        if (btnReset) btnReset.addEventListener('click', () => ImageLabCanvas.resetTransforms());

        // Zoom Controls
        const btnZoomIn = document.getElementById('btn-editor-zoom-in');
        if (btnZoomIn) btnZoomIn.addEventListener('click', () => ImageLabCanvas.zoomIn());

        const btnZoomOut = document.getElementById('btn-editor-zoom-out');
        if (btnZoomOut) btnZoomOut.addEventListener('click', () => ImageLabCanvas.zoomOut());

        const btnZoomFit = document.getElementById('btn-editor-zoom-fit');
        if (btnZoomFit) btnZoomFit.addEventListener('click', () => ImageLabCanvas.zoomFit());

        const btnZoom100 = document.getElementById('btn-editor-zoom-100');
        if (btnZoom100) btnZoom100.addEventListener('click', () => ImageLabCanvas.zoom100());

        // Crop System Presets
        const cropSelect = document.getElementById('editor-crop-presets');
        if (cropSelect) {
            cropSelect.addEventListener('change', (e) => {
                const aspect = e.target.value;
                if (aspect !== 'none') {
                    ImageLabCanvas.initCrop(aspect);
                } else {
                    ImageLabCanvas.cancelCrop();
                }
            });
        }

        const btnApplyCrop = document.getElementById('btn-editor-apply-crop');
        if (btnApplyCrop) {
            btnApplyCrop.addEventListener('click', () => {
                ImageLabCanvas.applyCrop();
                if (cropSelect) cropSelect.value = 'none';
            });
        }

        const btnCancelCrop = document.getElementById('btn-editor-cancel-crop');
        if (btnCancelCrop) {
            btnCancelCrop.addEventListener('click', () => {
                ImageLabCanvas.cancelCrop();
                if (cropSelect) cropSelect.value = 'none';
            });
        }

        // Overlay Platform Guides Selector
        const overlaySelect = document.getElementById('editor-guides-presets');
        const overlayToggle = document.getElementById('editor-guides-toggle');
        if (overlaySelect && overlayToggle) {
            const updateOverlay = () => {
                const platform = overlaySelect.value;
                const isVisible = overlayToggle.checked;
                ImageLabCanvas.toggleOverlayGuide(platform, isVisible);
            };

            overlaySelect.addEventListener('change', updateOverlay);
            overlayToggle.addEventListener('change', updateOverlay);
        }

        // Undo & Redo Buttons
        const btnUndo = document.getElementById('btn-editor-undo');
        if (btnUndo) {
            btnUndo.addEventListener('click', () => this.triggerUndo());
        }

        const btnRedo = document.getElementById('btn-editor-redo');
        if (btnRedo) {
            btnRedo.addEventListener('click', () => this.triggerRedo());
        }

        // Save & Restore Sessions
        const btnSaveWorkspace = document.getElementById('btn-editor-save-session');
        if (btnSaveWorkspace) {
            btnSaveWorkspace.addEventListener('click', () => this.saveSession());
        }

        const btnRestoreWorkspace = document.getElementById('btn-editor-restore-session');
        if (btnRestoreWorkspace) {
            btnRestoreWorkspace.addEventListener('click', () => this.loadSession());
        }
    },

    bindKeyboardShortcuts() {
        document.addEventListener('keydown', (e) => {
            // Check if user is typing in inputs to prevent triggering shortcuts
            if (e.target.tagName === 'INPUT' || e.target.tagName === 'SELECT') {
                return;
            }

            if (e.ctrlKey && e.key.toLowerCase() === 'z') {
                e.preventDefault();
                this.triggerUndo();
            } else if (e.ctrlKey && e.key.toLowerCase() === 'y') {
                e.preventDefault();
                this.triggerRedo();
            }
        });
    },

    triggerUndo() {
        const state = EditorHistory.undo();
        if (state) {
            ImageLabCanvas.restoreHistoryState(state);
        }
    },

    triggerRedo() {
        const state = EditorHistory.redo();
        if (state) {
            ImageLabCanvas.restoreHistoryState(state);
        }
    },

    /**
     * Save Workspace Session snapshots to Local Storage and Endpoint
     */
    saveSession() {
        if (!ImageLabCanvas.activeImage) {
            ImageLab.showAlert('editor-alert-box', 'No active image in canvas to save.', 'warning');
            return;
        }

        const snapshot = JSON.stringify(ImageLabCanvas.canvas.toJSON());
        const sessionData = {
            fileId: this.currentFileId,
            canvasState: snapshot,
            zoom: ImageLabCanvas.zoomLevel,
            timestamp: Date.now()
        };

        // Save to LocalStorage
        localStorage.setItem('imagelab_editor_session', JSON.stringify(sessionData));

        // Sync with backend API save endpoint
        const params = new URLSearchParams();
        params.append('session_id', this.currentFileId);
        params.append('canvas_data', snapshot);

        fetch(`${ImageLabBaseUrl}/api/save_workspace.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: params
        })
        .then(res => res.json())
        .then(res => {
            if (res.status === 'success') {
                ImageLab.showAlert('editor-alert-box', 'Workspace session successfully saved to Local Storage and Server.', 'success');
            } else {
                ImageLab.showAlert('editor-alert-box', 'Session saved to Local Storage (Server backup failed).', 'info');
            }
        })
        .catch(() => {
            ImageLab.showAlert('editor-alert-box', 'Session saved to Local Storage (Network error saving to Server).', 'info');
        });
    },

    /**
     * Load Workspace Session snapshot
     */
    loadSession() {
        const localData = localStorage.getItem('imagelab_editor_session');
        if (localData) {
            const session = JSON.parse(localData);
            this.currentFileId = session.fileId;
            
            // Clear history state and restore canvas objects
            EditorHistory.clear();
            ImageLabCanvas.restoreHistoryState(session.canvasState);
            setTimeout(() => {
                ImageLabCanvas.setZoom(session.zoom || 1.0);
                ImageLabCanvas.saveHistoryState(); // Initial state
                ImageLab.showAlert('editor-alert-box', 'Session restored successfully from Local Storage.', 'success');
            }, 300);
            return;
        }

        // Try to fetch from server as fallback
        fetch(`${ImageLabBaseUrl}/api/load_workspace.php?session_id=latest`)
        .then(res => res.json())
        .then(res => {
            if (res.status === 'success' && res.data && res.data.canvas_data) {
                EditorHistory.clear();
                ImageLabCanvas.restoreHistoryState(res.data.canvas_data);
                setTimeout(() => {
                    ImageLabCanvas.saveHistoryState();
                    ImageLab.showAlert('editor-alert-box', 'Session restored successfully from Server.', 'success');
                }, 300);
            } else {
                ImageLab.showAlert('editor-alert-box', 'No saved workspace session was found.', 'warning');
            }
        })
        .catch(() => {
            ImageLab.showAlert('editor-alert-box', 'Failed to retrieve saved session.', 'danger');
        });
    }
};
