-- ===============================================
-- ФИНАЛЬНАЯ СТРУКТУРА БД (без phone, с username)
-- ===============================================

USE aqum_db;

-- Убеждаемся что таблица users правильная
DROP TABLE IF EXISTS users;

CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    age INT NULL,
    interests TEXT NULL,
    role VARCHAR(20) DEFAULT 'user',
    avatar VARCHAR(255) DEFAULT '/img/default-avatar.png',
    bio TEXT NULL,
    theme VARCHAR(20) DEFAULT 'auto',
    google_id VARCHAR(255) NULL UNIQUE,
    last_seen_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_email (email),
    INDEX idx_username (username),
    INDEX idx_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Вставляем тестовых пользователей
INSERT INTO users (name, username, email, password, age, interests, role, last_seen_at) VALUES
('Тест Юзер', 'test', 'test@test.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 25, 'programming,design', 'user', NOW()),
('Иван Петров', 'ivan', 'ivan@aqum.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 32, 'languages,teaching', 'teacher', NOW()),
('Мария Смирнова', 'maria', 'maria@aqum.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 28, 'programming,teaching', 'teacher', NOW()),
('Админ', 'admin', 'admin@aqum.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 30, 'programming,management', 'admin', NOW());

-- Проверяем
SELECT id, name, username, email, role FROM users;
