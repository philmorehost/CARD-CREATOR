<?php
require_once __DIR__ . '/../core/helpers.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(0, 'Invalid request method');
}

if (is_demo_mode()) {
    json_response(0, 'File upload disabled in Demo mode');
}

if (empty($_FILES['file'])) {
    json_response(0, 'No file uploaded');
}

$file = $_FILES['file'];
$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$allowed = ['jpg', 'jpeg', 'png', 'webp', 'avif', 'svg'];

if (!in_array($ext, $allowed)) {
    json_response(0, 'Invalid file format. Allowed: JPG, PNG, WEBP, AVIF, SVG');
}

if ($file['size'] > 8 * 1024 * 1024) {
    json_response(0, 'File size exceeds maximum 8 MB limit');
}

$targetDir = __DIR__ . '/../uploads/photos/';
if (!is_dir($targetDir)) {
    mkdir($targetDir, 0777, true);
}

$outputExt = function_exists('imagewebp') ? 'webp' : 'jpg';
$filename = 'img_' . time() . '_' . bin2hex(random_bytes(6)) . '.' . $outputExt;
$targetPath = $targetDir . $filename;

if (compressAndResizeImage($file['tmp_name'], $targetPath, 800, 800, 85)) {
    json_response(1, 'Upload compressed successfully', [
        'url' => 'uploads/photos/' . $filename
    ]);
} else {
    json_response(0, 'Failed to process and compress uploaded file');
}
