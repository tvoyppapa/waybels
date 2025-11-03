<?php
/**
 * Очистка сессии
 */
session_start();
session_destroy();
session_start();

echo "<!DOCTYPE html>
<html lang='ru'>
<head>
    <meta charset='UTF-8'>
    <title>Сессия очищена</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            text-align: center;
            padding: 100px 20px;
            background: #f5f5f7;
        }
        h1 {
            color: #7F2CDF;
            font-size: 48px;
            margin-bottom: 20px;
        }
        p {
            font-size: 18px;
            margin-bottom: 30px;
        }
        a {
            background: #7F2CDF;
            color: white;
            padding: 15px 40px;
            text-decoration: none;
            border-radius: 10px;
            font-weight: bold;
            display: inline-block;
        }
    </style>
</head>
<body>
    <h1>✅ Сессия очищена!</h1>
    <p>Теперь можете войти заново</p>
    <a href='auth.php'>→ Перейти к входу</a>
</body>
</html>";
?>
