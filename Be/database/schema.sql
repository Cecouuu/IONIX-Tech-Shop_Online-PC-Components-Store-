CREATE DATABASE IF NOT EXISTS project_app
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE project_app;

CREATE TABLE IF NOT EXISTS users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  full_name VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  is_admin TINYINT(1) NOT NULL DEFAULT 0,
  is_owner TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS user_requests (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  title VARCHAR(140) NOT NULL,
  message TEXT NOT NULL,
  status ENUM('new', 'in_progress', 'done') NOT NULL DEFAULT 'new',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_user_requests_user
    FOREIGN KEY (user_id) REFERENCES users(id)
    ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS user_logins (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  logged_in_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  ip_address VARCHAR(45) NULL,
  user_agent VARCHAR(255) NULL,
  CONSTRAINT fk_user_logins_user
    FOREIGN KEY (user_id) REFERENCES users(id)
    ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS products (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  category VARCHAR(80) NOT NULL DEFAULT 'Components',
  description TEXT NOT NULL,
  image_url VARCHAR(600) NOT NULL,
  price DECIMAL(10,2) NOT NULL,
  stock INT UNSIGNED NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS orders (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NULL,
  guest_name VARCHAR(120) NOT NULL,
  guest_email VARCHAR(190) NOT NULL,
  guest_phone VARCHAR(40) NOT NULL,
  guest_address VARCHAR(255) NOT NULL,
  total_amount DECIMAL(10,2) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_orders_user
    FOREIGN KEY (user_id) REFERENCES users(id)
    ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS order_items (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_id INT UNSIGNED NOT NULL,
  product_id INT UNSIGNED NOT NULL,
  quantity INT UNSIGNED NOT NULL,
  unit_price DECIMAL(10,2) NOT NULL,
  CONSTRAINT fk_order_items_order
    FOREIGN KEY (order_id) REFERENCES orders(id)
    ON DELETE CASCADE,
  CONSTRAINT fk_order_items_product
    FOREIGN KEY (product_id) REFERENCES products(id)
    ON DELETE RESTRICT
);

CREATE TABLE IF NOT EXISTS product_highlights (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  product_id INT UNSIGNED NOT NULL,
  highlight_type ENUM('weekly', 'weekend') NOT NULL,
  discount_percent DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  label VARCHAR(120) NOT NULL DEFAULT '',
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_highlights_product
    FOREIGN KEY (product_id) REFERENCES products(id)
    ON DELETE CASCADE,
  UNIQUE KEY uniq_highlight_product_type (product_id, highlight_type)
);

INSERT INTO users (full_name, email, password_hash, is_admin, is_owner)
SELECT 'Admin', 'admin@techstore.local', '$2y$10$aKHKePxcxF39lgG9VBIFY.oUkEuTyq.x9EJWPKAi9huGzcEfOE97.', 1, 1
WHERE NOT EXISTS (
  SELECT 1 FROM users WHERE email = 'admin@techstore.local'
);

INSERT INTO products (name, category, description, image_url, price, stock)
SELECT 'NVIDIA GeForce RTX 4070', 'Graphics Cards', 'High-performance GPU for 1440p gaming and content creation.', 'https://source.unsplash.com/1200x900/?graphics-card,pc&sig=101', 629.99, 14
WHERE NOT EXISTS (SELECT 1 FROM products WHERE name = 'NVIDIA GeForce RTX 4070');

INSERT INTO products (name, category, description, image_url, price, stock)
SELECT 'NVIDIA GeForce RTX 4060 Ti', 'Graphics Cards', 'Efficient GPU for smooth 1080p and entry 1440p performance.', 'https://source.unsplash.com/1200x900/?gpu,computer&sig=102', 419.99, 19
WHERE NOT EXISTS (SELECT 1 FROM products WHERE name = 'NVIDIA GeForce RTX 4060 Ti');

INSERT INTO products (name, category, description, image_url, price, stock)
SELECT 'AMD Radeon RX 7800 XT', 'Graphics Cards', 'Strong raster performance with modern feature support.', 'https://source.unsplash.com/1200x900/?video-card,pc&sig=103', 499.99, 11
WHERE NOT EXISTS (SELECT 1 FROM products WHERE name = 'AMD Radeon RX 7800 XT');

INSERT INTO products (name, category, description, image_url, price, stock)
SELECT 'AMD Ryzen 7 7800X3D', 'Processors', '8-core gaming-focused CPU with excellent efficiency.', 'https://source.unsplash.com/1200x900/?processor,cpu&sig=104', 379.99, 22
WHERE NOT EXISTS (SELECT 1 FROM products WHERE name = 'AMD Ryzen 7 7800X3D');

INSERT INTO products (name, category, description, image_url, price, stock)
SELECT 'Intel Core i7-14700K', 'Processors', '14th gen hybrid CPU for gaming and production workloads.', 'https://source.unsplash.com/1200x900/?intel,cpu&sig=105', 409.99, 16
WHERE NOT EXISTS (SELECT 1 FROM products WHERE name = 'Intel Core i7-14700K');

INSERT INTO products (name, category, description, image_url, price, stock)
SELECT 'ASUS TUF B650-PLUS WIFI', 'Motherboards', 'AM5 motherboard with PCIe 5.0 and Wi-Fi support.', 'https://source.unsplash.com/1200x900/?motherboard,pc&sig=106', 229.00, 18
WHERE NOT EXISTS (SELECT 1 FROM products WHERE name = 'ASUS TUF B650-PLUS WIFI');

INSERT INTO products (name, category, description, image_url, price, stock)
SELECT 'MSI MAG Z790 Tomahawk WiFi', 'Motherboards', 'High-end Intel board with strong VRM and expansion.', 'https://source.unsplash.com/1200x900/?pc,motherboard&sig=107', 319.00, 9
WHERE NOT EXISTS (SELECT 1 FROM products WHERE name = 'MSI MAG Z790 Tomahawk WiFi');

INSERT INTO products (name, category, description, image_url, price, stock)
SELECT 'Corsair Vengeance 32GB DDR5', 'Memory', '32GB (2x16GB) DDR5 memory kit for modern systems.', 'https://source.unsplash.com/1200x900/?ram,memory&sig=108', 119.50, 34
WHERE NOT EXISTS (SELECT 1 FROM products WHERE name = 'Corsair Vengeance 32GB DDR5');

INSERT INTO products (name, category, description, image_url, price, stock)
SELECT 'G.Skill Trident Z5 64GB DDR5', 'Memory', 'Premium 64GB DDR5 kit for heavy multitasking builds.', 'https://source.unsplash.com/1200x900/?dram,computer&sig=109', 239.99, 12
WHERE NOT EXISTS (SELECT 1 FROM products WHERE name = 'G.Skill Trident Z5 64GB DDR5');

INSERT INTO products (name, category, description, image_url, price, stock)
SELECT 'Samsung 990 PRO 1TB NVMe SSD', 'Storage', 'Fast PCIe 4.0 SSD with strong sustained performance.', 'https://source.unsplash.com/1200x900/?ssd,nvme&sig=110', 109.90, 40
WHERE NOT EXISTS (SELECT 1 FROM products WHERE name = 'Samsung 990 PRO 1TB NVMe SSD');

INSERT INTO products (name, category, description, image_url, price, stock)
SELECT 'WD Black SN850X 2TB', 'Storage', 'High-end NVMe drive for fast game and app loads.', 'https://source.unsplash.com/1200x900/?storage,ssd&sig=111', 179.00, 27
WHERE NOT EXISTS (SELECT 1 FROM products WHERE name = 'WD Black SN850X 2TB');

INSERT INTO products (name, category, description, image_url, price, stock)
SELECT 'Corsair RM850e PSU', 'Power Supplies', '850W 80+ Gold fully modular PSU for stable power.', 'https://source.unsplash.com/1200x900/?power-supply,pc&sig=112', 129.00, 21
WHERE NOT EXISTS (SELECT 1 FROM products WHERE name = 'Corsair RM850e PSU');

INSERT INTO products (name, category, description, image_url, price, stock)
SELECT 'NZXT H7 Flow Case', 'Cases', 'Airflow-focused ATX case with modern cable management.', 'https://source.unsplash.com/1200x900/?pc-case,gaming&sig=113', 139.00, 13
WHERE NOT EXISTS (SELECT 1 FROM products WHERE name = 'NZXT H7 Flow Case');
