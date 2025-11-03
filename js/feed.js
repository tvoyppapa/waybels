/**
 * aqum Feed JavaScript
 * Обработка взаимодействий с постами в ленте
 */

document.addEventListener('DOMContentLoaded', function() {
    
    // ===============================================
    // Обработка лайков
    // ===============================================
    document.querySelectorAll('.post-action[data-action="like"]').forEach(button => {
        button.addEventListener('click', async function() {
            const postCard = this.closest('.post-card');
            const postId = postCard.dataset.postId;
            const likesCountElement = this.querySelector('.likes-count');
            const currentCount = parseInt(likesCountElement.textContent);
            
            // Добавляем анимацию
            this.classList.add('animating');
            setTimeout(() => this.classList.remove('animating'), 300);
            
            try {
                const response = await fetch('feed.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=like&post_id=${postId}`
                });
                
                const data = await response.json();
                
                if (data.success) {
                    if (data.liked) {
                        // Лайкнули
                        this.classList.add('active');
                        likesCountElement.textContent = currentCount + 1;
                    } else {
                        // Убрали лайк
                        this.classList.remove('active');
                        likesCountElement.textContent = currentCount - 1;
                    }
                }
            } catch (error) {
                console.error('Ошибка при обработке лайка:', error);
            }
        });
    });
    
    // ===============================================
    // Обработка комментариев
    // ===============================================
    document.querySelectorAll('.post-action[data-action="comment"]').forEach(button => {
        button.addEventListener('click', function() {
            const postCard = this.closest('.post-card');
            const postId = postCard.dataset.postId;
            
            // TODO: Открыть модальное окно с комментариями
            console.log('Открыть комментарии для поста', postId);
        });
    });
    
    // ===============================================
    // Обработка шаринга
    // ===============================================
    document.querySelectorAll('.post-action[data-action="share"]').forEach(button => {
        button.addEventListener('click', async function() {
            const postCard = this.closest('.post-card');
            const postId = postCard.dataset.postId;
            const postUrl = `${window.location.origin}/post.php?id=${postId}`;
            
            // Используем Web Share API если доступен
            if (navigator.share) {
                try {
                    await navigator.share({
                        title: 'Пост в aqum',
                        url: postUrl
                    });
                } catch (error) {
                    // Пользователь отменил шаринг
                    if (error.name !== 'AbortError') {
                        console.error('Ошибка при шаринге:', error);
                    }
                }
            } else {
                // Fallback - копируем ссылку в буфер обмена
                try {
                    await navigator.clipboard.writeText(postUrl);
                    showNotification('Ссылка скопирована в буфер обмена');
                } catch (error) {
                    console.error('Ошибка при копировании:', error);
                }
            }
        });
    });
    
    // ===============================================
    // Меню поста
    // ===============================================
    document.querySelectorAll('.post-menu-btn').forEach(button => {
        button.addEventListener('click', function(e) {
            e.stopPropagation();
            // TODO: Открыть меню поста (пожаловаться, скрыть, и т.д.)
            console.log('Открыть меню поста');
        });
    });
    
    // ===============================================
    // Отслеживание просмотров
    // ===============================================
    const observePostViews = () => {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const postCard = entry.target;
                    const postId = postCard.dataset.postId;
                    
                    // Отмечаем как просмотренное
                    if (!postCard.dataset.viewed) {
                        postCard.dataset.viewed = 'true';
                        trackPostView(postId);
                    }
                }
            });
        }, {
            threshold: 0.5, // 50% поста должно быть видно
            rootMargin: '0px'
        });
        
        document.querySelectorAll('.post-card').forEach(card => {
            observer.observe(card);
        });
    };
    
    const trackPostView = async (postId) => {
        try {
            await fetch('/api/track_view.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `post_id=${postId}`
            });
        } catch (error) {
            console.error('Ошибка при отслеживании просмотра:', error);
        }
    };
    
    // Запускаем отслеживание просмотров
    observePostViews();
    
    // ===============================================
    // Утилиты
    // ===============================================
    function showNotification(message) {
        // Простое уведомление
        const notification = document.createElement('div');
        notification.className = 'notification';
        notification.textContent = message;
        notification.style.cssText = `
            position: fixed;
            bottom: 80px;
            left: 50%;
            transform: translateX(-50%);
            background: var(--text-primary);
            color: white;
            padding: 12px 24px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            z-index: 9999;
            box-shadow: var(--shadow-lg);
            animation: slideUp 0.3s ease-out;
        `;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.style.animation = 'slideDown 0.3s ease-out';
            setTimeout(() => notification.remove(), 300);
        }, 2000);
    }
    
    // Добавляем CSS для анимаций уведомлений
    const style = document.createElement('style');
    style.textContent = `
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateX(-50%) translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateX(-50%) translateY(0);
            }
        }
        
        @keyframes slideDown {
            from {
                opacity: 1;
                transform: translateX(-50%) translateY(0);
            }
            to {
                opacity: 0;
                transform: translateX(-50%) translateY(20px);
            }
        }
    `;
    document.head.appendChild(style);
});
