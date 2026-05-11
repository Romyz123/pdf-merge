<?php

/**
 * process.php
 *
 * PURPOSE:
 * - Save final PDF blobs generated in the browser
 * - Validate save paths against an allowed root
 * - Test if a path exists / is writable
 * - Open destination folder (Windows only)
 *
 * IMPORTANT:
 * - NO PDF merging / editing here
 * - NO Ghostscript
 * - NO exec() PDF tools
 */

set_time_limit(300);

/* ==========================================================
   CONFIGURATION
   ========================================================== */

/**
 * Allowed root directory for saving files
 * (SECURITY CRITICAL)
 */
$ALLOWED_ROOT = 'U:\\01_TESP\\00_COMMON\\020_ACG\\vouchers'; 

/* ==========================================================
   HELPER FUNCTIONS
   ========================================================== */

/**
 * Validate that a path is inside the allowed root directory
 */
function validatePath(string $path, string $root): bool
{
    $rootReal = realpath($root);
    if ($rootReal === false) {
        return false;
    }

    // Normalize root
    $rootReal = rtrim($rootReal, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

    // Normalize target path
    $path = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
    $path = rtrim($path, DIRECTORY_SEPARATOR);

    // Resolve closest existing parent
    $check = $path;
    while ($check && !file_exists($check)) {
        $check = dirname($check);
    }

    $pathReal = realpath($check);
    if ($pathReal === false) {
        return false;
    }

    $pathReal = rtrim($pathReal, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

    // Windows is case‑insensitive
    return stripos($pathReal, $rootReal) === 0;
}

/**
 * Unified response helper
 */
function respond(string $message, int $status = 200): void
{
    http_response_code($status);
    echo $message;
    exit;
}

/* ==========================================================
   ROUTER
   ========================================================== */

$action = $_POST['action'] ?? '';

/* ==========================================================
   BROWSE FOLDER (WINDOWS ONLY)
   ========================================================== */
if ($action === 'browse_folder') {
    $startPath = trim($_POST['targetPath'] ?? $ALLOWED_ROOT);

    // Default to allowed root if provided path is invalid
    if (!is_dir($startPath) || !validatePath($startPath, $ALLOWED_ROOT)) {
        $startPath = $ALLOWED_ROOT;
    }

    // Use PowerShell to open a native Windows folder picker
    // Escape single quotes for PowerShell string literal
    $escapedPath = str_replace("'", "''", $startPath);
    $psCommand = "Add-Type -AssemblyName System.Windows.Forms; " .
        "\$f = New-Object System.Windows.Forms.FolderBrowserDialog; " .
        "\$f.SelectedPath = '$escapedPath'; " .
        "\$f.Description = 'Select PDF Output Folder'; " .
        "if(\$f.ShowDialog() -eq 'OK') { Write-Host \$f.SelectedPath }";

    $fullCmd = "powershell -NoProfile -ExecutionPolicy Bypass -Command \"$psCommand\"";
    $result = shell_exec($fullCmd);
    if ($result) {
        $selectedPath = trim($result);
        // Security check: Ensure the user didn't browse outside the allowed root
        if (validatePath($selectedPath, $ALLOWED_ROOT)) {
            echo $selectedPath;
        } else {
            // Improved debugging info
            $rootReal = realpath($ALLOWED_ROOT) ?: $ALLOWED_ROOT;
            $pathReal = realpath($selectedPath) ?: $selectedPath;
            respond("Security violation: Selected path is outside the allowed directory.\n\n" .
                "Allowed Root: $rootReal\nSelected: $pathReal", 403);
        }
    } else {
        // No output usually means the user cancelled
    }
    exit;
}

/* ==========================================================
   TEST PATH
   ========================================================== */
if ($action === 'test_path') {

    $targetPath = trim($_POST['targetPath'] ?? '');

    if ($targetPath === '') {
        respond('Path is empty.', 400);
    }

    if (!validatePath($targetPath, $ALLOWED_ROOT)) {
        respond(
            "Security violation.\nRequested path is outside the allowed directory.",
            403
        );
    }

    if (!file_exists($targetPath)) {
        if (!@mkdir($targetPath, 0777, true)) {
            respond('Failed to create directory.', 500);
        }
        respond('Success: Folder created and writable.');
    }

    if (!is_dir($targetPath)) {
        respond('Path exists but is not a directory.', 400);
    }

    if (!is_writable($targetPath)) {
        respond('Folder exists but is NOT writable.', 403);
    }

    respond('Success: Folder exists and is writable.');
}

/* ==========================================================
   SAVE PDF TO PATH
   ========================================================== */
if ($action === 'save_to_path') {

    $targetPath = trim($_POST['targetPath'] ?? '');
    $file = $_FILES['pdf'] ?? null;

    if ($targetPath === '' || !$file) {
        respond('Missing file or path.', 400);
    }

    if (!validatePath($targetPath, $ALLOWED_ROOT)) {
        respond('Security violation: invalid save path.', 403);
    }

    if (!is_uploaded_file($file['tmp_name'])) {
        respond('Invalid file upload.', 400);
    }

    // Normalize path
    $targetPath = rtrim($targetPath, '/\\') . DIRECTORY_SEPARATOR;

    if (!is_dir($targetPath)) {
        if (!@mkdir($targetPath, 0777, true)) {
            respond('Failed to create destination folder.', 500);
        }
    }

    $safeName = basename($file['name']);
    $destination = $targetPath . $safeName;

    // Check if the file exists and is locked (common on Windows with network shares or open PDF viewers)
    if (file_exists($destination) && !is_writable($destination)) {
        respond("File Lock Error: '{$safeName}' is currently open in another program (Browser, Acrobat, or Preview Pane). Please close it and try again.", 403);
    }

    // Use copy() + unlink() instead of move_uploaded_file() for better compatibility with network drives (U:\)
    // move_uploaded_file often fails with 'Resource temporarily unavailable' on mapped shares.
    if (@copy($file['tmp_name'], $destination)) {
        @unlink($file['tmp_name']);
        respond('Success');
    } else {
        $err = error_get_last();
        respond('Failed to save PDF: ' . ($err['message'] ?? 'Check permissions.'), 500);
    }
}

/* ==========================================================
   OPEN FOLDER (WINDOWS ONLY)
   ========================================================== */
if ($action === 'open_folder') {

    $targetPath = trim($_POST['targetPath'] ?? '');

    if (
        $targetPath !== '' &&
        validatePath($targetPath, $ALLOWED_ROOT) &&
        is_dir($targetPath)
    ) {
        $real = realpath($targetPath);
        if ($real) {
            // Open Windows Explorer safely
            shell_exec('explorer ' . escapeshellarg($real));
        }
    }
    exit;
}

/* ==========================================================
   FALLBACK
   ========================================================== */
respond('Invalid action.', 400);
