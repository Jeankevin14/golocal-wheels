-- 001: first version of the GoLocal Wheels database
-- Safe to run on a database created by the old install.php (IF NOT EXISTS).

CREATE TABLE IF NOT EXISTS admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(60) NOT NULL UNIQUE,
    pass_hash VARCHAR(255) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS shops (
    id INT AUTO_INCREMENT PRIMARY KEY,
    city VARCHAR(20) NOT NULL,
    name VARCHAR(120) NOT NULL,
    area VARCHAR(120) NOT NULL,
    lat DECIMAL(9,6) NOT NULL,
    lng DECIMAL(9,6) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    whatsapp VARCHAR(20) DEFAULT '',
    owner_name VARCHAR(80) DEFAULT '',
    open_time CHAR(5) NOT NULL DEFAULT '08:00',
    close_time CHAR(5) NOT NULL DEFAULT '20:00',
    rating DECIMAL(2,1) DEFAULT NULL,
    status ENUM('pending','approved','hidden') NOT NULL DEFAULT 'pending',
    is_sample TINYINT(1) NOT NULL DEFAULT 0,
    notes TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX (city, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS bikes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    shop_id INT NOT NULL,
    type VARCHAR(20) NOT NULL,
    model VARCHAR(80) NOT NULL,
    price_day INT NOT NULL,
    price_hour INT NOT NULL DEFAULT 0,
    deposit INT NOT NULL DEFAULT 0,
    qty INT NOT NULL DEFAULT 1,
    FOREIGN KEY (shop_id) REFERENCES shops(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(12) NOT NULL UNIQUE,
    shop_id INT NOT NULL,
    bike_id INT DEFAULT NULL,
    bike_model VARCHAR(80) NOT NULL,
    customer_name VARCHAR(80) NOT NULL,
    customer_phone VARCHAR(20) NOT NULL,
    pickup_date DATE NOT NULL,
    days INT NOT NULL,
    helmet TINYINT(1) NOT NULL DEFAULT 0,
    price_day INT NOT NULL,
    total INT NOT NULL,
    status ENUM('new','confirmed','completed','cancelled') NOT NULL DEFAULT 'new',
    admin_note VARCHAR(255) DEFAULT '',
    ip VARCHAR(45) DEFAULT '',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX (status), INDEX (created_at),
    FOREIGN KEY (shop_id) REFERENCES shops(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
