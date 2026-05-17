const {
  Document, Packer, Paragraph, TextRun, Table, TableRow, TableCell,
  AlignmentType, BorderStyle, WidthType, ShadingType, VerticalAlign,
  PageNumber, PageBreak, Header, Footer, LevelFormat
} = require('./node_modules/docx');
const fs = require('fs');

// ── Colours ───────────────────────────────────────────────────────
const C = {
  purple:      '4C1D95',
  purpleMid:   '7C3AED',
  purpleLight: 'EDE9FE',
  purplePale:  'F5F3FF',
  white:       'FFFFFF',
  dark:        '111827',
  gray:        '6B7280',
  lightGray:   'F9FAFB',
  border:      'D1D5DB',
  accent:      '8B5CF6',
};

// ── Layout constants (US Letter, 1" margins) ──────────────────────
const PAGE_W   = 12240;
const PAGE_H   = 15840;
const MARGIN   = 1440;
const CONTENT  = PAGE_W - MARGIN * 2; // 9360

// ── Tiny helpers ──────────────────────────────────────────────────
const pb  = () => new Paragraph({ children: [new PageBreak()] });
const sp  = (n = 6) => new Paragraph({ spacing: { before: 0, after: 0 }, children: [new TextRun({ text: '', size: n })] });
const hr  = () => new Paragraph({
  spacing: { before: 160, after: 160 },
  border:  { bottom: { style: BorderStyle.SINGLE, size: 4, color: C.accent, space: 1 } },
  children: [new TextRun({ text: '' })],
});

function h1(text) {
  return new Paragraph({
    spacing: { before: 360, after: 120 },
    children: [new TextRun({ text, font: 'Arial', size: 40, bold: true, color: C.purple })],
  });
}
function h2(text) {
  return new Paragraph({
    spacing: { before: 280, after: 80 },
    children: [new TextRun({ text, font: 'Arial', size: 28, bold: true, color: C.purpleMid })],
  });
}
function p(text, extra = {}) {
  return new Paragraph({
    spacing: { before: 60, after: 100 },
    children: [new TextRun({ text, font: 'Arial', size: 22, color: C.dark, ...extra })],
  });
}
function mono(text) {
  return new Paragraph({
    spacing: { before: 0, after: 0 },
    children: [new TextRun({ text, font: 'Courier New', size: 17, color: C.dark })],
  });
}
function bul(text) {
  return new Paragraph({
    numbering: { reference: 'bullets', level: 0 },
    spacing:   { before: 40, after: 40 },
    children:  [new TextRun({ text, font: 'Arial', size: 22, color: C.dark })],
  });
}

// ── Table helpers ─────────────────────────────────────────────────
const TB = { style: BorderStyle.SINGLE, size: 1, color: C.border };
const BORDS = { top: TB, bottom: TB, left: TB, right: TB };
const MARG  = { top: 80, bottom: 80, left: 120, right: 120 };

function hCell(text, w) {
  return new TableCell({
    width: { size: w, type: WidthType.DXA }, borders: BORDS, margins: MARG,
    shading: { fill: C.purple, type: ShadingType.CLEAR },
    verticalAlign: VerticalAlign.CENTER,
    children: [new Paragraph({ children: [new TextRun({ text, font: 'Arial', size: 20, bold: true, color: C.white })] })],
  });
}
function dCell(text, w, shade = false, mono2 = false) {
  return new TableCell({
    width: { size: w, type: WidthType.DXA }, borders: BORDS, margins: MARG,
    shading: { fill: shade ? C.purplePale : C.white, type: ShadingType.CLEAR },
    children: [new Paragraph({ children: [new TextRun({ text, font: mono2 ? 'Courier New' : 'Arial', size: 20, color: C.dark })] })],
  });
}

// 5-column column-detail table  [1600,1400,1200,2760,2400] = 9360
function colTable(rows) {
  const W = [1600, 1400, 1200, 2760, 2400];
  const H = ['Column Name', 'Data Type', 'Constraint', 'Purpose', 'Example Value'];
  return new Table({
    width: { size: CONTENT, type: WidthType.DXA }, columnWidths: W,
    rows: [
      new TableRow({ tableHeader: true, children: H.map((h, i) => hCell(h, W[i])) }),
      ...rows.map((r, ri) => new TableRow({
        children: r.map((c, ci) => dCell(c, W[ci], ri % 2 === 0, ci !== 3)),
      })),
    ],
  });
}

// Generic example-row table
function exTable(headers, rows, widths) {
  return new Table({
    width: { size: CONTENT, type: WidthType.DXA }, columnWidths: widths,
    rows: [
      new TableRow({ tableHeader: true, children: headers.map((h, i) => hCell(h, widths[i])) }),
      ...rows.map((r, ri) => new TableRow({
        children: r.map((c, ci) => dCell(c, widths[ci], ri % 2 === 0, true)),
      })),
    ],
  });
}

// ════════════════════════════════════════════════════════════════
// SECTION 1 — What is an ERD?
// ════════════════════════════════════════════════════════════════
const sec1 = [
  h1('Section 1 — What is an ERD?'), hr(),
  p('An Entity Relationship Diagram (ERD) is a blueprint of a database. Just like an architect draws floor plans before constructing a building, a developer draws an ERD before writing any SQL. It shows what data the system needs to store, how that data is organised, and how different pieces of data relate to each other.'),
  sp(),
  p('Think of a physical game store. There are products on the shelves, customers at the counter, receipts at the register, and a support desk for complaints. Each of these is an entity — a "thing" the store needs to track. In a database, each entity becomes a table. The details about each entity (the price of a product, the name of a customer) become columns inside that table. When two entities are connected — for example, a customer buys a product — that connection is a relationship, shown as a linking line in the ERD.'),
  sp(),
  p('Two concepts underpin every ERD:'),
  bul('Primary Key (PK) — A column that uniquely identifies every row in a table. Think of it as a serial number on a receipt. No two rows can share the same PK. Example: every product has its own product_id, and no two products share it.'),
  bul('Foreign Key (FK) — A column in one table that points to a Primary Key in another table. It is how tables talk to each other. Example: in the orders table, user_id is a Foreign Key pointing to the users table — telling us exactly which customer placed the order.'),
  sp(),
  p('The GameBlitz database has 10 tables across four groups: Lookup (platforms, categories), Core (users, products, product_platforms), Commerce (cart_sessions, cart_items, orders, order_items), and Support (inquiries). The sections that follow explain each table, every column, and all the relationships between them.'),
  pb(),
];

// ════════════════════════════════════════════════════════════════
// SECTION 2 — ERD Diagram
// ════════════════════════════════════════════════════════════════
const erdLines = [
  '  SECTION A: LOOKUP          SECTION B: CORE TABLES',
  '  ================          ======================',
  '',
  '  +----------------+         +-------------------------------------+',
  '  |  categories    |  1   N  |           products                  |',
  '  +----------------+---------+-------------------------------------+',
  '  | PK category_id |         | PK product_id                       |',
  '  |    slug        |         | FK category_id -> categories        |',
  '  |    name        |         |    name                             |',
  '  +----------------+         |    slug  (URL key)                  |',
  '                             |    description                      |',
  '  +----------------+         |    price        DECIMAL(10,2)       |',
  '  |   platforms    | M     N |    sale_price   DECIMAL(10,2) NULL  |',
  '  +----------------+         |    image_url                        |',
  '  | PK platform_id |--via--> |    stock        SMALLINT UNSIGNED   |',
  '  |    slug        | junc.   |    badge        ENUM                |',
  '  |    name        | table   |    is_featured  TINYINT(1) bool     |',
  '  +----------------+         |    is_active    TINYINT(1) bool     |',
  '                             |    created_at / updated_at          |',
  '  +---------------------+   +-------------------------------------+',
  '  | product_platforms   |',
  '  | (Junction M:N)      |',
  '  +---------------------+',
  '  | PK product_id  (FK) |',
  '  | PK platform_id (FK) |',
  '  +---------------------+',
  '',
  '  SECTION C: COMMERCE TABLES',
  '  ==========================',
  '',
  '  +-----------------------------+     +---------------------------+',
  '  |           users             |     |      cart_sessions        |',
  '  +-----------------------------+  1  +---------------------------+',
  '  | PK user_id                  |---->| FK user_id  (NULL=guest)  |',
  '  |    full_name                |     | PK session_id  VARCHAR    |',
  '  |    email     UNIQUE         |     |    created_at             |',
  '  |    password  VARCHAR(255)   |     |    updated_at             |',
  '  |    role      ENUM           |     +---------------------------+',
  '  |    is_active TINYINT(1)     |                  | 1:N',
  '  |    created_at / updated_at  |     +---------------------------+',
  '  +-----------------------------+     |       cart_items          |',
  '              |  1:N                  +---------------------------+',
  '  +-----------------------------+     | PK cart_item_id           |',
  '  |           orders            |     | FK session_id             |',
  '  +-----------------------------+     | FK product_id             |',
  '  | PK order_id                 |     |    quantity               |',
  '  | FK user_id  (NULL=guest)    |     |    unit_price  SNAPSHOT   |',
  '  |    order_number  UNIQUE     |     |    added_at               |',
  '  |    status   ENUM            |     +---------------------------+',
  '  |    total_amount  SNAPSHOT   |',
  '  |    notes                    |   SECTION D: SUPPORT',
  '  |    created_at / updated_at  |   ====================',
  '  +-----------------------------+',
  '              | 1:N               +---------------------------+',
  '  +-----------------------------+ |       inquiries           |',
  '  |        order_items          | +---------------------------+',
  '  +-----------------------------+ | PK inquiry_id             |',
  '  | PK order_item_id            | | FK user_id (NULL=guest)   |',
  '  | FK order_id  -> orders      | |    name / email           |',
  '  | FK product_id (NULL if del) | |    category  ENUM         |',
  '  |    product_name  SNAPSHOT   | |    order_number  optional |',
  '  |    unit_price    SNAPSHOT   | |    subject / message      |',
  '  |    quantity                 | |    status  ENUM           |',
  '  |    subtotal  pre-computed   | |    created_at             |',
  '  +-----------------------------+ +---------------------------+',
  '',
  '  KEY:  PK=Primary Key  FK=Foreign Key  1:N=One-to-Many  M:N=Many-to-Many',
  '        SNAPSHOT = value copied at time of action, never changes afterwards',
  '        NULL=guest = the field is optional; NULL means not logged in',
];

const sec2 = [
  h1('Section 2 — ERD Diagram'), hr(),
  p('Each box is a table. Lines connecting them show relationships. "1" and "N" show cardinality: one row in the 1-side can link to many rows on the N-side. "M:N" means many-to-many, which requires a junction table in between.'),
  sp(8),
  ...erdLines.map(l => mono(l)),
  pb(),
];

// ════════════════════════════════════════════════════════════════
// SECTION 3 — Table Reference (10 tables)
// ════════════════════════════════════════════════════════════════

// Helper: builds one table entry block
function tableBlock(name, purpose, analogy, colRows, relText, exHeaders, exRows, exWidths) {
  return [
    h2(name),
    p('Purpose: ' + purpose),
    sp(4),
    p('Analogy: ' + analogy, { italics: true }),
    sp(6),
    p('Column Reference:', { bold: true }),
    sp(4),
    colTable(colRows),
    sp(8),
    p('Relationships: ' + relText),
    sp(6),
    p('Example Rows:', { bold: true }),
    sp(4),
    exTable(exHeaders, exRows, exWidths),
    sp(16),
  ];
}

// --- Table 1: platforms ---
const t1 = tableBlock(
  'Table 1 — platforms',
  'Stores the list of gaming platforms GameBlitz sells for: PS5, Xbox, Switch, and PC.',
  'The genre/platform sticker labels in a physical store. Every game has stickers showing which consoles it runs on. This table defines those labels.',
  [
    ['platform_id', 'TINYINT UNSIGNED', 'PK, AUTO_INCREMENT', 'Unique number for each platform. Used internally by product_platforms to link games to consoles.', '1'],
    ['slug',        'VARCHAR(20)',       'UNIQUE, NOT NULL',   'Short lowercase code matching the HTML data-platform attribute (e.g., ps5). Keeps database and front end in sync.', 'ps5'],
    ['name',        'VARCHAR(60)',       'NOT NULL',           'Full display name shown to users on filter dropdowns and category tiles.', 'PlayStation 5'],
  ],
  'platforms does not link directly to products. Both connect through product_platforms (Table 5) — a junction table that handles the many-to-many relationship: one game runs on many platforms, and one platform hosts many games.',
  ['platform_id', 'slug', 'name'],
  [
    ['1', 'ps5',    'PlayStation 5'],
    ['2', 'xbox',   'Xbox Series X|S'],
    ['3', 'switch', 'Nintendo Switch'],
    ['4', 'pc',     'PC (Digital/Physical)'],
  ],
  [1800, 2200, 5360], // 9360
);

// --- Table 2: categories ---
const t2 = tableBlock(
  'Table 2 — categories',
  'Stores the genre list (RPG, Action, Horror, Indie, etc.) used to classify every product.',
  'The genre aisles of a game store — the RPG aisle, the Action aisle, the Horror aisle. This table defines those aisles.',
  [
    ['category_id', 'TINYINT UNSIGNED', 'PK, AUTO_INCREMENT', 'Unique number for each genre. Stored as a foreign key in every product row.', '1'],
    ['slug',        'VARCHAR(40)',       'UNIQUE, NOT NULL',   'Lowercase code used in PHP URLs and filter logic (e.g., ?category=rpg). Must match the HTML select option values.', 'rpg'],
    ['name',        'VARCHAR(80)',       'NOT NULL',           'Genre name displayed in dropdowns and the homepage category tiles.', 'RPG'],
  ],
  'One-to-many with products: one category (RPG) contains many products (Elden Ring, FF7, Cyberpunk). The products table holds category_id as a Foreign Key. ON DELETE RESTRICT means you cannot delete a category that still has products assigned to it.',
  ['category_id', 'slug', 'name'],
  [
    ['1', 'rpg',    'RPG'],
    ['2', 'action', 'Action'],
    ['3', 'horror', 'Horror'],
    ['4', 'indie',  'Indie'],
  ],
  [1800, 2200, 5360], // 9360
);

// --- Table 3: users ---
const t3 = tableBlock(
  'Table 3 — users',
  'Stores all registered accounts — customers and admins — in one table, separated by a role column.',
  'A store member card system. Every registered customer has a card with their name and contact. Admins carry a special card that unlocks the back-office management pages.',
  [
    ['user_id',    'INT UNSIGNED',              'PK, AUTO_INCREMENT',         'Unique ID for every account. PHP stores this in $_SESSION[user_id] after login.', '2'],
    ['full_name',  'VARCHAR(100)',              'NOT NULL',                   'The account holder\'s full name, entered at registration. Shown in greetings and printed on order records.', 'Juan Dela Cruz'],
    ['email',      'VARCHAR(180)',              'UNIQUE, NOT NULL',           'Login credential and support contact. VARCHAR(180) because MySQL\'s UNIQUE index on utf8mb4 has a 191-character limit per column. 180 is safely under it.', 'juan@email.com'],
    ['password',   'VARCHAR(255)',              'NOT NULL',                   'A bcrypt hash produced by PHP password_hash() — NEVER the plain-text password. VARCHAR(255) future-proofs for longer hash algorithms.', '$2y$12$abc...'],
    ['role',       'ENUM(customer, admin)',     'NOT NULL, DEFAULT customer', 'Controls page access. admin can manage products. customer can only shop. ENUM prevents any value outside this list from being stored.', 'customer'],
    ['is_active',  'TINYINT(1)',                'NOT NULL, DEFAULT 1',        'Boolean flag. 1 = active, 0 = banned or soft-deleted. Lets admins deactivate accounts without losing order history.', '1'],
    ['created_at', 'TIMESTAMP',                'DEFAULT CURRENT_TIMESTAMP',  'Set automatically by MySQL when the row is inserted. No PHP code required.', '2026-05-17 09:00:00'],
    ['updated_at', 'TIMESTAMP',                'AUTO-UPDATES ON CHANGE',     'MySQL updates this automatically whenever any column in the row changes. Tracks last profile edit.', '2026-05-17 14:30:00'],
  ],
  'Central hub — connects to four tables. orders.user_id links a purchase to the buyer. cart_sessions.user_id links an active cart to a logged-in user. inquiries.user_id links a support ticket to its submitter. All three use NULL to allow guest activity without an account.',
  ['user_id', 'full_name', 'email', 'role', 'is_active'],
  [
    ['1', 'GameBlitz Admin', 'admin@gameblitz.com', 'admin',    '1'],
    ['2', 'Juan Dela Cruz',  'juan@email.com',      'customer', '1'],
    ['3', 'Maria Santos',    'maria@email.com',     'customer', '0'],
  ],
  [1100, 2400, 2800, 1560, 1500], // 9360
);

// --- Table 4: products ---
const t4 = tableBlock(
  'Table 4 — products',
  'The main product catalog. Every game and accessory GameBlitz sells has one row here with all the details needed to display, filter, price, and sell it.',
  'The store\'s inventory ledger. Every item on the shelf has a record: name, price, stock count, genre, and whether it is on sale. This is the primary CRUD target — index.php / add.php / edit.php / delete.php all operate on this table.',
  [
    ['product_id',  'INT UNSIGNED',            'PK, AUTO_INCREMENT',          'Unique ID referenced by cart_items, order_items, and product_platforms.', '5'],
    ['category_id', 'TINYINT UNSIGNED',        'FK -> categories, NOT NULL',  'Links to a genres row. ON DELETE RESTRICT blocks deleting a category that still has products.', '2'],
    ['name',        'VARCHAR(200)',             'NOT NULL',                    'Full display name shown on product cards, the cart, and receipts.', "Marvel's Spider-Man 2"],
    ['slug',        'VARCHAR(220)',             'UNIQUE, NOT NULL',            'URL-safe key used in PHP URLs (products.php?slug=marvels-spider-man-2). Slightly longer than name to allow for extra hyphens.', 'marvels-spider-man-2'],
    ['description', 'TEXT',                    'NULL allowed',                'Long-form game description. TEXT has no fixed length cap unlike VARCHAR.', 'Swing through an expanded New York City...'],
    ['price',       'DECIMAL(10,2)',            'NOT NULL',                    'Base price in Philippine Peso. DECIMAL stores money exactly. FLOAT would cause rounding errors on totals.', '2995.00'],
    ['sale_price',  'DECIMAL(10,2)',            'NULL allowed',                'Discounted price. NULL = no active sale. PHP shows sale_price and crosses out price when this is not NULL.', '2495.00'],
    ['image_url',   'VARCHAR(300)',             'NOT NULL',                    'Relative path to the cover image (assets/img/...). Relative so the domain or CDN can change without a data migration.', 'assets/img/spider-man-2.jpg'],
    ['stock',       'SMALLINT UNSIGNED',        'NOT NULL, DEFAULT 0',         '0 triggers a Pre-Order or Out of Stock label in PHP. SMALLINT UNSIGNED handles 0-65,535 units.', '22'],
    ['badge',       'ENUM(new,hot,sale,preorder)', 'NULL allowed',            'Ribbon shown on the product card. ENUM ensures only these four values are ever stored.', 'sale'],
    ['is_featured', 'TINYINT(1)',               'NOT NULL, DEFAULT 0',         '1 = shown in the Today\'s Deals strip on the homepage.', '1'],
    ['is_active',   'TINYINT(1)',               'NOT NULL, DEFAULT 1',         '0 = soft-deleted. Hides the product from the shop without destroying order history that references it.', '1'],
    ['created_at',  'TIMESTAMP',                'DEFAULT CURRENT_TIMESTAMP',   'When the product was added to the catalog. Used for sorting Newest First.', '2026-01-15 10:00:00'],
    ['updated_at',  'TIMESTAMP',                'AUTO-UPDATES ON CHANGE',      'Last time any detail changed. Used by PHP to invalidate cached product pages.', '2026-05-01 08:30:00'],
  ],
  'Most connected table in the schema. Receives a FK from categories. Links to platforms via product_platforms. Referenced by cart_items and order_items. When a product is soft-deleted (is_active=0), its FK in order_items is SET NULL to preserve purchase history.',
  ['product_id', 'category_id', 'name', 'price', 'sale_price', 'stock', 'badge'],
  [
    ['1', '1', 'Elden Ring: Shadow of the Erdtree', '3495.00', 'NULL',    '14', 'hot'],
    ['5', '2', "Marvel's Spider-Man 2",              '2995.00', '2495.00', '22', 'sale'],
    ['6', '2', 'Monster Hunter Wilds',               '3495.00', 'NULL',    '0',  'preorder'],
    ['8', '4', 'Hollow Knight: Silksong',            '1295.00', 'NULL',    '48', 'new'],
  ],
  [1100, 1300, 2800, 1100, 1200, 800, 1060], // 9360
);

// --- Table 5: product_platforms ---
const t5 = tableBlock(
  'Table 5 — product_platforms (Junction Table)',
  'Resolves the many-to-many relationship between products and platforms. A game on three platforms creates three rows here, one per platform.',
  'A two-column spreadsheet: "Game" in column A, "Platform" in column B. Every valid game-platform combination gets its own row. That spreadsheet is this table.',
  [
    ['product_id',  'INT UNSIGNED',      'PK (part 1), FK -> products',  'Points to a product. Part of the composite primary key — prevents duplicate pairs. ON DELETE CASCADE removes these rows if the product is deleted.', '1'],
    ['platform_id', 'TINYINT UNSIGNED',  'PK (part 2), FK -> platforms', 'Points to a platform. Together with product_id, uniquely identifies one product-platform mapping.', '2'],
  ],
  'Sits between products and platforms. Both columns are Foreign Keys AND together form the Primary Key. No surrogate auto-increment ID is needed here because the combination itself is unique. Cascade deletes keep this table clean automatically.',
  ['product_id', 'platform_id', 'What it means'],
  [
    ['1', '1', 'Elden Ring is available on PS5'],
    ['1', '2', 'Elden Ring is available on Xbox'],
    ['1', '4', 'Elden Ring is available on PC'],
    ['2', '1', 'FF7 Rebirth is on PS5 only'],
    ['8', '3', 'Hollow Knight: Silksong is on Nintendo Switch'],
  ],
  [1600, 1760, 6000], // 9360
);

// --- Table 6: cart_sessions ---
const t6 = tableBlock(
  'Table 6 — cart_sessions',
  'Connects a PHP browser session (session_id) to an optional user account, enabling both guest and logged-in shopping carts.',
  'The basket-tag system at a physical store. When you walk in, you grab a basket with a unique tag. If you are a member, staff link your card to that tag. If you are browsing anonymously, the basket still tracks what you pick up.',
  [
    ['session_id', 'VARCHAR(128)', 'PK, NOT NULL',              'The PHP session ID string from session_id(). This is the bridge between the PHP code and the database cart.', 'abc1234def5678ghi...'],
    ['user_id',    'INT UNSIGNED', 'FK -> users, NULL allowed', 'user_id if logged in. NULL if guest. ON DELETE SET NULL keeps the session alive if the account is deleted.', '2 or NULL'],
    ['created_at', 'TIMESTAMP',    'DEFAULT CURRENT_TIMESTAMP', 'When the cart session was first created. Used by cleanup scripts to expire stale guest carts.', '2026-05-17 14:00:00'],
    ['updated_at', 'TIMESTAMP',    'AUTO-UPDATES ON CHANGE',    'Updates whenever the cart changes. Used to detect inactive sessions.', '2026-05-17 14:45:00'],
  ],
  'Bridge table between users and cart_items. Has an optional FK to users (NULL = guest). Is referenced by cart_items via session_id. When a session is deleted, all its cart_items cascade-delete automatically.',
  ['session_id', 'user_id', 'created_at'],
  [
    ['abc123def456ghi...', '2',    '2026-05-17 14:00:00'],
    ['xyz789jkl012mno...', 'NULL', '2026-05-17 15:30:00'],
  ],
  [4200, 1800, 3360], // 9360
);

// --- Table 7: cart_items ---
const t7 = tableBlock(
  'Table 7 — cart_items',
  'Stores the individual items inside a shopping cart — one row per unique product per session.',
  'The contents of the shopping basket. Each item in the basket has its own slot: which product, how many, and — critically — the price at the moment you placed it in the basket.',
  [
    ['cart_item_id', 'INT UNSIGNED',      'PK, AUTO_INCREMENT',           'Unique ID for each cart line item across all sessions.', '1'],
    ['session_id',   'VARCHAR(128)',      'FK -> cart_sessions, NOT NULL', 'Links this item to a cart. CASCADE delete: removing the session removes all its items.', 'abc123def456...'],
    ['product_id',   'INT UNSIGNED',      'FK -> products, NOT NULL',      'Which product was added to this cart.', '5'],
    ['quantity',     'SMALLINT UNSIGNED', 'NOT NULL, DEFAULT 1',           'Units of this product in the cart. PHP uses INSERT ... ON DUPLICATE KEY UPDATE to increment this rather than creating a second row.', '2'],
    ['unit_price',   'DECIMAL(10,2)',     'NOT NULL',                      'PRICE SNAPSHOT — locked in at the moment of adding. If a sale ends one hour later, the cart total stays the same. This is critical for fairness and legal compliance.', '2495.00'],
    ['added_at',     'TIMESTAMP',         'DEFAULT CURRENT_TIMESTAMP',     'When this item was added. Useful for showing "added 5 minutes ago" or expiring old carts.', '2026-05-17 14:31:00'],
  ],
  'Linked to cart_sessions (parent) and products (what was added). A UNIQUE KEY on (session_id, product_id) ensures the same product cannot appear twice in one cart — PHP increments quantity instead.',
  ['cart_item_id', 'session_id', 'product_id', 'quantity', 'unit_price'],
  [
    ['1', 'abc123...', '5', '1', '2495.00'],
    ['2', 'abc123...', '8', '2', '2995.00'],
    ['3', 'xyz789...', '1', '1', '3495.00'],
  ],
  [1400, 2600, 1400, 1400, 2560], // 9360
);

// --- Table 8: orders ---
const t8 = tableBlock(
  'Table 8 — orders',
  'Records every confirmed customer purchase. One row = one completed checkout.',
  'The receipts folder at the store register. Every completed sale is filed here: who bought, how much in total, and the current status of their shipment.',
  [
    ['order_id',      'INT UNSIGNED',                         'PK, AUTO_INCREMENT',         'Unique ID for each confirmed purchase.', '1'],
    ['user_id',       'INT UNSIGNED',                         'FK -> users, NULL allowed',   'The buyer\'s account. NULL = guest checkout. SET NULL on user delete preserves the order permanently.', '2 or NULL'],
    ['order_number',  'VARCHAR(30)',                           'UNIQUE, NOT NULL',            'Human-readable reference (GB-2026-00001). Generated by PHP — not AUTO_INCREMENT — so it can carry a year prefix meaningful to customers.', 'GB-2026-00001'],
    ['status',        'ENUM(pending,confirmed,shipped,completed,cancelled)', 'NOT NULL, DEFAULT pending', 'Tracks the lifecycle from placement to delivery. ENUM prevents invalid statuses like "on-the-way".', 'confirmed'],
    ['total_amount',  'DECIMAL(10,2)',                         'NOT NULL',                    'PRICE SNAPSHOT — grand total at checkout time. Unaffected by future price changes on any product.', '8485.00'],
    ['notes',         'TEXT',                                  'NULL allowed',                'Optional customer message (e.g., "Please gift wrap"). TEXT imposes no length cap.', 'Please gift wrap'],
    ['created_at',    'TIMESTAMP',                             'DEFAULT CURRENT_TIMESTAMP',   'Timestamp of when the order was placed.', '2026-05-17 16:00:00'],
    ['updated_at',    'TIMESTAMP',                             'AUTO-UPDATES ON CHANGE',      'Last time the status was changed by an admin (e.g., marked shipped).', '2026-05-18 10:00:00'],
  ],
  'Has an optional FK to users (NULL = guest). Is the parent of order_items — one order has many line items. The order_number is deliberately a VARCHAR, not AUTO_INCREMENT, so PHP can format it as GB-2026-NNNNN.',
  ['order_id', 'user_id', 'order_number', 'status', 'total_amount'],
  [
    ['1', '2',    'GB-2026-00001', 'confirmed', '8485.00'],
    ['2', 'NULL', 'GB-2026-00002', 'pending',   '3495.00'],
    ['3', '2',    'GB-2026-00003', 'shipped',   '1295.00'],
  ],
  [1100, 1100, 2600, 2000, 2560], // 9360
);

// --- Table 9: order_items ---
const t9 = tableBlock(
  'Table 9 — order_items',
  'The itemised line items of each confirmed order — one row per product purchased, with snapshot values to make the record permanent and tamper-proof.',
  'The itemised list on a till receipt. It shows exactly what you bought, at what price, and how many — and that receipt never changes even if the store raises prices the next day.',
  [
    ['order_item_id', 'INT UNSIGNED',      'PK, AUTO_INCREMENT',            'Unique ID for each line item across all orders.', '1'],
    ['order_id',      'INT UNSIGNED',      'FK -> orders, NOT NULL',        'Links to the parent order. CASCADE delete: removing the order removes all its line items.', '1'],
    ['product_id',    'INT UNSIGNED',      'FK -> products, NULL allowed',  'Points to the product. NULL if the product was deleted from the catalog after this order was placed. SET NULL preserves the line item.', '5 or NULL'],
    ['product_name',  'VARCHAR(200)',      'NOT NULL',                      'NAME SNAPSHOT — the product name at purchase time. Survives product renames or deletions. The receipt always shows what was actually bought.', "Marvel's Spider-Man 2"],
    ['unit_price',    'DECIMAL(10,2)',     'NOT NULL',                      'PRICE SNAPSHOT — the exact price paid per unit at checkout. Never changes even if the product is repriced.', '2495.00'],
    ['quantity',      'SMALLINT UNSIGNED', 'NOT NULL, DEFAULT 1',           'How many units were purchased.', '1'],
    ['subtotal',      'DECIMAL(10,2)',     'NOT NULL',                      'Pre-computed: unit_price x quantity. Stored so PHP does not need to recalculate on every order view.', '2495.00'],
  ],
  'Has two Foreign Keys. order_id (required, CASCADE) links to the parent orders row. product_id (optional, SET NULL) links to products — if the product is later deleted, this becomes NULL but product_name and unit_price snapshots keep the record readable.',
  ['order_item_id', 'order_id', 'product_id', 'product_name', 'unit_price', 'qty', 'subtotal'],
  [
    ['1', '1', '5',    "Marvel's Spider-Man 2",       '2495.00', '1', '2495.00'],
    ['2', '1', '8',    'Clair Obscur: Expedition 33', '2995.00', '2', '5990.00'],
    ['3', '2', 'NULL', '[Product removed]',           '3495.00', '1', '3495.00'],
  ],
  [1200, 1100, 1100, 2460, 1200, 600, 1700], // 9360
);

// --- Table 10: inquiries ---
const t10 = tableBlock(
  'Table 10 — inquiries',
  'Stores every submission from the Contact Us page, acting as a lightweight support ticket system.',
  'The customer complaint log at the store service desk. Every message is recorded with who sent it, their concern, and a status tag showing whether it has been handled.',
  [
    ['inquiry_id',   'INT UNSIGNED',                              'PK, AUTO_INCREMENT',        'Unique ID for each support ticket.', '1'],
    ['user_id',      'INT UNSIGNED',                              'FK -> users, NULL allowed',  'Links to the submitter\'s account if logged in. NULL = guest. SET NULL on delete preserves the ticket even if the user closes their account.', '2 or NULL'],
    ['name',         'VARCHAR(100)',                              'NOT NULL',                   'Full name entered in the contact form. Stored separately from users.full_name in case the submitter uses a different name.', 'Juan Dela Cruz'],
    ['email',        'VARCHAR(180)',                              'NOT NULL',                   'Reply-to address for the admin response. Stored explicitly even for logged-in users in case they provide a different contact email.', 'juan@email.com'],
    ['category',     'ENUM(order,product,warranty,preorder,general)', 'NOT NULL, DEFAULT general', 'Type of inquiry selected from the form dropdown. Matches the HTML select option values exactly, making PHP validation trivial.', 'order'],
    ['order_number', 'VARCHAR(30)',                               'NULL allowed',               'Optional order reference (e.g., GB-2026-00001). NULL for questions unrelated to a specific order.', 'GB-2026-00001'],
    ['subject',      'VARCHAR(200)',                              'NOT NULL',                   'Short summary of the issue entered by the customer in the Subject field.', 'Where is my order?'],
    ['message',      'TEXT',                                      'NOT NULL',                   'Full message body. TEXT allows long explanations with no character limit.', 'I placed order GB-2026-00001 three days ago...'],
    ['status',       'ENUM(open,in_progress,resolved,closed)',    'NOT NULL, DEFAULT open',     'Admin-managed lifecycle state. Allows filtering the support queue by unresolved tickets.', 'open'],
    ['created_at',   'TIMESTAMP',                                 'DEFAULT CURRENT_TIMESTAMP',  'When the inquiry was submitted.', '2026-05-17 11:00:00'],
    ['updated_at',   'TIMESTAMP',                                 'AUTO-UPDATES ON CHANGE',     'When the ticket status was last changed by an admin.', '2026-05-18 09:00:00'],
  ],
  'Has an optional FK to users. The category ENUM mirrors the HTML select options in contact.html exactly — keeping the form, PHP validation, and database perfectly aligned. The status ENUM lets admins build a simple ticket queue.',
  ['inquiry_id', 'user_id', 'name', 'category', 'subject', 'status'],
  [
    ['1', '2',    'Juan Dela Cruz', 'order',   'Where is my order?',   'in_progress'],
    ['2', 'NULL', 'Maria Santos',   'general', 'PS5 stock question',   'open'],
    ['3', '2',    'Juan Dela Cruz', 'warranty','Controller defective', 'resolved'],
  ],
  [1100, 1100, 2000, 1400, 2360, 1400], // 9360
);

const sec3 = [
  h1('Section 3 — Table-by-Table Reference'), hr(),
  p('Each table entry below contains: purpose, a real-world analogy, a full column guide, relationship explanation, and example rows showing exactly what data looks like inside the database.'),
  pb(),
  ...t1,  ...t2, pb(),
  ...t3, pb(),
  ...t4, pb(),
  ...t5,
  ...t6,
  ...t7, pb(),
  ...t8,
  ...t9, pb(),
  ...t10, pb(),
];

// ════════════════════════════════════════════════════════════════
// SECTION 4 — Relationship Deep Dive
// ════════════════════════════════════════════════════════════════
function relTable(rows) {
  const W = [2600, 3400, 3360]; // 9360
  return new Table({
    width: { size: CONTENT, type: WidthType.DXA }, columnWidths: W,
    rows: [
      new TableRow({ tableHeader: true, children: ['Relationship', 'Plain-English Rule', 'Linked Example'].map((h, i) => hCell(h, W[i])) }),
      ...rows.map((r, ri) => new TableRow({
        children: r.map((c, ci) => dCell(c, W[ci], ri % 2 === 0, ci === 0)),
      })),
    ],
  });
}

const sec4 = [
  h1('Section 4 — Relationship Deep Dive'), hr(),
  p('A relationship exists whenever one table\'s data depends on data in another table. The Foreign Key is the mechanism that enforces the connection. When the parent row is deleted, MySQL follows one of three rules on the child rows:'),
  bul('CASCADE — delete the children automatically. Used when children have no meaning without the parent (cart items without a cart, order items without an order).'),
  bul('SET NULL — set the FK column to NULL. Used when the child record is still valuable even without the parent (order history after a user deletes their account).'),
  bul('RESTRICT — block the parent delete entirely. Used when deleting the parent would cause orphan data that must be cleaned up first (a category that still has products).'),
  sp(8),
  relTable([
    [
      'products -> categories\nMany-to-One',
      'Many products belong to one category. You cannot delete a category that still has products (RESTRICT). Changing a category\'s ID cascades to all its products (CASCADE UPDATE).',
      'products rows 1, 3, 4 all have category_id=1 (RPG). categories row 1 is the single RPG record they all point to.',
    ],
    [
      'products <-> platforms\nMany-to-Many via product_platforms',
      'One game runs on many platforms. One platform hosts many games. This cannot be expressed with a single FK, so product_platforms holds one row per valid combination.',
      'product_platforms has rows (1,1),(1,2),(1,4) for Elden Ring on PS5, Xbox, and PC. Only one row (2,1) for FF7 Rebirth which is PS5 exclusive.',
    ],
    [
      'cart_sessions -> users\nMany-to-One, NULL allowed',
      'A session belongs to one user. NULL means a guest cart. If the user deletes their account the session stays alive (SET NULL) so the guest experience is unaffected.',
      'Session abc123 has user_id=2 (Juan browsing while logged in). Session xyz789 has user_id=NULL (an anonymous visitor).',
    ],
    [
      'cart_items -> cart_sessions\nMany-to-One',
      'One session holds many items. Items cannot exist without a session. If the session is cleared (e.g., after checkout or expiry), all its items cascade-delete.',
      'cart_items rows 1 and 2 both have session_id=abc123 — two different games in Juan\'s cart at the same time.',
    ],
    [
      'cart_items -> products\nMany-to-One',
      'Each cart item refers to exactly one product. The unit_price snapshot is captured at the moment of adding — if the sale ends, the cart total does not change.',
      'cart_items row 1: product_id=5 (Spider-Man 2 on sale), unit_price=2495.00. Even if the sale ends, this value stays 2495.00.',
    ],
    [
      'orders -> users\nMany-to-One, NULL allowed',
      'One user can place many orders over time. NULL allows guest checkout. SET NULL on user delete preserves all order records for business accounting.',
      'orders rows 1 and 3 both have user_id=2 (Juan has ordered twice). Row 2 has user_id=NULL (someone checked out as a guest).',
    ],
    [
      'order_items -> orders\nMany-to-One',
      'One order has many line items. A line item cannot exist without a parent order. Cascade delete keeps the database clean if an order is cancelled and removed.',
      'order_items rows 1 and 2 both have order_id=1. Juan\'s first order had two games: Spider-Man 2 and Clair Obscur.',
    ],
    [
      'order_items -> products\nMany-to-One, NULL allowed',
      'Each line item records which product was bought. SET NULL on product delete means the line item survives with product_id=NULL while product_name and unit_price snapshots stay intact.',
      'order_items row 3: product_id=NULL (product deleted later), product_name="Resident Evil Requiem", unit_price=3495.00. The receipt is still complete.',
    ],
    [
      'inquiries -> users\nMany-to-One, NULL allowed',
      'One user can submit many support tickets. NULL = anonymous guest. Linking to a user lets admins see all tickets from the same customer in one view.',
      'inquiries rows 1 and 3 both have user_id=2 — Juan submitted two tickets. Row 2 has user_id=NULL (Maria browsed as a guest).',
    ],
  ]),
  pb(),
];

// ════════════════════════════════════════════════════════════════
// SECTION 5 — Data Type Glossary
// ════════════════════════════════════════════════════════════════
function glossTable(rows) {
  const W = [1700, 2200, 2960, 2500]; // 9360
  return new Table({
    width: { size: CONTENT, type: WidthType.DXA }, columnWidths: W,
    rows: [
      new TableRow({ tableHeader: true, children: ['Data Type','What It Stores','Why We Chose It','Wrong Choice = Problem'].map((h,i) => hCell(h, W[i])) }),
      ...rows.map((r, ri) => new TableRow({
        children: r.map((c, ci) => dCell(c, W[ci], ri % 2 === 0, ci === 0)),
      })),
    ],
  });
}

const sec5 = [
  h1('Section 5 — Data Type Glossary'), hr(),
  p('Every column has a data type that limits what values can be stored. Choosing the wrong type can waste storage, allow invalid data, or silently corrupt calculations. This glossary explains every type used in the GameBlitz schema.'),
  sp(8),
  glossTable([
    ['INT UNSIGNED',        'Whole numbers 0 to 4,294,967,295.', 'Used for auto-increment Primary Keys (user_id, product_id, order_id). UNSIGNED doubles the positive range — IDs are never negative.', 'Signed INT wastes half the range on negative numbers that will never be used for IDs.'],
    ['TINYINT UNSIGNED',    'Whole numbers 0 to 255.',           'Used for lookup table PKs (platform_id, category_id). We will never have more than 255 platforms. Saves 3 bytes per row vs INT — significant at millions of FK references.', 'INT for platform_id wastes 3 bytes per row in product_platforms, which can hold millions of rows.'],
    ['SMALLINT UNSIGNED',   'Whole numbers 0 to 65,535.',        'Used for stock in products. Branch inventory never exceeds 65,535 units per product. Saves 2 bytes per product row vs INT.', 'INT for stock wastes 2 bytes per row with no benefit in this context.'],
    ['VARCHAR(n)',          'Variable text up to n characters.', 'Used for names, emails, slugs, URLs. Stores only as many bytes as the actual text (plus 1-2 for length). VARCHAR(200) for a short name only uses the space it needs.', 'CHAR(200) always pads to 200 characters. A short name like "RPG" wastes 197 bytes per row.'],
    ['TEXT',                'Variable text up to ~65,000 chars.','Used for description, message, notes — content with no practical length limit. A product description or customer message can be as long as needed.', 'VARCHAR(500) for a product description would silently truncate long descriptions submitted by content editors.'],
    ['DECIMAL(10,2)',       'Exact decimal: up to 10 digits, 2 after the point.', 'MANDATORY for all money columns. Stores values exactly as entered. 2995.00 is always 2995.00.', 'FLOAT stores 2995.00 as approximately 2994.9999998... in binary. Totalling 10 items gives the wrong amount on customer receipts.'],
    ['TIMESTAMP',          'Date and time, stored as UTC.', 'Used for created_at and updated_at. MySQL auto-sets created_at on INSERT and auto-updates updated_at on every change — zero PHP code needed.', 'DATETIME does not auto-update. PHP must set it manually on every UPDATE, and a forgotten SET clause means stale audit timestamps.'],
    ['ENUM(a,b,...)',       'Exactly one value from a fixed list.', 'Used for role, badge, status, category. Enforces valid values at the DB level. A locked door beyond PHP validation. Also stores more efficiently than VARCHAR.', "VARCHAR for role means a typo like 'Admon' or 'ADMIN' bypasses PHP validation and could grant wrong admin access."],
    ['TINYINT(1)',          '0 or 1, used as a boolean.',         'MySQL\'s boolean idiom. Used for is_active, is_featured. PHP reads it as falsy/truthy naturally: if ($row[\'is_active\']).', "No native BOOLEAN type in MySQL — BOOL is just an alias for TINYINT(1). Using 'Y'/'N' strings makes PHP comparisons verbose and typo-prone."],
  ]),
  pb(),
];

// ════════════════════════════════════════════════════════════════
// SECTION 6 — Key Design Decisions
// ════════════════════════════════════════════════════════════════
const sec6 = [
  h1('Section 6 — Key Design Decisions'), hr(),
  p('This section explains the reasoning behind five important choices in the GameBlitz schema. Each one prevents a specific class of bug or data-quality problem that is very hard to fix after going live.'),
  sp(8),

  h2('Decision 1 — DECIMAL, not FLOAT, for all money columns'),
  p('Every price column uses DECIMAL(10,2). Here is why this is non-negotiable:'),
  bul('FLOAT uses binary floating-point math — the same system that makes 0.1 + 0.2 = 0.30000000000000004 in a calculator.'),
  bul('A product priced at PHP 1,495.00 stored as FLOAT might be 1,494.9999998... internally.'),
  bul('Totalling 10 cart items at that price gives PHP 14,949.99998 instead of PHP 14,950.00. The customer receipt is wrong.'),
  bul('DECIMAL stores the value exactly as typed. 1495.00 is always 1495.00. Use DECIMAL for every money column, in every project, forever.'),
  sp(10),

  h2('Decision 2 — Snapshot columns in cart_items and order_items'),
  p('Both cart_items.unit_price and order_items.unit_price copy the price at the moment of action. order_items also snapshots product_name. Here is why:'),
  bul('Prices change. Spider-Man 2 is on sale for PHP 2,495 today. The sale may end tomorrow.'),
  bul('Without snapshots: a customer adds it to their cart during the sale, the sale ends before checkout, and their total silently increases. This is deceptive and, in many jurisdictions, illegal in e-commerce.'),
  bul('With snapshots: the price at add-time is locked in. The customer always pays what was shown.'),
  bul('Product names also change. If "Elden Ring: Shadow of the Erdtree" is renamed, every past order still shows the name the customer actually saw when they bought it.'),
  bul('This pattern is called an audit trail — essential for any system handling real money.'),
  sp(10),

  h2('Decision 3 — SET NULL vs CASCADE vs RESTRICT on delete'),
  p('The ON DELETE rule controls what happens to child rows when a parent is deleted. We chose different rules for different relationships:'),
  bul('CASCADE — used where children have no meaning without the parent. cart_items without a cart_session, or order_items without an order, are useless orphan data. Delete the parent, delete the children automatically.'),
  bul('SET NULL — used where the child record is still valuable without the parent. Deleting a user account should not erase their order history or support tickets. Setting user_id to NULL preserves the record while acknowledging the user is gone. Same logic for a deleted product: the order line item keeps its snapshot data.'),
  bul('RESTRICT — used to prevent accidental data destruction. You cannot delete the RPG category while Elden Ring and FF7 Rebirth still belong to it. MySQL blocks the delete until all products are re-categorised or removed. This forces intentional cleanup instead of silent data loss.'),
  sp(10),

  h2('Decision 4 — Junction table, not comma-separated platforms'),
  p('The HTML stores platforms as data-platform="ps5,xbox,pc". The database must not copy this pattern:'),
  bul('Querying is broken: WHERE platform LIKE \'%ps5%\' returns false positives as the catalog grows.'),
  bul('Counting is impossible: SELECT COUNT(*) for Xbox games cannot work on a comma-separated string.'),
  bul('Filtering for the products page requires PHP to split every string and compare — this cannot be indexed, making it slow.'),
  bul('The junction table product_platforms solves all of this. Finding all Xbox games: SELECT p.* FROM products p JOIN product_platforms pp ON pp.product_id = p.product_id WHERE pp.platform_id = 2. Clean, indexed, correct.'),
  sp(10),

  h2('Decision 5 — VARCHAR(180) for email, not VARCHAR(255)'),
  p('Email addresses are usually stored as VARCHAR(255) because the spec allows 254 characters. We use VARCHAR(180) for a technical reason:'),
  bul('MySQL InnoDB with utf8mb4 encoding uses up to 4 bytes per character.'),
  bul('The default index key length limit is 767 bytes. Dividing 767 by 4 gives 191 characters maximum per indexed utf8mb4 column.'),
  bul('VARCHAR(255) on a UNIQUE KEY requires 255 x 4 = 1,020 bytes — exceeding the 767-byte limit. MySQL either throws an error or silently truncates the index, allowing duplicate emails through.'),
  bul('VARCHAR(180) requires 180 x 4 = 720 bytes — safely under the limit.'),
  bul('No real email address is 180 characters long. This choice is technically correct and practically indistinguishable from VARCHAR(255).'),
];

// ════════════════════════════════════════════════════════════════
// COVER PAGE
// ════════════════════════════════════════════════════════════════
const cover = [
  sp(40),
  new Paragraph({ alignment: AlignmentType.CENTER, spacing: { before: 0, after: 80 }, children: [new TextRun({ text: 'COMP-016  |  Web Development  |  Activity 4', font: 'Arial', size: 22, color: C.gray })] }),
  new Paragraph({ alignment: AlignmentType.CENTER, spacing: { before: 0, after: 40 }, children: [new TextRun({ text: 'GameBlitz', font: 'Arial', size: 96, bold: true, color: C.purple })] }),
  new Paragraph({ alignment: AlignmentType.CENTER, spacing: { before: 0, after: 40 }, children: [new TextRun({ text: 'Database', font: 'Arial', size: 72, bold: true, color: C.purpleMid })] }),
  new Paragraph({ alignment: AlignmentType.CENTER, spacing: { before: 0, after: 200 }, children: [new TextRun({ text: 'Entity Relationship Document', font: 'Arial', size: 36, color: C.gray })] }),
  hr(),
  new Paragraph({ alignment: AlignmentType.CENTER, spacing: { before: 200, after: 80 }, children: [new TextRun({ text: 'gameblitz_db  |  10 Tables  |  MySQL 8.0+', font: 'Courier New', size: 24, color: C.purpleMid })] }),
  new Paragraph({ alignment: AlignmentType.CENTER, spacing: { before: 40, after: 0 }, children: [new TextRun({ text: '2026', font: 'Arial', size: 22, color: C.gray })] }),
  pb(),
];

// ════════════════════════════════════════════════════════════════
// ASSEMBLE & WRITE
// ════════════════════════════════════════════════════════════════
const doc = new Document({
  numbering: {
    config: [{
      reference: 'bullets',
      levels: [{
        level: 0, format: LevelFormat.BULLET, text: '•', alignment: AlignmentType.LEFT,
        style: {
          paragraph: { indent: { left: 720, hanging: 360 }, spacing: { before: 40, after: 40 } },
          run: { font: 'Arial', size: 22, color: C.dark },
        },
      }],
    }],
  },
  sections: [{
    properties: {
      page: {
        size: { width: PAGE_W, height: PAGE_H },
        margin: { top: MARGIN, right: MARGIN, bottom: MARGIN, left: MARGIN },
      },
    },
    headers: {
      default: new Header({
        children: [new Paragraph({
          border: { bottom: { style: BorderStyle.SINGLE, size: 2, color: C.accent, space: 1 } },
          children: [
            new TextRun({ text: 'GameBlitz Database — ERD Document', font: 'Arial', size: 18, color: C.gray }),
            new TextRun({ text: '    |    COMP-016 Web Development | Activity 4', font: 'Arial', size: 18, color: C.gray }),
          ],
        })],
      }),
    },
    footers: {
      default: new Footer({
        children: [new Paragraph({
          alignment: AlignmentType.RIGHT,
          border: { top: { style: BorderStyle.SINGLE, size: 2, color: C.accent, space: 1 } },
          children: [
            new TextRun({ text: 'Page ', font: 'Arial', size: 18, color: C.gray }),
            new TextRun({ children: [PageNumber.CURRENT], font: 'Arial', size: 18, color: C.gray }),
            new TextRun({ text: ' of ', font: 'Arial', size: 18, color: C.gray }),
            new TextRun({ children: [PageNumber.TOTAL_PAGES], font: 'Arial', size: 18, color: C.gray }),
          ],
        })],
      }),
    },
    children: [
      ...cover,
      ...sec1,
      ...sec2,
      ...sec3,
      ...sec4,
      ...sec5,
      ...sec6,
    ],
  }],
});

const OUT = 'C:\\Users\\janke\\Documents\\Git Clone\\COMP-016---WebDev\\database\\GameBlitz_ERD.docx';
Packer.toBuffer(doc).then(buf => {
  fs.writeFileSync(OUT, buf);
  console.log('Written: ' + OUT);
}).catch(err => { console.error(err); process.exit(1); });
