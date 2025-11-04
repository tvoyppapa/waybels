-- ===============================================
-- AQUM - Экосистема для онлайн-репетиторов
-- База данных MySQL 8+ / InnoDB / utf8mb4
-- ===============================================

-- Создание базы данных
DROP DATABASE IF EXISTS aqum_db;
CREATE DATABASE aqum_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE aqum_db;

-- ===============================================
-- 0. Справочники
-- ===============================================

CREATE TABLE subjects (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(64) UNIQUE NOT NULL COMMENT 'english, math, design',
  name VARCHAR(128) NOT NULL COMMENT 'Английский язык, Математика',
  icon VARCHAR(32) NULL COMMENT 'Emoji иконка',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===============================================
-- 1. Пользователи
-- ===============================================

CREATE TABLE users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  username VARCHAR(50) NOT NULL UNIQUE COMMENT '@username для профиля',
  email VARCHAR(255) NULL UNIQUE,
  phone VARCHAR(32) NULL UNIQUE,
  country_code VARCHAR(8) NULL COMMENT '+7, +1',
  password VARCHAR(255) NOT NULL COMMENT 'BCrypt хеш',
  role ENUM('user','teacher','admin') DEFAULT 'user',
  is_teacher TINYINT(1) DEFAULT 0,
  is_verified_teacher TINYINT(1) DEFAULT 0 COMMENT 'Прошел верификацию админом',
  avatar VARCHAR(255) NULL COMMENT 'URL аватара, если NULL - показываем инициал',
  bio TEXT NULL,
  interests VARCHAR(512) NULL COMMENT 'Через запятую: english,math,design',
  theme ENUM('auto','dark','light') DEFAULT 'auto',
  google_id VARCHAR(255) NULL UNIQUE,
  apple_id VARCHAR(255) NULL UNIQUE,
  last_seen_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  INDEX idx_role (role),
  INDEX idx_last_seen (last_seen_at),
  INDEX idx_username (username),
  INDEX idx_is_teacher (is_teacher)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===============================================
-- 2. Профиль преподавателя и верификация
-- ===============================================

CREATE TABLE teacher_profiles (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL UNIQUE,
  headline VARCHAR(255) NULL COMMENT 'Краткое описание преподавателя',
  experience_years INT UNSIGNED NULL,
  works_with VARCHAR(255) NULL COMMENT 'дети, взрослые, корпорации',
  subjects VARCHAR(255) NULL COMMENT 'english,math',
  price_per_hour DECIMAL(10,2) NULL,
  timezone VARCHAR(64) NULL COMMENT 'Europe/Moscow',
  video_intro_url VARCHAR(255) NULL COMMENT 'YouTube ссылка',
  status ENUM('draft','submitted','approved','rejected') DEFAULT 'draft',
  review_comment VARCHAR(255) NULL COMMENT 'Комментарий админа при проверке',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE teacher_documents (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  doc_type VARCHAR(64) NOT NULL COMMENT 'passport, certificate, diploma',
  file_url VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===============================================
-- 3. Социальный модуль: посты/комменты/лайки/подписки
-- ===============================================

CREATE TABLE posts (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  teacher_id INT UNSIGNED NOT NULL,
  title VARCHAR(255) NULL,
  content TEXT NOT NULL,
  media_urls TEXT NULL COMMENT 'JSON массив URL медиафайлов',
  tags VARCHAR(255) NULL COMMENT 'Через запятую',
  is_published TINYINT(1) DEFAULT 1,
  likes_count INT UNSIGNED DEFAULT 0,
  comments_count INT UNSIGNED DEFAULT 0,
  views_count INT UNSIGNED DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_teacher_id (teacher_id),
  INDEX idx_created_at (created_at),
  INDEX idx_is_published (is_published)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE post_likes (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  post_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  
  UNIQUE KEY uniq_like (post_id, user_id),
  FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE post_comments (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  post_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  content TEXT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  
  FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_post_id (post_id),
  INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE user_interactions (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  target_user_id INT UNSIGNED NOT NULL,
  type ENUM('follow','block') NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  
  UNIQUE KEY uniq_inter (user_id, target_user_id, type),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (target_user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_type (type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===============================================
-- 4. Сообщения/каналы/сторис
-- ===============================================

CREATE TABLE channels (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  type ENUM('dialog','group') NOT NULL DEFAULT 'dialog',
  owner_id INT UNSIGNED NULL COMMENT 'Владелец группы',
  title VARCHAR(255) NULL,
  avatar VARCHAR(255) NULL,
  last_message_at TIMESTAMP NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  
  FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_type (type),
  INDEX idx_last_message_at (last_message_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE channel_members (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  channel_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  role ENUM('member','admin','owner') DEFAULT 'member',
  unread_count INT UNSIGNED DEFAULT 0,
  last_read_message_id INT UNSIGNED NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  
  UNIQUE KEY uniq_member (channel_id, user_id),
  FOREIGN KEY (channel_id) REFERENCES channels(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE messages (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  channel_id INT UNSIGNED NOT NULL,
  sender_id INT UNSIGNED NOT NULL,
  content TEXT NULL,
  media_urls TEXT NULL COMMENT 'JSON массив медиа',
  message_type ENUM('text','image','video','audio','file') DEFAULT 'text',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  
  FOREIGN KEY (channel_id) REFERENCES channels(id) ON DELETE CASCADE,
  FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_channel_id (channel_id),
  INDEX idx_sender_id (sender_id),
  INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE message_reads (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  message_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  read_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  
  UNIQUE KEY uniq_read (message_id, user_id),
  FOREIGN KEY (message_id) REFERENCES messages(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Сторис (показываются ТОЛЬКО в разделе "Сообщения")
CREATE TABLE stories (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  author_id INT UNSIGNED NOT NULL,
  media_url VARCHAR(255) NOT NULL,
  media_type ENUM('image','video') DEFAULT 'image',
  caption VARCHAR(255) NULL,
  expires_at DATETIME NOT NULL,
  views_count INT UNSIGNED DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  
  FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_author_id (author_id),
  INDEX idx_expires_at (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE story_views (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  story_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  viewed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  
  UNIQUE KEY uniq_view (story_id, user_id),
  FOREIGN KEY (story_id) REFERENCES stories(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===============================================
-- 5. Учебный модуль
-- ===============================================

CREATE TABLE lessons (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  teacher_id INT UNSIGNED NOT NULL,
  title VARCHAR(255) NOT NULL,
  description TEXT NULL,
  duration_minutes INT UNSIGNED NULL COMMENT 'Длительность урока',
  is_group TINYINT(1) DEFAULT 0,
  max_students INT UNSIGNED NULL COMMENT 'Макс. студентов для группового',
  price DECIMAL(10,2) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_teacher_id (teacher_id),
  INDEX idx_is_group (is_group)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Заказы уроков (для подсчета метрик)
CREATE TABLE lesson_orders (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  lesson_id INT UNSIGNED NOT NULL,
  teacher_id INT UNSIGNED NOT NULL,
  student_id INT UNSIGNED NOT NULL,
  scheduled_at DATETIME NULL,
  status ENUM('pending','paid','confirmed','completed','canceled') DEFAULT 'pending',
  price DECIMAL(10,2) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  FOREIGN KEY (lesson_id) REFERENCES lessons(id) ON DELETE CASCADE,
  FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_teacher_id (teacher_id),
  INDEX idx_student_id (student_id),
  INDEX idx_status (status),
  INDEX idx_scheduled_at (scheduled_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE assignments (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  lesson_id INT UNSIGNED NOT NULL,
  teacher_id INT UNSIGNED NOT NULL,
  student_id INT UNSIGNED NOT NULL,
  title VARCHAR(255) NOT NULL,
  description TEXT NULL,
  due_date DATETIME NULL,
  status ENUM('assigned','submitted','reviewed','completed') DEFAULT 'assigned',
  grade VARCHAR(32) NULL,
  teacher_comment TEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  FOREIGN KEY (lesson_id) REFERENCES lessons(id) ON DELETE CASCADE,
  FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_student_id (student_id),
  INDEX idx_teacher_id (teacher_id),
  INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE progress (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  student_id INT UNSIGNED NOT NULL,
  lesson_id INT UNSIGNED NOT NULL,
  progress_percent TINYINT UNSIGNED DEFAULT 0,
  last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  UNIQUE KEY uniq_stu_lesson (student_id, lesson_id),
  FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (lesson_id) REFERENCES lessons(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===============================================
-- 6. Платежи и транзакции
-- ===============================================

CREATE TABLE transactions (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL COMMENT 'Кто инициировал (обычно студент)',
  teacher_id INT UNSIGNED NULL COMMENT 'Получатель (учитель)',
  order_id INT UNSIGNED NULL,
  amount DECIMAL(10,2) NOT NULL,
  currency VARCHAR(8) DEFAULT 'USD',
  type ENUM('debit','credit','payout') NOT NULL,
  status ENUM('pending','succeeded','failed','refunded') DEFAULT 'pending',
  provider VARCHAR(32) NULL COMMENT 'stripe, yookassa, paypal',
  provider_txn_id VARCHAR(128) NULL,
  description TEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE SET NULL,
  FOREIGN KEY (order_id) REFERENCES lesson_orders(id) ON DELETE SET NULL,
  INDEX idx_user_id (user_id),
  INDEX idx_teacher_id (teacher_id),
  INDEX idx_status (status),
  INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Балансы учителей
CREATE TABLE teacher_balances (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  teacher_id INT UNSIGNED NOT NULL UNIQUE,
  balance DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  total_earned DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  total_withdrawn DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===============================================
-- 7. Отзывы, избранное, уведомления
-- ===============================================

CREATE TABLE reviews (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  teacher_id INT UNSIGNED NOT NULL,
  student_id INT UNSIGNED NOT NULL,
  order_id INT UNSIGNED NULL,
  rating TINYINT UNSIGNED NOT NULL COMMENT '1-5 звезд',
  comment TEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  
  FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (order_id) REFERENCES lesson_orders(id) ON DELETE SET NULL,
  UNIQUE KEY uniq_review (teacher_id, student_id, order_id),
  INDEX idx_teacher_id (teacher_id),
  INDEX idx_rating (rating)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE favorites (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  teacher_id INT UNSIGNED NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  
  UNIQUE KEY uniq_fav (user_id, teacher_id),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE notifications (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  type VARCHAR(64) NOT NULL COMMENT 'assignment, message, payment, review',
  title VARCHAR(255) NOT NULL,
  payload JSON NULL,
  link_url VARCHAR(255) NULL,
  is_read TINYINT(1) DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_user_id (user_id),
  INDEX idx_is_read (is_read),
  INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE settings (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL UNIQUE,
  locale VARCHAR(16) DEFAULT 'ru',
  theme ENUM('auto','dark','light') DEFAULT 'auto',
  notifications_email TINYINT(1) DEFAULT 1,
  notifications_push TINYINT(1) DEFAULT 1,
  
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===============================================
-- 8. Онбординг и антифрод
-- ===============================================

CREATE TABLE onboarding_tasks (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  task_key VARCHAR(64) NOT NULL COMMENT 'complete_profile, find_tutor, attach_card',
  is_done TINYINT(1) DEFAULT 0,
  done_at TIMESTAMP NULL DEFAULT NULL,
  
  UNIQUE KEY uniq_user_task (user_id, task_key),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE ip_guard (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  ip VARBINARY(16) NOT NULL COMMENT 'IPv4/6 в бинарном виде',
  action ENUM('register','login') NOT NULL,
  user_id INT UNSIGNED NULL,
  user_agent VARCHAR(255) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  
  INDEX idx_ip (ip),
  INDEX idx_action (action),
  INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===============================================
-- 9. Вставка тестовых данных (SEED)
-- ===============================================

-- Справочник предметов
INSERT INTO subjects (code, name, icon) VALUES
('english', 'Английский язык', '🇬🇧'),
('math', 'Математика', '🔢'),
('programming', 'Программирование', '💻'),
('design', 'Дизайн', '🎨'),
('music', 'Музыка', '🎵'),
('physics', 'Физика', '⚛️'),
('chemistry', 'Химия', '🧪'),
('biology', 'Биология', '🧬');

-- Пароль для всех: password
-- Хеш: $2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi

-- Тестовые пользователи
INSERT INTO users (name, username, email, phone, password, role, is_teacher, is_verified_teacher, bio, interests, theme) VALUES
-- Обычные пользователи
('Алексей Иванов', 'alexey', 'student1@aqum.com', '+79991234567', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', 0, 0, 'Студент, изучаю английский и программирование', 'english,programming', 'dark'),
('Мария Петрова', 'maria', 'student2@aqum.com', '+79991234568', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', 0, 0, 'Хочу научиться играть на гитаре', 'music', 'auto'),

-- Преподаватели
('Анна Смирнова', 'anna_english', 'teacher1@aqum.com', '+79991234569', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'teacher', 1, 1, 'Преподаватель английского языка с 7-летним опытом. IELTS, TOEFL, разговорная практика.', 'english', 'dark'),
('Дмитрий Козлов', 'dmitry_math', 'teacher2@aqum.com', '+79991234570', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'teacher', 1, 1, 'Репетитор по математике. Подготовка к ЕГЭ, ОГЭ. Более 500 учеников.', 'math', 'dark'),
('Елена Волкова', 'elena_design', 'teacher3@aqum.com', '+79991234571', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'teacher', 1, 1, 'UX/UI дизайнер. Работала в Яндекс, Сбер. Научу создавать крутые интерфейсы.', 'design', 'light'),
('Сергей Новиков', 'sergey_code', 'teacher4@aqum.com', '+79991234572', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'teacher', 1, 1, 'Fullstack разработчик. Python, JavaScript, React. 8+ лет опыта.', 'programming', 'dark'),
('Ольга Морозова', 'olga_music', 'teacher5@aqum.com', '+79991234573', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'teacher', 1, 1, 'Преподаватель музыки. Гитара, вокал. Выпускница консерватории.', 'music', 'auto'),

-- Администратор
('Админ Главный', 'admin', 'admin@aqum.com', '+79991234574', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 0, 0, 'Администратор платформы AQUM', NULL, 'dark');

-- Профили преподавателей
INSERT INTO teacher_profiles (user_id, headline, experience_years, works_with, subjects, price_per_hour, timezone, video_intro_url, status) VALUES
(3, 'Английский от A1 до C2. IELTS/TOEFL эксперт', 7, 'дети, взрослые, корпорации', 'english', 2500.00, 'Europe/Moscow', 'https://www.youtube.com/embed/dQw4w9WgXcQ', 'approved'),
(4, 'Математика 5-11 класс. ЕГЭ на 100 баллов', 10, 'школьники, абитуриенты', 'math', 3000.00, 'Europe/Moscow', 'https://www.youtube.com/embed/dQw4w9WgXcQ', 'approved'),
(5, 'UX/UI дизайн для начинающих и продвинутых', 5, 'студенты, взрослые', 'design', 2800.00, 'Europe/Moscow', 'https://www.youtube.com/embed/dQw4w9WgXcQ', 'approved'),
(6, 'Python, JavaScript, React - с нуля до профи', 8, 'студенты, взрослые, начинающие разработчики', 'programming', 3500.00, 'Europe/Moscow', 'https://www.youtube.com/embed/dQw4w9WgXcQ', 'approved'),
(7, 'Гитара и вокал. От азов до выступлений', 6, 'дети, взрослые', 'music', 2000.00, 'Europe/Moscow', 'https://www.youtube.com/embed/dQw4w9WgXcQ', 'approved');

-- Посты преподавателей
INSERT INTO posts (teacher_id, title, content, tags, likes_count, comments_count, views_count) VALUES
(3, '5 фраз для уверенного общения на английском', 'Сегодня разберем фразы, которые помогут вам чувствовать себя увереннее в разговоре с носителями языка. Сохраняйте!\n\n1. "That makes sense" - Это имеет смысл\n2. "I see what you mean" - Понимаю, что вы имеете в виду\n3. "Could you elaborate on that?" - Не могли бы вы уточнить?\n4. "I''d like to add..." - Я хотел бы добавить...\n5. "Let me think about it" - Дайте мне подумать', 'english,learning,tips', 234, 45, 1823),
(4, 'Топ-3 ошибки при решении задач ЕГЭ', 'Разберем самые частые ошибки, которые делают выпускники на экзамене по математике. Избегайте их!\n\n❌ Ошибка 1: Невнимательность при чтении условия\n❌ Ошибка 2: Потеря знака при преобразовании\n❌ Ошибка 3: Пропуск проверки ОДЗ\n\nБудьте внимательны! 💯', 'math,ege,tips', 456, 89, 3421),
(5, 'Как создать первый прототип в Figma за 30 минут', 'Пошаговый гайд для новичков в UX/UI дизайне. Сегодня создадим простое мобильное приложение!\n\n🎨 Шаг 1: Создайте фреймы для мобильных экранов\n🎨 Шаг 2: Добавьте компоненты (кнопки, инпуты)\n🎨 Шаг 3: Настройте навигацию\n🎨 Шаг 4: Добавьте интерактивность\n\nПолное видео - в профиле!', 'design,figma,tutorial', 892, 156, 5234),
(6, 'Создаем Telegram бота на Python за 15 минут', 'Простой туториал для начинающих разработчиков. Код в комментариях!\n\n```python\nimport telebot\nbot = telebot.TeleBot("YOUR_TOKEN")\n\n@bot.message_handler(commands=["start"])\ndef start(message):\n    bot.send_message(message.chat.id, "Привет!")\n\nbot.polling()\n```\n\nЗапускайте и пробуйте! 🚀', 'programming,python,bot', 1234, 234, 8921);

-- Уроки
INSERT INTO lessons (teacher_id, title, description, duration_minutes, is_group, max_students, price) VALUES
(3, 'Разговорный английский A1-A2', 'Индивидуальные занятия для начинающих. Разберем основы грамматики и разговорные фразы.', 60, 0, NULL, 2500.00),
(3, 'Подготовка к IELTS', 'Интенсивный курс подготовки к экзамену IELTS. Все секции: Listening, Reading, Writing, Speaking.', 90, 0, NULL, 3500.00),
(4, 'Математика 10-11 класс', 'Подготовка к ЕГЭ по математике профильного уровня. Решаем сложные задачи.', 60, 0, NULL, 3000.00),
(5, 'Основы UX/UI дизайна', 'Групповой курс для начинающих дизайнеров. Figma, принципы дизайна, создание портфолио.', 90, 1, 10, 1500.00),
(6, 'Python для начинающих', 'Изучаем Python с нуля. От переменных до создания первого проекта.', 60, 0, NULL, 3500.00);

-- Заказы уроков (для метрик)
INSERT INTO lesson_orders (lesson_id, teacher_id, student_id, scheduled_at, status, price) VALUES
-- Анна (teacher_id=3) провела 15 уроков с 8 уникальными учениками
(1, 3, 1, '2024-11-01 10:00:00', 'completed', 2500.00),
(1, 3, 1, '2024-11-03 10:00:00', 'completed', 2500.00),
(1, 3, 2, '2024-11-02 14:00:00', 'completed', 2500.00),
(2, 3, 2, '2024-11-05 16:00:00', 'completed', 3500.00),
(1, 3, 1, '2024-11-08 10:00:00', 'completed', 2500.00),
(1, 3, 2, '2024-11-10 14:00:00', 'completed', 2500.00),
(2, 3, 1, '2024-11-12 11:00:00', 'completed', 3500.00),
(1, 3, 1, '2024-11-15 10:00:00', 'completed', 2500.00),
(1, 3, 2, '2024-11-17 14:00:00', 'completed', 2500.00),
(2, 3, 2, '2024-11-19 16:00:00', 'completed', 3500.00),
(1, 3, 1, '2024-11-22 10:00:00', 'completed', 2500.00),
(1, 3, 1, '2024-11-24 10:00:00', 'confirmed', 2500.00),
(1, 3, 2, '2024-11-26 14:00:00', 'paid', 2500.00),
(2, 3, 1, '2024-11-28 11:00:00', 'paid', 3500.00),
(1, 3, 2, '2024-11-30 14:00:00', 'pending', 2500.00),

-- Дмитрий (teacher_id=4) провел 12 уроков
(3, 4, 1, '2024-11-01 12:00:00', 'completed', 3000.00),
(3, 4, 1, '2024-11-04 12:00:00', 'completed', 3000.00),
(3, 4, 2, '2024-11-06 15:00:00', 'completed', 3000.00),
(3, 4, 1, '2024-11-09 12:00:00', 'completed', 3000.00),
(3, 4, 2, '2024-11-11 15:00:00', 'completed', 3000.00),
(3, 4, 1, '2024-11-14 12:00:00', 'completed', 3000.00),
(3, 4, 2, '2024-11-16 15:00:00', 'completed', 3000.00),
(3, 4, 1, '2024-11-20 12:00:00', 'completed', 3000.00),
(3, 4, 2, '2024-11-23 15:00:00', 'completed', 3000.00),
(3, 4, 1, '2024-11-25 12:00:00', 'completed', 3000.00),
(3, 4, 2, '2024-11-27 15:00:00', 'confirmed', 3000.00),
(3, 4, 1, '2024-11-29 12:00:00', 'paid', 3000.00);

-- Отзывы
INSERT INTO reviews (teacher_id, student_id, order_id, rating, comment) VALUES
(3, 1, 1, 5, 'Отличный преподаватель! За месяц занятий я значительно улучшил разговорный английский. Анна объясняет очень доступно.'),
(3, 2, 3, 5, 'Анна - профессионал своего дела. Помогла подготовиться к IELTS, получил 7.5 баллов!'),
(4, 1, 16, 5, 'Дмитрий - лучший репетитор по математике! Сдал ЕГЭ на 96 баллов благодаря его урокам.'),
(4, 2, 18, 4, 'Хороший преподаватель, объясняет понятно. Иногда немного быстро, но в целом все отлично.');

-- Избранное
INSERT INTO favorites (user_id, teacher_id) VALUES
(1, 3),
(1, 4),
(1, 5),
(2, 3),
(2, 6);

-- Задания
INSERT INTO assignments (lesson_id, teacher_id, student_id, title, description, due_date, status) VALUES
(1, 3, 1, 'Написать эссе на тему "My favorite book"', 'Напишите небольшое эссе (150-200 слов) о вашей любимой книге. Используйте Past Simple и Present Perfect.', '2024-11-05 23:59:59', 'completed'),
(3, 4, 1, 'Решить 10 задач на производные', 'Решите задачи из учебника, стр. 145, номера 1-10. Покажите полное решение.', '2024-11-08 23:59:59', 'submitted'),
(5, 6, 2, 'Создать простой калькулятор на Python', 'Напишите программу-калькулятор с функциями сложения, вычитания, умножения и деления.', '2024-11-12 23:59:59', 'assigned');

-- Уведомления
INSERT INTO notifications (user_id, type, title, payload, is_read) VALUES
(1, 'assignment', 'Новое задание по английскому', '{"assignment_id": 1, "teacher": "Анна Смирнова"}', 0),
(1, 'message', 'Новое сообщение от Анны', '{"channel_id": 1, "sender": "Анна Смирнова"}', 1),
(2, 'assignment', 'Задание по программированию готово к проверке', '{"assignment_id": 3, "teacher": "Сергей Новиков"}', 0);

-- Настройки пользователей
INSERT INTO settings (user_id, locale, theme, notifications_email, notifications_push) VALUES
(1, 'ru', 'dark', 1, 1),
(2, 'ru', 'auto', 1, 1),
(3, 'ru', 'dark', 1, 1),
(4, 'ru', 'dark', 1, 0);

-- Онбординг задачи для нового студента
INSERT INTO onboarding_tasks (user_id, task_key, is_done, done_at) VALUES
(1, 'complete_profile', 1, '2024-11-01 10:00:00'),
(1, 'find_tutor', 1, '2024-11-01 10:30:00'),
(1, 'attach_card', 0, NULL),
(1, 'book_first_lesson', 1, '2024-11-01 11:00:00');

-- ===============================================
-- ГОТОВО! База данных AQUM создана успешно!
-- ===============================================
