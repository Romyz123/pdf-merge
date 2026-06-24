<?php

/**
 * process.php
 *
 * PURPOSE:
 * - Save final PDF blobs generated in the browser
 * - Validate save paths against an allowed root
 * - Test if a path exists / is writable
 * - Open destination folder (Windows only)
 */

set_time_limit(300);

/* ==========================================================
   CONFIGURATION
   ========================================================== */

/**
 * Allowed root directory for saving files
 * (SECURITY NOTE: To allow saving anywhere on your local machine, set this to an empty string ''.
 * This is recommended for personal use on XAMPP to avoid "Security violation" errors.)
 */
$ALLOWED_ROOT = '';

/* ==========================================================
   HELPER FUNCTIONS
   ========================================================== */

function validatePath(string $path, string $root): bool
{
    // If no root is restricted, allow any path that looks absolute
    if ($root === '') {
        return $path !== '';
    }

    // Ensure root exists so realpath can resolve it
    if (!file_exists($root)) {
        @mkdir($root, 0777, true);
    }

    $rootReal = realpath($root);
    if ($rootReal === false) {
        return false;
    }

    $rootReal = rtrim($rootReal, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    $path = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
    $path = rtrim($path, DIRECTORY_SEPARATOR);

    $check = $path;
    while ($check && !file_exists($check)) {
        $check = dirname($check);
    }

    $pathReal = realpath($check);
    if ($pathReal === false) {
        return false;
    }

    $pathReal = rtrim($pathReal, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    return stripos($pathReal, $rootReal) === 0;
}

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

    // Reset to root only if root is restricted and path is invalid
    if (($ALLOWED_ROOT !== '' && !validatePath($startPath, $ALLOWED_ROOT)) || ($startPath !== '' && !is_dir($startPath))) {
        $startPath = $ALLOWED_ROOT;
    }

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
        if (validatePath($selectedPath, $ALLOWED_ROOT)) {
            echo $selectedPath;
        } else {
            $rootReal = realpath($ALLOWED_ROOT) ?: $ALLOWED_ROOT;
            $pathReal = realpath($selectedPath) ?: $selectedPath;
            respond("Security violation: Selected path is outside the allowed directory.\nAllowed Root: $rootReal\nSelected: $pathReal", 403);
        }
    }
    exit;
}

/* ==========================================================
   TEST PATH
   ========================================================== */
if ($action === 'test_path') {
    $targetPath = trim($_POST['targetPath'] ?? '');

    if ($targetPath === '') respond('Path is empty.', 400);
    if (!validatePath($targetPath, $ALLOWED_ROOT)) respond("Security violation.\nRequested path is outside the allowed directory.", 403);

    if (!file_exists($targetPath)) {
        if (!@mkdir($targetPath, 0777, true)) {
            $err = error_get_last();
            respond('Failed to create directory. ' . ($err['message'] ?? 'Check folder permissions or run XAMPP as Administrator.'), 500);
        }
        respond('Success: Folder created and writable.');
    }

    if (!is_dir($targetPath)) respond('Path exists but is not a directory.', 400);
    if (!is_writable($targetPath)) respond('Folder exists but is NOT writable. Try choosing a location outside protected System folders or run XAMPP as Administrator.', 403);

    respond('Success: Folder exists and is writable.');
}

/* ==========================================================
   SAVE PDF TO PATH
   ========================================================== */
if ($action === 'save_to_path') {
    $targetPath = trim($_POST['targetPath'] ?? '');
    $file = $_FILES['pdf'] ?? null;

    // --- NEW: Better Error Reporting for PHP Limits ---
    if (!$file && isset($_SERVER['CONTENT_LENGTH']) && $_SERVER['CONTENT_LENGTH'] > 0) {
        respond('Upload failed. The merged PDF exceeds your XAMPP php.ini post_max_size limit.', 400);
    }

    if ($file && $file['error'] !== UPLOAD_ERR_OK) {
        if ($file['error'] === UPLOAD_ERR_INI_SIZE) {
            respond('Upload failed. The merged PDF exceeds your XAMPP php.ini upload_max_filesize limit.', 400);
        }
        respond('File upload error code: ' . $file['error'], 400);
    }

    if ($targetPath === '' || !$file) respond('Missing file or path.', 400);
    if (!validatePath($targetPath, $ALLOWED_ROOT)) respond('Security violation: invalid save path.', 403);
    if (!is_uploaded_file($file['tmp_name'])) respond('Invalid file upload.', 400);

    $targetPath = rtrim($targetPath, '/\\') . DIRECTORY_SEPARATOR;

    if (!is_dir($targetPath)) {
        if (!@mkdir($targetPath, 0777, true)) {
            $err = error_get_last();
            respond('Failed to create destination folder. ' . ($err['message'] ?? 'Check permissions.'), 500);
        }
    }

    $safeName = basename($file['name']);
    $destination = $targetPath . $safeName;

    if (file_exists($destination) && !is_writable($destination)) {
        respond("File Lock Error: '{$safeName}' is currently open in another program (Browser, Acrobat, or Preview Pane). Please close it and try again.", 403);
    }

    if (@copy($file['tmp_name'], $destination)) {
        @unlink($file['tmp_name']);
        respond('Success');
    } else {
        $err = error_get_last();
        $msg = $err['message'] ?? 'Check folder permissions.';
        if (stripos($msg, 'permission denied') !== false) {
            $msg .= " (Suggestion: Run XAMPP/Apache as Administrator to allow writing to the C: drive root).";
        }
        respond('Failed to save PDF: ' . $msg, 500);
    }
}

/* ==========================================================
   OPEN FOLDER (WINDOWS ONLY)
   ========================================================== */
if ($action === 'open_folder') {
    $targetPath = trim($_POST['targetPath'] ?? '');

    if ($targetPath !== '' && validatePath($targetPath, $ALLOWED_ROOT) && is_dir($targetPath)) {
        $real = realpath($targetPath);
        if ($real) {
            shell_exec('explorer ' . escapeshellarg($real));
        }
    }
    exit;
}

respond('Invalid action.', 400);
