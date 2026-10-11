-- Agri Smart Hub Database Setup
-- Save this file as setup_database.sql
-- We'll run this later

CREATE DATABASE IF NOT EXISTS agri_smart_hub;
USE agri_smart_hub;

-- ==============================================
-- AGRI SMART HUB - COMPLETE DATABASE SETUP SCRIPT
-- ==============================================
-- DROP DATABASE IF EXISTS agri_smart_hub;
-- CREATE DATABASE agri_smart_hub;
-- USE agri_smart_hub;

-- 1. USERS TABLE
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100),
    phone VARCHAR(20),
    address TEXT,
    profile_image VARCHAR(255) DEFAULT 'default_profile.jpg',
    role ENUM('admin', 'farmer', 'supplier', 'customer') DEFAULT 'customer',
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    email_verified TINYINT(1) DEFAULT 0,
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 2. PRODUCTS TABLE
CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    description TEXT,
    specifications TEXT,
    price DECIMAL(10,2) NOT NULL,
    discounted_price DECIMAL(10,2),
    image VARCHAR(255) DEFAULT 'default_product.jpg',
    gallery_images TEXT,
    category VARCHAR(50) NOT NULL,
    subcategory VARCHAR(50),
    brand VARCHAR(100),
    unit VARCHAR(20) DEFAULT 'piece',
    stock INT DEFAULT 10,
    min_order INT DEFAULT 1,
    max_order INT DEFAULT 100,
    status ENUM('active', 'inactive', 'out_of_stock', 'discontinued') DEFAULT 'active',
    featured TINYINT(1) DEFAULT 0,
    best_seller TINYINT(1) DEFAULT 0,
    rating DECIMAL(3,2) DEFAULT 0.00,
    total_reviews INT DEFAULT 0,
    supplier_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 3. ORDERS TABLE
CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_number VARCHAR(50) UNIQUE NOT NULL,
    user_id INT NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    shipping_cost DECIMAL(10,2) DEFAULT 0,
    tax_amount DECIMAL(10,2) DEFAULT 0,
    discount_amount DECIMAL(10,2) DEFAULT 0,
    final_amount DECIMAL(10,2) NOT NULL,
    shipping_address TEXT NOT NULL,
    billing_address TEXT,
    phone VARCHAR(20) NOT NULL,
    email VARCHAR(100),
    payment_method ENUM('cash_on_delivery', 'bank_transfer', 'mobile_money', 'card') DEFAULT 'cash_on_delivery',
    payment_status ENUM('pending', 'paid', 'failed', 'refunded') DEFAULT 'pending',
    payment_details TEXT,
    status ENUM('pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled', 'returned') DEFAULT 'pending',
    tracking_number VARCHAR(100),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_status (status),
    INDEX idx_order_number (order_number)
);

-- 4. ORDER_ITEMS TABLE
CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    product_name VARCHAR(200) NOT NULL,
    product_price DECIMAL(10,2) NOT NULL,
    quantity INT NOT NULL,
    total_price DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    INDEX idx_order_id (order_id),
    INDEX idx_product_id (product_id)
);

-- 5. ADVISORY TABLE
-- First, ensure all advisory tables exist
CREATE TABLE IF NOT EXISTS advisory (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    excerpt TEXT,
    content LONGTEXT NOT NULL,
    question_type ENUM('why', 'what', 'how', 'when', 'where') DEFAULT 'how',
    short_answer TEXT,
    detailed_steps TEXT,
    key_points TEXT,
    benefits TEXT,
    warnings TEXT,
    application_rate VARCHAR(255),
    frequency VARCHAR(255),
    best_time VARCHAR(255),
    category VARCHAR(50) NOT NULL,
    subcategory VARCHAR(50),
    tags TEXT,
    image VARCHAR(255) DEFAULT 'default_article.jpg',
    featured TINYINT(1) DEFAULT 0,
    status ENUM('published', 'draft', 'archived') DEFAULT 'published',
    views INT DEFAULT 0,
    likes INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Drop and recreate chatbot_responses table with better structure
DROP TABLE IF EXISTS chatbot_responses;
CREATE TABLE chatbot_responses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    keyword VARCHAR(100) NOT NULL,
    question_pattern VARCHAR(255) NOT NULL,
    response TEXT NOT NULL,
    related_products TEXT,
    related_advisory TEXT,
    usage_count INT DEFAULT 0,
    last_used TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_keyword (keyword)
);
-- 6. CART TABLE
CREATE TABLE IF NOT EXISTS cart (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    selected_attributes TEXT,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_product (user_id, product_id),
    INDEX idx_user_id (user_id),
    INDEX idx_product_id (product_id)
);

-- 7. WISHLIST TABLE
CREATE TABLE IF NOT EXISTS wishlist (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_wishlist (user_id, product_id),
    INDEX idx_user_id (user_id),
    INDEX idx_product_id (product_id)
);

-- 8. REVIEWS TABLE
CREATE TABLE IF NOT EXISTS reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    order_id INT,
    rating TINYINT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    title VARCHAR(200),
    comment TEXT,
    images TEXT,
    verified_purchase TINYINT(1) DEFAULT 0,
    helpful_count INT DEFAULT 0,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_product_review (user_id, product_id),
    INDEX idx_product_id (product_id),
    INDEX idx_user_id (user_id),
    INDEX idx_status (status)
);

-- 9. CATEGORIES TABLE
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) UNIQUE NOT NULL,
    description TEXT,
    parent_id INT DEFAULT 0,
    image VARCHAR(255),
    sort_order INT DEFAULT 0,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_parent_id (parent_id),
    INDEX idx_slug (slug)
);

-- 10. SUPPLIERS TABLE
CREATE TABLE IF NOT EXISTS suppliers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    phone VARCHAR(20),
    address TEXT,
    contact_person VARCHAR(100),
    website VARCHAR(255),
    rating DECIMAL(3,2) DEFAULT 0.00,
    total_products INT DEFAULT 0,
    status ENUM('active', 'inactive', 'pending') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 11. FARMING CALCULATORS TABLE
CREATE TABLE IF NOT EXISTS calculators (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) UNIQUE NOT NULL,
    description TEXT,
    formula TEXT,
    inputs JSON,
    outputs JSON,
    category VARCHAR(50),
    featured TINYINT(1) DEFAULT 0,
    usage_count INT DEFAULT 0,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 12. WEATHER DATA TABLE (CORRECTED)
CREATE TABLE IF NOT EXISTS weather_data (
    id INT AUTO_INCREMENT PRIMARY KEY,
    location VARCHAR(100),
    temperature DECIMAL(5,2),
    humidity DECIMAL(5,2),
    rainfall DECIMAL(5,2),
    wind_speed DECIMAL(5,2),
    forecast_date DATE,
    weather_condition VARCHAR(50),  -- Fixed: Changed 'condition' to 'weather_condition'
    advice TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_location_date (location, forecast_date)
);
-- 13. PRICE TRENDS TABLE
CREATE TABLE IF NOT EXISTS price_trends (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT,
    product_name VARCHAR(200),
    market VARCHAR(100),
    price DECIMAL(10,2),
    unit VARCHAR(20),
    trend ENUM('up', 'down', 'stable'),
    change_percentage DECIMAL(5,2),
    recorded_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_product_date (product_id, recorded_date)
);

-- 14. NOTIFICATIONS TABLE
-- To fix the basic structure if needed:
CREATE TABLE IF NOT EXISTS notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL DEFAULT 0,
    type VARCHAR(50) NOT NULL DEFAULT 'system',
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    link VARCHAR(500) NULL,
    read_status TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_read_status (read_status),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 15. SETTINGS TABLE
CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    setting_group VARCHAR(50),
    data_type ENUM('string', 'integer', 'boolean', 'json', 'array') DEFAULT 'string',
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Add missing tables to your database

-- 16. CONTACT_MESSAGES TABLE
CREATE TABLE IF NOT EXISTS contact_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    subject VARCHAR(200) NOT NULL,
    category ENUM('general', 'products', 'order', 'technical', 'advisory', 'partner') DEFAULT 'general',
    message TEXT NOT NULL,
    status ENUM('new', 'read', 'replied', 'closed') DEFAULT 'new',
    admin_notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    replied_at TIMESTAMP NULL,
    INDEX idx_email (email),
    INDEX idx_status (status),
    INDEX idx_created (created_at)
);

--17 User Sessions Table
CREATE TABLE IF NOT EXISTS user_sessions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    session_id VARCHAR(128) UNIQUE NOT NULL,
    user_id INT DEFAULT NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent TEXT,
    fingerprint VARCHAR(64) NOT NULL,
    data TEXT, -- Serialized session data
    created_at DATETIME NOT NULL,
    last_activity DATETIME NOT NULL,
    INDEX idx_session_id (session_id),
    INDEX idx_user_id (user_id),
    INDEX idx_last_activity (last_activity),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- 18 Security Logs Table
CREATE TABLE IF NOT EXISTS security_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    event_type VARCHAR(50) NOT NULL,
    session_id VARCHAR(128),
    user_id INT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    event_data JSON,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_event_type (event_type),
    INDEX idx_created_at (created_at),
    INDEX idx_session_id (session_id),
    INDEX idx_user_id (user_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

--19 User Activities Table
CREATE TABLE IF NOT EXISTS user_activities (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    activity_type VARCHAR(100) NOT NULL,
    description TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_activity_type (activity_type),
    INDEX idx_created_at (created_at),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- 20 Login Attempts Table (for rate limiting)
CREATE TABLE IF NOT EXISTS login_attempts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    ip_address VARCHAR(45) NOT NULL,
    email VARCHAR(100),
    attempt_time DATETIME DEFAULT CURRENT_TIMESTAMP,
    success BOOLEAN DEFAULT FALSE,
    INDEX idx_ip_address (ip_address),
    INDEX idx_email (email),
    INDEX idx_attempt_time (attempt_time)
);

-- Update notifications table to include contact type
ALTER TABLE notifications 
MODIFY COLUMN type ENUM('order', 'price_alert', 'advisory', 'system', 'promotion', 'contact') DEFAULT 'system';

-- ==============================================
-- INSERT SAMPLE DATA
-- ==============================================

-- Insert default admin user (password: admin123)
-- Then insert the users
INSERT INTO users (username, email, password, full_name, phone, role, email_verified) VALUES 
('admin', 'admin@agrismarthub.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', '+251911223344', 'admin', 1),
('farmer1', 'farmer1@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Abebe Kebede', '+251912345678', 'farmer', 1),
('supplier1', 'supplier@agro.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Agro Supplier Ltd', '+251913456789', 'supplier', 1),
('customer1', 'customer1@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Mesfin Hailu', '+251914567890', 'customer', 1);
-- Insert categories
INSERT INTO categories (name, slug, description, parent_id, sort_order) VALUES
('Seeds', 'seeds', 'Agricultural seeds for various crops', 0, 1),
('Cereal Seeds', 'cereal-seeds', 'Seeds for cereal crops', 1, 1),
('Vegetable Seeds', 'vegetable-seeds', 'Seeds for vegetable crops', 1, 2),
('Fertilizers', 'fertilizers', 'Chemical and organic fertilizers', 0, 2),
('Chemical Fertilizers', 'chemical-fertilizers', 'Synthetic fertilizers', 4, 1),
('Organic Fertilizers', 'organic-fertilizers', 'Natural organic fertilizers', 4, 2),
('Pesticides', 'pesticides', 'Crop protection chemicals', 0, 3),
('Insecticides', 'insecticides', 'For insect control', 7, 1),
('Fungicides', 'fungicides', 'For fungal disease control', 7, 2),
('Herbicides', 'herbicides', 'For weed control', 7, 3),
('Equipment', 'equipment', 'Farming tools and machinery', 0, 4),
('Irrigation Equipment', 'irrigation-equipment', 'Watering systems', 11, 1),
('Hand Tools', 'hand-tools', 'Manual farming tools', 11, 2),
('Animal Feed', 'animal-feed', 'Livestock and poultry feed', 0, 5);

-- Insert suppliers
INSERT INTO suppliers (name, email, phone, contact_person, rating, status) VALUES
('Ethio Seed Enterprise', 'ethioseed@example.com', '+251115556677', 'Mr. Alemayehu', 4.5, 'active'),
('Green Agro Chemicals', 'greenagro@example.com', '+251116667788', 'Ms. Selamawit', 4.2, 'active'),
('Farm Tools Ethiopia', 'farmtools@example.com', '+251117778899', 'Mr. Teklu', 4.7, 'active'),
('Organic Solutions PLC', 'organic@example.com', '+251118889900', 'Dr. Wondimu', 4.8, 'active');

-- Insert comprehensive products data (50+ products)
INSERT INTO products (name, description, price, category, brand, stock, status, featured, supplier_id, rating, total_reviews) VALUES
-- Cereal Seeds (10 products)
('Hybrid Maize Seeds BH-546', 'High yield hybrid maize, matures in 120-130 days, drought tolerant', 1850.00, 'Seeds', 'Pioneer', 500, 'active', 1, 1, 4.5, 45),
('Wheat Seeds Danda\'a', 'Improved bread wheat variety, resistant to rust diseases', 2200.00, 'Seeds', 'EIAR', 350, 'active', 1, 1, 4.3, 38),
('Teff Seeds DZ-Cr-387 (White)', 'High-yielding white teff variety, good for injera', 3200.00, 'Seeds', 'DZARC', 600, 'active', 1, 1, 4.7, 52),
('Barley Seeds (Hulled)', 'Six-row barley for high altitude areas, good for brewing', 1800.00, 'Seeds', 'Local', 400, 'active', 0, 1, 4.0, 28),
('Sorghum Seeds Macia', 'Drought-tolerant sorghum for dryland farming', 1650.00, 'Seeds', 'ICRISAT', 300, 'active', 0, 1, 4.2, 31),
('Millet Seeds (Pearl)', 'Fast maturing millet for low rainfall areas', 1550.00, 'Seeds', 'Local', 450, 'active', 0, 1, 4.1, 24),
('Rice Seeds (NERICA)', 'Upland rice variety, doesn\'t need flooding', 2800.00, 'Seeds', 'AfricaRice', 250, 'active', 1, 1, 4.4, 19),
('Oat Seeds (Forage)', 'High quality forage oats for animal feed', 1350.00, 'Seeds', 'Local', 550, 'active', 0, 1, 4.0, 22),
('Faba Bean Seeds (Highland)', 'High protein faba bean for rotation', 2450.00, 'Seeds', 'EIAR', 320, 'active', 0, 1, 4.3, 35),
('Field Pea Seeds', 'Improved field pea for soil fertility improvement', 2100.00, 'Seeds', 'Local', 380, 'active', 0, 1, 4.1, 29),

-- Vegetable Seeds (8 products)
('Tomato Seeds Roma VF', 'Determinate tomato for processing, disease resistant', 850.00, 'Seeds', 'East-West', 1200, 'active', 1, 1, 4.6, 67),
('Onion Seeds (Red Creole)', 'Red onion variety, good storage life', 920.00, 'Seeds', 'Local', 950, 'active', 0, 1, 4.2, 42),
('Cabbage Seeds (Copenhagen)', 'Early maturing cabbage, forms firm heads', 780.00, 'Seeds', 'Royal Sluis', 1100, 'active', 0, 1, 4.3, 38),
('Carrot Seeds (Nantes)', 'Sweet carrot variety, deep orange color', 650.00, 'Seeds', 'Local', 850, 'active', 0, 1, 4.1, 31),
('Green Pepper Seeds (California Wonder)', 'Bell pepper, thick fleshed, green to red', 890.00, 'Seeds', 'Seminis', 720, 'active', 1, 1, 4.5, 49),
('Potato Seed Tubers (Gudanie)', 'Certified potato seeds, resistant to late blight', 4500.00, 'Seeds', 'EIAR', 800, 'active', 1, 1, 4.7, 58),
('Lettuce Seeds (Iceberg)', 'Crisphead lettuce for salads', 520.00, 'Seeds', 'Local', 1300, 'active', 0, 1, 4.0, 26),
('Spinach Seeds (Fordhook Giant)', 'Large leaf spinach, heat tolerant', 480.00, 'Seeds', 'Local', 1400, 'active', 0, 1, 4.2, 33),

-- Chemical Fertilizers (8 products)
('DAP Fertilizer 18-46-0 (50kg)', 'Diammonium phosphate for root development', 4250.00, 'Fertilizers', 'Yara', 200, 'active', 1, 2, 4.8, 89),
('Urea Fertilizer 46-0-0 (50kg)', 'Nitrogen fertilizer for vegetative growth', 3950.00, 'Fertilizers', 'Chemical', 250, 'active', 1, 2, 4.7, 76),
('NPK 15-15-15 (50kg)', 'Complete balanced fertilizer for all crops', 4450.00, 'Fertilizers', 'Complex', 180, 'active', 1, 2, 4.9, 94),
('NPS Fertilizer 19-38-7 (50kg)', 'Blended fertilizer for Ethiopian soils', 4550.00, 'Fertilizers', 'Blend', 150, 'active', 0, 2, 4.6, 68),
('Potassium Sulfate 0-0-50 (25kg)', 'Potassium for fruit quality and disease resistance', 3850.00, 'Fertilizers', 'K2SO4', 120, 'active', 0, 2, 4.5, 57),
('Calcium Ammonium Nitrate (50kg)', 'Nitrogen + Calcium for acidic soils', 4150.00, 'Fertilizers', 'CAN', 170, 'active', 0, 2, 4.4, 62),
('Ammonium Sulfate 21-0-0 (50kg)', 'Nitrogen and sulfur source', 3750.00, 'Fertilizers', 'AS', 140, 'active', 0, 2, 4.3, 51),
('Single Super Phosphate (50kg)', 'Phosphorus and calcium source', 3250.00, 'Fertilizers', 'SSP', 160, 'active', 0, 2, 4.2, 48),

-- Organic Fertilizers (5 products)
('Vermicompost (50kg Bag)', 'Earthworm processed organic fertilizer', 1250.00, 'Fertilizers', 'Organic', 300, 'active', 1, 4, 4.9, 102),
('Farm Yard Manure (Cart Load)', 'Well-decomposed animal manure', 850.00, 'Fertilizers', 'Traditional', 500, 'active', 0, 4, 4.7, 88),
('Bone Meal (25kg)', 'Natural phosphorus source from bones', 1850.00, 'Fertilizers', 'Natural', 90, 'active', 0, 4, 4.6, 45),
('Fish Meal (25kg)', 'High nitrogen organic fertilizer', 2200.00, 'Fertilizers', 'Marine', 75, 'active', 1, 4, 4.8, 56),
('Compost (50kg Bag)', 'Municipal waste compost, rich in nutrients', 950.00, 'Fertilizers', 'Municipal', 400, 'active', 0, 4, 4.5, 72),

-- Insecticides (5 products)
('Cypermethrin 10% EC (1L)', 'Synthetic pyrethroid for insect control', 1150.00, 'Pesticides', 'Agrochemical', 150, 'active', 1, 2, 4.3, 64),
('Malathion 50% EC (1L)', 'Organophosphate for sucking pests', 980.00, 'Pesticides', 'Chemical', 180, 'active', 0, 2, 4.1, 52),
('Imidacloprid 17.8% SL (250ml)', 'Systemic insecticide for aphids and whiteflies', 1650.00, 'Pesticides', 'Bayer', 120, 'active', 1, 2, 4.5, 71),
('Lambda-cyhalothrin 5% EC (500ml)', 'Broad spectrum insecticide', 1350.00, 'Pesticides', 'Syngenta', 140, 'active', 0, 2, 4.4, 59),
('Chlorpyrifos 20% EC (1L)', 'For soil pests and termites', 1420.00, 'Pesticides', 'Dow', 110, 'active', 0, 2, 4.2, 47),

-- Fungicides (4 products)
('Carbendazim 50% WP (500g)', 'Systemic fungicide for many diseases', 1250.00, 'Pesticides', 'BASF', 130, 'active', 1, 2, 4.6, 68),
('Mancozeb 80% WP (1kg)', 'Protective fungicide for foliar diseases', 1450.00, 'Pesticides', 'Indofil', 160, 'active', 0, 2, 4.3, 55),
('Metalaxyl 35% WS (100g)', 'Seed treatment for damping off', 850.00, 'Pesticides', 'Syngenta', 200, 'active', 0, 2, 4.4, 61),
('Copper Oxychloride 50% WP (1kg)', 'Copper fungicide for bacterial diseases', 1650.00, 'Pesticides', 'Local', 95, 'active', 0, 2, 4.2, 49),

-- Herbicides (5 products)
('Glyphosate 41% SL (5L)', 'Non-selective systemic herbicide', 2250.00, 'Pesticides', 'Monsanto', 80, 'active', 1, 2, 4.7, 82),
('2,4-D Amine 72% (1L)', 'Selective herbicide for broadleaf weeds', 920.00, 'Pesticides', 'Dow', 220, 'active', 0, 2, 4.5, 73),
('Atrazine 50% WP (1kg)', 'Pre-emergence herbicide for maize', 1350.00, 'Pesticides', 'Syngenta', 100, 'active', 0, 2, 4.3, 58),
('Pendimethalin 30% EC (1L)', 'Pre-emergence herbicide for cereals', 1050.00, 'Pesticides', 'BASF', 140, 'active', 0, 2, 4.4, 62),
('Paraquat 20% SL (1L)', 'Contact herbicide for quick action', 890.00, 'Pesticides', 'Syngenta', 160, 'active', 0, 2, 4.1, 51),

-- Irrigation Equipment (5 products)
('Drip Irrigation Kit (1/4 Acre)', 'Complete drip system with filters and pipes', 18500.00, 'Equipment', 'Netafim', 25, 'active', 1, 3, 4.9, 34),
('Water Pump 3HP Diesel', 'Diesel engine pump for irrigation', 12500.00, 'Equipment', 'Koshin', 18, 'active', 1, 3, 4.7, 28),
('Manual Sprayer 16L', 'Knapsack sprayer with adjustable nozzle', 1250.00, 'Equipment', 'Matabi', 120, 'active', 0, 3, 4.5, 92),
('PVC Pipes (3 inch, 6m)', 'Irrigation pipes, UV resistant', 850.00, 'Equipment', 'Local', 300, 'active', 0, 3, 4.3, 67),
('Water Tank 5000L', 'Plastic water storage tank', 18500.00, 'Equipment', 'Rotocast', 15, 'active', 1, 3, 4.8, 41),

-- Hand Tools (5 products)
('Wheelbarrow (Heavy Duty)', 'Garden wheelbarrow 85L capacity', 2850.00, 'Equipment', 'Local', 80, 'active', 1, 3, 4.6, 78),
('Pruning Shears (Professional)', 'Bypass pruner for garden use', 650.00, 'Equipment', 'Fiskars', 200, 'active', 0, 3, 4.7, 85),
('Garden Hoe (Forged Steel)', 'Heavy duty garden hoe', 350.00, 'Equipment', 'Local', 400, 'active', 0, 3, 4.4, 112),
('Rake (16-tooth)', 'Garden rake for collecting debris', 420.00, 'Equipment', 'Local', 250, 'active', 0, 3, 4.3, 76),
('Shovel (Round Point)', 'Digging shovel with D-grip', 550.00, 'Equipment', 'True Temper', 180, 'active', 0, 3, 4.5, 89),

-- Animal Feed (5 products)
('Dairy Meal 18% CP (50kg)', 'Complete feed for dairy cows', 1850.00, 'Animal Feed', 'Mekonnen', 150, 'active', 1, 4, 4.8, 95),
('Broiler Starter Feed (50kg)', 'Feed for broiler chicks 0-3 weeks', 1650.00, 'Animal Feed', 'EthioChicken', 200, 'active', 0, 4, 4.6, 82),
('Layer Mash 16% CP (50kg)', 'Feed for laying hens', 1750.00, 'Animal Feed', 'Local', 180, 'active', 0, 4, 4.7, 78),
('Calf Starter (25kg)', 'Feed for young calves', 1250.00, 'Animal Feed', 'Mekonnen', 120, 'active', 0, 4, 4.5, 64),
('Salt Lick (Mineral Block)', 'Mineral supplement for livestock', 450.00, 'Animal Feed', 'Natural', 300, 'active', 0, 4, 4.4, 57);

-- Insert advisory articles (20 comprehensive guides)
-- Insert comprehensive chatbot responses for ALL products
INSERT INTO chatbot_responses (keyword, question_pattern, response, related_products, related_advisory) VALUES
-- MAIZE PRODUCTS
('hybrid maize bh-546', 'how to use|planting|growing|cultivation',
'**🌽 Hybrid Maize BH-546 Complete Guide:**\n\n**Planting Instructions:**\n• **Time:** Plant at onset of rains (June-July)\n• **Spacing:** 75cm between rows, 25cm between plants\n• **Depth:** 3-5 cm in moist soil\n• **Seed Rate:** 20-25 kg/ha (2-3 seeds per hole)\n\n**Fertilization:**\n• **At planting:** 100kg DAP per hectare\n• **30 days after:** 100kg Urea per hectare\n\n**Expected Results:**\n• **Yield:** 8-10 tons/hectare\n• **Maturity:** 120-130 days\n• **Profit:** 45,000-63,000 ETB per hectare\n\n**Product:** Hybrid Maize Seeds BH-546 (1,850 ETB/10kg)',
'1', '1'),

('maize seed rate', 'how much seed|seed per hectare|planting rate',
'**📊 Maize Seed Calculation:**\n\n**For 1 hectare (10,000 sq meters):**\n• **Spacing:** 75cm × 25cm\n• **Plants needed:** 53,333 plants\n• **Seed rate:** 20-25 kg\n• **Bags needed:** 2 bags (10kg each)\n• **Cost:** 3,700 ETB\n\n**For smaller areas:**\n• **1 acre:** 8-10 kg seed\n• **1/2 acre:** 4-5 kg seed\n• **1/4 acre:** 2-2.5 kg seed\n\n**Always plant 2-3 seeds per hole for better germination.**',
'1', '1'),

-- FERTILIZER PRODUCTS
('dap fertilizer', 'how to use dap|dap application|phosphorus fertilizer',
'**🌱 DAP Fertilizer (18-46-0) Guide:**\n\n**Application Rates:**\n• **Maize:** 100 kg/ha at planting\n• **Wheat:** 100 kg/ha before planting\n• **Teff:** 50-75 kg/ha\n• **Vegetables:** 75-100 kg/ha\n\n**How to Apply:**\n1. Broadcast evenly before final plowing\n2. OR apply in bands 5cm beside seed row\n3. **Never** place directly with seeds\n\n**Benefits:**\n• Strong root development\n• Early crop establishment\n• Better flowering and fruiting\n\n**Product:** DAP Fertilizer 18-46-0 (4,250 ETB/50kg)',
'21', '4'),

('urea fertilizer', 'when to apply urea|nitrogen fertilizer|top dressing',
'**💚 Urea Fertilizer (46-0-0) Guide:**\n\n**Application Timing:**\n• **Maize:** 30 days after planting\n• **Wheat:** 45 days after planting\n• **Teff:** 30 days after planting\n\n**Rates:**\n• **Maize:** 100 kg/ha at 30 days\n• Additional: 50 kg/ha at tasseling (optional)\n\n**Important Notes:**\n• Apply when soil is moist\n• Broadcast evenly between rows\n• Avoid contact with plant leaves\n• Incorporate lightly into soil\n\n**Product:** Urea Fertilizer 46-0-0 (3,950 ETB/50kg)',
'22', NULL),

('npk fertilizer', 'complete fertilizer|balanced fertilizer|15-15-15',
'**⚖️ NPK 15-15-15 Complete Guide:**\n\n**When to Use:**\n• For all crops needing balanced nutrition\n• When soil test shows deficiencies\n• For continuous cropping systems\n\n**Application Rates:**\n• **General:** 100-150 kg/ha\n• **Specific crops:** Adjust based on soil test\n\n**Benefits:**\n• Balanced N-P-K nutrition\n• Promotes overall plant health\n• Suitable for most Ethiopian soils\n\n**Product:** NPK 15-15-15 (4,450 ETB/50kg)',
'23', NULL),

-- TEF PRODUCTS
('teff seed', 'teff planting|teff cultivation|teff seed rate',
'**🌾 Teff Seed (DZ-Cr-387) Guide:**\n\n**Planting Method:**\n1. Prepare fine, level seedbed\n2. Broadcast seeds evenly\n3. Cover lightly with soil (1-2 cm)\n4. Roll or press for good contact\n\n**Seed Rate:** 25-30 kg per hectare\n\n**Fertilization:**\n• **NPS:** 100 kg/ha at planting\n• **Urea:** 50 kg/ha at 30 days\n\n**Expected Yield:** 1.5-2.5 tons/ha\n**Maturity:** 90-110 days\n\n**Product:** Teff Seeds DZ-Cr-387 (3,200 ETB)',
'3', NULL),

-- PESTICIDE PRODUCTS
('cypermethrin', 'insecticide use|pest control|spray mixing',
'**🐛 Cypermethrin 10% EC Safe Usage:**\n\n**Mixing Ratio:** 1ml per liter of water\n**Coverage:** 200-400L solution per hectare\n\n**Step-by-Step:**\n1. **Protection:** Wear gloves, mask, goggles\n2. **Mixing:** Add 10ml to 10L water tank\n3. **Timing:** Spray early morning or late evening\n4. **Safety:** Avoid flowering period (protect bees)\n5. **Harvest:** Wait 14 days after spraying\n\n**Target Pests:**\n• Maize stalk borer\n• Armyworms\n• Aphids\n• Whiteflies\n\n**Product:** Cypermethrin 10% EC (1,150 ETB/L)',
'31', '6'),

('carbendazim', 'fungicide|disease control|plant disease',
'**🍄 Carbendazim 50% WP Fungicide Guide:**\n\n**Target Diseases:**\n• Wheat rust\n• Maize leaf blight\n• Tomato early blight\n• Various fungal diseases\n\n**Application:**\n• **Rate:** 500g per hectare\n• **Mixing:** 2g per liter of water\n• **Frequency:** Every 10-14 days\n\n**Safety:**\n• Wear protective clothing\n• Wash hands after use\n• Store in cool, dry place\n\n**Product:** Carbendazim 50% WP (1,250 ETB/500g)',
'36', NULL),

-- IRRIGATION PRODUCTS
('drip irrigation', 'water saving|irrigation system|drip kit',
'**💧 Drip Irrigation Kit (1/4 Acre):**\n\n**Kit Includes:**\n• Main pipes and lateral pipes\n• Drip emitters\n• Filters and connectors\n• Installation manual\n\n**Benefits:**\n• Saves 40-60% water\n• Increases yield 20-40%\n• Reduces weed growth\n• Improfer fertilizer efficiency\n\n**Installation Time:** 1-2 days\n**Coverage:** 1,000 sq meters (1/4 acre)\n\n**Product:** Drip Irrigation Kit (18,500 ETB)',
'41', NULL),

('water pump', 'irrigation pump|water pumping|3hp pump',
'**🚰 Water Pump 3HP Diesel Guide:**\n\n**Specifications:**\n• **Power:** 3 Horsepower\n• **Fuel:** Diesel\n• **Coverage:** 2-3 hectares per day\n• **Fuel use:** 2-3 liters per hour\n\n**Installation:**\n1. Place on stable surface\n2. Connect suction pipe to water source\n3. Connect delivery pipe to irrigation system\n4. Prime pump before starting\n\n**Maintenance:**\n• Change oil every 100 hours\n• Clean filter regularly\n• Store fuel properly\n\n**Product:** Water Pump 3HP Diesel (12,500 ETB)',
'42', NULL),

-- TOOL PRODUCTS
('wheelbarrow', 'garden tools|farm equipment|transport',
'**🛒 Wheelbarrow (Heavy Duty) Guide:**\n\n**Specifications:**\n• **Capacity:** 85 liters\n• **Material:** Steel frame, rubber wheel\n• **Weight capacity:** 150 kg\n\n**Uses:**\n• Transporting soil and compost\n• Moving harvested crops\n• Carrying tools and equipment\n• Distributing fertilizer\n\n**Maintenance:**\n• Keep wheel inflated\n• Clean after use\n• Store in dry place\n• Lubricate moving parts\n\n**Product:** Wheelbarrow Heavy Duty (2,850 ETB)',
'46', NULL),

-- ANIMAL FEED PRODUCTS
('dairy meal', 'cattle feed|cow feed|animal nutrition',
'**🐄 Dairy Meal 18% CP Complete Guide:**\n\n**For:** Dairy cows in milk production\n\n**Feeding Rates:**\n• **High producers:** 4-6 kg per day\n• **Average producers:** 2-4 kg per day\n• **Dry cows:** 1-2 kg per day\n\n**Benefits:**\n• Increases milk production 20-30%\n• Improves milk quality\n• Better cow health\n• Reduced feed costs\n\n**Storage:** Keep in dry, cool place\n\n**Product:** Dairy Meal 18% CP (1,850 ETB/50kg)',
'51', NULL),

-- GENERAL QUESTIONS
('price list', 'how much|cost|price of',
'**💰 Current Product Prices:**\n\n**SEEDS (per unit):**\n• Maize BH-546: 1,850 ETB\n• Teff DZ-Cr-387: 3,200 ETB\n• Wheat Danda''a: 2,200 ETB\n• Tomato Roma: 850 ETB\n\n**FERTILIZERS (per 50kg):**\n• DAP 18-46-0: 4,250 ETB\n• Urea 46-0-0: 3,950 ETB\n• NPK 15-15-15: 4,450 ETB\n\n**PESTICIDES:**\n• Cypermethrin 10%: 1,150 ETB/L\n• Carbendazim 50%: 1,250 ETB/500g\n• Glyphosate 41%: 2,250 ETB/5L\n\n**EQUIPMENT:**\n• Drip Irrigation Kit: 18,500 ETB\n• Water Pump 3HP: 12,500 ETB\n• Wheelbarrow: 2,850 ETB\n\n*Contact for bulk discounts*',
NULL, NULL),

('contact help', 'call|phone|contact|expert',
'**📞 Expert Support Available:**\n\n**Phone Numbers:**\n• **Technical Support:** 0911-223-344\n• **Sales Inquiries:** 0922-334-455\n• **Emergency Help:** 0933-445-566\n\n**Service Hours:**\n• Monday-Friday: 8:30 AM - 5:30 PM\n• Saturday: 9:00 AM - 1:00 PM\n• Sunday: Emergency only\n\n**Email:** support@agrismarthub.com\n\n**Visit:** Nearest agricultural office for field assistance',
NULL, NULL);



-- Insert calculators
INSERT INTO calculators (name, slug, description, formula, inputs, outputs, category, featured) VALUES
('Fertilizer Calculator', 'fertilizer-calculator', 'Calculate fertilizer requirements based on soil test and crop needs',
'fertilizer_kg = (recommended_rate * area) / 100',
'{"area": "hectares", "crop": "text", "soil_type": "text", "target_yield": "tons"}',
'{"urea_kg": "number", "dap_kg": "number", "npk_kg": "number", "total_cost": "birr"}',
'Fertilization', 1),

('Seed Rate Calculator', 'seed-rate-calculator', 'Calculate seeds needed per hectare',
'seeds_kg = (plants_per_ha * 1000) / (germination_percentage * 1000)',
'{"area": "hectares", "spacing_row": "cm", "spacing_plant": "cm", "germination": "%"}',
'{"seeds_kg": "number", "plants_per_ha": "number", "cost": "birr"}',
'Planting', 1),

('Irrigation Water Calculator', 'irrigation-water-calculator', 'Calculate water requirements for crops',
'water_liters = (crop_water_need * area * 10000) / irrigation_efficiency',
'{"crop": "text", "area": "hectares", "growth_stage": "text", "soil_type": "text"}',
'{"water_m3": "number", "irrigation_hours": "number", "pump_size": "HP"}',
'Irrigation', 1),

('Pesticide Mix Calculator', 'pesticide-mix-calculator', 'Calculate pesticide mixing rates',
'chemical_ml = (recommended_rate * spray_volume) / 1000',
'{"pesticide": "text", "spray_volume": "liters", "area": "hectares"}',
'{"chemical_amount": "ml", "water_amount": "liters", "total_cost": "birr"}',
'Pest Control', 0),

('Profit Calculator', 'profit-calculator', 'Calculate farm profitability',
'profit = (yield * price) - total_costs',
'{"crop": "text", "area": "hectares", "expected_yield": "kg", "market_price": "birr/kg"}',
'{"total_revenue": "birr", "total_cost": "birr", "net_profit": "birr", "roi": "%"}',
'Economics', 1);

-- Insert price trends
INSERT INTO price_trends (product_name, market, price, unit, trend, change_percentage, recorded_date) VALUES
('Maize', 'Addis Ababa', 8500, 'Quintal', 'up', 5.2, CURDATE() - INTERVAL 1 DAY),
('Wheat', 'Addis Ababa', 9200, 'Quintal', 'stable', 0.5, CURDATE() - INTERVAL 1 DAY),
('Teff (White)', 'Addis Ababa', 18500, 'Quintal', 'up', 8.7, CURDATE() - INTERVAL 1 DAY),
('Potato', 'Addis Ababa', 3500, 'Quintal', 'down', -3.2, CURDATE() - INTERVAL 1 DAY),
('Tomato', 'Addis Ababa', 4200, 'Quintal', 'up', 12.5, CURDATE() - INTERVAL 1 DAY),
('Onion', 'Addis Ababa', 3800, 'Quintal', 'stable', 0.8, CURDATE() - INTERVAL 1 DAY);

-- Insert system settings
INSERT INTO settings (setting_key, setting_value, setting_group, data_type) VALUES
('site_name', 'Agri Smart Hub', 'general', 'string'),
('site_email', 'info@agrismarthub.com', 'general', 'string'),
('currency', 'ETB', 'general', 'string'),
('currency_symbol', 'Birr', 'general', 'string'),
('tax_rate', '15', 'commerce', 'integer'),
('shipping_cost', '200', 'commerce', 'integer'),
('free_shipping_threshold', '5000', 'commerce', 'integer'),
('featured_products_limit', '12', 'display', 'integer'),
('products_per_page', '24', 'display', 'integer'),
('order_confirmation_email', '1', 'email', 'boolean'),
('maintenance_mode', '0', 'system', 'boolean');

-- Insert sample order
INSERT INTO orders (order_number, user_id, total_amount, shipping_cost, final_amount, shipping_address, phone, payment_method, status) VALUES
('ORD-2024-001', 4, 12500.00, 200.00, 12700.00, 'Bole, Addis Ababa, Ethiopia', '+251914567890', 'cash_on_delivery', 'delivered');

INSERT INTO order_items (order_id, product_id, product_name, product_price, quantity, total_price) VALUES
(1, 1, 'Hybrid Maize Seeds BH-546', 1850.00, 5, 9250.00),
(1, 21, 'DAP Fertilizer 18-46-0 (50kg)', 4250.00, 1, 4250.00);

-- Insert sample cart items
INSERT INTO cart (user_id, product_id, quantity) VALUES
(4, 3, 2),
(4, 5, 1),
(4, 8, 3);

-- Insert sample reviews
INSERT INTO reviews (user_id, product_id, rating, title, comment, status) VALUES
(4, 1, 5, 'Excellent Maize Seeds', 'Germination rate was 95%, plants are very healthy', 'approved'),
(4, 21, 4, 'Good Fertilizer', 'Yield increased by 30% compared to last season', 'approved');


INSERT INTO notifications (user_id, type, title, message, link, read_status) VALUES
(1, 'contact', 'New Contact Message', 'You have received a new message from John Doe', 'contact_messages.php?view=1', 0),
(1, 'order', 'New Order Received', 'Order #12345 has been placed successfully', 'orders.php?view=12345', 0),
(0, 'system', 'System Update', 'The system will be undergoing maintenance tonight', '', 0),
(1, 'alert', 'Low Stock Alert', 'Product XYZ is running low on stock', 'products.php?id=10', 0);
-- ==============================================
-- VERIFICATION QUERIES (CORRECTED)
-- ==============================================

SELECT '✅ DATABASE SETUP COMPLETE' as setup_status;
SELECT '=================================' as line_separator;

SELECT '📊 DATA SUMMARY:' as summary_header;
SELECT 'Users:' as table_name, COUNT(*) as total_count FROM users
UNION ALL
SELECT 'Products:', COUNT(*) FROM products
UNION ALL
SELECT 'Categories:', COUNT(*) FROM categories
UNION ALL
SELECT 'Advisory Articles:', COUNT(*) FROM advisory
UNION ALL
SELECT 'Suppliers:', COUNT(*) FROM suppliers
UNION ALL
SELECT 'Orders:', COUNT(*) FROM orders;

SELECT '=================================' as line_separator;
SELECT '👥 ADMIN LOGIN DETAILS:' as login_header;
SELECT 'Username: admin' as field, 'Password: admin123' as value;

SELECT '=================================' as line_separator;
SELECT '📦 SAMPLE PRODUCTS BY CATEGORY:' as category_header;
SELECT category, COUNT(*) as product_count 
FROM products 
GROUP BY category 
ORDER BY product_count DESC;

SELECT '=================================' as line_separator;
SELECT '🚀 NEXT STEPS:' as steps_header;
SELECT '1. Update config.php with database credentials' as instruction
UNION ALL
SELECT '2. Visit http://localhost/agri-smart-hub' 
UNION ALL
SELECT '3. Login with admin/admin123 to access dashboard'
UNION ALL
SELECT '4. Browse products at /products.php'
UNION ALL
SELECT '5. Read advisory articles at /advisory.php';