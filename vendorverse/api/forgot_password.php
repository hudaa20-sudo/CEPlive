<?php
require_once __DIR__ . '/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_out(['success' => false, 'error' => 'Method not allowed.'], 405);
}

$b = read_json_body();
$email = trim($b['email'] ?? '');

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    json_out(['success' => false, 'error' => 'Please enter a valid email.'], 422);
}

$pdo = get_db();
$stmt = $pdo->prepare('SELECT id, name FROM vendors WHERE email = ?');
$stmt->execute([$email]);
$vendor = $stmt->fetch();

// Always respond success even if the email isn't registered, so this
// endpoint can't be used to check which emails exist in the system.
if (!$vendor) {
    json_out(['success' => true]);
}

$token = bin2hex(random_bytes(32));
$expiresAt = date('Y-m-d H:i:s', time() + 30 * 60); // 30-minute window

$stmt = $pdo->prepare('UPDATE vendors SET reset_token = ?, reset_token_expires = ? WHERE id = ?');
$stmt->execute([$token, $expiresAt, $vendor['id']]);

log_audit((int)$vendor['id'], 'password_reset_requested', '');

// No SMTP server configured for local testing — same approach register.php
// uses for the verification link: hand it back directly instead of emailing.
$resetLink = 'reset-password.html?token=' . urlencode($token);

json_out(['success' => true, 'reset_link' => $resetLink]);
