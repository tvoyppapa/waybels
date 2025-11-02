// Календарь для dashboard - отображение ближайших уроков

async function loadUpcomingLessons() {
    try {
        const response = await fetch('api/get_bookings.php');
        const data = await response.json();
        
        const container = document.getElementById('upcomingLessons');
        if (!container) return;
        
        if (!data.bookings || data.bookings.length === 0) {
            container.innerHTML = `
                <div style="text-align: center; padding: 24px; color: var(--text-secondary);">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 48px; height: 48px; margin-bottom: 12px; opacity: 0.5;">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                        <line x1="16" y1="2" x2="16" y2="6"/>
                        <line x1="8" y1="2" x2="8" y2="6"/>
                        <line x1="3" y1="10" x2="21" y2="10"/>
                    </svg>
                    <p style="font-size: 14px;">Нет запланированных уроков</p>
                    <p style="font-size: 12px; margin-top: 8px;">Запишитесь к репетитору</p>
                </div>
            `;
            return;
        }
        
        let html = '<div style="display: flex; flex-direction: column; gap: 12px;">';
        
        data.bookings.slice(0, 3).forEach(booking => {
            const date = new Date(booking.booking_date + ' ' + booking.start_time);
            const dateStr = date.toLocaleDateString('ru', {day: 'numeric', month: 'short'});
            const timeStr = date.toLocaleTimeString('ru', {hour: '2-digit', minute: '2-digit'});
            
            html += `
                <div style="
                    padding: 16px;
                    background: var(--glass-bg-subtle);
                    border: 1px solid var(--glass-border);
                    border-radius: var(--radius-md);
                    transition: all 0.3s;
                    cursor: pointer;
                " onmouseover="this.style.background='var(--glass-bg)'" onmouseout="this.style.background='var(--glass-bg-subtle)'">
                    <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 8px;">
                        <img src="${booking.teacher_avatar}" style="width: 40px; height: 40px; border-radius: 50%; border: 2px solid var(--glass-border);">
                        <div>
                            <div style="font-weight: 600; font-size: 14px;">${booking.teacher_name}</div>
                            <div style="font-size: 12px; color: var(--text-secondary);">${booking.subject}</div>
                        </div>
                    </div>
                    <div style="display: flex; gap: 16px; font-size: 13px; color: var(--text-secondary);">
                        <span>📅 ${dateStr}</span>
                        <span>⏰ ${timeStr}</span>
                    </div>
                    <div style="margin-top: 8px;">
                        <span style="
                            display: inline-block;
                            padding: 4px 10px;
                            background: ${booking.status === 'confirmed' ? 'rgba(52, 199, 89, 0.1)' : 'rgba(255, 149, 0, 0.1)'};
                            color: ${booking.status === 'confirmed' ? '#34C759' : '#FF9500'};
                            border-radius: 12px;
                            font-size: 11px;
                            font-weight: 600;
                        ">
                            ${booking.status === 'confirmed' ? '✓ Подтверждено' : '⏳ Ожидание'}
                        </span>
                    </div>
                </div>
            `;
        });
        
        html += '</div>';
        container.innerHTML = html;
        
    } catch (error) {
        console.error('Error loading lessons:', error);
    }
}

// Загружаем при загрузке страницы
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', loadUpcomingLessons);
} else {
    loadUpcomingLessons();
}

// Обновляем каждые 30 секунд
setInterval(loadUpcomingLessons, 30000);
