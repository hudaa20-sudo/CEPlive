<?php
require_once __DIR__ . '/helpers.php';

$vendorId = require_login();

$pdo = get_db();
$stmt = $pdo->prepare(
    'SELECT id, token, qr_url, upi_id, payee_name, amount, note, status, created_at, scan_count, last_scanned_at
     FROM qr_transactions WHERE vendor_id = ? ORDER BY created_at DESC LIMIT 50'
);
$stmt->execute([$vendorId]);

json_out(['success' => true, 'transactions' => $stmt->fetchAll()]);
