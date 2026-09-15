<?php
require_once __DIR__ . '/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_out(['success' => false, 'error' => 'Method not allowed.'], 405);
}

$body = read_json_body();
$name = trim($body['name'] ?? '');
$email = trim($body['email'] ?? '');
$password = $body['password'] ?? '';

if (strlen($name) < 2) {
    json_out(['success' => false, 'error' => 'Please enter your name.'], 422);
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    json_out(['success' => false, 'error' => 'Please enter a valid email.'], 422);
}
if (strlen($password) < 6) {
    json_out(['success' => false, 'error' => 'Password must be at least 6 characters.'], 422);
}

$pdo = get_db();

$stmt = $pdo->prepare('SELECT id FROM vendors WHERE email = ?');
$stmt->execute([$email]);
if ($stmt->fetch()) {
    json_out(['success' => false, 'error' => 'An account with that email already exists.'], 409);
}

$hash = password_hash($password, PASSWORD_DEFAULT);
$verifyToken = bin2hex(random_bytes(16));

$stmt = $pdo->prepare(
    'INSERT INTO vendors (name, email, password_hash, verify_token) VALUES (?, ?, ?, ?)'
);
$stmt->execute([$name, $email, $hash, $verifyToken]);
$vendorId = (int)$pdo->lastInsertId();

log_audit($vendorId, 'register', 'New vendor account created.');

// No SMTP server is configured for local testing, so instead of emailing the
// verification link we just hand it back here. In production, email this
// link to the vendor instead of returning it.
$verifyLink = 'api/verify_email.php?token=' . $verifyToken;

// Log the vendor in immediately so local testing is friction-free.
$_SESSION['vendor_id'] = $vendorId;
$_SESSION['vendor_name'] = $name;

json_out([
    'success' => true,
    'message' => 'Account created! Verify your email to unlock QR generation.',
    'verify_link' => $verifyLink,
    'vendor' => ['id' => $vendorId, 'name' => $name, 'email' => $email, 'email_verified' => false],
]);
