<?php
set_time_limit(300);

$uploadDir = __DIR__ . '/uploads/';
if (!is_dir($uploadDir)) mkdir($uploadDir);

$action = $_POST['action'] ?? null;

// Security: Define a restricted root for saving files to prevent access to system folders
/** 
 * CORPORATE SAFETY NOTICE:
 * To save anywhere on your computer (e.g., your Desktop), change the line below to:
 * $allowedRoot = 'C:\\'; 
 * 
 * For maximum security, keep it restricted to the 'output' folder.
 */
$allowedRoot = 'U:\\01_TESP\\00_COMMON\\020_ACG\\vouchers\\MERGE';
if (!is_dir($allowedRoot)) mkdir($allowedRoot);

/**
 * Validates that the path is within the allowed folder
 */
function validatePath($path, $root)
{
    $realRoot = realpath($root);
    if (!$realRoot) return false;

    // Ensure root ends with a separator for precise folder matching
    $realRoot = rtrim($realRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

    // Normalize separators and remove trailing slashes for consistent comparison
    $path = rtrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path), DIRECTORY_SEPARATOR);

    // Get realpath of the path (or its closest existing parent if the folder doesn't exist yet)
    $checkPath = $path;
    while ($checkPath && !file_exists($checkPath)) {
        $checkPath = dirname($checkPath);
    }
    $absolutePath = realpath($checkPath);

    if (!$absolutePath) return false;

    // Add separator to check path to prevent partial matches (e.g., 'output' vs 'output_secret')
    $absolutePath = rtrim($absolutePath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

    // On Windows, paths are case-insensitive. We use strtolower for comparison.
    return (strpos(strtolower($absolutePath), strtolower($realRoot)) === 0);
}

if ($action === 'test_path') {
    $targetPath = $_POST['targetPath'] ?? '';
    if (empty($targetPath)) exit('Path is empty');

    if (!validatePath($targetPath, $allowedRoot)) {
        $resolvedRoot = realpath($allowedRoot);
        exit("Error: Security Violation.\n" .
            "Requested: $targetPath\n" .
            "Allowed Root: $resolvedRoot\n" .
            "The requested path is outside the permitted directory.");
    }

    if (is_dir($targetPath)) {
        if (is_writable($targetPath)) {
            exit('Success: Folder is found and writable.');
        }
        exit('Error: Folder exists but is not writable.');
    } else {
        if (mkdir($targetPath, 0777, true)) {
            exit('Success: Folder was created.');
        }
        exit('Error: Path does not exist and could not be created.');
    }
}

if ($action === 'open_folder') {
    $targetPath = $_POST['targetPath'] ?? '';
    if (is_dir($targetPath) && validatePath($targetPath, $allowedRoot)) {
        shell_exec('explorer ' . escapeshellarg(realpath($targetPath)));
    }
    exit;
}

if ($action === 'save_to_path') {
    $targetPath = $_POST['targetPath'] ?? '';
    $pdf = $_FILES['pdf'] ?? null;

    if (!$pdf || !$targetPath) {
        http_response_code(400);
        exit('Missing file or path');
    }

    if (!validatePath($targetPath, $allowedRoot)) {
        http_response_code(403);
        exit('Security Violation: Invalid save path.');
    }

    // Ensure path ends with a slash
    $targetPath = rtrim($targetPath, '/\\') . DIRECTORY_SEPARATOR;

    if (!is_dir($targetPath)) {
        mkdir($targetPath, 0777, true);
    }

    $fullPath = $targetPath . $pdf['name'];

    if (move_uploaded_file($pdf['tmp_name'], $fullPath)) {
        exit('Success');
    } else {
        http_response_code(500);
        exit('Failed to write to destination. Check permissions.');
    }
}

$files  = $_FILES['files'] ?? null;

$paths = [];

foreach ($files['tmp_name'] as $i => $tmp) {
    $dest = $uploadDir . uniqid() . '.pdf';
    move_uploaded_file($tmp, $dest);
    $paths[] = $dest;
}

$output = $uploadDir . uniqid() . '_out.pdf';

switch ($action) {

    case 'merge':
        $cmd = 'gswin64c -dBATCH -dNOPAUSE -q -sDEVICE=pdfwrite '
            . '-sOutputFile="' . $output . '" '
            . implode(' ', array_map('escapeshellarg', $paths));
        break;

    case 'compress':
        $cmd = 'gswin64c -sDEVICE=pdfwrite '
            . '-dCompatibilityLevel=1.4 '
            . '-dPDFSETTINGS=/ebook '
            . '-dNOPAUSE -dBATCH '
            . '-sOutputFile="' . $output . '" '
            . escapeshellarg($paths[0]);
        break;

    default:
        exit('Invalid action');
}

exec($cmd, $result, $status);

if ($status !== 0) {
    exit('PDF processing failed');
}

header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename=result.pdf');
readfile($output);
