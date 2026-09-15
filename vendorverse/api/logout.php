<?php
require_once __DIR__ . '/helpers.php';

$vendorId = current_vendor_id();
if ($vendorId) {
    log_audit($vendorId, 'logout', '');
}
$_SESSION = [];
session_destroy();

json_out(['success' => true]);
