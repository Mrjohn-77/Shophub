-- SHOPHUB: affiliate product links grouped by category
-- Run on your existing database in phpMyAdmin (select the database first).
-- NON-DESTRUCTIVE: no DROP TABLE. Table names do not clash with the resume lab tables.

CREATE TABLE IF NOT EXISTS admins (
  id INT AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(190) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS categories (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS platforms (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS products (
  id INT AUTO_INCREMENT PRIMARY KEY,
  category_id INT NOT NULL,
  platform_id INT NOT NULL,
  name VARCHAR(200) NOT NULL,
  price DECIMAL(10,2) NOT NULL,
  image_url VARCHAR(500),
  affiliate_url VARCHAR(500) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_products_category FOREIGN KEY (category_id) REFERENCES categories(id)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_products_platform FOREIGN KEY (platform_id) REFERENCES platforms(id)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS clicks (
  id INT AUTO_INCREMENT PRIMARY KEY,
  product_id INT NOT NULL,
  clicked_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_clicks_product FOREIGN KEY (product_id) REFERENCES products(id)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Sample data
INSERT INTO categories (name) VALUES ('IT & Gadgets'), ('Fashion'), ('Beauty'), ('Home & Living');
INSERT INTO platforms (name) VALUES ('Shopee'), ('Lazada');
INSERT INTO products (category_id, platform_id, name, price, image_url, affiliate_url) VALUES
 (1, 1, 'Wireless Mouse', 299.00, NULL, 'https://shopee.co.th/'),
 (1, 2, 'Mechanical Keyboard', 1290.00, NULL, 'https://www.lazada.co.th/'),
 (2, 1, 'Oversize T-Shirt', 199.00, NULL, 'https://shopee.co.th/'),
 (3, 2, 'Sunscreen SPF50', 359.00, NULL, 'https://www.lazada.co.th/'),
 (4, 1, 'Desk Lamp', 450.00, NULL, 'https://shopee.co.th/');
INSERT INTO clicks (product_id) VALUES (1), (1), (1), (2), (4);