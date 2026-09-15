<?php
require_once __DIR__ . '/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_out(['success' => false, 'error' => 'Method not allowed.'], 405);
}

$vendorId = require_login();
$b = read_json_body();

// Basic required-field validation (mirrors the required attrs in profile.html)
$fullName = trim($b['fullName'] ?? '');
$shopName = trim($b['shopName'] ?? '');
$mobile = trim($b['mobile'] ?? '');
$city = trim($b['city'] ?? '');

if ($fullName === '' || $shopName === '' || $mobile === '' || $city === '') {
    json_out(['success' => false, 'error' => 'Full name, shop name, mobile, and city are required.'], 422);
}

$fields = [
    'full_name' => $fullName,
    'shop_name' => $shopName,
    'mobile' => $mobile,
    'contact_email' => trim($b['email'] ?? '') ?: null,
    'language' => $b['language'] ?? null,
    'city' => $city,
    'address' => $b['address'] ?? null,
    'category' => $b['category'] ?? null,
    'products_offered' => $b['productsOffered'] ?? null,
    'years_in_business' => isset($b['yearsInBusiness']) && $b['yearsInBusiness'] !== '' ? (int)$b['yearsInBusiness'] : null,
    'shop_type' => $b['shopType'] ?? null,
    'hours_open' => $b['hoursOpen'] ?: null,
    'hours_close' => $b['hoursClose'] ?: null,
    'upi_available' => !empty($b['upiAvailable']) ? 1 : 0,
    'qr_available' => !empty($b['qrAvailable']) ? 1 : 0,
    'smartphone_available' => !empty($b['smartphoneAvailable']) ? 1 : 0,
    'internet_access' => !empty($b['internetAccess']) ? 1 : 0,
    'payment_apps' => isset($b['paymentApps']) ? implode(',', (array)$b['paymentApps']) : null,
    'earnings_range' => $b['earningsRange'] ?? null,
    'payment_methods' => isset($b['paymentMethods']) ? implode(',', (array)$b['paymentMethods']) : null,
    'photo_data_url' => $b['photoDataUrl'] ?? null,
];

$pdo = get_db();
$stmt = $pdo->prepare('SELECT vendor_id FROM vendor_profiles WHERE vendor_id = ?');
$stmt->execute([$vendorId]);
$exists = $stmt->fetch();

$fieldsWithVendor = array_merge($fields, ['vendor_id' => $vendorId]);

if ($exists) {
    $sets = implode(', ', array_map(fn($k) => "$k = :$k", array_keys($fields)));
    $stmt = $pdo->prepare("UPDATE vendor_profiles SET $sets WHERE vendor_id = :vendor_id");
    $stmt->execute($fieldsWithVendor);
} else {
    $cols = array_keys($fieldsWithVendor);
    $placeholders = implode(', ', array_map(fn($k) => ":$k", $cols));
    $stmt = $pdo->prepare('INSERT INTO vendor_profiles (' . implode(', ', $cols) . ") VALUES ($placeholders)");
    $stmt->execute($fieldsWithVendor);
}

log_audit($vendorId, 'profile_save', '');

json_out(['success' => true, 'message' => 'Profile saved.']);
