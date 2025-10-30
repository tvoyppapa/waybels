/**
 * Profile Page JavaScript
 */

document.addEventListener('DOMContentLoaded', function() {
  
  // Загрузка аватара
  const avatarUpload = document.getElementById('avatar-upload');
  const avatarPreview = document.getElementById('avatar-preview');
  
  if (avatarUpload) {
    avatarUpload.addEventListener('change', function(e) {
      const file = e.target.files[0];
      if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
          avatarPreview.src = e.target.result;
          
          // Здесь можно отправить файл на сервер
          uploadAvatar(file);
        };
        reader.readAsDataURL(file);
      }
    });
  }
  
  // Автоскрытие алертов
  const alerts = document.querySelectorAll('.alert');
  alerts.forEach(alert => {
    setTimeout(() => {
      alert.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
      alert.style.opacity = '0';
      alert.style.transform = 'translateY(-10px)';
      setTimeout(() => alert.remove(), 300);
    }, 5000);
  });
  
});

function uploadAvatar(file) {
  const formData = new FormData();
  formData.append('avatar', file);
  
  fetch('/upload_avatar.php', {
    method: 'POST',
    body: formData
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      console.log('Avatar uploaded successfully');
    }
  })
  .catch(error => console.error('Error:', error));
}
