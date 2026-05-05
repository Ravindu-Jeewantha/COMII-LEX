-- ===== COMII LEX DATABASE =====
-- Run this in phpMyAdmin or MySQL CLI

CREATE DATABASE IF NOT EXISTS comii_lex CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE comii_lex;

-- Products Table
CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    image VARCHAR(255) DEFAULT NULL,
    brand VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Cart Table (session-based)
CREATE TABLE IF NOT EXISTS cart (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id VARCHAR(128) NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE KEY unique_cart_item (session_id, product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─── Sample Products ───
INSERT INTO products (name, price, image, brand, description) VALUES
('MacBook Pro 16" M3 Max', 3499.00, NULL, 'Apple', 'The most powerful MacBook Pro ever. Featuring the M3 Max chip with up to 16-core CPU and 40-core GPU. With up to 128GB unified memory and 8TB SSD storage, this machine handles the most demanding professional workflows with ease. Up to 22 hours battery life.'),
('iPhone 15 Pro Max', 1199.00, NULL, 'Apple', 'Titanium design. A17 Pro chip. 48MP main camera with 5x optical zoom. Action Button. USB 3. The most advanced iPhone ever made, with a camera system that redefines what a smartphone can do.'),
('Sony WH-1000XM5', 349.00, NULL, 'Sony', 'Industry-leading noise canceling with two processors and eight microphones. Crystal clear hands-free calling with four beamforming microphones. Up to 30 hours battery life with quick charging.'),
('Samsung Galaxy S24 Ultra', 1299.00, NULL, 'Samsung', 'Galaxy AI is here. Note everything with the built-in S Pen. 200MP main camera with 50x Space Zoom. Titanium frame. 7 years of OS upgrades promised.'),
('iPad Pro 13" M4', 1299.00, NULL, 'Apple', 'Impossibly thin. The thinnest Apple product ever. Ultra Retina XDR OLED display. M4 chip with 10-core CPU. Perfect for creative professionals, artists, and power users.'),
('Dell XPS 15 OLED', 2199.00, NULL, 'Dell', 'The best Windows laptop. 3.5K OLED InfinityEdge touch display. Intel Core i9-13900H. NVIDIA RTX 4070. 32GB DDR5 RAM. Precision engineered for creators.'),
('Bose QuietComfort 45', 279.00, NULL, 'Bose', 'Quiet mode and Aware mode. High-fidelity audio with TriPort acoustic architecture. Up to 24 hours battery. Comfortable all-day wear with premium materials.'),
('Google Pixel 8 Pro', 999.00, NULL, 'Google', 'The most helpful phone ever. Google Tensor G3 chip. 50MP main camera with 5x optical zoom. 7 years of software updates. AI-powered photography and calling features.'),
('Apple Watch Ultra 2', 799.00, NULL, 'Apple', 'The most rugged and capable Apple Watch. Titanium case. Precision dual-frequency GPS. 60-hour battery life in low-power mode. 100m water resistance. Built for athletes.'),
('Logitech MX Master 3S', 99.00, NULL, 'Logitech', 'The perfect advanced wireless mouse. MagSpeed electromagnetic scroll wheel. 8000 DPI sensor. Up to 70 days battery life. Connects to 3 devices. USB-C charging.'),
('Samsung 49" Odyssey G9', 1499.00, NULL, 'Samsung', 'Ultimate curved gaming monitor. 5120x1440 DQHD resolution. 240Hz refresh rate. 1ms response time. NVIDIA G-Sync Compatible. HDR1000. Dual QHD immersive experience.'),
('DJI Mini 4 Pro', 759.00, NULL, 'DJI', 'Professional drone under 249g. Omnidirectional obstacle sensing. 4K HDR video at 60fps. 34-minute flight time. Extended range transmission. Intelligent flight modes.');
