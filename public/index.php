<?php
// Load configuration
require_once __DIR__ . '/../core/Config.php';

// Trigger automatic cleanup of files older than 24 hours on page load
require_once __DIR__ . '/../core/FileManager.php';
$fileManager = new FileManager();
$cleanedFiles = $fileManager->cleanupOldFiles();

// Include Header Layout
include_once __DIR__ . '/../includes/header.php';

// Include Sidebar Layout
include_once __DIR__ . '/../includes/sidebar.php';
?>

<!-- Main Content Area -->
<div class="main-content">
    <?php 
    // Include Top Navbar
    include_once __DIR__ . '/../includes/navbar.php'; 
    ?>
    
    <!-- Page Body Content -->
    <main class="page-body-content">

        <!-- ================= PANEL 1: DASHBOARD ================= -->
        <div id="panel-dashboard" class="dashboard-panel">
            <div class="row mb-4 align-items-center">
                <div class="col-sm-7">
                    <h2 class="h4 mb-1 fw-bold text-dark">System Analytics</h2>
                    <p class="text-muted mb-0" style="font-size: 14px;">Real-time processing statistics, database tracking, and historical metrics.</p>
                </div>
                <div class="col-sm-5 text-sm-end mt-3 mt-sm-0">
                    <button class="btn btn-light border btn-sm px-3" onclick="ImageLab.refreshAnalytics()"><i class="fa-solid fa-arrows-rotate me-1"></i> Refresh Stats</button>
                </div>
            </div>

            <!-- Analytics Cards Row -->
            <div class="row g-3 mb-4">
                <div class="col-md-3 col-sm-6">
                    <div class="card card-premium p-3 border-0 bg-white">
                        <div class="d-flex align-items-center gap-3">
                            <div class="bg-primary-subtle text-primary rounded-3 p-3 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                                <i class="fa-solid fa-cloud-arrow-up fs-5"></i>
                            </div>
                            <div>
                                <span class="text-muted d-block small mb-1">Total Uploads</span>
                                <h4 class="fw-bold text-dark mb-0" id="stat-total-uploads">0</h4>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="card card-premium p-3 border-0 bg-white">
                        <div class="d-flex align-items-center gap-3">
                            <div class="bg-success-subtle text-success rounded-3 p-3 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                                <i class="fa-solid fa-arrows-rotate fs-5"></i>
                            </div>
                            <div>
                                <span class="text-muted d-block small mb-1">Conversions</span>
                                <h4 class="fw-bold text-dark mb-0" id="stat-total-conversions">0</h4>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="card card-premium p-3 border-0 bg-white">
                        <div class="d-flex align-items-center gap-3">
                            <div class="bg-warning-subtle text-warning rounded-3 p-3 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                                <i class="fa-solid fa-hard-drive fs-5"></i>
                            </div>
                            <div>
                                <span class="text-muted d-block small mb-1">Storage Saved</span>
                                <h4 class="fw-bold text-dark mb-0" id="stat-storage-saved">0 KB</h4>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="card card-premium p-3 border-0 bg-white">
                        <div class="d-flex align-items-center gap-3">
                            <div class="bg-info-subtle text-info rounded-3 p-3 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;" id="stat-db-icon-container">
                                <i class="fa-solid fa-database fs-5"></i>
                            </div>
                            <div>
                                <span class="text-muted d-block small mb-1">Database Mode</span>
                                <h4 class="fw-bold text-dark mb-0" style="font-size: 14px;" id="stat-database-status">Checking...</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Database Fallback Alert -->
            <div id="db-warning-alert" class="alert alert-warning border-0 shadow-sm d-none align-items-center gap-2 mb-4" role="alert">
                <i class="fa-solid fa-triangle-exclamation text-warning fs-5"></i>
                <div style="font-size: 13px;">
                    <strong>Database Offline Mode</strong>: ImageLab is currently saving transaction logs and processing queues using local fallback files because MySQL is offline. Set up the MySQL database using <code>schema.sql</code> and configure <code>core/Config.php</code> to activate real SQL tracking.
                </div>
            </div>

            <!-- Analytics Details & Recent Tables -->
            <div class="row g-4">
                <div class="col-lg-8">
                    <div class="card card-premium border-0">
                        <div class="card-body p-4">
                            <h3 class="card-title-main mb-3"><i class="fa-solid fa-history text-primary me-2"></i>Global Conversion History (Database)</h3>
                            <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                                <table class="table table-hover align-middle mb-0" style="font-size: 13px;">
                                    <thead class="table-light">
                                        <tr class="text-muted">
                                            <th>Timestamp</th>
                                            <th>Operation</th>
                                            <th>Original Name</th>
                                            <th>New Size</th>
                                            <th class="text-end">Efficiency</th>
                                        </tr>
                                    </thead>
                                    <tbody id="db-history-tbody">
                                        <tr>
                                            <td colspan="5" class="text-center text-muted py-4">No records found.</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <!-- Preset Quick Apply Card -->
                    <div class="card card-premium border-0 mb-4">
                        <div class="card-body p-4">
                            <h3 class="card-title-main mb-3"><i class="fa-solid fa-sliders text-primary me-2"></i>Apply Presets</h3>
                            <p class="text-muted small">Instantly convert and resize active uploads using preset rules.</p>
                            
                            <div class="mb-3">
                                <label for="dashboard-preset-select" class="form-label fw-semibold" style="font-size: 12px;">Select Preset</label>
                                <select class="form-select" id="dashboard-preset-select">
                                    <option value="web_optimized">Web Optimized (WEBP, 80% Q, Max Width 1920)</option>
                                    <option value="social_media">Social Media Square (JPG, 85% Q, 1080x1080)</option>
                                    <option value="thumbnail">Thumbnail (WEBP, 70% Q, 500x500)</option>
                                </select>
                            </div>
                            
                            <button class="btn btn-primary w-100 py-2 fw-semibold" id="dashboard-apply-preset-btn" onclick="ImageLab.applyPresetQuick()">
                                Apply Preset Settings
                            </button>
                            <div id="preset-alert-container" class="mt-3"></div>
                        </div>
                    </div>
                    
                    <!-- System Cleanups Info -->
                    <div class="card card-premium border-0 p-3 bg-light-subtle">
                        <div class="d-flex align-items-center gap-3">
                            <div class="bg-secondary-subtle text-secondary rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                                <i class="fa-solid fa-broom small"></i>
                            </div>
                            <div>
                                <h4 class="h6 mb-0 fw-semibold text-dark">File Garbage Cleanup</h4>
                                <span class="text-muted" style="font-size: 11px;">Temp & processed files cleared every 24 hours automatically on access.</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>


        <!-- ================= PANEL 2: CONVERT FORMAT ================= -->
        <div id="panel-convert" class="dashboard-panel d-none">
            <h2 class="h4 mb-4 fw-bold text-dark"><i class="fa-solid fa-arrows-rotate text-primary me-2"></i>Convert Image Format</h2>
            
            <!-- Shared Active File Header -->
            <div id="active-file-header-convert" class="alert alert-primary border-0 shadow-sm d-none align-items-center justify-content-between mb-4">
                <div class="d-flex align-items-center gap-2">
                    <i class="fa-solid fa-file-circle-check fs-5"></i>
                    <span class="small">Active File uploaded: <strong id="active-filename-convert">cat.jpg</strong></span>
                </div>
                <button class="btn btn-sm btn-outline-primary" onclick="ImageLab.resetActiveFile()">Upload New File</button>
            </div>

            <div class="row g-4">
                <!-- Column 1: Upload and Controls -->
                <div class="col-lg-6">
                    <!-- Upload Dropzone Card (Visible if no active file) -->
                    <div class="card card-premium border-0 mb-4" id="convert-upload-card">
                        <div class="card-body p-4">
                            <h3 class="card-title-main mb-3">Upload File</h3>
                            <div class="upload-dropzone-box" onclick="document.getElementById('file-uploader-convert').click()">
                                <div class="icon-container"><i class="fa-solid fa-cloud-arrow-up"></i></div>
                                <h4 class="h6 fw-semibold mb-2">Drag and drop file here</h4>
                                <p class="text-muted small mb-0">Supports JPG, PNG, WEBP, GIF, SVG, RAW, PSD, PDF, AI, HEIC, TGA, etc. (Max 10MB)</p>
                                <input type="file" id="file-uploader-convert" class="d-none" accept=".png,.jpg,.jpeg,.gif,.webp,.svg,.bmp,.tiff,.tif,.ico,.heic,.heif,.avif,.raw,.cr2,.cr3,.nef,.arw,.dng,.orf,.raf,.psd,.ai,.eps,.pdf,.tga,.exr,.hdr,.jfif" onchange="ImageLab.handleFileSelect(this.files[0])">
                            </div>
                            <div id="progress-container-convert" class="mt-3 d-none">
                                <div class="progress progress-premium"><div class="progress-bar bg-primary" id="progress-bar-convert" style="width: 0%;"></div></div>
                            </div>
                        </div>
                    </div>

                    <!-- Convert Controls Card -->
                    <div class="card card-premium border-0">
                        <div class="card-body p-4">
                            <h3 class="card-title-main mb-3">Select Target Format</h3>
                            <div class="mb-3">
                                <select class="form-select" id="convert-target-select">
                                    <option value="webp">WEBP (Highly compressed)</option>
                                    <option value="png">PNG (Lossless quality)</option>
                                    <option value="jpg">JPG (Standard format)</option>
                                    <option value="gif">GIF (Animation)</option>
                                    <option value="svg">SVG (Vector container)</option>
                                    <option value="bmp">BMP (Windows Bitmap)</option>
                                    <option value="tiff">TIFF (Tagged Image File)</option>
                                    <option value="ico">ICO (Windows Icon)</option>
                                    <option value="heic">HEIC (High Efficiency Image)</option>
                                    <option value="avif">AVIF (AV1 Image format)</option>
                                    <option value="psd">PSD (Photoshop Document)</option>
                                    <option value="pdf">PDF (Portable Document Format)</option>
                                    <option value="eps">EPS (Encapsulated PostScript)</option>
                                    <option value="tga">TGA (Truevision Targa)</option>
                                    <option value="exr">EXR (OpenEXR HDR)</option>
                                    <option value="hdr">HDR (Radiance HDR)</option>
                                    <option value="jfif">JFIF (JPEG File Interchange)</option>
                                </select>
                            </div>
                            <button class="btn btn-primary w-100 py-2 fw-semibold" id="execute-convert-btn" onclick="ImageLab.runConvert()">
                                <i class="fa-solid fa-arrows-spin me-2"></i>Convert Format
                            </button>
                            <div id="convert-alert-box" class="mt-3"></div>
                        </div>
                    </div>
                </div>

                <!-- Column 2: Previews -->
                <div class="col-lg-6">
                    <!-- Original Preview -->
                    <div class="card card-premium border-0 mb-4" id="convert-preview-orig-card">
                        <div class="card-body p-4">
                            <h3 class="card-title-main mb-3">Original Image</h3>
                            <div id="convert-orig-empty" class="preview-empty-state"><i class="fa-regular fa-image"></i><p class="small mb-0">Upload file to see preview</p></div>
                            <div id="convert-orig-content" class="d-none">
                                <div class="preview-image-container mb-3"><img id="convert-orig-img" src="" alt="Original"></div>
                                <span class="badge bg-light text-dark border p-2" id="convert-orig-meta">Dimensions: 0x0</span>
                            </div>
                        </div>
                    </div>

                    <!-- Converted Preview -->
                    <div class="card card-premium border-0" id="convert-preview-conv-card">
                        <div class="card-body p-4">
                            <h3 class="card-title-main mb-3">Converted Image</h3>
                            <div id="convert-conv-empty" class="preview-empty-state"><i class="fa-solid fa-arrows-spin"></i><p class="small mb-0">Execute convert to view processed image</p></div>
                            <div id="convert-conv-content" class="d-none">
                                <div class="preview-image-container mb-3"><img id="convert-conv-img" src="" alt="Converted"></div>
                                <div class="d-flex align-items-center justify-content-between mb-3">
                                    <span class="badge bg-success-subtle text-success border p-2" id="convert-conv-meta">Size Saved: 0%</span>
                                    <span class="text-muted small" id="convert-conv-metrics">0 KB</span>
                                </div>
                                <a id="convert-download-btn" href="#" class="btn btn-success w-100 py-2 fw-semibold"><i class="fa-solid fa-cloud-arrow-down me-2"></i>Download Image</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>


        <!-- ================= PANEL 3: RESIZE & CROP ================= -->
        <div id="panel-resize" class="dashboard-panel d-none">
            <h2 class="h4 mb-4 fw-bold text-dark"><i class="fa-solid fa-maximize text-primary me-2"></i>Advanced Image Resizer</h2>

            <!-- Shared Active File Header -->
            <div id="active-file-header-resize" class="alert alert-primary border-0 shadow-sm d-none align-items-center justify-content-between mb-4">
                <div class="d-flex align-items-center gap-2">
                    <i class="fa-solid fa-file-circle-check fs-5"></i>
                    <span class="small">Active File: <strong id="active-filename-resize">cat.jpg</strong></span>
                </div>
                <button class="btn btn-sm btn-outline-primary" onclick="ImageLab.resetActiveFile()">Upload New File</button>
            </div>

            <div class="row g-4">
                <!-- Column 1: Controls -->
                <div class="col-lg-6">
                    <!-- Upload Dropzone Card (Visible if no active file) -->
                    <div class="card card-premium border-0 mb-4" id="resize-upload-card">
                        <div class="card-body p-4">
                            <h3 class="card-title-main mb-3">Upload File</h3>
                            <div class="upload-dropzone-box" onclick="document.getElementById('file-uploader-resize').click()">
                                <div class="icon-container"><i class="fa-solid fa-cloud-arrow-up"></i></div>
                                <h4 class="h6 fw-semibold mb-2">Drag and drop file here</h4>
                                <p class="text-muted small mb-0">Supports JPG, PNG, WEBP, GIF, SVG, RAW, PSD, PDF, AI, HEIC, TGA, etc. (Max 10MB)</p>
                                <input type="file" id="file-uploader-resize" class="d-none" accept=".png,.jpg,.jpeg,.gif,.webp,.svg,.bmp,.tiff,.tif,.ico,.heic,.heif,.avif,.raw,.cr2,.cr3,.nef,.arw,.dng,.orf,.raf,.psd,.ai,.eps,.pdf,.tga,.exr,.hdr,.jfif" onchange="ImageLab.handleFileSelect(this.files[0])">
                            </div>
                            <div id="progress-container-resize" class="mt-3 d-none">
                                <div class="progress progress-premium"><div class="progress-bar bg-primary" id="progress-bar-resize" style="width: 0%;"></div></div>
                            </div>
                        </div>
                    </div>

                    <!-- Resizer Options Card -->
                    <div class="card card-premium border-0">
                        <div class="card-body p-4">
                            <h3 class="card-title-main mb-3">Resize Configuration</h3>
                            
                            <!-- Preset selector -->
                            <div class="mb-3">
                                <label for="resize-preset-select" class="form-label fw-semibold" style="font-size: 13px;">Social & Web Presets</label>
                                <select class="form-select" id="resize-preset-select" onchange="ImageLab.applyDimensionPreset(this.value)">
                                    <option value="" selected>-- Custom Dimensions --</option>
                                    <optgroup label="Social Media">
                                        <option value="instagram_post">Instagram Post (1080x1080)</option>
                                        <option value="instagram_story">Instagram Story (1080x1920)</option>
                                        <option value="facebook_post">Facebook Post (1200x630)</option>
                                        <option value="youtube_thumbnail">YouTube Thumbnail (1280x720)</option>
                                        <option value="tiktok_cover">TikTok Cover (1080x1920)</option>
                                    </optgroup>
                                    <optgroup label="Web Development">
                                        <option value="thumbnail_150">Thumbnail (150x150)</option>
                                        <option value="medium_500">Medium (500x500)</option>
                                        <option value="large_1200">Large (1200x1200)</option>
                                    </optgroup>
                                </select>
                            </div>

                            <!-- Dimension Inputs -->
                            <div class="row g-2 mb-3">
                                <div class="col-6">
                                    <label for="resize-width" class="form-label small text-muted">Width (px)</label>
                                    <input type="number" class="form-control" id="resize-width" value="800" min="1" oninput="ImageLab.updateResizePreview()">
                                </div>
                                <div class="col-6">
                                    <label for="resize-height" class="form-label small text-muted">Height (px)</label>
                                    <input type="number" class="form-control" id="resize-height" value="600" min="1" oninput="ImageLab.updateResizePreview()">
                                </div>
                            </div>

                            <!-- Aspect Ratio Checkbox -->
                            <div class="form-check form-switch mb-4">
                                <input class="form-check-input" type="checkbox" role="switch" id="resize-ratio-checkbox" checked onchange="ImageLab.updateResizePreview()">
                                <label class="form-check-label text-dark small" for="resize-ratio-checkbox">Maintain Aspect Ratio</label>
                            </div>

                            <!-- Live preview of dimensions -->
                            <div class="bg-light p-3 rounded mb-4 text-center border" style="font-size: 13px;">
                                <span class="text-muted">Target Output Size:</span>
                                <strong class="text-primary d-block mt-1 fs-5" id="resize-live-preview-display">800 x 600 px (Preserved)</strong>
                            </div>

                            <button class="btn btn-primary w-100 py-2 fw-semibold" id="execute-resize-btn" onclick="ImageLab.runResize()">
                                <i class="fa-solid fa-maximize me-2"></i>Resize Image
                            </button>
                            <div id="resize-alert-box" class="mt-3"></div>
                        </div>
                    </div>
                </div>

                <!-- Column 2: Previews -->
                <div class="col-lg-6">
                    <!-- Original Preview -->
                    <div class="card card-premium border-0 mb-4" id="resize-preview-orig-card">
                        <div class="card-body p-4">
                            <h3 class="card-title-main mb-3">Original Image</h3>
                            <div id="resize-orig-empty" class="preview-empty-state"><i class="fa-regular fa-image"></i><p class="small mb-0">Upload file to see preview</p></div>
                            <div id="resize-orig-content" class="d-none">
                                <div class="preview-image-container mb-3"><img id="resize-orig-img" src="" alt="Original"></div>
                                <span class="badge bg-light text-dark border p-2" id="resize-orig-meta">Dimensions: 0x0</span>
                            </div>
                        </div>
                    </div>

                    <!-- Resized Preview -->
                    <div class="card card-premium border-0" id="resize-preview-conv-card">
                        <div class="card-body p-4">
                            <h3 class="card-title-main mb-3">Resized Image Output</h3>
                            <div id="resize-conv-empty" class="preview-empty-state"><i class="fa-solid fa-arrows-spin"></i><p class="small mb-0">Execute resize to view processed image</p></div>
                            <div id="resize-conv-content" class="d-none">
                                <div class="preview-image-container mb-3"><img id="resize-conv-img" src="" alt="Resized"></div>
                                <div class="d-flex align-items-center justify-content-between mb-3">
                                    <span class="badge bg-success-subtle text-success border p-2" id="resize-conv-meta">New Dimensions: 800x600</span>
                                    <span class="text-muted small" id="resize-conv-metrics">0 KB</span>
                                </div>
                                <a id="resize-download-btn" href="#" class="btn btn-success w-100 py-2 fw-semibold"><i class="fa-solid fa-cloud-arrow-down me-2"></i>Download Resized Image</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>


        <!-- ================= PANEL 4: COMPRESSION ================= -->
        <div id="panel-compress" class="dashboard-panel d-none">
            <h2 class="h4 mb-4 fw-bold text-dark"><i class="fa-solid fa-compress text-primary me-2"></i>Image Compression & Optimization</h2>

            <!-- Shared Active File Header -->
            <div id="active-file-header-compress" class="alert alert-primary border-0 shadow-sm d-none align-items-center justify-content-between mb-4">
                <div class="d-flex align-items-center gap-2">
                    <i class="fa-solid fa-file-circle-check fs-5"></i>
                    <span class="small">Active File: <strong id="active-filename-compress">cat.jpg</strong></span>
                </div>
                <button class="btn btn-sm btn-outline-primary" onclick="ImageLab.resetActiveFile()">Upload New File</button>
            </div>

            <div class="row g-4">
                <!-- Column 1: Controls -->
                <div class="col-lg-6">
                    <!-- Upload Dropzone Card (Visible if no active file) -->
                    <div class="card card-premium border-0 mb-4" id="compress-upload-card">
                        <div class="card-body p-4">
                            <h3 class="card-title-main mb-3">Upload File</h3>
                            <div class="upload-dropzone-box" onclick="document.getElementById('file-uploader-compress').click()">
                                <div class="icon-container"><i class="fa-solid fa-cloud-arrow-up"></i></div>
                                <h4 class="h6 fw-semibold mb-2">Drag and drop file here</h4>
                                <p class="text-muted small mb-0">Supports JPG, PNG, WEBP, GIF, SVG, RAW, PSD, PDF, AI, HEIC, TGA, etc. (Max 10MB)</p>
                                <input type="file" id="file-uploader-compress" class="d-none" accept=".png,.jpg,.jpeg,.gif,.webp,.svg,.bmp,.tiff,.tif,.ico,.heic,.heif,.avif,.raw,.cr2,.cr3,.nef,.arw,.dng,.orf,.raf,.psd,.ai,.eps,.pdf,.tga,.exr,.hdr,.jfif" onchange="ImageLab.handleFileSelect(this.files[0])">
                            </div>
                            <div id="progress-container-compress" class="mt-3 d-none">
                                <div class="progress progress-premium"><div class="progress-bar bg-primary" id="progress-bar-compress" style="width: 0%;"></div></div>
                            </div>
                        </div>
                    </div>

                    <!-- Compression Settings Card -->
                    <div class="card card-premium border-0">
                        <div class="card-body p-4">
                            <h3 class="card-title-main mb-3">Compression Levels</h3>
                            <p class="text-muted small">Choose optimization configuration settings to strip metadata and compress.</p>
                            
                            <!-- Compression Selector Cards -->
                            <div class="row g-2 mb-4">
                                <div class="col-6">
                                    <div class="card p-3 text-center border-2 btn-outline-primary btn cursor-pointer active" id="btn-comp-low" onclick="ImageLab.selectCompressionLevel('low')">
                                        <h4 class="h6 mb-1 fw-bold text-dark">Low</h4>
                                        <span class="text-muted" style="font-size: 10px;">High Quality (85%)</span>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="card p-3 text-center border btn-outline-primary btn cursor-pointer" id="btn-comp-medium" onclick="ImageLab.selectCompressionLevel('medium')">
                                        <h4 class="h6 mb-1 fw-bold text-dark">Medium</h4>
                                        <span class="text-muted" style="font-size: 10px;">Balanced (65%)</span>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="card p-3 text-center border btn-outline-primary btn cursor-pointer" id="btn-comp-high" onclick="ImageLab.selectCompressionLevel('high')">
                                        <h4 class="h6 mb-1 fw-bold text-dark">High</h4>
                                        <span class="text-muted" style="font-size: 10px;">Strong Size Cut (45%)</span>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="card p-3 text-center border btn-outline-primary btn cursor-pointer" id="btn-comp-max" onclick="ImageLab.selectCompressionLevel('max')">
                                        <h4 class="h6 mb-1 fw-bold text-dark">Maximum</h4>
                                        <span class="text-muted" style="font-size: 10px;">Extreme Compression (25%)</span>
                                    </div>
                                </div>
                            </div>

                            <input type="hidden" id="selected-compression-level" value="low">

                            <button class="btn btn-primary w-100 py-2 fw-semibold" id="execute-compress-btn" onclick="ImageLab.runCompress()">
                                <i class="fa-solid fa-compress me-2"></i>Compress Image
                            </button>
                            <div id="compress-alert-box" class="mt-3"></div>
                        </div>
                    </div>
                </div>

                <!-- Column 2: Previews -->
                <div class="col-lg-6">
                    <!-- Original Preview -->
                    <div class="card card-premium border-0 mb-4" id="compress-preview-orig-card">
                        <div class="card-body p-4">
                            <h3 class="card-title-main mb-3">Original Image</h3>
                            <div id="compress-orig-empty" class="preview-empty-state"><i class="fa-regular fa-image"></i><p class="small mb-0">Upload file to see preview</p></div>
                            <div id="compress-orig-content" class="d-none">
                                <div class="preview-image-container mb-3"><img id="compress-orig-img" src="" alt="Original"></div>
                                <span class="badge bg-light text-dark border p-2" id="compress-orig-meta">Dimensions: 0x0</span>
                            </div>
                        </div>
                    </div>

                    <!-- Compressed Preview -->
                    <div class="card card-premium border-0" id="compress-preview-conv-card">
                        <div class="card-body p-4">
                            <h3 class="card-title-main mb-3">Compressed Image Output</h3>
                            <div id="compress-conv-empty" class="preview-empty-state"><i class="fa-solid fa-arrows-spin"></i><p class="small mb-0">Execute compress to view processed image</p></div>
                            <div id="compress-conv-content" class="d-none">
                                <div class="preview-image-container mb-3"><img id="compress-conv-img" src="" alt="Compressed"></div>
                                <div class="table-responsive mb-3">
                                    <table class="table table-sm table-borderless small mb-0">
                                        <tbody>
                                            <tr class="border-bottom">
                                                <td class="text-muted">Original Size</td>
                                                <td class="fw-semibold text-dark text-end text-decoration-line-through" id="compress-meta-old-size">0 KB</td>
                                            </tr>
                                            <tr class="border-bottom">
                                                <td class="text-muted">Compressed Size</td>
                                                <td class="fw-semibold text-success text-end" id="compress-meta-new-size">0 KB</td>
                                            </tr>
                                            <tr>
                                                <td class="text-muted">Total Savings</td>
                                                <td class="fw-bold text-success text-end" id="compress-meta-saved-pct">0%</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <a id="compress-download-btn" href="#" class="btn btn-success w-100 py-2 fw-semibold"><i class="fa-solid fa-cloud-arrow-down me-2"></i>Download Compressed Image</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>


        <!-- ================= PANEL 4B: ENHANCEMENT ================= -->
        <div id="panel-enhance" class="dashboard-panel d-none">
            <h2 class="h4 mb-4 fw-bold text-dark"><i class="fa-solid fa-wand-magic-sparkles text-primary me-2"></i>Image Optimization & Enhancement</h2>

            <!-- Shared Active File Header -->
            <div id="active-file-header-enhance" class="alert alert-primary border-0 shadow-sm d-none align-items-center justify-content-between mb-4">
                <div class="d-flex align-items-center gap-2">
                    <i class="fa-solid fa-file-circle-check fs-5"></i>
                    <span class="small">Active File: <strong id="active-filename-enhance">cat.jpg</strong></span>
                </div>
                <button class="btn btn-sm btn-outline-primary" onclick="ImageLab.resetActiveFile()">Upload New File</button>
            </div>

            <div class="row g-4">
                <!-- Column 1: Control Accordion -->
                <div class="col-lg-5">
                    <!-- Upload Dropzone Card (Visible if no active file) -->
                    <div class="card card-premium border-0 mb-4" id="enhance-upload-card">
                        <div class="card-body p-4">
                            <h3 class="card-title-main mb-3">Upload File</h3>
                            <div class="upload-dropzone-box" onclick="document.getElementById('file-uploader-enhance').click()">
                                <div class="icon-container"><i class="fa-solid fa-cloud-arrow-up"></i></div>
                                <h4 class="h6 fw-semibold mb-2">Drag and drop file here</h4>
                                <p class="text-muted small mb-0">Supports JPG, PNG, WEBP, GIF, SVG, RAW, PSD, PDF, AI, HEIC, TGA, etc. (Max 10MB)</p>
                                <input type="file" id="file-uploader-enhance" class="d-none" accept=".png,.jpg,.jpeg,.gif,.webp,.svg,.bmp,.tiff,.tif,.ico,.heic,.heif,.avif,.raw,.cr2,.cr3,.nef,.arw,.dng,.orf,.raf,.psd,.ai,.eps,.pdf,.tga,.exr,.hdr,.jfif" onchange="ImageLab.handleFileSelect(this.files[0])">
                            </div>
                            <div id="progress-container-enhance" class="mt-3 d-none">
                                <div class="progress progress-premium"><div class="progress-bar bg-primary" id="progress-bar-enhance" style="width: 0%;"></div></div>
                            </div>
                        </div>
                    </div>

                    <!-- Accordion Toolbox (Disabled until image uploaded) -->
                    <div class="accordion accordion-flush" id="enhance-toolbox-accordion">
                        
                        <!-- Accordion 1: Color Adjustments & Presets -->
                        <div class="accordion-item card card-premium border-0 mb-3 overflow-hidden">
                            <h2 class="accordion-header" id="heading-adj">
                                <button class="accordion-button fw-semibold text-dark" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-adj" aria-expanded="true" aria-controls="collapse-adj">
                                    <i class="fa-solid fa-sliders text-primary me-2"></i>Lighting & Color Sliders
                                </button>
                            </h2>
                            <div id="collapse-adj" class="accordion-collapse collapse show" aria-labelledby="heading-adj" data-bs-parent="#enhance-toolbox-accordion">
                                <div class="accordion-body p-3">
                                    
                                    <!-- Auto Presets -->
                                    <div class="mb-3">
                                        <label for="enhance-auto-preset" class="form-label small fw-semibold text-muted">Auto-Enhance Preset</label>
                                        <select class="form-select form-select-sm" id="enhance-auto-preset" onchange="ImageLab.applyAutoPreset(this.value)">
                                            <option value="">-- Choose Auto Preset --</option>
                                            <option value="auto">Standard Auto</option>
                                            <option value="landscape">Vibrant Landscape</option>
                                            <option value="portrait">Soft Portrait</option>
                                            <option value="product">Product Studio</option>
                                            <option value="social">Social Pop</option>
                                        </select>
                                    </div>
                                    <hr>

                                    <!-- Sliders -->
                                    <div class="row g-2">
                                        <!-- Brightness -->
                                        <div class="col-12 mb-2">
                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                <label for="slider-enhance-brightness" class="form-label small mb-0">Brightness</label>
                                                <span class="badge bg-light text-primary border" id="val-enhance-brightness">0%</span>
                                            </div>
                                            <input type="range" class="form-range" id="slider-enhance-brightness" min="-100" max="100" value="0" oninput="ImageLab.onEnhanceSliderInput(true)">
                                        </div>
                                        <!-- Contrast -->
                                        <div class="col-12 mb-2">
                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                <label for="slider-enhance-contrast" class="form-label small mb-0">Contrast</label>
                                                <span class="badge bg-light text-primary border" id="val-enhance-contrast">0%</span>
                                            </div>
                                            <input type="range" class="form-range" id="slider-enhance-contrast" min="-100" max="100" value="0" oninput="ImageLab.onEnhanceSliderInput(true)">
                                        </div>
                                        <!-- Saturation -->
                                        <div class="col-12 mb-2">
                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                <label for="slider-enhance-saturation" class="form-label small mb-0">Saturation</label>
                                                <span class="badge bg-light text-primary border" id="val-enhance-saturation">0%</span>
                                            </div>
                                            <input type="range" class="form-range" id="slider-enhance-saturation" min="-100" max="100" value="0" oninput="ImageLab.onEnhanceSliderInput(true)">
                                        </div>
                                        <!-- Sharpness -->
                                        <div class="col-12 mb-2">
                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                <label for="slider-enhance-sharpness" class="form-label small mb-0">Sharpness</label>
                                                <span class="badge bg-light text-primary border" id="val-enhance-sharpness">0%</span>
                                            </div>
                                            <input type="range" class="form-range" id="slider-enhance-sharpness" min="0" max="100" value="0" oninput="ImageLab.onEnhanceSliderInput(true)">
                                        </div>
                                        <!-- Exposure -->
                                        <div class="col-12 mb-2">
                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                <label for="slider-enhance-exposure" class="form-label small mb-0">Exposure</label>
                                                <span class="badge bg-light text-primary border" id="val-enhance-exposure">0%</span>
                                            </div>
                                            <input type="range" class="form-range" id="slider-enhance-exposure" min="-100" max="100" value="0" oninput="ImageLab.onEnhanceSliderInput(true)">
                                        </div>
                                        <!-- Highlights -->
                                        <div class="col-12 mb-2">
                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                <label for="slider-enhance-highlights" class="form-label small mb-0">Highlights</label>
                                                <span class="badge bg-light text-primary border" id="val-enhance-highlights">0%</span>
                                            </div>
                                            <input type="range" class="form-range" id="slider-enhance-highlights" min="-100" max="100" value="0" oninput="ImageLab.onEnhanceSliderInput(true)">
                                        </div>
                                        <!-- Shadows -->
                                        <div class="col-12 mb-2">
                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                <label for="slider-enhance-shadows" class="form-label small mb-0">Shadows</label>
                                                <span class="badge bg-light text-primary border" id="val-enhance-shadows">0%</span>
                                            </div>
                                            <input type="range" class="form-range" id="slider-enhance-shadows" min="-100" max="100" value="0" oninput="ImageLab.onEnhanceSliderInput(true)">
                                        </div>
                                        <!-- Temperature -->
                                        <div class="col-12 mb-2">
                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                <label for="slider-enhance-temperature" class="form-label small mb-0">Temperature</label>
                                                <span class="badge bg-light text-primary border" id="val-enhance-temperature">0%</span>
                                            </div>
                                            <input type="range" class="form-range" id="slider-enhance-temperature" min="-100" max="100" value="0" oninput="ImageLab.onEnhanceSliderInput(true)">
                                        </div>
                                        <!-- Tint -->
                                        <div class="col-12 mb-3">
                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                <label for="slider-enhance-tint" class="form-label small mb-0">Tint</label>
                                                <span class="badge bg-light text-primary border" id="val-enhance-tint">0%</span>
                                            </div>
                                            <input type="range" class="form-range" id="slider-enhance-tint" min="-100" max="100" value="0" oninput="ImageLab.onEnhanceSliderInput(true)">
                                        </div>
                                    </div>

                                    <div class="d-flex gap-2">
                                        <button class="btn btn-primary flex-grow-1 fw-semibold py-2 btn-sm" onclick="ImageLab.applyCustomEnhancements()">
                                            <i class="fa-solid fa-check me-1"></i>Apply Sliders
                                        </button>
                                        <button class="btn btn-light border fw-semibold py-2 btn-sm" onclick="ImageLab.resetEnhanceSliders()">
                                            Reset
                                        </button>
                                    </div>
                                    <hr>

                                    <!-- Save Custom Preset -->
                                    <div class="mb-2">
                                        <label class="form-label small fw-semibold text-muted">Save Custom Preset</label>
                                        <div class="input-group input-group-sm">
                                            <input type="text" class="form-control" id="custom-preset-name" placeholder="Preset name...">
                                            <button class="btn btn-outline-primary" type="button" onclick="ImageLab.saveCustomPreset()">Save</button>
                                        </div>
                                    </div>

                                    <!-- Load Custom Preset -->
                                    <div class="mb-0 mt-3">
                                        <label for="custom-preset-load-select" class="form-label small fw-semibold text-muted">Load Saved Preset</label>
                                        <div class="d-flex gap-2">
                                            <select class="form-select form-select-sm" id="custom-preset-load-select">
                                                <option value="">-- No Preset Selected --</option>
                                            </select>
                                            <button class="btn btn-sm btn-outline-success" onclick="ImageLab.loadCustomPreset()">Load</button>
                                            <button class="btn btn-sm btn-outline-danger" onclick="ImageLab.deleteCustomPreset()"><i class="fa-solid fa-trash"></i></button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Accordion 2: Photo Filters -->
                        <div class="accordion-item card card-premium border-0 mb-3 overflow-hidden">
                            <h2 class="accordion-header" id="heading-filters">
                                <button class="accordion-button collapsed fw-semibold text-dark" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-filters" aria-expanded="false" aria-controls="collapse-filters">
                                    <i class="fa-solid fa-wand-magic-sparkles text-primary me-2"></i>Photo Filter Gallery
                                </button>
                            </h2>
                            <div id="collapse-filters" class="accordion-collapse collapse" aria-labelledby="heading-filters" data-bs-parent="#enhance-toolbox-accordion">
                                <div class="accordion-body p-3">
                                    <p class="text-muted small mb-3">Apply professional lookup styles instantly to active uploads.</p>
                                    <div class="filter-gallery-container" id="filter-gallery-cards">
                                        <div class="filter-thumb-card active" onclick="ImageLab.applyFilter('original')">
                                            <div class="filter-thumb-preview d-flex align-items-center justify-content-center text-muted small"><i class="fa-solid fa-ban fs-5"></i></div>
                                            <span class="filter-thumb-label">Original</span>
                                        </div>
                                        <div class="filter-thumb-card" onclick="ImageLab.applyFilter('vivid')">
                                            <div class="filter-thumb-preview d-flex align-items-center justify-content-center bg-primary text-white small"><i class="fa-solid fa-sun fs-5"></i></div>
                                            <span class="filter-thumb-label">Vivid</span>
                                        </div>
                                        <div class="filter-thumb-card" onclick="ImageLab.applyFilter('vintage')">
                                            <div class="filter-thumb-preview d-flex align-items-center justify-content-center bg-warning text-dark small"><i class="fa-solid fa-palette fs-5"></i></div>
                                            <span class="filter-thumb-label">Vintage</span>
                                        </div>
                                        <div class="filter-thumb-card" onclick="ImageLab.applyFilter('bw')">
                                            <div class="filter-thumb-preview d-flex align-items-center justify-content-center bg-dark text-white small"><i class="fa-solid fa-circle-half-stroke fs-5"></i></div>
                                            <span class="filter-thumb-label">B&W</span>
                                        </div>
                                        <div class="filter-thumb-card" onclick="ImageLab.applyFilter('cinema')">
                                            <div class="filter-thumb-preview d-flex align-items-center justify-content-center bg-secondary text-white small"><i class="fa-solid fa-video fs-5"></i></div>
                                            <span class="filter-thumb-label">Cinema</span>
                                        </div>
                                        <div class="filter-thumb-card" onclick="ImageLab.applyFilter('hdr')">
                                            <div class="filter-thumb-preview d-flex align-items-center justify-content-center bg-info text-dark small"><i class="fa-solid fa-bolt fs-5"></i></div>
                                            <span class="filter-thumb-label">HDR</span>
                                        </div>
                                        <div class="filter-thumb-card" onclick="ImageLab.applyFilter('warm')">
                                            <div class="filter-thumb-preview d-flex align-items-center justify-content-center bg-danger text-white small"><i class="fa-solid fa-fire fs-5"></i></div>
                                            <span class="filter-thumb-label">Warm</span>
                                        </div>
                                        <div class="filter-thumb-card" onclick="ImageLab.applyFilter('cool')">
                                            <div class="filter-thumb-preview d-flex align-items-center justify-content-center bg-info text-white small"><i class="fa-solid fa-snowflake fs-5"></i></div>
                                            <span class="filter-thumb-label">Cool</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Accordion 3: Watermarks -->
                        <div class="accordion-item card card-premium border-0 mb-3 overflow-hidden">
                            <h2 class="accordion-header" id="heading-watermark">
                                <button class="accordion-button collapsed fw-semibold text-dark" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-watermark" aria-expanded="false" aria-controls="collapse-watermark">
                                    <i class="fa-solid fa-copyright text-primary me-2"></i>Watermark Protection
                                </button>
                            </h2>
                            <div id="collapse-watermark" class="accordion-collapse collapse" aria-labelledby="heading-watermark" data-bs-parent="#enhance-toolbox-accordion">
                                <div class="accordion-body p-3">
                                    
                                    <div class="mb-3">
                                        <label class="form-label small fw-semibold text-muted">Watermark Type</label>
                                        <div class="btn-group w-100" role="group">
                                            <input type="radio" class="btn-check" name="watermark-type" id="wm-type-text" checked onclick="ImageLab.toggleWatermarkType('text')">
                                            <label class="btn btn-sm btn-outline-primary" for="wm-type-text">Text Watermark</label>
                                            
                                            <input type="radio" class="btn-check" name="watermark-type" id="wm-type-logo" onclick="ImageLab.toggleWatermarkType('logo')">
                                            <label class="btn btn-sm btn-outline-primary" for="wm-type-logo">Logo Overlay</label>
                                        </div>
                                    </div>

                                    <!-- Text Watermark Options -->
                                    <div id="wm-options-text">
                                        <div class="mb-3">
                                            <label for="wm-text-input" class="form-label small text-muted">Watermark Text</label>
                                            <input type="text" class="form-control form-control-sm" id="wm-text-input" placeholder="© ImageLab Studio">
                                        </div>
                                        <div class="row g-2 mb-3">
                                            <div class="col-6">
                                                <label for="wm-text-color" class="form-label small text-muted">Color</label>
                                                <input type="color" class="form-control form-control-color form-control-sm w-100" id="wm-text-color" value="#ffffff">
                                            </div>
                                            <div class="col-6">
                                                <label for="wm-text-size" class="form-label small text-muted">Size (px)</label>
                                                <input type="number" class="form-control form-control-sm" id="wm-text-size" value="30">
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <div class="d-flex justify-content-between mb-1">
                                                <label for="wm-text-opacity" class="form-label small text-muted mb-0">Opacity</label>
                                                <span class="small fw-semibold text-primary" id="val-wm-text-opacity">50%</span>
                                            </div>
                                            <input type="range" class="form-range" id="wm-text-opacity" min="10" max="100" value="50" oninput="document.getElementById('val-wm-text-opacity').textContent = this.value + '%'">
                                        </div>
                                        <div class="mb-3">
                                            <label for="wm-text-rotation" class="form-label small text-muted">Rotation (Degrees)</label>
                                            <input type="range" class="form-range" id="wm-text-rotation" min="-180" max="180" value="0">
                                        </div>
                                    </div>

                                    <!-- Logo Watermark Options -->
                                    <div id="wm-options-logo" class="d-none">
                                        <div class="mb-3">
                                            <label for="wm-logo-upload" class="form-label small text-muted">Choose Logo Image</label>
                                            <input type="file" class="form-control form-control-sm" id="wm-logo-upload" accept=".png,.jpg,.jpeg,.gif,.webp,.svg,.bmp,.tiff,.tif,.ico,.heic,.heif,.avif,.raw,.cr2,.cr3,.nef,.arw,.dng,.orf,.raf,.psd,.ai,.eps,.pdf,.tga,.exr,.hdr,.jfif">
                                            <span class="form-text small text-muted" style="font-size: 10px;">For best results, upload a transparent PNG logo.</span>
                                        </div>
                                        <div class="mb-3">
                                            <div class="d-flex justify-content-between mb-1">
                                                <label for="wm-logo-scale" class="form-label small text-muted mb-0">Scale Ratio</label>
                                                <span class="small fw-semibold text-primary" id="val-wm-logo-scale">20%</span>
                                            </div>
                                            <input type="range" class="form-range" id="wm-logo-scale" min="5" max="50" value="20" oninput="document.getElementById('val-wm-logo-scale').textContent = this.value + '%'">
                                        </div>
                                        <div class="mb-3">
                                            <div class="d-flex justify-content-between mb-1">
                                                <label for="wm-logo-opacity" class="form-label small text-muted mb-0">Opacity</label>
                                                <span class="small fw-semibold text-primary" id="val-wm-logo-opacity">80%</span>
                                            </div>
                                            <input type="range" class="form-range" id="wm-logo-opacity" min="10" max="100" value="80" oninput="document.getElementById('val-wm-logo-opacity').textContent = this.value + '%'">
                                        </div>
                                    </div>

                                    <!-- Common Overlay Options -->
                                    <div class="mb-3">
                                        <label for="wm-position" class="form-label small text-muted">Overlay Position</label>
                                        <select class="form-select form-select-sm" id="wm-position">
                                            <option value="top-left">Top Left</option>
                                            <option value="top-center">Top Center</option>
                                            <option value="top-right">Top Right</option>
                                            <option value="center-left">Center Left</option>
                                            <option value="center">Center</option>
                                            <option value="center-right">Center Right</option>
                                            <option value="bottom-left">Bottom Left</option>
                                            <option value="bottom-center">Bottom Center</option>
                                            <option value="bottom-right" selected>Bottom Right (Standard)</option>
                                        </select>
                                    </div>

                                    <div class="row g-2 mb-4">
                                        <div class="col-6">
                                            <label for="wm-offset-x" class="form-label small text-muted">Offset X (px)</label>
                                            <input type="number" class="form-control form-control-sm" id="wm-offset-x" value="20">
                                        </div>
                                        <div class="col-6">
                                            <label for="wm-offset-y" class="form-label small text-muted">Offset Y (px)</label>
                                            <input type="number" class="form-control form-control-sm" id="wm-offset-y" value="20">
                                        </div>
                                    </div>

                                    <button class="btn btn-primary w-100 fw-semibold py-2 btn-sm" onclick="ImageLab.applyWatermark()">
                                        Apply Overlay Protection
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Accordion 4: Metadata Viewer -->
                        <div class="accordion-item card card-premium border-0 mb-3 overflow-hidden">
                            <h2 class="accordion-header" id="heading-metadata">
                                <button class="accordion-button collapsed fw-semibold text-dark" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-metadata" aria-expanded="false" aria-controls="collapse-metadata">
                                    <i class="fa-solid fa-info-circle text-primary me-2"></i>EXIF Metadata Inspector
                                </button>
                            </h2>
                            <div id="collapse-metadata" class="accordion-collapse collapse" aria-labelledby="heading-metadata" data-bs-parent="#enhance-toolbox-accordion">
                                <div class="accordion-body p-3">
                                    <p class="text-muted small mb-3">Inspect technical headers and strip privacy elements from the image.</p>
                                    
                                    <div id="meta-empty-inspector" class="text-center py-3 text-muted small">
                                        No active file to inspect EXIF.
                                    </div>
                                    <div id="meta-detail-table" class="d-none">
                                        <div class="table-responsive">
                                            <table class="table table-sm small mb-3">
                                                <tbody id="metadata-rows">
                                                    <!-- Filled Dynamically -->
                                                </tbody>
                                            </table>
                                        </div>
                                        
                                        <button class="btn btn-outline-danger w-100 btn-sm fw-semibold" onclick="ImageLab.stripPrivacyHeaders()">
                                            <i class="fa-solid fa-shield-halved me-1"></i>Strip Privacy EXIF Headers
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Accordion 5: Quality Export & BG Tools -->
                        <div class="accordion-item card card-premium border-0 mb-3 overflow-hidden">
                            <h2 class="accordion-header" id="heading-export">
                                <button class="accordion-button collapsed fw-semibold text-dark" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-export" aria-expanded="false" aria-controls="collapse-export">
                                    <i class="fa-solid fa-cloud-arrow-down text-primary me-2"></i>Export & Background Tools
                                </button>
                            </h2>
                            <div id="collapse-export" class="accordion-collapse collapse" aria-labelledby="heading-export" data-bs-parent="#enhance-toolbox-accordion">
                                <div class="accordion-body p-3">
                                    
                                    <!-- Target Format -->
                                    <div class="mb-3">
                                        <label for="export-format" class="form-label small fw-semibold text-muted">Output Format</label>
                                        <select class="form-select form-select-sm" id="export-format" onchange="ImageLab.onExportParamsChanged()">
                                            <option value="webp" selected>WEBP (Highly Recommended)</option>
                                            <option value="jpg">JPEG (Photographic)</option>
                                            <option value="png">PNG (Lossless Transparency)</option>
                                            <option value="gif">GIF (Animation)</option>
                                            <option value="svg">SVG (Vector container)</option>
                                            <option value="bmp">BMP (Windows Bitmap)</option>
                                            <option value="tiff">TIFF (Tagged Image File)</option>
                                            <option value="ico">ICO (Windows Icon)</option>
                                            <option value="heic">HEIC (High Efficiency)</option>
                                            <option value="avif">AVIF (AV1 Image)</option>
                                            <option value="psd">PSD (Photoshop Document)</option>
                                            <option value="pdf">PDF (Portable Document)</option>
                                            <option value="eps">EPS (Encapsulated PostScript)</option>
                                            <option value="tga">TGA (Truevision Targa)</option>
                                            <option value="exr">EXR (OpenEXR HDR)</option>
                                            <option value="hdr">HDR (Radiance HDR)</option>
                                            <option value="jfif">JFIF (JPEG Interchange)</option>
                                        </select>
                                    </div>

                                    <!-- Quality Slider -->
                                    <div class="mb-3" id="export-quality-container">
                                        <div class="d-flex justify-content-between mb-1">
                                            <label for="export-quality" class="form-label small fw-semibold text-muted mb-0">Compression Quality</label>
                                            <span class="small fw-semibold text-primary" id="val-export-quality">80%</span>
                                        </div>
                                        <input type="range" class="form-range" id="export-quality" min="1" max="100" value="80" oninput="ImageLab.onExportQualitySlider(this.value)" onchange="ImageLab.onExportParamsChanged()">
                                    </div>

                                    <div class="alert alert-secondary border-0 p-2 small mb-4">
                                        <div class="d-flex justify-content-between">
                                            <span>Est. Output Size:</span>
                                            <strong id="export-est-size">Calculating...</strong>
                                        </div>
                                    </div>

                                    <!-- Background Tools -->
                                    <h4 class="h6 mb-3 fw-bold text-dark border-top pt-3"><i class="fa-solid fa-fill-drip text-primary me-1"></i>Background Manipulation</h4>
                                    
                                    <!-- Tool 1: Solid BG Fill -->
                                    <div class="mb-3">
                                        <label for="export-bg-solid" class="form-label small text-muted">Fill Transparent Background</label>
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text"><i class="fa-solid fa-brush"></i></span>
                                            <input type="text" class="form-control" id="export-bg-solid" placeholder="Hex color e.g., #f3f3f3">
                                            <input type="color" class="form-control form-control-color" id="export-bg-solid-picker" value="#ffffff" oninput="document.getElementById('export-bg-solid').value = this.value">
                                        </div>
                                    </div>

                                    <!-- Tool 2: Color Replace -->
                                    <div class="mb-3 border-top pt-2">
                                        <label class="form-label small text-muted fw-semibold">Replace Specific Color</label>
                                        <div class="row g-2 mb-2">
                                            <div class="col-6">
                                                <label class="form-label small text-muted mb-1" style="font-size: 11px;">Source Color</label>
                                                <div class="input-group input-group-sm">
                                                    <input type="text" class="form-control" id="export-color-rep-old" placeholder="#000000">
                                                    <input type="color" class="form-control form-control-color" id="export-color-rep-old-picker" value="#000000" oninput="document.getElementById('export-color-rep-old').value = this.value">
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <label class="form-label small text-muted mb-1" style="font-size: 11px;">Target Color</label>
                                                <div class="input-group input-group-sm">
                                                    <input type="text" class="form-control" id="export-color-rep-new" placeholder="#ffffff">
                                                    <input type="color" class="form-control form-control-color" id="export-color-rep-new-picker" value="#ffffff" oninput="document.getElementById('export-color-rep-new').value = this.value">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="mb-2">
                                            <div class="d-flex justify-content-between">
                                                <label for="export-color-rep-fuzz" class="form-label small text-muted mb-0">Fuzz Tolerance</label>
                                                <span class="small fw-semibold text-primary" id="val-color-rep-fuzz">10%</span>
                                            </div>
                                            <input type="range" class="form-range" id="export-color-rep-fuzz" min="0" max="50" value="10" oninput="document.getElementById('val-color-rep-fuzz').textContent = this.value + '%'">
                                        </div>
                                    </div>

                                    <!-- Tool 3: Expand Canvas -->
                                    <div class="mb-3 border-top pt-2">
                                        <label class="form-label small text-muted fw-semibold">Expand Canvas Border</label>
                                        <div class="row g-2 mb-2">
                                            <div class="col-6">
                                                <label for="export-expand-w" class="form-label small text-muted mb-1" style="font-size: 11px;">New Width (px)</label>
                                                <input type="number" class="form-control form-control-sm" id="export-expand-w" placeholder="e.g. 1920">
                                            </div>
                                            <div class="col-6">
                                                <label for="export-expand-h" class="form-label small text-muted mb-1" style="font-size: 11px;">New Height (px)</label>
                                                <input type="number" class="form-control form-control-sm" id="export-expand-h" placeholder="e.g. 1080">
                                            </div>
                                        </div>
                                        <div class="row g-2 mb-3">
                                            <div class="col-7">
                                                <label for="export-expand-gravity" class="form-label small text-muted mb-1" style="font-size: 11px;">Image Position</label>
                                                <select class="form-select form-select-sm" id="export-expand-gravity">
                                                    <option value="center" selected>Center</option>
                                                    <option value="top-left">Top Left</option>
                                                    <option value="top-right">Top Right</option>
                                                    <option value="bottom-left">Bottom Left</option>
                                                    <option value="bottom-right">Bottom Right</option>
                                                </select>
                                            </div>
                                            <div class="col-5">
                                                <label for="export-expand-bg" class="form-label small text-muted mb-1" style="font-size: 11px;">Border Fill</label>
                                                <div class="input-group input-group-sm">
                                                    <input type="text" class="form-control" id="export-expand-bg" value="#ffffff">
                                                    <input type="color" class="form-control form-control-color" id="export-expand-bg-picker" value="#ffffff" oninput="document.getElementById('export-expand-bg').value = this.value">
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <button class="btn btn-success w-100 fw-semibold py-2 btn-sm mt-2" onclick="ImageLab.runFinalExport()">
                                        Process & Export Design
                                    </button>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- Column 2: Split Slider Interactive Comparison View -->
                <div class="col-lg-7">
                    <div id="enhance-alert-box" class="mb-3"></div>
                    <div class="card card-premium border-0 mb-4 d-flex flex-column">
                        <div class="card-body p-4 d-flex flex-column">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h3 class="card-title-main mb-0"><i class="fa-solid fa-images text-primary me-2"></i>Comparison Preview</h3>
                                <div class="d-flex gap-2">
                                    <button class="btn btn-sm btn-outline-primary" id="btn-enhance-to-editor" onclick="ImageLab.sendEnhanceToCanvas()" disabled>
                                        <i class="fa-solid fa-crop-simple me-1"></i>Send to Canvas
                                    </button>
                                    <a id="enhance-download-btn" href="#" class="btn btn-sm btn-success disabled"><i class="fa-solid fa-download me-1"></i>Download</a>
                                </div>
                            </div>

                            <div id="enhance-orig-empty" class="preview-empty-state flex-grow-1 d-flex flex-column align-items-center justify-content-center py-5">
                                <i class="fa-solid fa-images fs-1 text-muted mb-3"></i>
                                <p class="small text-muted mb-0">Upload an image to activate split comparisons</p>
                            </div>

                            <div id="enhance-comparison-viewport" class="d-none flex-grow-1">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <div class="text-muted mb-2 small fw-semibold"><i class="fa-solid fa-image me-1"></i> Original</div>
                                        <div class="preview-image-container">
                                            <img id="comp-img-before" src="" alt="Original">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="text-muted mb-2 small fw-semibold"><i class="fa-solid fa-wand-magic-sparkles me-1"></i> Result</div>
                                        <div class="preview-image-container">
                                            <img id="comp-img-after" src="" alt="Result">
                                        </div>
                                    </div>
                                </div>

                                <div class="table-responsive mt-3 mb-0">
                                    <table class="table table-sm table-borderless small mb-0">
                                        <tbody>
                                            <tr class="border-bottom">
                                                <td class="text-muted">Original Size</td>
                                                <td class="fw-semibold text-dark text-end" id="enhance-meta-old-size">0 KB</td>
                                                <td class="text-muted ps-4">Dimensions</td>
                                                <td class="fw-semibold text-dark text-end" id="enhance-orig-meta">Dimensions: 0x0</td>
                                            </tr>
                                            <tr>
                                                <td class="text-muted">Processed Size</td>
                                                <td class="fw-semibold text-dark text-end" id="enhance-meta-new-size">0 KB</td>
                                                <td class="text-muted ps-4">Savings</td>
                                                <td class="fw-semibold text-success text-end" id="enhance-meta-saved-pct">0%</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

            </div>
        </div>


        <!-- ================= PANEL 4C: CANVA EDITOR ================= -->
        <div id="panel-editor" class="dashboard-panel d-none">
            <div class="row mb-4 align-items-center">
                <div class="col-sm-7">
                    <h2 class="h4 mb-1 fw-bold text-dark"><i class="fa-solid fa-crop-simple text-primary me-2"></i>Interactive Canvas Workspace</h2>
                    <p class="text-muted mb-0" style="font-size: 14px;">Crop, rotate, scale, flip, and preview platform dimensions before downloading.</p>
                </div>
            </div>

            <!-- Workspace Editor Alert Box -->
            <div id="editor-alert-box"></div>

            <div class="editor-workspace-container">
                
                <!-- COLUMN 1: Tools & Upload -->
                <div class="editor-tools-sidebar">
                    <!-- Load Image Card -->
                    <div class="card card-premium border-0 mb-4">
                        <div class="card-body p-3">
                            <h3 class="card-title-main mb-2"><i class="fa-solid fa-file-import text-primary me-1"></i>Load Image</h3>
                            <button class="btn btn-primary w-100 py-2 btn-sm fw-semibold" onclick="document.getElementById('file-uploader-editor').click()">
                                <i class="fa-solid fa-upload me-1"></i>Choose File
                            </button>
                            <input type="file" id="file-uploader-editor" class="d-none" accept=".png,.jpg,.jpeg,.gif,.webp,.svg,.bmp,.tiff,.tif,.ico,.heic,.heif,.avif,.raw,.cr2,.cr3,.nef,.arw,.dng,.orf,.raf,.psd,.ai,.eps,.pdf,.tga,.exr,.hdr,.jfif">
                        </div>
                    </div>

                    <!-- Crop tool Card -->
                    <div class="card card-premium border-0">
                        <div class="card-body p-3">
                            <h3 class="card-title-main mb-2"><i class="fa-solid fa-crop text-primary me-1"></i>Smart Crop</h3>
                            
                            <div class="mb-3">
                                <label for="editor-crop-presets" class="form-label small fw-semibold text-muted">Crop Presets</label>
                                <select class="form-select form-select-sm" id="editor-crop-presets">
                                    <option value="none">Choose Aspect Ratio</option>
                                    <option value="free">Free Transform Crop</option>
                                    <option value="1_1">Square (1:1)</option>
                                    <option value="4_5">Portrait (4:5)</option>
                                    <option value="16_9">Landscape (16:9)</option>
                                    <option value="9_16">Story (9:16)</option>
                                </select>
                            </div>

                            <div class="d-flex gap-2">
                                <button class="btn btn-success flex-grow-1 py-1.5 btn-sm fw-semibold" id="btn-editor-apply-crop">
                                    <i class="fa-solid fa-check me-1"></i>Apply
                                </button>
                                <button class="btn btn-light border flex-grow-1 py-1.5 btn-sm fw-semibold" id="btn-editor-cancel-crop">
                                    <i class="fa-solid fa-xmark me-1"></i>Cancel
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- COLUMN 2: Canvas Viewport & Zoom -->
                <div class="editor-canvas-center">
                    <div class="editor-canvas-outer">
                        <canvas id="editor-canvas"></canvas>
                    </div>

                    <!-- Zoom & Viewport Bar -->
                    <div class="card card-premium border-0">
                        <div class="card-body py-2 px-3 d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center gap-2">
                                <button class="btn btn-light btn-sm border" id="btn-editor-zoom-out" title="Zoom Out"><i class="fa-solid fa-magnifying-glass-minus"></i></button>
                                <span class="small fw-semibold text-dark px-2" id="lbl-editor-zoom">100%</span>
                                <button class="btn btn-light btn-sm border" id="btn-editor-zoom-in" title="Zoom In"><i class="fa-solid fa-magnifying-glass-plus"></i></button>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <button class="btn btn-light btn-sm border" id="btn-editor-zoom-fit" title="Fit to Screen"><i class="fa-solid fa-expand"></i> Fit</button>
                                <button class="btn btn-light btn-sm border" id="btn-editor-zoom-100" title="100% Zoom"><i class="fa-solid fa-compress"></i> 100%</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- COLUMN 3: Properties Panel & Overlays -->
                <div class="editor-properties-sidebar">
                    
                    <!-- Dimensions Card -->
                    <div class="card card-premium border-0 mb-4">
                        <div class="card-body p-3">
                            <h3 class="card-title-main mb-3"><i class="fa-solid fa-sliders text-primary me-1"></i>Dimensions</h3>
                            
                            <div class="row g-2 mb-3">
                                <div class="col-6">
                                    <label for="prop-editor-width" class="form-label small text-muted mb-1">Width (px)</label>
                                    <input type="number" class="form-control form-control-sm" id="prop-editor-width" value="0">
                                </div>
                                <div class="col-6">
                                    <label for="prop-editor-height" class="form-label small text-muted mb-1">Height (px)</label>
                                    <input type="number" class="form-control form-control-sm" id="prop-editor-height" value="0">
                                </div>
                            </div>

                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" id="prop-editor-ratio" checked>
                                <label class="form-check-label small fw-semibold text-dark" for="prop-editor-ratio">Lock Aspect Ratio</label>
                            </div>
                            <div class="small text-muted">Current Scale: <span id="lbl-editor-scale" class="fw-semibold">100%</span></div>
                        </div>
                    </div>

                    <!-- Transformations Card -->
                    <div class="card card-premium border-0 mb-4">
                        <div class="card-body p-3">
                            <h3 class="card-title-main mb-3"><i class="fa-solid fa-arrows-rotate text-primary me-1"></i>Transform</h3>
                            
                            <!-- Rotation Buttons & Slider -->
                            <div class="mb-3">
                                <label class="form-label small text-muted mb-2">Rotate Image</label>
                                <div class="d-flex gap-2 mb-3">
                                    <button class="btn btn-light border btn-sm flex-grow-1" id="btn-editor-rot-left"><i class="fa-solid fa-rotate-left me-1"></i>-90°</button>
                                    <button class="btn btn-light border btn-sm flex-grow-1" id="btn-editor-rot-right"><i class="fa-solid fa-rotate-right me-1"></i>+90°</button>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="small text-muted">Angle</span>
                                    <span class="badge bg-light text-primary border" id="lbl-editor-rotate" style="font-size: 10px;">0°</span>
                                </div>
                                <input type="range" class="form-range editor-range-slider" id="slider-editor-rotate" min="0" max="360" value="0">
                            </div>

                            <!-- Flips & Resets -->
                            <div>
                                <label class="form-label small text-muted mb-2">Flip & Reset</label>
                                <div class="d-flex gap-2 mb-2">
                                    <button class="btn btn-light border btn-sm flex-grow-1" id="btn-editor-flip-h" title="Flip Horizontal"><i class="fa-solid fa-arrows-left-right me-1"></i>Horizontal</button>
                                    <button class="btn btn-light border btn-sm flex-grow-1" id="btn-editor-flip-v" title="Flip Vertical"><i class="fa-solid fa-arrows-up-down me-1"></i>Vertical</button>
                                </div>
                                <button class="btn btn-outline-danger btn-sm w-100 py-1.5 fw-semibold mt-1" id="btn-editor-reset">
                                    <i class="fa-solid fa-trash-can-arrow-up me-1"></i>Reset Transforms
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Platform Guide Overlays -->
                    <div class="card card-premium border-0">
                        <div class="card-body p-3">
                            <h3 class="card-title-main mb-2"><i class="fa-solid fa-border-top-left text-primary me-1"></i>Social Overlays</h3>
                            <div class="mb-3">
                                <select class="form-select form-select-sm" id="editor-guides-presets">
                                    <option value="instagram_post">Instagram Square (1:1)</option>
                                    <option value="instagram_story">Instagram Story (9:16)</option>
                                    <option value="facebook_post">Facebook Shared Post</option>
                                    <option value="youtube_thumbnail">YouTube Thumbnail</option>
                                </select>
                            </div>
                            <div class="form-check form-switch mb-0">
                                <input class="form-check-input" type="checkbox" id="editor-guides-toggle">
                                <label class="form-check-label small fw-semibold text-dark" for="editor-guides-toggle">Display Guides Overlay</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- BOTTOM ROW: Undo/Redo & Save session -->
            <div class="row g-4 mt-2">
                <div class="col-lg-7">
                    <div class="card card-premium border-0">
                        <div class="card-body p-3 d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center gap-2">
                                <button class="btn btn-light btn-sm border" id="btn-editor-undo" disabled title="Undo (Ctrl+Z)"><i class="fa-solid fa-arrow-rotate-left"></i> Undo</button>
                                <button class="btn btn-light btn-sm border" id="btn-editor-redo" disabled title="Redo (Ctrl+Y)"><i class="fa-solid fa-arrow-rotate-right"></i> Redo</button>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <button class="btn btn-success btn-sm px-3 fw-semibold" id="btn-editor-save-session"><i class="fa-solid fa-floppy-disk me-1"></i>Save Workspace</button>
                                <button class="btn btn-outline-primary btn-sm px-3 fw-semibold" id="btn-editor-restore-session"><i class="fa-solid fa-cloud-arrow-up me-1"></i>Restore Workspace</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- History Logs sidebar panel -->
                <div class="col-lg-5">
                    <div class="card card-premium border-0">
                        <div class="card-body p-3">
                            <h3 class="card-title-main mb-2" style="font-size: 13px;"><i class="fa-solid fa-clock-history text-muted me-1"></i>Undo Stack History Log</h3>
                            <div id="editor-history-list" style="max-height: 80px; overflow-y: auto;">
                                <div class="text-center text-muted small py-2">No operations registered.</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>


        <!-- ================= PANEL 5: BATCH PROCESS ================= -->
        <div id="panel-batch" class="dashboard-panel d-none">
            <h2 class="h4 mb-4 fw-bold text-dark"><i class="fa-solid fa-layer-group text-primary me-2"></i>Batch Upload & Queue Manager</h2>

            <div class="row g-4">
                <!-- Column 1: File Batch Selector and Operation settings -->
                <div class="col-lg-5">
                    <!-- Batch Upload Card -->
                    <div class="card card-premium border-0 mb-4">
                        <div class="card-body p-4">
                            <h3 class="card-title-main mb-3">Select Batch Files</h3>
                            <div class="upload-dropzone-box" id="batch-dropzone">
                                <div class="icon-container"><i class="fa-solid fa-images"></i></div>
                                <h4 class="h6 fw-semibold mb-2">Drag multiple files here</h4>
                                <p class="text-muted small mb-0">Or click to select (Max 20 files, Max 100MB)</p>
                                <input type="file" id="file-uploader-batch" class="d-none" multiple accept=".png,.jpg,.jpeg,.gif,.webp,.svg,.bmp,.tiff,.tif,.ico,.heic,.heif,.avif,.raw,.cr2,.cr3,.nef,.arw,.dng,.orf,.raf,.psd,.ai,.eps,.pdf,.tga,.exr,.hdr,.jfif">
                            </div>
                        </div>
                    </div>

                    <!-- Batch Operation Config Card -->
                    <div class="card card-premium border-0">
                        <div class="card-body p-4">
                            <h3 class="card-title-main mb-3">Batch Actions</h3>
                            <p class="text-muted small">Select an operation to apply uniformly to all enqueued items.</p>
                            
                            <!-- Action Selection -->
                            <div class="mb-3">
                                <label for="batch-action-select" class="form-label fw-semibold" style="font-size: 13px;">Operation Type</label>
                                <select class="form-select" id="batch-action-select" onchange="ImageLab.toggleBatchActionControls(this.value)">
                                    <option value="convert" selected>Convert Format</option>
                                    <option value="resize">Resize Dimensions</option>
                                    <option value="compress">Compress Image</option>
                                </select>
                            </div>

                            <!-- Dynamic Action Controls: Convert -->
                            <div id="batch-ctrl-convert" class="batch-control-sub-panel mb-4">
                                <label for="batch-convert-format" class="form-label text-muted small">Target Format</label>
                                <select class="form-select" id="batch-convert-format">
                                    <option value="webp">WEBP</option>
                                    <option value="png">PNG</option>
                                    <option value="jpg">JPG</option>
                                </select>
                            </div>

                            <!-- Dynamic Action Controls: Resize -->
                            <div id="batch-ctrl-resize" class="batch-control-sub-panel d-none mb-4">
                                <div class="row g-2 mb-3">
                                    <div class="col-6">
                                        <label for="batch-resize-width" class="form-label text-muted small">Width (px)</label>
                                        <input type="number" class="form-control" id="batch-resize-width" value="800">
                                    </div>
                                    <div class="col-6">
                                        <label for="batch-resize-height" class="form-label text-muted small">Height (px)</label>
                                        <input type="number" class="form-control" id="batch-resize-height" value="600">
                                    </div>
                                </div>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch" id="batch-resize-ratio" checked>
                                    <label class="form-check-label text-muted small" for="batch-resize-ratio">Maintain Ratio</label>
                                </div>
                            </div>

                            <!-- Dynamic Action Controls: Compress -->
                            <div id="batch-ctrl-compress" class="batch-control-sub-panel d-none mb-4">
                                <label for="batch-compress-quality" class="form-label text-muted small">Quality Rating (1-100)</label>
                                <input type="range" class="form-range" min="1" max="100" value="80" id="batch-compress-quality" oninput="document.getElementById('batch-compress-val').textContent = this.value">
                                <span class="text-primary small" id="batch-compress-val">80</span>%
                            </div>

                            <button class="btn btn-primary w-100 py-2.5 fw-semibold d-flex align-items-center justify-content-center gap-2" id="enqueue-batch-btn" onclick="ImageLab.submitBatch()">
                                <i class="fa-solid fa-list-check"></i> Enqueue Files for Processing
                            </button>
                            <div id="batch-alert-box" class="mt-3"></div>
                        </div>
                    </div>
                </div>

                <!-- Column 2: Queue Monitor List -->
                <div class="col-lg-7">
                    <div class="card card-premium border-0">
                        <div class="card-body p-4">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h3 class="card-title-main m-0"><i class="fa-solid fa-server text-primary me-2"></i>Queue Monitor</h3>
                                <button class="btn btn-light border btn-sm text-secondary" onclick="ImageLab.clearQueueCompleted()">Clear Done</button>
                            </div>

                            <!-- Batch Progress Loader Bar -->
                            <div id="batch-progress-bar-container" class="mb-4 d-none">
                                <div class="d-flex justify-content-between align-items-center mb-1 small text-muted">
                                    <span>Batch Progress</span>
                                    <span id="batch-progress-pct">0%</span>
                                </div>
                                <div class="progress progress-premium"><div class="progress-bar bg-success" id="batch-progress-bar" style="width: 0%;"></div></div>
                            </div>

                            <!-- Queue Table -->
                            <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                                <table class="table table-hover align-middle mb-0" style="font-size: 13px;">
                                    <thead class="table-light">
                                        <tr class="text-muted">
                                            <th>ID</th>
                                            <th>Filename</th>
                                            <th>Operation</th>
                                            <th class="text-end">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody id="batch-queue-tbody">
                                        <tr>
                                            <td colspan="4" class="text-center text-muted py-4">No jobs registered in the queue. Upload files on the left.</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Execute Queue Button -->
                            <div class="mt-4 pt-3 border-top">
                                <button class="btn btn-success w-100 py-2.5 fw-semibold d-flex align-items-center justify-content-center gap-2" id="execute-queue-btn" disabled onclick="ImageLab.runProcessingQueue()">
                                    <i class="fa-solid fa-circle-play"></i> Start Queue Processing
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>


        <!-- ================= PANEL 5B: AI PROCESSING ================= -->
        <div id="panel-ai" class="dashboard-panel d-none">
            <h2 class="h4 mb-4 fw-bold text-dark"><i class="fa-solid fa-robot text-primary me-2"></i>AI-Powered Optimization</h2>

            <!-- Shared Active File Header -->
            <div id="active-file-header-ai" class="alert alert-primary border-0 shadow-sm d-none align-items-center justify-content-between mb-4">
                <div class="d-flex align-items-center gap-2">
                    <i class="fa-solid fa-file-circle-check fs-5"></i>
                    <span class="small">Active File: <strong id="active-filename-ai">cat.jpg</strong></span>
                </div>
                <button class="btn btn-sm btn-outline-primary" onclick="ImageLab.resetActiveFile()">Upload New File</button>
            </div>

            <div class="row g-4">
                <!-- Column 1: Controls & AI Toolbox -->
                <div class="col-lg-5">
                    <!-- Upload Dropzone Card (Visible if no active file) -->
                    <div class="card card-premium border-0 mb-4" id="ai-upload-card">
                        <div class="card-body p-4">
                            <h3 class="card-title-main mb-3">Upload File for AI Processing</h3>
                            <div class="upload-dropzone-box" onclick="document.getElementById('file-uploader-ai').click()">
                                <div class="icon-container"><i class="fa-solid fa-robot"></i></div>
                                <h4 class="h6 fw-semibold mb-2">Drag and drop file here</h4>
                                <p class="text-muted small mb-0">Supports JPG, PNG, WEBP, GIF, SVG, RAW, PSD, PDF, AI, HEIC, TGA, etc. (Max 10MB)</p>
                                <input type="file" id="file-uploader-ai" class="d-none" accept=".png,.jpg,.jpeg,.gif,.webp,.svg,.bmp,.tiff,.tif,.ico,.heic,.heif,.avif,.raw,.cr2,.cr3,.nef,.arw,.dng,.orf,.raf,.psd,.ai,.eps,.pdf,.tga,.exr,.hdr,.jfif" onchange="ImageLab.handleFileSelect(this.files[0])">
                            </div>
                            <div id="progress-container-ai" class="mt-3 d-none">
                                <div class="progress progress-premium"><div class="progress-bar bg-primary" id="progress-bar-ai" style="width: 0%;"></div></div>
                            </div>
                        </div>
                    </div>

                    <!-- AI Queue Settings -->
                    <div class="card card-premium border-0 mb-3" id="ai-settings-card">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <div>
                                    <h4 class="h6 mb-0 fw-bold text-dark"><i class="fa-solid fa-clock text-primary me-2"></i>Async Queue Processing</h4>
                                    <span class="text-muted" style="font-size: 11px;">Process long-running tasks in the background</span>
                                </div>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch" id="ai-queue-toggle" checked>
                                </div>
                            </div>
                            <div class="d-flex gap-2 mt-2 border-top pt-2">
                                <button class="btn btn-xs btn-outline-warning w-50 fw-semibold" style="font-size: 11px; padding: 4px;" onclick="ImageLab.cancelAIQueue()">
                                    <i class="fa-solid fa-ban me-1"></i>Cancel Queue
                                </button>
                                <button class="btn btn-xs btn-outline-danger w-50 fw-semibold" style="font-size: 11px; padding: 4px;" onclick="ImageLab.clearAIQueueHistory()">
                                    <i class="fa-solid fa-trash-can me-1"></i>Clear History
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Accordion AI Toolbox -->
                    <div class="accordion accordion-flush" id="ai-toolbox-accordion">
                        
                        <!-- Accordion 1: Quality Score & Metrics -->
                        <div class="accordion-item card card-premium border-0 mb-3 overflow-hidden">
                            <h2 class="accordion-header" id="heading-ai-metrics">
                                <button class="accordion-button fw-semibold text-dark" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-ai-metrics" aria-expanded="true" aria-controls="collapse-ai-metrics">
                                    <i class="fa-solid fa-chart-line text-primary me-2"></i>Image Quality Score & Tags
                                </button>
                            </h2>
                            <div id="collapse-ai-metrics" class="accordion-collapse collapse show" aria-labelledby="heading-ai-metrics" data-bs-parent="#ai-toolbox-accordion">
                                <div class="accordion-body p-3">
                                    <!-- Score Gauge -->
                                    <div class="d-flex align-items-center gap-3 mb-3">
                                        <div class="position-relative d-flex align-items-center justify-content-center" style="width: 70px; height: 70px;">
                                            <!-- Simple SVG Circle Gauge -->
                                            <svg viewBox="0 0 36 36" class="circular-chart" style="width: 70px; height: 70px;">
                                                <path class="circle-bg" style="fill: none; stroke: #eee; stroke-width: 3;" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                                                <path id="ai-score-circle" class="circle" style="fill: none; stroke: #0d6efd; stroke-width: 3; stroke-linecap: round; stroke-dasharray: 0, 100; transition: stroke-dasharray 1s ease;" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                                            </svg>
                                            <div class="position-absolute fw-bold text-dark fs-5" id="ai-score-value">--</div>
                                        </div>
                                        <div>
                                            <h4 class="h6 mb-1 fw-bold text-dark">Overall AI Quality Score</h4>
                                            <span class="text-muted small" id="ai-score-label">Upload an image to evaluate.</span>
                                        </div>
                                    </div>

                                    <!-- Metrics Breakdown -->
                                    <div id="ai-metrics-breakdown" class="mb-3 d-none">
                                        <div class="d-flex flex-column gap-2 border-top pt-3">
                                            <div>
                                                <div class="d-flex justify-content-between small mb-1">
                                                    <span class="text-muted" style="font-size: 11px;">Sharpness</span>
                                                    <span class="fw-semibold text-dark" id="metric-sharpness-val" style="font-size: 11px;">0%</span>
                                                </div>
                                                <div class="progress" style="height: 5px; background-color: var(--input-bg);">
                                                    <div class="progress-bar bg-info" id="metric-sharpness-bar" style="width: 0%"></div>
                                                </div>
                                            </div>
                                            <div>
                                                <div class="d-flex justify-content-between small mb-1">
                                                    <span class="text-muted" style="font-size: 11px;">Noise Control</span>
                                                    <span class="fw-semibold text-dark" id="metric-noise-val" style="font-size: 11px;">0%</span>
                                                </div>
                                                <div class="progress" style="height: 5px; background-color: var(--input-bg);">
                                                    <div class="progress-bar bg-info" id="metric-noise-bar" style="width: 0%"></div>
                                                </div>
                                            </div>
                                            <div>
                                                <div class="d-flex justify-content-between small mb-1">
                                                    <span class="text-muted" style="font-size: 11px;">Exposure Balance</span>
                                                    <span class="fw-semibold text-dark" id="metric-exposure-val" style="font-size: 11px;">0%</span>
                                                </div>
                                                <div class="progress" style="height: 5px; background-color: var(--input-bg);">
                                                    <div class="progress-bar bg-info" id="metric-exposure-bar" style="width: 0%"></div>
                                                </div>
                                            </div>
                                            <div>
                                                <div class="d-flex justify-content-between small mb-1">
                                                    <span class="text-muted" style="font-size: 11px;">Resolution Rank</span>
                                                    <span class="fw-semibold text-dark" id="metric-resolution-val" style="font-size: 11px;">0%</span>
                                                </div>
                                                <div class="progress" style="height: 5px; background-color: var(--input-bg);">
                                                    <div class="progress-bar bg-info" id="metric-resolution-bar" style="width: 0%"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Tags list -->
                                    <div class="mb-3">
                                        <label class="form-label small fw-semibold text-muted d-block">AI Image Tags</label>
                                        <div id="ai-tags-container" class="d-flex flex-wrap gap-1">
                                            <span class="badge bg-light text-muted border">No tags generated yet.</span>
                                        </div>
                                    </div>

                                    <!-- Suggestions -->
                                    <div>
                                        <label class="form-label small fw-semibold text-muted d-block">Intelligent Action Suggestions</label>
                                        <div id="ai-suggestions-container" class="d-flex flex-column gap-2">
                                            <div class="alert alert-light border p-2 small mb-0">No suggestions available.</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Accordion 2: Upscale -->
                        <div class="accordion-item card card-premium border-0 mb-3 overflow-hidden">
                            <h2 class="accordion-header" id="heading-ai-upscale">
                                <button class="accordion-button collapsed fw-semibold text-dark" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-ai-upscale" aria-expanded="false" aria-controls="collapse-ai-upscale">
                                    <i class="fa-solid fa-maximize text-primary me-2"></i>AI Super Resolution (Upscale)
                                </button>
                            </h2>
                            <div id="collapse-ai-upscale" class="accordion-collapse collapse" aria-labelledby="heading-ai-upscale" data-bs-parent="#ai-toolbox-accordion">
                                <div class="accordion-body p-3">
                                    <p class="text-muted small mb-3">Enlarge images using Real-ESRGAN upscaling network. Enhances details and removes blur.</p>
                                    <div class="mb-3">
                                        <label class="form-label small fw-semibold text-muted d-block">Upscale Scale Factor</label>
                                        <div class="btn-group w-100" role="group">
                                            <input type="radio" class="btn-check" name="ai-upscale-scale" id="ai-upscale-2x" value="2" checked>
                                            <label class="btn btn-outline-primary btn-sm py-2" for="ai-upscale-2x">2x Resolution</label>
                                            <input type="radio" class="btn-check" name="ai-upscale-scale" id="ai-upscale-4x" value="4">
                                            <label class="btn btn-outline-primary btn-sm py-2" for="ai-upscale-4x">4x Resolution</label>
                                        </div>
                                    </div>
                                    <button class="btn btn-primary w-100 fw-semibold py-2 btn-sm" onclick="ImageLab.applyAITool('upscale')">
                                        <i class="fa-solid fa-wand-magic-sparkles me-1"></i>Run AI Upscale
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Accordion 3: Face Enhance -->
                        <div class="accordion-item card card-premium border-0 mb-3 overflow-hidden">
                            <h2 class="accordion-header" id="heading-ai-face">
                                <button class="accordion-button collapsed fw-semibold text-dark" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-ai-face" aria-expanded="false" aria-controls="collapse-ai-face">
                                    <i class="fa-solid fa-face-smile text-primary me-2"></i>AI Face Restoration (GFPGAN)
                                </button>
                            </h2>
                            <div id="collapse-ai-face" class="accordion-collapse collapse" aria-labelledby="heading-ai-face" data-bs-parent="#ai-toolbox-accordion">
                                <div class="accordion-body p-3">
                                    <p class="text-muted small mb-3">Detect and restore blurred or low-quality faces using generative AI. Softens and sharpens facial features.</p>
                                    <button class="btn btn-primary w-100 fw-semibold py-2 btn-sm" onclick="ImageLab.applyAITool('face-enhance')">
                                        <i class="fa-solid fa-face-smile me-1"></i>Restore Faces
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Accordion 4: Remove Background -->
                        <div class="accordion-item card card-premium border-0 mb-3 overflow-hidden">
                            <h2 class="accordion-header" id="heading-ai-bg">
                                <button class="accordion-button collapsed fw-semibold text-dark" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-ai-bg" aria-expanded="false" aria-controls="collapse-ai-bg">
                                    <i class="fa-solid fa-scissors text-primary me-2"></i>AI Background Removal
                                </button>
                            </h2>
                            <div id="collapse-ai-bg" class="accordion-collapse collapse" aria-labelledby="heading-ai-bg" data-bs-parent="#ai-toolbox-accordion">
                                <div class="accordion-body p-3">
                                    <p class="text-muted small mb-3">Automatically segment and remove backgrounds using AI models. Outputs a transparent PNG.</p>
                                    <button class="btn btn-primary w-100 fw-semibold py-2 btn-sm" onclick="ImageLab.applyAITool('background-remove')">
                                        <i class="fa-solid fa-scissors me-1"></i>Remove Background
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Accordion 5: Auto Enhance -->
                        <div class="accordion-item card card-premium border-0 mb-3 overflow-hidden">
                            <h2 class="accordion-header" id="heading-ai-auto">
                                <button class="accordion-button collapsed fw-semibold text-dark" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-ai-auto" aria-expanded="false" aria-controls="collapse-ai-auto">
                                    <i class="fa-solid fa-wand-magic text-primary me-2"></i>AI Smart Auto-Enhance
                                </button>
                            </h2>
                            <div id="collapse-ai-auto" class="accordion-collapse collapse" aria-labelledby="heading-ai-auto" data-bs-parent="#ai-toolbox-accordion">
                                <div class="accordion-body p-3">
                                    <p class="text-muted small mb-3">Evaluate exposure, contrast, temperature, and sharpness automatically, and chains multi-step correction rules to fix overall quality.</p>
                                    <button class="btn btn-primary w-100 fw-semibold py-2 btn-sm" onclick="ImageLab.applyAITool('auto-enhance')">
                                        <i class="fa-solid fa-wand-magic me-1"></i>Run Smart Auto Enhance
                                    </button>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- Column 2: Split Slider Interactive Comparison View -->
                <div class="col-lg-7">
                    <div id="ai-alert-box" class="mb-3"></div>
                    <div class="card card-premium border-0 mb-4 d-flex flex-column">
                        <div class="card-body p-4 d-flex flex-column">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h3 class="card-title-main mb-0"><i class="fa-solid fa-images text-primary me-2"></i>AI Result Comparison</h3>
                                <div class="d-flex gap-2">
                                    <a id="ai-download-btn" href="#" class="btn btn-sm btn-success disabled"><i class="fa-solid fa-download me-1"></i>Download</a>
                                </div>
                            </div>

                            <div id="ai-orig-empty" class="preview-empty-state flex-grow-1 d-flex flex-column align-items-center justify-content-center py-5">
                                <i class="fa-solid fa-robot fs-1 text-muted mb-3"></i>
                                <p class="small text-muted mb-0">Upload an image to activate AI processing</p>
                            </div>

                            <div id="ai-comparison-viewport" class="d-none flex-grow-1">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <div class="text-muted mb-2 small fw-semibold"><i class="fa-solid fa-image me-1"></i> Original</div>
                                        <div class="preview-image-container">
                                            <img id="ai-comp-img-before" src="" alt="Original">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="text-muted mb-2 small fw-semibold"><i class="fa-solid fa-wand-magic-sparkles me-1"></i> Result</div>
                                        <div class="preview-image-container">
                                            <img id="ai-comp-img-after" src="" alt="Result">
                                        </div>
                                    </div>
                                </div>

                                <div class="table-responsive mt-3 mb-0">
                                    <table class="table table-sm table-borderless small mb-0">
                                        <tbody>
                                            <tr class="border-bottom">
                                                <td class="text-muted">Original Size</td>
                                                <td class="fw-semibold text-dark text-end" id="ai-meta-old-size">0 KB</td>
                                                <td class="text-muted ps-4">Dimensions</td>
                                                <td class="fw-semibold text-dark text-end" id="ai-orig-meta">Dimensions: 0x0</td>
                                            </tr>
                                            <tr>
                                                <td class="text-muted">Processed Size</td>
                                                <td class="fw-semibold text-dark text-end" id="ai-meta-new-size">0 KB</td>
                                                <td class="text-muted ps-4">Savings</td>
                                                <td class="fw-semibold text-success text-end" id="ai-meta-saved-pct">0%</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

            </div>
        </div>


        <!-- ================= PANEL 6: CONFIG SETTINGS ================= -->
        <div id="panel-config" class="dashboard-panel d-none">
            <h2 class="h4 mb-4 fw-bold text-dark"><i class="fa-solid fa-gears text-primary me-2"></i>Config Settings</h2>
            
            <div class="row g-4">
                <div class="col-lg-6">
                    <div class="card card-premium border-0">
                        <div class="card-body p-4">
                            <h3 class="card-title-main mb-3">Database Connection parameters</h3>
                            <table class="table table-borderless align-middle mb-0 small">
                                <tbody>
                                    <tr class="border-bottom">
                                        <td class="text-muted py-2">MySQL Host</td>
                                        <td class="fw-semibold text-dark py-2 text-end"><?= Config::DB_HOST ?></td>
                                    </tr>
                                    <tr class="border-bottom">
                                        <td class="text-muted py-2">Database Name</td>
                                        <td class="fw-semibold text-dark py-2 text-end"><?= Config::DB_NAME ?></td>
                                    </tr>
                                    <tr class="border-bottom">
                                        <td class="text-muted py-2">User Account</td>
                                        <td class="fw-semibold text-dark py-2 text-end"><?= Config::DB_USER ?></td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted py-2">Password Configuration</td>
                                        <td class="fw-semibold text-dark py-2 text-end"><?= empty(Config::DB_PASS) ? 'Empty (Laragon Default)' : '***' ?></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="card card-premium border-0">
                        <div class="card-body p-4">
                            <h3 class="card-title-main mb-3">System Threshold Constraints</h3>
                            <table class="table table-borderless align-middle mb-0 small">
                                <tbody>
                                    <tr class="border-bottom">
                                        <td class="text-muted py-2">Max Single File Upload</td>
                                        <td class="fw-semibold text-dark py-2 text-end"><?= Config::MAX_FILE_SIZE / (1024*1024) ?> MB</td>
                                    </tr>
                                    <tr class="border-bottom">
                                        <td class="text-muted py-2">Max Batch File Limit</td>
                                        <td class="fw-semibold text-dark py-2 text-end"><?= Config::MAX_BATCH_FILES ?> Files</td>
                                    </tr>
                                    <tr class="border-bottom">
                                        <td class="text-muted py-2">Max Batch File Size Total</td>
                                        <td class="fw-semibold text-dark py-2 text-end"><?= Config::MAX_BATCH_SIZE / (1024*1024) ?> MB</td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted py-2">Upload Directory</td>
                                        <td class="fw-semibold text-secondary py-2 text-end" style="font-size: 10px;"><?= realpath(Config::UPLOAD_PATH) ?></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ================= PANEL 10: USER DASHBOARD ================= -->
        <div id="panel-user-dashboard" class="dashboard-panel d-none">
            <div class="row mb-4">
                <div class="col">
                    <h2 class="h4 mb-1 fw-bold text-dark">Welcome back, <span id="ud-user-name">User</span>!</h2>
                    <p class="text-muted mb-0 small">Manage your saved project workspaces, subscription plan, and developer API keys.</p>
                </div>
            </div>

            <!-- Quick SaaS Widgets -->
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="card card-premium p-3 border-0 bg-white shadow-sm">
                        <div class="d-flex align-items-center gap-3">
                            <div class="bg-primary-subtle text-primary rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                                <i class="fa-solid fa-cloud-arrow-up fs-5"></i>
                            </div>
                            <div>
                                <span class="text-muted d-block small mb-1">Plan Level</span>
                                <h5 class="fw-bold text-dark mb-0" id="ud-user-role">USER</h5>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card card-premium p-3 border-0 bg-white shadow-sm">
                        <div class="d-flex align-items-center gap-3">
                            <div class="bg-warning-subtle text-warning rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                                <i class="fa-solid fa-folder-closed fs-5"></i>
                            </div>
                            <div>
                                <span class="text-muted d-block small mb-1">Storage Footprint</span>
                                <h5 class="fw-bold text-dark mb-0">Local SSD Enabled</h5>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card card-premium p-3 border-0 bg-white shadow-sm">
                        <div class="d-flex align-items-center gap-3">
                            <div class="bg-success-subtle text-success rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                                <i class="fa-solid fa-robot fs-5"></i>
                            </div>
                            <div>
                                <span class="text-muted d-block small mb-1">AI Credits Pool</span>
                                <h5 class="fw-bold text-dark mb-0">Dynamic Resets</h5>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-lg-8">
                    <div class="card card-premium border-0 shadow-sm bg-white mb-4">
                        <div class="card-body p-4">
                            <h3 class="card-title-main mb-3"><i class="fa-solid fa-folder-open text-primary me-2"></i>Recent Saved Workspaces</h3>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle" style="font-size:13px;">
                                    <thead>
                                        <tr class="table-light">
                                            <th>Project Name</th>
                                            <th>Last Saved</th>
                                            <th class="text-end">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="ud-recent-projects-tbody">
                                        <tr><td colspan="3" class="text-center text-muted py-3">Loading projects...</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card card-premium border-0 shadow-sm bg-white">
                        <div class="card-body p-4">
                            <h3 class="card-title-main mb-3"><i class="fa-solid fa-circle-question text-primary me-2"></i>Quick Help</h3>
                            <p class="small text-muted mb-2">ImageLab SaaS provides cloud workspace saving. Save your projects, resume editing on any browser, or use developer API keys to automate conversions on remote hosts.</p>
                            <a href="#" onclick="event.preventDefault(); document.getElementById('nav-developer').click();" class="btn btn-sm btn-light w-100 border py-2">Get API Keys</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ================= PANEL 11: BILLING & PLANS ================= -->
        <div id="panel-billing" class="dashboard-panel d-none">
            <div class="row mb-4">
                <div class="col">
                    <h2 class="h4 mb-1 fw-bold text-dark">Billing & Subscription Plans</h2>
                    <p class="text-muted mb-0 small">Select a pricing plan to fit your image optimization demands.</p>
                </div>
            </div>

            <!-- Pricing Matrix -->
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="card border-0 bg-white shadow-sm text-center p-3 h-100">
                        <div class="card-body p-2 d-flex flex-column justify-content-between">
                            <div>
                                <h4 class="h6 text-muted text-uppercase fw-bold mb-2">Free</h4>
                                <h3 class="fw-bold mb-3">$0<span class="text-muted fs-6">/mo</span></h3>
                                <ul class="list-unstyled text-start small text-muted gap-2 d-flex flex-column my-3">
                                    <li><i class="fa-solid fa-check text-success me-2"></i>20 uploads / day</li>
                                    <li><i class="fa-solid fa-check text-success me-2"></i>5 AI requests / day</li>
                                    <li><i class="fa-solid fa-xmark text-danger me-2"></i>No API Access</li>
                                    <li><i class="fa-solid fa-check text-success me-2"></i>50 MB storage limit</li>
                                </ul>
                            </div>
                            <button class="btn btn-outline-secondary w-100 py-2 btn-sm disabled mt-3">Active Free Plan</button>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border border-primary bg-white shadow-sm text-center p-3 h-100">
                        <div class="card-body p-2 d-flex flex-column justify-content-between">
                            <div>
                                <span class="badge bg-primary text-white mb-2" style="font-size:10px;">POPULAR</span>
                                <h4 class="h6 text-muted text-uppercase fw-bold mb-2">Starter</h4>
                                <h3 class="fw-bold mb-3">$9.99<span class="text-muted fs-6">/mo</span></h3>
                                <ul class="list-unstyled text-start small text-muted gap-2 d-flex flex-column my-3">
                                    <li><i class="fa-solid fa-check text-success me-2"></i>500 uploads / month</li>
                                    <li><i class="fa-solid fa-check text-success me-2"></i>100 AI requests / month</li>
                                    <li><i class="fa-solid fa-check text-success me-2"></i>Developer API access</li>
                                    <li><i class="fa-solid fa-check text-success me-2"></i>500 MB storage limit</li>
                                </ul>
                            </div>
                            <button class="btn btn-primary w-100 py-2 btn-sm mt-3" onclick="ImageLabSaaS.upgradePlan('starter')">Upgrade Starter</button>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 bg-white shadow-sm text-center p-3 h-100">
                        <div class="card-body p-2 d-flex flex-column justify-content-between">
                            <div>
                                <h4 class="h6 text-muted text-uppercase fw-bold mb-2">Professional</h4>
                                <h3 class="fw-bold mb-3">$29.99<span class="text-muted fs-6">/mo</span></h3>
                                <ul class="list-unstyled text-start small text-muted gap-2 d-flex flex-column my-3">
                                    <li><i class="fa-solid fa-check text-success me-2"></i>Unlimited uploads</li>
                                    <li><i class="fa-solid fa-check text-success me-2"></i>1000 AI requests / month</li>
                                    <li><i class="fa-solid fa-check text-success me-2"></i>Full Developer REST API</li>
                                    <li><i class="fa-solid fa-check text-success me-2"></i>5 GB storage limit</li>
                                </ul>
                            </div>
                            <button class="btn btn-primary w-100 py-2 btn-sm mt-3" onclick="ImageLabSaaS.upgradePlan('professional')">Upgrade Pro</button>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 bg-white shadow-sm text-center p-3 h-100">
                        <div class="card-body p-2 d-flex flex-column justify-content-between">
                            <div>
                                <h4 class="h6 text-muted text-uppercase fw-bold mb-2">Enterprise</h4>
                                <h3 class="fw-bold mb-3">$99.99<span class="text-muted fs-6">/mo</span></h3>
                                <ul class="list-unstyled text-start small text-muted gap-2 d-flex flex-column my-3">
                                    <li><i class="fa-solid fa-check text-success me-2"></i>Unlimited uploads</li>
                                    <li><i class="fa-solid fa-check text-success me-2"></i>Unlimited AI calls</li>
                                    <li><i class="fa-solid fa-check text-success me-2"></i>Full REST API & Webhooks</li>
                                    <li><i class="fa-solid fa-check text-success me-2"></i>50 GB storage limit</li>
                                </ul>
                            </div>
                            <button class="btn btn-primary w-100 py-2 btn-sm mt-3" onclick="ImageLabSaaS.upgradePlan('enterprise')">Upgrade Enterprise</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Cancel Plan Options -->
            <div class="card card-premium border-0 bg-white shadow-sm">
                <div class="card-body p-4 d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="h6 mb-1 fw-bold text-dark">Cancel Subscription</h4>
                        <p class="text-muted small mb-0">Downgrade your active plan and revert your account to the Free tier immediately.</p>
                    </div>
                    <button class="btn btn-outline-danger btn-sm px-3 py-2 fw-semibold" onclick="ImageLabSaaS.cancelSubscription()">Cancel Plan</button>
                </div>
            </div>
        </div>

        <!-- ================= PANEL 12: DEVELOPER API ================= -->
        <div id="panel-developer" class="dashboard-panel d-none">
            <div class="row mb-4 align-items-center">
                <div class="col-sm-8">
                    <h2 class="h4 mb-1 fw-bold text-dark">Developer API Console</h2>
                    <p class="text-muted mb-0 small">Create secure credentials and review REST documentation to automate image tasks remote.</p>
                </div>
                <div class="col-sm-4 text-sm-end mt-2 mt-sm-0">
                    <button class="btn btn-primary btn-sm px-3 py-2 fw-semibold" onclick="ImageLabSaaS.createAPIKey()"><i class="fa-solid fa-plus me-1"></i> Generate API Key</button>
                </div>
            </div>

            <!-- Key List -->
            <div class="card card-premium border-0 bg-white shadow-sm mb-4">
                <div class="card-body p-4">
                    <h3 class="card-title-main mb-3"><i class="fa-solid fa-key text-primary me-2"></i>Your API Credentials</h3>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle" style="font-size:13px;">
                            <thead>
                                <tr class="table-light">
                                    <th>Key Name</th>
                                    <th>API Key Token</th>
                                    <th>Status</th>
                                    <th>Last Used</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody id="dev-keys-tbody">
                                <tr><td colspan="5" class="text-center text-muted py-3">Loading credentials...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- API Docs -->
            <div class="card card-premium border-0 bg-white shadow-sm">
                <div class="card-body p-4">
                    <h3 class="card-title-main mb-3"><i class="fa-solid fa-book-open text-primary me-2"></i>REST API Integration Documentation</h3>
                    <p class="small text-muted">All endpoints require the API Key sent in the headers as <code>X-API-Key: il_yourkeyhere</code>.</p>
                    
                    <hr class="my-3">

                    <div class="mb-4">
                        <h4 class="h6 fw-bold text-dark mb-2">1. Image Upload: <code>POST /api/v1/api_gateway.php?endpoint=upload</code></h4>
                        <p class="small text-muted mb-2">Upload a raw image to server uploads directory.</p>
                        <pre class="bg-light p-3 rounded" style="font-size:11px;">curl -X POST http://localhost/ImageLab/api/v1/api_gateway.php?endpoint=upload \
  -H "X-API-Key: il_yourkeyhere" \
  -F "image=@/path/to/my_image.png"</pre>
                    </div>

                    <div class="mb-4">
                        <h4 class="h6 fw-bold text-dark mb-2">2. Format Conversion: <code>POST /api/v1/api_gateway.php?endpoint=convert</code></h4>
                        <p class="small text-muted mb-2">Convert an uploaded filename to target format (webp, png, jpg, gif, pdf, etc.).</p>
                        <pre class="bg-light p-3 rounded" style="font-size:11px;">curl -X POST http://localhost/ImageLab/api/v1/api_gateway.php?endpoint=convert \
  -H "X-API-Key: il_yourkeyhere" \
  -F "filename=myfile123.png" \
  -F "format=webp"</pre>
                    </div>

                    <div class="mb-4">
                        <h4 class="h6 fw-bold text-dark mb-2">3. Image Resizer: <code>POST /api/v1/api_gateway.php?endpoint=resize</code></h4>
                        <p class="small text-muted mb-2">Resize an image width/height (px).</p>
                        <pre class="bg-light p-3 rounded" style="font-size:11px;">curl -X POST http://localhost/ImageLab/api/v1/api_gateway.php?endpoint=resize \
  -H "X-API-Key: il_yourkeyhere" \
  -F "filename=myfile123.png" \
  -F "width=800" \
  -F "height=600" \
  -F "maintainRatio=1"</pre>
                    </div>
                </div>
            </div>
        </div>

        <!-- ================= PANEL 13: ADMIN PANEL ================= -->
        <div id="panel-admin" class="dashboard-panel d-none">
            <div class="row mb-4">
                <div class="col">
                    <h2 class="h4 mb-1 fw-bold text-dark">Admin Control Dashboard</h2>
                    <p class="text-muted mb-0 small">Audit logins, update plan user roles, and monitor system analytics graphs.</p>
                </div>
            </div>

            <!-- Admin Summary -->
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="card border-0 bg-white shadow-sm p-3">
                        <span class="text-muted d-block small mb-1">Total Users</span>
                        <h4 class="fw-bold text-dark mb-0" id="ad-total-users">0</h4>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 bg-white shadow-sm p-3">
                        <span class="text-muted d-block small mb-1">Gross Revenue</span>
                        <h4 class="fw-bold text-dark mb-0 text-success" id="ad-total-revenue">$0.00</h4>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 bg-white shadow-sm p-3">
                        <span class="text-muted d-block small mb-1">Total Storage Footprint</span>
                        <h4 class="fw-bold text-dark mb-0" id="ad-total-storage">0 MB</h4>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 bg-white shadow-sm p-3">
                        <span class="text-muted d-block small mb-1">AI Queue Operations</span>
                        <h4 class="fw-bold text-dark mb-0" id="ad-total-ai">0</h4>
                    </div>
                </div>
            </div>

            <!-- Charts Row -->
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="card border-0 bg-white shadow-sm p-3">
                        <h5 class="card-title h6 fw-bold mb-3 text-muted">Daily Active Users</h5>
                        <canvas id="ad-users-chart" style="max-height:220px;"></canvas>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-0 bg-white shadow-sm p-3">
                        <h5 class="card-title h6 fw-bold mb-3 text-muted">Gross Billing Revenue</h5>
                        <canvas id="ad-revenue-chart" style="max-height:220px;"></canvas>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-0 bg-white shadow-sm p-3">
                        <h5 class="card-title h6 fw-bold mb-3 text-muted">Feature Usage Split</h5>
                        <canvas id="ad-features-chart" style="max-height:220px;"></canvas>
                    </div>
                </div>
            </div>

            <!-- User Database management -->
            <div class="card border-0 bg-white shadow-sm">
                <div class="card-body p-4">
                    <h3 class="card-title-main mb-3"><i class="fa-solid fa-users text-primary me-2"></i>User Database Management</h3>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle" style="font-size:13px;">
                            <thead>
                                <tr class="table-light">
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Plan</th>
                                    <th>Verified</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="ad-users-tbody">
                                <tr><td colspan="7" class="text-center text-muted py-3">Loading users directory...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- ================= PANEL 14: RESET PASSWORD FORM ================= -->
        <div id="panel-reset-password" class="dashboard-panel d-none">
            <div class="row justify-content-center">
                <div class="col-md-6">
                    <div class="card border-0 bg-white shadow p-4 mt-5">
                        <div class="card-body">
                            <h3 class="card-title h5 fw-bold text-dark text-center mb-3">Reset Password</h3>
                            <form id="saas-reset-form">
                                <div class="mb-3">
                                    <label class="form-label small fw-semibold text-muted">New Password</label>
                                    <input type="password" class="form-control" id="reset-password-val" placeholder="At least 6 characters" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label small fw-semibold text-muted">Confirm New Password</label>
                                    <input type="password" class="form-control" id="reset-password-confirm" placeholder="Confirm password" required>
                                </div>
                                <button type="submit" class="btn btn-primary w-100 py-2 mt-2 fw-semibold">Save New Password</button>
                            </form>
                            <div id="reset-alert-box" class="mt-3"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ================= PANEL 13: HOMEPAGE / HERO ================= -->
        <div id="panel-home" class="dashboard-panel d-none position-relative overflow-hidden" style="min-height: 80vh; background-color: var(--void-bg);">
            <!-- Large faint aperture blade watermark behind the hero content -->
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" class="position-absolute start-50 top-50 translate-middle" style="width: 500px; height: 500px; opacity: 0.04; stroke: var(--steel-blue); stroke-width: 0.5; fill: none; pointer-events: none; z-index: 0;">
                <circle cx="50" cy="50" r="45" />
                <line x1="5" y1="50" x2="80" y2="95" />
                <line x1="95" y1="50" x2="20" y2="5" />
                <line x1="50" y1="5" x2="95" y2="80" />
                <line x1="50" y1="95" x2="5" y2="20" />
            </svg>

            <div class="position-relative z-1 text-center py-5 px-3">
                <!-- Headline -->
                <h1 class="fw-bold mb-4" style="font-family: 'IBM Plex Sans', sans-serif; font-size: 58px; color: var(--text-primary); max-width: 800px; margin: 0 auto; line-height: 1.1;">
                    Precision Image Engineering
                </h1>
                
                <!-- Trust Badges -->
                <div class="d-flex flex-wrap justify-content-center gap-2 mb-5">
                    <span class="badge" style="border: 1px solid var(--steel-blue); color: var(--text-muted); font-size: 11px; letter-spacing: 0.05em;">Lossless Compression</span>
                    <span class="badge" style="border: 1px solid var(--steel-blue); color: var(--text-muted); font-size: 11px; letter-spacing: 0.05em;">Batch Processing</span>
                    <span class="badge" style="border: 1px solid var(--steel-blue); color: var(--text-muted); font-size: 11px; letter-spacing: 0.05em;">AI Enhancement</span>
                    <span class="badge" style="border: 1px solid var(--steel-blue); color: var(--text-muted); font-size: 11px; letter-spacing: 0.05em;">MySQL Backed</span>
                </div>

                <!-- Stats Row -->
                <div class="row justify-content-center g-4 py-4 border-top border-bottom" style="border-color: rgba(43, 108, 176, 0.15) !important; max-width: 900px; margin: 0 auto;">
                    <!-- Left Pixel Cluster Bullet -->
                    <div class="col-12 col-md-1 d-flex align-items-center justify-content-center">
                        <div style="width: 7px; height: 7px; background: linear-gradient(var(--orange-cta), var(--orange-cta)) 0 0, linear-gradient(var(--orange-cta), var(--orange-cta)) 4px 0, linear-gradient(var(--orange-cta), var(--orange-cta)) 0 4px, linear-gradient(var(--orange-cta), var(--orange-cta)) 4px 4px; background-size: 3px 3px; background-repeat: no-repeat; opacity: 0.4;"></div>
                    </div>
                    <div class="col-12 col-md-3">
                        <div style="font-family: 'IBM Plex Mono', monospace; font-size: 42px; color: var(--text-primary); font-weight: 500; line-height: 1.1;">1,048,576</div>
                        <div style="font-family: 'IBM Plex Sans', sans-serif; font-size: 10px; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.1em; margin-top: 4px;">Files Processed</div>
                    </div>
                    <div class="col-12 col-md-3">
                        <div style="font-family: 'IBM Plex Mono', monospace; font-size: 42px; color: var(--text-primary); font-weight: 500; line-height: 1.1;">99.998%</div>
                        <div style="font-family: 'IBM Plex Sans', sans-serif; font-size: 10px; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.1em; margin-top: 4px;">API Uptime</div>
                    </div>
                    <div class="col-12 col-md-3">
                        <div style="font-family: 'IBM Plex Mono', monospace; font-size: 42px; color: var(--text-primary); font-weight: 500; line-height: 1.1;">4,096 GB</div>
                        <div style="font-family: 'IBM Plex Sans', sans-serif; font-size: 10px; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.1em; margin-top: 4px;">Storage Saved</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ================= PANEL 14: SHOP / PRODUCT LISTINGS ================= -->
        <div id="panel-shop" class="dashboard-panel d-none">
            <h2 class="h4 mb-4 fw-bold text-dark"><i class="fa-solid fa-store me-2 text-primary"></i>SaaS Subscriptions Shop</h2>

            <!-- Search and Filter Bar -->
            <div class="d-flex align-items-center mb-4 p-0" style="background-color: var(--panel-bg); border: 1px solid var(--steel-blue); border-radius: var(--radius-max); overflow: hidden;">
                <input type="text" class="form-control bg-transparent border-0 px-3 py-2 flex-grow-1" placeholder="Search billing plans..." style="font-family: 'IBM Plex Sans', sans-serif; font-size: 13px; color: var(--text-primary);">
                <div style="width: 1px; height: 24px; background-color: rgba(43, 108, 176, 0.2);"></div>
                <select class="form-select bg-transparent border-0 py-2 px-3 text-muted" style="width: 160px; font-size: 13px;">
                    <option value="all">All Plan Cycles</option>
                    <option value="monthly">Monthly Bills</option>
                    <option value="annual">Annual Bills</option>
                </select>
                <!-- Asymmetric aperture-blade CTA button -->
                <button class="btn px-4 py-2" style="background-color: var(--orange-cta); color: var(--void-bg); font-family: 'IBM Plex Sans', sans-serif; font-size: 13px; font-weight: 500; border-radius: 4px; border-top-left-radius: 0; border-bottom-left-radius: 0; border: none;">Apply</button>
            </div>

            <!-- Products Grid -->
            <div class="row g-4">
                <!-- Product 1: Starter -->
                <div class="col-md-4">
                    <div class="card p-3 position-relative" style="background-color: var(--panel-bg); border: 1px solid rgba(43, 108, 176, 0.15); border-radius: 4px;">
                        <!-- Faint Pixel cluster watermark inside card -->
                        <div class="position-absolute" style="top: 8px; left: 8px; width: 7px; height: 7px; background: linear-gradient(var(--orange-cta), var(--orange-cta)) 0 0, linear-gradient(var(--orange-cta), var(--orange-cta)) 4px 0, linear-gradient(var(--orange-cta), var(--orange-cta)) 0 4px, linear-gradient(var(--orange-cta), var(--orange-cta)) 4px 4px; background-size: 3px 3px; background-repeat: no-repeat; opacity: 0.03;"></div>
                        
                        <!-- Image Container -->
                        <div class="text-center p-4 mb-3" style="background-color: var(--input-bg); border-radius: 4px; height: 160px; display: flex; align-items: center; justify-content: center;">
                            <i class="fa-solid fa-compass-drafting text-muted" style="font-size: 48px;"></i>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span style="font-family: 'IBM Plex Sans', sans-serif; font-size: 10px; color: var(--text-muted); text-transform: uppercase;">Starter Tier</span>
                            <span class="badge" style="border-color: rgba(59, 159, 212, 0.3); color: var(--sky-blue); font-size: 9px; padding: 2px 6px;">IN STOCK</span>
                        </div>
                        <h3 class="h6 mb-2 fw-semibold" style="font-family: 'IBM Plex Sans', sans-serif; color: var(--text-primary);"><a href="#" onclick="event.preventDefault(); ImageLab.switchPanel('product');" class="text-decoration-none text-reset">Starter Plan</a></h3>
                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <span style="font-family: 'IBM Plex Mono', monospace; font-size: 16px; color: var(--orange-cta); font-weight: 500;">$9.99/mo</span>
                            <button class="btn btn-sm btn-outline-primary py-1" onclick="ImageLabSaaS.upgradePlan('starter')">Purchase</button>
                        </div>
                    </div>
                </div>

                <!-- Product 2: Professional -->
                <div class="col-md-4">
                    <div class="card p-3 position-relative" style="background-color: var(--panel-bg); border: 1px solid rgba(43, 108, 176, 0.15); border-radius: 4px;">
                        <div class="position-absolute" style="top: 8px; left: 8px; width: 7px; height: 7px; background: linear-gradient(var(--orange-cta), var(--orange-cta)) 0 0, linear-gradient(var(--orange-cta), var(--orange-cta)) 4px 0, linear-gradient(var(--orange-cta), var(--orange-cta)) 0 4px, linear-gradient(var(--orange-cta), var(--orange-cta)) 4px 4px; background-size: 3px 3px; background-repeat: no-repeat; opacity: 0.03;"></div>
                        
                        <div class="text-center p-4 mb-3" style="background-color: var(--input-bg); border-radius: 4px; height: 160px; display: flex; align-items: center; justify-content: center;">
                            <i class="fa-solid fa-wand-magic-sparkles text-muted" style="font-size: 48px;"></i>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span style="font-family: 'IBM Plex Sans', sans-serif; font-size: 10px; color: var(--text-muted); text-transform: uppercase;">Professional Tier</span>
                            <span class="badge" style="border-color: rgba(59, 159, 212, 0.3); color: var(--sky-blue); font-size: 9px; padding: 2px 6px;">IN STOCK</span>
                        </div>
                        <h3 class="h6 mb-2 fw-semibold" style="font-family: 'IBM Plex Sans', sans-serif; color: var(--text-primary);"><a href="#" onclick="event.preventDefault(); ImageLab.switchPanel('product');" class="text-decoration-none text-reset">Professional Plan</a></h3>
                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <span style="font-family: 'IBM Plex Mono', monospace; font-size: 16px; color: var(--orange-cta); font-weight: 500;">$29.99/mo</span>
                            <button class="btn btn-sm btn-outline-primary py-1" onclick="ImageLabSaaS.upgradePlan('professional')">Purchase</button>
                        </div>
                    </div>
                </div>

                <!-- Product 3: Enterprise -->
                <div class="col-md-4">
                    <div class="card p-3 position-relative" style="background-color: var(--panel-bg); border: 1px solid rgba(43, 108, 176, 0.15); border-radius: 4px;">
                        <div class="position-absolute" style="top: 8px; left: 8px; width: 7px; height: 7px; background: linear-gradient(var(--orange-cta), var(--orange-cta)) 0 0, linear-gradient(var(--orange-cta), var(--orange-cta)) 4px 0, linear-gradient(var(--orange-cta), var(--orange-cta)) 0 4px, linear-gradient(var(--orange-cta), var(--orange-cta)) 4px 4px; background-size: 3px 3px; background-repeat: no-repeat; opacity: 0.03;"></div>
                        
                        <div class="text-center p-4 mb-3" style="background-color: var(--input-bg); border-radius: 4px; height: 160px; display: flex; align-items: center; justify-content: center;">
                            <i class="fa-solid fa-server text-muted" style="font-size: 48px;"></i>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span style="font-family: 'IBM Plex Sans', sans-serif; font-size: 10px; color: var(--text-muted); text-transform: uppercase;">Enterprise Tier</span>
                            <span class="badge" style="border-color: rgba(59, 159, 212, 0.3); color: var(--sky-blue); font-size: 9px; padding: 2px 6px;">IN STOCK</span>
                        </div>
                        <h3 class="h6 mb-2 fw-semibold" style="font-family: 'IBM Plex Sans', sans-serif; color: var(--text-primary);"><a href="#" onclick="event.preventDefault(); ImageLab.switchPanel('product');" class="text-decoration-none text-reset">Enterprise Plan</a></h3>
                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <span style="font-family: 'IBM Plex Mono', monospace; font-size: 16px; color: var(--orange-cta); font-weight: 500;">$99.99/mo</span>
                            <button class="btn btn-sm btn-outline-primary py-1" onclick="ImageLabSaaS.upgradePlan('enterprise')">Purchase</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ================= PANEL 15: PRODUCT DETAIL ================= -->
        <div id="panel-product" class="dashboard-panel d-none">
            <div class="row g-0" style="border: 1px solid var(--border-color); border-radius: 4px; overflow: hidden;">
                <!-- Left Column (58%): Image & Watermark -->
                <div class="col-lg-7 p-4 position-relative" style="background-color: var(--panel-bg); min-height: 400px; display: flex; align-items: center; justify-content: center;">
                    <!-- Shutter Watermark -->
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" class="position-absolute start-50 top-50 translate-middle" style="width: 300px; height: 300px; opacity: 0.06; stroke: var(--steel-blue); stroke-width: 0.7; fill: none; pointer-events: none;">
                        <circle cx="50" cy="50" r="45" />
                        <line x1="5" y1="50" x2="80" y2="95" />
                        <line x1="95" y1="50" x2="20" y2="5" />
                        <line x1="50" y1="5" x2="95" y2="80" />
                        <line x1="50" y1="95" x2="5" y2="20" />
                    </svg>
                    
                    <!-- Core Image/Icon Surface -->
                    <div class="position-relative z-1 p-5 rounded-3" style="background-color: var(--input-bg); border: 1px solid var(--border-color); width: 260px; height: 260px; display: flex; align-items: center; justify-content: center;">
                        <i class="fa-solid fa-wand-magic-sparkles text-primary" style="font-size: 80px;"></i>
                    </div>
                </div>

                <!-- Right Column (42%): Details & Specs -->
                <div class="col-lg-5 p-4 d-flex flex-column justify-content-between" style="background-color: var(--void-bg);">
                    <div>
                        <h2 class="h3 fw-bold mb-2" style="font-family: 'IBM Plex Sans', sans-serif; color: var(--text-primary);">Professional Plan</h2>
                        <div style="font-family: 'IBM Plex Mono', monospace; font-size: 28px; color: var(--orange-cta); font-weight: 500;" class="mb-3">$29.99/mo</div>
                        
                        <hr style="border-color: rgba(43, 108, 176, 0.15) !important;" class="my-3">
                        
                        <p style="font-family: 'IBM Plex Sans', sans-serif; font-size: 14px; color: var(--text-muted); line-height: 1.8; margin-bottom: 24px;">
                            Unlock high-priority processing speeds, unlimited conversions, and 1,000 monthly AI operations with dedicated server resources for your studio layout.
                        </p>

                        <!-- Quantity Selector and Subscribe -->
                        <div class="d-flex align-items-center gap-3 mb-4">
                            <div class="d-flex align-items-center p-1" style="background-color: var(--input-bg); border: 1px solid var(--steel-blue); border-radius: 4px; height: 36px;">
                                <button class="btn btn-link p-0 px-2 text-decoration-none text-muted" style="font-size: 14px; border:none !important;" onclick="const val = document.getElementById('prod-qty'); if(val.value > 1) val.value--;">−</button>
                                <input type="text" id="prod-qty" value="1" class="border-0 bg-transparent text-center" style="width: 30px; font-family: 'IBM Plex Mono', monospace; color: var(--text-primary); font-size: 13px;" readonly>
                                <button class="btn btn-link p-0 px-2 text-decoration-none text-muted" style="font-size: 14px; border:none !important;" onclick="const val = document.getElementById('prod-qty'); val.value++;">+</button>
                            </div>
                            
                            <button class="btn btn-primary flex-grow-1" style="height: 36px; font-weight: 600;" onclick="ImageLabSaaS.upgradePlan('professional')">Subscribe Now</button>
                        </div>

                        <!-- Trust badges -->
                        <div class="d-flex flex-wrap gap-2 mb-4">
                            <span class="badge" style="border: 1px solid var(--steel-blue); color: var(--text-muted); font-size: 10px;">PRO PRIORITY</span>
                            <span class="badge" style="border: 1px solid var(--steel-blue); color: var(--text-muted); font-size: 10px;">1000 AI CREDITS</span>
                            <span class="badge" style="border: 1px solid var(--steel-blue); color: var(--text-muted); font-size: 10px;">5GB STORAGE</span>
                        </div>
                    </div>

                    <!-- Specs Table -->
                    <div class="table-responsive">
                        <table class="table table-bordered mb-0" style="font-size: 12px; font-family: 'IBM Plex Mono', monospace;">
                            <tbody>
                                <tr style="background-color: var(--panel-bg); border-color: var(--border-color);">
                                    <td class="text-muted py-2 px-3">Upload Bandwidth</td>
                                    <td class="text-end py-2 px-3">Unlimited</td>
                                </tr>
                                <tr style="background-color: var(--input-bg); border-color: var(--border-color);">
                                    <td class="text-muted py-2 px-3">Storage Allocation</td>
                                    <td class="text-end py-2 px-3">5.0 GB</td>
                                </tr>
                                <tr style="background-color: var(--panel-bg); border-color: var(--border-color);">
                                    <td class="text-muted py-2 px-3">AI Credits / Month</td>
                                    <td class="text-end py-2 px-3">1,000</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- ================= PANEL 16: ABOUT US ================= -->
        <div id="panel-about" class="dashboard-panel d-none text-center py-5 position-relative overflow-hidden" style="background-color: var(--void-bg);">
            <!-- Large Shutter background watermark -->
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" class="position-absolute start-50 top-50 translate-middle" style="width: 450px; height: 450px; opacity: 0.05; stroke: var(--steel-blue); stroke-width: 0.5; fill: none; pointer-events: none;">
                <circle cx="50" cy="50" r="45" />
                <line x1="5" y1="50" x2="80" y2="95" />
                <line x1="95" y1="50" x2="20" y2="5" />
                <line x1="50" y1="5" x2="95" y2="80" />
                <line x1="50" y1="95" x2="5" y2="20" />
            </svg>

            <div class="position-relative z-1 max-width-900 mx-auto px-3">
                <h2 class="fw-bold mb-3" style="font-family: 'IBM Plex Sans', sans-serif; font-size: 52px; color: var(--text-primary);">Our Philosophy</h2>
                <p class="mb-5" style="font-family: 'IBM Plex Sans', sans-serif; font-size: 18px; font-weight: 300; color: var(--text-muted); max-width: 600px; margin: 0 auto;">
                    We build precision layout instruments to process, convert, and scale images flawlessly.
                </p>

                <!-- Three-column story section -->
                <div class="row g-0 text-start mt-5" style="background-color: var(--panel-bg); border: 1px solid var(--border-color); border-radius: 4px; overflow: hidden;">
                    <!-- Column 1 -->
                    <div class="col-md-4 p-4 border-end position-relative" style="border-color: rgba(43, 108, 176, 0.1) !important;">
                        <h4 style="font-family: 'IBM Plex Sans', sans-serif; font-size: 13px; font-weight: 500; text-transform: uppercase; color: var(--sky-blue); letter-spacing: 0.08em;" class="mb-3">
                            Mechanical Logic
                        </h4>
                        <p style="font-family: 'IBM Plex Sans', sans-serif; font-size: 13px; color: var(--text-muted); line-height: 1.9; margin-bottom: 30px;">
                            We avoid digital bloat. Everything we create revolves around the precision geometry of the camera lens, compiling clean workflows for developers and designers alike.
                        </p>
                        <!-- Pixel cluster arrangement in the bottom-left column (8 dots, 2x4, #F6821F at 25% opacity) -->
                        <div class="position-absolute" style="bottom: 16px; left: 24px; width: 15px; height: 7px; background: 
                            linear-gradient(var(--orange-cta), var(--orange-cta)) 0 0,
                            linear-gradient(var(--orange-cta), var(--orange-cta)) 4px 0,
                            linear-gradient(var(--orange-cta), var(--orange-cta)) 8px 0,
                            linear-gradient(var(--orange-cta), var(--orange-cta)) 12px 0,
                            linear-gradient(var(--orange-cta), var(--orange-cta)) 0 4px,
                            linear-gradient(var(--orange-cta), var(--orange-cta)) 4px 4px,
                            linear-gradient(var(--orange-cta), var(--orange-cta)) 8px 4px,
                            linear-gradient(var(--orange-cta), var(--orange-cta)) 12px 4px;
                            background-size: 3px 3px; background-repeat: no-repeat; opacity: 0.25;"></div>
                    </div>
                    
                    <!-- Column 2 -->
                    <div class="col-md-4 p-4 border-end" style="border-color: rgba(43, 108, 176, 0.1) !important;">
                        <h4 style="font-family: 'IBM Plex Sans', sans-serif; font-size: 13px; font-weight: 500; text-transform: uppercase; color: var(--sky-blue); letter-spacing: 0.08em;" class="mb-3">
                            Engineered Quality
                        </h4>
                        <p style="font-family: 'IBM Plex Sans', sans-serif; font-size: 13px; color: var(--text-muted); line-height: 1.9;">
                            All filters, compression processes, and upscale networks undergo rigorous math testing to achieve optimal byte-saving metrics without sacrificing high-resolution visual layout.
                        </p>
                    </div>

                    <!-- Column 3 -->
                    <div class="col-md-4 p-4">
                        <h4 style="font-family: 'IBM Plex Sans', sans-serif; font-size: 13px; font-weight: 500; text-transform: uppercase; color: var(--sky-blue); letter-spacing: 0.08em;" class="mb-3">
                            Open API Access
                        </h4>
                        <p style="font-family: 'IBM Plex Sans', sans-serif; font-size: 13px; color: var(--text-muted); line-height: 1.9;">
                            The suite is developer-first. Generate REST API keys instantly and connect our background queue processing pipelines directly into your production pipelines.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- ================= PANEL 17: CONTACT & SUPPORT ================= -->
        <div id="panel-contact" class="dashboard-panel d-none" style="background-color: var(--void-bg);">
            <div class="mx-auto" style="max-width: 480px; padding: 40px 0;">
                <h2 class="h3 fw-bold mb-2 text-center" style="font-family: 'IBM Plex Sans', sans-serif; color: var(--text-primary);">Support Terminal</h2>
                <p class="text-center text-muted mb-4" style="font-size:13px;">Send a message to our developers. Fields validation is handled in IBM Plex Sans.</p>
                
                <form id="contact-support-form" onsubmit="event.preventDefault(); document.getElementById('contact-success-alert').classList.remove('d-none'); this.reset();">
                    <div class="mb-3">
                        <label class="form-label" style="font-family: 'IBM Plex Sans', sans-serif; font-size: 11px; font-weight: 500; text-transform: uppercase; color: var(--text-muted); letter-spacing: 0.08em;">Full Name</label>
                        <input type="text" class="form-control" placeholder="Enter name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" style="font-family: 'IBM Plex Sans', sans-serif; font-size: 11px; font-weight: 500; text-transform: uppercase; color: var(--text-muted); letter-spacing: 0.08em;">Email Address</label>
                        <input type="email" class="form-control" placeholder="Enter email" required>
                    </div>
                    <div class="mb-4">
                        <label class="form-label" style="font-family: 'IBM Plex Sans', sans-serif; font-size: 11px; font-weight: 500; text-transform: uppercase; color: var(--text-muted); letter-spacing: 0.08em;">Message Description</label>
                        <textarea class="form-control" rows="4" placeholder="Enter description of request..." required></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-100 py-2.5 fw-semibold" style="border-radius: 4px;">Submit Ticket</button>
                </form>

                <!-- Success State Container -->
                <div id="contact-success-alert" class="alert alert-primary d-none align-items-center gap-2 mt-4" style="border-color: rgba(59, 159, 212, 0.4) !important;" role="alert">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--sky-blue)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="20 6 9 17 4 12" />
                    </svg>
                    <div style="font-size: 13px; color: var(--text-primary);">Ticket submitted successfully! We will email you back shortly.</div>
                </div>
            </div>
        </div>

    </main>
</div>

<!-- ================= SAAS AUTH MODAL DIALOG ================= -->
<div class="modal fade" id="saas-auth-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width:400px;">
        <div class="modal-content border-0 shadow">
            
            <!-- Login Form Card -->
            <div class="card border-0 p-3" id="saas-login-card">
                <div class="card-body">
                    <div class="text-center mb-4">
                        <div class="brand-logo bg-primary text-white rounded-3 d-inline-flex align-items-center justify-content-center mb-2" style="width: 48px; height: 48px;">
                            <i class="fa-solid fa-compass-drafting fs-4"></i>
                        </div>
                        <h4 class="fw-bold text-dark mb-0">Sign In to ImageLab</h4>
                        <span class="text-muted small">Access cloud workspace & developer features</span>
                    </div>

                    <form id="saas-login-form">
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-muted">Email address</label>
                            <input type="email" class="form-control form-control-lg" style="font-size:14px;" id="login-email" placeholder="name@example.com" required>
                        </div>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between mb-1">
                                <label class="form-label small fw-semibold text-muted mb-0">Password</label>
                                <a href="#" class="small text-decoration-none" onclick="event.preventDefault(); ImageLabSaaS.showAuthModal('forgot')">Forgot?</a>
                            </div>
                            <input type="password" class="form-control form-control-lg" style="font-size:14px;" id="login-password" placeholder="Password" required>
                        </div>
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="login-remember">
                            <label class="form-check-label small text-muted" for="login-remember">Remember me</label>
                        </div>
                        <button type="submit" class="btn btn-primary btn-lg w-100 py-2.5 fw-semibold" style="font-size:15px;">Sign In</button>
                    </form>

                    <div id="login-alert-box" class="mt-3"></div>

                    <div class="text-center mt-3">
                        <span class="small text-muted">New to ImageLab? <a href="#" class="text-decoration-none fw-semibold" onclick="event.preventDefault(); ImageLabSaaS.showAuthModal('register')">Create account</a></span>
                    </div>
                </div>
            </div>

            <!-- Register Form Card -->
            <div class="card border-0 p-3 d-none" id="saas-register-card">
                <div class="card-body">
                    <div class="text-center mb-4">
                        <h4 class="fw-bold text-dark mb-0">Create Account</h4>
                        <span class="text-muted small">Register for 20 free daily optimization uploads</span>
                    </div>

                    <form id="saas-register-form">
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-muted">Full name</label>
                            <input type="text" class="form-control form-control-lg" style="font-size:14px;" id="reg-name" placeholder="John Doe" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-muted">Email address</label>
                            <input type="email" class="form-control form-control-lg" style="font-size:14px;" id="reg-email" placeholder="name@example.com" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-muted">Password</label>
                            <input type="password" class="form-control form-control-lg" style="font-size:14px;" id="reg-password" placeholder="At least 6 characters" required>
                        </div>
                        <button type="submit" class="btn btn-primary btn-lg w-100 py-2.5 fw-semibold" style="font-size:15px;">Sign Up</button>
                    </form>

                    <div id="reg-alert-box" class="mt-3"></div>

                    <div class="text-center mt-3">
                        <span class="small text-muted">Already have an account? <a href="#" class="text-decoration-none fw-semibold" onclick="event.preventDefault(); ImageLabSaaS.showAuthModal('login')">Sign In</a></span>
                    </div>
                </div>
            </div>

            <!-- Forgot Form Card -->
            <div class="card border-0 p-3 d-none" id="saas-forgot-card">
                <div class="card-body">
                    <div class="text-center mb-4">
                        <h4 class="fw-bold text-dark mb-0">Reset Password</h4>
                        <span class="text-muted small">Enter your email and we will send you a reset link</span>
                    </div>

                    <form id="saas-forgot-form">
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-muted">Email address</label>
                            <input type="email" class="form-control form-control-lg" style="font-size:14px;" id="forgot-email" placeholder="name@example.com" required>
                        </div>
                        <button type="submit" class="btn btn-primary btn-lg w-100 py-2.5 fw-semibold" style="font-size:15px;">Send Reset Link</button>
                    </form>

                    <div id="forgot-alert-box" class="mt-3"></div>

                    <div class="text-center mt-3">
                        <a href="#" class="small text-decoration-none fw-semibold" onclick="event.preventDefault(); ImageLabSaaS.showAuthModal('login')">Back to Sign In</a>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<?php
// Include Footer Layout
include_once __DIR__ . '/../includes/footer.php';
?>
