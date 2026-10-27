# UIU Friends Loan & Crowdfunding

A peer-to-peer student loan and crowdfunding platform for UIU students, built with
**PHP, MySQL, HTML, CSS, and JavaScript** — including a full **Admin Panel**.

Students can:
- Post a loan request and receive competing offers from other students
- Make lending offers, get accepted, digitally sign an agreement, and pay via a simulated escrow/payment flow (bKash / Nagad / Card / Bank)
- Start and donate to verified crowdfunding campaigns with a public donation ledger
- Message each other about a loan post (monitored chat, with a "Report" option)
- Track a wallet/transaction history, trust score, and reviews
- Manage UIU ID / email / phone verification

Admins can:
- View platform KPIs (users, loan volume, donation volume, open reports)
- Verify or suspend student accounts
- Approve/remove loan posts
- Verify/reject crowdfunding campaigns
- View all loan payments and donations
- Review and resolve reported conversations / disputed agreements

---

## 1. Requirements

- PHP **8.0+** (uses `str_ends_with`, typed properties, etc.)
- MySQL **5.7+** or MariaDB **10.4+**
- Any web server: Apache/XAMPP/WAMP/MAMP, Nginx, or PHP's built-in server
- PHP extensions: `pdo_mysql`, `mbstring` (recommended, not required — the app falls back gracefully if it's missing)

## 2. Setup (XAMPP / WAMP — most common for students)

1. Copy the whole `uiu-friends` folder into your server's web root, e.g. `htdocs/uiu-friends`.
2. Start Apache and MySQL from your XAMPP/WAMP control panel.
3. Open **phpMyAdmin** → **Import** → choose `database/schema.sql` → Go.
   This creates the `uiu_friends` database, all tables, and seed/demo data.
4. Open `config/db.php` and confirm the credentials match your MySQL setup
   (defaults `root` / empty password work out-of-the-box on XAMPP):
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'uiu_friends');
   define('DB_USER', 'root');
   define('DB_PASS', '');
   ```
5. Visit `http://localhost/uiu-friends/login.php` in your browser.

## 2b. Setup with PHP's built-in server (quick local test, no Apache needed)

```bash
mysql -u root < database/schema.sql
php -S localhost:8000
```
Then open `http://localhost:8000/login.php`.

## 3. Demo Logins

All seed accounts use the password: **password123**

| Role     | Email                        | Notes                          |
|----------|-------------------------------|---------------------------------|
| Student  | nadia@bscee.uiu.ac.bd         | Has an active loan agreement    |
| Student  | mahmud@bscse.uiu.ac.bd        | Lender on that agreement        |
| Student  | rashed@bscse.uiu.ac.bd        | Organizes the surgery campaign  |
| Admin    | admin@uiufriends.com          | Admin Panel → `/admin/login.php`|

You can also register a brand-new student account from `register.php`.

## 4. Project Structure

```
uiu-friends/
├── config/db.php              # MySQL connection settings
├── database/schema.sql        # Full schema + seed/demo data
├── includes/
│   ├── functions.php          # Session, auth, CSRF, and formatting helpers
│   ├── header.php / sidebar.php / topbar.php
├── assets/
│   ├── css/style.css          # Main site styling
│   ├── css/admin.css          # Admin-panel-only styling
│   ├── js/main.js             # Small progressive-enhancement JS
│   └── img/default-avatar.svg
├── login.php / register.php / logout.php
├── home.php                   # Main feed (loan posts + campaigns)
├── loan_posts.php             # Browse / post loan requests
├── loan_details.php           # View a post, make/accept offers
├── agreement.php              # Digital agreement + e-signatures
├── payment.php                # Choose method, pay an installment
├── summary.php                # Transaction receipt + review
├── crowdfunding.php           # Browse / start campaigns
├── campaign_details.php       # Donate + public ledger
├── messages.php               # Student-to-student chat
├── wallet.php                 # Transaction history, dues
├── profile.php                # Public/own profile, trust score
├── security.php               # Verification + password
├── search.php                 # Unified search (loans + campaigns)
└── admin/
    ├── login.php / logout.php
    ├── dashboard.php           # KPIs
    ├── users.php               # Verify / suspend students
    ├── loan_posts.php          # Approve / remove posts
    ├── campaigns.php           # Verify / reject campaigns
    ├── transactions.php        # All loan payments + donations
    ├── reports.php             # Reported chats + disputed agreements
    ├── view_user.php           # Full user credentials review + verify each one
    ├── view_post.php           # Full loan post view (offers, agreement, payments)
    └── view_campaign.php       # Full campaign view + donation ledger
```

## 5. Database Overview

Key tables: `users`, `loan_posts`, `loan_offers`, `agreements`, `installments`,
`transactions`, `campaigns`, `donations`, `messages`, `reviews`, `reports`.
See `database/schema.sql` for full column definitions, foreign keys, and comments.

## 6. Security Notes (already implemented)

- Passwords hashed with PHP's `password_hash()` (bcrypt)
- All forms protected with CSRF tokens
- All SQL uses PDO **prepared statements** (no raw string interpolation)
- Output escaped with `htmlspecialchars()` via the `e()` helper
- Separate admin session (`$_SESSION['admin_id']`) from the student session
- Agreements require **both parties** to digitally sign before funds can be released

## 7. Notes / Possible Extensions

- Payments (bKash/Nagad/Card/Bank) are **simulated** for demo purposes — no real
  payment gateway is called. In production you'd integrate SSLCommerz/bKash's
  actual API inside `payment.php`.
- File uploads for ID/document verification (`security.php`) currently just log
  a "submitted for review" flash message — wire up real file storage under
  `uploads/` if you need it to persist.
- The layout is desktop-first (matches the original design); the sidebar hides
  under ~860px — add a mobile hamburger toggle if you need a full mobile nav.
