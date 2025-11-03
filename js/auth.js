/* ==========================================
   AQUM AUTH.JS
   ========================================== */

document.addEventListener('DOMContentLoaded', function() {
    
    // ==========================================
    // ПЕРЕКЛЮЧЕНИЕ ФОРМ
    // ==========================================
    
    window.showRegister = function() {
        document.getElementById('login-form')?.classList.remove('active');
        document.getElementById('register-form')?.classList.add('active');
    };
    
    window.showLogin = function() {
        document.getElementById('register-form')?.classList.remove('active');
        document.getElementById('login-form')?.classList.add('active');
    };
    
    // Показываем форму входа по умолчанию
    const loginForm = document.getElementById('login-form');
    if (loginForm && !loginForm.classList.contains('active')) {
        const registerForm = document.getElementById('register-form');
        if (registerForm && !registerForm.classList.contains('active')) {
            loginForm.classList.add('active');
        }
    }
    
    // ==========================================
    // ПЕРЕКЛЮЧАТЕЛЬ EMAIL/PHONE
    // ==========================================
    
    const switchButtons = document.querySelectorAll('.switch-btn');
    const loginTypeInput = document.getElementById('login-type');
    const registerLoginInput = document.getElementById('register-login');
    
    switchButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            // Убираем active у всех
            switchButtons.forEach(b => b.classList.remove('active'));
            // Добавляем active текущей
            this.classList.add('active');
            
            const type = this.getAttribute('data-type');
            if (loginTypeInput) {
                loginTypeInput.value = type;
            }
            
            // Меняем placeholder
            if (registerLoginInput) {
                if (type === 'email') {
                    registerLoginInput.placeholder = 'example@mail.com';
                    registerLoginInput.type = 'email';
                } else {
                    registerLoginInput.placeholder = '+7 999 123 45 67';
                    registerLoginInput.type = 'tel';
                }
                registerLoginInput.value = '';
                registerLoginInput.focus();
            }
        });
    });
    
    // ==========================================
    // ФОРМАТИРОВАНИЕ ИМЕНИ
    // ==========================================
    
    const nameInput = document.getElementById('register-name');
    if (nameInput) {
        nameInput.addEventListener('input', function(e) {
            this.value = this.value.replace(/[0-9_\.@#$%^&*()+=\[\]{};:'",<>?\/\\|`~!]/g, '');
        });
        
        nameInput.addEventListener('blur', function(e) {
            const words = this.value.trim().split(/\s+/);
            const formatted = words.map(word => {
                if (word.length === 0) return '';
                return word.charAt(0).toUpperCase() + word.slice(1).toLowerCase();
            }).join(' ');
            this.value = formatted;
        });
    }
    
    // ==========================================
    // ФОРМАТИРОВАНИЕ ТЕЛЕФОНА
    // ==========================================
    
    if (registerLoginInput) {
        registerLoginInput.addEventListener('input', function(e) {
            const type = loginTypeInput?.value;
            
            if (type === 'phone') {
                let value = this.value.replace(/[^\d+]/g, '');
                
                if (value.startsWith('8')) {
                    value = '+7' + value.slice(1);
                } else if (value.startsWith('7') && !value.startsWith('+')) {
                    value = '+' + value;
                } else if (!value.startsWith('+') && value.length > 0) {
                    value = '+7' + value;
                }
                
                this.value = value;
            }
        });
    }
    
    // ==========================================
    // ВАЛИДАЦИЯ ВОЗРАСТА
    // ==========================================
    
    const ageInput = document.querySelector('input[name="age"]');
    if (ageInput) {
        ageInput.addEventListener('input', function(e) {
            const value = parseInt(this.value);
            
            if (value < 13) {
                this.setCustomValidity('Минимальный возраст: 13');
            } else if (value > 100) {
                this.setCustomValidity('Максимальный возраст: 100');
            } else {
                this.setCustomValidity('');
            }
        });
    }
    
    // ==========================================
    // ВАЛИДАЦИЯ ИНТЕРЕСОВ
    // ==========================================
    
    const interestsForm = document.querySelector('form:has(input[name="interests[]"])');
    if (interestsForm) {
        const checkboxes = interestsForm.querySelectorAll('input[name="interests[]"]');
        const submitBtn = interestsForm.querySelector('button[name="register_step3"]');
        
        interestsForm.addEventListener('submit', function(e) {
            const checked = Array.from(checkboxes).filter(cb => cb.checked);
            if (checked.length === 0) {
                e.preventDefault();
                alert('Выберите хотя бы один интерес');
            }
        });
    }
    
    // ==========================================
    // АНИМАЦИЯ ЗАГРУЗКИ
    // ==========================================
    
    const allForms = document.querySelectorAll('form');
    allForms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('button[type="submit"]:not(.btn-link)');
            if (submitBtn && !submitBtn.disabled) {
                submitBtn.disabled = true;
                submitBtn.style.opacity = '0.7';
                const originalText = submitBtn.innerHTML;
                submitBtn.innerHTML = '⏳ Загрузка...';
                
                setTimeout(() => {
                    submitBtn.disabled = false;
                    submitBtn.style.opacity = '1';
                    submitBtn.innerHTML = originalText;
                }, 10000);
            }
        });
    });
});
