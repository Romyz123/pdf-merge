<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>PDF Merge Tool</title>

    <!-- Load from CDN for immediate use. For offline/secured use, download this file to /js/pdf-lib.min.js and update src. -->
    <script src="https://unpkg.com/pdf-lib/dist/pdf-lib.min.js"></script>

    <!-- Load PDF.js for visual page thumbnails -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
    <script>
        const pdfjsLib = window['pdfjs-dist/build/pdf'];
        pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
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
            transition: border-color 0.3s;
        }

        .input-group.drag-active {
            border-color: #3182ce;
            background-color: #ebf8ff;
        }

        .input-group:hover {
            border-color: #3182ce;
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

        .page-card:hover {
            border-color: #3182ce;
            transform: translateY(-2px);
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
            height: 120px;
            object-fit: contain;
            background: #eee;
            border-radius: 4px;
            margin-bottom: 8px;
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

        .tab-btn.active {
            color: #3182ce;
            border-bottom: 2px solid #3182ce;
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
            <div id="fileList"></div>
            <button id="clearAllBtn" class="btn-remove" style="width: 100%; margin-bottom: 20px; display: none; padding: 10px;" onclick="clearAllFiles()">Clear All Files</button>
        </div>

        <!-- Edit/Manager Tab -->
        <div id="editTab" class="tab-content" style="display: none;">
            <div class="settings-group">
                <label>Select PDF to Organize</label>
                <input type="file" id="mainPdf" accept="application/pdf" onchange="loadOrganizer()">
            </div>

            <div id="organizerControls" style="display: none; margin-bottom: 15px; display: flex; gap: 10px;">
                <button class="btn-preview" onclick="addBlankPage()">+ Insert Blank Page</button>
                <button class="btn-preview" onclick="triggerAddFile()">+ Add from File</button>
                <button id="selectAllBtn" class="btn-preview" style="display: none;" onclick="toggleSelectAll()">Select All</button>
                <button id="previewOrganizedBtn" class="btn-preview" style="display: none;" onclick="previewFullOrganizedPdf()" title="Preview the entire organized document">Preview Organized PDF</button>
                <button id="rotateAllBtn" class="btn-preview" style="display: none;" onclick="rotateAllPages()">Rotate All</button>
                <button id="downloadIndividualBtn" class="btn-preview" style="display: none;" onclick="downloadIndividualSelected()" title="Download selected as separate files">Download Individual</button>
                <button id="deleteSelectedBtn" class="btn-remove" style="display: none;" onclick="deleteSelectedPages()">Delete Selected</button>
                <input type="file" id="addFileHidden" accept="application/pdf" style="display: none;" onchange="handleAddFileChange()">
            </div>

            <div id="pageGrid" class="page-grid">
                <p style="grid-column: 1/-1; text-align: center; color: #718096; padding: 20px;">Upload a PDF to start organizing pages...</p>
            </div>

            <button id="processEditBtn" class="btn-merge" style="margin-top: 20px;" onclick="processPageEdit()" disabled>Save Organized PDF</button>
        </div>

        <!-- Common Settings -->
        <div class="settings-group" style="margin-top: 20px;">
            <label for="outName">Output Filename</label>
            <input type="text" id="outName" placeholder="merged-document" value="merged">
            <div style="margin-top: 10px; font-size: 14px; color: #4a5568;">
                <input type="checkbox" id="clearAfter" checked>
                <label for="clearAfter" style="display: inline; font-weight: normal;">Clear list after success</label>
            </div>
        </div>

        <div class="settings-group">
            <label for="savePath">Automatic Save Path (Optional - Local Only)</label>
            <div style="display: flex; gap: 5px;">
                <input type="text" id="savePath" placeholder="e.g., C:/Users/Documents/Merged/" style="flex-grow: 1;" value="U:\01_TESP\00_COMMON\020_ACG\vouchers\MERGE">
                <button type="button" class="btn-preview" onclick="testPath()" title="Test Connection">Test</button>
            </div>
        </div>

        <div id="progressContainer" class="progress-container">
            <div id="progressBar" class="progress-bar"></div>
            <div id="progressText" class="progress-text">0%</div>
        </div>

        <button id="mergeBtn" class="btn-merge" onclick="mergePDFs()" disabled>Merge All Selection</button>
        <button id="openFolderBtn" class="btn-merge" style="display: none; background: #4a5568;" onclick="openFolder()">Open Destination Folder</button>

        <div id="historySection" style="margin-top: 30px; display: none; border-top: 1px solid #e2e8f0; padding-top: 20px;">
            <label style="display: block; font-size: 14px; font-weight: 600; margin-bottom: 10px; color: #4a5568;">Recent Merges</label>
            <div id="historyList"></div>
            <button class="btn-remove" style="width: 100%; margin-top: 10px; font-size: 12px; padding: 5px;" onclick="clearHistory()">Clear History</button>
        </div>
    </div>

    <div id="previewModal" class="modal">
        <div class="modal-content">
            <span class="close-modal" onclick="closePreview()">&times;</span>
            <iframe id="previewFrame"></iframe>
        </div>
    </div>

    <script>
        let selectedFiles = [];
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
        const rotateAllBtn = document.getElementById('rotateAllBtn');
        const previewOrganizedBtn = document.getElementById('previewOrganizedBtn');
        const downloadIndividualBtn = document.getElementById('downloadIndividualBtn');
        const deleteSelectedBtn = document.getElementById('deleteSelectedBtn');
        const processEditBtn = document.getElementById('processEditBtn');

        let organizerPages = []; // Tracks { file, sourceIndex, rotation, isBlank, thumbnail }
        let dragSrcEl = null;

        function switchTab(tabId) {
            document.querySelectorAll('.tab-content').forEach(tab => tab.style.display = 'none');
            document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));

            document.getElementById(tabId + 'Tab').style.display = 'block';
            event.currentTarget.classList.add('active');

            // Hide open folder button when switching
            openFolderBtn.style.display = 'none';
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

        function handleFiles(files) {
            const newFiles = Array.from(files).filter(f => f.type === 'application/pdf');
            selectedFiles = [...selectedFiles, ...newFiles];
            renderList();
            fileInput.value = '';
        }

        function renderList() {
            fileListContainer.innerHTML = '';
            selectedFiles.forEach((file, index) => {
                const item = document.createElement('div');
                item.className = 'file-item';
                item.innerHTML = `
                    <div class="file-name">${file.name}</div>
                    <div class="controls">
                        <button class="btn-preview" onclick="previewFile(${index})" title="Preview">👁</button>
                        <button class="btn-move" onclick="move(${index}, -1)" ${index === 0 ? 'disabled' : ''}>↑</button>
                        <button class="btn-move" onclick="move(${index}, 1)" ${index === selectedFiles.length - 1 ? 'disabled' : ''}>↓</button>
                        <button class="btn-remove" onclick="removeFile(${index})">✕</button>
                    </div>
                `;
                fileListContainer.appendChild(item);
            });
            mergeBtn.disabled = selectedFiles.length < 2;
            clearAllBtn.style.display = selectedFiles.length > 0 ? 'block' : 'none';
        }

        function move(index, direction) {
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
            if (selectedFiles.length > 0 && confirm("Are you sure you want to remove all selected files?")) {
                selectedFiles = [];
                renderList();
            }
        }

        function previewFile(index) {
            const file = selectedFiles[index];
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
            if (confirm("Clear merge history?")) {
                localStorage.removeItem('pdfMergeHistory');
                renderHistory();
            }
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
                alert("Could not open folder.");
            }
        }

        async function testPath() {
            const savePath = document.getElementById('savePath').value.trim();
            if (!savePath) {
                alert("Please enter a path first.");
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
                alert(result);
            } catch (e) {
                alert("Error connecting to local server: " + e.message);
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
                alert("Could not open folder.");
            }
        }

        function togglePageSelection(index) {
            organizerPages[index].selected = !organizerPages[index].selected;
            renderOrganizer();
        }

        function toggleSelectAll() {
            if (organizerPages.length === 0) return;
            const allSelected = organizerPages.every(p => p.selected);
            organizerPages.forEach(p => p.selected = !allSelected);
            renderOrganizer();
        }

        function rotateAllPages() {
            if (organizerPages.length === 0) return;
            organizerPages.forEach(p => {
                p.rotation = (p.rotation + 90) % 360;
            });
            renderOrganizer();
        }

        async function downloadIndividualSelected() {
            const selected = organizerPages.filter(p => p.selected);
            if (selected.length === 0) {
                alert("Please select pages to download.");
                return;
            }

            progressContainer.style.display = 'block';
            progressText.textContent = 'Preparing individual downloads...';

            try {
                for (let i = 0; i < selected.length; i++) {
                    const pageEntry = selected[i];
                    const blob = await generateSinglePageBlob(pageEntry);
                    const link = document.createElement("a");
                    link.href = URL.createObjectURL(blob);
                    link.download = `page_${i + 1}_${pageEntry.label}.pdf`;
                    link.click();
                }
            } catch (e) {
                alert("Error during individual download: " + e.message);
            } finally {
                progressContainer.style.display = 'none';
            }
        }

        function deleteSelectedPages() {
            const selectedCount = organizerPages.filter(p => p.selected).length;
            if (selectedCount === 0) return;

            if (confirm(`Are you sure you want to delete ${selectedCount} selected pages?`)) {
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

                const pdfjsDoc = await pdfjsLib.getDocument(arrayBuffer).promise;

                // Create placeholders immediately so UI is interactive
                organizerPages = Array.from({
                    length: pageCount
                }, (_, i) => ({
                    file,
                    sourceIndex: i,
                    rotation: 0,
                    isBlank: false, // Initial pages are not "new" in this context
                    selected: false,
                    label: i + 1,
                    thumbnail: null // Loaded later
                }));

                document.getElementById('organizerControls').style.display = 'flex';
                processEditBtn.disabled = false;
                renderOrganizer();

                // Background rendering of thumbnails
                progressText.textContent = 'Generating previews in background...';
                loadThumbnails(pdfjsDoc, 0, organizerPages.length);

            } catch (e) {
                alert("Error loading PDF: " + e.message);
            } finally {
                progressContainer.style.display = 'none';
                progressBar.style.width = '0%';
            }
        }

        async function loadThumbnails(pdfjsDoc, startIndex, count) {
            for (let i = 0; i < count; i++) {
                const idx = startIndex + i;
                // Only render if not already rendered (relevant for multiple file adds)
                if (organizerPages[idx] && !organizerPages[idx].thumbnail && !organizerPages[idx].isBlank) {
                    generateThumbnail(pdfjsDoc, organizerPages[idx].sourceIndex + 1).then(url => {
                        organizerPages[idx].thumbnail = url;
                        // Target the specific image to avoid full grid re-render
                        const img = document.getElementById(`thumb-${idx}`);
                        if (img) {
                            img.src = url;
                            img.style.display = 'block';
                            const placeholder = img.previousElementSibling;
                            if (placeholder && placeholder.classList.contains('thumb-placeholder')) placeholder.remove();
                        }
                    });
                }
            }
        }

        function renderOrganizer() {
            pageGrid.innerHTML = '';
            organizerPages.forEach((page, index) => {
                const card = document.createElement('div');
                card.className = `page-card ${page.selected ? 'selected' : ''}`;
                card.draggable = true;
                card.dataset.index = index;

                card.innerHTML = `
                    <input type="checkbox" class="page-checkbox" ${page.selected ? 'checked' : ''} onclick="event.stopPropagation(); togglePageSelection(${index})">
                    ${page.isNew ? '<span class="new-badge">NEW</span>' : ''}
                    ${page.isBlank 
                        ? '<div class="page-thumbnail" style="background:#fff; border:1px dashed #ccc; display:flex; align-items:center; justify-content:center; color:#ccc;">Empty</div>' 
                        : `<div class="thumb-placeholder" style="height:120px; background:#edf2f7; display:flex; align-items:center; justify-content:center; font-size:10px; color:#a0aec0; border-radius:4px;">Loading...</div>
                           <img id="thumb-${index}" src="${page.thumbnail || ''}" class="page-thumbnail" style="transform: rotate(${page.rotation}deg); display: ${page.thumbnail ? 'block' : 'none'}">`}
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

            const selectedCount = organizerPages.filter(p => p.selected).length;
            deleteSelectedBtn.style.display = selectedCount > 0 ? 'inline-block' : 'none';
            deleteSelectedBtn.textContent = `Delete Selected (${selectedCount})`;

            downloadIndividualBtn.style.display = selectedCount > 0 ? 'inline-block' : 'none';
            previewOrganizedBtn.style.display = organizerPages.length > 0 ? 'inline-block' : 'none';
            rotateAllBtn.style.display = organizerPages.length > 0 ? 'inline-block' : 'none';

            selectAllBtn.style.display = organizerPages.length > 0 ? 'inline-block' : 'none';
            selectAllBtn.textContent = (organizerPages.length > 0 && selectedCount === organizerPages.length) ? 'Deselect All' : 'Select All';
        }

        function handleDragStart(e) {
            this.classList.add('dragging');
            dragSrcEl = this;
            e.dataTransfer.effectAllowed = 'move';
        }

        function handleDragOver(e) {
            e.preventDefault();
            return false;
        }

        function handleDragEnter(e) {
            this.classList.add('drag-over');
        }

        function handleDragLeave(e) {
            this.classList.remove('drag-over');
        }

        function handleDrop(e) {
            e.stopPropagation();
            e.preventDefault();
            this.classList.remove('drag-over');

            // Handle File Drop directly onto a card
            if (e.dataTransfer.files && e.dataTransfer.files.length > 0) {
                const insertAt = parseInt(this.dataset.index) + 1;
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
                const fromIndex = parseInt(dragSrcEl.dataset.index);
                const toIndex = parseInt(this.dataset.index);

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
            organizerPages[index].rotation = (organizerPages[index].rotation + 90) % 360;
            renderOrganizer();
        }

        function deleteOrganizerPage(index) {
            organizerPages.splice(index, 1);
            renderOrganizer();
        }

        function addBlankPage() {
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
                progressContainer.style.display = 'block';
                progressText.textContent = 'Adding file...';

                const arrayBuffer = await file.arrayBuffer();
                const pdfDoc = await PDFLib.PDFDocument.load(arrayBuffer);
                const pageCount = pdfDoc.getPageCount();
                const pdfjsDoc = await pdfjsLib.getDocument(arrayBuffer).promise;

                const newPages = Array.from({
                    length: pageCount
                }, (_, i) => ({
                    file,
                    sourceIndex: i,
                    rotation: 0,
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
                alert("Error adding file: " + e.message);
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
                alert("No pages to preview.");
                return;
            }

            progressContainer.style.display = 'block';
            progressBar.style.width = '50%';
            progressText.textContent = 'Generating full preview...';

            try {
                const {
                    PDFDocument,
                    degrees
                } = PDFLib;
                const resultPdf = await PDFDocument.create();
                const fileCache = new Map(); // Reuse file cache from processPageEdit

                for (const pageEntry of organizerPages) {
                    if (pageEntry.isBlank) {
                        resultPdf.addPage();
                    } else {
                        if (!fileCache.has(pageEntry.file)) fileCache.set(pageEntry.file, await PDFDocument.load(await pageEntry.file.arrayBuffer()));
                        const srcDoc = fileCache.get(pageEntry.file);
                        const [copiedPage] = await resultPdf.copyPages(srcDoc, [pageEntry.sourceIndex]);
                        if (pageEntry.rotation !== 0) copiedPage.setRotation(degrees(pageEntry.rotation));
                        resultPdf.addPage(copiedPage);
                    }
                }
                const blob = new Blob([await resultPdf.save()], {
                    type: "application/pdf"
                });
                document.getElementById('previewFrame').src = URL.createObjectURL(blob);
                document.getElementById('previewModal').style.display = 'block';
            } catch (e) {
                alert("Failed to generate full preview: " + e.message);
            } finally {
                progressContainer.style.display = 'none';
            }
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
                alert("Failed to generate preview: " + e.message);
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
                tempPdf.addPage(copiedPage);
            }
            const bytes = await tempPdf.save();
            return new Blob([bytes], {
                type: "application/pdf"
            });
        }

        async function generateThumbnail(pdfjsDoc, pageNum) {
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
            return canvas.toDataURL('image/jpeg', 0.7); // Compress as JPEG with 70% quality
        }


        async function processPageEdit() {
            if (organizerPages.length === 0) return;

            progressContainer.style.display = 'block';
            progressBar.style.width = '50%';
            progressText.textContent = 'Building organized PDF...';

            try {
                const {
                    PDFDocument,
                    degrees
                } = PDFLib;
                const resultPdf = await PDFDocument.create();

                // Cache loaded documents to avoid re-loading same file multiple times
                const fileCache = new Map();

                for (const pageEntry of organizerPages) {
                    if (pageEntry.isBlank) {
                        resultPdf.addPage(); // Standard A4 blank
                    } else {
                        if (!fileCache.has(pageEntry.file)) {
                            fileCache.set(pageEntry.file, await PDFDocument.load(await pageEntry.file.arrayBuffer()));
                        }
                        const srcDoc = fileCache.get(pageEntry.file);
                        const [copiedPage] = await resultPdf.copyPages(srcDoc, [pageEntry.sourceIndex]);

                        if (pageEntry.rotation !== 0) {
                            copiedPage.setRotation(degrees(pageEntry.rotation));
                        }
                        resultPdf.addPage(copiedPage);
                    }
                }

                const pdfBytes = await resultPdf.save();
                const blob = new Blob([pdfBytes], {
                    type: "application/pdf"
                });
                await handleOutput(blob);

            } catch (err) {
                alert("Error: " + err.message);
            } finally {
                progressContainer.style.display = 'none';
            }
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
                    alert("Saved to: " + savePath + fileName);
                    openFolderBtn.style.display = 'block';
                    addToHistory(fileName, savePath);
                } else {
                    alert("Failed to save to local path.");
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
            if (selectedFiles.length < 2) {
                alert("Please select at least two PDF files.");
                return;
            }

            openFolderBtn.style.display = 'none';
            mergeBtn.disabled = true;
            mergeBtn.textContent = 'Working...';
            progressContainer.style.display = 'block';
            progressBar.style.width = '0%';
            progressText.textContent = '0%';

            try {
                if (typeof PDFLib === 'undefined') {
                    throw new Error("The PDF library could not be loaded. Please check your internet connection or ensure the local library exists in the /js/ folder.");
                }

                const {
                    PDFDocument
                } = PDFLib;
                const mergedPdf = await PDFDocument.create();

                for (let i = 0; i < selectedFiles.length; i++) {
                    const file = selectedFiles[i];
                    let pdf;
                    try {
                        const fileBuffer = await file.arrayBuffer();
                        // Attempt to load. Note: pdf-lib cannot handle encrypted PDFs.
                        pdf = await PDFDocument.load(fileBuffer);
                    } catch (e) {
                        throw new Error(`Could not load "${file.name}". It might be encrypted or corrupted.`);
                    }

                    const indices = pdf.getPageIndices();
                    const copiedPages = await mergedPdf.copyPages(pdf, indices);
                    copiedPages.forEach(page => mergedPdf.addPage(page));

                    const progress = Math.round(((i + 1) / selectedFiles.length) * 100);
                    progressBar.style.width = `${progress}%`;
                    progressText.textContent = `${progress}% - Processing ${file.name}`;
                }

                progressText.textContent = 'Finalizing PDF...';
                const mergedPdfBytes = await mergedPdf.save();
                const blob = new Blob([mergedPdfBytes], {
                    type: "application/pdf"
                });

                let fileName = document.getElementById('outName').value.trim() || 'merged';
                if (!fileName.toLowerCase().endsWith('.pdf')) fileName += '.pdf';

                const savePath = document.getElementById('savePath').value.trim();

                if (savePath) {
                    // Send to server to save at specific path
                    progressText.textContent = 'Saving to local path...';
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
                        alert("Saved successfully to: " + savePath + fileName);
                        openFolderBtn.style.display = 'block';
                        addToHistory(fileName, savePath);
                    } else {
                        throw new Error(result);
                    }
                } else {
                    // Standard browser download
                    const link = document.createElement("a");
                    link.href = URL.createObjectURL(blob);
                    link.download = fileName;
                    link.click();
                    addToHistory(fileName, null);
                }

                if (document.getElementById('clearAfter').checked) {
                    selectedFiles = [];
                    renderList();
                }

            } catch (err) {
                console.error(err);
                alert("Merge Failed: " + err.message);
            } finally {
                progressContainer.style.display = 'none';
                mergeBtn.disabled = false;
                mergeBtn.textContent = 'Merge & Download';
            }
        }
    </script>