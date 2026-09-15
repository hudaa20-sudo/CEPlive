<?php

require_once __DIR__ . '/helpers.php';

$vendorId = require_login();

$pdo = get_db();


/*
|--------------------------------------------------------------------------
| GET VENDOR PROFILE
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare(
    'SELECT *
     FROM vendor_profiles
     WHERE vendor_id = ?'
);

$stmt->execute([$vendorId]);

$profile = $stmt->fetch();


/*
|--------------------------------------------------------------------------
| GET TRANSACTION HISTORY
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare(
    'SELECT
        id,
        token,
        upi_id,
        payee_name,
        amount,
        note,
        qr_url,
        status,
        scan_count,
        last_scanned_at,
        created_at
     FROM qr_transactions
     WHERE vendor_id = ?
     ORDER BY id DESC'
);

$stmt->execute([$vendorId]);

$transactions = $stmt->fetchAll();


/*
|--------------------------------------------------------------------------
| RETURN PROFILE + TRANSACTIONS
|--------------------------------------------------------------------------
*/

json_out([
    'success' => true,

    'profile' => $profile ?: null,

    'transactions' => $transactions ?: []
]);