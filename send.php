<?php
declare(strict_types=1);

require_once __DIR__ . '/mailer.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Méthode non autorisée.']);
    exit;
}

if (!empty($_POST['website'])) {
    echo json_encode(['ok' => true]);
    exit;
}

function clean_field(string $value): string
{
    return trim(filter_var($value, FILTER_SANITIZE_FULL_SPECIAL_CHARS));
}

$nom = clean_field($_POST['nom'] ?? '');
$prenom = clean_field($_POST['prenom'] ?? '');
$email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
$phone = clean_field($_POST['phone'] ?? '');
$date = clean_field($_POST['date'] ?? '');

$errors = [];
if ($nom === '' || mb_strlen($nom) > 80) {
    $errors[] = 'Nom invalide.';
}
if ($prenom === '' || mb_strlen($prenom) > 80) {
    $errors[] = 'Prénom invalide.';
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Email invalide.';
}
if ($phone === '' || mb_strlen($phone) > 30) {
    $errors[] = 'Téléphone invalide.';
}
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    $errors[] = 'Date invalide.';
}

if ($errors) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => implode(' ', $errors)]);
    exit;
}

$payload = [
    'nom' => $nom,
    'prenom' => $prenom,
    'email' => $email,
    'phone' => $phone,
    'date' => $date,
    'date_fr' => date('d/m/Y', strtotime($date)),
    'received_at' => date('d/m/Y H:i'),
];

$result = send_inscription_email($payload);

$dataDir = __DIR__ . DIRECTORY_SEPARATOR . 'data';
if (!is_dir($dataDir)) {
    mkdir($dataDir, 0755, true);
}

$store = $dataDir . DIRECTORY_SEPARATOR . 'inscriptions.json';
$existing = [];
if (is_file($store)) {
    $decoded = json_decode((string) file_get_contents($store), true);
    if (is_array($decoded)) {
        $existing = $decoded;
    }
}

$existing[] = [
    'nom' => $nom,
    'prenom' => $prenom,
    'email' => $email,
    'phone' => $phone,
    'date' => $date,
    'received_at' => date('c'),
    'email_sent' => !empty($result['ok']),
    'channel' => $result['channel'] ?? '',
    'activation_required' => !empty($result['activation_required']),
];
file_put_contents($store, json_encode($existing, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

if (empty($result['ok']) && empty($result['activation_required'])) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'error' => $result['error'] ?? "L'email n'a pas pu partir.",
    ]);
    exit;
}

echo json_encode([
    'ok' => true,
    'email_sent' => !empty($result['ok']),
    'activation_required' => !empty($result['activation_required']),
]);
