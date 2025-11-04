-- ===============================================
-- БЫСТРАЯ ВСТАВКА ТЕСТОВЫХ ПОЛЬЗОВАТЕЛЕЙ
-- ===============================================

USE aqum_db;

-- Пароль для всех: password
-- Хеш: $2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi

INSERT INTO users (name, username, email, phone, password, age, interests, role, last_seen_at) VALUES
('Тест Юзер', 'test', 'test@test.com', NULL, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 25, 'programming,design', 'user', NOW()),
('Иван Петров', 'ivan', 'ivan@aqum.com', '+79991234567', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 32, 'languages,teaching', 'teacher', NOW()),
('Мария Смирнова', 'maria', 'maria@aqum.com', '+79997654321', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 28, 'programming,teaching', 'teacher', NOW()),
('Админ', 'admin', 'admin@aqum.com', NULL, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 30, 'programming,management', 'admin', NOW());

-- Проверяем
SELECT id, name, username, email, role FROM users;
