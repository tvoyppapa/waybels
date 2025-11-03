-- ===============================================
-- Рабочая база данных для aqum
-- Готова к импорту
-- ===============================================

-- Создание базы данных
DROP DATABASE IF EXISTS aqum_db;
CREATE DATABASE aqum_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE aqum_db;

-- ===============================================
-- ОСНОВНЫЕ ТАБЛИЦЫ
-- ===============================================

-- Таблица пользователей
CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    nickname VARCHAR(50) NULL UNIQUE,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    interests ENUM('languages', 'programming', 'design', 'marketing', 'growth') NULL,
    role ENUM('user', 'teacher', 'admin') DEFAULT 'user',
    avatar VARCHAR(255) DEFAULT '/img/default-avatar.png',
    bio TEXT NULL,
    email_verified_at TIMESTAMP NULL,
    remember_token VARCHAR(100) NULL,
    last_seen_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_email (email),
    INDEX idx_nickname (nickname),
    INDEX idx_interests (interests),
    INDEX idx_role (role),
    INDEX idx_last_seen (last_seen_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблица профилей репетиторов
CREATE TABLE teacher_profiles (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL UNIQUE,
    subject VARCHAR(100) NOT NULL,
    description TEXT NULL,
    short_description VARCHAR(90) NULL COMMENT 'Короткое описание 30-90 символов',
    experience_years INT UNSIGNED DEFAULT 0,
    hourly_rate DECIMAL(10, 2) DEFAULT 0.00,
    rating DECIMAL(3, 2) DEFAULT 0.00,
    rating_count INT UNSIGNED DEFAULT 0,
    total_lessons INT UNSIGNED DEFAULT 0,
    total_students INT UNSIGNED DEFAULT 0,
    video_url VARCHAR(500) NULL,
    languages VARCHAR(255) NULL,
    certifications TEXT NULL,
    is_approved BOOLEAN DEFAULT FALSE,
    status ENUM('active', 'inactive', 'pending') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_subject (subject),
    INDEX idx_rating (rating),
    INDEX idx_status (status),
    INDEX idx_total_lessons (total_lessons)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблица постов
CREATE TABLE posts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    teacher_id INT UNSIGNED NOT NULL,
    title VARCHAR(255) NULL,
    content TEXT NOT NULL,
    media_type ENUM('none', 'image', 'video', 'mixed') DEFAULT 'none',
    media_urls JSON NULL,
    tags VARCHAR(500) NULL,
    likes_count INT UNSIGNED DEFAULT 0,
    comments_count INT UNSIGNED DEFAULT 0,
    views_count INT UNSIGNED DEFAULT 0,
    shares_count INT UNSIGNED DEFAULT 0,
    is_pinned BOOLEAN DEFAULT FALSE,
    is_published BOOLEAN DEFAULT TRUE,
    published_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_teacher (teacher_id),
    INDEX idx_published (is_published, published_at),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблица лайков постов
CREATE TABLE post_likes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    post_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_like (post_id, user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблица комментариев
CREATE TABLE post_comments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    post_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    parent_id INT UNSIGNED NULL,
    content TEXT NOT NULL,
    likes_count INT UNSIGNED DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (parent_id) REFERENCES post_comments(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблица каналов
CREATE TABLE channels (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    owner_id INT UNSIGNED NOT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT NULL,
    avatar VARCHAR(255) NULL,
    is_private BOOLEAN DEFAULT FALSE,
    members_count INT UNSIGNED DEFAULT 0,
    category ENUM('languages', 'programming', 'design', 'marketing', 'growth', 'other') NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблица избранного
CREATE TABLE favorites (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    teacher_id INT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_favorite (user_id, teacher_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблица взаимодействий (для рекомендаций)
CREATE TABLE user_interactions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    interaction_type ENUM('view', 'like', 'comment', 'share', 'search') NOT NULL,
    target_type ENUM('post', 'teacher', 'channel', 'group') NOT NULL,
    target_id INT UNSIGNED NOT NULL,
    metadata JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблица для OAuth
CREATE TABLE oauth_providers (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    provider VARCHAR(50) NOT NULL,
    provider_user_id VARCHAR(255) NOT NULL,
    access_token TEXT NULL,
    refresh_token TEXT NULL,
    expires_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_provider (provider, provider_user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблица для сброса пароля
CREATE TABLE password_resets (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    token VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_email (email),
    INDEX idx_token (token)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===============================================
-- ТЕСТОВЫЕ ДАННЫЕ
-- ===============================================

-- Пароль для всех: password
INSERT INTO users (name, nickname, email, password, interests, role, bio, last_seen_at) VALUES
('Иван Петров', 'ivan_teacher', 'ivan@aqum.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'languages', 'teacher', 'Преподаватель английского языка с 10-летним опытом', NOW()),
('Мария Смирнова', 'maria_prog', 'maria@aqum.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'programming', 'teacher', 'Full-stack разработчик и ментор', NOW()),
('Алексей Козлов', 'alex_design', 'alex@aqum.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'design', 'teacher', 'UI/UX дизайнер', NOW()),
('Анна Волкова', 'anna_student', 'anna@aqum.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'languages', 'user', 'Изучаю английский', NOW()),
('Тест Пользователь', 'test', 'test@test.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'programming', 'user', 'Тестовый аккаунт', NOW()),
('Админ', 'admin', 'admin@aqum.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'programming', 'admin', 'Администратор', NOW());

-- Профили репетиторов
INSERT INTO teacher_profiles (user_id, subject, description, short_description, experience_years, hourly_rate, rating, rating_count, total_lessons, total_students, status, is_approved) VALUES
(1, 'Английский язык', 'Помогу освоить английский с нуля или подготовиться к экзаменам. Использую коммуникативную методику.', 'Разговариваем с первого урока!', 10, 1500.00, 4.95, 87, 250, 45, 'active', TRUE),
(2, 'Программирование', 'Обучаю веб-разработке: HTML, CSS, JavaScript, React, Node.js.', 'Веб-разработка от А до Я', 5, 2000.00, 4.88, 56, 180, 32, 'active', TRUE),
(3, 'Дизайн', 'Научу создавать красивые интерфейсы в Figma.', 'UI/UX дизайн в Figma', 7, 1800.00, 4.92, 43, 120, 28, 'active', TRUE);

-- Тестовые посты
INSERT INTO posts (teacher_id, title, content, tags, likes_count, comments_count, views_count, is_published) VALUES
(1, '5 фраз для начала разговора', 'Сегодня разберем самые полезные фразы для начала беседы:\n\n1. How are you doing?\n2. What''s up?\n3. Nice to meet you!\n4. How''s it going?\n5. Long time no see!\n\nПрактикуйте их каждый день!', 'английский,разговорный,фразы', 45, 12, 320, TRUE),
(2, 'Основы React', 'React - это JavaScript библиотека для создания пользовательских интерфейсов.', 'программирование,react,javascript', 67, 23, 580, TRUE),
(3, 'Секреты UI дизайна', '3 принципа:\n\n✅ Простота\n✅ Контраст\n✅ Согласованность', 'дизайн,ui,tips', 89, 15, 720, TRUE);

-- ===============================================
-- Готово к использованию!
-- ===============================================
-- Импорт: mysql -u root -p < database_aqum_ready.sql
-- Тестовый логин: test@test.com
-- Пароль: password
