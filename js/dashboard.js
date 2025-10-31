/**
 * WayBels Dashboard JavaScript
 */

document.addEventListener('DOMContentLoaded', function() {
  
  // ===============================================
  // Сортировка
  // ===============================================
  const sortSelect = document.getElementById('sort-select');
  if (sortSelect) {
    sortSelect.addEventListener('change', function() {
      const url = new URL(window.location);
      url.searchParams.set('sort', this.value);
      window.location = url;
    });
  }
  
  // ===============================================
  // Избранное (AJAX)
  // ===============================================
  const favoriteButtons = document.querySelectorAll('.favorite-btn');
  favoriteButtons.forEach(btn => {
    btn.addEventListener('click', function(e) {
      e.preventDefault();
      e.stopPropagation();
      
      const teacherId = this.dataset.teacherId;
      const isActive = this.classList.contains('active');
      
      // Отправляем запрос
      fetch('dashboard.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `toggle_favorite=1&teacher_id=${teacherId}`
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          // Переключаем класс
          this.classList.toggle('active');
          
          // Показываем уведомление
          showNotification(
            isActive ? 'Удалено из избранного' : 'Добавлено в избранное',
            'success'
          );
        }
      })
      .catch(error => {
        console.error('Error:', error);
        showNotification('Ошибка. Попробуйте еще раз', 'error');
      });
    });
  });
  
  // ===============================================
  // Поиск с debounce
  // ===============================================
  const searchInput = document.querySelector('.search-input');
  if (searchInput) {
    let searchTimeout;
    
    searchInput.addEventListener('input', function() {
      clearTimeout(searchTimeout);
      
      searchTimeout = setTimeout(() => {
        this.form.submit();
      }, 500);
    });
  }
  
  // ===============================================
  // Уведомления
  // ===============================================
  function showNotification(message, type = 'success') {
    // Создаем элемент уведомления
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.textContent = message;
    
    // Добавляем стили
    notification.style.cssText = `
      position: fixed;
      top: 20px;
      right: 20px;
      padding: 16px 24px;
      background: ${type === 'success' ? '#34C759' : '#FF3B30'};
      color: white;
      border-radius: 12px;
      box-shadow: 0 4px 16px rgba(0, 0, 0, 0.2);
      font-weight: 500;
      z-index: 1000;
      animation: slideIn 0.3s ease-out;
    `;
    
    document.body.appendChild(notification);
    
    // Удаляем через 3 секунды
    setTimeout(() => {
      notification.style.animation = 'slideOut 0.3s ease-out';
      setTimeout(() => notification.remove(), 300);
    }, 3000);
  }
  
  // ===============================================
  // Анимация карточек при прокрутке
  // ===============================================
  const observerOptions = {
    threshold: 0.1,
    rootMargin: '0px 0px -50px 0px'
  };
  
  const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        entry.target.style.opacity = '1';
        entry.target.style.transform = 'translateY(0)';
      }
    });
  }, observerOptions);
  
  // Наблюдаем за карточками
  document.querySelectorAll('.teacher-card').forEach(card => {
    observer.observe(card);
  });
  
  // ===============================================
  // Активная навигация
  // ===============================================
  const currentPath = window.location.pathname;
  document.querySelectorAll('.nav-item, .bottom-nav-item').forEach(link => {
    if (link.getAttribute('href') === currentPath.split('/').pop()) {
      link.classList.add('active');
    }
  });
  
});

// CSS для анимаций уведомлений
const style = document.createElement('style');
style.textContent = `
  @keyframes slideIn {
    from {
      transform: translateX(100%);
      opacity: 0;
    }
    to {
      transform: translateX(0);
      opacity: 1;
    }
  }
  
  @keyframes slideOut {
    from {
      transform: translateX(0);
      opacity: 1;
    }
    to {
      transform: translateX(100%);
      opacity: 0;
    }
  }
`;
document.head.appendChild(style);
