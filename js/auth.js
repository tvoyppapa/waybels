/* ==========================================
   AQUM AUTH.JS - ФИНАЛЬНАЯ ВЕРСИЯ
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
    
    const toggleLinks = document.querySelectorAll('.input-type-toggle a');
    const loginTypeInput = document.getElementById('login-type');
    const registerLoginInput = document.getElementById('register-login');
    const loginLabel = document.getElementById('login-label');
    
    toggleLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Убираем active у всех
            toggleLinks.forEach(l => l.classList.remove('active'));
            // Добавляем active текущей
            this.classList.add('active');
            
            const type = this.getAttribute('data-type');
            if (loginTypeInput) {
                loginTypeInput.value = type;
            }
            
            // Меняем label и placeholder
            if (registerLoginInput && loginLabel) {
                if (type === 'email') {
                    // Меняем только первую часть label (до span)
                    const labelText = loginLabel.childNodes[0];
                    if (labelText) {
                        labelText.textContent = 'Email ';
                    }
                    registerLoginInput.placeholder = 'example@mail.com';
                    registerLoginInput.type = 'email';
                } else {
                    const labelText = loginLabel.childNodes[0];
                    if (labelText) {
                        labelText.textContent = 'Номер телефона ';
                    }
                    registerLoginInput.placeholder = '+7 999 123 45 67';
                    registerLoginInput.type = 'tel';
                }
                registerLoginInput.value = '';
            }
        });
    });
    
    // ==========================================
    // ФОРМАТИРОВАНИЕ ИМЕНИ (ТОЛЬКО БУКВЫ)
    // ==========================================
    
    const nameInput = document.getElementById('register-name');
    if (nameInput) {
        nameInput.addEventListener('input', function(e) {
            // Удаляем всё кроме букв, пробелов и дефисов
            this.value = this.value.replace(/[^а-яёА-ЯЁa-zA-Z\s\-]/g, '');
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
                // Оставляем только цифры и +
                let value = this.value.replace(/[^\d+]/g, '');
                
                // Автоматически добавляем +7
                if (value.startsWith('8')) {
                    value = '+7' + value.slice(1);
                } else if (value.startsWith('7') && !value.startsWith('+')) {
                    value = '+' + value;
                } else if (!value.startsWith('+') && value.length > 0 && !value.startsWith('7')) {
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
        
        interestsForm.addEventListener('submit', function(e) {
            const checked = Array.from(checkboxes).filter(cb => cb.checked);
            if (checked.length === 0 && e.submitter?.name !== 'skip_interests') {
                e.preventDefault();
                alert('Выберите хотя бы один интерес');
            }
        });
    }
});
