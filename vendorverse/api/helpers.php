<?php
require_once __DIR__ . '/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

function json_out($data, int $code = 200): void {
    http_response_code($code);
    echo json_encode($data);
    exit;
}

function read_json_body(): array {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function current_vendor_id(): ?int {
    return $_SESSION['vendor_id'] ?? null;
}

function require_login(): int {
    $id = current_vendor_id();
    if (!$id) {
        json_out(['success' => false, 'error' => 'Not logged in.'], 401);
    }
    return $id;
}

function log_audit(?int $vendorId, string $action, string $details = ''): void {
    $pdo = get_db();
    $stmt = $pdo->prepare('INSERT INTO audit_log (vendor_id, action, details) VALUES (?, ?, ?)');
    $stmt->execute([$vendorId, $action, $details]);
}
