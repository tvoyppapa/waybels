<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Отладка auth.php</h2>";

echo "<h3>1. POST данные:</h3>";
echo "<pre>";
print_r($_POST);
echo "</pre>";

echo "<h3>2. SESSION данные:</h3>";
session_start();
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

echo "<h3>3. Проверка обработчика:</h3>";
require_once 'db.php';
require_once 'helpers.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "✅ POST запрос получен<br>";
    
    if (isset($_POST['register'])) {
        echo "✅ Кнопка register нажата<br>";
        echo "Имя: " . ($_POST['name'] ?? 'НЕТ') . "<br>";
        echo "Email: " . ($_POST['email'] ?? 'НЕТ') . "<br>";
        echo "Password: " . (isset($_POST['password']) ? 'ЕСТЬ' : 'НЕТ') . "<br>";
    }
    
    if (isset($_POST['login'])) {
        echo "✅ Кнопка login нажата<br>";
        echo "Email: " . ($_POST['email'] ?? 'НЕТ') . "<br>";
        echo "Password: " . (isset($_POST['password']) ? 'ЕСТЬ' : 'НЕТ') . "<br>";
    }
} else {
    echo "❌ Это GET запрос, не POST<br>";
}

echo "<h3>4. Тестовая форма:</h3>";
?>
<form method="POST" action="debug_auth.php">
    <input type="text" name="name" placeholder="Имя" required><br>
    <input type="email" name="email" placeholder="Email" required><br>
    <input type="password" name="password" placeholder="Пароль" required><br>
    <button type="submit" name="register">Тест регистрации</button>
</form>
