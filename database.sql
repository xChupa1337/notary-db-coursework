-- ============================================================
-- БАЗА ДАНИХ НОТАРІАЛЬНИХ ПОСЛУГ
-- ============================================================

CREATE DATABASE IF NOT EXISTS notary_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE notary_db;

-- ------------------------------------------------------------
-- 1. НОТАРІУСИ
-- ------------------------------------------------------------
CREATE TABLE notaries (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    full_name   VARCHAR(150) NOT NULL,
    license_no  VARCHAR(50)  NOT NULL UNIQUE,
    phone       VARCHAR(20),
    email       VARCHAR(100),
    address     TEXT,
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 2. КЛІЄНТИ
-- ------------------------------------------------------------
CREATE TABLE clients (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    full_name     VARCHAR(150) NOT NULL,
    passport_no   VARCHAR(20)  NOT NULL UNIQUE,
    tax_id        VARCHAR(20),
    phone         VARCHAR(20),
    email         VARCHAR(100),
    address       TEXT,
    created_at    DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 3. КАТЕГОРІЇ ПОСЛУГ
-- ------------------------------------------------------------
CREATE TABLE service_categories (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(100) NOT NULL,
    description TEXT
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 4. ПОСЛУГИ
-- ------------------------------------------------------------
CREATE TABLE services (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    name        VARCHAR(200) NOT NULL,
    description TEXT,
    base_price  DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    FOREIGN KEY (category_id) REFERENCES service_categories(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 5. ЗАМОВЛЕННЯ (УГОДИ)
-- ------------------------------------------------------------
CREATE TABLE orders (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    client_id    INT NOT NULL,
    notary_id    INT NOT NULL,
    service_id   INT NOT NULL,
    order_date   DATE NOT NULL,
    status       ENUM('pending','in_progress','completed','cancelled') DEFAULT 'pending',
    total_price  DECIMAL(10,2) NOT NULL,
    notes        TEXT,
    created_at   DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id)  REFERENCES clients(id)  ON DELETE RESTRICT,
    FOREIGN KEY (notary_id)  REFERENCES notaries(id) ON DELETE RESTRICT,
    FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 6. ДОКУМЕНТИ
-- ------------------------------------------------------------
CREATE TABLE documents (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    order_id    INT NOT NULL,
    doc_type    VARCHAR(100) NOT NULL,
    doc_number  VARCHAR(100),
    issue_date  DATE,
    notes       TEXT,
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- ТЕСТОВІ ДАНІ
-- ------------------------------------------------------------
INSERT INTO notaries (full_name, license_no, phone, email, address) VALUES
('Іваненко Олена Василівна',  'НОТ-2019-00123', '+380441234567', 'ivanenko@notary.ua', 'м. Київ, вул. Хрещатик, 10'),
('Петренко Микола Андрійович','НОТ-2017-00456', '+380442345678', 'petrenko@notary.ua', 'м. Київ, вул. Саксаганського, 25'),
('Сидоренко Тетяна Іванівна', 'НОТ-2021-00789', '+380443456789', 'sydorenko@notary.ua','м. Київ, пр. Перемоги, 47');

INSERT INTO clients (full_name, passport_no, tax_id, phone, email, address) VALUES
('Коваленко Сергій Михайлович','АБ123456','1234567890','+380501111111','kovalenko@gmail.com','м. Київ, вул. Ярославів Вал, 5'),
('Мельник Ірина Олегівна',    'ВГ789012','2345678901','+380502222222','melnyk@gmail.com',   'м. Київ, вул. Велика Васильківська, 18'),
('Бондаренко Олексій Петрович','ДЕ345678','3456789012','+380503333333','bondarenko@gmail.com','м. Київ, вул. Льва Толстого, 3'),
('Гриценко Наталія Вікторівна','ЄЖ901234','4567890123','+380504444444','hrycenko@gmail.com',  'м. Київ, вул. Богдана Хмельницького, 33'),
('Савченко Дмитро Іванович',  'ЗИ567890','5678901234','+380505555555','savchenko@gmail.com', 'м. Київ, вул. Антоновича, 22');

INSERT INTO service_categories (name, description) VALUES
('Посвідчення правочинів',     'Посвідчення договорів купівлі-продажу, дарування, оренди тощо'),
('Спадкові справи',            'Оформлення свідоцтв про право на спадщину'),
('Засвідчення підписів',       'Засвідчення справжності підписів на документах'),
('Видача дублікатів',          'Видача дублікатів нотаріальних документів'),
('Корпоративні послуги',       'Засвідчення статутів, протоколів, довіреностей для юросіб');

INSERT INTO services (category_id, name, base_price) VALUES
(1, 'Договір купівлі-продажу нерухомості',    3500.00),
(1, 'Договір дарування нерухомості',          2500.00),
(1, 'Договір оренди',                          800.00),
(1, 'Шлюбний договір',                        1500.00),
(2, 'Свідоцтво про право на спадщину за законом', 2000.00),
(2, 'Свідоцтво про право на спадщину за заповітом',1800.00),
(2, 'Посвідчення заповіту',                   1200.00),
(3, 'Засвідчення підпису фізичної особи',      300.00),
(3, 'Засвідчення копії документа',             150.00),
(4, 'Видача дубліката договору',               600.00),
(5, 'Засвідчення статуту підприємства',       2200.00),
(5, 'Генеральна довіреність',                  900.00);

INSERT INTO orders (client_id, notary_id, service_id, order_date, status, total_price, notes) VALUES
(1, 1, 1, '2024-01-15', 'completed', 3500.00, 'Квартира на Позняках'),
(2, 2, 5, '2024-02-10', 'completed', 2000.00, 'Спадщина після батька'),
(3, 1, 8, '2024-03-05', 'completed',  300.00, NULL),
(4, 3,12, '2024-03-20', 'in_progress', 900.00, 'Довіреність на авто'),
(5, 2, 2, '2024-04-01', 'pending',   2500.00, 'Дарування будинку'),
(1, 3, 9, '2024-04-15', 'completed',  150.00, NULL),
(3, 1,11, '2024-05-02', 'in_progress',2200.00,'ТОВ Альфа'),
(2, 2, 7, '2024-05-18', 'completed', 1200.00, 'Заповіт на доньку');

INSERT INTO documents (order_id, doc_type, doc_number, issue_date) VALUES
(1, 'Договір купівлі-продажу', 'ДКП-2024-001', '2024-01-15'),
(2, 'Свідоцтво про спадщину',  'СС-2024-001',  '2024-02-15'),
(3, 'Засвідчений підпис',      'ЗП-2024-001',  '2024-03-05'),
(6, 'Копія документа',         'КД-2024-001',  '2024-04-15'),
(8, 'Заповіт',                 'ЗВ-2024-001',  '2024-05-18');

-- ------------------------------------------------------------
-- 7. КОРИСТУВАЧІ (реєстрація / вхід)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(150) NOT NULL,
    email      VARCHAR(150) NOT NULL UNIQUE,
    password   VARCHAR(255) NOT NULL,
    role       ENUM('admin','user') DEFAULT 'user',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Тестовий адмін: admin@notary.ua / admin123
INSERT IGNORE INTO users (name, email, password, role) VALUES
('Адміністратор', 'admin@notary.ua', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');
