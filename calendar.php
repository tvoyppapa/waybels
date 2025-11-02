<?php
session_start();
require_once 'db.php';
require_once 'helpers.php';

if (!isset($_SESSION['user_id'])) {
    redirect('index.php');
}

$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();
$theme = $user['theme'] ?? 'light';

// Получаем уроки пользователя
$stmt = $pdo->prepare("
    SELECT 
        b.*,
        u.name as teacher_name,
        u.avatar as teacher_avatar,
        tp.subject,
        tp.hourly_rate
    FROM bookings b
    LEFT JOIN users u ON b.teacher_id = u.id
    LEFT JOIN teacher_profiles tp ON tp.user_id = u.id
    WHERE b.student_id = ?
    ORDER BY b.booking_date DESC, b.start_time DESC
");
$stmt->execute([$user_id]);
$bookings = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="ru" data-theme="<?= $theme ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Мои уроки - WayBels</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style/glass.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.9/index.global.min.css">
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.9/index.global.min.js"></script>
    <style>
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 32px 20px;
        }
        
        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            border-radius: var(--radius-full);
            color: var(--text-primary);
            text-decoration: none;
            font-weight: 600;
            margin-bottom: 24px;
            transition: all var(--transition);
        }
        
        .back-btn:hover {
            background: var(--glass-bg-strong);
            transform: translateX(-4px);
        }
        
        .back-btn svg {
            width: 20px;
            height: 20px;
        }
        
        .page-title {
            font-size: 36px;
            font-weight: 700;
            margin-bottom: 32px;
        }
        
        .calendar-container {
            background: var(--glass-bg);
            backdrop-filter: blur(var(--glass-blur));
            border: 1px solid var(--glass-border);
            border-radius: var(--radius-xl);
            padding: 32px;
            margin-bottom: 32px;
        }
        
        .fc {
            --fc-border-color: var(--glass-border);
            --fc-button-bg-color: var(--primary);
            --fc-button-border-color: var(--primary);
            --fc-button-hover-bg-color: var(--primary-hover);
            --fc-button-active-bg-color: var(--primary-hover);
            --fc-event-bg-color: var(--primary);
            --fc-event-border-color: var(--primary);
        }
        
        .fc .fc-button {
            border-radius: var(--radius-sm);
            font-weight: 600;
        }
        
        .bookings-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
        }
        
        .booking-card {
            background: var(--glass-bg);
            backdrop-filter: blur(var(--glass-blur));
            border: 1px solid var(--glass-border);
            border-radius: var(--radius-xl);
            padding: 24px;
            transition: all var(--transition);
        }
        
        .booking-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--glass-shadow-lg);
        }
        
        .booking-header {
            display: flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 16px;
        }
        
        .booking-avatar {
            width: 60px;
            height: 60px;
            border-radius: var(--radius-lg);
            background-size: cover;
            border: 2px solid var(--glass-border);
        }
        
        .booking-info h3 {
            font-size: 18px;
            margin-bottom: 4px;
        }
        
        .booking-info p {
            font-size: 14px;
            color: var(--text-secondary);
        }
        
        .booking-details {
            display: flex;
            flex-direction: column;
            gap: 8px;
            margin-bottom: 16px;
            font-size: 14px;
        }
        
        .booking-detail {
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--text-secondary);
        }
        
        .booking-status {
            display: inline-block;
            padding: 6px 14px;
            border-radius: var(--radius-full);
            font-size: 12px;
            font-weight: 600;
        }
        
        .status-pending { background: rgba(255, 149, 0, 0.1); color: #FF9500; }
        .status-confirmed { background: rgba(52, 199, 89, 0.1); color: #34C759; }
        .status-cancelled { background: rgba(255, 59, 48, 0.1); color: #FF3B30; }
        .status-completed { background: rgba(0, 122, 255, 0.1); color: #007AFF; }
    </style>
</head>
<body>
    <div class="container">
        <a href="dashboard.php" class="back-btn">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M19 12H5M12 19l-7-7 7-7"/>
            </svg>
            Назад
        </a>
        
        <h1 class="page-title">📅 Мои уроки</h1>
        
        <div class="calendar-container">
            <div id="calendar"></div>
        </div>
        
        <h2 style="font-size: 24px; font-weight: 700; margin-bottom: 20px;">Все записи</h2>
        
        <div class="bookings-list">
            <?php if (empty($bookings)): ?>
                <div class="booking-card">
                    <p style="text-align: center; color: var(--text-secondary);">
                        У вас пока нет записей на уроки
                    </p>
                </div>
            <?php else: ?>
                <?php foreach ($bookings as $booking): ?>
                    <div class="booking-card">
                        <div class="booking-header">
                            <div class="booking-avatar" style="background-image: url('<?= e($booking['teacher_avatar']) ?>')"></div>
                            <div class="booking-info">
                                <h3><?= e($booking['teacher_name']) ?></h3>
                                <p><?= e($booking['subject']) ?></p>
                            </div>
                        </div>
                        
                        <div class="booking-details">
                            <div class="booking-detail">
                                <span>📅</span>
                                <span><?= date('d.m.Y', strtotime($booking['booking_date'])) ?></span>
                            </div>
                            <div class="booking-detail">
                                <span>⏰</span>
                                <span><?= substr($booking['start_time'], 0, 5) ?> - <?= substr($booking['end_time'], 0, 5) ?></span>
                            </div>
                            <div class="booking-detail">
                                <span>💰</span>
                                <span><?= number_format($booking['price'], 0) ?> ₽</span>
                            </div>
                        </div>
                        
                        <span class="booking-status status-<?= $booking['status'] ?>">
                            <?php
                            $statusText = [
                                'pending' => '⏳ Ожидание',
                                'confirmed' => '✓ Подтверждено',
                                'cancelled' => '✗ Отменено',
                                'completed' => '✓ Завершено'
                            ];
                            echo $statusText[$booking['status']];
                            ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const calendarEl = document.getElementById('calendar');
            const calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                locale: 'ru',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek'
                },
                buttonText: {
                    today: 'Сегодня',
                    month: 'Месяц',
                    week: 'Неделя'
                },
                events: async function(info, successCallback, failureCallback) {
                    try {
                        const response = await fetch('api/get_bookings.php');
                        const data = await response.json();
                        
                        const events = data.bookings.map(booking => ({
                            title: `${booking.teacher_name} - ${booking.subject}`,
                            start: booking.booking_date + 'T' + booking.start_time,
                            end: booking.booking_date + 'T' + booking.end_time,
                            color: booking.status === 'confirmed' ? '#34C759' : '#FF9500'
                        }));
                        
                        successCallback(events);
                    } catch (error) {
                        failureCallback(error);
                    }
                },
                eventClick: function(info) {
                    alert('Урок: ' + info.event.title);
                }
            });
            
            calendar.render();
        });
    </script>
</body>
</html>
