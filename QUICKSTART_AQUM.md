# 🚀 Быстрый старт aqum

## Что изменилось?

**WayBels** → **aqum** 

Новая навигация:
- 🏠 **Лента** (feed.php) - главная страница с постами
- 🔍 **Поиск** (search.php) - универсальный поиск
- 💬 **Сообщения** (messages.php) - чаты и каналы
- ⚙️ **Профиль** (profile.php) - настройки

---

## 📦 Установка (5 минут)

### 1. Импорт базы данных

```bash
mysql -u root -p < database_aqum.sql
```

### 2. Проверить config.php

```php
define('DB_NAME', 'aqum_db');
```

### 3. Готово! 🎉

Откройте `index.php` в браузере.

**Тестовые аккаунты:**
- Email: `admin@aqum.com`
- Пароль: `password`

---

## 🎨 Использование Layout

### Создание новой страницы:

```php
<?php
session_start();
$page_title = "Моя страница";
require_once 'includes/layout.php';
?>

<div>
    <h1>Мой контент</h1>
</div>

<?php require_once 'includes/layout_footer.php'; ?>
```

Меню появится **автоматически**!

### Параметры:

```php
$page_title = "Заголовок";      // Название страницы
$page_css = ['custom.css'];     // Доп. стили
$page_js = ['custom.js'];       // Доп. скрипты
$compact = true;                // Компактное меню (только иконки)
$hide_nav = true;               // Скрыть нижнее меню (мобильные)
$show_back = true;              // Показать кнопку "Назад"
$back_url = '/previous.php';   // URL для возврата
```

---

## 🔥 Ключевые фичи

### 1. Умная лента (feed.php)
- ✅ Персонализированные рекомендации
- ✅ Лайки, комментарии, шаринг
- ✅ Фильтрация неактивных репетиторов (>24ч)

### 2. Поиск (search.php)
- ✅ Поиск по репетиторам, постам, каналам
- ✅ Smart Match - умный подбор
- ✅ Фильтры: категория, цена, рейтинг
- ✅ Рейтинг только при >10 уроках

### 3. Адаптивность
- 🖥️ Desktop: боковое меню
- 📱 Mobile: нижнее меню
- ✨ Компактный режим для чатов

---

## 📁 Основные файлы

```
/workspace/
├── feed.php           → Лента (главная)
├── search.php         → Поиск
├── database_aqum.sql  → База данных
└── includes/
    ├── layout.php     → Шаблон (начало)
    ├── layout_footer.php → Шаблон (конец)
    ├── sidebar.php    → Боковое меню
    ├── bottom_nav.php → Нижнее меню
    └── header.php     → Хедер с профилем
```

---

## 💡 Примеры

### Обычная страница:
```php
$page_title = "О нас";
require_once 'includes/layout.php';
// контент
require_once 'includes/layout_footer.php';
```

### Чат (компактное меню):
```php
$page_title = "Чат";
$compact = true;
require_once 'includes/layout.php';
// контент
require_once 'includes/layout_footer.php';
```

### Видеозвонок (без меню):
```php
$page_title = "Звонок";
$compact = true;
$hide_nav = true;
require_once 'includes/layout.php';
// контент
require_once 'includes/layout_footer.php';
```

---

## 🎯 Что дальше?

1. ✅ Импортируйте базу данных
2. ✅ Откройте `feed.php` - увидите ленту
3. ✅ Попробуйте `search.php` - умный поиск
4. ✅ Используйте layout для новых страниц

**Готово! Платформа работает! 🚀**

---

## 📖 Документация

- **Полная документация:** `AQUM_TRANSFORMATION.md`
- **Использование layout:** `LAYOUT_USAGE.md`
- **Пример страницы:** `includes/layout_example.php`

---

## 🆘 Частые вопросы

**Q: Как добавить новую страницу в меню?**
A: Отредактируйте `includes/sidebar.php` и `includes/bottom_nav.php`

**Q: Как изменить цвета?**
A: В `style/dashboard.css` есть CSS переменные в `:root`

**Q: Где логика рекомендаций?**
A: В `feed.php` - алгоритм с весами 40/30/20/10

**Q: Как работает фильтрация репетиторов?**
A: В `search.php` - SQL запрос с `last_seen_at` и `total_lessons`

---

*aqum - образовательная платформа нового поколения* 💜
