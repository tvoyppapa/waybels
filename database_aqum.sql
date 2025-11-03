-- ===============================================
-- Схема базы данных для aqum
-- Образовательная платформа нового поколения
-- ===============================================

-- Создание базы данных
CREATE DATABASE IF NOT EXISTS aqum_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE aqum_db;

-- ===============================================
-- ТАБЛИЦЫ ПОЛЬЗОВАТЕЛЕЙ
-- ===============================================

-- Таблица пользователей
CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    nickname VARCHAR(50) NULL UNIQUE,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    interests ENUM('languages', 'programming', 'design', 'marketing', 'growth') NULL,
    role ENUM('user', 'teacher', 'admin') DEFAULT 'user',
    avatar VARCHAR(255) NULL,
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
    INDEX idx_last_seen (last_seen_at),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблица профилей репетиторов
CREATE TABLE IF NOT EXISTS teacher_profiles (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL UNIQUE,
    subject VARCHAR(100) NOT NULL,
    description TEXT NULL,
    short_description VARCHAR(90) NULL COMMENT 'Короткое описание 30-90 символов',
    experience_years INT UNSIGNED DEFAULT 0,
    hourly_rate DECIMAL(10, 2) DEFAULT 0.00,
    rating DECIMAL(3, 2) DEFAULT 0.00,
    rating_count INT UNSIGNED DEFAULT 0,
    total_lessons INT UNSIGNED DEFAULT 0 COMMENT 'Всего проведено уроков',
    total_students INT UNSIGNED DEFAULT 0 COMMENT 'Всего учеников',
    video_url VARCHAR(500) NULL,
    languages VARCHAR(255) NULL COMMENT 'Языки преподавания через запятую',
    certifications TEXT NULL COMMENT 'Сертификаты и квалификации',
    is_approved BOOLEAN DEFAULT FALSE,
    status ENUM('active', 'inactive', 'pending') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_subject (subject),
    INDEX idx_rating (rating),
    INDEX idx_hourly_rate (hourly_rate),
    INDEX idx_status (status),
    INDEX idx_total_lessons (total_lessons)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===============================================
-- ТАБЛИЦЫ КОНТЕНТА (ЛЕНТА)
-- ===============================================

-- Таблица постов
CREATE TABLE IF NOT EXISTS posts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    teacher_id INT UNSIGNED NOT NULL,
    title VARCHAR(255) NULL,
    content TEXT NOT NULL,
    media_type ENUM('none', 'image', 'video', 'mixed') DEFAULT 'none',
    media_urls JSON NULL COMMENT 'Массив URL медиафайлов',
    tags VARCHAR(500) NULL COMMENT 'Теги через запятую',
    likes_count INT UNSIGNED DEFAULT 0,
    comments_count INT UNSIGNED DEFAULT 0,
    views_count INT UNSIGNED DEFAULT 0,
    shares_count INT UNSIGNED DEFAULT 0,
    is_pinned BOOLEAN DEFAULT FALSE,
    is_published BOOLEAN DEFAULT TRUE,
    published_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_teacher (teacher_id),
    INDEX idx_published (is_published, published_at),
    INDEX idx_likes (likes_count),
    INDEX idx_views (views_count),
    INDEX idx_created (created_at),
    FULLTEXT idx_content (title, content, tags)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблица лайков постов
CREATE TABLE IF NOT EXISTS post_likes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    post_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_like (post_id, user_id),
    INDEX idx_post (post_id),
    INDEX idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблица комментариев
CREATE TABLE IF NOT EXISTS post_comments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    post_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    parent_id INT UNSIGNED NULL COMMENT 'Для ответов на комментарии',
    content TEXT NOT NULL,
    likes_count INT UNSIGNED DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (parent_id) REFERENCES post_comments(id) ON DELETE CASCADE,
    INDEX idx_post (post_id),
    INDEX idx_user (user_id),
    INDEX idx_parent (parent_id),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблица просмотров постов (для аналитики)
CREATE TABLE IF NOT EXISTS post_views (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    post_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NULL,
    ip_address VARCHAR(45) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_post (post_id),
    INDEX idx_user (user_id),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===============================================
-- ТАБЛИЦЫ ИСТОРИЙ (STORIES)
-- ===============================================

-- Таблица историй
CREATE TABLE IF NOT EXISTS stories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    media_type ENUM('image', 'video') NOT NULL,
    media_url VARCHAR(500) NOT NULL,
    thumbnail_url VARCHAR(500) NULL,
    content TEXT NULL COMMENT 'Текст истории',
    duration INT UNSIGNED DEFAULT 15 COMMENT 'Длительность в секундах',
    views_count INT UNSIGNED DEFAULT 0,
    expires_at TIMESTAMP NOT NULL COMMENT 'Истории исчезают через 24 часа',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_expires (expires_at),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблица просмотров историй
CREATE TABLE IF NOT EXISTS story_views (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    story_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (story_id) REFERENCES stories(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_view (story_id, user_id),
    INDEX idx_story (story_id),
    INDEX idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===============================================
-- ТАБЛИЦЫ КАНАЛОВ И ГРУПП
-- ===============================================

-- Таблица каналов
CREATE TABLE IF NOT EXISTS channels (
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
    
    FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_owner (owner_id),
    INDEX idx_category (category),
    INDEX idx_members (members_count)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблица подписчиков каналов
CREATE TABLE IF NOT EXISTS channel_subscribers (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    channel_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    is_muted BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (channel_id) REFERENCES channels(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_subscription (channel_id, user_id),
    INDEX idx_channel (channel_id),
    INDEX idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблица групп (для групповых занятий)
CREATE TABLE IF NOT EXISTS groups (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    teacher_id INT UNSIGNED NOT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT NULL,
    avatar VARCHAR(255) NULL,
    max_members INT UNSIGNED DEFAULT 20,
    current_members INT UNSIGNED DEFAULT 0,
    category ENUM('languages', 'programming', 'design', 'marketing', 'growth', 'other') NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_teacher (teacher_id),
    INDEX idx_category (category),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблица участников групп
CREATE TABLE IF NOT EXISTS group_members (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    group_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    role ENUM('member', 'moderator') DEFAULT 'member',
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (group_id) REFERENCES groups(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_membership (group_id, user_id),
    INDEX idx_group (group_id),
    INDEX idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===============================================
-- ТАБЛИЦЫ СООБЩЕНИЙ
-- ===============================================

-- Таблица чатов
CREATE TABLE IF NOT EXISTS chats (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    type ENUM('private', 'group', 'channel') DEFAULT 'private',
    name VARCHAR(100) NULL COMMENT 'Название для групповых чатов',
    avatar VARCHAR(255) NULL,
    last_message_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_type (type),
    INDEX idx_last_message (last_message_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблица участников чатов
CREATE TABLE IF NOT EXISTS chat_participants (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    chat_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    role ENUM('member', 'admin') DEFAULT 'member',
    is_muted BOOLEAN DEFAULT FALSE,
    last_read_at TIMESTAMP NULL,
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (chat_id) REFERENCES chats(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_participant (chat_id, user_id),
    INDEX idx_chat (chat_id),
    INDEX idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблица сообщений
CREATE TABLE IF NOT EXISTS messages (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    chat_id INT UNSIGNED NOT NULL,
    sender_id INT UNSIGNED NOT NULL,
    content TEXT NOT NULL,
    media_type ENUM('none', 'image', 'video', 'file', 'voice') DEFAULT 'none',
    media_url VARCHAR(500) NULL,
    reply_to_id INT UNSIGNED NULL COMMENT 'ID сообщения, на которое отвечают',
    is_edited BOOLEAN DEFAULT FALSE,
    is_pinned BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (chat_id) REFERENCES chats(id) ON DELETE CASCADE,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (reply_to_id) REFERENCES messages(id) ON DELETE SET NULL,
    INDEX idx_chat (chat_id),
    INDEX idx_sender (sender_id),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===============================================
-- ТАБЛИЦЫ УРОКОВ И ЗАДАНИЙ
-- ===============================================

-- Таблица уроков
CREATE TABLE IF NOT EXISTS lessons (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    teacher_id INT UNSIGNED NOT NULL,
    student_id INT UNSIGNED NOT NULL,
    group_id INT UNSIGNED NULL COMMENT 'NULL для индивидуальных уроков',
    title VARCHAR(255) NOT NULL,
    description TEXT NULL,
    scheduled_at TIMESTAMP NOT NULL,
    duration_minutes INT UNSIGNED DEFAULT 60,
    status ENUM('scheduled', 'in_progress', 'completed', 'cancelled') DEFAULT 'scheduled',
    is_trial BOOLEAN DEFAULT FALSE COMMENT 'Пробный урок',
    meeting_url VARCHAR(500) NULL COMMENT 'Ссылка на видеозвонок',
    notes TEXT NULL COMMENT 'Заметки после урока',
    rating INT UNSIGNED NULL COMMENT 'Оценка урока от 1 до 5',
    feedback TEXT NULL COMMENT 'Отзыв ученика',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (group_id) REFERENCES groups(id) ON DELETE SET NULL,
    INDEX idx_teacher (teacher_id),
    INDEX idx_student (student_id),
    INDEX idx_group (group_id),
    INDEX idx_scheduled (scheduled_at),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблица заданий
CREATE TABLE IF NOT EXISTS assignments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    teacher_id INT UNSIGNED NOT NULL,
    student_id INT UNSIGNED NOT NULL,
    lesson_id INT UNSIGNED NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT NULL,
    files JSON NULL COMMENT 'Прикрепленные файлы',
    due_date TIMESTAMP NULL,
    status ENUM('pending', 'in_progress', 'completed', 'overdue') DEFAULT 'pending',
    grade INT UNSIGNED NULL COMMENT 'Оценка от 1 до 100',
    feedback TEXT NULL COMMENT 'Комментарий от преподавателя',
    submitted_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (lesson_id) REFERENCES lessons(id) ON DELETE SET NULL,
    INDEX idx_teacher (teacher_id),
    INDEX idx_student (student_id),
    INDEX idx_lesson (lesson_id),
    INDEX idx_status (status),
    INDEX idx_due_date (due_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблица прогресса студентов
CREATE TABLE IF NOT EXISTS student_progress (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id INT UNSIGNED NOT NULL,
    teacher_id INT UNSIGNED NOT NULL,
    subject VARCHAR(100) NOT NULL,
    level VARCHAR(50) NULL COMMENT 'Уровень (A1, B1, Beginner, etc.)',
    completed_lessons INT UNSIGNED DEFAULT 0,
    total_assignments INT UNSIGNED DEFAULT 0,
    completed_assignments INT UNSIGNED DEFAULT 0,
    average_grade DECIMAL(5, 2) NULL,
    notes TEXT NULL COMMENT 'Заметки преподавателя о прогрессе',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_progress (student_id, teacher_id, subject),
    INDEX idx_student (student_id),
    INDEX idx_teacher (teacher_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===============================================
-- ТАБЛИЦЫ СИСТЕМЫ РЕКОМЕНДАЦИЙ
-- ===============================================

-- Таблица взаимодействий пользователя (для алгоритма рекомендаций)
CREATE TABLE IF NOT EXISTS user_interactions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    interaction_type ENUM('view', 'like', 'comment', 'share', 'search') NOT NULL,
    target_type ENUM('post', 'teacher', 'channel', 'group') NOT NULL,
    target_id INT UNSIGNED NOT NULL,
    metadata JSON NULL COMMENT 'Дополнительные данные',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_interaction (interaction_type),
    INDEX idx_target (target_type, target_id),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблица избранного
CREATE TABLE IF NOT EXISTS favorites (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    teacher_id INT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_favorite (user_id, teacher_id),
    INDEX idx_user (user_id),
    INDEX idx_teacher (teacher_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===============================================
-- ТАБЛИЦЫ ГЕЙМИФИКАЦИИ
-- ===============================================

-- Таблица достижений (бейджей)
CREATE TABLE IF NOT EXISTS achievements (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    description TEXT NULL,
    icon VARCHAR(100) NULL,
    points INT UNSIGNED DEFAULT 0,
    category ENUM('lessons', 'assignments', 'social', 'progress', 'special') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблица достижений пользователей
CREATE TABLE IF NOT EXISTS user_achievements (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    achievement_id INT UNSIGNED NOT NULL,
    earned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (achievement_id) REFERENCES achievements(id) ON DELETE CASCADE,
    UNIQUE KEY unique_achievement (user_id, achievement_id),
    INDEX idx_user (user_id),
    INDEX idx_achievement (achievement_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблица уровней и опыта пользователей
CREATE TABLE IF NOT EXISTS user_experience (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL UNIQUE,
    total_points INT UNSIGNED DEFAULT 0,
    level INT UNSIGNED DEFAULT 1,
    current_streak INT UNSIGNED DEFAULT 0 COMMENT 'Текущая серия дней активности',
    longest_streak INT UNSIGNED DEFAULT 0 COMMENT 'Самая длинная серия',
    last_activity_at TIMESTAMP NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_level (level),
    INDEX idx_points (total_points)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===============================================
-- СЛУЖЕБНЫЕ ТАБЛИЦЫ
-- ===============================================

-- Таблица для OAuth (Google и др.)
CREATE TABLE IF NOT EXISTS oauth_providers (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    provider VARCHAR(50) NOT NULL,
    provider_user_id VARCHAR(255) NOT NULL,
    access_token TEXT NULL,
    refresh_token TEXT NULL,
    expires_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_provider_user (provider, provider_user_id),
    INDEX idx_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблица для сброса пароля
CREATE TABLE IF NOT EXISTS password_resets (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    token VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_email (email),
    INDEX idx_token (token)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===============================================
-- ВСТАВКА ТЕСТОВЫХ ДАННЫХ
-- ===============================================

-- Тестовые пользователи
INSERT INTO users (name, nickname, email, password, interests, role, bio, last_seen_at) VALUES
('Иван Петров', 'ivan_teacher', 'ivan@aqum.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'languages', 'teacher', 'Преподаватель английского языка с 10-летним опытом', NOW()),
('Мария Смирнова', 'maria_prog', 'maria@aqum.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'programming', 'teacher', 'Full-stack разработчик и ментор', NOW()),
('Алексей Козлов', 'alex_design', 'alex@aqum.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'design', 'teacher', 'UI/UX дизайнер и преподаватель', NOW()),
('Анна Волкова', 'anna_student', 'anna@aqum.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'languages', 'user', 'Изучаю английский язык', NOW()),
('Админ', 'admin', 'admin@aqum.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'programming', 'admin', 'Администратор платформы aqum', NOW());

-- Профили репетиторов
INSERT INTO teacher_profiles (user_id, subject, description, short_description, experience_years, hourly_rate, rating, rating_count, total_lessons, total_students, status, is_approved) VALUES
(1, 'Английский язык', 'Помогу освоить английский с нуля или подготовиться к экзаменам. Использую коммуникативную методику - разговариваем с первого урока!', 'Разговариваем с первого урока!', 10, 1500.00, 4.95, 87, 250, 45, 'active', TRUE),
(2, 'Программирование', 'Обучаю веб-разработке: HTML, CSS, JavaScript, React, Node.js. Помогу с проектами и портфолио.', 'Веб-разработка от А до Я', 5, 2000.00, 4.88, 56, 180, 32, 'active', TRUE),
(3, 'Дизайн', 'Научу создавать красивые интерфейсы в Figma. Основы UX, композиция, типографика, прототипирование.', 'UI/UX дизайн в Figma', 7, 1800.00, 4.92, 43, 120, 28, 'active', TRUE);

-- Тестовые посты
INSERT INTO posts (teacher_id, title, content, media_type, tags, likes_count, comments_count, views_count, is_published, published_at) VALUES
(1, '5 фраз для начала разговора на английском', 'Сегодня разберем самые полезные фразы для начала беседы:\n\n1. How are you doing? - Как дела?\n2. What''s up? - Что нового?\n3. Nice to meet you! - Приятно познакомиться!\n4. How''s it going? - Как идут дела?\n5. Long time no see! - Давно не виделись!\n\nПрактикуйте их каждый день!', 'none', 'английский,разговорный,фразы', 45, 12, 320, TRUE, NOW()),
(2, 'Основы React для начинающих', 'React - это JavaScript библиотека для создания пользовательских интерфейсов. Начнем с компонентов:\n\n```jsx\nfunction Welcome() {\n  return <h1>Hello, World!</h1>;\n}\n```\n\nЭто ваш первый React компонент! Просто и понятно.', 'none', 'программирование,react,javascript', 67, 23, 580, TRUE, NOW()),
(3, 'Секреты хорошего UI дизайна', 'Три главных принципа:\n\n✅ Простота - не перегружайте интерфейс\n✅ Контраст - делайте важное заметным\n✅ Согласованность - используйте единый стиль\n\nПрименяйте их в каждом проекте!', 'none', 'дизайн,ui,tips', 89, 15, 720, TRUE, NOW());

-- Тестовые достижения
INSERT INTO achievements (code, name, description, icon, points, category) VALUES
('first_lesson', 'Первый урок', 'Завершите свой первый урок', '🎓', 10, 'lessons'),
('10_lessons', '10 уроков', 'Завершите 10 уроков', '📚', 50, 'lessons'),
('first_assignment', 'Первое задание', 'Выполните первое задание', '✏️', 10, 'assignments'),
('perfect_week', 'Идеальная неделя', 'Занимайтесь 7 дней подряд', '🔥', 100, 'progress'),
('social_butterfly', 'Социальная бабочка', 'Получите 50 лайков на постах', '🦋', 30, 'social');

-- Пароль для всех тестовых пользователей: password
