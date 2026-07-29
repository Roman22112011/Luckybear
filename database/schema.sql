CREATE DATABASE IF NOT EXISTS luckybear_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE luckybear_db;

CREATE TABLE settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE games (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    icon VARCHAR(10) DEFAULT '🎰',
    rtp DECIMAL(5,2) DEFAULT 96.00,
    provider VARCHAR(100),
    category ENUM('slots', 'live', 'crash') DEFAULT 'slots',
    is_active TINYINT(1) DEFAULT 1,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    avatar VARCHAR(10) DEFAULT '👤',
    stars TINYINT DEFAULT 5,
    text TEXT NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE pages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(50) NOT NULL UNIQUE,
    title VARCHAR(255) NOT NULL,
    content TEXT,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE tracking (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event VARCHAR(50) NOT NULL,
    data JSON,
    page_url VARCHAR(500),
    ip_address VARCHAR(45),
    user_agent TEXT,
    device VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_event (event),
    INDEX idx_created (created_at)
) ENGINE=InnoDB;

INSERT INTO settings (setting_key, setting_value) VALUES
('register_link', '#'),
('play_link', '#');

INSERT INTO games (name, icon, rtp, provider, category, sort_order) VALUES
('Gates of Olympus', '🏛️', 96.50, 'Pragmatic Play', 'slots', 1),
('Sweet Bonanza', '🍬', 96.51, 'Pragmatic Play', 'slots', 2),
('Book of Dead', '📖', 96.21, 'Play''n GO', 'slots', 3),
('Aviator', '✈️', 97.00, 'Spribe', 'crash', 4),
('Crazy Time', '🎡', 96.08, 'Evolution', 'live', 5),
('Big Bamboo', '🐼', 96.13, 'Push Gaming', 'slots', 6);

INSERT INTO reviews (name, avatar, stars, text, sort_order) VALUES
('Мария К.', '👩‍🦰', 5, 'Регистрировалась ради фриспинов, а в итоге подняла x1200 в Gates of Olympus. Вывела за 5 минут!', 1),
('Алексей В.', '👨', 5, 'Медведь реально щедрый. Закинул 1000 рублей, играл в Aviator. Вывод пришел на карту мгновенно.', 2),
('Игорь L.', '🧔', 5, 'Лучший саппорт! Помогли разобраться с вейджером за пару минут. Все честно и прозрачно.', 3);

INSERT INTO pages (slug, title, content) VALUES
('terms', 'Правила и условия', '<h2>Правила и условия</h2><p>Добро пожаловать в LuckyBear Casino.</p>'),
('privacy', 'Политика конфиденциальности', '<h2>Конфиденциальность</h2><p>Мы защищаем ваши данные.</p>'),
('responsible', 'Ответственная игра', '<h2>Ответственная игра</h2><p>Играйте ответственно.</p>'),
('faq', 'FAQ', '<h2>Частые вопросы</h2><p>Ответы на вопросы.</p>');
