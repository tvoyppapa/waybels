-- ===============================================
-- ИСПРАВЛЕНИЕ: Добавление username без ошибок
-- ===============================================

USE aqum_db;

-- Шаг 1: Удаляем nickname если есть
ALTER TABLE users DROP COLUMN IF EXISTS nickname;

-- Шаг 2: Добавляем username БЕЗ ограничений (если не существует)
SET @col_exists = 0;
SELECT COUNT(*) INTO @col_exists 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = 'aqum_db' 
AND TABLE_NAME = 'users' 
AND COLUMN_NAME = 'username';

-- Если колонки нет - добавляем
SET @query = IF(@col_exists = 0,
    'ALTER TABLE users ADD COLUMN username VARCHAR(50) NULL AFTER name',
    'SELECT "Column username already exists" as info'
);
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Шаг 3: Заполняем username для всех пользователей
UPDATE users 
SET username = LOWER(
    REPLACE(
        REPLACE(
            REPLACE(SUBSTRING_INDEX(COALESCE(email, CONCAT('user', id)), '@', 1), '.', ''),
            '-', ''
        ),
        ' ', ''
    )
)
WHERE username IS NULL OR username = '';

-- Шаг 4: Обрабатываем дубликаты (добавляем номер)
SET @row_number = 0;
UPDATE users u1
JOIN (
    SELECT id, 
           username,
           @row_number := IF(@username = username, @row_number + 1, 0) AS rn,
           @username := username
    FROM users
    ORDER BY username, id
) u2 ON u1.id = u2.id
SET u1.username = IF(u2.rn > 0, CONCAT(u2.username, u2.rn), u2.username);

-- Шаг 5: Теперь делаем NOT NULL и UNIQUE
ALTER TABLE users MODIFY username VARCHAR(50) NOT NULL;
ALTER TABLE users ADD UNIQUE KEY unique_username (username);

-- Шаг 6: Добавляем theme если нет
SET @theme_exists = 0;
SELECT COUNT(*) INTO @theme_exists 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = 'aqum_db' 
AND TABLE_NAME = 'users' 
AND COLUMN_NAME = 'theme';

SET @query2 = IF(@theme_exists = 0,
    'ALTER TABLE users ADD COLUMN theme VARCHAR(20) DEFAULT "auto" AFTER bio',
    'SELECT "Column theme already exists" as info'
);
PREPARE stmt2 FROM @query2;
EXECUTE stmt2;
DEALLOCATE PREPARE stmt2;

-- Проверяем результат
SELECT id, name, username, email, role FROM users ORDER BY id;

SELECT 'SUCCESS: username column added and filled!' as status;
