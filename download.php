<?php

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

require_auth();

$file_key = $_GET['file'] ?? '';

$allowed = [
    'infographics'  => ['path' => 'content/infographics/infographics-pack.zip', 'type' => 'application/zip', 'name' => 'Crafting-Coral-Infographics.zip'],
    'video'         => ['path' => 'content/video-tutorial.mp4', 'type' => 'video/mp4', 'name' => 'Crafting-Coral-Video-Tutorial.mp4'],
    'presentation'  => ['path' => 'content/presentation.pptx', 'type' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation', 'name' => 'Crafting-Coral-Presentation.pptx'],
    'module'        => ['path' => 'content/module.pdf', 'type' => 'application/pdf', 'name' => 'Crafting-Coral-Teaching-Module.pdf'],
    '360-video'     => ['path' => 'content/360-video/video.mp4', 'type' => 'video/mp4', 'name' => 'Crafting-Coral-360-Video.mp4'],
];

if (!isset($allowed[$file_key])) {
    http_response_code(404);
    require_once __DIR__ . '/includes/config.php';
    $page_title = 'File Not Found — ' . SITE_NAME;
    require_once __DIR__ . '/includes/header.php';
    echo '<main class="auth-page"><div class="container"><div class="auth-card"><h1>File Not Found</h1><p>The requested resource could not be found.</p><a href="/" class="btn btn-primary btn-full">Back to Dashboard</a></div></div></main>';
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

$file = $allowed[$file_key];
$filepath = __DIR__ . '/' . $file['path'];

if (!file_exists($filepath)) {
    http_response_code(404);
    $page_title = 'File Not Available — ' . SITE_NAME;
    require_once __DIR__ . '/includes/header.php';
    echo '<main class="auth-page"><div class="container"><div class="auth-card"><h1>Coming Soon</h1><p>This resource is being prepared and will be available shortly.</p><a href="/" class="btn btn-primary btn-full">Back to Dashboard</a></div></div></main>';
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

// Serve the file
header('Content-Type: ' . $file['type']);
header('Content-Disposition: attachment; filename="' . $file['name'] . '"');
header('Content-Length: ' . filesize($filepath));
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');

readfile($filepath);
exit;
