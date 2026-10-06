<?php
require_once __DIR__ . '/../core/helpers.php';

if (!is_installed() || !isset($_SESSION['user_id'])) {
    json_response(0, 'Unauthorized access.');
}

$pdo = get_db_connection();
if (!$pdo) {
    json_response(0, 'Database connection error.');
}

$userId = $_SESSION['user_id'];
$cardholderName = sanitize_input($_POST['cardholder_name'] ?? 'Cardholder');
$cardType = sanitize_input($_POST['card_type'] ?? 'id_card');
$templateTitle = sanitize_input($_POST['template_title'] ?? 'Custom Card');
$dataJson = $_POST['data_json'] ?? '{}';
$previewFront = $_POST['preview_front'] ?? '';
$previewBack = $_POST['preview_back'] ?? '';

try {
    $stmt = $pdo->prepare("INSERT INTO card_history (user_id, cardholder_name, card_type, template_title, data_json, preview_front, preview_back) VALUES (:u, :c, :t, :tt, :dj, :pf, :pb)");
    $stmt->execute([
        'u' => $userId,
        'c' => $cardholderName,
        't' => $cardType,
        'tt' => $templateTitle,
        'dj' => $dataJson,
        'pf' => $previewFront,
        'pb' => $previewBack
    ]);

    json_response(1, 'Card history saved successfully.');
} catch (\Exception $e) {
    json_response(0, 'Failed to save history: ' . $e->getMessage());
}
