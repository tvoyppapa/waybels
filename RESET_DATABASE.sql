-- ===============================================
-- ПОЛНЫЙ СБРОС И СОЗДАНИЕ БД aqum
-- ===============================================

-- УДАЛЯЕМ СТАРУЮ БД
DROP DATABASE IF EXISTS aqum_db;
DROP DATABASE IF EXISTS wibs;
DROP DATABASE IF EXISTS waybels_db;

-- СОЗДАЕМ НОВУЮ БД
CREATE DATABASE aqum_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE aqum_db;

-- ===============================================
-- ТАБЛИЦЫ
-- ===============================================

-- Пользователи
CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    nickname VARCHAR(50) NULL,
    email VARCHAR(255) NULL UNIQUE,
    phone VARCHAR(20) NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    age INT NULL,
    interests TEXT NULL,
    role VARCHAR(20) DEFAULT 'user',
    avatar VARCHAR(255) DEFAULT '/img/default-avatar.png',
    bio TEXT NULL,
    google_id VARCHAR(255) NULL UNIQUE,
    last_seen_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Профили репетиторов
CREATE TABLE teacher_profiles (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL UNIQUE,
    subject VARCHAR(100) NOT NULL,
    description TEXT NULL,
    short_description VARCHAR(90) NULL,
    experience_years INT UNSIGNED DEFAULT 0,
    hourly_rate DECIMAL(10, 2) DEFAULT 0.00,
    rating DECIMAL(3, 2) DEFAULT 0.00,
    rating_count INT UNSIGNED DEFAULT 0,
    total_lessons INT UNSIGNED DEFAULT 0,
    total_students INT UNSIGNED DEFAULT 0,
    video_url VARCHAR(500) NULL,
    is_approved BOOLEAN DEFAULT FALSE,
    status VARCHAR(20) DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Посты
CREATE TABLE posts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    teacher_id INT UNSIGNED NOT NULL,
    title VARCHAR(255) NULL,
    content TEXT NOT NULL,
    media_type VARCHAR(20) DEFAULT 'none',
    media_urls TEXT NULL,
    tags VARCHAR(500) NULL,
    likes_count INT UNSIGNED DEFAULT 0,
    comments_count INT UNSIGNED DEFAULT 0,
    views_count INT UNSIGNED DEFAULT 0,
    is_published BOOLEAN DEFAULT TRUE,
    published_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Лайки
CREATE TABLE post_likes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    post_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_like (post_id, user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Избранное
CREATE TABLE favorites (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    teacher_id INT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_favorite (user_id, teacher_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Взаимодействия
CREATE TABLE user_interactions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    interaction_type VARCHAR(20) NOT NULL,
    target_type VARCHAR(20) NOT NULL,
    target_id INT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===============================================
-- ТЕСТОВЫЕ ДАННЫЕ
-- ===============================================

-- Пароль для всех: password
-- Хеш: $2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi

INSERT INTO users (name, nickname, email, phone, password, age, interests, role, last_seen_at) VALUES
('Тест Юзер', 'test', 'test@test.com', NULL, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 25, 'programming,design', 'user', NOW()),
('Иван Петров', 'ivan', 'ivan@aqum.com', '+79991234567', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 32, 'languages,teaching', 'teacher', NOW()),
('Мария Смирнова', 'maria', 'maria@aqum.com', '+79997654321', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 28, 'programming,teaching', 'teacher', NOW()),
('Админ', 'admin', 'admin@aqum.com', NULL, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 30, 'programming,management', 'admin', NOW());

-- Репетиторы
INSERT INTO teacher_profiles (user_id, subject, description, short_description, experience_years, hourly_rate, rating, rating_count, total_lessons, total_students, status, is_approved) VALUES
(2, 'Английский язык', 'Преподаватель английского с 10-летним опытом', 'Разговариваем с первого урока!', 10, 1500, 4.95, 87, 250, 45, 'active', TRUE),
(3, 'Программирование', 'Full-stack разработчик и ментор', 'Веб-разработка от А до Я', 5, 2000, 4.88, 56, 180, 32, 'active', TRUE);

-- Посты
INSERT INTO posts (teacher_id, title, content, tags, likes_count, comments_count, views_count) VALUES
(2, '5 фраз для разговора', 'How are you doing?\nWhat''s up?\nNice to meet you!', 'английский,фразы', 45, 12, 320),
(3, 'Основы React', 'React - библиотека для UI', 'react,javascript', 67, 23, 580);

-- ===============================================
-- ГОТОВО!
-- ===============================================
-- Тестовый аккаунт:
-- Email: test@test.com
-- Пароль: password
