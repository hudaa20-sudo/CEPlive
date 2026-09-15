# VendorVerse — local setup (XAMPP / PHP + MySQL)

This is a plain HTML/CSS/JS site backed by a small PHP + MySQL API. No Node,
no build step — you just need XAMPP.

## 1. Install XAMPP
Download from https://www.apachefriends.org and install it (Windows/Mac/Linux
all work the same way from here).

## 2. Put the project in place
Copy this whole `vendorverse` folder into XAMPP's web root:
- Windows: `C:\xampp\htdocs\vendorverse`
- Mac: `/Applications/XAMPP/htdocs/vendorverse`
- Linux: `/opt/lampp/htdocs/vendorverse`

## 3. Start Apache and MySQL
Open the XAMPP Control Panel and click **Start** next to both **Apache** and
**MySQL**.

## 4. Create the database
1. Open http://localhost/phpmyadmin in your browser.
2. Click **Import** in the top menu.
3. Choose the file `api/schema.sql` from this project and click **Go**.
   This creates a `vendorverse` database with all the tables the app needs.

If your MySQL root user has a password (most default XAMPP installs don't),
open `api/config.php` and fill in `DB_USER` / `DB_PASS`.

## 5. Open the site
Go to **http://localhost/vendorverse/index.html** in your browser.

That's it — registration, login, profile saving, and QR generation all talk
to the PHP API under `api/` and store data in MySQL.

## How it's organized
```
vendorverse/
├── index.html          Landing page (nav shows login state, contact form)
├── login.html          Log in / sign up
├── profile.html        Vendor profile form + QR transaction history
├── qr.html             Generate a UPI-payment or link/text QR code
└── api/
    ├── config.php       Database connection settings — edit this if needed
    ├── schema.sql        Run this once in phpMyAdmin to create the tables
    ├── helpers.php       Shared session/JSON helpers
    ├── register.php       POST  create a vendor account
    ├── login.php           POST  log in
    ├── logout.php          POST  log out
    ├── session_check.php  GET   "am I logged in?" (used by every page's nav)
    ├── verify_email.php   GET   email verification link target
    ├── profile_get.php    GET   load the logged-in vendor's profile
    ├── profile_save.php   POST  save/update the profile
    ├── qr_generate.php    POST  create a signed, one-time QR transaction
    ├── qr_scan.php         GET   what the QR code itself opens/validates
    ├── qr_history.php      GET   the vendor's past QR transactions
    └── contact.php         POST  store a contact-form message
```

## Notes on how QR codes work here
Scanning a generated QR doesn't open a raw UPI link with payment details in
it — it opens a link to `api/qr_scan.php?token=...`. That endpoint checks the
token's signature, confirms it hasn't expired or already been used, marks it
"scanned", and shows the transaction details. This matches the "backend
resolves the transaction, nothing sensitive lives inside the QR itself"
requirement from the spec.

Tokens expire after 15 minutes (see `QR_EXPIRY_MINUTES` in `config.php`) and
can only be scanned once.

## Email verification
No mail server is set up for local testing, so instead of emailing the
verification link, `register.php` returns it directly in its response and
logs it to the browser console after signup. When you're ready to go live,
add a real mail step (`mail()`, or a library like PHPMailer with an SMTP
provider) in `register.php` and email that same link instead of returning it.

## What's not built yet
This covers the vendor-facing flow from the spec (register, verify, log in,
edit profile, generate/scan QR, contact form). The admin dashboard (vendor
list, warnings, blocking, contact inbox, audit log viewer) isn't built —
happy to add it as a next step once this part is working for you.
