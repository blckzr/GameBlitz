# GameBlitz 🎮

A full-stack PHP/MySQL e-commerce website for a Filipino gaming store. Customers browse and buy games, contact support, and track their orders. Admins manage the catalog, users, orders, and inquiries from a dedicated panel.

Built as the capstone for a Web Development course, it covers the full stack — semantic HTML, responsive CSS, vanilla JavaScript, PHP with PDO, and MySQL.

---

## ✨ Features

**Customer side**
- Browse games with **live AJAX search**, platform/category filters, and sort
- Product detail modal with description
- Cart (localStorage) → checkout → order placed (instant "paid" simulation)
- Sign up / sign in with hashed passwords and PHP sessions
- Profile page: edit name/email/password, view orders, view inquiries
- Contact form auto-fills name/email when logged in

**Admin side** (`/admin`)
- Dashboard with stat cards and recent activity
- Full CRUD for **Products** (with JPG/PNG image upload, drag & drop)
- Full CRUD for **Users** (promote/demote, deactivate)
- **Orders** management — change status (pending → confirmed → shipped → completed)
- **Inquiries** management — update status, view customer details

---

## 🛠️ Tech Stack

| Layer | Technology |
|---|---|
| Frontend | HTML5, CSS3 (Flexbox/Grid), vanilla JavaScript (`fetch` API) |
| Backend | PHP 8 (PDO with prepared statements) |
| Database | MySQL 8 (InnoDB, utf8mb4) |
| Server | Apache via XAMPP |

---

## 🚀 Setup (for developers)

### 1. Install XAMPP

Download and install [XAMPP](https://www.apachefriends.org/) (any recent version with PHP 8+ and MySQL).

### 2. Clone into the XAMPP htdocs folder

```bash
cd C:\xampp\htdocs
git clone <your-repo-url> GameBlitz
```

### 3. Start Apache and MySQL

Open the XAMPP Control Panel → click **Start** next to both **Apache** and **MySQL**.

### 4. Create the database

1. Visit [http://localhost/phpmyadmin](http://localhost/phpmyadmin)
2. Click the **Import** tab
3. Choose the file: `database/gameblitz.sql`
4. Click **Go**

This creates the `gameblitz_db` database with all 10 tables and seed data (8 sample games, categories, platforms, and a default admin user).

### 5. Set the admin password

The seed admin has a placeholder password hash. Run this once to set a real one:

```
http://localhost/GameBlitz/database/setup.php
```

Default credentials after setup:

| | |
|---|---|
| **Email** | `admin@gameblitz.com` |
| **Password** | `Admin@GameBlitz1` |

> ⚠️ **Delete `database/setup.php` immediately** after running it (or just don't deploy it).

### 6. Open the site

```
http://localhost/GameBlitz/
```

You're done. Sign in as the admin to see the `/admin` panel, or register a new account as a customer.

---

## 📂 Project Structure

```
GameBlitz/
├── index.php              # Home (deals, latest releases, categories)
├── products.php           # Catalog with AJAX search & filters
├── contact.php            # Support contact form
├── profile.php            # User profile, orders, inquiries
├── signin.php             # Login
├── register.php           # Sign up
├── cart.html              # Shopping cart (localStorage)
│
├── admin/                 # Admin panel (session-guarded)
│   ├── index.php          #   dashboard
│   ├── products.php       #   product CRUD
│   ├── users.php          #   user management
│   ├── orders.php         #   order status management
│   ├── inquiries.php      #   customer support tickets
│   └── guard.php          #   admin-only access check
│
├── api/                   # AJAX endpoints (return JSON)
│   ├── login.php
│   ├── register.php
│   ├── logout.php
│   ├── products.php       #   AJAX product search/filter
│   ├── checkout.php       #   order placement
│   ├── contact.php        #   inquiry submission
│   └── profile_update.php #   profile edit
│
├── database/
│   ├── db.php             # PDO connection (edit credentials here)
│   ├── gameblitz.sql      # full schema + seed data
│   └── setup.php          # one-time admin password setup (delete after use)
│
├── css/                   # Stylesheets
├── js/                    # Client-side scripts
└── assets/img/            # Product images & media
```

---

## 🔐 Database Configuration

Edit `database/db.php` if your MySQL setup differs from the defaults:

```php
define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_NAME', 'gameblitz_db');
define('DB_USER', 'root');
define('DB_PASS', '');
```

---

## 🧪 Common Tasks

| Task | How |
|---|---|
| Reset the database | Re-import `database/gameblitz.sql` via phpMyAdmin |
| Add a new product | Sign in as admin → Products → Add Product |
| Test checkout | Add items to cart → Sign in → Proceed to Checkout |
| View an order's status | Profile page (customer) or `/admin/orders.php` (admin) |

---

## 📝 Notes

- All database queries use **PDO prepared statements** — no SQL injection vectors.
- All passwords are hashed with `password_hash()` (bcrypt).
- Image uploads accept JPG, JPEG, and PNG up to 10 MB.
- The cart is stored in `localStorage` so guests can shop before signing in.
- The checkout marks orders as paid immediately (no real payment gateway integration).
