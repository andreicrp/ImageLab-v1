/**
 * ImageLab Client Application Namespace (Phase 2)
 */
function empty(val) {
    return val === undefined || val === null || val === '' || val === false || 
           (Array.isArray(val) && val.length === 0) || 
           (typeof val === 'object' && Object.keys(val).length === 0);
}
const ImageLab = {
    // Current state variables
    state: {
        activeFilename: null,      // Unique filename in uploads
        activeOriginalName: null,  // Original name for display
        activeWidth: 0,
        activeHeight: 0,
        activeExtension: '',
        activeSize: 0,

        isQueueProcessing: false,
        totalQueueJobs: 0,
        completedQueueJobs: 0
    },

    /**
     * Display Bootstrap alerts inside specified container
     */
    showAlert(containerId, message, type = 'danger') {
        const container = document.getElementById(containerId);
        if (!container) return;

        let iconClass = 'fa-triangle-exclamation text-danger';
        if (type === 'success') {
            iconClass = 'fa-circle-check text-success';
        } else if (type === 'danger') {
            iconClass = 'fa-circle-xmark text-danger';
        } else if (type === 'warning') {
            iconClass = 'fa-triangle-exclamation text-warning';
        } else if (type === 'info') {
            iconClass = 'fa-circle-info text-info';
        }

        container.innerHTML = `
            <div class="alert alert-${type} alert-dismissible fade show shadow-sm border-0 d-flex align-items-center gap-2" role="alert">
                <i class="fa-solid ${iconClass}"></i>
                <div style="font-size: 13px;">${message}</div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        `;
    },

    /**
     * Format byte values
     */
    formatBytes(bytes, decimals = 1) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const dm = decimals < 0 ? 0 : decimals;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
    },

    /**
     * Switch panel view dynamically (Single Page App experience)
     */
    switchPanel(panelId) {
        // Hide all panels
        document.querySelectorAll('.dashboard-panel').forEach(panel => {
            panel.classList.add('d-none');
        });

        // Show target panel
        const target = document.getElementById(`panel-${panelId}`);
        if (target) {
            target.classList.remove('d-none');
        }

        // Update active sidebar class
        document.querySelectorAll('.sidebar-nav-wrapper .nav-link').forEach(link => {
            link.classList.remove('active');
        });
        const activeLink = document.getElementById(`nav-${panelId}`);
        if (activeLink) {
            activeLink.classList.add('active');
        }

        // Close sidebar and backdrop on mobile
        const sidebar = document.getElementById('sidebar');
        const backdrop = document.getElementById('sidebar-backdrop');
        if (sidebar) {
            sidebar.classList.remove('show');
        }
        if (backdrop) {
            backdrop.classList.remove('show');
        }

        // Refresh stats if opening dashboard
        if (panelId === 'dashboard') {
            this.refreshAnalytics();
        }

        // Trigger canvas resize when switching to Editor Panel
        if (panelId === 'editor' && typeof ImageLabCanvas !== 'undefined' && ImageLabCanvas.canvas) {
            const outer = document.querySelector('.editor-canvas-outer');
            if (outer && outer.clientWidth > 0 && outer.clientHeight > 0) {
                ImageLabCanvas.canvas.setDimensions({
                    width: outer.clientWidth - 2,
                    height: outer.clientHeight - 2
                });
                ImageLabCanvas.canvas.renderAll();
            }
        }
    },

    /**
     * Synchronize and display the active uploaded file across workspace tabs
     */
    syncActiveFileUI() {
        const hasFile = (this.state.activeFilename !== null);

        // Auto-run AI quality and tags analysis in the background when active file changes
        if (hasFile && typeof this.inspectAIQualityAndTags === 'function') {
            this.inspectAIQualityAndTags();
        }

        // Convert Tab Header
        const headerConvert = document.getElementById('active-file-header-convert');
        const uploadCardConvert = document.getElementById('convert-upload-card');
        if (hasFile) {
            headerConvert.classList.remove('d-none');
            headerConvert.classList.add('d-flex');
            document.getElementById('active-filename-convert').textContent = this.state.activeOriginalName;
            uploadCardConvert.classList.add('d-none');
            
            // Pop original preview card
            document.getElementById('convert-orig-empty').classList.add('d-none');
            document.getElementById('convert-orig-content').classList.remove('d-none');
            document.getElementById('convert-orig-img').src = `${ImageLabBaseUrl}/api/download.php?file=${this.state.activeFilename}&type=uploads&t=${Date.now()}`;
            document.getElementById('convert-orig-meta').textContent = `Dimensions: ${this.state.activeWidth}x${this.state.activeHeight}px (${this.state.activeExtension.toUpperCase()})`;
            
            // Unlock convert button
            document.getElementById('convert-target-select').disabled = false;
            document.getElementById('execute-convert-btn').disabled = false;
        } else {
            headerConvert.classList.add('d-none');
            headerConvert.classList.remove('d-flex');
            uploadCardConvert.classList.remove('d-none');
            document.getElementById('convert-orig-content').classList.add('d-none');
            document.getElementById('convert-orig-empty').classList.remove('d-none');
            document.getElementById('convert-conv-content').classList.add('d-none');
            document.getElementById('convert-conv-empty').classList.remove('d-none');
            
            document.getElementById('convert-target-select').disabled = true;
            document.getElementById('execute-convert-btn').disabled = true;
        }

        // Resize Tab Header
        const headerResize = document.getElementById('active-file-header-resize');
        const uploadCardResize = document.getElementById('resize-upload-card');
        if (hasFile) {
            headerResize.classList.remove('d-none');
            headerResize.classList.add('d-flex');
            document.getElementById('active-filename-resize').textContent = this.state.activeOriginalName;
            uploadCardResize.classList.add('d-none');

            document.getElementById('resize-orig-empty').classList.add('d-none');
            document.getElementById('resize-orig-content').classList.remove('d-none');
            document.getElementById('resize-orig-img').src = `${ImageLabBaseUrl}/api/download.php?file=${this.state.activeFilename}&type=uploads&t=${Date.now()}`;
            document.getElementById('resize-orig-meta').textContent = `Dimensions: ${this.state.activeWidth}x${this.state.activeHeight}px (${this.state.activeExtension.toUpperCase()})`;
            
            // Unlock resize settings
            document.getElementById('resize-preset-select').disabled = false;
            document.getElementById('resize-width').disabled = false;
            document.getElementById('resize-height').disabled = false;
            document.getElementById('resize-ratio-checkbox').disabled = false;
            document.getElementById('execute-resize-btn').disabled = false;
            
            // Prefill inputs
            document.getElementById('resize-width').value = this.state.activeWidth;
            document.getElementById('resize-height').value = this.state.activeHeight;
            this.updateResizePreview();
        } else {
            headerResize.classList.add('d-none');
            headerResize.classList.remove('d-flex');
            uploadCardResize.classList.remove('d-none');
            document.getElementById('resize-orig-content').classList.add('d-none');
            document.getElementById('resize-orig-empty').classList.remove('d-none');
            document.getElementById('resize-conv-content').classList.add('d-none');
            document.getElementById('resize-conv-empty').classList.remove('d-none');
            
            document.getElementById('resize-preset-select').disabled = true;
            document.getElementById('resize-width').disabled = true;
            document.getElementById('resize-height').disabled = true;
            document.getElementById('resize-ratio-checkbox').disabled = true;
            document.getElementById('execute-resize-btn').disabled = true;
        }

        // Compress Tab Header
        const headerCompress = document.getElementById('active-file-header-compress');
        const uploadCardCompress = document.getElementById('compress-upload-card');
        if (hasFile) {
            headerCompress.classList.remove('d-none');
            headerCompress.classList.add('d-flex');
            document.getElementById('active-filename-compress').textContent = this.state.activeOriginalName;
            uploadCardCompress.classList.add('d-none');

            document.getElementById('compress-orig-empty').classList.add('d-none');
            document.getElementById('compress-orig-content').classList.remove('d-none');
            document.getElementById('compress-orig-img').src = `${ImageLabBaseUrl}/api/download.php?file=${this.state.activeFilename}&type=uploads&t=${Date.now()}`;
            document.getElementById('compress-orig-meta').textContent = `Dimensions: ${this.state.activeWidth}x${this.state.activeHeight}px (${this.state.activeExtension.toUpperCase()})`;
            
            // Unlock compression
            document.getElementById('execute-compress-btn').disabled = false;
        } else {
            headerCompress.classList.add('d-none');
            headerCompress.classList.remove('d-flex');
            uploadCardCompress.classList.remove('d-none');
            document.getElementById('compress-orig-content').classList.add('d-none');
            document.getElementById('compress-orig-empty').classList.remove('d-none');
            document.getElementById('compress-conv-content').classList.add('d-none');
            document.getElementById('compress-conv-empty').classList.remove('d-none');
            
            document.getElementById('execute-compress-btn').disabled = true;
        }

        // Enhance Tab Header
        const headerEnhance = document.getElementById('active-file-header-enhance');
        const uploadCardEnhance = document.getElementById('enhance-upload-card');
        if (headerEnhance && uploadCardEnhance) {
            if (hasFile) {
                headerEnhance.classList.remove('d-none');
                headerEnhance.classList.add('d-flex');
                document.getElementById('active-filename-enhance').textContent = this.state.activeOriginalName;
                uploadCardEnhance.classList.add('d-none');

                document.getElementById('enhance-orig-empty').classList.add('d-none');
                document.getElementById('enhance-comparison-viewport').classList.remove('d-none');
                
                // Set images on side-by-side preview
                const fileUrl = `${ImageLabBaseUrl}/api/download.php?file=${this.state.activeFilename}&type=uploads&t=${Date.now()}`;
                
                document.getElementById('comp-img-before').src = fileUrl;
                document.getElementById('comp-img-after').src = fileUrl;

                // Render metrics
                document.getElementById('enhance-orig-meta').textContent = `${this.state.activeWidth}x${this.state.activeHeight}px (${this.state.activeExtension.toUpperCase()})`;
                document.getElementById('enhance-meta-old-size').textContent = this.formatBytes(this.state.activeSize);
                document.getElementById('enhance-meta-new-size').textContent = this.formatBytes(this.state.activeSize);
                document.getElementById('enhance-meta-saved-pct').textContent = '0%';

                // Trigger EXIF inspect
                this.inspectMetadata();
                
                // Trigger size estimation
                this.onExportParamsChanged();
                
                // Load presets
                this.loadSavedPresetsList();
            } else {
                headerEnhance.classList.add('d-none');
                headerEnhance.classList.remove('d-flex');
                uploadCardEnhance.classList.remove('d-none');
                document.getElementById('enhance-comparison-viewport').classList.add('d-none');
                document.getElementById('enhance-orig-empty').classList.remove('d-none');
                
                // Disable dynamic buttons
                const cBtn = document.getElementById('btn-enhance-to-editor');
                if (cBtn) cBtn.disabled = true;
                const dBtn = document.getElementById('enhance-download-btn');
                if (dBtn) dBtn.classList.add('disabled');
            }
        }

        // AI Tab Header
        const headerAI = document.getElementById('active-file-header-ai');
        const uploadCardAI = document.getElementById('ai-upload-card');
        if (headerAI && uploadCardAI) {
            if (hasFile) {
                headerAI.classList.remove('d-none');
                headerAI.classList.add('d-flex');
                document.getElementById('active-filename-ai').textContent = this.state.activeOriginalName;
                uploadCardAI.classList.add('d-none');

                document.getElementById('ai-orig-empty').classList.add('d-none');
                document.getElementById('ai-comparison-viewport').classList.remove('d-none');
                
                // Set images on side-by-side preview
                const fileUrl = `${ImageLabBaseUrl}/api/download.php?file=${this.state.activeFilename}&type=uploads&t=${Date.now()}`;
                
                document.getElementById('ai-comp-img-before').src = fileUrl;
                document.getElementById('ai-comp-img-after').src = fileUrl;

                // Render metrics
                document.getElementById('ai-orig-meta').textContent = `${this.state.activeWidth}x${this.state.activeHeight}px (${this.state.activeExtension.toUpperCase()})`;
                document.getElementById('ai-meta-old-size').textContent = this.formatBytes(this.state.activeSize);
                document.getElementById('ai-meta-new-size').textContent = this.formatBytes(this.state.activeSize);
                document.getElementById('ai-meta-saved-pct').textContent = '0%';

            } else {
                headerAI.classList.add('d-none');
                headerAI.classList.remove('d-flex');
                uploadCardAI.classList.remove('d-none');
                document.getElementById('ai-comparison-viewport').classList.add('d-none');
                document.getElementById('ai-orig-empty').classList.remove('d-none');
                
                // Disable download button
                const dBtn = document.getElementById('ai-download-btn');
                if (dBtn) dBtn.classList.add('disabled');
            }
        }
    },

    /**
     * Clear active file cache
     */
    resetActiveFile() {
        this.state.activeFilename = null;
        this.state.activeOriginalName = null;
        this.state.activeWidth = 0;
        this.state.activeHeight = 0;
        this.state.activeExtension = '';
        this.state.activeSize = 0;
        this.syncActiveFileUI();
    },

    /**
     * Handle single file uploads for convert/resize/compress
     */
    handleFileSelect(file) {
        if (!file) return;

        // Fetch current active panel prefix to identify progress bar target
        let targetPrefix = 'convert';
        const convertPanel = document.getElementById('panel-convert');
        const resizePanel = document.getElementById('panel-resize');
        const compressPanel = document.getElementById('panel-compress');
        const enhancePanel = document.getElementById('panel-enhance');
        
        if (!convertPanel.classList.contains('d-none')) {
            targetPrefix = 'convert';
        } else if (!resizePanel.classList.contains('d-none')) {
            targetPrefix = 'resize';
        } else if (!compressPanel.classList.contains('d-none')) {
            targetPrefix = 'compress';
        } else if (enhancePanel && !enhancePanel.classList.contains('d-none')) {
            targetPrefix = 'enhance';
        } else if (document.getElementById('panel-ai') && !document.getElementById('panel-ai').classList.contains('d-none')) {
            targetPrefix = 'ai';
        }

        const progressBarContainer = document.getElementById(`progress-container-${targetPrefix}`);
        const progressBar = document.getElementById(`progress-bar-${targetPrefix}`);
        const alertBox = document.getElementById(`${targetPrefix}-alert-box`);

        // Clear alerts & show progress
        if (alertBox) alertBox.innerHTML = '';
        progressBarContainer.classList.remove('d-none');
        progressBar.style.width = '0%';

        const formData = new FormData();
        formData.append('image', file);

        const xhr = new XMLHttpRequest();
        xhr.open('POST', `${ImageLabBaseUrl}/api/upload.php`, true);

        xhr.upload.addEventListener('progress', (e) => {
            if (e.lengthComputable) {
                const percent = Math.round((e.loaded / e.total) * 100);
                progressBar.style.width = percent + '%';
            }
        });

        xhr.onload = () => {
            progressBarContainer.classList.add('d-none');
            if (xhr.status === 200) {
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response.success) {
                        // Store global state
                        this.state.activeFilename = response.filename;
                        this.state.activeOriginalName = response.original_name;
                        this.state.activeWidth = response.width;
                        this.state.activeHeight = response.height;
                        this.state.activeExtension = response.extension;
                        this.state.activeSize = response.size;

                        this.syncActiveFileUI();
                        this.showAlert(`${targetPrefix}-alert-box`, 'Image uploaded successfully.', 'success');
                    } else {
                        this.showAlert(`${targetPrefix}-alert-box`, response.message || 'Upload failed.', 'danger');
                    }
                } catch (e) {
                    this.showAlert(`${targetPrefix}-alert-box`, 'Error parsing upload response.', 'danger');
                }
            } else {
                this.showAlert(`${targetPrefix}-alert-box`, 'Upload failed.', 'danger');
            }
        };

        xhr.onerror = () => {
            progressBarContainer.classList.add('d-none');
            this.showAlert(`${targetPrefix}-alert-box`, 'Network error during upload.', 'danger');
        };

        xhr.send(formData);
    },

    /**
     * Run Convert format action
     */
    runConvert() {
        if (!this.state.activeFilename) return;

        const targetFormat = document.getElementById('convert-target-select').value;
        const btn = document.getElementById('execute-convert-btn');
        const alertBox = document.getElementById('convert-alert-box');

        alertBox.innerHTML = '';
        btn.disabled = true;

        const params = new URLSearchParams();
        params.append('filename', this.state.uploadedFilename || this.state.activeFilename);
        params.append('format', targetFormat);

        fetch(`${ImageLabBaseUrl}/api/convert.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: params
        })
        .then(res => res.json())
        .then(res => {
            if (res.success) {
                this.showAlert('convert-alert-box', 'Format converted successfully.', 'success');
                
                // Show processed card
                document.getElementById('convert-conv-empty').classList.add('d-none');
                const content = document.getElementById('convert-conv-content');
                content.classList.remove('d-none');

                document.getElementById('convert-conv-img').src = `${ImageLabBaseUrl}/api/download.php?file=${res.filename}&type=processed&t=${Date.now()}`;
                
                const savings = Math.round((1 - (res.new_size / res.original_size)) * 100);
                document.getElementById('convert-conv-meta').innerHTML = savings > 0 ? `Saved ${savings}%` : 'No size saved';
                document.getElementById('convert-conv-metrics').textContent = `New size: ${this.formatBytes(res.new_size)}`;
                document.getElementById('convert-download-btn').href = `${ImageLabBaseUrl}/api/download.php?file=${res.filename}&type=processed&name=${encodeURIComponent(this.state.activeOriginalName)}`;
            } else {
                this.showAlert('convert-alert-box', res.message || 'Convert failed.', 'danger');
            }
        })
        .catch(() => this.showAlert('convert-alert-box', 'Network error during conversion.', 'danger'))
        .finally(() => btn.disabled = false);
    },

    /**
     * Apply preselected dimensions for Resizer presets
     */
    applyDimensionPreset(val) {
        if (val === '') return;

        const presetMappings = {
            'instagram_post': { w: 1080, h: 1080, ratio: false },
            'instagram_story': { w: 1080, h: 1920, ratio: false },
            'facebook_post': { w: 1200, h: 630, ratio: false },
            'youtube_thumbnail': { w: 1280, h: 720, ratio: false },
            'tiktok_cover': { w: 1080, h: 1920, ratio: false },
            'thumbnail_150': { w: 150, h: 150, ratio: true },
            'medium_500': { w: 500, h: 500, ratio: true },
            'large_1200': { w: 1200, h: 1200, ratio: true }
        };

        const config = presetMappings[val];
        if (config) {
            document.getElementById('resize-width').value = config.w;
            document.getElementById('resize-height').value = config.h;
            document.getElementById('resize-ratio-checkbox').checked = config.ratio;
            this.updateResizePreview();
        }
    },

    /**
     * Update dynamic dimensions preview text
     */
    updateResizePreview() {
        const w = parseInt(document.getElementById('resize-width').value) || 0;
        const h = parseInt(document.getElementById('resize-height').value) || 0;
        const ratio = document.getElementById('resize-ratio-checkbox').checked;

        document.getElementById('resize-live-preview-display').textContent = 
            `${w} x ${h} px (${ratio ? 'Ratio Maintained' : 'Stretched'})`;
    },

    /**
     * Run Resize Image action
     */
    runResize() {
        if (!this.state.activeFilename) return;

        const w = parseInt(document.getElementById('resize-width').value) || 0;
        const h = parseInt(document.getElementById('resize-height').value) || 0;
        const ratio = document.getElementById('resize-ratio-checkbox').checked ? 1 : 0;
        const btn = document.getElementById('execute-resize-btn');
        const alertBox = document.getElementById('resize-alert-box');

        alertBox.innerHTML = '';
        btn.disabled = true;

        const params = new URLSearchParams();
        params.append('filename', this.state.activeFilename);
        params.append('width', w);
        params.append('height', h);
        params.append('maintainRatio', ratio);

        fetch(`${ImageLabBaseUrl}/api/resize.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: params
        })
        .then(res => res.json())
        .then(res => {
            if (res.success) {
                this.showAlert('resize-alert-box', 'Image resized successfully.', 'success');

                // Update active state to the newly resized file
                this.state.activeFilename = res.filename;
                this.state.activeWidth = res.width;
                this.state.activeHeight = res.height;
                this.state.activeSize = res.new_size;

                this.syncActiveFileUI();

                // Display resized output preview
                document.getElementById('resize-conv-empty').classList.add('d-none');
                const content = document.getElementById('resize-conv-content');
                content.classList.remove('d-none');

                document.getElementById('resize-conv-img').src = `${ImageLabBaseUrl}/api/download.php?file=${res.filename}&type=processed&t=${Date.now()}`;
                document.getElementById('resize-conv-meta').textContent = `New dimensions: ${res.width}x${res.height}px`;
                document.getElementById('resize-conv-metrics').textContent = `New size: ${this.formatBytes(res.new_size)}`;
                document.getElementById('resize-download-btn').href = `${ImageLabBaseUrl}/api/download.php?file=${res.filename}&type=processed&name=${encodeURIComponent(this.state.activeOriginalName)}`;
            } else {
                this.showAlert('resize-alert-box', res.message || 'Resize failed.', 'danger');
            }
        })
        .catch(() => this.showAlert('resize-alert-box', 'Network error during resize.', 'danger'))
        .finally(() => btn.disabled = false);
    },

    /**
     * Select compression level card
     */
    selectCompressionLevel(level) {
        const levels = ['low', 'medium', 'high', 'max'];
        levels.forEach(lvl => {
            document.getElementById(`btn-comp-${lvl}`).classList.remove('active');
        });

        document.getElementById(`btn-comp-${level}`).classList.add('active');
        document.getElementById('selected-compression-level').value = level;
    },

    /**
     * Run Image Compression action
     */
    runCompress() {
        if (!this.state.activeFilename) return;

        const level = document.getElementById('selected-compression-level').value;
        const btn = document.getElementById('execute-compress-btn');
        const alertBox = document.getElementById('compress-alert-box');

        alertBox.innerHTML = '';
        btn.disabled = true;

        const params = new URLSearchParams();
        params.append('filename', this.state.activeFilename);
        params.append('quality', level);

        fetch(`${ImageLabBaseUrl}/api/compress.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: params
        })
        .then(res => res.json())
        .then(res => {
            if (res.success) {
                this.showAlert('compress-alert-box', 'Image optimized successfully.', 'success');

                // Display compressed output preview
                document.getElementById('compress-conv-empty').classList.add('d-none');
                const content = document.getElementById('compress-conv-content');
                content.classList.remove('d-none');

                document.getElementById('compress-conv-img').src = `${ImageLabBaseUrl}/api/download.php?file=${res.filename}&type=processed&t=${Date.now()}`;
                document.getElementById('compress-meta-old-size').textContent = this.formatBytes(res.original_size);
                document.getElementById('compress-meta-new-size').textContent = this.formatBytes(res.new_size);
                document.getElementById('compress-meta-saved-pct').textContent = `-${res.saved_percent}%`;
                document.getElementById('compress-download-btn').href = `${ImageLabBaseUrl}/api/download.php?file=${res.filename}&type=processed&name=${encodeURIComponent(this.state.activeOriginalName)}`;
            } else {
                this.showAlert('compress-alert-box', res.message || 'Compression failed.', 'danger');
            }
        })
        .catch(() => this.showAlert('compress-alert-box', 'Network error during optimization.', 'danger'))
        .finally(() => btn.disabled = false);
    },

    /**
     * Toggle Batch control setting blocks
     */
    toggleBatchActionControls(action) {
        document.querySelectorAll('.batch-control-sub-panel').forEach(el => {
            el.classList.add('d-none');
        });
        document.getElementById(`batch-ctrl-${action}`).classList.remove('d-none');
    },

    /**
     * Submit batch upload to enqueuing system
     */
    submitBatch() {
        const fileInput = document.getElementById('file-uploader-batch');
        const alertBox = document.getElementById('batch-alert-box');
        const btn = document.getElementById('enqueue-batch-btn');

        if (fileInput.files.length === 0) {
            this.showAlert('batch-alert-box', 'Please select at least one file.', 'warning');
            return;
        }

        // Pre-validate batch files limit
        if (fileInput.files.length > 20) {
            this.showAlert('batch-alert-box', 'Batch exceeds maximum limit of 20 files.', 'danger');
            return;
        }

        alertBox.innerHTML = '';
        btn.disabled = true;

        // Gather operation variables
        const action = document.getElementById('batch-action-select').value;
        const payload = { action };

        if (action === 'convert') {
            payload.format = document.getElementById('batch-convert-format').value;
        } else if (action === 'resize') {
            payload.width = parseInt(document.getElementById('batch-resize-width').value) || 800;
            payload.height = parseInt(document.getElementById('batch-resize-height').value) || 600;
            payload.maintainRatio = document.getElementById('batch-resize-ratio').checked;
        } else if (action === 'compress') {
            payload.quality = parseInt(document.getElementById('batch-compress-quality').value) || 80;
        }

        const formData = new FormData();
        for (let i = 0; i < fileInput.files.length; i++) {
            formData.append('images[]', fileInput.files[i]);
        }
        formData.append('operation', JSON.stringify(payload));

        fetch(`${ImageLabBaseUrl}/api/batch.php?action=upload`, {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(res => {
            if (res.success) {
                this.showAlert('batch-alert-box', 'Batch enqueued successfully. Click start below to process.', 'success');
                fileInput.value = ''; // Reset uploader

                // Update queue table
                this.refreshQueueTable();
            } else {
                this.showAlert('batch-alert-box', res.message || 'Batch enqueue failed.', 'danger');
            }
        })
        .catch(() => this.showAlert('batch-alert-box', 'Network error during batch uploading.', 'danger'))
        .finally(() => btn.disabled = false);
    },

    /**
     * Fetch and render enqueued jobs in queue table
     */
    refreshQueueTable() {
        fetch(`${ImageLabBaseUrl}/api/batch.php?action=status`)
        .then(res => res.json())
        .then(response => {
            const tbody = document.getElementById('batch-queue-tbody');
            if (!tbody) return;

            const jobs = response.data || [];
            
            // Check status indicators
            let pendingCount = 0;
            
            if (jobs.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="4" class="text-center text-muted py-4">No jobs registered in the queue. Upload files on the left.</td>
                    </tr>
                `;
                document.getElementById('execute-queue-btn').disabled = true;
                return;
            }

            tbody.innerHTML = jobs.map(job => {
                let badgeClass = 'bg-secondary-subtle text-secondary';
                let icon = '<i class="fa-regular fa-clock me-1"></i>';
                
                if (job.status === 'processing') {
                    badgeClass = 'bg-warning-subtle text-warning border border-warning-subtle text-dark';
                    icon = '<span class="spinner-border spinner-border-sm me-1" role="status"></span>';
                } else if (job.status === 'completed') {
                    badgeClass = 'bg-success-subtle text-success border border-success-subtle';
                    icon = '<i class="fa-solid fa-circle-check me-1"></i>';
                } else if (job.status === 'failed') {
                    badgeClass = 'bg-danger-subtle text-danger border border-danger-subtle';
                    icon = '<i class="fa-solid fa-triangle-exclamation me-1"></i>';
                }

                if (job.status === 'waiting') {
                    pendingCount++;
                }

                // Render parameters
                let actionText = 'Process';
                try {
                    const ops = JSON.parse(job.operation);
                    actionText = ops.action.toUpperCase() + (ops.format ? ` ➜ ${ops.format.toUpperCase()}` : '');
                } catch(e) {}

                return `
                    <tr>
                        <td class="text-secondary">#${job.id}</td>
                        <td class="text-truncate fw-medium text-dark" style="max-width: 180px;" title="${job.filename}">${job.filename}</td>
                        <td class="small text-muted">${actionText}</td>
                        <td class="text-end">
                            <span class="badge ${badgeClass} px-3 py-1.5 d-inline-flex align-items-center">
                                ${icon} ${job.status.toUpperCase()}
                            </span>
                        </td>
                    </tr>
                `;
            }).join('');

            // Enable execute queue button if there are waiting files
            document.getElementById('execute-queue-btn').disabled = (pendingCount === 0 || this.state.isQueueProcessing);

            // Compute total jobs to track progress bar metrics
            if (!this.state.isQueueProcessing) {
                const completedCount = jobs.filter(j => j.status === 'completed' || j.status === 'failed').length;
                this.state.totalQueueJobs = jobs.length;
                this.state.completedQueueJobs = completedCount;
            }
        });
    },

    /**
     * Clear finished jobs from queue list
     */
    clearQueueCompleted() {
        fetch(`${ImageLabBaseUrl}/api/batch.php?action=clear_completed`, { method: 'POST' })
        .then(() => {
            this.refreshQueueTable();
            document.getElementById('batch-progress-bar-container').classList.add('d-none');
        });
    },

    /**
     * Run sequential loop processing queue jobs
     */
    runProcessingQueue() {
        if (this.state.isQueueProcessing) return;

        this.state.isQueueProcessing = true;
        document.getElementById('execute-queue-btn').disabled = true;
        document.getElementById('batch-progress-bar-container').classList.remove('d-none');

        this.processNextJob();
    },

    /**
     * Recursive call to run next queue job
     */
    processNextJob() {
        fetch(`${ImageLabBaseUrl}/api/batch.php?action=process_next`, { method: 'POST' })
        .then(res => res.json())
        .then(res => {
            if (res.success && res.data && res.data.status) {
                // An item was processed
                this.state.completedQueueJobs++;
                const pct = Math.round((this.state.completedQueueJobs / this.state.totalQueueJobs) * 100);
                document.getElementById('batch-progress-bar').style.width = pct + '%';
                document.getElementById('batch-progress-pct').textContent = pct + '%';

                this.refreshQueueTable();
                
                // Recurse to run next job
                setTimeout(() => this.processNextJob(), 400);
            } else {
                // Queue completed or empty
                this.state.isQueueProcessing = false;
                this.refreshQueueTable();
                this.refreshAnalytics();
            }
        })
        .catch(() => {
            this.state.isQueueProcessing = false;
            this.refreshQueueTable();
        });
    },

    /**
     * Fast Quick Preset applier on Dashboard page
     */
    applyPresetQuick() {
        if (!this.state.activeFilename) {
            this.showAlert('preset-alert-container', 'Please upload an image first using the Convert, Resize, or Compress workspace tabs.', 'warning');
            return;
        }

        const select = document.getElementById('dashboard-preset-select');
        const btn = document.getElementById('dashboard-apply-preset-btn');
        const presetKey = select.value;

        btn.disabled = true;
        document.getElementById('preset-alert-container').innerHTML = '';

        const params = new URLSearchParams();
        params.append('filename', this.state.activeFilename);
        params.append('preset', presetKey);

        fetch(`${ImageLabBaseUrl}/api/resize.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: params
        })
        .then(res => res.json())
        .then(res => {
            if (res.success) {
                this.showAlert('preset-alert-container', `Preset "${presetKey.replace('_', ' ').toUpperCase()}" applied successfully! Download converted asset.`, 'success');
                
                // Show processed card on conversion tab
                document.getElementById('convert-conv-empty').classList.add('d-none');
                const content = document.getElementById('convert-conv-content');
                content.classList.remove('d-none');
                document.getElementById('convert-conv-img').src = `${ImageLabBaseUrl}/api/download.php?file=${res.filename}&type=processed&t=${Date.now()}`;
                document.getElementById('convert-conv-meta').textContent = 'Preset applied';
                document.getElementById('convert-conv-metrics').textContent = `Size: ${this.formatBytes(res.new_size)}`;
                document.getElementById('convert-download-btn').href = `${ImageLabBaseUrl}/api/download.php?file=${res.filename}&type=processed&name=${encodeURIComponent(this.state.activeOriginalName)}`;

                // Automatically switch to Convert Format tab to preview output
                setTimeout(() => {
                    this.switchPanel('convert');
                }, 1500);

            } else {
                this.showAlert('preset-alert-container', res.message || 'Preset application failed.', 'danger');
            }
        })
        .catch(() => this.showAlert('preset-alert-container', 'Error occurred applying preset.', 'danger'))
        .finally(() => btn.disabled = false);
    },

    /**
     * Retrieve statistics from server and draw metric counts
     */
    refreshAnalytics() {
        fetch(`${ImageLabBaseUrl}/api/history.php`)
        .then(res => res.json())
        .then(res => {
            if (res.success && res.data) {
                const stats = res.data.analytics;
                
                // Pop counts
                document.getElementById('stat-total-uploads').textContent = stats.total_uploads;
                document.getElementById('stat-total-conversions').textContent = stats.total_conversions;
                document.getElementById('stat-storage-saved').textContent = this.formatBytes(stats.storage_saved);

                // Check DB offline status
                const dbStatusLabel = document.getElementById('stat-database-status');
                const dbIconContainer = document.getElementById('stat-db-icon-container');
                const dbWarning = document.getElementById('db-warning-alert');

                if (stats.db_connected) {
                    dbStatusLabel.textContent = 'MySQL Online';
                    dbIconContainer.className = 'bg-info-subtle text-info rounded-3 p-3 d-flex align-items-center justify-content-center';
                    dbWarning.classList.add('d-none');
                    dbWarning.classList.remove('d-flex');
                } else {
                    dbStatusLabel.textContent = 'Fallback JSON';
                    dbIconContainer.className = 'bg-danger-subtle text-danger rounded-3 p-3 d-flex align-items-center justify-content-center';
                    dbWarning.classList.remove('d-none');
                    dbWarning.classList.add('d-flex');
                }

                // Render History List
                const historyList = res.data.recent_logs || [];
                this.state.recentHistory = historyList;
                this.renderHistoryTable(historyList);
            }
        });
    },

    /**
     * Render the conversion history table
     */
    renderHistoryTable(historyList) {
        const tbody = document.getElementById('db-history-tbody');
        if (!tbody) return;

        if (historyList.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="5" class="text-center text-muted py-4">No records found.</td>
                </tr>
            `;
            return;
        }

        tbody.innerHTML = historyList.map(log => {
            const savings = Math.round((1 - (log.file_size_after / log.file_size_before)) * 100);
            const formatTimestamp = log.created_at.split(' ')[1] || log.created_at;

            return `
                <tr>
                    <td class="text-secondary">${formatTimestamp}</td>
                    <td><span class="badge bg-light text-primary border border-primary-subtle">${log.operation}</span></td>
                    <td class="text-truncate fw-medium text-dark" style="max-width: 140px;" title="${log.original_filename}">${log.original_filename}</td>
                    <td>${this.formatBytes(log.file_size_after)}</td>
                    <td class="text-end">
                        <span class="badge ${savings > 0 ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-secondary-subtle text-secondary'}">
                            ${savings > 0 ? '-' + savings + '%' : '0%'}
                        </span>
                    </td>
                </tr>
            `;
        }).join('');
    },

    /**
     * Update labels for enhance sliders (Phase 4)
     */
    onEnhanceSliderInput(autoApply = false) {
        const sliders = ['brightness', 'contrast', 'saturation', 'sharpness', 'exposure', 'highlights', 'shadows', 'temperature', 'tint'];
        sliders.forEach(s => {
            const val = document.getElementById(`slider-enhance-${s}`).value;
            const lbl = document.getElementById(`val-enhance-${s}`);
            if (lbl) {
                lbl.textContent = (val > 0 && s !== 'sharpness' ? '+' : '') + val + (s === 'sharpness' ? '' : '%');
            }
        });

        if (autoApply) {
            if (this._enhanceTimeout) {
                clearTimeout(this._enhanceTimeout);
            }
            this._enhanceTimeout = setTimeout(() => {
                this.applyCustomEnhancements(true);
            }, 400); // 400ms debounce
        }
    },

    /**
     * Submit manual slider values to backend API
     */
    applyCustomEnhancements(isAuto = false) {
        if (!this.state.activeFilename) return;

        const btn = document.querySelector('[onclick="ImageLab.applyCustomEnhancements()"]');
        if (btn && !isAuto) btn.disabled = true;

        const params = new URLSearchParams();
        params.append('filename', this.state.activeFilename);
        params.append('brightness', document.getElementById('slider-enhance-brightness').value);
        params.append('contrast', document.getElementById('slider-enhance-contrast').value);
        params.append('saturation', document.getElementById('slider-enhance-saturation').value);
        params.append('sharpness', document.getElementById('slider-enhance-sharpness').value);
        params.append('exposure', document.getElementById('slider-enhance-exposure').value);
        params.append('highlights', document.getElementById('slider-enhance-highlights').value);
        params.append('shadows', document.getElementById('slider-enhance-shadows').value);
        params.append('temperature', document.getElementById('slider-enhance-temperature').value);
        params.append('tint', document.getElementById('slider-enhance-tint').value);

        fetch(`${ImageLabBaseUrl}/api/enhance.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: params
        })
        .then(res => res.json())
        .then(res => {
            if (res.success) {
                if (!isAuto) {
                    this.showAlert('enhance-alert-box', 'Sliders applied successfully.', 'success');
                }
                this.updateComparisonAfterImage(res.filename, res.original_size, res.new_size);
            } else {
                this.showAlert('enhance-alert-box', res.message || 'Enhancement failed.', 'danger');
            }
        })
        .catch(() => this.showAlert('enhance-alert-box', 'Network error applying enhancements.', 'danger'))
        .finally(() => { if (btn && !isAuto) btn.disabled = false; });
    },

    /**
     * Apply Auto-Enhance Preset
     */
    applyAutoPreset(mode) {
        if (empty(mode) || !this.state.activeFilename) return;

        const select = document.getElementById('enhance-auto-preset');
        select.disabled = true;

        const params = new URLSearchParams();
        params.append('filename', this.state.activeFilename);
        params.append('mode', mode);

        fetch(`${ImageLabBaseUrl}/api/enhance.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: params
        })
        .then(res => res.json())
        .then(res => {
            if (res.success) {
                this.showAlert('enhance-alert-box', `Auto preset "${mode.toUpperCase()}" applied.`, 'success');
                this.updateComparisonAfterImage(res.filename, res.original_size, res.new_size);
                
                // If this is a known auto preset, set sliders representation in UI
                const presets = {
                    'auto': { brightness: 10, contrast: 5, saturation: 5, sharpness: 10, exposure: 5, highlights: 0, shadows: 0, temperature: 0, tint: 0 },
                    'landscape': { brightness: 5, contrast: 15, saturation: 20, sharpness: 25, exposure: 0, highlights: 0, shadows: 0, temperature: 5, tint: 0 },
                    'portrait': { brightness: 15, contrast: -5, saturation: -5, sharpness: 5, exposure: 0, highlights: 0, shadows: 0, temperature: 0, tint: 5 },
                    'product': { brightness: 20, contrast: 10, saturation: 5, sharpness: 30, exposure: 0, highlights: 5, shadows: 0, temperature: 0, tint: 0 },
                    'social': { brightness: 10, contrast: 15, saturation: 15, sharpness: 15, exposure: 5, highlights: 0, shadows: 0, temperature: 0, tint: 0 }
                };

                const pData = presets[mode];
                if (pData) {
                    for (const [key, val] of Object.entries(pData)) {
                        const slider = document.getElementById(`slider-enhance-${key}`);
                        if (slider) slider.value = val;
                    }
                    this.onEnhanceSliderInput();
                }
            } else {
                this.showAlert('enhance-alert-box', res.message || 'Auto preset failed.', 'danger');
            }
        })
        .catch(() => this.showAlert('enhance-alert-box', 'Error applying auto preset.', 'danger'))
        .finally(() => { select.disabled = false; });
    },

    /**
     * Reset sliders
     */
    resetEnhanceSliders() {
        const sliders = ['brightness', 'contrast', 'saturation', 'sharpness', 'exposure', 'highlights', 'shadows', 'temperature', 'tint'];
        sliders.forEach(s => {
            const slider = document.getElementById(`slider-enhance-${s}`);
            if (slider) slider.value = 0;
        });
        document.getElementById('enhance-auto-preset').value = '';
        this.onEnhanceSliderInput(true);
    },

    /**
     * Save current sliders as a Custom Preset
     */
    saveCustomPreset() {
        const name = document.getElementById('custom-preset-name').value.trim();
        if (empty(name)) {
            this.showAlert('enhance-alert-box', 'Please enter a name for the custom preset.', 'warning');
            return;
        }

        const data = {
            brightness: parseInt(document.getElementById('slider-enhance-brightness').value),
            contrast: parseInt(document.getElementById('slider-enhance-contrast').value),
            saturation: parseInt(document.getElementById('slider-enhance-saturation').value),
            sharpness: parseInt(document.getElementById('slider-enhance-sharpness').value),
            exposure: parseInt(document.getElementById('slider-enhance-exposure').value),
            highlights: parseInt(document.getElementById('slider-enhance-highlights').value),
            shadows: parseInt(document.getElementById('slider-enhance-shadows').value),
            temperature: parseInt(document.getElementById('slider-enhance-temperature').value),
            tint: parseInt(document.getElementById('slider-enhance-tint').value)
        };

        const params = new URLSearchParams();
        params.append('action', 'save');
        params.append('preset_name', name);
        params.append('preset_data', JSON.stringify(data));

        fetch(`${ImageLabBaseUrl}/api/presets.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: params
        })
        .then(res => res.json())
        .then(res => {
            if (res.success) {
                this.showAlert('enhance-alert-box', 'Custom preset saved successfully.', 'success');
                document.getElementById('custom-preset-name').value = '';
                this.loadSavedPresetsList();
            } else {
                this.showAlert('enhance-alert-box', res.message || 'Failed to save preset.', 'danger');
            }
        })
        .catch(() => this.showAlert('enhance-alert-box', 'Network error saving preset.', 'danger'));
    },

    /**
     * Fetch custom presets and populate select list
     */
    loadSavedPresetsList() {
        fetch(`${ImageLabBaseUrl}/api/presets.php`)
        .then(res => res.json())
        .then(res => {
            if (res.success) {
                const select = document.getElementById('custom-preset-load-select');
                if (!select) return;

                select.innerHTML = '<option value="">-- Choose Custom Preset --</option>';
                const customs = res.customs || [];
                customs.forEach(p => {
                    const opt = document.createElement('option');
                    opt.value = p.id;
                    opt.dataset.json = p.preset_data;
                    opt.textContent = p.preset_name;
                    select.appendChild(opt);
                });
            }
        });
    },

    /**
     * Load values of a custom preset to UI and sliders
     */
    loadCustomPreset() {
        const select = document.getElementById('custom-preset-load-select');
        const opt = select.options[select.selectedIndex];
        if (!opt || !opt.value) return;

        try {
            const data = JSON.parse(opt.dataset.json);
            for (const [key, val] of Object.entries(data)) {
                const slider = document.getElementById(`slider-enhance-${key}`);
                if (slider) slider.value = val;
            }
            this.onEnhanceSliderInput();
            this.showAlert('enhance-alert-box', `Loaded preset: ${opt.textContent}`, 'info');
        } catch(e) {
            this.showAlert('enhance-alert-box', 'Error loading preset values.', 'danger');
        }
    },

    /**
     * Delete a custom preset
     */
    deleteCustomPreset() {
        const id = document.getElementById('custom-preset-load-select').value;
        if (empty(id)) return;

        if (!confirm('Are you sure you want to delete this custom preset?')) return;

        const params = new URLSearchParams();
        params.append('action', 'delete');
        params.append('id', id);

        fetch(`${ImageLabBaseUrl}/api/presets.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: params
        })
        .then(res => res.json())
        .then(res => {
            if (res.success) {
                this.showAlert('enhance-alert-box', 'Preset deleted successfully.', 'success');
                this.loadSavedPresetsList();
            } else {
                this.showAlert('enhance-alert-box', res.message || 'Failed to delete preset.', 'danger');
            }
        })
        .catch(() => this.showAlert('enhance-alert-box', 'Network error deleting preset.', 'danger'));
    },

    /**
     * Apply a Filter layout style
     */
    applyFilter(filterName) {
        if (!this.state.activeFilename) return;

        // Visual active state highlights
        document.querySelectorAll('#filter-gallery-cards .filter-thumb-card').forEach(card => {
            card.classList.remove('active');
        });
        
        // Find correct card to activate
        const eventCard = window.event ? window.event.currentTarget : null;
        if (eventCard) {
            eventCard.classList.add('active');
        }

        const params = new URLSearchParams();
        params.append('filename', this.state.activeFilename);
        params.append('filter', filterName);

        fetch(`${ImageLabBaseUrl}/api/filter.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: params
        })
        .then(res => res.json())
        .then(res => {
            if (res.success) {
                this.showAlert('enhance-alert-box', `Filter "${filterName.toUpperCase()}" applied.`, 'success');
                this.updateComparisonAfterImage(res.filename, res.original_size, res.new_size);
            } else {
                this.showAlert('enhance-alert-box', res.message || 'Filter application failed.', 'danger');
            }
        })
        .catch(() => this.showAlert('enhance-alert-box', 'Error applying filter.', 'danger'));
    },

    /**
     * Toggle watermark types inputs
     */
    toggleWatermarkType(type) {
        if (type === 'text') {
            document.getElementById('wm-options-text').classList.remove('d-none');
            document.getElementById('wm-options-logo').classList.add('d-none');
        } else {
            document.getElementById('wm-options-text').classList.add('d-none');
            document.getElementById('wm-options-logo').classList.remove('d-none');
        }
    },

    /**
     * Apply Watermark
     */
    applyWatermark() {
        if (!this.state.activeFilename) return;

        const isText = document.getElementById('wm-type-text').checked;
        const btn = document.querySelector('[onclick="ImageLab.applyWatermark()"]');
        btn.disabled = true;

        const formData = new FormData();
        formData.append('filename', this.state.activeFilename);
        formData.append('position', document.getElementById('wm-position').value);
        formData.append('offset_x', document.getElementById('wm-offset-x').value);
        formData.append('offset_y', document.getElementById('wm-offset-y').value);

        if (isText) {
            formData.append('type', 'text');
            formData.append('text', document.getElementById('wm-text-input').value || '© ImageLab Studio');
            formData.append('color', document.getElementById('wm-text-color').value);
            formData.append('size', document.getElementById('wm-text-size').value);
            formData.append('opacity', parseFloat(document.getElementById('wm-text-opacity').value) / 100);
            formData.append('rotation', document.getElementById('wm-text-rotation').value);
        } else {
            formData.append('type', 'logo');
            formData.append('opacity', parseFloat(document.getElementById('wm-logo-opacity').value) / 100);
            formData.append('scale', document.getElementById('wm-logo-scale').value);
            
            const logoUpload = document.getElementById('wm-logo-upload');
            if (logoUpload.files.length > 0) {
                formData.append('logo', logoUpload.files[0]);
            } else {
                this.showAlert('enhance-alert-box', 'Please choose a logo file first.', 'warning');
                btn.disabled = false;
                return;
            }
        }

        fetch(`${ImageLabBaseUrl}/api/watermark.php`, {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(res => {
            if (res.success) {
                this.showAlert('enhance-alert-box', 'Watermark overlay applied successfully.', 'success');
                this.updateComparisonAfterImage(res.filename, res.original_size, res.new_size);
            } else {
                this.showAlert('enhance-alert-box', res.message || 'Watermarking failed.', 'danger');
            }
        })
        .catch(() => this.showAlert('enhance-alert-box', 'Network error applying watermark.', 'danger'))
        .finally(() => { btn.disabled = false; });
    },

    /**
     * Inspect and render EXIF Metadata details
     */
    inspectMetadata() {
        if (!this.state.activeFilename) return;

        const emptyBox = document.getElementById('meta-empty-inspector');
        const detailBox = document.getElementById('meta-detail-table');
        const rows = document.getElementById('metadata-rows');

        fetch(`${ImageLabBaseUrl}/api/metadata.php?action=read&filename=${this.state.activeFilename}`)
        .then(res => res.json())
        .then(res => {
            if (res.success && res.has_exif) {
                emptyBox.classList.add('d-none');
                detailBox.classList.remove('d-none');

                rows.innerHTML = '';
                for (const [key, val] of Object.entries(res.fields)) {
                    const tr = document.createElement('tr');
                    
                    const tdKey = document.createElement('td');
                    tdKey.className = 'text-muted';
                    tdKey.textContent = key.replace(/([A-Z])/g, ' $1').trim();
                    
                    const tdVal = document.createElement('td');
                    tdVal.className = 'fw-semibold text-dark text-end';
                    
                    if (key === 'GPS') {
                        tdVal.textContent = val.formatted;
                        tdVal.title = 'GPS Coordinates found';
                    } else {
                        tdVal.textContent = val;
                    }

                    tr.appendChild(tdKey);
                    tr.appendChild(tdVal);
                    rows.appendChild(tr);
                }
            } else {
                emptyBox.textContent = res.message || 'No technical EXIF headers detected in this file.';
                emptyBox.classList.remove('d-none');
                detailBox.classList.add('d-none');
            }
        });
    },

    /**
     * Strip metadata headers
     */
    stripPrivacyHeaders() {
        if (!this.state.activeFilename) return;

        const btn = document.querySelector('[onclick="ImageLab.stripPrivacyHeaders()"]');
        btn.disabled = true;

        const params = new URLSearchParams();
        params.append('action', 'strip');
        params.append('filename', this.state.activeFilename);

        fetch(`${ImageLabBaseUrl}/api/metadata.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: params
        })
        .then(res => res.json())
        .then(res => {
            if (res.success) {
                this.showAlert('enhance-alert-box', 'EXIF metadata stripped successfully for privacy.', 'success');
                this.updateComparisonAfterImage(res.filename, res.original_size, res.new_size);
                
                // Clear metadata inspector UI
                document.getElementById('meta-empty-inspector').textContent = 'Metadata stripped.';
                document.getElementById('meta-empty-inspector').classList.remove('d-none');
                document.getElementById('meta-detail-table').classList.add('d-none');
            } else {
                this.showAlert('enhance-alert-box', 'Failed to strip metadata.', 'danger');
            }
        })
        .catch(() => this.showAlert('enhance-alert-box', 'Error stripping headers.', 'danger'))
        .finally(() => { btn.disabled = false; });
    },

    /**
     * Format/Quality sliders change triggers size estimates
     */
    onExportQualitySlider(val) {
        document.getElementById('val-export-quality').textContent = val + '%';
    },

    onExportParamsChanged() {
        if (!this.state.activeFilename) return;

        const format = document.getElementById('export-format').value;
        const quality = document.getElementById('export-quality').value;
        const qContainer = document.getElementById('export-quality-container');
        const estLabel = document.getElementById('export-est-size');

        // Hide quality slider for lossless or non-JPEG/WebP/AVIF formats
        if (['png', 'gif', 'svg', 'bmp', 'pdf', 'eps', 'tga', 'psd'].includes(format)) {
            qContainer.classList.add('d-none');
        } else {
            qContainer.classList.remove('d-none');
        }

        estLabel.textContent = 'Calculating...';

        const params = new URLSearchParams();
        params.append('filename', this.state.activeFilename);
        params.append('format', format);
        params.append('quality', quality);
        params.append('action', 'estimate');

        fetch(`${ImageLabBaseUrl}/api/export.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: params
        })
        .then(res => res.json())
        .then(res => {
            if (res.success) {
                estLabel.textContent = res.formatted_size;
            } else {
                estLabel.textContent = 'N/A';
            }
        })
        .catch(() => { estLabel.textContent = 'Error'; });
    },

    /**
     * Perform Final processed exports & background tasks
     */
    runFinalExport() {
        if (!this.state.activeFilename) return;

        const btn = document.querySelector('[onclick="ImageLab.runFinalExport()"]');
        btn.disabled = true;

        const params = new URLSearchParams();
        params.append('filename', this.state.activeFilename);
        params.append('format', document.getElementById('export-format').value);
        params.append('quality', document.getElementById('export-quality').value);
        params.append('action', 'process');

        // Solid background
        const solidBg = document.getElementById('export-bg-solid').value.trim();
        if (!empty(solidBg)) {
            params.append('solid_bg', solidBg);
        }

        // Color replace
        const oldC = document.getElementById('export-color-rep-old').value.trim();
        const newC = document.getElementById('export-color-rep-new').value.trim();
        const fuzz = document.getElementById('export-color-rep-fuzz').value;
        if (!empty(oldC) && !empty(newC)) {
            params.append('color_replace_old', oldC);
            params.append('color_replace_new', newC);
            params.append('color_replace_fuzz', fuzz);
        }

        // Expand canvas
        const expW = parseInt(document.getElementById('export-expand-w').value) || 0;
        const expH = parseInt(document.getElementById('export-expand-h').value) || 0;
        const expBg = document.getElementById('export-expand-bg').value.trim();
        const expGravity = document.getElementById('export-expand-gravity').value;
        if (expW > 0 && expH > 0) {
            params.append('expand_width', expW);
            params.append('expand_height', expH);
            params.append('expand_bg', expBg);
            params.append('expand_gravity', expGravity);
        }

        fetch(`${ImageLabBaseUrl}/api/export.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: params
        })
        .then(res => res.json())
        .then(res => {
            if (res.success) {
                this.showAlert('enhance-alert-box', 'Image optimized and exported successfully!', 'success');
                this.updateComparisonAfterImage(res.filename, res.original_size, res.new_size);
            } else {
                this.showAlert('enhance-alert-box', res.message || 'Export failed.', 'danger');
            }
        })
        .catch(() => this.showAlert('enhance-alert-box', 'Network error during export.', 'danger'))
        .finally(() => { btn.disabled = false; });
    },

    /**
     * Send current enhanced image directly to Fabric.js Canvas Editor Workspace
     */
    sendEnhanceToCanvas() {
        if (!this.state.activeProcessedFilename) return;

        const fileUrl = `${ImageLabBaseUrl}/api/download.php?file=${this.state.activeProcessedFilename}&type=processed`;
        
        if (typeof ImageLabCanvas !== 'undefined' && typeof ImageLabEditor !== 'undefined') {
            ImageLabCanvas.loadImage(fileUrl);
            ImageLabEditor.currentFileId = 'editor_' + Date.now();
            
            this.showAlert('enhance-alert-box', 'Image successfully loaded into Canvas Editor Workspace!', 'success');
            
            // Switch panel to editor tab
            setTimeout(() => {
                this.switchPanel('editor');
            }, 1000);
        }
    },

    /**
     * Helper to render outputs on the split-screen comparison slider
     */
    updateComparisonAfterImage(filename, originalSize, newSize) {
        this.state.activeProcessedFilename = filename;

        const beforeUrl = `${ImageLabBaseUrl}/api/download.php?file=${this.state.activeFilename}&type=uploads&t=${Date.now()}`;
        const afterUrl = `${ImageLabBaseUrl}/api/download.php?file=${filename}&type=processed&t=${Date.now()}`;

        // Update side-by-side preview images
        const compImgBefore = document.getElementById('comp-img-before');
        const compImgAfter = document.getElementById('comp-img-after');
        if (compImgBefore) compImgBefore.src = beforeUrl;
        if (compImgAfter) compImgAfter.src = afterUrl;

        const aiCompImgBefore = document.getElementById('ai-comp-img-before');
        const aiCompImgAfter = document.getElementById('ai-comp-img-after');
        if (aiCompImgBefore) aiCompImgBefore.src = beforeUrl;
        if (aiCompImgAfter) aiCompImgAfter.src = afterUrl;

        // Update metrics
        document.getElementById('enhance-meta-old-size').textContent = this.formatBytes(originalSize);
        document.getElementById('enhance-meta-new-size').textContent = this.formatBytes(newSize);
        
        const savings = Math.round((1 - (newSize / originalSize)) * 100);
        const pctEl = document.getElementById('enhance-meta-saved-pct');
        pctEl.textContent = savings > 0 ? `-${savings}%` : '0%';
        if (savings > 0) {
            pctEl.className = 'fw-semibold text-success text-end';
        } else {
            pctEl.className = 'fw-semibold text-muted text-end';
        }

        // Enable Canvas Editor transfer button and download link
        const cBtn = document.getElementById('btn-enhance-to-editor');
        if (cBtn) cBtn.disabled = false;

        const dBtn = document.getElementById('enhance-download-btn');
        if (dBtn) {
            dBtn.classList.remove('disabled');
            dBtn.href = `${ImageLabBaseUrl}/api/download.php?file=${filename}&type=processed&name=${encodeURIComponent(this.state.activeOriginalName)}`;
        }

        // Update dynamic EXIF and export calculations based on new processed image
        this.onExportParamsChanged();
        
        // Refresh dashboard history logs
        this.refreshAnalytics();
    },

    /**
     * Bind UI event loop handlers
     */
    init() {
        console.log('ImageLab Phase 2 Engine Init.');

        // Bind panel switching click actions
        const panels = ['dashboard', 'convert', 'resize', 'compress', 'enhance', 'editor', 'batch', 'ai', 'config', 'user-dashboard', 'billing', 'developer', 'admin', 'home', 'shop', 'product', 'about', 'contact'];
        panels.forEach(id => {
            const btn = document.getElementById(`nav-${id}`);
            if (btn) {
                btn.addEventListener('click', (e) => {
                    e.preventDefault();
                    this.switchPanel(id);
                });
            }
        });

        // Mobile Sidebar Toggle and Backdrop Setup
        const sidebarToggle = document.getElementById('sidebar-toggle');
        const sidebar = document.getElementById('sidebar');
        
        let backdrop = document.getElementById('sidebar-backdrop');
        if (!backdrop) {
            backdrop = document.createElement('div');
            backdrop.id = 'sidebar-backdrop';
            backdrop.className = 'sidebar-backdrop';
            document.body.appendChild(backdrop);
        }

        if (sidebarToggle && sidebar && backdrop) {
            sidebarToggle.addEventListener('click', () => {
                sidebar.classList.toggle('show');
                if (sidebar.classList.contains('show')) {
                    backdrop.classList.add('show');
                } else {
                    backdrop.classList.remove('show');
                }
            });

            backdrop.addEventListener('click', () => {
                sidebar.classList.remove('show');
                backdrop.classList.remove('show');
            });
        }

        // Bind Navbar Search Input for real-time history filtering
        const searchInput = document.getElementById('navbar-search-input');
        if (searchInput) {
            searchInput.addEventListener('input', (e) => {
                const query = e.target.value.toLowerCase().trim();
                if (!this.state.recentHistory) return;

                if (query === '') {
                    this.renderHistoryTable(this.state.recentHistory);
                } else {
                    const filtered = this.state.recentHistory.filter(log => {
                        const filename = (log.original_filename || '').toLowerCase();
                        const operation = (log.operation || '').toLowerCase();
                        return filename.includes(query) || operation.includes(query);
                    });
                    this.renderHistoryTable(filtered);
                }
            });
        }

        // Initialize drag-and-drop batch upload area
        const batchDropzone = document.getElementById('batch-dropzone');
        const batchUploader = document.getElementById('file-uploader-batch');

        if (batchDropzone && batchUploader) {
            batchDropzone.addEventListener('click', () => {
                batchUploader.click();
            });

            // Prevent default drag over animations
            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(ev => {
                batchDropzone.addEventListener(ev, (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                });
            });

            ['dragenter', 'dragover'].forEach(ev => {
                batchDropzone.addEventListener(ev, () => {
                    batchDropzone.style.borderColor = 'var(--primary)';
                    batchDropzone.style.backgroundColor = 'rgba(99, 102, 241, 0.05)';
                });
            });

            ['dragleave', 'drop'].forEach(ev => {
                batchDropzone.addEventListener(ev, () => {
                    batchDropzone.style.borderColor = 'var(--border)';
                    batchDropzone.style.backgroundColor = '#fafbfc';
                });
            });

            batchDropzone.addEventListener('drop', (e) => {
                const dt = e.dataTransfer;
                batchUploader.files = dt.files;
                this.showAlert('batch-alert-box', `${dt.files.length} files dropped. Press Enqueue to proceed.`, 'info');
            });

            batchUploader.addEventListener('change', () => {
                this.showAlert('batch-alert-box', `${batchUploader.files.length} files selected. Press Enqueue to proceed.`, 'info');
            });
        }

        // Initialize queue logs table and stats
        this.refreshQueueTable();
        this.refreshAnalytics();
        this.syncActiveFileUI();
        
        // Initialize Canvas Editor
        if (typeof ImageLabEditor !== 'undefined') {
            ImageLabEditor.init();
        }
    }
};

// Start application
document.addEventListener('DOMContentLoaded', () => {
    ImageLab.init();
});
