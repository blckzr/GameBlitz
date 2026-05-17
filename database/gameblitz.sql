-- ============================================================
--  GameBlitz — Master Database Schema
--  Compatible with: MySQL 8.0+
--  Character set:   utf8mb4 (full Unicode, emoji-safe)
--  Storage engine:  InnoDB (ACID transactions, foreign keys)
--
--  Table inventory (10 tables)
--  ─────────────────────────────────────────────────────────
--  Lookup        : platforms, categories
--  Core          : users, products, product_platforms
--  Commerce      : cart_sessions, cart_items, orders, order_items
--  Support       : inquiries
--
--  PHP phase mapping
--  ─────────────────────────────────────────────────────────
--  CRUD target   : products  (index.php / add.php / edit.php / delete.php)
--  Auth session  : users     ($_SESSION['user_id'])
--  Cart session  : cart_sessions + cart_items  ($_SESSION / session_id())
--  Contact form  : inquiries (contact.php)
-- ============================================================

-- ------------------------------------------------------------
-- 0. Database bootstrap
-- ------------------------------------------------------------
CREATE DATABASE IF NOT EXISTS gameblitz_db
    CHARACTER SET  utf8mb4
    COLLATE        utf8mb4_unicode_ci;

USE gameblitz_db;

-- Safety: disable FK checks while rebuilding (dev / re-run friendly)
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================
-- SECTION A — LOOKUP TABLES
-- ============================================================

-- ------------------------------------------------------------
-- 1. platforms
--    Stores every gaming platform GameBlitz sells for.
--    TINYINT UNSIGNED (0-255) is sufficient; we have < 10 platforms.
--    Slug is the lowercase key used in HTML data-* attributes
--    and the PHP filter layer — keeps UI ↔ DB in sync.
-- ------------------------------------------------------------
DROP TABLE IF EXISTS platforms;
CREATE TABLE platforms (
    platform_id   TINYINT UNSIGNED    NOT NULL AUTO_INCREMENT,
    slug          VARCHAR(20)         NOT NULL  COMMENT 'Machine key: ps5 | xbox | switch | pc',
    name          VARCHAR(60)         NOT NULL  COMMENT 'Display name: PlayStation 5',

    PRIMARY KEY (platform_id),
    UNIQUE KEY uq_platforms_slug (slug)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Gaming platform reference — joined to products via product_platforms';

INSERT INTO platforms (slug, name) VALUES
    ('ps5',    'PlayStation 5'),
    ('xbox',   'Xbox Series X|S'),
    ('switch', 'Nintendo Switch'),
    ('pc',     'PC (Digital/Physical)');


-- ------------------------------------------------------------
-- 2. categories
--    Game genre / product-type taxonomy.
--    Separated from products so new genres can be added without
--    an ALTER TABLE — just an INSERT.
-- ------------------------------------------------------------
DROP TABLE IF EXISTS categories;
CREATE TABLE categories (
    category_id   TINYINT UNSIGNED    NOT NULL AUTO_INCREMENT,
    slug          VARCHAR(40)         NOT NULL  COMMENT 'Machine key: rpg | action | horror | indie',
    name          VARCHAR(80)         NOT NULL  COMMENT 'Display name: RPG',

    PRIMARY KEY (category_id),
    UNIQUE KEY uq_categories_slug (slug)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Product genre / category taxonomy';

INSERT INTO categories (slug, name) VALUES
    ('rpg',         'RPG'),
    ('action',      'Action'),
    ('horror',      'Horror'),
    ('indie',       'Indie'),
    ('sports',      'Sports'),
    ('racing',      'Racing'),
    ('strategy',    'Strategy'),
    ('simulation',  'Simulation');


-- ============================================================
-- SECTION B — CORE TABLES
-- ============================================================

-- ------------------------------------------------------------
-- 3. users
--    Stores both customers and admins under one table.
--    role ENUM avoids a whole separate admin_users table for
--    this project scale; use separate tables at enterprise scale.
--
--    password: ALWAYS store bcrypt/Argon2 hash via PHP's
--              password_hash($plain, PASSWORD_BCRYPT).
--              VARCHAR(255) is the safe length for any future
--              hash algorithm upgrade.
--    email:    VARCHAR(180) not 255 — MySQL UNIQUE index limit
--              on utf8mb4 is 191 chars per column; 180 is safe.
-- ------------------------------------------------------------
DROP TABLE IF EXISTS users;
CREATE TABLE users (
    user_id       INT UNSIGNED        NOT NULL AUTO_INCREMENT,
    full_name     VARCHAR(100)        NOT NULL,
    email         VARCHAR(180)        NOT NULL,
    password      VARCHAR(255)        NOT NULL  COMMENT 'bcrypt/Argon2 hash — NEVER plain text',
    role          ENUM(
                      'customer',
                      'admin'
                  )                   NOT NULL DEFAULT 'customer',
    is_active     TINYINT(1)          NOT NULL DEFAULT 1  COMMENT '0 = soft-deleted / banned',
    created_at    TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP
                                               ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (user_id),
    UNIQUE KEY uq_users_email (email),
    KEY idx_users_role      (role),
    KEY idx_users_is_active (is_active)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Registered customers and admin accounts';

-- Default admin — change password immediately after first login.
-- Hash below = password_hash('Admin@GameBlitz1', PASSWORD_BCRYPT)
INSERT INTO users (full_name, email, password, role) VALUES
    ('GameBlitz Admin',
     'admin@gameblitz.com',
     '$2y$12$placeholderHashReplaceThisBeforeGoingLive000000000000000',
     'admin');


-- ------------------------------------------------------------
-- 4. products
--    Central catalog table — the primary CRUD target for PHP.
--
--    price / sale_price: DECIMAL(10,2) is mandatory for money.
--      FLOAT/DOUBLE introduce binary rounding errors (e.g.,
--      ₱1,495.00 becomes ₱1,494.9999... in FLOAT).
--    stock: SMALLINT UNSIGNED (0–65,535) — enough for any
--      branch inventory; use INT if aggregating multi-branch.
--    image_url: relative path stored (e.g., assets/img/re9.jpg)
--      so the domain/CDN can change without a data migration.
--    slug: URL-friendly unique identifier for clean PHP URLs
--      (products.php?slug=elden-ring-shadow).
--    badge: ENUM enforces only known values at the DB level,
--      eliminating a whole class of validation bugs.
--    is_featured / is_active: TINYINT(1) = MySQL boolean idiom.
-- ------------------------------------------------------------
DROP TABLE IF EXISTS products;
CREATE TABLE products (
    product_id    INT UNSIGNED        NOT NULL AUTO_INCREMENT,
    category_id   TINYINT UNSIGNED    NOT NULL,
    name          VARCHAR(200)        NOT NULL,
    slug          VARCHAR(220)        NOT NULL  COMMENT 'URL-safe unique key: elden-ring-shadow',
    description   TEXT                          COMMENT 'Long-form product description',
    price         DECIMAL(10, 2)      NOT NULL  COMMENT 'Base price in PHP (Philippine Peso)',
    sale_price    DECIMAL(10, 2)      DEFAULT NULL
                                               COMMENT 'NULL means no active sale',
    image_url     VARCHAR(300)        NOT NULL  COMMENT 'Relative path: assets/img/game.jpg',
    stock         SMALLINT UNSIGNED   NOT NULL DEFAULT 0,
    badge         ENUM(
                      'new',
                      'hot',
                      'sale',
                      'preorder'
                  )                   DEFAULT NULL,
    is_featured   TINYINT(1)          NOT NULL DEFAULT 0,
    is_active     TINYINT(1)          NOT NULL DEFAULT 1,
    created_at    TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP
                                               ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY  (product_id),
    UNIQUE KEY   uq_products_slug       (slug),
    KEY          idx_products_category  (category_id),
    KEY          idx_products_is_active (is_active),
    KEY          idx_products_featured  (is_featured),
    KEY          idx_products_badge     (badge),

    CONSTRAINT fk_products_category
        FOREIGN KEY (category_id)
        REFERENCES  categories (category_id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT   -- prevent orphaned products if category deleted
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Game and accessory catalog — primary CRUD target';

-- Seed data matching the current frontend product cards
INSERT INTO products
    (category_id, name, slug, description, price, sale_price, image_url, stock, badge, is_featured)
VALUES
    -- RPG (category_id = 1)
    (1, 'Elden Ring: Shadow of the Erdtree',
        'elden-ring-shadow-of-the-erdtree',
        'The highly anticipated DLC expansion to FromSoftware\'s open-world masterpiece.',
        3495.00, NULL, 'assets/img/elden_ring_shadow.jpg', 14, 'hot', 1),

    (1, 'Final Fantasy VII Rebirth',
        'final-fantasy-vii-rebirth',
        'The second chapter of the Final Fantasy VII remake trilogy.',
        3295.00, NULL, 'assets/img/ff7rebirth.jpg', 9, NULL, 1),

    (1, 'Cyberpunk 2077: Ultimate Edition',
        'cyberpunk-2077-ultimate-edition',
        'The complete Cyberpunk 2077 experience including the Phantom Liberty expansion.',
        1995.00, 1495.00, 'assets/img/cyberpunk.jpg', 31, 'sale', 0),

    (1, 'Clair Obscur: Expedition 33',
        'clair-obscur-expedition-33',
        'A turn-based RPG set in a breathtaking painterly world.',
        2995.00, NULL, 'assets/img/clair-obscur.jpg', 17, 'new', 1),

    -- Action (category_id = 2)
    (2, 'Marvel\'s Spider-Man 2',
        'marvels-spider-man-2',
        'Swing through an expanded New York City as both Peter Parker and Miles Morales.',
        2995.00, 2495.00, 'assets/img/spider-man-2.jpg', 22, 'sale', 1),

    (2, 'Monster Hunter Wilds',
        'monster-hunter-wilds',
        'The next evolution of the Monster Hunter series with seamless open environments.',
        3495.00, NULL, 'assets/img/monsterhunterwilds.jpg', 0, 'preorder', 1),

    -- Horror (category_id = 3)
    (3, 'Resident Evil Requiem',
        'resident-evil-requiem',
        'The next chapter in the legendary Resident Evil survival horror franchise.',
        3495.00, NULL, 'assets/img/re9.jpg', 0, 'preorder', 1),

    -- Indie (category_id = 4)
    (4, 'Hollow Knight: Silksong',
        'hollow-knight-silksong',
        'Play as Hornet, the princess-protector of Hallownest, in an all-new adventure.',
        1295.00, NULL, 'assets/img/hollowknightsilksong.jpg', 48, 'new', 0);


-- ------------------------------------------------------------
-- 5. product_platforms  (junction / associative table)
--    Resolves the many-to-many relationship between products
--    and platforms. A composite PK on (product_id, platform_id)
--    is both the primary and unique constraint — no surrogate
--    key needed here.
--    ON DELETE CASCADE: removing a product auto-removes its
--    platform mappings — no orphan rows.
-- ------------------------------------------------------------
DROP TABLE IF EXISTS product_platforms;
CREATE TABLE product_platforms (
    product_id    INT UNSIGNED        NOT NULL,
    platform_id   TINYINT UNSIGNED    NOT NULL,

    PRIMARY KEY (product_id, platform_id),

    CONSTRAINT fk_pp_product
        FOREIGN KEY (product_id)
        REFERENCES  products  (product_id)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT fk_pp_platform
        FOREIGN KEY (platform_id)
        REFERENCES  platforms (platform_id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Many-to-many: products ↔ platforms';

-- Map each seeded product to its platforms
-- Elden Ring → PS5(1), Xbox(2), PC(4)
INSERT INTO product_platforms VALUES (1,1),(1,2),(1,4);
-- FF7 Rebirth → PS5(1)
INSERT INTO product_platforms VALUES (2,1);
-- Cyberpunk → PS5(1), Xbox(2), PC(4)
INSERT INTO product_platforms VALUES (3,1),(3,2),(3,4);
-- Clair Obscur → PS5(1), Xbox(2), PC(4)
INSERT INTO product_platforms VALUES (4,1),(4,2),(4,4);
-- Spider-Man 2 → PS5(1)
INSERT INTO product_platforms VALUES (5,1);
-- Monster Hunter Wilds → PS5(1), Xbox(2), PC(4)
INSERT INTO product_platforms VALUES (6,1),(6,2),(6,4);
-- Resident Evil Requiem → PS5(1), Xbox(2), PC(4)
INSERT INTO product_platforms VALUES (7,1),(7,2),(7,4);
-- Hollow Knight Silksong → PS5(1), Switch(3), PC(4)
INSERT INTO product_platforms VALUES (8,1),(8,3),(8,4);


-- ============================================================
-- SECTION C — COMMERCE TABLES
-- ============================================================

-- ------------------------------------------------------------
-- 6. cart_sessions
--    Bridges PHP's session_id() with an optional user_id.
--    Guest carts (user_id IS NULL) are supported out of the
--    box — important for "add to cart before sign-in" UX.
--    On login, the PHP layer updates user_id to merge the
--    guest cart into the authenticated session.
-- ------------------------------------------------------------
DROP TABLE IF EXISTS cart_sessions;
CREATE TABLE cart_sessions (
    session_id    VARCHAR(128)        NOT NULL  COMMENT 'PHP session_id() value',
    user_id       INT UNSIGNED        DEFAULT NULL
                                               COMMENT 'NULL = guest cart',
    created_at    TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP
                                               ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (session_id),
    KEY idx_cart_sessions_user (user_id),

    CONSTRAINT fk_cart_sessions_user
        FOREIGN KEY (user_id)
        REFERENCES  users (user_id)
        ON DELETE SET NULL   -- cart persists even if user deleted
        ON UPDATE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='PHP session ↔ user mapping for shopping carts';


-- ------------------------------------------------------------
-- 7. cart_items
--    Line items in a cart session.
--    unit_price snapshot: critical — captures the price at the
--    moment the item was added, not the current price. Prevents
--    cart total changing mid-session if a sale ends.
--    UNIQUE KEY on (session_id, product_id): duplicate adds
--    trigger an ON DUPLICATE KEY UPDATE quantity += 1 in PHP.
-- ------------------------------------------------------------
DROP TABLE IF EXISTS cart_items;
CREATE TABLE cart_items (
    cart_item_id  INT UNSIGNED        NOT NULL AUTO_INCREMENT,
    session_id    VARCHAR(128)        NOT NULL,
    product_id    INT UNSIGNED        NOT NULL,
    quantity      SMALLINT UNSIGNED   NOT NULL DEFAULT 1
                                               COMMENT 'Minimum 1; enforced in PHP',
    unit_price    DECIMAL(10, 2)      NOT NULL COMMENT 'Price snapshot at time of add',
    added_at      TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (cart_item_id),
    UNIQUE KEY uq_cart_item (session_id, product_id),
    KEY idx_cart_items_product (product_id),

    CONSTRAINT fk_cart_items_session
        FOREIGN KEY (session_id)
        REFERENCES  cart_sessions (session_id)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT fk_cart_items_product
        FOREIGN KEY (product_id)
        REFERENCES  products (product_id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Shopping cart line items; keyed to a cart_session';


-- ------------------------------------------------------------
-- 8. orders
--    Confirmed purchase records. Once a cart is checked out,
--    a row is written here and cart_items are cleared.
--    order_number: human-readable reference (GB-2026-00001)
--    generated in PHP — not AUTO_INCREMENT so it can carry
--    a year prefix and remain meaningful to customers.
--    total_amount: stored as a snapshot so historical records
--    are unaffected by future price changes.
-- ------------------------------------------------------------
DROP TABLE IF EXISTS orders;
CREATE TABLE orders (
    order_id      INT UNSIGNED        NOT NULL AUTO_INCREMENT,
    user_id       INT UNSIGNED        DEFAULT NULL  COMMENT 'NULL = guest order',
    order_number  VARCHAR(30)         NOT NULL      COMMENT 'e.g. GB-2026-00001',
    status        ENUM(
                      'pending',
                      'confirmed',
                      'shipped',
                      'completed',
                      'cancelled'
                  )                   NOT NULL DEFAULT 'pending',
    total_amount  DECIMAL(10, 2)      NOT NULL COMMENT 'Snapshot total at checkout',
    notes         TEXT                          COMMENT 'Customer order notes',
    created_at    TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP
                                               ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY  (order_id),
    UNIQUE KEY   uq_orders_number   (order_number),
    KEY          idx_orders_user    (user_id),
    KEY          idx_orders_status  (status),

    CONSTRAINT fk_orders_user
        FOREIGN KEY (user_id)
        REFERENCES  users (user_id)
        ON DELETE SET NULL   -- preserve order history if user deleted
        ON UPDATE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Confirmed customer purchase records';


-- ------------------------------------------------------------
-- 9. order_items
--    Line items belonging to a confirmed order. Both product_id
--    (FK) and product_name/unit_price (snapshots) are stored:
--    — product_id allows joining to current catalog data.
--    — snapshot columns preserve the historical record if the
--      product is later renamed, repriced, or deleted.
--    ON DELETE SET NULL for product_id: the order history
--    remains intact even if the product is removed from the
--    catalog. PHP checks for NULL to show "[Product removed]".
-- ------------------------------------------------------------
DROP TABLE IF EXISTS order_items;
CREATE TABLE order_items (
    order_item_id INT UNSIGNED        NOT NULL AUTO_INCREMENT,
    order_id      INT UNSIGNED        NOT NULL,
    product_id    INT UNSIGNED        DEFAULT NULL COMMENT 'NULL if product later deleted',
    product_name  VARCHAR(200)        NOT NULL     COMMENT 'Snapshot at time of purchase',
    unit_price    DECIMAL(10, 2)      NOT NULL     COMMENT 'Snapshot at time of purchase',
    quantity      SMALLINT UNSIGNED   NOT NULL DEFAULT 1,
    subtotal      DECIMAL(10, 2)      NOT NULL     COMMENT 'unit_price × quantity; pre-computed',

    PRIMARY KEY (order_item_id),
    KEY idx_order_items_order   (order_id),
    KEY idx_order_items_product (product_id),

    CONSTRAINT fk_order_items_order
        FOREIGN KEY (order_id)
        REFERENCES  orders   (order_id)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT fk_order_items_product
        FOREIGN KEY (product_id)
        REFERENCES  products (product_id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Line items for each confirmed order; prices are snapshots';


-- ============================================================
-- SECTION D — SUPPORT TABLE
-- ============================================================

-- ------------------------------------------------------------
-- 10. inquiries
--     Stores contact form submissions from contact.html.
--     HTML form fields map 1-to-1 to columns (no hidden fields,
--     no massaged data) so the PHP insert is straightforward.
--     category ENUM mirrors the <select> options exactly —
--     server-side validation just checks in_array() against
--     the allowed values; the ENUM acts as a second safety net.
--     status tracks the support ticket lifecycle for an admin
--     panel (future enhancement).
-- ------------------------------------------------------------
DROP TABLE IF EXISTS inquiries;
CREATE TABLE inquiries (
    inquiry_id    INT UNSIGNED        NOT NULL AUTO_INCREMENT,
    user_id       INT UNSIGNED        DEFAULT NULL COMMENT 'NULL = guest / not logged in',
    name          VARCHAR(100)        NOT NULL,
    email         VARCHAR(180)        NOT NULL,
    category      ENUM(
                      'order',
                      'product',
                      'warranty',
                      'preorder',
                      'general'
                  )                   NOT NULL DEFAULT 'general',
    order_number  VARCHAR(30)         DEFAULT NULL COMMENT 'Optional reference e.g. GB-2026-00001',
    subject       VARCHAR(200)        NOT NULL,
    message       TEXT                NOT NULL,
    status        ENUM(
                      'open',
                      'in_progress',
                      'resolved',
                      'closed'
                  )                   NOT NULL DEFAULT 'open',
    created_at    TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP
                                               ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (inquiry_id),
    KEY idx_inquiries_user    (user_id),
    KEY idx_inquiries_status  (status),
    KEY idx_inquiries_email   (email),

    CONSTRAINT fk_inquiries_user
        FOREIGN KEY (user_id)
        REFERENCES  users (user_id)
        ON DELETE SET NULL   -- keep inquiry even if user deleted
        ON UPDATE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Contact form submissions; maps directly to contact.html fields';


-- ------------------------------------------------------------
-- Restore FK checks
-- ------------------------------------------------------------
SET FOREIGN_KEY_CHECKS = 1;


-- ============================================================
-- QUICK-REFERENCE: PHP prepared statement patterns
-- ============================================================
--
--  CREATE (add.php)
--  ────────────────
--  $stmt = $pdo->prepare("
--      INSERT INTO products
--          (category_id, name, slug, price, sale_price, image_url, stock, badge, is_featured)
--      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
--  ");
--  $stmt->execute([$catId, $name, $slug, $price, $salePrice, $img, $stock, $badge, $featured]);
--
--  READ (index.php)
--  ────────────────
--  $stmt = $pdo->prepare("
--      SELECT p.*, c.name AS category_name
--      FROM   products p
--      JOIN   categories c ON c.category_id = p.category_id
--      WHERE  p.is_active = 1
--      ORDER  BY p.created_at DESC
--  ");
--  $stmt->execute();
--  $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
--
--  UPDATE (edit.php)
--  ─────────────────
--  $stmt = $pdo->prepare("
--      UPDATE products
--      SET    name=?, price=?, sale_price=?, stock=?, badge=?, updated_at=NOW()
--      WHERE  product_id=?
--  ");
--  $stmt->execute([$name, $price, $salePrice, $stock, $badge, $id]);
--
--  DELETE (delete.php — POST only, never GET)
--  ──────────────────────────────────────────
--  $stmt = $pdo->prepare("DELETE FROM products WHERE product_id = ?");
--  $stmt->execute([$id]);
--  header('Location: index.php');
--  exit;
--
-- ============================================================
