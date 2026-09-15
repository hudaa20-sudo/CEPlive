<?php
// Direct password reset: verify the email exists, then set the new password
// immediately. No email/token step — the modal on login.html collects the
// email + new password + confirm password all at once.
//
// Note: since there's no email-ownership check here, anyone who knows a
// vendor's email can reset that account's password. That's fine for local
// testing, but before this goes on a real server you'd want to bring back
// a verification step (e.g. re-require the old password, or a one-time
// code sent to the email) so a stranger can't take over an account.

require_once __DIR__ . '/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_out(['success' => false, 'error' => 'Method not allowed.'], 405);
}

$b = read_json_body();
$email = trim($b['email'] ?? '');
$password = (string) ($b['password'] ?? '');

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    json_out(['success' => false, 'error' => 'Please enter a valid email.'], 422);
}
if (strlen($password) < 6) {
    json_out(['success' => false, 'error' => 'Password must be at least 6 characters.'], 422);
}

$pdo = get_db();
$stmt = $pdo->prepare('SELECT id FROM vendors WHERE email = ?');
$stmt->execute([$email]);
$vendor = $stmt->fetch();

if (!$vendor) {
    json_out(['success' => false, 'error' => 'No account found with that email.'], 404);
}

$hash = password_hash($password, PASSWORD_DEFAULT);
$stmt = $pdo->prepare('UPDATE vendors SET password_hash = ?, reset_token = NULL, reset_token_expires = NULL WHERE id = ?');
$stmt->execute([$hash, $vendor['id']]);

log_audit((int)$vendor['id'], 'password_reset', '');

json_out(['success' => true, 'message' => 'Password updated.']);
