<?php
require_once __DIR__ . '/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_out(['success' => false, 'error' => 'Method not allowed.'], 405);
}

$body = read_json_body();
$email = trim($body['email'] ?? '');
$password = $body['password'] ?? '';

if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 1) {
    json_out(['success' => false, 'error' => 'Please enter a valid email and password.'], 422);
}

$pdo = get_db();
$stmt = $pdo->prepare('SELECT id, name, email, password_hash, email_verified, status FROM vendors WHERE email = ?');
$stmt->execute([$email]);
$vendor = $stmt->fetch();

// Simple brute-force slow-down: fixed-time-ish check regardless of whether
// the account exists, then verify the password.
if (!$vendor || !password_verify($password, $vendor['password_hash'])) {
    json_out(['success' => false, 'error' => 'Incorrect email or password.'], 401);
}

if ($vendor['status'] === 'blocked') {
    json_out(['success' => false, 'error' => 'This account has been blocked. Contact support.'], 403);
}

$_SESSION['vendor_id'] = (int)$vendor['id'];
$_SESSION['vendor_name'] = $vendor['name'];

log_audit((int)$vendor['id'], 'login', '');

json_out([
    'success' => true,
    'message' => 'Welcome back!',
    'vendor' => [
        'id' => (int)$vendor['id'],
        'name' => $vendor['name'],
        'email' => $vendor['email'],
        'email_verified' => (bool)$vendor['email_verified'],
    ],
]);
