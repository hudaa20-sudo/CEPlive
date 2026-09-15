<?php
require_once __DIR__ . '/config.php';
// This endpoint is what a scanned QR code actually opens, so it renders a
// plain HTML page (not JSON) and does not require the visitor to be logged in.

header('Content-Type: text/html');

function render($title, $message, $ok) {
    $color = $ok ? '#1e824c' : '#c0392b';
    echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>$title</title>
    <style>body{font-family:-apple-system,Arial,sans-serif;max-width:420px;margin:60px auto;text-align:center;color:#14161a;padding:0 20px;}
    h1{color:$color;font-size:20px;}p{color:#4b4f58;font-size:15px;line-height:1.5;}</style></head><body>
    <h1>$title</h1><p>$message</p></body></html>";
    exit;
}

$token = $_GET['token'] ?? '';
if (!preg_match('/^[a-f0-9]{48}$/', $token)) {
    render('Invalid QR code', 'This code is not a recognized VendorVerse transaction.', false);
}

// Verify the HMAC signature before even touching the database.
$random = substr($token, 0, 32);
$signature = substr($token, 32);
$expectedSignature = substr(hash_hmac('sha256', $random, QR_SECRET), 0, 16);
if (!hash_equals($expectedSignature, $signature)) {
    render('Invalid QR code', 'This code failed signature verification and may be tampered with.', false);
}

$pdo = get_db();
$stmt = $pdo->prepare(
    'SELECT qt.*, v.name AS vendor_display_name FROM qr_transactions qt
     JOIN vendors v ON v.id = qt.vendor_id WHERE qt.token = ?'
);
$stmt->execute([$token]);
$txn = $stmt->fetch();

if (!$txn) {
    render('Invalid QR code', 'No matching transaction was found for this code.', false);
}

if ($txn['status'] === 'revoked') {
    render('QR code deactivated', 'The vendor has deactivated this QR code.', false);
}

// This is a permanent, reusable shop QR — scanning it just logs the visit,
// it doesn't expire or lock after one use.
$stmt = $pdo->prepare('UPDATE qr_transactions SET scan_count = scan_count + 1, last_scanned_at = NOW(), scan_ip = ? WHERE id = ?');
$stmt->execute([$_SERVER['REMOTE_ADDR'] ?? null, $txn['id']]);
log_audit_public($txn['vendor_id'], 'qr_scanned', "token={$token}");

$amount = $txn['amount'] !== null ? '₹' . number_format((float)$txn['amount'], 2) : 'not specified';
$note = htmlspecialchars($txn['note'] ?? '', ENT_QUOTES);

render(
    'Transaction verified',
    "Vendor: " . htmlspecialchars($txn['vendor_display_name'], ENT_QUOTES) . "<br>"
    . "Payee: " . htmlspecialchars($txn['payee_name'], ENT_QUOTES) . "<br>"
    . "Amount: $amount" . ($note ? "<br>Note: $note" : '')
    . "<br><br><small>This confirms an authorized VendorVerse QR record. It does not, by itself, confirm a payment provider transferred funds.</small>",
    true
);

function log_audit_public($vendorId, $action, $details) {
    $pdo = get_db();
    $stmt = $pdo->prepare('INSERT INTO audit_log (vendor_id, action, details) VALUES (?, ?, ?)');
    $stmt->execute([$vendorId, $action, $details]);
}
