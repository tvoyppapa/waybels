# 📦 УСТАНОВКА AQUM

## 🚨 ЕСЛИ ОШИБКА "username not found"

### Выполни этот SQL (копируй в phpMyAdmin):

```sql
USE aqum_db;

-- Добавляем username
ALTER TABLE users ADD COLUMN username VARCHAR(50) NULL AFTER name;

-- Заполняем
UPDATE users SET username = CONCAT('user', id);

-- Делаем NOT NULL и UNIQUE
ALTER TABLE users MODIFY COLUMN username VARCHAR(50) NOT NULL;
CREATE UNIQUE INDEX unique_username ON users(username);

-- Добавляем theme
ALTER TABLE users ADD COLUMN theme VARCHAR(20) DEFAULT 'auto' AFTER bio;

-- Удаляем nickname
ALTER TABLE users DROP COLUMN nickname;

-- Проверяем
SELECT id, name, username, email FROM users;
```

**Или используй файл:**
```bash
mysql -u root -p aqum_db < ADD_USERNAME_SIMPLE.sql
```

---

## 🚨 ЕСЛИ ОШИБКА "assignments not found"

```sql
USE aqum_db;

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

---

## 📝 Полная установка с нуля

### 1. Создать БД
```bash
mysql -u root -p < RESET_DATABASE.sql
```

### 2. Добавить username
```bash
mysql -u root -p aqum_db < ADD_USERNAME_SIMPLE.sql
```

### 3. Вставить пользователей
```bash
mysql -u root -p aqum_db < QUICK_INSERT_USERS.sql
```

### 4. Включить mod_rewrite
```bash
sudo a2enmod rewrite
sudo systemctl restart apache2
```

### 5. Готово!
Откройте: `http://ваш-домен/auth.php`

---

## 🎯 Тестовые аккаунты

| Email | Username | Password |
|-------|----------|----------|
| test@test.com | test | password |
| ivan@aqum.com | ivan | password |
| maria@aqum.com | maria | password |

**Профили:**
- `/@test`
- `/@ivan`
- `/@maria`

---

## 🔧 Если что-то не работает

### URL не работают (/@username)
```bash
sudo a2enmod rewrite
sudo systemctl restart apache2
```

### Меню синее вместо белого
Очисти кеш браузера: `Ctrl+Shift+R`

### profile.php редиректит на feed
Обнови `profile.php` из репозитория

---

**Все работает!** 🚀
