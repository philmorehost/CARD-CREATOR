<?php
require_once __DIR__ . '/core/helpers.php';

$siteTitle = get_setting('site_title', 'CARD-CREATOR');
$firstLetter = strtoupper(substr(trim($siteTitle ?: 'C'), 0, 1));

// Output dynamic SVG favicon
header('Content-Type: image/svg+xml');
header('Cache-Control: max-age=86400');

echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 64 64">
    <rect width="64" height="64" rx="14" fill="#3b82f6"/>
    <text x="32" y="44" font-family="Inter, -apple-system, sans-serif" font-size="36" font-weight="bold" fill="#ffffff" text-anchor="middle"><?= htmlspecialchars($firstLetter) ?></text>
</svg>
