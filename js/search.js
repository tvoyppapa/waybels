/**
 * aqum Search JavaScript
 * Обработка поиска и фильтрации
 */

document.addEventListener('DOMContentLoaded', function() {
    
    // ===============================================
    // Автодополнение поиска
    // ===============================================
    const searchInput = document.querySelector('.search-input-main');
    
    if (searchInput) {
        let debounceTimer;
        
        searchInput.addEventListener('input', function() {
            clearTimeout(debounceTimer);
            
            debounceTimer = setTimeout(() => {
                const query = this.value.trim();
                
                if (query.length >= 2) {
                    // TODO: Реализовать автодополнение через AJAX
                    console.log('Поиск:', query);
                }
            }, 300);
        });
    }
    
    // ===============================================
    // Подписка на каналы
    // ===============================================
    document.querySelectorAll('.btn-subscribe').forEach(button => {
        button.addEventListener('click', async function() {
            const channelCard = this.closest('.channel-card');
            const channelId = channelCard.dataset.channelId;
            
            try {
                const response = await fetch('/api/subscribe_channel.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `channel_id=${channelId}`
                });
                
                const data = await response.json();
                
                if (data.success) {
                    if (data.subscribed) {
                        this.classList.add('subscribed');
                        this.textContent = 'Подписан';
                    } else {
                        this.classList.remove('subscribed');
                        this.textContent = 'Подписаться';
                    }
                }
            } catch (error) {
                console.error('Ошибка при подписке:', error);
            }
        });
    });
    
    // ===============================================
    // Аналитика поисковых запросов
    // ===============================================
    if (searchInput && searchInput.value) {
        trackSearchQuery(searchInput.value);
    }
    
    function trackSearchQuery(query) {
        // Отправляем запрос для аналитики
        fetch('/api/track_search.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `query=${encodeURIComponent(query)}`
        }).catch(error => {
            console.error('Ошибка отслеживания поиска:', error);
        });
    }
});
