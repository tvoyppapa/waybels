<?php
session_start();

echo "<h1>Проверка POST данных</h1>";

echo "<h2>Метод запроса:</h2>";
echo "<p><strong>" . $_SERVER['REQUEST_METHOD'] . "</strong></p>";

echo "<h2>POST данные:</h2>";
if (empty($_POST)) {
    echo "<p style='color: red;'>❌ POST данные ПУСТЫЕ</p>";
} else {
    echo "<p style='color: green;'>✅ POST данные ЕСТЬ:</p>";
    echo "<pre>";
    print_r($_POST);
    echo "</pre>";
}

echo "<h2>Сессия:</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

echo "<hr>";

// Тестовая форма
?>
<h2>Тестовая форма:</h2>
<form method="POST" action="check_post.php" style="background: #f5f5f5; padding: 20px; border-radius: 10px; max-width: 400px;">
    <div style="margin-bottom: 15px;">
        <label>Email:</label><br>
        <input type="email" name="email" value="test@test.com" style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 5px;">
    </div>
    
    <div style="margin-bottom: 15px;">
        <label>Пароль:</label><br>
        <input type="password" name="password" value="password" style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 5px;">
    </div>
    
    <button type="submit" name="login" style="background: #7F2CDF; color: white; padding: 12px 30px; border: none; border-radius: 5px; cursor: pointer; font-weight: bold;">
        ОТПРАВИТЬ ФОРМУ
    </button>
</form>

<p style="margin-top: 20px;">
    <a href="auth.php" style="background: #34C759; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">
        → Вернуться к auth.php
    </a>
</p>

<style>
body {
    font-family: Arial, sans-serif;
    max-width: 800px;
    margin: 40px auto;
    padding: 20px;
    background: #f9f9f9;
}
h1 { color: #7F2CDF; }
h2 { color: #333; margin-top: 30px; }
pre {
    background: white;
    padding: 15px;
    border-radius: 5px;
    border-left: 3px solid #7F2CDF;
}
</style>
