<?php
/**
 * ТЕСТ: Проверка работы чистых URL
 */

echo '<h1>Тест профилей</h1>';
echo '<pre>';
echo 'GET параметры:' . "\n";
print_r($_GET);
echo "\n";
echo 'REQUEST_URI: ' . $_SERVER['REQUEST_URI'] . "\n";
echo 'PHP_SELF: ' . $_SERVER['PHP_SELF'] . "\n";
echo '</pre>';

echo '<h2>Тестовые ссылки:</h2>';
echo '<ul>';
echo '<li><a href="/@test">/@test</a></li>';
echo '<li><a href="/@ivan">/@ivan</a></li>';
echo '<li><a href="/@maria">/@maria</a></li>';
echo '<li><a href="/@notexist">/@notexist (не существует)</a></li>';
echo '</ul>';

echo '<h2>Переход на профиль:</h2>';
echo '<a href="/profile.php" style="padding: 10px 20px; background: #667eea; color: white; text-decoration: none; border-radius: 8px;">Мой профиль (profile.php)</a>';
