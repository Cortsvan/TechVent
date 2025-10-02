-- TechVent Database Schema for Supplier-Product Management
-- Run this script to create the necessary tables for the supplier-product workflow

-- Create suppliers table
CREATE TABLE IF NOT EXISTS suppliers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    contact_person VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    phone VARCHAR(20),
    address TEXT,
    website VARCHAR(255),
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create products table (supplier catalog)
CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    supplier_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    sku VARCHAR(100) UNIQUE,
    category VARCHAR(100),
    brand VARCHAR(100),
    unit_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    min_order_quantity INT DEFAULT 1,
    status ENUM('active', 'inactive', 'discontinued') DEFAULT 'active',
    specifications JSON,
    image_url VARCHAR(500),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE CASCADE,
    INDEX idx_supplier_id (supplier_id),
    INDEX idx_sku (sku),
    INDEX idx_category (category),
    INDEX idx_status (status)
);

-- Create inventory table (actual stock management)
CREATE TABLE IF NOT EXISTS inventory (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    quantity_in_stock INT NOT NULL DEFAULT 0,
    quantity_reserved INT NOT NULL DEFAULT 0,
    quantity_available INT GENERATED ALWAYS AS (quantity_in_stock - quantity_reserved) STORED,
    reorder_level INT DEFAULT 10,
    reorder_quantity INT DEFAULT 50,
    location VARCHAR(100),
    last_restocked TIMESTAMP NULL,
    cost_price DECIMAL(10,2),
    selling_price DECIMAL(10,2),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    INDEX idx_product_id (product_id),
    INDEX idx_quantity_available (quantity_available),
    INDEX idx_reorder_level (reorder_level)
);

-- Create inventory transactions table (track all stock movements)
CREATE TABLE IF NOT EXISTS inventory_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    inventory_id INT NOT NULL,
    transaction_type ENUM('stock_in', 'stock_out', 'adjustment', 'transfer', 'return') NOT NULL,
    quantity INT NOT NULL,
    reference_number VARCHAR(100),
    notes TEXT,
    user_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (inventory_id) REFERENCES inventory(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_inventory_id (inventory_id),
    INDEX idx_transaction_type (transaction_type),
    INDEX idx_created_at (created_at)
);

-- Insert sample suppliers
INSERT IGNORE INTO suppliers (name, contact_person, email, phone, address, website) VALUES
('TechDistributor Corp', 'John Smith', 'john@techdistributor.com', '+1-555-0101', '123 Tech Street, Silicon Valley, CA', 'https://techdistributor.com'),
('Global Electronics Supply', 'Sarah Johnson', 'sarah@globales.com', '+1-555-0102', '456 Electronics Blvd, Austin, TX', 'https://globales.com'),
('Premium Components Inc', 'Mike Chen', 'mike@premiumcomp.com', '+1-555-0103', '789 Component Ave, Seattle, WA', 'https://premiumcomp.com'),
('Digital Solutions Ltd', 'Emma Wilson', 'emma@digitalsol.com', '+1-555-0104', '321 Digital Way, Boston, MA', 'https://digitalsol.com');

-- Insert sample products
INSERT IGNORE INTO products (supplier_id, name, description, sku, category, brand, unit_price, min_order_quantity) VALUES
(1, 'Wireless Gaming Mouse', 'High-precision wireless gaming mouse with RGB lighting', 'WGM-001', 'Peripherals', 'TechBrand', 79.99, 10),
(1, 'Mechanical Keyboard', 'RGB mechanical keyboard with blue switches', 'MKB-002', 'Peripherals', 'TechBrand', 129.99, 5),
(1, 'USB-C Hub', '7-in-1 USB-C hub with HDMI and ethernet', 'UCH-003', 'Accessories', 'TechBrand', 49.99, 25),

(2, 'Gaming Headset', 'Surround sound gaming headset with noise cancellation', 'GHS-004', 'Audio', 'SoundMax', 159.99, 8),
(2, 'Webcam 4K', '4K ultra HD webcam with auto-focus', 'WC4K-005', 'Video', 'VisionPro', 89.99, 15),
(2, 'Monitor 27" 4K', '27-inch 4K IPS monitor with USB-C', 'MON-006', 'Displays', 'DisplayMax', 399.99, 3),

(3, 'SSD 1TB NVMe', 'High-speed 1TB NVMe SSD', 'SSD-007', 'Storage', 'SpeedDrive', 199.99, 12),
(3, 'RAM 16GB DDR4', '16GB DDR4-3200 memory kit', 'RAM-008', 'Memory', 'MemoryPro', 149.99, 20),
(3, 'Graphics Card GTX', 'Mid-range graphics card for gaming', 'GPU-009', 'Graphics', 'GraphicsMax', 599.99, 2),

(4, 'Laptop Stand', 'Adjustable aluminum laptop stand', 'LS-010', 'Accessories', 'ErgoTech', 39.99, 30),
(4, 'Bluetooth Speaker', 'Portable Bluetooth speaker with 20hr battery', 'BTS-011', 'Audio', 'SoundWave', 69.99, 18),
(4, 'Power Bank 20000mAh', 'High-capacity power bank with fast charging', 'PB-012', 'Power', 'PowerMax', 59.99, 25);

-- Insert sample inventory (some products in stock, others need restocking)
INSERT IGNORE INTO inventory (product_id, quantity_in_stock, reorder_level, reorder_quantity, location, cost_price, selling_price) VALUES
(1, 45, 10, 50, 'A-001', 65.00, 79.99),
(2, 8, 5, 25, 'A-002', 105.00, 129.99),
(3, 120, 25, 100, 'B-001', 35.00, 49.99),
(4, 15, 8, 30, 'C-001', 125.00, 159.99),
(5, 3, 15, 40, 'C-002', 70.00, 89.99),  -- Low stock
(6, 7, 3, 10, 'D-001', 320.00, 399.99),
(7, 25, 12, 50, 'E-001', 155.00, 199.99),
(8, 55, 20, 75, 'E-002', 115.00, 149.99),
(9, 1, 2, 8, 'F-001', 475.00, 599.99),   -- Low stock
(10, 85, 30, 100, 'G-001', 28.00, 39.99),
(11, 22, 18, 60, 'G-002', 52.00, 69.99),
(12, 95, 25, 80, 'H-001', 42.00, 59.99);