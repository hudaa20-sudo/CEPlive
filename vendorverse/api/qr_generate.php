<?php

require_once __DIR__ . '/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_out([
        'success' => false,
        'error' => 'Method not allowed.'
    ], 405);
}

$vendorId = require_login();
$b = read_json_body();

$upiId = trim($b['upiId'] ?? '');
$payeeName = trim($b['payeeName'] ?? '');
$amount = isset($b['amount']) && $b['amount'] !== ''
    ? (float)$b['amount']
    : null;
$note = trim($b['note'] ?? '') ?: null;


/*
|--------------------------------------------------------------------------
| VALIDATION
|--------------------------------------------------------------------------
*/

if (!preg_match('/^[\w.\-]+@[\w.\-]+$/', $upiId)) {
    json_out([
        'success' => false,
        'error' => 'Please enter a valid UPI ID.'
    ], 422);
}

if ($payeeName === '') {
    json_out([
        'success' => false,
        'error' => 'Please enter the payee/business name.'
    ], 422);
}

if ($amount !== null && $amount < 0) {
    json_out([
        'success' => false,
        'error' => 'Amount cannot be negative.'
    ], 422);
}


/*
|--------------------------------------------------------------------------
| CREATE INTERNAL TOKEN
|--------------------------------------------------------------------------
|
| This token is saved in your database so the transaction
| can be identified in your profile/history.
|
*/

$random = bin2hex(random_bytes(16));

$signature = substr(
    hash_hmac('sha256', $random, QR_SECRET),
    0,
    16
);

$token = $random . $signature;


/*
|--------------------------------------------------------------------------
| SAVE TRANSACTION IN DATABASE
|--------------------------------------------------------------------------
*/

$pdo = get_db();

$stmt = $pdo->prepare(
    'INSERT INTO qr_transactions
    (vendor_id, token, upi_id, payee_name, amount, note)
    VALUES (?, ?, ?, ?, ?, ?)'
);

$stmt->execute([
    $vendorId,
    $token,
    $upiId,
    $payeeName,
    $amount,
    $note
]);

$txnId = (int)$pdo->lastInsertId();


/*
|--------------------------------------------------------------------------
| BUILD REAL UPI PAYMENT QR
|--------------------------------------------------------------------------
|
| IMPORTANT:
| The QR now contains a UPI payment URI.
| It does NOT contain localhost.
|
*/

$upiUrl =
    'upi://pay' .
    '?pa=' . rawurlencode($upiId) .
    '&pn=' . rawurlencode($payeeName) .
    '&cu=INR';

if ($amount !== null) {
    $upiUrl .= '&am=' . rawurlencode(
        number_format($amount, 2, '.', '')
    );
}

if ($note !== null) {
    $upiUrl .= '&tn=' . rawurlencode($note);
}


/*
|--------------------------------------------------------------------------
| SAVE QR CONTENT
|--------------------------------------------------------------------------
|
| Save the actual UPI QR content in the database.
|
*/

$stmt = $pdo->prepare(
    'UPDATE qr_transactions
     SET qr_url = ?
     WHERE id = ?'
);

$stmt->execute([
    $upiUrl,
    $txnId
]);


/*
|--------------------------------------------------------------------------
| AUDIT LOG
|--------------------------------------------------------------------------
*/

log_audit(
    $vendorId,
    'qr_generate',
    "transaction_id=$txnId"
);


/*
|--------------------------------------------------------------------------
| RETURN RESULT
|--------------------------------------------------------------------------
*/

json_out([
    'success' => true,

    // This is what your JavaScript will turn into the QR.
    'qr_content' => $upiUrl,

    // Internal database transaction information.
    'transaction' => [
        'id' => $txnId,
        'token' => $token,
        'upi_id' => $upiId,
        'payee_name' => $payeeName,
        'amount' => $amount,
        'note' => $note,
        'status' => 'active'
    ]
]);