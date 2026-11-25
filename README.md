# ScholarLocal

**ScholarLocal: A Web-Based Research Repository with TF-IDF and Cosine Similarity for PUP Institute of Technology Students**

This package is a clean, standalone **HTML, CSS, JavaScript, PHP, and MySQL/SQLite** conversion of ScholarLocal—completely independent of Figma, Next.js, Payload CMS, and Node.js.

## Directory Structure

```text
scholarlocal/
├── index.php                  # Home page (hero search, filters, archive stats)
├── browse.php                 # TF-IDF & Cosine Similarity search + sidebar filters
├── study.php                  # Study details, watermarked PDF viewer, download requests
├── about.php                  # About PUP ITECH ScholarLocal
├── login.php                  # Student/Faculty login & registration (PUP email + RA 10173)
├── logout.php                 # Session logout
├── admin/
│   ├── index.php              # Admin & Super Admin dashboard
│   └── login.php              # Repository staff portal login
├── api/
│   ├── search.php             # JSON TF-IDF search endpoint
│   ├── download-request.php   # Submit & review PDF download requests
│   ├── download-pdf.php       # RBAC-protected PDF download streamer
│   └── study-save.php         # Admin study upload + automated PDF text extraction
├── includes/
│   ├── auth.php               # Session, CSRF, RBAC, and audit log helpers
│   ├── db.php                 # PDO database connector (MySQL & SQLite)
│   ├── tfidf.php              # TF-IDF + Cosine Similarity engine (unigrams + bigrams)
│   ├── pdf_parser.php         # PDF text extraction helper
│   ├── header.php             # Shared header
│   └── footer.php             # Shared footer
├── config/
│   └── config.php             # App & database configuration
├── database/
│   ├── schema.mysql.sql       # MySQL / MariaDB schema + seed data
│   └── schema.sqlite.sql      # SQLite schema + seed data (zero-config option)
├── assets/
│   ├── css/style.css          # Responsive PUP ITECH stylesheet
│   ├── js/app.js              # PDF viewer controls & filter interactions
│   └── images/                # Optimized logos & campus hero WebP/JPG
└── uploads/
    └── studies/               # Protected PDF storage (blocked from direct HTTP access)
```

## Quick Setup

### Option 1: MySQL / MariaDB (XAMPP, Laragon, Hostinger, cPanel)
1. Import `database/schema.mysql.sql` into phpMyAdmin or MySQL CLI (`scholarlocal_db`).
2. Edit `config/config.php` with your `DB_HOST`, `DB_NAME`, `DB_USER`, and `DB_PASS` (default is `127.0.0.1`, `scholarlocal_db`, `root`, empty password).
3. Upload the `scholarlocal/` folder to your web root (`htdocs/`, `www/`, or `public_html/`).

### Option 2: Zero-Config SQLite (Local Testing)
1. In `config/config.php`, change `DB_DRIVER` from `'mysql'` to `'sqlite'`.
2. Open `index.php` in your PHP server—`database/scholarlocal.sqlite` is automatically created and seeded from `database/schema.sqlite.sql`.

## Seed Test Accounts
- **Super Admin** (`/admin/login.php`): `director@pup.edu.ph` / `Admin@2026!`
- **Content Admin** (`/admin/login.php`): `repo.admin@pup.edu.ph` / `Admin@2026!`
- **Faculty** (`/login.php`): `prof.payra@pup.edu.ph` / `Faculty@2026!`
- **Student** (`/login.php`): `juan.delacruz@iskolarngbayan.pup.edu.ph` / `Student@2026!`


