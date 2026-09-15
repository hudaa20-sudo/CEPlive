<?php
require_once __DIR__ . '/helpers.php';

$token = $_GET['token'] ?? '';
header('Content-Type: text/html');

if (!$token) {
    echo '<p>Missing verification token.</p>';
    exit;
}

$pdo = get_db();
$stmt = $pdo->prepare('SELECT id, name FROM vendors WHERE verify_token = ?');
$stmt->execute([$token]);
$vendor = $stmt->fetch();

if (!$vendor) {
    echo '<p>This verification link is invalid or has already been used.</p>';
    exit;
}

$stmt = $pdo->prepare('UPDATE vendors SET email_verified = 1, verify_token = NULL WHERE id = ?');
$stmt->execute([$vendor['id']]);
log_audit((int)$vendor['id'], 'email_verified', '');

echo '<p>Email verified! You can close this tab and return to VendorVerse.</p>'
   . '<p><a href="../profile.html">Go to your profile</a></p>';
