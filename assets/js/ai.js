/**
 * ImageLab AI Processing Client Script (Phase 5)
 */
Object.assign(ImageLab, {
    // Extend state to track active AI jobs and worker status
    aiState: {
        activeAIJobs: [],
        isWorkerRunning: false
    },

    /**
     * Inspect active file quality and generate AI tags/suggestions
     */
    inspectAIQualityAndTags() {
        if (!this.state.activeFilename) return;

        const tagsContainer = document.getElementById('ai-tags-container');
        const suggestionsContainer = document.getElementById('ai-suggestions-container');
        const scoreValue = document.getElementById('ai-score-value');
        const scoreCircle = document.getElementById('ai-score-circle');
        const scoreLabel = document.getElementById('ai-score-label');
        const breakdown = document.getElementById('ai-metrics-breakdown');

        if (tagsContainer) {
            tagsContainer.innerHTML = '<span class="badge bg-light text-secondary border"><i class="fa-solid fa-spinner fa-spin me-1"></i>Analyzing...</span>';
        }
        if (suggestionsContainer) {
            suggestionsContainer.innerHTML = '<div class="alert alert-light border p-2 small mb-0"><i class="fa-solid fa-spinner fa-spin me-1"></i>Evaluating...</div>';
        }
        if (scoreValue) scoreValue.textContent = '--';
        if (scoreCircle) scoreCircle.style.strokeDasharray = '0, 100';
        if (scoreLabel) scoreLabel.textContent = 'Analyzing image quality...';
        if (breakdown) breakdown.classList.add('d-none');

        fetch(`${ImageLabBaseUrl}/api/ai/tag.php?filename=${encodeURIComponent(this.state.activeFilename)}`)
            .then(res => res.json())
            .then(res => {
                if (res.success) {
                    // Update Quality Score gauge
                    const score = Math.round(res.quality_score);
                    if (scoreValue) scoreValue.textContent = score;
                    if (scoreCircle) {
                        scoreCircle.style.strokeDasharray = `${score}, 100`;
                        // Color color mapping: Green (>70), Orange (40-70), Red (<40)
                        if (score >= 70) {
                            scoreCircle.style.stroke = '#198754'; // Success green
                            if (scoreLabel) scoreLabel.textContent = `Excellent Quality (${score}/100)`;
                        } else if (score >= 40) {
                            scoreCircle.style.stroke = '#fd7e14'; // Orange
                            if (scoreLabel) scoreLabel.textContent = `Fair Quality (${score}/100)`;
                        } else {
                            scoreCircle.style.stroke = '#dc3545'; // Danger red
                            if (scoreLabel) scoreLabel.textContent = `Low Quality (${score}/100)`;
                        }
                    }

                    // Update metrics breakdown
                    if (breakdown && res.metrics) {
                        breakdown.classList.remove('d-none');
                        const sh = Math.round(res.metrics.sharpness || 0);
                        const ns = Math.round(res.metrics.noise || 0);
                        const ex = Math.round(res.metrics.exposure || 0);
                        const rs = Math.round(res.metrics.resolution || 0);

                        const shVal = document.getElementById('metric-sharpness-val');
                        const shBar = document.getElementById('metric-sharpness-bar');
                        if (shVal) shVal.textContent = sh + '%';
                        if (shBar) shBar.style.width = sh + '%';

                        const nsVal = document.getElementById('metric-noise-val');
                        const nsBar = document.getElementById('metric-noise-bar');
                        if (nsVal) nsVal.textContent = ns + '%';
                        if (nsBar) nsBar.style.width = ns + '%';

                        const exVal = document.getElementById('metric-exposure-val');
                        const exBar = document.getElementById('metric-exposure-bar');
                        if (exVal) exVal.textContent = ex + '%';
                        if (exBar) exBar.style.width = ex + '%';

                        const rsVal = document.getElementById('metric-resolution-val');
                        const rsBar = document.getElementById('metric-resolution-bar');
                        if (rsVal) rsVal.textContent = rs + '%';
                        if (rsBar) rsBar.style.width = rs + '%';
                    }

                    // Update tags
                    if (tagsContainer) {
                        tagsContainer.innerHTML = '';
                        if (res.tags && res.tags.length > 0) {
                            res.tags.forEach(tag => {
                                const badge = document.createElement('span');
                                badge.className = 'badge bg-primary text-white border me-1 mb-1 text-capitalize';
                                badge.innerHTML = `<i class="fa-solid fa-tag me-1"></i>${tag}`;
                                tagsContainer.appendChild(badge);
                            });
                        } else {
                            tagsContainer.innerHTML = '<span class="badge bg-light text-muted border">No tags generated.</span>';
                        }
                    }

                    // Update suggestions
                    if (suggestionsContainer) {
                        suggestionsContainer.innerHTML = '';
                        if (res.suggestions && res.suggestions.length > 0) {
                            res.suggestions.forEach(suggestion => {
                                const item = document.createElement('div');
                                item.className = 'alert alert-info border-0 shadow-sm p-2 mb-2 d-flex align-items-start gap-2 cursor-pointer';
                                item.style.fontSize = '12px';
                                item.style.borderRadius = '8px';
                                item.innerHTML = `
                                    <i class="fa-solid fa-circle-info mt-1 text-info"></i>
                                    <div>
                                        <strong>Suggested: ${suggestion.action}</strong>
                                        <p class="mb-0 text-muted" style="font-size: 11px;">${suggestion.reason}</p>
                                    </div>
                                `;
                                // Bind click handler to open the corresponding accordion collapse
                                item.addEventListener('click', () => {
                                    this.focusAITool(suggestion.action);
                                });
                                suggestionsContainer.appendChild(item);
                            });
                        } else {
                            suggestionsContainer.innerHTML = '<div class="alert alert-success border-0 p-2 small mb-0"><i class="fa-solid fa-circle-check me-1"></i>Image meets standard quality metrics! No corrections suggested.</div>';
                        }
                    }
                } else {
                    if (scoreLabel) scoreLabel.textContent = 'Evaluation failed: ' + (res.message || 'Unknown error');
                }
            })
            .catch(err => {
                if (scoreLabel) scoreLabel.textContent = 'Error analyzing quality.';
                console.error(err);
            });
    },

    /**
     * Map action suggestion string to an accordion panel collapse
     */
    focusAITool(action) {
        const accordion = document.getElementById('ai-toolbox-accordion');
        if (!accordion) return;

        let targetCollapseId = '';
        if (action.includes('Upscale') || action.includes('Resolution')) {
            targetCollapseId = 'collapse-ai-upscale';
        } else if (action.includes('Face')) {
            targetCollapseId = 'collapse-ai-face';
        } else if (action.includes('Background')) {
            targetCollapseId = 'collapse-ai-bg';
        } else if (action.includes('Auto Enhance') || action.includes('Exposure') || action.includes('Color')) {
            targetCollapseId = 'collapse-ai-auto';
        }

        if (targetCollapseId) {
            const element = document.getElementById(targetCollapseId);
            if (element) {
                const bsCollapse = bootstrap.Collapse.getOrCreateInstance(element);
                bsCollapse.show();
                element.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }
        }
    },

    /**
     * Apply selected AI Tool (Sync or Async Queue)
     */
    applyAITool(toolName) {
        if (!this.state.activeFilename) return;

        const isAsync = document.getElementById('ai-queue-toggle').checked;
        const alertBox = 'ai-alert-box';
        this.showAlert(alertBox, `<i class="fa-solid fa-spinner fa-spin me-2"></i>Processing ${toolName}... please wait.`, 'info');

        const params = new URLSearchParams();
        params.append('filename', this.state.activeFilename);
        params.append('mode', isAsync ? 'async' : 'sync');

        // Append tool-specific parameters
        if (toolName === 'upscale') {
            const scale = document.querySelector('input[name="ai-upscale-scale"]:checked').value;
            params.append('scale', scale);
        }

        fetch(`${ImageLabBaseUrl}/api/ai/${toolName}.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: params
        })
            .then(res => res.json())
            .then(res => {
                if (res.success) {
                    if (isAsync) {
                        // Job enqueued
                        this.aiState.activeAIJobs.push(res.job_id);
                        const friendlyName = toolName.replace('-', ' ').toUpperCase();
                        this.showAlert(alertBox, `<strong>AI Task Enqueued:</strong> ${friendlyName} processing in background...`, 'warning');
                        // Trigger queue worker immediately
                        this.startAIQueueWorker();
                    } else {
                        // Synchronous complete
                        const friendlyName = toolName.replace('-', ' ').toUpperCase();
                        this.showAlert(alertBox, `<strong>${friendlyName} Completed:</strong> Processed successfully.`, 'success');
                        this.displayAIResult(res);
                    }
                } else {
                    this.showAlert(alertBox, res.message || 'AI processing failed.', 'danger');
                }
            })
            .catch(err => {
                this.showAlert(alertBox, 'Network error during AI execution.', 'danger');
                console.error(err);
            });
    },

    /**
     * Display AI Result in Split Slider and update dimensions/metrics
     */
    displayAIResult(res) {
        // Show comparison viewport
        document.getElementById('ai-orig-empty').classList.add('d-none');
        document.getElementById('ai-comparison-viewport').classList.remove('d-none');

        const originalUrl = `${ImageLabBaseUrl}/api/download.php?file=${this.state.activeFilename}&type=uploads&t=${Date.now()}`;
        const processedUrl = `${ImageLabBaseUrl}/api/download.php?file=${res.filename}&type=processed&t=${Date.now()}`;

        // Update side-by-side preview images
        const compImgBefore = document.getElementById('ai-comp-img-before');
        const compImgAfter = document.getElementById('ai-comp-img-after');
        if (compImgBefore) compImgBefore.src = originalUrl;
        if (compImgAfter) compImgAfter.src = processedUrl;

        // Update Slider Images (if backup slider widget exists)
        if (typeof ImageLabComparison !== 'undefined' && document.getElementById('ai-slider-widget')) {
            ImageLabComparison.init('ai-slider-widget');
            ImageLabComparison.updateImages(originalUrl, processedUrl);
        }

        // Update metrics table
        document.getElementById('ai-orig-meta').textContent = `${res.width}x${res.height}px (${res.extension.toUpperCase()})`;
        document.getElementById('ai-meta-old-size').textContent = this.formatBytes(res.original_size);
        document.getElementById('ai-meta-new-size').textContent = this.formatBytes(res.new_size);

        const savings = Math.round((1 - (res.new_size / res.original_size)) * 100);
        document.getElementById('ai-meta-saved-pct').textContent = savings > 0 ? `${savings}%` : '0%';

        // Unlock download button
        const dBtn = document.getElementById('ai-download-btn');
        dBtn.classList.remove('disabled');
        dBtn.href = `${ImageLabBaseUrl}/api/download.php?file=${res.filename}&type=processed&name=${encodeURIComponent(this.state.activeOriginalName)}`;

        // Refresh system analytics
        this.refreshAnalytics();
    },

    /**
     * Start the client-driven background AI queue worker polling loop
     */
    startAIQueueWorker() {
        if (this.aiState.isWorkerRunning) return;
        this.aiState.isWorkerRunning = true;
        this.runQueueWorkerLoop();
    },

    /**
     * Internal queue worker loop function
     */
    runQueueWorkerLoop() {
        if (this.aiState.activeAIJobs.length === 0) {
            // No jobs to wait for
            this.aiState.isWorkerRunning = false;
            return;
        }

        // Trigger execution of the next queued job on the database queue
        fetch(`${ImageLabBaseUrl}/api/ai/queue.php?action=process_next`, {
            method: 'POST'
        })
            .then(res => res.json())
            .then(res => {
                if (res.success && res.data) {
                    const job = res.data;
                    const index = this.aiState.activeAIJobs.indexOf(job.id);

                    if (index !== -1) {
                        // This was one of our enqueued jobs!
                        this.aiState.activeAIJobs.splice(index, 1);

                        const opNames = {
                            'upscale_2x': 'AI Super Resolution (2x)',
                            'upscale_4x': 'AI Super Resolution (4x)',
                            'face_enhance': 'AI Face Restoration',
                            'remove_background': 'AI Background Removal',
                            'auto_enhance': 'AI Smart Auto-Enhance'
                        };
                        const friendlyOp = opNames[job.operation] || 'AI Processing';

                        if (job.status === 'completed') {
                            this.showAlert('ai-alert-box', `<strong>${friendlyOp} Completed:</strong> Processed successfully.`, 'success');
                            this.displayAIResult({
                                filename: job.result_path,
                                original_size: job.original_size,
                                new_size: job.new_size,
                                width: job.width,
                                height: job.height,
                                extension: job.extension
                            });
                        } else {
                            this.showAlert('ai-alert-box', `<strong>${friendlyOp} Failed:</strong> ${job.error_message}`, 'danger');
                        }
                    }

                    // Process next job immediately
                    setTimeout(() => this.runQueueWorkerLoop(), 1000);
                } else {
                    // Queue empty or processing finished, wait and poll again
                    setTimeout(() => this.runQueueWorkerLoop(), 3000);
                }
            })
            .catch(err => {
                console.error("Queue worker poll error:", err);
                // retry after delay
                setTimeout(() => this.runQueueWorkerLoop(), 5000);
            });
    },

    /**
     * Cancel all queued and processing AI jobs
     */
    cancelAIQueue() {
        if (!confirm('Are you sure you want to cancel all pending and active queue jobs?')) return;
        
        fetch(`${ImageLabBaseUrl}/api/ai/queue.php?action=cancel_all`, {
            method: 'POST'
        })
            .then(res => res.json())
            .then(res => {
                if (res.success) {
                    this.aiState.activeAIJobs = [];
                    this.showAlert('ai-alert-box', res.message, 'success');
                } else {
                    this.showAlert('ai-alert-box', res.message || 'Failed to cancel queue jobs.', 'danger');
                }
            })
            .catch(err => {
                this.showAlert('ai-alert-box', 'Network error while cancelling queue.', 'danger');
                console.error(err);
            });
    },

    /**
     * Clear all completed and failed queue jobs from the database history
     */
    clearAIQueueHistory() {
        if (!confirm('Are you sure you want to delete all queue jobs from history? This cannot be undone.')) return;
        
        fetch(`${ImageLabBaseUrl}/api/ai/queue.php?action=clear_all`, {
            method: 'POST'
        })
            .then(res => res.json())
            .then(res => {
                if (res.success) {
                    this.aiState.activeAIJobs = [];
                    this.showAlert('ai-alert-box', res.message, 'success');
                } else {
                    this.showAlert('ai-alert-box', res.message || 'Failed to clear queue history.', 'danger');
                }
            })
            .catch(err => {
                this.showAlert('ai-alert-box', 'Network error while clearing queue history.', 'danger');
                console.error(err);
            });
    }
});
