# 📚 Использование универсального Layout

## Быстрый старт

### Базовое использование

```php
<?php
session_start();

$page_title = "Моя страница";

require_once 'includes/layout.php';
?>

<!-- Ваш контент -->
<div>
    <h1>Привет, мир!</h1>
</div>

<?php require_once 'includes/layout_footer.php'; ?>
```

## Параметры

### `$page_title`
Заголовок страницы, отображается в теге `<title>` и в хедере.

```php
$page_title = "Лента новостей";
```

### `$page_css`
Массив дополнительных CSS файлов (из папки `/style/`).

```php
$page_css = ['feed.css', 'custom.css'];
```

### `$page_js`
Массив дополнительных JS файлов (из папки `/js/`).

```php
$page_js = ['feed.js', 'custom.js'];
```

### `$compact`
Компактный режим sidebar - показывает только иконки (для чатов, узких интерфейсов).

```php
$compact = true; // sidebar сужается до 80px
```

### `$hide_nav`
Скрыть нижнее меню на мобильных (для полноэкранных режимов - видеозвонки).

```php
$hide_nav = true; // нижнее меню не отображается
```

### `$show_back`
Показать кнопку "Назад" в хедере.

```php
$show_back = true;
$back_url = '/feed.php'; // URL для возврата
```

## Примеры использования

### 1. Обычная страница

```php
<?php
session_start();
$page_title = "Лента";
require_once 'includes/layout.php';
?>

<div class="feed-container">
    <!-- Контент ленты -->
</div>

<?php require_once 'includes/layout_footer.php'; ?>
```

### 2. Страница с кнопкой "Назад"

```php
<?php
session_start();
$page_title = "Профиль репетитора";
$show_back = true;
$back_url = '/search.php';
require_once 'includes/layout.php';
?>

<div class="teacher-profile">
    <!-- Профиль репетитора -->
</div>

<?php require_once 'includes/layout_footer.php'; ?>
```

### 3. Страница чата (компактное меню)

```php
<?php
session_start();
$page_title = "Чат";
$compact = true; // Меню сворачивается
$page_css = ['messages.css'];
$page_js = ['chat.js'];
require_once 'includes/layout.php';
?>

<div class="chat-container">
    <!-- Интерфейс чата -->
</div>

<?php require_once 'includes/layout_footer.php'; ?>
```

### 4. Видеозвонок (без меню)

```php
<?php
session_start();
$page_title = "Видеозвонок";
$hide_nav = true; // Скрываем нижнее меню на мобильных
$compact = true; // Компактный sidebar
require_once 'includes/layout.php';
?>

<div class="video-call-container">
    <!-- Интерфейс видеозвонка -->
</div>

<?php require_once 'includes/layout_footer.php'; ?>
```

## Навигация

Меню автоматически подстраивается под:
- 🖥️ **Desktop**: Боковое меню слева (sidebar)
- 📱 **Mobile**: Нижнее меню (bottom navigation)

### Структура навигации

1. **Лента** (`/feed.php`) - Главная страница с постами
2. **Поиск** (`/search.php`) - Универсальный поиск
3. **Сообщения** (`/messages.php`) - Чаты, каналы, группы
4. **Профиль** (`/profile.php`) - Настройки пользователя

## Профиль (справа вверху)

В хедере автоматически отображается:
- Аватар пользователя
- Имя (скрывается на мобильных)
- Dropdown меню:
  - Мой профиль
  - Админ-панель (если админ)
  - Стать репетитором
  - Выйти

## Адаптивность

### Desktop (>968px)
- Боковое меню слева (240px или 80px в компактном режиме)
- Профиль справа вверху с именем
- Полный хедер

### Tablet/Mobile (<968px)
- Боковое меню скрыто
- Нижнее меню появляется
- Профиль только с аватаром
- Мобильный логотип в хедере

### Small Mobile (<480px)
- Упрощенный хедер
- Меньшие размеры элементов
- Оптимизированный dropdown

## CSS переменные

Используйте эти переменные для кастомизации:

```css
:root {
  --primary: #7F2CDF;
  --primary-dark: #6A1FC9;
  --primary-light: #9851E8;
  --background: #F5F5F7;
  --surface: #FFFFFF;
  --text-primary: #1D1D1F;
  --text-secondary: #6E6E73;
  --border: #D2D2D7;
  --success: #34C759;
  --warning: #FF9500;
  --error: #FF3B30;
}
```

## Файловая структура

```
/workspace/
├── includes/
│   ├── sidebar.php          # Боковое меню (Desktop)
│   ├── bottom_nav.php       # Нижнее меню (Mobile)
│   ├── header.php           # Хедер с профилем
│   ├── layout.php           # Начало шаблона
│   ├── layout_footer.php    # Конец шаблона
│   └── layout_example.php   # Пример использования
├── style/
│   └── dashboard.css        # Основные стили
└── js/
    └── dashboard.js         # Основные скрипты
```

## Советы

1. **Всегда используйте layout** для всех страниц приложения
2. **Не дублируйте код** - меню подключится автоматически
3. **Используйте параметры** для кастомизации под конкретные задачи
4. **Тестируйте на мобильных** - layout адаптивный из коробки
5. **Используйте CSS переменные** для единообразия дизайна
