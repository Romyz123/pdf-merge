<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>PDF Merge Tool</title>

    <!-- Local versions for offline use -->
    <script src="js/pdf-lib.min.js"></script>

    <!-- Load PDF.js for visual page thumbnails -->
    <script src="js/pdf.min.js"></script>

    <!-- SweetAlert2 for beautiful alerts -->
    <script src="js/sweetalert2.all.min.js"></script>

    <script>
        const pdfjsLib = window['pdfjs-dist/build/pdf'];
        // Point worker to local file
        pdfjsLib.GlobalWorkerOptions.workerSrc = 'js/pdf.worker.min.js';
    </script>

    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
            display: flex;
            justify-content: center;
            padding-top: 50px;
            margin: 0;
        }

        .container {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            width: 100%;
            max-width: 500px;
        }

        h2 {
            margin-bottom: 1.5rem;
            color: #333;
            text-align: center;
        }

        .input-group {
            border: 2px dashed #cbd5e0;
            padding: 25px;
            border-radius: 8px;
            text-align: center;
            margin-bottom: 20px;
            transition: border-color 0.3s, background-color 0.3s;
            background-color: #ffffff;
            background-image: radial-gradient(#e2e8f0 1.5px, transparent 1.5px);
            background-size: 20px 20px;
        }

        .input-group.drag-active {
            border-color: #3182ce;
            background-color: #ebf8ff;
            background-image: radial-gradient(#3182ce 1.5px, transparent 1.5px);
        }

        .input-group:hover {
            border-color: #3182ce;
            background-image: radial-gradient(#cbd5e0 1.5px, transparent 1.5px);
        }

        #fileList {
            margin-bottom: 20px;
        }

        .file-item {
            display: flex;
            align-items: center;
            background: #fff;
            border: 1px solid #e2e8f0;
            padding: 10px 15px;
            margin-bottom: 8px;
            border-radius: 6px;
            cursor: grab;
            user-select: none;
        }

        .file-item.selected {
            background-color: #ebf8ff;
            border-color: #3182ce;
        }

        .file-checkbox {
            margin-right: 12px;
            cursor: pointer;
        }

        .file-item.dragging {
            opacity: 0.4;
            border: 1px dashed #3182ce;
        }

        .file-name {
            flex-grow: 1;
            font-size: 14px;
            color: #4a5568;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .controls {
            display: flex;
            gap: 5px;
        }

        button {
            border: none;
            border-radius: 4px;
            padding: 5px 10px;
            cursor: pointer;
            transition: all 0.2s;
            font-size: 14px;
        }

        .btn-preview {
            background: #e2e8f0;
            color: #2d3748;
        }

        .btn-move {
            background: #edf2f7;
            color: #4a5568;
        }

        .btn-move:hover:not(:disabled) {
            background: #e2e8f0;
        }

        .btn-move:disabled {
            opacity: 0.4;
            cursor: not-allowed;
        }

        .btn-secondary {
            background: #718096;
            color: white;
        }

        .btn-secondary:hover {
            background: #4a5568;
        }

        .btn-remove {
            background: #fff5f5;
            color: #c53030;
        }

        .btn-remove:hover {
            background: #fed7d7;
        }

        .btn-merge {
            background: #3182ce;
            color: white;
            width: 100%;
            padding: 12px;
            font-size: 16px;
            font-weight: 600;
            margin-top: 10px;
        }

        .btn-merge:hover:not(:disabled) {
            background: #2b6cb0;
        }

        .btn-merge:disabled {
            background: #cbd5e0;
            cursor: not-allowed;
        }

        .btn-group-input {
            display: flex;
            gap: 5px;
        }

        .settings-group {
            margin-bottom: 20px;
        }

        .settings-group label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 5px;
            color: #4a5568;
        }

        .settings-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            box-sizing: border-box;
            transition: border-color 0.2s;
        }

        .settings-group input:focus {
            border-color: #3182ce;
            outline: none;
        }

        .progress-container {
            display: none;
            margin-bottom: 20px;
            background: #edf2f7;
            border-radius: 8px;
            height: 24px;
            overflow: hidden;
            position: relative;
            border: 1px solid #e2e8f0;
        }

        .progress-bar {
            background: #48bb78;
            height: 100%;
            width: 0%;
            transition: width 0.3s ease;
        }

        .progress-text {
            position: absolute;
            width: 100%;
            text-align: center;
            font-size: 11px;
            line-height: 24px;
            color: #2d3748;
            font-weight: bold;
            top: 0;
        }

        #cancelBtn {
            display: none;
            margin-top: 5px;
        }

        /* Preview Modal */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
        }

        .modal-content {
            background: #fff;
            margin: 2% auto;
            padding: 20px;
            width: 80%;
            height: 90%;
            border-radius: 8px;
            position: relative;
        }

        .close-modal {
            position: absolute;
            right: 20px;
            top: 10px;
            font-size: 28px;
            cursor: pointer;
        }

        iframe {
            width: 100%;
            height: 100%;
            border: none;
        }

        /* Organizer Grid Styling */
        .page-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(130px, 1fr));
            gap: 15px;
            margin-top: 20px;
            padding: 10px;
            background: #f1f5f9;
            border-radius: 8px;
            min-height: 150px;
        }

        .page-grid.drag-active {
            border: 2px dashed #3182ce;
            background-color: #ebf8ff;
        }

        .page-card {
            background: white;
            border: 2px solid #e2e8f0;
            border-radius: 6px;
            padding: 10px;
            text-align: center;
            cursor: grab;
            position: relative;
            transition: transform 0.2s, border-color 0.2s;
            user-select: none;
        }

        .page-card {
            width: var(--thumb-width, 130px);
        }

        .page-card:hover {
            border-color: #3182ce;
            transform: translateY(-2px);
        }

        /* Drop Indicators */
        .page-card.drop-before::before {
            content: '';
            position: absolute;
            left: -10px;
            top: 0;
            bottom: 0;
            width: 5px;
            background: #3182ce;
            border-radius: 4px;
            z-index: 20;
        }

        .page-card.drop-after::after {
            content: '';
            position: absolute;
            right: -10px;
            top: 0;
            bottom: 0;
            width: 5px;
            background: #3182ce;
            border-radius: 4px;
            z-index: 20;
        }

        .page-card.selected {
            border-color: #3182ce;
            background-color: #f0f7ff;
            box-shadow: 0 0 0 2px #3182ce;
        }

        .page-card.drag-over {
            border-color: #3182ce;
            background-color: #ebf8ff;
        }

        .page-card.dragging {
            opacity: 0.5;
            border: 2px dashed #3182ce;
        }

        .page-thumbnail {
            width: 100%;
            height: var(--thumb-height, 120px);
            object-fit: contain;
            background: #eee;
            border-radius: 4px;
            margin-bottom: 8px;
        }

        .page-card.search-match {
            border-color: #f6ad55;
            background-color: #fffaf0;
            box-shadow: 0 0 0 2px #f6ad55;
        }

        .new-badge {
            position: absolute;
            top: 5px;
            right: 5px;
            background: #48bb78;
            color: white;
            font-size: 9px;
            padding: 2px 4px;
            border-radius: 3px;
            font-weight: bold;
            z-index: 10;
        }

        .page-checkbox {
            position: absolute;
            top: 5px;
            left: 5px;
            cursor: pointer;
            z-index: 10;
        }

        .page-num {
            font-weight: bold;
            font-size: 14px;
            color: #2d3748;
            margin-bottom: 5px;
        }

        .page-actions {
            display: flex;
            justify-content: center;
            gap: 4px;
        }

        .btn-tiny {
            padding: 2px 5px;
            font-size: 10px;
        }

        /* Tabs Styling */
        .tabs {
            display: flex;
            border-bottom: 2px solid #e2e8f0;
            margin-bottom: 20px;
            gap: 10px;
        }

        .tab-btn {
            background: none;
            border: none;
            padding: 10px 20px;
            font-weight: 600;
            color: #718096;
            border-bottom: 2px solid transparent;
            margin-bottom: -2px;
            border-radius: 0;
            cursor: pointer;
        }

        .selection-box {
            position: absolute;
            border: 2px solid #3182ce;
            background: rgba(49, 130, 206, 0.15);
            pointer-events: none;
            display: none;
            z-index: 9999;
        }

        /* Context Menu Styling */
        .context-menu {
            display: none;
            position: absolute;
            background: white;
            border: 1px solid #cbd5e0;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            border-radius: 8px;
            z-index: 10000;
            padding: 5px 0;
            min-width: 160px;
        }

        .context-menu-item {
            padding: 10px 15px;
            cursor: pointer;
            font-size: 14px;
            color: #4a5568;
            transition: background 0.2s;
        }

        .context-menu-item:hover {
            background-color: #ebf8ff;
            color: #3182ce;
        }

        .range-group {
            display: flex;
            gap: 5px;
            margin-top: 10px;
            align-items: center;
        }

        .tab-btn.active {
            color: #3182ce;
            border-bottom: 2px solid #3182ce;
        }

        /* Floating Selection Toolbar */
        .selection-toolbar {
            position: fixed;
            bottom: 30px;
            left: 50%;
            transform: translateX(-50%);
            background: #2d3748;
            color: white;
            padding: 10px 20px;
            border-radius: 50px;
            display: none;
            align-items: center;
            gap: 12px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            z-index: 1001;
            transition: all 0.3s ease;
        }

        .count-badge {
            background: #3182ce;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: bold;
            margin-right: 8px;
        }

        .toolbar-btn {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.2);
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 13px;
            cursor: pointer;
        }

        .toolbar-btn:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        .toolbar-btn.btn-danger {
            color: #feb2b2;
            border-color: #c53030;
        }

        .toolbar-btn.btn-danger:hover {
            background: #c53030;
            color: white;
        }

        .history-item {
            background: #fff;
            border: 1px solid #e2e8f0;
            padding: 8px 12px;
            margin-bottom: 5px;
            border-radius: 6px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 13px;
        }

        .history-date {
            font-size: 11px;
            color: #718096;
            display: block;
        }

        /* SweetAlert2 Customization */
        .swal2-popup {
            border-radius: 12px !important;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif !important;
        }

        .swal2-title {
            color: #2d3748 !important;
            font-weight: 600 !important;
        }

        .swal2-styled {
            border-radius: 6px !important;
            font-weight: 600 !important;
            padding: 10px 24px !important;
        }

        .swal2-loader {
            border-color: #48bb78 transparent #48bb78 transparent !important;
        }

        /* Custom Progress Bar inside Swal */
        .swal-progress-bar {
            background: #48bb78 !important;
        }
    </style>
</head>

<body>

    <div class="container">
        <h2>PDF Merge Tool</h2>

        <div class="tabs">
            <button class="tab-btn active" onclick="switchTab('merge')">Merge Files</button>
            <button class="tab-btn" onclick="switchTab('edit')">Page Manager</button>
        </div>

        <!-- Merge Tab -->
        <div id="mergeTab" class="tab-content">
            <div class="input-group" id="dropZone">
                <label for="pdfFiles" style="cursor: pointer; color: #3182ce; font-weight: bold;">Select PDF Files</label>
                <p style="font-size: 12px; color: #718096; margin-top: 5px;">or drag and drop them here</p>
                <input type="file" id="pdfFiles" multiple accept="application/pdf" style="display: none;">
            </div>
            <div id="mergeSelectionControls" style="display: none; margin-bottom: 15px; display: flex; gap: 8px;">
                <button class="btn-preview" onclick="selectAllMerge(true)">Select All</button>
                <button class="btn-preview" onclick="selectAllMerge(false)">Deselect All</button>
            </div>
            <div id="fileList"></div>
            <button id="clearAllBtn" class="btn-remove" style="width: 100%; margin-bottom: 20px; display: none; padding: 10px;" onclick="clearAllFiles()">Clear All Files</button>
        </div>

        <!-- Edit/Manager Tab -->
        <div id="editTab" class="tab-content" style="display: none;">
            <div style="display: flex; gap: 10px; align-items: flex-end; margin-bottom: 15px;">
                <div class="settings-group" style="margin-bottom: 0; flex-grow: 1;">
                    <label>Select PDF to Organize</label>
                    <input type="file" id="mainPdf" accept="application/pdf" onchange="loadOrganizer()">
                </div>
                <div id="undoRedoGroup" style="display: none;">
                    <button class="btn-preview" onclick="undo()" title="Undo (Ctrl+Z)">Undo</button>
                    <button class="btn-preview" onclick="redo()" title="Redo (Ctrl+Y)">Redo</button>
                </div>
            </div>

            <div id="organizerControls" style="display: none; margin-bottom: 15px; display: flex; flex-wrap: wrap; gap: 8px;">
                <button class="btn-preview" onclick="addBlankPage()">+ Insert Blank Page</button>
                <button class="btn-preview" onclick="triggerAddFile()">+ Add from File</button>
                <button id="selectAllBtn" class="btn-preview" onclick="toggleSelectAll()">Select All</button>
                <button id="splitBtn" class="btn-secondary" onclick="splitToMultiple()" title="Export every page as a separate PDF">Split into Multiple PDFs</button>
                <button id="previewOrganizedBtn" class="btn-preview" onclick="previewFullOrganizedPdf()" title="Preview organized PDF">Preview Final</button>
                <input type="file" id="addFileHidden" accept="application/pdf" style="display: none;" onchange="handleAddFileChange()">
            </div>

            <div id="rangeActions" style="display: none; padding: 10px; background: #edf2f7; border-radius: 6px; margin-bottom: 15px;">
                <label style="font-size: 12px; font-weight: bold; color: #4a5568;">Range Actions (e.g. 1, 3, 5-8)</label>
                <div class="range-group">
                    <input type="text" id="rangeInput" placeholder="Enter page numbers..." style="flex-grow: 1; padding: 5px; border: 1px solid #cbd5e0; border-radius: 4px;">
                    <button class="btn-preview btn-tiny" onclick="applyRangeAction('select')">Select</button>
                    <button class="btn-preview btn-tiny" onclick="applyRangeAction('rotate')">Rotate</button>
                    <button class="btn-preview btn-tiny" onclick="applyRangeAction('duplicate')">Duplicate</button>
                    <button class="btn-remove btn-tiny" onclick="applyRangeAction('delete')">Delete</button>
                </div>
            </div>

            <div id="searchGroup" style="display: none; margin-bottom: 15px; padding: 10px; background: #ebf4ff; border-radius: 6px;">
                <label style="font-size: 12px; font-weight: bold; color: #2b6cb0;">Search Content</label>
                <div class="range-group">
                    <input type="text" id="pageSearchInput" placeholder="Find text in pages..." style="flex-grow: 1; padding: 5px; border: 1px solid #bee3f8; border-radius: 4px;">
                    <button class="btn-preview btn-tiny" onclick="searchPages()">Search</button>
                    <button class="btn-remove btn-tiny" onclick="document.getElementById('pageSearchInput').value=''; searchPages()">Clear</button>
                </div>
            </div>

            <div id="zoomGroup" style="display: none; align-items: center; gap: 10px; margin-bottom: 10px;">
                <span style="font-size: 12px; color: #718096;">Zoom</span>
                <input type="range" id="zoomSlider" min="80" max="300" value="130" oninput="adjustZoom(this.value)">
            </div>

            <div id="pageGrid" class="page-grid">
                <p style="grid-column: 1/-1; text-align: center; color: #718096; padding: 20px;">Upload a PDF to start organizing pages...</p>
            </div>

            <button id="processEditBtn" class="btn-merge" style="margin-top: 20px;" onclick="processPageEdit()" disabled>Save Organized PDF</button>
        </div>

        <!-- Common Settings -->
        <div class="settings-group" style="margin-top: 20px;">
            <label for="outName">Output Filename</label>
            <div class="btn-group-input">
                <input type="text" id="outName" placeholder="merged-document" value="merged" style="flex-grow: 1;">
                <button type="button" class="btn-preview" onclick="pasteFromClipboard()" title="Paste from clipboard">📋 Paste</button>
            </div>
            <div style="margin-top: 10px; font-size: 14px; color: #4a5568;">
                <input type="checkbox" id="clearAfter" checked>
                <label for="clearAfter" style="display: inline; font-weight: normal;">Clear list after success</label>
            </div>
        </div>

        <div class="settings-group">
            <label for="savePath">Automatic Save Path (Optional - Local Only)</label>
            <div style="display: flex; gap: 5px;">
                <input type="text" id="savePath" style="flex-grow: 1; background-color: #f7fafc; color: #718096; cursor: default;" value="U:\01_TESP\00_COMMON\020_ACG\vouchers\MERGE" readonly>
                <button type="button" class="btn-preview" onclick="browseFolder()" title="Browse Folder">Browse</button>
                <button type="button" class="btn-preview" onclick="testPath()" title="Test Connection">Test</button>
            </div>
        </div>

        <div id="progressContainer" class="progress-container">
            <div id="progressBar" class="progress-bar"></div>
            <div id="progressText" class="progress-text">0%</div>
            <button id="cancelBtn" class="btn-remove btn-tiny" onclick="requestCancel()">Cancel Operation</button>
        </div>

        <button id="mergeBtn" class="btn-merge" onclick="mergePDFs()" disabled>Merge All Selection</button>
        <button id="openFolderBtn" class="btn-merge" style="display: none; background: #4a5568;" onclick="openFolder()">Open Destination Folder</button>

        <div id="historySection" style="margin-top: 30px; display: none; border-top: 1px solid #e2e8f0; padding-top: 20px;">
            <label style="display: block; font-size: 14px; font-weight: 600; margin-bottom: 10px; color: #4a5568;">Recent Merges</label>
            <div id="historyList"></div>
            <button class="btn-remove" style="width: 100%; margin-top: 10px; font-size: 12px; padding: 5px;" onclick="clearHistory()">Clear History</button>
        </div>
    </div>

    <!-- Floating Selection Toolbar -->
    <div id="selectionToolbar" class="selection-toolbar">
        <span id="selectionCount" class="count-badge">0 selected</span>
        <button class="toolbar-btn" onclick="rotateSelectedPages()" title="Rotate 90° clockwise">↻ Rotate</button>
        <button class="toolbar-btn" onclick="flipSelectedPages('H')" title="Flip horizontally">↔ Flip H</button>
        <button class="toolbar-btn" onclick="flipSelectedPages('V')" title="Flip vertically">↕ Flip V</button>
        <button class="toolbar-btn" onclick="duplicateSelectedPages()" title="Duplicate pages">⧉ Duplicate</button>
        <button class="toolbar-btn btn-danger" onclick="deleteSelectedPages()" title="Remove pages">✕ Delete</button>
    </div>

    <div id="previewModal" class="modal">
        <div class="modal-content">
            <span class="close-modal" onclick="closePreview()">&times;</span>
            <iframe id="previewFrame"></iframe>
        </div>
    </div>

    <div id="contextMenu" class="context-menu">
        <div class="context-menu-item" onclick="rotateFromMenu()">↻ Rotate Selection</div>
        <div class="context-menu-item" onclick="flipFromMenu('H')">↔ Flip Horizontal</div>
        <div class="context-menu-item" onclick="flipFromMenu('V')">↕ Flip Vertical</div>
        <div class="context-menu-item" onclick="duplicateFromMenu()">⧉ Duplicate Selection</div>
        <div class="context-menu-item" style="color: #c53030;" onclick="deleteFromMenu()">✕ Delete Selection</div>
    </div>

    <script>
        // --- Global UI References (Initialized first to prevent ReferenceErrors) ---
        const progressContainer = document.getElementById('progressContainer');
        const progressBar = document.getElementById('progressBar');
        const progressText = document.getElementById('progressText');
        const fileInput = document.getElementById('pdfFiles');
        const fileListContainer = document.getElementById('fileList');
        const mergeBtn = document.getElementById('mergeBtn');
        const clearAllBtn = document.getElementById('clearAllBtn');
        const openFolderBtn = document.getElementById('openFolderBtn');
        const historySection = document.getElementById('historySection');
        const historyList = document.getElementById('historyList');
        const dropZone = document.getElementById('dropZone');
        const pageGrid = document.getElementById('pageGrid');
        const selectAllBtn = document.getElementById('selectAllBtn');
        const selectionToolbar = document.getElementById('selectionToolbar');
        const selectionCountLabel = document.getElementById('selectionCount');
        const previewOrganizedBtn = document.getElementById('previewOrganizedBtn');
        const downloadIndividualBtn = document.getElementById('downloadIndividualBtn');
        const cancelBtn = document.getElementById('cancelBtn');
        const processEditBtn = document.getElementById('processEditBtn');

        /**
         * Copies filename to clipboard and automatically fills output name field
         */
        function copyToClipboard(text) {
            const cleanName = text.replace(/\.[^/.]+$/, ""); // Remove extension
            document.getElementById('outName').value = cleanName;
            navigator.clipboard.writeText(cleanName).catch(err => console.error("Clipboard copy failed", err));
        }

        /**
         * Pastes text from clipboard into the output name field
         */
        async function pasteFromClipboard() {
            try {
                const text = await navigator.clipboard.readText();
                if (text) document.getElementById('outName').value = text;
            } catch (err) {
                Swal.fire("Clipboard Error", "Clipboard access denied. Please paste manually (Ctrl+V) or use a secure connection (HTTPS).", "error");
            }
        }

        let selectedFiles = [];

        // Multi-select Marquee Logic
        let isSelecting = false;
        let startX, startY;
        const selectionBox = document.createElement('div');
        selectionBox.className = 'selection-box';
        document.body.appendChild(selectionBox);

        pageGrid.addEventListener('mousedown', (e) => {
            // Only trigger on left-click on the grid background or the cards (but not buttons/inputs)
            if (e.button !== 0) return;
            if (e.target.closest('.page-actions') || e.target.closest('.page-checkbox') || e.target.tagName === 'BUTTON' || e.target.tagName === 'INPUT') return;

            // If we click directly on a card, we usually want to allow drag-and-drop to start 
            // instead of a marquee, unless we are holding a modifier key.
            if (e.target.closest('.page-card') && !e.shiftKey && !e.ctrlKey) return;

            isSelecting = true;
            startX = e.pageX;
            startY = e.pageY;

            selectionBox.style.left = `${startX}px`;
            selectionBox.style.top = `${startY}px`;
            selectionBox.style.width = '0px';
            selectionBox.style.height = '0px';
            selectionBox.style.display = 'block';

            // Clear selection if no modifier key is held
            if (!e.ctrlKey && !e.shiftKey) {
                organizerPages.forEach(p => p.selected = false);
                renderOrganizer();
            }

            e.preventDefault(); // Prevent text selection/drag triggers
        });

        window.addEventListener('mousemove', (e) => {
            if (!isSelecting) return;

            const curX = e.pageX;
            const curY = e.pageY;

            const left = Math.min(startX, curX);
            const top = Math.min(startY, curY);
            const width = Math.abs(startX - curX);
            const height = Math.abs(startY - curY);

            selectionBox.style.left = `${left}px`;
            selectionBox.style.top = `${top}px`;
            selectionBox.style.width = `${width}px`;
            selectionBox.style.height = `${height}px`;

            const boxRect = selectionBox.getBoundingClientRect();
            const cards = document.querySelectorAll('.page-card');

            cards.forEach((card, idx) => {
                const cardRect = card.getBoundingClientRect();
                const isOverlapping = !(boxRect.right < cardRect.left ||
                    boxRect.left > cardRect.right ||
                    boxRect.bottom < cardRect.top ||
                    boxRect.top > cardRect.bottom);

                if (isOverlapping) {
                    if (!organizerPages[idx].selected) {
                        organizerPages[idx].selected = true;
                        card.classList.add('selected');
                        const cb = card.querySelector('.page-checkbox');
                        if (cb) cb.checked = true;
                    }
                }
            });
        });

        window.addEventListener('mouseup', () => {
            if (!isSelecting) return;
            isSelecting = false;
            selectionBox.style.display = 'none';
            updateOrganizerControls(); // Sync button states
        });

        // Context Menu Logic
        let contextMenuPageIndex = null;

        function showContextMenu(e, index) {
            e.preventDefault();
            contextMenuPageIndex = index;

            // Auto-select if not already part of selection (standard UX)
            if (!organizerPages[index].selected) {
                organizerPages.forEach(p => p.selected = false);
                organizerPages[index].selected = true;
                renderOrganizer();
            }

            const menu = document.getElementById('contextMenu');
            menu.style.display = 'block';
            menu.style.left = `${e.pageX}px`;
            menu.style.top = `${e.pageY}px`;
        }

        function hideContextMenu() {
            document.getElementById('contextMenu').style.display = 'none';
        }

        window.addEventListener('click', hideContextMenu);
        window.addEventListener('scroll', hideContextMenu);

        function rotateFromMenu() {
            if (contextMenuPageIndex === null) return;
            const selectedPages = organizerPages.filter(p => p.selected);
            if (selectedPages.length === 0) return;
            saveState();
            selectedPages.forEach(p => p.rotation = (p.rotation + 90) % 360);
            renderOrganizer();
        }

        function deleteFromMenu() {
            if (contextMenuPageIndex === null) return;
            deleteSelectedPages();
        }

        function flipFromMenu(axis) {
            if (contextMenuPageIndex === null) return;
            flipSelectedPages(axis);
        }

        function duplicateFromMenu() {
            if (contextMenuPageIndex === null) return;
            applyRangeAction('duplicate', true);
        }

        let organizerPages = []; // Tracks { file, sourceIndex, rotation, isBlank, thumbnail }
        let pdfjsCache = new Map(); // Cache PDF.js documents to prevent redundant re-loading
        let undoStack = [];
        let redoStack = [];
        let cancelRequested = false;

        function saveState() {
            if (undoStack.length > 30) undoStack.shift();
            undoStack.push(JSON.stringify(organizerPages.map(p => {
                const {
                    thumbnail,
                    ...rest
                } = p; // Don't stringify massive dataURLs
                return rest;
            })));
            redoStack = [];
            document.getElementById('undoRedoGroup').style.display = 'flex';
        }

        function requestCancel() {
            cancelRequested = true;
        }
        let dragSrcEl = null;

        function switchTab(tabId) {
            document.querySelectorAll('.tab-content').forEach(tab => tab.style.display = 'none');
            document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));

            document.getElementById(tabId + 'Tab').style.display = 'block';
            event.currentTarget.classList.add('active');

            // Hide open folder button when switching
            openFolderBtn.style.display = 'none';
        }

        function adjustZoom(val) {
            document.documentElement.style.setProperty('--thumb-width', val + 'px');
            document.documentElement.style.setProperty('--thumb-height', Math.floor(val * 0.92) + 'px');
        }

        function undo() {
            if (undoStack.length === 0) return;
            const currentState = JSON.stringify(organizerPages.map(p => {
                const {
                    thumbnail,
                    ...rest
                } = p;
                return rest;
            }));
            redoStack.push(currentState);
            const oldThumbnails = organizerPages.map(p => ({
                file: p.file,
                sourceIndex: p.sourceIndex,
                thumbnail: p.thumbnail
            }));
            organizerPages = JSON.parse(undoStack.pop());
            // Restore thumbnails for matching pages
            organizerPages.forEach(p => {
                const match = oldThumbnails.find(t => t.file === p.file && t.sourceIndex === p.sourceIndex);
                if (match) p.thumbnail = match.thumbnail;
            });
            renderOrganizer();
        }

        function redo() {
            if (redoStack.length === 0) return;
            const currentState = JSON.stringify(organizerPages.map(p => {
                const {
                    thumbnail,
                    ...rest
                } = p;
                return rest;
            }));
            undoStack.push(currentState);
            const oldThumbnails = organizerPages.map(p => ({
                file: p.file,
                sourceIndex: p.sourceIndex,
                thumbnail: p.thumbnail
            }));
            organizerPages = JSON.parse(redoStack.pop());
            // Restore thumbnails for matching pages to prevent re-rendering
            organizerPages.forEach(p => {
                const match = oldThumbnails.find(t => t.file === p.file && t.sourceIndex === p.sourceIndex);
                if (match) p.thumbnail = match.thumbnail;
            });
            renderOrganizer();
        }

        // File Drop Logic for Page Manager Grid
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(evt => {
            [pageGrid, dropZone].forEach(el => el.addEventListener(evt, e => {
                if (e.dataTransfer.types.includes('Files')) {
                    e.preventDefault();
                    e.stopPropagation();
                }
            }, false));
        });

        pageGrid.addEventListener('dragover', (e) => {
            if (e.dataTransfer.types.includes('Files')) pageGrid.classList.add('drag-active');
        });
        pageGrid.addEventListener('dragleave', (e) => {
            if (e.dataTransfer.types.includes('Files')) pageGrid.classList.remove('drag-active');
        });
        pageGrid.addEventListener('drop', async (e) => {
            if (e.dataTransfer.types.includes('Files')) {
                pageGrid.classList.remove('drag-active');
                const files = Array.from(e.dataTransfer.files).filter(f => f.type === 'application/pdf');
                if (files.length > 0) {
                    if (organizerPages.length === 0) {
                        loadOrganizer(files[0]);
                    } else {
                        for (const file of files) await addFileToOrganizer(file);
                    }
                }
            }
        });

        // Drag and Drop Logic
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(evt => {
            dropZone.addEventListener(evt, e => {
                e.preventDefault();
                e.stopPropagation();
            });
        });

        dropZone.addEventListener('dragover', () => dropZone.classList.add('drag-active'));
        dropZone.addEventListener('dragleave', () => dropZone.classList.remove('drag-active'));
        dropZone.addEventListener('drop', (e) => {
            dropZone.classList.remove('drag-active');
            handleFiles(e.dataTransfer.files);
        });

        fileInput.addEventListener('change', (e) => {
            handleFiles(e.target.files);
        });

        async function handleFiles(files) {
            const filesArray = Array.from(files).filter(f => f.type === 'application/pdf');
            for (const file of filesArray) {
                const entry = {
                    file: file,
                    selected: true,
                    rotation: 0,
                    thumbnail: null
                };
                selectedFiles.push(entry);
                renderList(); // Render immediately with placeholder

                try {
                    const arrayBuffer = await file.arrayBuffer();
                    const cacheKey = file.name + file.size;
                    let pdfjsDoc = pdfjsCache.get(cacheKey);
                    if (!pdfjsDoc) {
                        pdfjsDoc = await pdfjsLib.getDocument({
                            data: new Uint8Array(arrayBuffer)
                        }).promise;
                        pdfjsCache.set(cacheKey, pdfjsDoc);
                    }
                    // Generate a small thumbnail of the first page
                    entry.thumbnail = await generateThumbnail(pdfjsDoc, 1, 0.4);
                    renderList(); // Re-render once thumbnail is ready
                } catch (e) {
                    console.error("Merge list thumbnail generation failed", e);
                }
            }
            fileInput.value = '';
        }

        function selectAllMerge(status) {
            selectedFiles.forEach(item => item.selected = status);
            renderList();
        }

        function toggleMergeSelection(index) {
            selectedFiles[index].selected = !selectedFiles[index].selected;
            renderList();
        }

        function renderList() {
            fileListContainer.innerHTML = '';
            selectedFiles.forEach((entry, index) => {
                const file = entry.file;
                const item = document.createElement('div');
                item.className = `file-item ${entry.selected ? 'selected' : ''}`;
                item.draggable = true;
                item.dataset.index = index;
                item.addEventListener('dragstart', handleMergeDragStart);
                item.addEventListener('dragover', handleMergeDragOver);
                item.addEventListener('drop', handleMergeDrop);
                item.addEventListener('dragend', handleMergeDragEnd);
                const fileNameSafe = file.name.replace(/'/g, "\\'");
                item.innerHTML = `
                    <input type="checkbox" class="file-checkbox" ${entry.selected ? 'checked' : ''} onclick="toggleMergeSelection(${index})">
                    <div style="width: 32px; height: 42px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 4px; margin-right: 12px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; overflow: hidden;">
                        ${entry.thumbnail ? `<img src="${entry.thumbnail}" style="width: 100%; height: 100%; object-fit: contain; transform: rotate(${entry.rotation}deg); transition: transform 0.2s;">` : '<span style="font-size: 8px; color: #cbd5e0;">PDF</span>'}
                    </div>
                    <div class="file-name">${file.name} ${entry.rotation !== 0 ? `<span style="color: #3182ce; font-weight: bold; margin-left: 5px;">(${entry.rotation}°)</span>` : ''}</div>
                    <div class="controls">
                        <button class="btn-preview" onclick="copyToClipboard('${fileNameSafe}')" title="Copy & Use as Output Name">📋</button>
                        <button class="btn-preview" onclick="previewFile(${index})" title="Preview">👁</button>
                        <button class="btn-preview" onclick="rotateFileMerge(${index})" title="Rotate All Pages">↻</button>
                        <button class="btn-move" onclick="move(${index}, -1)" ${index === 0 ? 'disabled' : ''}>↑</button>
                        <button class="btn-move" onclick="move(${index}, 1)" ${index === selectedFiles.length - 1 ? 'disabled' : ''}>↓</button>
                        <button class="btn-remove" onclick="removeFile(${index})">✕</button>
                    </div>
                `;
                fileListContainer.appendChild(item);
            });
            const selectedCount = selectedFiles.filter(f => f.selected).length;
            mergeBtn.disabled = selectedCount < 2;
            clearAllBtn.style.display = selectedFiles.length > 0 ? 'block' : 'none';
            document.getElementById('mergeSelectionControls').style.display = selectedFiles.length > 0 ? 'flex' : 'none';
        }

        function rotateFileMerge(index) {
            selectedFiles[index].rotation = (selectedFiles[index].rotation + 90) % 360;
            renderList();
        }

        function handleMergeDragStart(e) {
            this.classList.add('dragging');
            dragSrcEl = this;
            e.dataTransfer.effectAllowed = 'move';
        }

        function handleMergeDragOver(e) {
            e.preventDefault();
            return false;
        }

        function handleMergeDrop(e) {
            e.stopPropagation();
            if (dragSrcEl !== this && dragSrcEl.classList.contains('file-item')) {
                const fromIndex = parseInt(dragSrcEl.dataset.index);
                const toIndex = parseInt(this.dataset.index);
                const item = selectedFiles.splice(fromIndex, 1)[0];
                selectedFiles.splice(toIndex, 0, item);
                renderList();
            }
            return false;
        }

        function handleMergeDragEnd() {
            this.classList.remove('dragging');
        }

        function move(index, direction) {
            saveState();
            const newIndex = index + direction;
            if (newIndex >= 0 && newIndex < selectedFiles.length) {
                [selectedFiles[index], selectedFiles[newIndex]] = [selectedFiles[newIndex], selectedFiles[index]];
                renderList();
            }
        }

        function removeFile(index) {
            selectedFiles.splice(index, 1);
            renderList();
        }

        function clearAllFiles() {
            if (selectedFiles.length > 0) {
                Swal.fire({
                    title: 'Clear All Files?',
                    text: "Are you sure you want to remove all selected files from the list?",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3182ce',
                    cancelButtonColor: '#c53030',
                    confirmButtonText: 'Yes, clear all'
                }).then((result) => {
                    if (result.isConfirmed) {
                        selectedFiles = [];
                        renderList();
                    }
                });
            }
        }

        function previewFile(index) {
            const file = selectedFiles[index].file;
            const url = URL.createObjectURL(file);
            document.getElementById('previewFrame').src = url;
            document.getElementById('previewModal').style.display = 'block';
        }

        function closePreview() {
            document.getElementById('previewModal').style.display = 'none';
            document.getElementById('previewFrame').src = '';
        }

        function addToHistory(filename, path) {
            let history = JSON.parse(localStorage.getItem('pdfMergeHistory') || '[]');
            const newEntry = {
                filename,
                path,
                timestamp: new Date().toLocaleString()
            };
            history.unshift(newEntry);
            history = history.slice(0, 5); // Keep last 5 entries
            localStorage.setItem('pdfMergeHistory', JSON.stringify(history));
            renderHistory();
        }

        function renderHistory() {
            const history = JSON.parse(localStorage.getItem('pdfMergeHistory') || '[]');
            if (history.length === 0) {
                historySection.style.display = 'none';
                return;
            }
            historySection.style.display = 'block';
            historyList.innerHTML = '';
            history.forEach(item => {
                const div = document.createElement('div');
                div.className = 'history-item';
                div.innerHTML = `
                    <div style="flex-grow: 1;">
                        <strong>${item.filename}</strong>
                        <span class="history-date">${item.timestamp}</span>
                    </div>
                    ${item.path ? `<button class="btn-preview" style="padding: 2px 6px;" onclick="openSpecificFolder('${item.path.replace(/\\/g, '\\\\')}')" title="Open Folder">📂</button>` : ''}
                `;
                historyList.appendChild(div);
            });
        }

        function clearHistory() {
            Swal.fire({
                title: 'Clear History?',
                text: "This will remove all recent merge records.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3182ce',
                cancelButtonColor: '#718096',
                confirmButtonText: 'Yes, clear it'
            }).then((result) => {
                if (result.isConfirmed) {
                    localStorage.removeItem('pdfMergeHistory');
                    renderHistory();
                }
            });
        }

        // Initialize history on load
        renderHistory();

        async function openSpecificFolder(path) {
            const formData = new FormData();
            formData.append('action', 'open_folder');
            formData.append('targetPath', path);
            try {
                await fetch('process.php', {
                    method: 'POST',
                    body: formData
                });
            } catch (e) {
                Swal.fire("Error", "Could not open folder.", "error");
            }
        }

        function handlePathChange() {
            // Hide the open folder button because the path has changed and is no longer verified
            openFolderBtn.style.display = 'none';
            document.getElementById('savePath').style.borderColor = '#e2e8f0';
        }

        async function browseFolder() {
            const currentPath = document.getElementById('savePath').value.trim();
            const formData = new FormData();
            formData.append('action', 'browse_folder');
            formData.append('targetPath', currentPath);

            try {
                const response = await fetch('process.php', {
                    method: 'POST',
                    body: formData
                });
                if (response.ok) {
                    const newPath = await response.text();
                    if (newPath) {
                        document.getElementById('savePath').value = newPath;
                        handlePathChange(); // Reset verification state until re-tested
                    }
                } else {
                    const err = await response.text();
                    if (err) Swal.fire("Browse Error", err, "error");
                }
            } catch (e) {
                Swal.fire("Error", "Could not trigger folder picker. Ensure XAMPP is running interactively.", "error");
            }
        }

        async function testPath() {
            const savePath = document.getElementById('savePath').value.trim();
            if (!savePath) {
                Swal.fire("Input Required", "Please enter a path first.", "warning");
                return;
            }
            try {
                const formData = new FormData();
                formData.append('action', 'test_path');
                formData.append('targetPath', savePath);
                const response = await fetch('process.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.text();
                const isSuccess = result.toLowerCase().includes('success');

                if (isSuccess) {
                    document.getElementById('savePath').style.borderColor = '#48bb78';
                }

                Swal.fire({
                    title: 'Path Test',
                    text: result,
                    icon: isSuccess ? 'success' : 'warning'
                });
            } catch (e) {
                Swal.fire("Connection Error", "Error connecting to local server: " + e.message, "error");
            }
        }

        async function openFolder() {
            const savePath = document.getElementById('savePath').value.trim();
            const formData = new FormData();
            formData.append('action', 'open_folder');
            formData.append('targetPath', savePath);
            try {
                await fetch('process.php', {
                    method: 'POST',
                    body: formData
                });
            } catch (e) {
                Swal.fire("Error", "Could not open folder.", "error");
            }
        }

        function togglePageSelection(index) {
            organizerPages[index].selected = !organizerPages[index].selected;
            // No saveState for selection
            renderOrganizer();
        }

        function toggleSelectAll() {
            if (organizerPages.length === 0) return;
            const allSelected = organizerPages.every(p => p.selected);
            organizerPages.forEach(p => p.selected = !allSelected);
            renderOrganizer();
        }

        function duplicateSelectedPages() {
            const selectedCount = organizerPages.filter(p => p.selected).length;
            if (selectedCount === 0) return;
            saveState();
            applyRangeAction('duplicate', true);
        }

        function rotateSelectedPages() {
            const selected = organizerPages.filter(p => p.selected);
            if (selected.length === 0) return;

            saveState();
            selected.forEach(p => {
                p.rotation = (p.rotation + 90) % 360;
            });
            renderOrganizer();
        }

        function flipSelectedPages(axis) {
            const selected = organizerPages.filter(p => p.selected);
            if (selected.length === 0) return;

            saveState();
            selected.forEach(p => {
                if (axis === 'H') p.flipH = !p.flipH;
                if (axis === 'V') p.flipV = !p.flipV;
            });
            renderOrganizer();
        }

        function parsePageRange(text, max) {
            const indices = new Set();
            const parts = text.split(',');
            parts.forEach(p => {
                const range = p.trim().split('-');
                if (range.length === 1) {
                    const val = parseInt(range[0]);
                    if (val > 0 && val <= max) indices.add(val - 1);
                } else if (range.length === 2) {
                    const start = parseInt(range[0]);
                    const end = parseInt(range[1]);
                    if (!isNaN(start) && !isNaN(end)) {
                        for (let i = Math.min(start, end); i <= Math.max(start, end); i++) {
                            if (i > 0 && i <= max) indices.add(i - 1);
                        }
                    }
                }
            });
            return Array.from(indices).sort((a, b) => a - b);
        }

        function applyRangeAction(action, useSelection = false) {
            const input = document.getElementById('rangeInput').value;
            const indices = useSelection ?
                organizerPages.map((p, i) => p.selected ? i : -1).filter(i => i !== -1) :
                parsePageRange(input, organizerPages.length);
            if (indices.length === 0 && !useSelection) return;

            if (action !== 'select') saveState();

            if (action === 'select') {
                organizerPages.forEach((p, i) => p.selected = indices.includes(i));
            } else if (action === 'rotate') {
                indices.forEach(i => organizerPages[i].rotation = (organizerPages[i].rotation + 90) % 360);
            } else if (action === 'delete') {
                organizerPages = organizerPages.filter((_, i) => !indices.includes(i));
            } else if (action === 'duplicate') {
                const newPages = [];
                organizerPages.forEach((p, i) => {
                    newPages.push(p);
                    if (indices.includes(i)) newPages.push({
                        ...p,
                        selected: false,
                        isNew: true
                    });
                });
                organizerPages = newPages;
            }
            renderOrganizer();
        }

        async function splitToMultiple() {
            const targets = organizerPages.filter(p => p.selected).length > 0 ?
                organizerPages.filter(p => p.selected) :
                organizerPages;

            const result = await Swal.fire({
                title: 'Split PDF?',
                text: `This will export ${targets.length} separate PDF files. Continue?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3182ce',
                cancelButtonColor: '#718096',
                confirmButtonText: 'Yes, split'
            });
            if (!result.isConfirmed) return;

            cancelRequested = false;

            Swal.fire({
                title: 'Splitting PDF',
                html: `
                    <div id="swal-progress-text" style="margin-bottom: 10px; font-size: 14px; color: #4a5568;">Initializing...</div>
                    <div class="progress-container" style="display: block; width: 100%; border: 1px solid #e2e8f0;">
                        <div id="swal-progress-bar" class="progress-bar swal-progress-bar" style="width: 0%;"></div>
                    </div>
                `,
                showCancelButton: true,
                cancelButtonText: 'Cancel Split',
                cancelButtonColor: '#718096',
                allowOutsideClick: false,
                showConfirmButton: false,
                didOpen: async () => {
                    Swal.showLoading();
                    const swalBar = document.getElementById('swal-progress-bar');
                    const swalText = document.getElementById('swal-progress-text');

                    try {
                        for (let i = 0; i < targets.length; i++) {
                            if (cancelRequested) throw new Error('Split cancelled');
                            const pageEntry = targets[i];
                            const progress = Math.round(((i + 1) / targets.length) * 100);

                            swalText.textContent = `Processing page ${i + 1} of ${targets.length}...`;
                            swalBar.style.width = `${progress}%`;

                            const blob = await generateSinglePageBlob(pageEntry);
                            const savePath = document.getElementById('savePath').value.trim();

                            let baseName = document.getElementById('outName').value.trim();
                            // Use source filename if output name is left as default 'merged'
                            if ((!baseName || baseName === 'merged') && !pageEntry.isBlank && pageEntry.file) {
                                baseName = pageEntry.file.name.replace(/\.[^/.]+$/, "");
                            }
                            const fileName = `${baseName || 'split'}_page_${i + 1}.pdf`;

                            if (savePath) {
                                const formData = new FormData();
                                formData.append('action', 'save_to_path');
                                formData.append('pdf', blob, fileName);
                                formData.append('targetPath', savePath);

                                const response = await fetch('process.php', {
                                    method: 'POST',
                                    body: formData
                                });

                                if (response.ok) {
                                    openFolderBtn.style.display = 'block';
                                } else {
                                    const errorMsg = await response.text();
                                    throw new Error(`Failed to save ${fileName}: ${errorMsg}`);
                                }
                            } else {
                                const link = document.createElement("a");
                                link.href = URL.createObjectURL(blob);
                                link.download = fileName;
                                link.click();
                                // Brief delay to prevent browser download queue issues
                                await new Promise(r => setTimeout(r, 100));
                            }
                        }
                        Swal.fire("Success", "Split complete.", "success");
                    } catch (e) {
                        if (e.message !== 'Split cancelled') {
                            Swal.fire("Error", e.message, "error");
                        }
                    }
                }
            }).then((result) => {
                if (result.dismiss === Swal.DismissReason.cancel) {
                    cancelRequested = true;
                    Swal.fire({
                        title: 'Cancelled',
                        text: 'Split process was stopped.',
                        icon: 'info',
                        timer: 2000,
                        showConfirmButton: false
                    });
                }
            });
        }

        async function downloadIndividualSelected() {
            const selected = organizerPages.filter(p => p.selected);
            if (selected.length === 0) {
                Swal.fire("Selection Required", "Please select pages to download.", "warning");
                return;
            }

            const savePath = document.getElementById('savePath').value.trim();
            progressContainer.style.display = 'block';
            progressText.textContent = savePath ? 'Saving pages to local path...' : 'Preparing individual downloads...';

            try {
                for (let i = 0; i < selected.length; i++) {
                    const pageEntry = selected[i];
                    const blob = await generateSinglePageBlob(pageEntry);

                    let baseName = document.getElementById('outName').value.trim();
                    // Apply same smart naming for individual exports
                    if ((!baseName || baseName === 'merged') && !pageEntry.isBlank && pageEntry.file) {
                        baseName = pageEntry.file.name.replace(/\.[^/.]+$/, "");
                    }
                    const fileName = `${baseName || 'page'}_label_${pageEntry.label}.pdf`;

                    if (savePath) {
                        const formData = new FormData();
                        formData.append('action', 'save_to_path');
                        formData.append('pdf', blob, fileName);
                        formData.append('targetPath', savePath);

                        const response = await fetch('process.php', {
                            method: 'POST',
                            body: formData
                        });

                        if (response.ok) {
                            openFolderBtn.style.display = 'block';
                        } else {
                            const errorMsg = await response.text();
                            throw new Error(`Failed to save ${fileName}: ${errorMsg}`);
                        }
                    } else {
                        const link = document.createElement("a");
                        link.href = URL.createObjectURL(blob);
                        link.download = fileName;
                        link.click();
                        // Brief delay to prevent browser download queue issues
                        await new Promise(r => setTimeout(r, 100));
                    }
                }
                if (savePath) {
                    Swal.fire("Success", `Saved ${selected.length} pages to: ${savePath}`, "success");
                }
            } catch (e) {
                Swal.fire("Error", "Error during individual download: " + e.message, "error");
            } finally {
                progressContainer.style.display = 'none';
            }
        }

        async function deleteSelectedPages() {
            const selectedCount = organizerPages.filter(p => p.selected).length;
            if (selectedCount === 0) return;

            const result = await Swal.fire({
                title: 'Delete Pages?',
                text: `Are you sure you want to delete ${selectedCount} selected pages?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#c53030',
                cancelButtonColor: '#718096',
                confirmButtonText: 'Yes, delete'
            });

            if (result.isConfirmed) {
                saveState();
                organizerPages = organizerPages.filter(p => !p.selected);
                renderOrganizer();
            }
        }

        async function loadOrganizer(droppedFile = null) {
            const file = droppedFile || document.getElementById('mainPdf').files[0];
            if (!file) return;

            try {
                progressContainer.style.display = 'block';
                progressText.textContent = 'Reading document structure...';

                const arrayBuffer = await file.arrayBuffer();
                const pdfDoc = await PDFLib.PDFDocument.load(arrayBuffer);
                const pageCount = pdfDoc.getPageCount();

                // PDF.js Caching: Reuse document if possible
                let pdfjsDoc;
                const cacheKey = file.name + file.size;
                if (pdfjsCache.has(cacheKey)) {
                    pdfjsDoc = pdfjsCache.get(cacheKey);
                } else {
                    pdfjsDoc = await pdfjsLib.getDocument({
                        data: new Uint8Array(arrayBuffer)
                    }).promise;
                    pdfjsCache.set(cacheKey, pdfjsDoc);
                }

                // Adaptive Quality: Reduce quality for very large documents to save memory
                const thumbQuality = pageCount > 50 ? 0.5 : 0.7;

                // Create placeholders immediately so UI is interactive
                organizerPages = Array.from({
                    length: pageCount
                }, (_, i) => ({
                    file,
                    sourceIndex: i,
                    rotation: 0,
                    flipH: false,
                    flipV: false,
                    isBlank: false, // Initial pages are not "new" in this context
                    selected: false,
                    label: i + 1,
                    thumbnail: null // Loaded later
                }));

                document.getElementById('organizerControls').style.display = 'flex';
                document.getElementById('rangeActions').style.display = 'block';
                document.getElementById('searchGroup').style.display = 'block';
                document.getElementById('zoomGroup').style.display = 'flex';

                processEditBtn.disabled = false;
                renderOrganizer();

                // Background rendering of thumbnails
                progressText.textContent = 'Generating previews in background...';
                loadThumbnails(pdfjsDoc, 0, organizerPages.length, thumbQuality);

            } catch (e) {
                Swal.fire("Error", "Error loading PDF: " + e.message, "error");
            } finally {
                progressContainer.style.display = 'none';
                progressBar.style.width = '0%';
            }
        }

        async function loadThumbnails(pdfjsDoc, startIndex, count, quality = 0.7) {
            for (let i = 0; i < count; i++) {
                if (cancelRequested) break;
                const idx = startIndex + i;
                // Only render if not already rendered (relevant for multiple file adds)
                if (organizerPages[idx] && !organizerPages[idx].thumbnail && !organizerPages[idx].isBlank) {
                    try {
                        const url = await generateThumbnail(pdfjsDoc, organizerPages[idx].sourceIndex + 1, quality);
                        if (!organizerPages[idx]) continue; // Guard against list cleared during async
                        organizerPages[idx].thumbnail = url;

                        // Target the specific image to avoid heavy full grid re-renders
                        const img = document.getElementById(`thumb-${idx}`);
                        if (img) {
                            img.src = url;
                            img.style.display = 'block';
                            const placeholder = img.previousElementSibling;
                            if (placeholder && placeholder.classList.contains('thumb-placeholder')) placeholder.remove();
                        }
                    } catch (e) {
                        console.warn("Thumbnail generation failed for index " + idx, e);
                    }
                    // Lazy rendering: yield to main thread every 3 pages
                    if (i % 3 === 0) await new Promise(r => setTimeout(r, 10));
                }
            }
        }

        function updateOrganizerControls() {
            if (!selectionToolbar || !selectionCountLabel) return;
            if (!selectionToolbar || !selectionCountLabel || !selectAllBtn) return;

            const selectedCount = organizerPages.filter(p => p.selected).length;

            if (selectedCount > 0) {
                selectionToolbar.style.display = 'flex';
                selectionCountLabel.textContent = `${selectedCount} Selected`;
                if (selectionCountLabel) selectionCountLabel.textContent = `${selectedCount} Selected`;
            } else {
                selectionToolbar.style.display = 'none';
            }

            selectAllBtn.textContent = (organizerPages.length > 0 && selectedCount === organizerPages.length) ? 'Deselect All' : 'Select All';
            if (selectAllBtn) selectAllBtn.textContent = (organizerPages.length > 0 && selectedCount === organizerPages.length) ? 'Deselect All' : 'Select All';
        }

        function renderOrganizer() {
            pageGrid.innerHTML = '';
            organizerPages.forEach((page, index) => {
                const card = document.createElement('div');
                card.className = `page-card ${page.selected ? 'selected' : ''} ${page.searchMatch ? 'search-match' : ''}`;
                card.draggable = true;
                card.dataset.index = index;

                card.oncontextmenu = (e) => showContextMenu(e, index);

                card.innerHTML = `
                    <input type="checkbox" class="page-checkbox" ${page.selected ? 'checked' : ''} onclick="event.stopPropagation(); togglePageSelection(${index})">
                    ${page.isNew ? '<span class="new-badge">NEW</span>' : ''}
                    ${page.isBlank 
                        ? '<div class="page-thumbnail" style="background:#fff; border:1px dashed #ccc; display:flex; align-items:center; justify-content:center; color:#ccc;">Empty</div>' 
                        : `<div class="thumb-placeholder" style="height: var(--thumb-height, 120px); background:#edf2f7; display:flex; align-items:center; justify-content:center; font-size:10px; color:#a0aec0; border-radius:4px;">Loading...</div>
                           <img id="thumb-${index}" src="${page.thumbnail || ''}" class="page-thumbnail" style="transform: rotate(${page.rotation}deg) scale(${page.flipH ? -1 : 1}, ${page.flipV ? -1 : 1}); display: ${page.thumbnail ? 'block' : 'none'}">`}
                    <div class="page-num">${page.isBlank ? 'Blank' : 'Page ' + page.label}</div>
                    <div class="page-actions">
                        <button class="btn-tiny btn-preview" onclick="previewOrganizerPage(${index})" title="Preview Page">👁</button>
                        <button class="btn-tiny btn-preview" onclick="rotatePage(${index})">↻</button>
                        <button class="btn-tiny btn-remove" onclick="deleteOrganizerPage(${index})">✕</button>
                    </div>
                `;

                card.addEventListener('click', (e) => {
                    if (e.target.tagName !== 'BUTTON' && e.target.tagName !== 'INPUT') {
                        togglePageSelection(index);
                    }
                });

                card.addEventListener('dragstart', handleDragStart);
                card.addEventListener('dragover', handleDragOver);
                card.addEventListener('dragenter', handleDragEnter);
                card.addEventListener('dragleave', handleDragLeave);
                card.addEventListener('drop', handleDrop);
                card.addEventListener('dragend', handleDragEnd);

                pageGrid.appendChild(card);
            });

            updateOrganizerControls();
        }

        function handleDragStart(e) {
            this.classList.add('dragging');
            dragSrcEl = this;
            // Set dummy data for Firefox
            e.dataTransfer.setData('text/plain', '');
            e.dataTransfer.effectAllowed = 'move';
        }

        function handleDragOver(e) {
            e.preventDefault();
            const rect = this.getBoundingClientRect();
            const relX = e.clientX - rect.left;

            this.classList.remove('drop-before', 'drop-after');
            if (relX < rect.width / 2) {
                this.classList.add('drop-before');
            } else {
                this.classList.add('drop-after');
            }
            return false;
        }

        function handleDragEnter(e) {
            // Visual feedback handled by handleDragOver
        }

        function handleDragLeave(e) {
            this.classList.remove('drop-before', 'drop-after');
        }

        function handleDrop(e) {
            e.stopPropagation();
            e.preventDefault();

            const isBefore = this.classList.contains('drop-before');
            this.classList.remove('drop-before', 'drop-after');

            // Handle File Drop directly onto a card
            if (e.dataTransfer.files && e.dataTransfer.files.length > 0) {
                const insertAt = parseInt(this.dataset.index) + (isBefore ? 0 : 1);
                const files = Array.from(e.dataTransfer.files).filter(f => f.type === 'application/pdf');
                if (files.length > 0) {
                    (async () => {
                        for (const file of files) await addFileToOrganizer(file, insertAt);
                    })();
                }
                return false;
            }

            // Handle Page Reordering
            if (dragSrcEl !== this) {
                saveState();

                const fromIndex = parseInt(dragSrcEl.dataset.index);
                let toIndex = parseInt(this.dataset.index);

                if (!isBefore && fromIndex > toIndex) toIndex++;
                if (isBefore && fromIndex < toIndex) toIndex--;

                const item = organizerPages.splice(fromIndex, 1)[0];
                organizerPages.splice(toIndex, 0, item);
                renderOrganizer();
            }
            return false;
        }

        function handleDragEnd() {
            this.classList.remove('dragging');
        }

        function rotatePage(index) {
            saveState();
            organizerPages[index].rotation = (organizerPages[index].rotation + 90) % 360;
            renderOrganizer();
        }

        function deleteOrganizerPage(index) {
            saveState();
            organizerPages.splice(index, 1);
            renderOrganizer();
        }

        function addBlankPage() {
            saveState();
            const lastSelected = organizerPages.findLastIndex(p => p.selected);

            const insertAt = lastSelected !== -1 ? lastSelected + 1 : organizerPages.length;

            organizerPages.splice(insertAt, 0, {
                isBlank: true,
                rotation: 0,
                label: 'B',
                selected: false,
                isNew: true // Blank pages are new
            });
            renderOrganizer();
            scrollToIndex(insertAt);
        }

        function triggerAddFile() {
            document.getElementById('addFileHidden').click();
        }

        async function handleAddFileChange() {
            const file = document.getElementById('addFileHidden').files[0];
            if (file) {
                const lastSelected = organizerPages.findLastIndex(p => p.selected);
                const insertAt = lastSelected !== -1 ? lastSelected + 1 : organizerPages.length;
                await addFileToOrganizer(file, insertAt);
            }
            document.getElementById('addFileHidden').value = '';
        }

        async function addFileToOrganizer(file, insertAt = -1) {
            const targetIndex = insertAt === -1 ? organizerPages.length : insertAt;
            try {
                saveState();

                progressContainer.style.display = 'block';
                progressText.textContent = 'Adding file...';

                const arrayBuffer = await file.arrayBuffer();
                const pdfDoc = await PDFLib.PDFDocument.load(arrayBuffer);
                const pageCount = pdfDoc.getPageCount();

                let pdfjsDoc;
                const cacheKey = file.name + file.size;
                if (pdfjsCache.has(cacheKey)) {
                    pdfjsDoc = pdfjsCache.get(cacheKey);
                } else {
                    pdfjsDoc = await pdfjsLib.getDocument({
                        data: new Uint8Array(arrayBuffer)
                    }).promise;
                    pdfjsCache.set(cacheKey, pdfjsDoc);
                }

                const newPages = Array.from({
                    length: pageCount
                }, (_, i) => ({
                    file,
                    sourceIndex: i,
                    rotation: 0,
                    flipH: false,
                    flipV: false,
                    isBlank: false,
                    label: 'New', // Label for newly added pages
                    selected: false,
                    isNew: true, // Pages from added file are new
                    thumbnail: null
                }));

                organizerPages.splice(targetIndex, 0, ...newPages);
                renderOrganizer();
                loadThumbnails(pdfjsDoc, targetIndex, pageCount);
                scrollToIndex(targetIndex);
            } catch (e) {
                Swal.fire("Error", "Error adding file: " + e.message, "error");
            } finally {
                progressContainer.style.display = 'none';
            }
        }

        function scrollToIndex(index) {
            setTimeout(() => {
                const el = pageGrid.children[index];
                if (el) el.scrollIntoView({
                    behavior: 'smooth',
                    block: 'center'
                });
            }, 100);
        }

        async function previewFullOrganizedPdf() {
            if (organizerPages.length === 0) {
                Swal.fire("No Pages", "No pages to preview.", "warning");
                return;
            }
            cancelRequested = false;

            Swal.fire({
                title: 'Preparing Full Preview',
                html: `
                    <div id="swal-progress-text" style="margin-bottom: 10px; font-size: 14px; color: #4a5568;">Initializing...</div>
                    <div class="progress-container" style="display: block; width: 100%; border: 1px solid #e2e8f0;">
                        <div id="swal-progress-bar" class="progress-bar swal-progress-bar" style="width: 0%;"></div>
                    </div>
                `,
                showCancelButton: true,
                cancelButtonText: 'Cancel',
                cancelButtonColor: '#718096',
                allowOutsideClick: false,
                showConfirmButton: false,
                didOpen: async () => {
                    Swal.showLoading();
                    const swalBar = document.getElementById('swal-progress-bar');
                    const swalText = document.getElementById('swal-progress-text');

                    try {
                        const {
                            PDFDocument,
                            degrees
                        } = PDFLib;
                        const resultPdf = await PDFDocument.create();
                        const fileCache = new Map();

                        for (let i = 0; i < organizerPages.length; i++) {
                            if (cancelRequested) throw new Error('Preview cancelled');
                            const pageEntry = organizerPages[i];
                            const progress = Math.round(((i + 1) / organizerPages.length) * 100);
                            swalBar.style.width = `${progress}%`;
                            swalText.textContent = `Processing page ${i + 1} of ${organizerPages.length}`;

                            if (pageEntry.isBlank) {
                                resultPdf.addPage();
                            } else {
                                if (!fileCache.has(pageEntry.file)) fileCache.set(pageEntry.file, await PDFDocument.load(await pageEntry.file.arrayBuffer()));
                                const srcDoc = fileCache.get(pageEntry.file);
                                const [copiedPage] = await resultPdf.copyPages(srcDoc, [pageEntry.sourceIndex]);
                                if (pageEntry.rotation !== 0) copiedPage.setRotation(degrees(pageEntry.rotation));

                                // Apply Flipping
                                const {
                                    width,
                                    height
                                } = copiedPage.getSize();
                                if (pageEntry.flipH) {
                                    copiedPage.translate(width, 0);
                                    copiedPage.scale(-1, 1);
                                }
                                if (pageEntry.flipV) {
                                    copiedPage.translate(0, height);
                                    copiedPage.scale(1, -1);
                                }

                                resultPdf.addPage(copiedPage);
                            }
                        }
                        const blob = new Blob([await resultPdf.save()], {
                            type: "application/pdf"
                        });
                        document.getElementById('previewFrame').src = URL.createObjectURL(blob);
                        document.getElementById('previewModal').style.display = 'block';
                        Swal.close();
                    } catch (e) {
                        if (e.message !== 'Preview cancelled') {
                            Swal.fire("Error", "Failed to generate full preview: " + e.message, "error");
                        }
                    }
                }
            }).then((result) => {
                if (result.dismiss === Swal.DismissReason.cancel) {
                    cancelRequested = true;
                }
            });
        }

        async function previewOrganizerPage(index) {
            const pageEntry = organizerPages[index];
            try {
                progressText.textContent = 'Generating preview...';
                progressContainer.style.display = 'block';
                const blob = await generateSinglePageBlob(pageEntry);
                document.getElementById('previewFrame').src = URL.createObjectURL(blob);
                document.getElementById('previewModal').style.display = 'block';
            } catch (e) {
                Swal.fire("Error", "Failed to generate preview: " + e.message, "error");
            } finally {
                progressContainer.style.display = 'none';
            }
        }

        async function generateSinglePageBlob(pageEntry) {
            const {
                PDFDocument,
                degrees
            } = PDFLib;
            const tempPdf = await PDFDocument.create();

            if (pageEntry.isBlank) {
                tempPdf.addPage();
            } else {
                const srcDoc = await PDFDocument.load(await pageEntry.file.arrayBuffer());
                const [copiedPage] = await tempPdf.copyPages(srcDoc, [pageEntry.sourceIndex]);
                if (pageEntry.rotation !== 0) {
                    copiedPage.setRotation(degrees(pageEntry.rotation));
                }

                // Apply Flipping
                const {
                    width,
                    height
                } = copiedPage.getSize();
                if (pageEntry.flipH) {
                    copiedPage.translate(width, 0);
                    copiedPage.scale(-1, 1);
                }
                if (pageEntry.flipV) {
                    copiedPage.translate(0, height);
                    copiedPage.scale(1, -1);
                }

                tempPdf.addPage(copiedPage);
            }
            const bytes = await tempPdf.save();
            return new Blob([bytes], {
                type: "application/pdf"
            });
        }

        async function generateThumbnail(pdfjsDoc, pageNum, quality = 0.7) {
            const page = await pdfjsDoc.getPage(pageNum);
            const viewport = page.getViewport({
                scale: 0.3
            });
            const canvas = document.createElement('canvas');
            const context = canvas.getContext('2d');
            canvas.height = viewport.height;
            canvas.width = viewport.width;

            await page.render({
                canvasContext: context,
                viewport: viewport
            }).promise;
            return canvas.toDataURL('image/jpeg', quality);
        }

        async function searchPages() {
            const query = document.getElementById('pageSearchInput').value.toLowerCase().trim();
            if (!query) {
                organizerPages.forEach(p => p.searchMatch = false);
                renderOrganizer();
                return;
            }

            cancelRequested = false;

            Swal.fire({
                title: 'Searching Pages',
                html: `
                    <div id="swal-progress-text" style="margin-bottom: 10px; font-size: 14px; color: #4a5568;">Searching content...</div>
                    <div class="progress-container" style="display: block; width: 100%; border: 1px solid #e2e8f0;">
                        <div id="swal-progress-bar" class="progress-bar swal-progress-bar" style="width: 0%;"></div>
                    </div>
                `,
                showCancelButton: true,
                cancelButtonText: 'Stop Search',
                cancelButtonColor: '#718096',
                allowOutsideClick: false,
                showConfirmButton: false,
                didOpen: async () => {
                    Swal.showLoading();
                    const swalBar = document.getElementById('swal-progress-bar');
                    const swalText = document.getElementById('swal-progress-text');

                    try {
                        for (let i = 0; i < organizerPages.length; i++) {
                            if (cancelRequested) break;
                            const p = organizerPages[i];

                            if (!p.isBlank && p.textContent === undefined) {
                                const cacheKey = p.file.name + p.file.size;
                                let pdfjsDoc = pdfjsCache.get(cacheKey);
                                if (!pdfjsDoc) {
                                    // Re-load document if not cached
                                    const arrayBuffer = await p.file.arrayBuffer();
                                    pdfjsDoc = await pdfjsLib.getDocument({
                                        data: new Uint8Array(arrayBuffer)
                                    }).promise;
                                    pdfjsCache.set(cacheKey, pdfjsDoc);
                                }
                                if (pdfjsDoc) {
                                    const page = await pdfjsDoc.getPage(p.sourceIndex + 1);
                                    const content = await page.getTextContent();
                                    p.textContent = content.items.map(item => item.str).join(' ').toLowerCase();
                                }
                            }
                            p.searchMatch = p.textContent && p.textContent.includes(query);
                            const progress = Math.round(((i + 1) / organizerPages.length) * 100);
                            swalBar.style.width = `${progress}%`;
                            swalText.textContent = `Searching page ${i + 1} of ${organizerPages.length}...`;
                        }
                        Swal.close();
                    } catch (e) {
                        console.error("Search failed:", e);
                        Swal.fire("Search Error", e.message, "error");
                    } finally {
                        renderOrganizer();
                    }
                }
            }).then((result) => {
                if (result.dismiss === Swal.DismissReason.cancel) {
                    cancelRequested = true;
                }
            });
        }

        async function processPageEdit() {
            if (organizerPages.length === 0) return;

            cancelRequested = false;

            Swal.fire({
                title: 'Saving Organized PDF',
                html: `
                    <div id="swal-progress-text" style="margin-bottom: 10px; font-size: 14px; color: #4a5568;">Initializing...</div>
                    <div class="progress-container" style="display: block; width: 100%; border: 1px solid #e2e8f0;">
                        <div id="swal-progress-bar" class="progress-bar swal-progress-bar" style="width: 0%;"></div>
                    </div>
                `,
                showCancelButton: true,
                cancelButtonText: 'Cancel',
                cancelButtonColor: '#718096',
                allowOutsideClick: false,
                showConfirmButton: false,
                didOpen: async () => {
                    Swal.showLoading();
                    const swalBar = document.getElementById('swal-progress-bar');
                    const swalText = document.getElementById('swal-progress-text');

                    try {
                        const {
                            PDFDocument,
                            degrees
                        } = PDFLib;
                        const resultPdf = await PDFDocument.create();
                        const fileCache = new Map();

                        for (let i = 0; i < organizerPages.length; i++) {
                            if (cancelRequested) throw new Error('Save operation cancelled');
                            const pageEntry = organizerPages[i];
                            const progress = Math.round(((i + 1) / organizerPages.length) * 100);
                            swalBar.style.width = `${progress}%`;
                            swalText.textContent = `Building page ${i + 1} of ${organizerPages.length}`;

                            if (pageEntry.isBlank) {
                                resultPdf.addPage();
                            } else {
                                if (!fileCache.has(pageEntry.file)) fileCache.set(pageEntry.file, await PDFDocument.load(await pageEntry.file.arrayBuffer()));
                                const srcDoc = fileCache.get(pageEntry.file);
                                const [copiedPage] = await resultPdf.copyPages(srcDoc, [pageEntry.sourceIndex]);
                                if (pageEntry.rotation !== 0) copiedPage.setRotation(degrees(pageEntry.rotation));

                                // Apply Flipping
                                const {
                                    width,
                                    height
                                } = copiedPage.getSize();
                                if (pageEntry.flipH) {
                                    copiedPage.translate(width, 0);
                                    copiedPage.scale(-1, 1);
                                }
                                if (pageEntry.flipV) {
                                    copiedPage.translate(0, height);
                                    copiedPage.scale(1, -1);
                                }

                                resultPdf.addPage(copiedPage);
                            }
                        }

                        swalText.textContent = 'Finalizing PDF...';
                        const pdfBytes = await resultPdf.save();
                        const blob = new Blob([pdfBytes], {
                            type: "application/pdf"
                        });

                        Swal.close();
                        await handleOutput(blob);
                    } catch (err) {
                        if (err.message !== 'Save operation cancelled') {
                            Swal.fire("Error", "Error: " + err.message, "error");
                        }
                    }
                }
            }).then((result) => {
                if (result.dismiss === Swal.DismissReason.cancel) {
                    cancelRequested = true;
                }
            });
        }

        async function handleOutput(blob) {
            let fileName = document.getElementById('outName').value.trim() || 'modified';
            if (!fileName.toLowerCase().endsWith('.pdf')) fileName += '.pdf';
            const savePath = document.getElementById('savePath').value.trim();

            if (savePath) {
                const formData = new FormData();
                formData.append('action', 'save_to_path');
                formData.append('pdf', blob, fileName);
                formData.append('targetPath', savePath);
                const response = await fetch('process.php', {
                    method: 'POST',
                    body: formData
                });
                if (response.ok) {
                    Swal.fire("Success", "Saved to: " + savePath + fileName, "success");
                    openFolderBtn.style.display = 'block';
                    addToHistory(fileName, savePath);
                } else {
                    Swal.fire("Error", "Failed to save to local path.", "error");
                }
            } else {
                const link = document.createElement("a");
                link.href = URL.createObjectURL(blob);
                link.download = fileName;
                link.click();
                addToHistory(fileName, null);
            }
        }

        async function mergePDFs() {
            const filesToMerge = selectedFiles.filter(f => f.selected);
            if (filesToMerge.length < 2) {
                Swal.fire("Selection Required", "Please select at least two PDF files.", "warning");
                return;
            }

            cancelRequested = false;
            openFolderBtn.style.display = 'none';
            mergeBtn.disabled = true;

            Swal.fire({
                title: 'Merging PDFs',
                html: `
                    <div id="swal-progress-text" style="margin-bottom: 10px; font-size: 14px; color: #4a5568;">Initializing...</div>
                    <div class="progress-container" style="display: block; width: 100%; border: 1px solid #e2e8f0;">
                        <div id="swal-progress-bar" class="progress-bar swal-progress-bar" style="width: 0%;"></div>
                    </div>
                `,
                showCancelButton: true,
                cancelButtonText: 'Cancel Merge',
                cancelButtonColor: '#718096',
                allowOutsideClick: false,
                showConfirmButton: false,
                didOpen: async () => {
                    Swal.showLoading();
                    const swalBar = document.getElementById('swal-progress-bar');
                    const swalText = document.getElementById('swal-progress-text');

                    try {
                        if (typeof PDFLib === 'undefined') {
                            throw new Error("The PDF library could not be loaded.");
                        }

                        const {
                            PDFDocument,
                            degrees
                        } = PDFLib;
                        const mergedPdf = await PDFDocument.create();

                        for (let i = 0; i < filesToMerge.length; i++) {
                            if (cancelRequested) throw new Error('Merge operation cancelled');

                            const entry = filesToMerge[i];
                            const file = entry.file;
                            const rotation = entry.rotation || 0;
                            const progress = Math.round(((i + 1) / filesToMerge.length) * 100);

                            swalText.textContent = `Processing: ${file.name}`;
                            swalBar.style.width = `${progress}%`;

                            try {
                                const fileBuffer = await file.arrayBuffer();
                                const pdf = await PDFDocument.load(fileBuffer);
                                const indices = pdf.getPageIndices();
                                const copiedPages = await mergedPdf.copyPages(pdf, indices);
                                copiedPages.forEach(page => {
                                    if (rotation !== 0) {
                                        page.setRotation(degrees((page.getRotation().angle + rotation) % 360));
                                    }
                                    mergedPdf.addPage(page);
                                });
                            } catch (e) {
                                throw new Error(`Could not load "${file.name}".`);
                            }
                        }

                        swalText.textContent = 'Finalizing PDF...';
                        const mergedPdfBytes = await mergedPdf.save();
                        const blob = new Blob([mergedPdfBytes], {
                            type: "application/pdf"
                        });

                        let fileName = document.getElementById('outName').value.trim() || 'merged';
                        if (!fileName.toLowerCase().endsWith('.pdf')) fileName += '.pdf';
                        const savePath = document.getElementById('savePath').value.trim();

                        if (savePath) {
                            swalText.textContent = 'Saving to local path...';
                            const formData = new FormData();
                            formData.append('action', 'save_to_path');
                            formData.append('pdf', blob, fileName);
                            formData.append('targetPath', savePath);

                            const response = await fetch('process.php', {
                                method: 'POST',
                                body: formData
                            });
                            const result = await response.text();

                            if (response.ok) {
                                Swal.fire("Success", "Saved successfully to: " + savePath + fileName, "success");
                                openFolderBtn.style.display = 'block';
                                addToHistory(fileName, savePath);
                            } else {
                                throw new Error(result);
                            }
                        } else {
                            const link = document.createElement("a");
                            link.href = URL.createObjectURL(blob);
                            link.download = fileName;
                            link.click();
                            addToHistory(fileName, null);
                            Swal.close();
                        }

                        if (document.getElementById('clearAfter').checked) {
                            selectedFiles = [];
                            renderList();
                        }

                    } catch (err) {
                        if (err.message !== 'Merge operation cancelled') {
                            Swal.fire("Merge Failed", err.message, "error");
                        }
                    } finally {
                        mergeBtn.disabled = false;
                    }
                }
            }).then((result) => {
                if (result.dismiss === Swal.DismissReason.cancel) {
                    cancelRequested = true;
                    mergeBtn.disabled = false;
                    Swal.fire({
                        title: 'Cancelled',
                        text: 'Merge process was stopped by user.',
                        icon: 'info',
                        timer: 2000,
                        showConfirmButton: false
                    });
                }
            });
        }
    </script>