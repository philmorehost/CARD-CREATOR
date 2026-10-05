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

$filename = 'img_' . time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
$targetDir = __DIR__ . '/../uploads/photos/';
if (!is_dir($targetDir)) {
    mkdir($targetDir, 0777, true);
}

$targetPath = $targetDir . $filename;
if (move_uploaded_file($file['tmp_name'], $targetPath)) {
    json_response(1, 'Upload successful', [
        'url' => 'uploads/photos/' . $filename
    ]);
} else {
    json_response(0, 'Failed to save uploaded file');
}
