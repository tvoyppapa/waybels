<?php
/**
 * ПРИМЕР ИСПОЛЬЗОВАНИЯ LAYOUT
 * 
 * Этот файл показывает, как использовать универсальный шаблон для создания новых страниц
 */

session_start();

// Параметры страницы
$page_title = "Название страницы";
$page_css = ['custom.css']; // Дополнительные стили (опционально)
$page_js = ['custom.js']; // Дополнительные скрипты (опционально)

// Для компактного меню (только иконки) - например, для чатов
// $compact = true;

// Для скрытия нижнего меню на мобильных - например, для видеозвонков
// $hide_nav = true;

// Для кнопки "Назад"
// $show_back = true;
// $back_url = '/previous-page.php';

// Подключаем хедер layout
require_once 'layout.php';
?>

<!-- ВАШ КОНТЕНТ ЗДЕСЬ -->
<div class="content-wrapper">
    <h1>Привет, это пример страницы!</h1>
    <p>Меню автоматически подключено и работает на всех устройствах.</p>
    
    <div class="example-card">
        <h2>Возможности:</h2>
        <ul>
            <li>✅ Автоматическая навигация (Desktop + Mobile)</li>
            <li>✅ Профиль справа вверху с dropdown меню</li>
            <li>✅ Компактный режим для чатов ($compact = true)</li>
            <li>✅ Скрытие меню для полноэкранных режимов ($hide_nav = true)</li>
            <li>✅ Адаптивный дизайн</li>
        </ul>
    </div>
</div>

<style>
.content-wrapper {
    max-width: 1000px;
    margin: 0 auto;
}

.example-card {
    background: var(--surface);
    padding: 24px;
    border-radius: var(--radius);
    box-shadow: var(--shadow-md);
    margin-top: 24px;
}

.example-card h2 {
    margin-bottom: 16px;
    color: var(--primary);
}

.example-card ul {
    list-style: none;
    padding: 0;
}

.example-card li {
    padding: 8px 0;
    font-size: 16px;
}
</style>

<?php
// Подключаем футер layout
require_once 'layout_footer.php';
?>
