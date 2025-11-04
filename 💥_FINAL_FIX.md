# 💥 ФИНАЛЬНОЕ ИСПРАВЛЕНИЕ

## Проблема с правами? Используй этот SQL:

### ⚡ Копируй в phpMyAdmin по очереди:

```sql
USE aqum_db;
```

```sql
-- Шаг 1: Удаляем индексы (игнорируй ошибки)
ALTER TABLE users DROP INDEX username;
```

```sql
-- Шаг 2: Удаляем второй индекс (игнорируй ошибки)
ALTER TABLE users DROP INDEX unique_username;
```

```sql
-- Шаг 3: Заполняем username
UPDATE users SET username = CONCAT('user', id);
```

```sql
-- Шаг 4: Делаем NOT NULL
ALTER TABLE users MODIFY COLUMN username VARCHAR(50) NOT NULL;
```

```sql
-- Шаг 5: Добавляем UNIQUE
ALTER TABLE users ADD UNIQUE KEY unique_username (username);
```

```sql
-- Шаг 6: Добавляем theme (игнорируй если есть)
ALTER TABLE users ADD COLUMN theme VARCHAR(20) DEFAULT 'auto' AFTER bio;
```

```sql
-- Шаг 7: Создаем assignments
CREATE TABLE IF NOT EXISTS assignments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    teacher_id INT UNSIGNED NOT NULL,
    student_id INT UNSIGNED NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    due_date DATETIME NULL,
    status VARCHAR(20) DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

```sql
-- Шаг 8: Создаем stories
CREATE TABLE IF NOT EXISTS stories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    media_url VARCHAR(500) NOT NULL,
    media_type VARCHAR(20) DEFAULT 'image',
    caption TEXT NULL,
    views_count INT UNSIGNED DEFAULT 0,
    expires_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

```sql
-- Проверяем результат
SELECT id, name, username, email FROM users;
```

---

## 📝 Важно:
- **Игнорируй ошибки** на шагах 1, 2, 6 (если колонки/индексов нет)
- **Главное** чтобы шаги 3, 4, 5, 7, 8 выполнились

---

## ✅ После этого:
1. Вставь пользователей через `QUICK_INSERT_USERS.sql`
2. Обнови страницу feed.php
3. Все заработает!

**Выполняй по шагам!** 🚀
