/**
 * Teacher Detail Page JavaScript
 */

document.addEventListener('DOMContentLoaded', function() {
  
  // Избранное
  const favoriteBtn = document.querySelector('.btn-favorite');
  if (favoriteBtn) {
    favoriteBtn.addEventListener('click', function() {
      const teacherId = this.dataset.teacherId;
      const isActive = this.classList.contains('active');
      
      fetch('/dashboard.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `toggle_favorite=1&teacher_id=${teacherId}`
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          this.classList.toggle('active');
        }
      })
      .catch(error => console.error('Error:', error));
    });
  }
  
});

// Начать чат
function startChat(teacherId) {
  window.location.href = `/chat.php?id=${teacherId}`;
}
