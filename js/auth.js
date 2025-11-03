/* ==========================================
   AQUM AUTH.JS - УПРАВЛЕНИЕ ФОРМАМИ
   ========================================== */

document.addEventListener('DOMContentLoaded', function() {
    
    // Переключение между формами
    window.showRegister = function() {
        document.getElementById('login-form')?.classList.remove('active');
        document.getElementById('register-form')?.classList.add('active');
    };
    
    window.showLogin = function() {
        document.getElementById('register-form')?.classList.remove('active');
        document.getElementById('login-form')?.classList.add('active');
    };
    
    // Показываем форму входа по умолчанию (если нет шага регистрации)
    const loginForm = document.getElementById('login-form');
    if (loginForm && !loginForm.classList.contains('active')) {
        const registerForm = document.getElementById('register-form');
        if (registerForm && !registerForm.classList.contains('active')) {
            loginForm.classList.add('active');
        }
    }
    
    // ==========================================
    // ВАЛИДАЦИЯ И ФОРМАТИРОВАНИЕ ИМЕНИ
    // ==========================================
    
    const nameInput = document.getElementById('register-name');
    if (nameInput) {
        // Удаляем цифры и спецсимволы при вводе
        nameInput.addEventListener('input', function(e) {
            this.value = this.value.replace(/[0-9_\.@#$%^&*()+=\[\]{};:'",<>?\/\\|`~!]/g, '');
        });
        
        // Автоматическая капитализация при потере фокуса
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
    
    const loginInput = document.getElementById('register-login');
    if (loginInput) {
        loginInput.addEventListener('input', function(e) {
            let value = this.value;
            
            // Если начинается с цифры или +, форматируем как телефон
            if (/^[\d+]/.test(value)) {
                // Удаляем все кроме цифр и +
                value = value.replace(/[^\d+]/g, '');
                
                // Если начинается с 8, заменяем на +7
                if (value.startsWith('8')) {
                    value = '+7' + value.slice(1);
                }
                
                // Если начинается с 7, добавляем +
                if (value.startsWith('7') && !value.startsWith('+')) {
                    value = '+' + value;
                }
                
                this.value = value;
            }
        });
        
        // Подсказка при фокусе
        loginInput.addEventListener('focus', function(e) {
            if (!this.value) {
                this.placeholder = 'example@mail.com или +7...';
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
                this.setCustomValidity('Минимальный возраст: 13 лет');
            } else if (value > 100) {
                this.setCustomValidity('Максимальный возраст: 100 лет');
            } else {
                this.setCustomValidity('');
            }
        });
    }
    
    // ==========================================
    // ПРОВЕРКА ПАРОЛЕЙ
    // ==========================================
    
    const registerForm = document.getElementById('register-form');
    if (registerForm) {
        const passwordInput = registerForm.querySelector('input[name="password"]');
        const confirmInput = registerForm.querySelector('input[name="password_confirm"]');
        
        if (passwordInput && confirmInput) {
            function checkPasswords() {
                if (confirmInput.value && passwordInput.value !== confirmInput.value) {
                    confirmInput.setCustomValidity('Пароли не совпадают');
                } else {
                    confirmInput.setCustomValidity('');
                }
            }
            
            passwordInput.addEventListener('input', checkPasswords);
            confirmInput.addEventListener('input', checkPasswords);
        }
    }
    
    // ==========================================
    // ВЫБОР ИНТЕРЕСОВ (минимум 1)
    // ==========================================
    
    const interestsForm = document.querySelector('form:has(input[name="interests[]"])');
    if (interestsForm) {
        const checkboxes = interestsForm.querySelectorAll('input[name="interests[]"]');
        const submitBtn = interestsForm.querySelector('button[name="register_step3"]');
        
        function updateInterestsButton() {
            const checked = Array.from(checkboxes).filter(cb => cb.checked);
            if (submitBtn) {
                if (checked.length === 0) {
                    submitBtn.style.opacity = '0.6';
                    submitBtn.style.cursor = 'not-allowed';
                } else {
                    submitBtn.style.opacity = '1';
                    submitBtn.style.cursor = 'pointer';
                }
            }
        }
        
        checkboxes.forEach(checkbox => {
            checkbox.addEventListener('change', updateInterestsButton);
        });
        
        updateInterestsButton();
        
        // Валидация при отправке
        interestsForm.addEventListener('submit', function(e) {
            const checked = Array.from(checkboxes).filter(cb => cb.checked);
            if (checked.length === 0) {
                e.preventDefault();
                alert('Выберите хотя бы один интерес');
            }
        });
    }
    
    // ==========================================
    // АНИМАЦИЯ ПРИ ОТПРАВКЕ ФОРМЫ
    // ==========================================
    
    const allForms = document.querySelectorAll('form');
    allForms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn && !submitBtn.disabled) {
                submitBtn.disabled = true;
                submitBtn.style.opacity = '0.7';
                const originalText = submitBtn.innerHTML;
                submitBtn.innerHTML = '<span style="display: inline-block; animation: spin 1s linear infinite;">⏳</span> Загрузка...';
                
                // Через 10 секунд возвращаем кнопку (если что-то пошло не так)
                setTimeout(() => {
                    submitBtn.disabled = false;
                    submitBtn.style.opacity = '1';
                    submitBtn.innerHTML = originalText;
                }, 10000);
            }
        });
    });
    
    // ==========================================
    // АВТОЗАПОЛНЕНИЕ ТЕЛЕФОНА ПО ГЕОЛОКАЦИИ
    // ==========================================
    
    // Определяем страну по таймзоне (простой способ)
    function getCountryCode() {
        const timezone = Intl.DateTimeFormat().resolvedOptions().timeZone;
        
        // Список популярных стран и их телефонных кодов
        const countryCodes = {
            'Europe/Moscow': '+7',
            'Asia/Almaty': '+7',
            'Europe/Kiev': '+380',
            'Europe/Minsk': '+375',
            'Asia/Baku': '+994',
            'Asia/Yerevan': '+374',
            'Asia/Tbilisi': '+995',
            'Asia/Tashkent': '+998',
            'Asia/Bishkek': '+996',
            'Asia/Dushanbe': '+992',
            'Europe/Riga': '+371',
            'Europe/Tallinn': '+372',
            'Europe/Vilnius': '+370',
        };
        
        for (let tz in countryCodes) {
            if (timezone.includes(tz.split('/')[1])) {
                return countryCodes[tz];
            }
        }
        
        // По умолчанию Россия
        return '+7';
    }
    
    // Устанавливаем код страны в placeholder
    if (loginInput) {
        const countryCode = getCountryCode();
        const currentPlaceholder = loginInput.placeholder;
        if (currentPlaceholder.includes('...')) {
            loginInput.placeholder = currentPlaceholder.replace('...', countryCode);
        }
    }
    
    // ==========================================
    // ПЛАВНАЯ ПРОКРУТКА К ОШИБКАМ
    // ==========================================
    
    const alertError = document.querySelector('.alert-error');
    if (alertError) {
        alertError.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
    
    // ==========================================
    // KEYBOARD SHORTCUTS
    // ==========================================
    
    document.addEventListener('keydown', function(e) {
        // Enter на форме интересов - отправить
        if (e.key === 'Enter' && !e.shiftKey) {
            const activeForm = document.querySelector('.auth-form.active');
            if (activeForm && activeForm.querySelector('.interests-grid')) {
                e.preventDefault();
                activeForm.querySelector('button[type="submit"]')?.click();
            }
        }
    });
    
    // ==========================================
    // ПОКАЗ/СКРЫТИЕ ПАРОЛЯ
    // ==========================================
    
    const passwordInputs = document.querySelectorAll('input[type="password"]');
    passwordInputs.forEach(input => {
        const wrapper = input.parentElement;
        
        // Создаем кнопку показа пароля
        const toggleBtn = document.createElement('button');
        toggleBtn.type = 'button';
        toggleBtn.innerHTML = '👁️';
        toggleBtn.style.cssText = `
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            font-size: 20px;
            cursor: pointer;
            opacity: 0.5;
            transition: opacity 0.3s;
        `;
        
        toggleBtn.addEventListener('mouseenter', () => toggleBtn.style.opacity = '1');
        toggleBtn.addEventListener('mouseleave', () => toggleBtn.style.opacity = '0.5');
        
        toggleBtn.addEventListener('click', function() {
            if (input.type === 'password') {
                input.type = 'text';
                this.innerHTML = '👁️‍🗨️';
            } else {
                input.type = 'password';
                this.innerHTML = '👁️';
            }
        });
        
        // Делаем wrapper относительным
        wrapper.style.position = 'relative';
        
        // Добавляем кнопку после label
        if (wrapper.querySelector('label')) {
            wrapper.querySelector('label').insertAdjacentElement('afterend', toggleBtn);
        }
    });
});

/* ==========================================
   CSS АНИМАЦИЯ ЗАГРУЗКИ
   ========================================== */

const style = document.createElement('style');
style.textContent = `
    @keyframes spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }
`;
document.head.appendChild(style);
