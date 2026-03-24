# NotaryPRO

A web-based management system for notarial services — built with PHP 8, MySQL, and vanilla JavaScript. Includes a fully functional standalone HTML/JS version with localStorage persistence.

---

## Tech Stack

**PHP** — PHP 8.0+, MySQL 5.7+, PDO, Apache/Nginx  
---

## Features

- **Authentication** — registration, login, session-based access control
- **Dashboard** — KPI cards, recent orders, top services, order status breakdown
- **Clients** — full CRUD, search by name / passport / phone, client order history
- **Orders** — CRUD, status workflow (`pending → in_progress → completed / cancelled`), service price autofill
- **Services** — grouped by category, base price management
- **Notaries** — license tracking, per-notary revenue and order stats
- **Documents** — linked to orders, type / number / issue date
- **Reports** — monthly revenue, breakdown by notary and service category

---

## Project Structure

```
notary/
├── index.php                   # Dashboard
├── database.sql                # Schema + seed data
├── includes/
│   ├── config.php              # DB connection, auth helpers, flash messages
│   ├── header.php              # Navigation layout
│   └── footer.php
├── pages/
│   ├── login.php
│   ├── register.php
│   ├── logout.php
│   ├── clients.php
│   ├── orders.php
│   ├── services.php
│   ├── notaries.php
│   ├── documents.php
│   └── reports.php
└── assets/
    ├── css/style.css
    └── js/main.js
```

---

## Database Schema

| Table                | Description                        |
|----------------------|------------------------------------|
| `users`              | User accounts (auth)               |
| `notaries`           | Notaries with license numbers      |
| `clients`            | Clients — passport, tax ID         |
| `service_categories` | Service groupings                  |
| `services`           | Services with base prices          |
| `orders`             | Orders linked to client + notary   |
| `documents`          | Documents issued per order         |

---

## Getting Started — PHP version

### Requirements

- PHP 8.0+
- MySQL 5.7+ or MariaDB 10.3+
- Apache or Nginx

### 1. Import the database

```bash
mysql -u root -p < database.sql
```

Or import via phpMyAdmin.

### 2. Configure the connection

Edit `includes/config.php`:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'notary_db');
define('DB_USER', 'root');
define('DB_PASS', '');
```

### 3. Place files on the server

| Environment | Path                        |
|-------------|------------------------------|
| XAMPP       | `C:/xampp/htdocs/notary/`   |
| Laragon     | `C:/laragon/www/notary/`    |
| Linux       | `/var/www/html/notary/`     |

### 4. Open in browser

```
http://localhost/notary/
```

Default credentials:

```
Email:    admin@notary.ua
Password: admin123
```

---

## Getting Started — JS / HTML version

No installation required. Open `notary_pro.html` in any browser.

Data persists in `localStorage` — survives page reloads, local to the device.

Default credentials:

```
Email:    admin@notary.ua
Password: admin123
```

---

## Security (PHP version)

- PDO prepared statements — SQL injection protection
- `htmlspecialchars()` on all output — XSS protection
- `password_hash()` / `password_verify()` for credentials
- Session-based auth — unauthenticated requests redirect to login

---

## Key SQL Queries

**Revenue by notary:**

```sql
SELECT n.full_name,
       COUNT(o.id) AS orders,
       SUM(CASE WHEN o.status = 'completed' THEN o.total_price ELSE 0 END) AS revenue
FROM notaries n
LEFT JOIN orders o ON o.notary_id = n.id
GROUP BY n.id
ORDER BY revenue DESC;
```

**Monthly revenue (current year):**

```sql
SELECT MONTH(order_date) AS month,
       COUNT(*)           AS orders,
       SUM(total_price)   AS revenue
FROM orders
WHERE YEAR(order_date) = YEAR(CURDATE())
GROUP BY MONTH(order_date)
ORDER BY month;
```

**Top services:**

```sql
SELECT s.name,
       COUNT(o.id)        AS orders,
       SUM(o.total_price) AS revenue
FROM orders o
JOIN services s ON s.id = o.service_id
GROUP BY o.service_id
ORDER BY orders DESC
LIMIT 10;
```

---

## License

MIT
