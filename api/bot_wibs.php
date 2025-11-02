<?php
session_start();
require_once '../db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];
$action = $_POST['action'] ?? $_GET['action'] ?? '';

// Получаем или создаем бота
$stmt = $pdo->prepare("SELECT id FROM users WHERE username = 'wibs_bot' LIMIT 1");
$stmt->execute();
$bot = $stmt->fetch();

if (!$bot) {
    // Создаем бота если не существует
    $stmt = $pdo->prepare("INSERT INTO users (name, email, password, username, role, avatar, is_online) VALUES (?, ?, ?, ?, ?, ?, 1)");
    $stmt->execute([
        'Wibs Bot',
        'bot@wibs.com',
        password_hash('bot123', PASSWORD_BCRYPT),
        'wibs_bot',
        'admin',
        '/img/bot-avatar.png'
    ]);
    $bot_id = $pdo->lastInsertId();
} else {
    $bot_id = $bot['id'];
}

// Отправка сообщения боту
if ($action === 'send') {
    $message = trim($_POST['message'] ?? '');
    
    if (empty($message)) {
        echo json_encode(['error' => 'Empty message']);
        exit;
    }
    
    // Сохраняем сообщение от пользователя
    $stmt = $pdo->prepare("INSERT INTO bot_messages (user_id, message, is_from_user, message_type) VALUES (?, ?, 1, 'text')");
    $stmt->execute([$user_id, $message]);
    
    // Генерируем ответ бота
    $bot_response = generateBotResponse($message);
    
    // Сохраняем ответ бота
    $stmt = $pdo->prepare("INSERT INTO bot_messages (user_id, message, is_from_user, message_type) VALUES (?, ?, 0, 'auto_reply')");
    $stmt->execute([$user_id, $bot_response]);
    
    echo json_encode([
        'success' => true,
        'user_message' => $message,
        'bot_response' => $bot_response
    ]);
    exit;
}

// Получение истории
if ($action === 'get_history') {
    $stmt = $pdo->prepare("
        SELECT * FROM bot_messages 
        WHERE user_id = ? 
        ORDER BY created_at ASC 
        LIMIT 50
    ");
    $stmt->execute([$user_id]);
    $history = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['messages' => $history]);
    exit;
}

// Функция генерации ответа бота
function generateBotResponse($message) {
    $message = mb_strtolower($message);
    
    // Ключевые слова и ответы
    $responses = [
        'привет' => 'Здравствуйте! Я Wibs Bot 🤖 Чем могу помочь?',
        'помощь' => 'Я могу помочь вам с:\n• Поиском репетиторов\n• Записью на урок\n• Техническими вопросами\n\nНапишите "поддержка" чтобы связаться с нашей командой.',
        'поддержка' => 'Ваше сообщение передано в службу поддержки. Мы ответим в ближайшее время! ✉️',
        'как записаться' => 'Чтобы записаться на урок:\n1. Найдите репетитора на главной странице\n2. Откройте его профиль\n3. Нажмите кнопку "Записаться"\n4. Выберите удобное время',
        'оплата' => 'Оплата уроков:\n• После подтверждения урока репетитором\n• Безопасные платежи через ЮKassa\n• Деньги удерживаются до проведения урока',
        'отменить урок' => 'Отмена урока возможна за 24 часа до начала без штрафов. Для отмены перейдите в "Мои уроки" → выберите урок → "Отменить".',
    ];
    
    // Проверяем ключевые слова
    foreach ($responses as $keyword => $response) {
        if (strpos($message, $keyword) !== false) {
            return $response;
        }
    }
    
    // Дефолтный ответ
    $default_responses = [
        'Спасибо за ваше сообщение! Чем могу помочь? Напишите "помощь" для списка команд.',
        'Я здесь чтобы помочь! Напишите "помощь" чтобы увидеть что я умею.',
        'Не совсем понял ваш вопрос. Напишите "помощь" для списка доступных команд или "поддержка" чтобы связаться с оператором.'
    ];
    
    return $default_responses[array_rand($default_responses)];
}
?>
