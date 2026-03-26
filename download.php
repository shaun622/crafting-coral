<?php

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

require_auth();

$slot = $_GET['file'] ?? $_GET['slot'] ?? '';
$filename = $_GET['name'] ?? '';

$valid_slots = ['infographics', 'video', 'presentation', 'module', '360-video'];

if (!in_array($slot, $valid_slots)) {
    http_response_code(404);
    $page_title = 'File Not Found — ' . SITE_NAME;
    require_once __DIR__ . '/includes/header.php';
    echo '<main class="auth-page"><div class="container"><div class="auth-card"><h1>File Not Found</h1><p>The requested resource could not be found.</p><a href="/" class="btn btn-primary btn-full">Back to Dashboard</a></div></div></main>';
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

$dir = __DIR__ . '/content/' . $slot;

// If no specific file requested, check if there's only one file in the slot
if (empty($filename) && is_dir($dir)) {
    $files = array_diff(scandir($dir), ['.', '..', '.htaccess']);
    if (count($files) === 1) {
        $filename = reset($files);
    } elseif (count($files) === 0) {
        http_response_code(404);
        $page_title = 'File Not Available — ' . SITE_NAME;
        require_once __DIR__ . '/includes/header.php';
        echo '<main class="auth-page"><div class="container"><div class="auth-card"><h1>Coming Soon</h1><p>This resource is being prepared and will be available shortly.</p><a href="/" class="btn btn-primary btn-full">Back to Dashboard</a></div></div></main>';
        require_once __DIR__ . '/includes/footer.php';
        exit;
    } else {
        // Multiple files — redirect to dashboard (shouldn't happen with proper links)
        header('Location: /');
        exit;
    }
}

// Sanitise filename (prevent path traversal)
$safe_name = basename($filename);
$filepath = $dir . '/' . $safe_name;

if (!file_exists($filepath) || !is_file($filepath)) {
    http_response_code(404);
    $page_title = 'File Not Available — ' . SITE_NAME;
    require_once __DIR__ . '/includes/header.php';
    echo '<main class="auth-page"><div class="container"><div class="auth-card"><h1>Coming Soon</h1><p>This resource is being prepared and will be available shortly.</p><a href="/" class="btn btn-primary btn-full">Back to Dashboard</a></div></div></main>';
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

// Determine content type
$ext = strtolower(pathinfo($safe_name, PATHINFO_EXTENSION));
$types = [
    'pdf' => 'application/pdf',
    'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
    'mp4' => 'video/mp4',
    'zip' => 'application/zip',
    'png' => 'image/png',
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'gif' => 'image/gif',
    'webp' => 'image/webp',
    'svg' => 'image/svg+xml',
    'doc' => 'application/msword',
    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'xls' => 'application/vnd.ms-excel',
    'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
];
$content_type = $types[$ext] ?? 'application/octet-stream';

// Serve the file
header('Content-Type: ' . $content_type);
header('Content-Disposition: attachment; filename="' . $safe_name . '"');
header('Content-Length: ' . filesize($filepath));
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');

readfile($filepath);
exit;
