-- 002: call notes for shops; a shop goes on the website only after a call is logged and approved

CREATE TABLE IF NOT EXISTS shop_comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    shop_id INT NOT NULL,
    note TEXT NOT NULL,
    approved TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX (shop_id),
    FOREIGN KEY (shop_id) REFERENCES shops(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE shops ADD COLUMN IF NOT EXISTS verified_at DATETIME NULL DEFAULT NULL;
