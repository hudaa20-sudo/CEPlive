<?php
require_once __DIR__ . '/helpers.php';

$vendorId = current_vendor_id();
if (!$vendorId) {
    json_out(['success' => true, 'logged_in' => false]);
}

$pdo = get_db();
$stmt = $pdo->prepare('SELECT id, name, email, email_verified FROM vendors WHERE id = ?');
$stmt->execute([$vendorId]);
$vendor = $stmt->fetch();

if (!$vendor) {
    json_out(['success' => true, 'logged_in' => false]);
}

json_out([
    'success' => true,
    'logged_in' => true,
    'vendor' => [
        'id' => (int)$vendor['id'],
        'name' => $vendor['name'],
        'email' => $vendor['email'],
        'email_verified' => (bool)$vendor['email_verified'],
    ],
]);
