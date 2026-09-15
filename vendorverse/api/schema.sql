-- VendorVerse database schema
-- Import this in phpMyAdmin (or run via `mysql -u root -p vendorverse < schema.sql`)

CREATE DATABASE IF NOT EXISTS vendorverse CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE vendorverse;

-- Vendors (the people who register/log in)
CREATE TABLE IF NOT EXISTS vendors (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  email_verified TINYINT(1) NOT NULL DEFAULT 0,
  verify_token VARCHAR(64) DEFAULT NULL,
  reset_token VARCHAR(64) DEFAULT NULL,
  reset_token_expires DATETIME DEFAULT NULL,
  status ENUM('active','blocked') NOT NULL DEFAULT 'active',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- One profile row per vendor (fields mirror profile.html)
CREATE TABLE IF NOT EXISTS vendor_profiles (
  vendor_id INT PRIMARY KEY,
  full_name VARCHAR(150),
  shop_name VARCHAR(150),
  mobile VARCHAR(20),
  contact_email VARCHAR(190),
  language VARCHAR(30),
  city VARCHAR(120),
  address TEXT,
  category VARCHAR(60),
  products_offered TEXT,
  years_in_business INT,
  shop_type VARCHAR(40),
  hours_open TIME,
  hours_close TIME,
  upi_available TINYINT(1) DEFAULT 0,
  qr_available TINYINT(1) DEFAULT 0,
  smartphone_available TINYINT(1) DEFAULT 0,
  internet_access TINYINT(1) DEFAULT 0,
  payment_apps VARCHAR(255),      -- comma separated
  earnings_range VARCHAR(30),
  payment_methods VARCHAR(255),   -- comma separated
  photo_data_url MEDIUMTEXT,      -- optional base64 image
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (vendor_id) REFERENCES vendors(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Signed QR transaction records. QR codes are permanent and reusable by
-- design (a vendor's standing shop QR) — each scan is just logged, not
-- consumed.
CREATE TABLE IF NOT EXISTS qr_transactions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  vendor_id INT NOT NULL,
  token VARCHAR(64) NOT NULL UNIQUE,
  qr_url VARCHAR(255) DEFAULT NULL,
  upi_id VARCHAR(150) NOT NULL,
  payee_name VARCHAR(150) NOT NULL,
  amount DECIMAL(10,2) DEFAULT NULL,
  note VARCHAR(255) DEFAULT NULL,
  status ENUM('active','revoked') NOT NULL DEFAULT 'active',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  expires_at DATETIME DEFAULT NULL,
  scan_count INT NOT NULL DEFAULT 0,
  last_scanned_at DATETIME DEFAULT NULL,
  scan_ip VARCHAR(64) DEFAULT NULL,
  FOREIGN KEY (vendor_id) REFERENCES vendors(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Contact form submissions (index.html modal)
CREATE TABLE IF NOT EXISTS contact_messages (
  id INT AUTO_INCREMENT PRIMARY KEY,
  vendor_id INT DEFAULT NULL,
  name VARCHAR(150) NOT NULL,
  email VARCHAR(190) NOT NULL,
  message TEXT NOT NULL,
  status ENUM('new','read','resolved') NOT NULL DEFAULT 'new',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (vendor_id) REFERENCES vendors(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Simple audit log (grows over time as you add admin features)
CREATE TABLE IF NOT EXISTS audit_log (
  id INT AUTO_INCREMENT PRIMARY KEY,
  vendor_id INT DEFAULT NULL,
  action VARCHAR(100) NOT NULL,
  details TEXT,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;
