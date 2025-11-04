# 🚀 Инструкция по созданию базы данных WayBels

## 📋 О проекте

**WayBels** - современная платформа для поиска репетиторов с богатым функционалом.

### Основные возможности:
- 👨‍🎓 Поиск и фильтрация репетиторов
- 💬 Система сообщений
- ⭐ Избранное и лайки
- 📊 Рейтинги и отзывы
- 🎥 Видео-презентации репетиторов
- 📱 Адаптивный дизайн (Desktop + Mobile)
- 🎨 Современный UI с Glass morphism эффектами

---

## ⚙️ Настройка базы данных

### Вариант 1: Через командную строку MySQL

```bash
# 1. Войдите в MySQL
mysql -u root -p

# 2. Введите пароль: WayBels2553030App!

# 3. Выполните команды:
DROP DATABASE IF EXISTS waybels_db;
DROP DATABASE IF EXISTS wibs;
DROP DATABASE IF EXISTS aqum_db;

# 4. Выйдите и импортируйте схему
exit

# 5. Импортируйте базу данных
mysql -u root -p < /workspace/database_waybels.sql
```

### Вариант 2: Через phpMyAdmin

1. Откройте **phpMyAdmin** (обычно http://localhost/phpmyadmin)
2. Войдите с учетными данными:
   - Пользователь: `root`
   - Пароль: `WayBels2553030App!`
3. Удалите старые базы данных (если есть):
   - `waybels_db`
   - `wibs`
   - `aqum_db`
4. Нажмите **Импорт**
5. Выберите файл `/workspace/database_waybels.sql`
6. Нажмите **Вперёд**

### Вариант 3: Через PHP скрипт

```bash
# Запустите подготовленный скрипт
php /workspace/setup_database.php
```

---

## 📊 Структура базы данных

После создания БД `waybels_db` будут созданы следующие таблицы:

### 1. **users** - Пользователи
- id, name, email, password
- google_id, apple_id (для OAuth)
- username, phone, avatar, bio
- interests (enum: languages, programming, design, marketing, growth, math, science, music, other)
- role (enum: user, teacher, admin)
- is_verified, created_at, updated_at, last_login_at

### 2. **teacher_profiles** - Профили репетиторов
- id, user_id
- subject, description
- experience_years, hourly_rate
- video_url
- total_lessons, rating, rating_count
- is_approved, status (pending, active, inactive, rejected)
- created_at, updated_at

### 3. **posts** - Посты репетиторов
- id, teacher_id
- title, content
- media_type (image, video, none), media_url
- likes_count, views_count
- created_at, updated_at

### 4. **favorites** - Избранные репетиторы
- id, user_id, teacher_id
- created_at

### 5. **chats** - Чаты
- id, user1_id, user2_id
- last_message, last_message_at
- created_at

### 6. **messages** - Сообщения
- id, chat_id, sender_id
- message, is_read
- created_at

### 7. **teacher_applications** - Заявки на репетиторство
- id, user_id
- subject, description
- experience_years, education, certificates
- video_url
- status (pending, approved, rejected)
- admin_comment
- created_at, updated_at

---

## 👥 Тестовые аккаунты

После импорта базы данных будут доступны следующие аккаунты:

### Обычный пользователь:
- 📧 Email: `test@example.com`
- 🔑 Пароль: `password`

### Репетиторы:

1. **Анна Смирнова** - Английский язык
   - 📧 Email: `anna@example.com`
   - 🔑 Пароль: `password`
   - ⭐ Рейтинг: 4.9 (47 отзывов)
   - 📚 Уроков: 156

2. **Дмитрий Козлов** - Математика
   - 📧 Email: `dmitry@example.com`
   - 🔑 Пароль: `password`
   - ⭐ Рейтинг: 4.95 (89 отзывов)
   - 📚 Уроков: 289

3. **Елена Волкова** - UX/UI Дизайн
   - 📧 Email: `elena@example.com`
   - 🔑 Пароль: `password`
   - ⭐ Рейтинг: 4.85 (34 отзыва)
   - 📚 Уроков: 92

4. **Сергей Новиков** - Python программирование
   - 📧 Email: `sergey@example.com`
   - 🔑 Пароль: `password`
   - ⭐ Рейтинг: 4.92 (67 отзывов)
   - 📚 Уроков: 203

5. **Мария Иванова** - Фортепиано
   - 📧 Email: `maria@example.com`
   - 🔑 Пароль: `password`
   - ⭐ Рейтинг: 4.88 (123 отзыва)
   - 📚 Уроков: 345

### Администратор:
- 📧 Email: `admin@waybels.com`
- 🔑 Пароль: `password`

---

## 🔧 Конфигурация

### Параметры подключения к БД

В файлах `config.php` и `db.php` используются следующие параметры:

```php
// config.php
define('DB_HOST', 'localhost');
define('DB_NAME', 'waybels_db');
define('DB_USER', 'root');
define('DB_PASS', 'WayBels2553030App!');
define('DB_CHARSET', 'utf8mb4');

// db.php
$host = "localhost";
$dbname = "waybels_db";
$username = "root";
$password = "WayBels2553030App!";
```

**⚠️ ИСПРАВЛЕНО:** Раньше в `db.php` было `$dbname = "wibs"` - теперь исправлено на `waybels_db`!

---

## 🚀 Запуск проекта

### 1. Создайте базу данных (см. инструкции выше)

### 2. Создайте необходимые папки

```bash
mkdir -p /workspace/img/avatars
chmod 755 /workspace/img/avatars
```

### 3. Запустите веб-сервер

#### Вариант A: Встроенный сервер PHP
```bash
cd /workspace
php -S localhost:8000
```

#### Вариант B: Apache/Nginx
Настройте виртуальный хост на папку `/workspace`

### 4. Откройте в браузере

```
http://localhost:8000
```

Или ваш настроенный URL для Apache/Nginx

---

## ✅ Проверка установки

### Проверка БД через MySQL:

```sql
-- Войдите в MySQL
mysql -u root -p

-- Проверьте базу данных
USE waybels_db;
SHOW TABLES;

-- Проверьте пользователей
SELECT email, role FROM users;

-- Проверьте репетиторов
SELECT u.name, tp.subject, tp.rating 
FROM users u 
INNER JOIN teacher_profiles tp ON u.id = tp.user_id;
```

Должно быть:
- ✅ 7 пользователей
- ✅ 5 репетиторов
- ✅ 4 поста
- ✅ 3 избранных

### Проверка через браузер:

1. Откройте главную страницу
2. Должен показаться квиз выбора интересов
3. Войдите через тестовый аккаунт (`test@example.com` / `password`)
4. Должна открыться лента с репетиторами

---

## 📁 Структура файлов проекта

```
/workspace/
├── 📄 index.php                    # Главная (вход/регистрация)
├── 📄 auth.php                     # Альтернативная страница входа
├── 📄 dashboard.php                # Лента репетиторов ⭐
├── 📄 teacher.php                  # Страница репетитора
├── 📄 profile.php                  # Профиль пользователя
├── 📄 messages.php                 # Сообщения
├── 📄 favorites.php                # Избранное
├── 📄 apply_teacher.php            # Заявка на репетитора
├── 📄 logout.php                   # Выход
│
├── 🔧 config.php                   # Конфигурация
├── 🔧 db.php                       # Подключение к БД (ИСПРАВЛЕНО ✅)
├── 🔧 helpers.php                  # Вспомогательные функции
├── 🔧 auth_handler.php             # Логика аутентификации
│
├── 🗄️ database_waybels.sql         # СХЕМА БАЗЫ ДАННЫХ ⭐
├── 🗄️ database.sql                 # Старая схема (Wibs)
├── 🗄️ setup_database.php           # Скрипт создания БД
│
├── 📁 includes/
│   ├── sidebar.php                 # Боковое меню (Desktop)
│   └── bottom_nav.php              # Нижнее меню (Mobile)
│
├── 📁 style/
│   ├── auth.css                    # Аутентификация
│   ├── dashboard.css               # Основные стили
│   ├── teacher.css                 # Страница репетитора
│   ├── profile.css                 # Профиль
│   └── messages.css                # Сообщения
│
├── 📁 js/
│   ├── auth.js                     # Аутентификация
│   ├── dashboard.js                # Лента
│   ├── teacher.js                  # Страница репетитора
│   └── profile.js                  # Профиль
│
├── 📁 img/
│   ├── logo.svg                    # Логотип цветной
│   ├── logo-white.svg              # Логотип белый
│   └── default-avatar.png          # Аватар по умолчанию
│
└── 📁 Документация/
    ├── README.md
    ├── WAYBELS_README.md
    ├── PROJECT_SUMMARY.md
    ├── INSTALLATION.md
    └── SETUP_INSTRUCTIONS.md       # Этот файл ⭐
```

---

## 🎯 Основные возможности проекта

### Для учеников:
- ✅ Регистрация с выбором интересов (квиз)
- ✅ Поиск репетиторов по предмету/имени
- ✅ Фильтрация по интересам
- ✅ Сортировка (рейтинг, уроки, новые)
- ✅ Просмотр видео-презентаций
- ✅ Добавление в избранное (AJAX)
- ✅ Система сообщений
- ✅ Управление профилем
- ✅ Загрузка аватара

### Для репетиторов:
- ✅ Подача заявки на репетиторство
- ✅ Создание профиля
- ✅ Добавление видео (YouTube)
- ✅ Публикация постов
- ✅ Статистика (рейтинг, уроки, опыт)
- ✅ Получение сообщений

### Безопасность:
- ✅ CSRF токены
- ✅ Prepared Statements (SQL injection защита)
- ✅ Password hashing (BCrypt)
- ✅ XSS защита (htmlspecialchars)
- ✅ Валидация данных
- ✅ Безопасные сессии

---

## 🎨 Дизайн

### Цветовая схема:
- **Primary:** `#7F2CDF` (фирменный фиолетовый)
- **Primary Dark:** `#6A1FC9`
- **Primary Light:** `#9851E8`
- **Background:** `#F5F5F7`
- **Surface:** `#FFFFFF`

### Особенности:
- ✅ Адаптивная верстка (Desktop + Mobile)
- ✅ Glass morphism для мобильного меню
- ✅ Плавные анимации
- ✅ Градиенты и тени
- ✅ SVG иконки
- ✅ Современный шрифт Inter (Google Fonts)

---

## 🐛 Решение проблем

### Проблема: "Не могу подключиться к БД"

**Решение:**
1. Убедитесь, что MySQL запущен
2. Проверьте пароль: `WayBels2553030App!`
3. Проверьте, что БД `waybels_db` создана
4. Проверьте настройки в `db.php` и `config.php`

### Проблема: "База данных не найдена"

**Решение:**
```bash
mysql -u root -p < /workspace/database_waybels.sql
```

### Проблема: "Страницы не загружаются"

**Решение:**
1. Проверьте, что веб-сервер запущен
2. Проверьте права доступа к файлам: `chmod 644 *.php`
3. Проверьте логи ошибок PHP

### Проблема: "Ошибка входа"

**Решение:**
1. Проверьте, что БД создана правильно
2. Используйте тестовый аккаунт: `test@example.com` / `password`
3. Проверьте логи в консоли браузера (F12)

---

## 📞 Дополнительная информация

### Требования:
- PHP 7.4+
- MySQL 5.7+
- Apache/Nginx
- mod_rewrite (для красивых URL)
- GD Library (для загрузки изображений)

### Рекомендации:
- Используйте HTTPS в продакшене
- Измените пароль БД
- Отключите отображение ошибок в продакшене
- Настройте резервное копирование БД

---

## ✅ Чеклист установки

- [ ] MySQL установлен и запущен
- [ ] База данных `waybels_db` создана из `database_waybels.sql`
- [ ] Файл `db.php` настроен правильно (используется `waybels_db`)
- [ ] Файл `config.php` настроен правильно
- [ ] Папка `/workspace/img/avatars` создана с правами 755
- [ ] Веб-сервер запущен и настроен
- [ ] Тестовый вход работает (`test@example.com` / `password`)
- [ ] Лента репетиторов отображается
- [ ] Поиск работает
- [ ] Избранное работает (AJAX)

---

🎉 **Готово! База данных создана, проект настроен!**

**WayBels** - Connecting Students with Great Teachers! 🎓✨
