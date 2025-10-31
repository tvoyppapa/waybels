# 📦 Установка WayBels

## Быстрая установка (5 минут)

### Шаг 1: Импорт базы данных

**Через командную строку:**
```bash
mysql -u root -p
```

Затем:
```sql
source /path/to/workspace/database_waybels.sql
```

**Через phpMyAdmin:**
1. Откройте phpMyAdmin
2. Создайте новую базу данных `waybels_db`
3. Выберите её
4. Перейдите во вкладку "Импорт"
5. Выберите файл `database_waybels.sql`
6. Нажмите "Вперед"

### Шаг 2: Проверьте настройки подключения

Файл `db.php` должен содержать:
```php
$host = "localhost";
$dbname = "waybels_db";
$username = "root";
$password = "WayBels2553030App!";
```

Если ваш пароль MySQL другой, измените его!

### Шаг 3: Создайте необходимые папки

```bash
cd /workspace
mkdir -p img/avatars
chmod 755 img/avatars
```

### Шаг 4: Запустите сервер

**Вариант 1: Встроенный PHP сервер**
```bash
cd /workspace
php -S localhost:8000
```

Откройте: `http://localhost:8000`

**Вариант 2: Apache/Nginx**
1. Настройте виртуальный хост
2. DocumentRoot: `/workspace`
3. Убедитесь, что mod_rewrite включен

**Вариант 3: XAMPP/MAMP**
1. Скопируйте проект в `htdocs/waybels`
2. Откройте: `http://localhost/waybels`

### Шаг 5: Войдите в систему

Используйте тестовый аккаунт:
- **Email:** `test@example.com`
- **Пароль:** `password`

---

## 🎯 Проверка установки

### 1. Проверьте подключение к БД
Откройте `http://localhost:8000/db_test.php`

Создайте файл `/workspace/db_test.php`:
```php
<?php
require_once 'db.php';
try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $result = $stmt->fetch();
    echo "✅ Подключение успешно! Пользователей в БД: " . $result['count'];
} catch (Exception $e) {
    echo "❌ Ошибка: " . $e->getMessage();
}
?>
```

### 2. Проверьте таблицы
```sql
USE waybels_db;
SHOW TABLES;
```

Должны быть таблицы:
- users
- teacher_profiles
- posts
- favorites
- chats
- messages
- teacher_applications

### 3. Проверьте тестовые данные
```sql
SELECT * FROM users;
SELECT * FROM teacher_profiles;
```

Должно быть:
- 7 пользователей
- 5 репетиторов

---

## 🔧 Настройка для продакшена

### 1. Измените пароль БД
```sql
CREATE USER 'waybels_user'@'localhost' IDENTIFIED BY 'strong_password_here';
GRANT SELECT, INSERT, UPDATE, DELETE ON waybels_db.* TO 'waybels_user'@'localhost';
FLUSH PRIVILEGES;
```

Обновите `db.php`:
```php
$username = "waybels_user";
$password = "strong_password_here";
```

### 2. Отключите отображение ошибок
В `config.php`:
```php
error_reporting(0);
ini_set('display_errors', 0);
```

### 3. Включите HTTPS
В `.htaccess` раскомментируйте:
```apache
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

### 4. Настройте загрузку файлов
Создайте папки:
```bash
mkdir -p uploads/avatars uploads/videos uploads/documents
chmod 755 uploads
chmod 755 uploads/avatars
chmod 755 uploads/videos
chmod 755 uploads/documents
```

### 5. Настройте логирование
```php
// В config.php
ini_set('error_log', '/var/log/waybels/error.log');
```

---

## 🐛 Решение проблем

### Проблема: "Ошибка подключения к БД"
**Решение:**
1. Проверьте, запущен ли MySQL:
   ```bash
   sudo systemctl status mysql
   ```
2. Проверьте пароль в `db.php`
3. Убедитесь, что база `waybels_db` существует

### Проблема: "Call to undefined function password_verify()"
**Решение:**
Обновите PHP до версии 7.4+:
```bash
php -v
```

### Проблема: CSS/JS не загружаются
**Решение:**
1. Проверьте пути в файлах
2. Убедитесь, что папки `style/` и `js/` существуют
3. Проверьте права доступа:
   ```bash
   chmod 644 style/*.css
   chmod 644 js/*.js
   ```

### Проблема: Изображения не отображаются
**Решение:**
1. Создайте папку `img/`:
   ```bash
   mkdir -p img/avatars
   ```
2. Проверьте пути к изображениям
3. Убедитесь в наличии `img/logo-white.svg`

### Проблема: "Headers already sent"
**Решение:**
1. Уберите пробелы до `<?php`
2. Проверьте кодировку файлов (UTF-8 без BOM)
3. Не выводите ничего до `redirect()`

### Проблема: Сессия не работает
**Решение:**
1. Проверьте права на папку сессий:
   ```bash
   sudo chmod 1777 /tmp
   ```
2. Проверьте настройки PHP:
   ```php
   <?php phpinfo(); ?>
   ```
   Найдите `session.save_path`

---

## 📊 Проверочный чеклист

- [ ] MySQL запущен
- [ ] База `waybels_db` создана
- [ ] Таблицы импортированы (7 таблиц)
- [ ] Тестовые данные присутствуют
- [ ] PHP 7.4+ установлен
- [ ] Папка `img/avatars` создана
- [ ] Файл `db.php` настроен правильно
- [ ] Сервер запущен
- [ ] Можно войти через `test@example.com`
- [ ] Страница dashboard открывается
- [ ] Видны карточки репетиторов
- [ ] Можно добавить в избранное
- [ ] Можно открыть профиль

---

## 🎨 Кастомизация

### Изменить логотип:
Замените файлы:
- `/img/logo.svg` (цветной)
- `/img/logo-white.svg` (белый для sidebar)

### Изменить цвета:
В `/style/dashboard.css`:
```css
:root {
  --primary: #7F2CDF;        /* Ваш цвет */
  --primary-dark: #6A1FC9;   /* Темнее */
  --primary-light: #9851E8;  /* Светлее */
}
```

### Изменить название:
В `/config.php`:
```php
define('APP_NAME', 'Ваше название');
```

---

## 📞 Дополнительная помощь

### Логи ошибок:
```bash
# Apache
tail -f /var/log/apache2/error.log

# Nginx
tail -f /var/log/nginx/error.log

# PHP
tail -f /var/log/php_errors.log
```

### Отладка SQL:
```php
try {
    $stmt = $pdo->query("YOUR QUERY");
} catch (PDOException $e) {
    echo $e->getMessage();
    var_dump($stmt->errorInfo());
}
```

### Отладка сессий:
```php
<?php
session_start();
echo '<pre>';
print_r($_SESSION);
echo '</pre>';
?>
```

---

**Готово! Платформа WayBels установлена и готова к использованию! 🎉**
