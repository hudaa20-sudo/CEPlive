<?php
require_once __DIR__ . '/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_out(['success' => false, 'error' => 'Method not allowed.'], 405);
}

$b = read_json_body();
$name = trim($b['name'] ?? '');
$email = trim($b['email'] ?? '');
$message = trim($b['message'] ?? '');

if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || $message === '') {
    json_out(['success' => false, 'error' => 'Please fill in your name, a valid email, and a message.'], 422);
}

$pdo = get_db();
$stmt = $pdo->prepare('INSERT INTO contact_messages (vendor_id, name, email, message) VALUES (?, ?, ?, ?)');
$stmt->execute([current_vendor_id(), $name, $email, $message]);

// No SMTP server configured for local testing. In production, send an email
// here to both the vendor's registered address and the admin inbox.

json_out(['success' => true, 'message' => 'Thanks for reaching out — we will be in touch soon.']);
