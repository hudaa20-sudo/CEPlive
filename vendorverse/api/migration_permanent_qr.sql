-- Run this ONCE in phpMyAdmin (SQL tab) on your existing vendorverse database.
-- It updates qr_transactions so QR codes no longer expire or get locked
-- after one scan, and stores the scan URL so it can be redrawn on the
-- profile page.

USE vendorverse;

ALTER TABLE qr_transactions
  MODIFY COLUMN status ENUM('active','revoked') NOT NULL DEFAULT 'active',
  MODIFY COLUMN expires_at DATETIME NULL,
  ADD COLUMN qr_url VARCHAR(255) NULL AFTER token,
  ADD COLUMN scan_count INT NOT NULL DEFAULT 0 AFTER status,
  ADD COLUMN last_scanned_at DATETIME NULL AFTER scan_count;

-- Any old rows that were 'pending'/'scanned'/'expired' just become 'active'.
UPDATE qr_transactions SET status = 'active' WHERE status NOT IN ('active','revoked');
