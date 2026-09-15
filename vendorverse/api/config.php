<?php
// ---- Database connection settings ----
// Defaults match a stock XAMPP install (MySQL user "root", no password).
// Change these if your setup is different.

define('DB_HOST', 'localhost');
define('DB_NAME', 'vendorverse');
define('DB_USER', 'root');
define('DB_PASS', '');

// A secret key used to sign QR tokens. Change this to any random string
// before you put this site on a real server.
define('QR_SECRET', 'change-this-to-a-long-random-string');

// QR codes are now permanent/reusable, so this is unused — kept in case you
// want expiring codes again later.
define('QR_EXPIRY_MINUTES', 15);

// ---- AI chatbot settings (Google Gemini — free tier, no card needed) ----
// Get a key from https://aistudio.google.com/app/apikey and paste it below.
// Never share this key or commit it to a public repo.
define('GEMINI_API_KEY', 'AQ.Ab8RN6K-5gslCdI4KKKTG9d5uB_5gTM1llbCH_poGr--CWxLfA');
define('GEMINI_MODEL', 'gemini-3.6-flash');

function get_db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error' => 'Database connection failed. Did you create the "vendorverse" database and import schema.sql? (' . $e->getMessage() . ')'
            ]);
            exit;
        }
    }
    return $pdo;
}
