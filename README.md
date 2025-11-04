# 🚀 AQUM - Образовательная платформа

Современная платформа для онлайн обучения, объединяющая репетиторов и учеников.

## 🎯 Главные возможности

- **Лента** - посты и контент от репетиторов (как Threads/Instagram)
- **Чистые URL** - профили по @username
- **Темная тема** - адаптивное переключение
- **Задания** - система заданий от репетиторов
- **Поиск** - умный поиск репетиторов и контента
- **Сообщения** - чат с репетиторами
- **Белое меню** - компактный sidebar 80px

## 📦 Установка

### 1. База данных
```bash
# Создать БД и структуру
mysql -u root -p < RESET_DATABASE.sql

# Добавить username колонку
mysql -u root -p aqum_db < ADD_USERNAME_SIMPLE.sql

# Вставить тестовых пользователей
mysql -u root -p aqum_db < QUICK_INSERT_USERS.sql
```

### 2. Apache настройки
```bash
# Включить mod_rewrite
sudo a2enmod rewrite

# Перезагрузить Apache
sudo systemctl restart apache2
```

### 3. Проверка
Откройте: `http://ваш-домен/auth.php`

## 🎯 Тестовые аккаунты

| Email | Username | Password | URL |
|-------|----------|----------|-----|
| test@test.com | test | password | `/@test` |
| ivan@aqum.com | ivan | password | `/@ivan` |
| maria@aqum.com | maria | password | `/@maria` |

## 📂 Структура

```
/workspace/
├── auth.php              # Авторизация
├── feed.php              # Лента (главная)
├── profile.php           # Профиль (@username)
├── search.php            # Поиск
├── messages.php          # Сообщения
├── settings.php          # Настройки
├── includes/
│   ├── sidebar.php       # Компактное меню (80px)
│   ├── bottom_nav.php    # Мобильное меню + бургер
│   ├── header.php        # Хедер с dropdown
│   └── layout.php        # Общий layout
├── js/
│   ├── theme.js          # Переключатель темы
│   └── auth.js           # Авторизация
├── style/
│   ├── theme.css         # Темы (светлая/темная)
│   ├── dashboard.css     # Основные стили
│   └── auth.css          # Стили авторизации
└── api/
    └── like.php          # API лайков

SQL файлы:
├── RESET_DATABASE.sql           # Полная структура БД
├── ADD_USERNAME_SIMPLE.sql      # Добавить username
└── QUICK_INSERT_USERS.sql       # Тестовые пользователи
```

## 🎨 Дизайн

- **Sidebar:** 80px, белый фон, только иконки
- **Active:** Градиент синий (#667eea → #764ba2)
- **Бургер меню:** Поддержка, Тема, Настройки, Выйти
- **Feed:** Стиль Threads с заданиями
- **Profile:** @username URL

## 🔧 Конфигурация

Редактируй `config.php`:
```php
define('APP_NAME', 'aqum');
define('DB_NAME', 'aqum_db');
define('DASHBOARD_URL', 'feed.php');
```

## 📱 Чистые URL

`.htaccess` настроен для:
- `/@username` → профиль пользователя
- `/profile.php` → твой профиль

## 🌓 Темы

3 режима:
- **Авто** - по системе
- **Светлая** - белый фон
- **Темная** - темный фон

Переключение: кнопка в sidebar или бургер меню

## 🐛 Решение проблем

### URL не работают?
```bash
sudo a2enmod rewrite
sudo systemctl restart apache2
```

### Ошибка "username not found"?
```bash
mysql -u root -p aqum_db < ADD_USERNAME_SIMPLE.sql
```

### Ошибка "assignments not found"?
```bash
mysql -u root -p aqum_db < CREATE_MISSING_TABLES.sql
```

## 📚 Документация

- `💥_FINAL_FIX.md` - Исправление ошибок
- `🔥_FINALIZED.md` - Финальная версия
- `✅_NICKNAME_FIXED.md` - Исправление nickname

## 🚀 Готово!

Откройте `/@test` и наслаждайтесь! 🎉
