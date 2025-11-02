<!-- Модальное окно бронирования (вставляется в teacher.php) -->
<div id="bookingModal" class="modal-overlay" style="display: none;">
    <div class="modal-glass">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
            <h2 style="font-size: 24px; font-weight: 700;">📅 Записаться на урок</h2>
            <button onclick="closeBookingModal()" style="background: none; border: none; cursor: pointer; font-size: 24px; color: var(--text-secondary);">×</button>
        </div>
        
        <form id="bookingForm" onsubmit="submitBooking(event)">
            <input type="hidden" id="teacherId" name="teacher_id">
            
            <div style="margin-bottom: 20px;">
                <label style="display: block; font-weight: 600; margin-bottom: 8px;">Дата урока</label>
                <input type="date" name="date" class="input-glass" required min="<?= date('Y-m-d') ?>">
            </div>
            
            <div style="margin-bottom: 20px;">
                <label style="display: block; font-weight: 600; margin-bottom: 8px;">Время начала</label>
                <select name="time" class="input-glass" required>
                    <option value="">Выберите время</option>
                    <?php for ($h = 8; $h <= 20; $h++): ?>
                        <option value="<?= sprintf('%02d:00', $h) ?>"><?= sprintf('%02d:00', $h) ?></option>
                        <option value="<?= sprintf('%02d:30', $h) ?>"><?= sprintf('%02d:30', $h) ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            
            <div style="margin-bottom: 20px;">
                <label style="display: block; font-weight: 600; margin-bottom: 8px;">Длительность</label>
                <select name="duration" class="input-glass" required>
                    <option value="60">1 час</option>
                    <option value="90">1.5 часа</option>
                    <option value="120">2 часа</option>
                </select>
            </div>
            
            <div style="margin-bottom: 24px;">
                <label style="display: block; font-weight: 600; margin-bottom: 8px;">Комментарий (необязательно)</label>
                <textarea name="notes" class="input-glass" rows="3" placeholder="Что хотите изучить?"></textarea>
            </div>
            
            <div style="display: flex; gap: 12px;">
                <button type="button" onclick="closeBookingModal()" style="flex: 1; padding: 14px; background: var(--glass-bg); border: 1px solid var(--glass-border); border-radius: var(--radius-md); font-weight: 600; cursor: pointer;">
                    Отмена
                </button>
                <button type="submit" class="btn-primary-glass" style="flex: 1; padding: 14px; border: none; border-radius: var(--radius-md); font-weight: 600; cursor: pointer;">
                    Записаться
                </button>
            </div>
        </form>
    </div>
</div>

<style>
.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(8px);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999;
    animation: fadeIn 0.3s ease-out;
}

.modal-glass {
    background: var(--glass-bg-strong);
    backdrop-filter: blur(var(--glass-blur-strong));
    border: 1px solid var(--glass-border);
    border-radius: var(--radius-xl);
    padding: 32px;
    max-width: 500px;
    width: 90%;
    box-shadow: var(--glass-shadow-lg);
    animation: scaleIn 0.3s ease-out;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes scaleIn {
    from { transform: scale(0.9); opacity: 0; }
    to { transform: scale(1); opacity: 1; }
}
</style>

<script>
function openBookingModal(teacherId) {
    document.getElementById('teacherId').value = teacherId;
    document.getElementById('bookingModal').style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function closeBookingModal() {
    document.getElementById('bookingModal').style.display = 'none';
    document.body.style.overflow = 'auto';
}

async function submitBooking(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    
    try {
        const response = await fetch('api/create_booking.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            alert('✅ Заявка на урок отправлена! Ожидайте подтверждения от репетитора.');
            closeBookingModal();
            e.target.reset();
        } else {
            alert('❌ Ошибка: ' + (data.error || 'Не удалось записаться'));
        }
    } catch (error) {
        alert('❌ Ошибка отправки заявки');
        console.error(error);
    }
}
</script>
