/**
 * Скрипт для страницы аутентификации
 */

document.addEventListener('DOMContentLoaded', function() {
  
  // ===============================================
  // Переключение между формами входа и регистрации
  // ===============================================
  const loginForm = document.getElementById('login-form');
  const registerForm = document.getElementById('register-form');
  const showRegisterBtn = document.getElementById('show-register');
  const showLoginBtn = document.getElementById('show-login');
  
  if (showRegisterBtn) {
    showRegisterBtn.addEventListener('click', function(e) {
      e.preventDefault();
      loginForm.style.display = 'none';
      registerForm.style.display = 'flex';
      
      // Анимация появления
      registerForm.style.opacity = '0';
      setTimeout(() => {
        registerForm.style.transition = 'opacity 0.3s ease';
        registerForm.style.opacity = '1';
      }, 10);
      
      // Фокус на первом поле
      const firstInput = registerForm.querySelector('input');
      if (firstInput) firstInput.focus();
    });
  }
  
  if (showLoginBtn) {
    showLoginBtn.addEventListener('click', function(e) {
      e.preventDefault();
      registerForm.style.display = 'none';
      loginForm.style.display = 'flex';
      
      // Анимация появления
      loginForm.style.opacity = '0';
      setTimeout(() => {
        loginForm.style.transition = 'opacity 0.3s ease';
        loginForm.style.opacity = '1';
      }, 10);
      
      // Фокус на первом поле
      const firstInput = loginForm.querySelector('input');
      if (firstInput) firstInput.focus();
    });
  }
  
  // ===============================================
  // Валидация формы в реальном времени
  // ===============================================
  
  // Валидация email
  const emailInputs = document.querySelectorAll('input[type="email"]');
  emailInputs.forEach(input => {
    input.addEventListener('blur', function() {
      validateEmail(this);
    });
    
    input.addEventListener('input', function() {
      // Убираем ошибку при вводе
      if (this.classList.contains('error')) {
        this.classList.remove('error');
        removeErrorMessage(this);
      }
    });
  });
  
  function validateEmail(input) {
    const email = input.value.trim();
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    
    if (email && !emailRegex.test(email)) {
      showInputError(input, 'Неверный формат email');
      return false;
    } else {
      removeInputError(input);
      return true;
    }
  }
  
  // Валидация пароля
  const passwordInputs = document.querySelectorAll('input[type="password"]');
  passwordInputs.forEach(input => {
    // Для формы регистрации
    if (input.id === 'register-password') {
      input.addEventListener('input', function() {
        validatePassword(this);
      });
    }
    
    input.addEventListener('input', function() {
      if (this.classList.contains('error')) {
        this.classList.remove('error');
        removeErrorMessage(this);
      }
    });
  });
  
  function validatePassword(input) {
    const password = input.value;
    const minLength = 6;
    
    if (password.length > 0 && password.length < minLength) {
      showInputError(input, `Минимум ${minLength} символов`);
      return false;
    } else {
      removeInputError(input);
      return true;
    }
  }
  
  // Валидация имени
  const nameInput = document.getElementById('register-name');
  if (nameInput) {
    nameInput.addEventListener('blur', function() {
      validateName(this);
    });
    
    nameInput.addEventListener('input', function() {
      if (this.classList.contains('error')) {
        this.classList.remove('error');
        removeErrorMessage(this);
      }
    });
  }
  
  function validateName(input) {
    const name = input.value.trim();
    
    if (name && name.length < 2) {
      showInputError(input, 'Минимум 2 символа');
      return false;
    } else {
      removeInputError(input);
      return true;
    }
  }
  
  // Показать ошибку поля
  function showInputError(input, message) {
    input.classList.add('error');
    
    // Добавляем стили для ошибки
    input.style.borderColor = '#FF3B30';
    
    // Показываем сообщение
    let errorEl = input.parentElement.querySelector('.input-error');
    if (!errorEl) {
      errorEl = document.createElement('small');
      errorEl.className = 'input-error';
      errorEl.style.color = '#FF3B30';
      errorEl.style.fontSize = '13px';
      errorEl.style.marginTop = '-4px';
      input.parentElement.appendChild(errorEl);
    }
    errorEl.textContent = message;
  }
  
  // Убрать ошибку поля
  function removeInputError(input) {
    input.classList.remove('error');
    input.style.borderColor = '';
    
    const errorEl = input.parentElement.querySelector('.input-error');
    if (errorEl) {
      errorEl.remove();
    }
  }
  
  function removeErrorMessage(input) {
    const errorEl = input.parentElement.querySelector('.input-error');
    if (errorEl) {
      errorEl.remove();
    }
  }
  
  // ===============================================
  // Валидация перед отправкой формы
  // ===============================================
  if (loginForm) {
    loginForm.addEventListener('submit', function(e) {
      const emailInput = this.querySelector('input[type="email"]');
      
      if (!validateEmail(emailInput)) {
        e.preventDefault();
        emailInput.focus();
      }
    });
  }
  
  if (registerForm) {
    registerForm.addEventListener('submit', function(e) {
      const nameInput = this.querySelector('#register-name');
      const emailInput = this.querySelector('input[type="email"]');
      const passwordInput = this.querySelector('input[type="password"]');
      
      let isValid = true;
      
      if (!validateName(nameInput)) {
        isValid = false;
        if (isValid) nameInput.focus();
      }
      
      if (!validateEmail(emailInput)) {
        isValid = false;
        if (isValid) emailInput.focus();
      }
      
      if (!validatePassword(passwordInput)) {
        isValid = false;
        if (isValid) passwordInput.focus();
      }
      
      if (!isValid) {
        e.preventDefault();
      }
    });
  }
  
  // ===============================================
  // Анимация кнопок квиза
  // ===============================================
  const quizButtons = document.querySelectorAll('.quiz-btn');
  quizButtons.forEach((btn, index) => {
    // Задержка для анимации появления
    btn.style.animation = `fadeInUp 0.5s ease-out ${0.1 * index}s backwards`;
    
    // Эффект ripple при клике
    btn.addEventListener('click', function(e) {
      const ripple = document.createElement('span');
      ripple.style.position = 'absolute';
      ripple.style.borderRadius = '50%';
      ripple.style.background = 'rgba(255, 255, 255, 0.6)';
      ripple.style.width = '20px';
      ripple.style.height = '20px';
      ripple.style.animation = 'ripple 0.6s ease-out';
      
      const rect = this.getBoundingClientRect();
      ripple.style.left = (e.clientX - rect.left - 10) + 'px';
      ripple.style.top = (e.clientY - rect.top - 10) + 'px';
      
      this.style.position = 'relative';
      this.appendChild(ripple);
      
      setTimeout(() => ripple.remove(), 600);
    });
  });
  
  // ===============================================
  // Автоматическое скрытие алертов
  // ===============================================
  const alerts = document.querySelectorAll('.alert');
  alerts.forEach(alert => {
    setTimeout(() => {
      alert.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
      alert.style.opacity = '0';
      alert.style.transform = 'translateY(-10px)';
      
      setTimeout(() => {
        alert.remove();
      }, 300);
    }, 5000); // Скрываем через 5 секунд
  });
  
  // ===============================================
  // Показать/скрыть пароль
  // ===============================================
  passwordInputs.forEach(input => {
    // Создаем кнопку показа/скрытия пароля
    const toggleBtn = document.createElement('button');
    toggleBtn.type = 'button';
    toggleBtn.innerHTML = '👁️';
    toggleBtn.className = 'toggle-password';
    toggleBtn.style.cssText = `
      position: absolute;
      right: 12px;
      top: 50%;
      transform: translateY(-50%);
      background: none;
      border: none;
      cursor: pointer;
      font-size: 18px;
      opacity: 0.6;
      transition: opacity 0.2s;
    `;
    
    toggleBtn.addEventListener('mouseenter', () => toggleBtn.style.opacity = '1');
    toggleBtn.addEventListener('mouseleave', () => toggleBtn.style.opacity = '0.6');
    
    toggleBtn.addEventListener('click', function() {
      if (input.type === 'password') {
        input.type = 'text';
        this.innerHTML = '🙈';
      } else {
        input.type = 'password';
        this.innerHTML = '👁️';
      }
    });
    
    // Добавляем кнопку
    const formGroup = input.parentElement;
    formGroup.style.position = 'relative';
    formGroup.appendChild(toggleBtn);
    
    // Добавляем отступ справа в поле
    input.style.paddingRight = '45px';
  });
  
  // ===============================================
  // Индикатор загрузки на формах
  // ===============================================
  const forms = document.querySelectorAll('form');
  forms.forEach(form => {
    form.addEventListener('submit', function() {
      const submitBtn = this.querySelector('button[type="submit"]');
      if (submitBtn) {
        const originalText = submitBtn.textContent;
        submitBtn.textContent = 'Загрузка...';
        submitBtn.disabled = true;
        submitBtn.style.opacity = '0.7';
        
        // Возвращаем в исходное состояние через 3 секунды (на случай ошибки)
        setTimeout(() => {
          submitBtn.textContent = originalText;
          submitBtn.disabled = false;
          submitBtn.style.opacity = '1';
        }, 3000);
      }
    });
  });
  
  // ===============================================
  // Автофокус на первом поле
  // ===============================================
  const firstVisibleInput = document.querySelector('form:not([style*="display: none"]) input:not([type="hidden"])');
  if (firstVisibleInput) {
    setTimeout(() => firstVisibleInput.focus(), 100);
  }
  
});

// Добавляем CSS для анимации ripple
const style = document.createElement('style');
style.textContent = `
  @keyframes ripple {
    from {
      transform: scale(0);
      opacity: 1;
    }
    to {
      transform: scale(10);
      opacity: 0;
    }
  }
`;
document.head.appendChild(style);
