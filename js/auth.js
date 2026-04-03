// auth.js - скрипты для работы с авторизацией

// Функция для проверки прав доступа
function checkAdminAccess() {
    if (!CURRENT_USER.isAuthenticated) {
        showAuthModal('Требуется авторизация', 'Для доступа к этой функции необходимо войти в систему');
        return false;
    }
    return true;
}

function checkAdminRole() {
    if (!CURRENT_USER.isAuthenticated) {
        showAuthModal('Требуется авторизация', 'Для доступа к этой функции необходимо войти в систему');
        return false;
    }
    if (!CURRENT_USER.isAdmin) {
        showAuthModal('Доступ запрещен', 'Эта функция доступна только администраторам');
        return false;
    }
    return true;
}

// Модальное окно для сообщений
function showAuthModal(title, message) {
    // Создаем модальное окно, если его нет
    let modal = document.getElementById('auth-modal');
    if (!modal) {
        modal = document.createElement('div');
        modal.id = 'auth-modal';
        modal.className = 'auth-modal';
        modal.innerHTML = `
            <div class="auth-modal-content">
                <div class="auth-modal-header">
                    <h3 id="auth-modal-title"></h3>
                    <span class="auth-modal-close">&times;</span>
                </div>
                <div class="auth-modal-body" id="auth-modal-message"></div>
                <div class="auth-modal-footer">
                    <button class="auth-modal-btn" onclick="closeAuthModal()">OK</button>
                    <a href="login.php" class="auth-modal-btn primary">Войти</a>
                </div>
            </div>
        `;
        document.body.appendChild(modal);
        
        // Добавляем стили
        const style = document.createElement('style');
        style.textContent = `
            .auth-modal {
                display: none;
                position: fixed;
                z-index: 9999;
                left: 0;
                top: 0;
                width: 100%;
                height: 100%;
                background-color: rgba(0,0,0,0.5);
                animation: fadeIn 0.3s;
            }
            
            .auth-modal.show {
                display: flex;
                align-items: center;
                justify-content: center;
            }
            
            .auth-modal-content {
                background: white;
                border-radius: 20px;
                max-width: 400px;
                width: 90%;
                box-shadow: 0 10px 30px rgba(106, 78, 142, 0.3);
                animation: slideIn 0.3s;
            }
            
            .auth-modal-header {
                padding: 20px;
                border-bottom: 1px solid #e0d4f0;
                display: flex;
                justify-content: space-between;
                align-items: center;
            }
            
            .auth-modal-header h3 {
                color: #6b4e8e;
                margin: 0;
            }
            
            .auth-modal-close {
                font-size: 24px;
                cursor: pointer;
                color: #999;
            }
            
            .auth-modal-close:hover {
                color: #6b4e8e;
            }
            
            .auth-modal-body {
                padding: 20px;
                color: #4a2e5e;
            }
            
            .auth-modal-footer {
                padding: 20px;
                border-top: 1px solid #e0d4f0;
                display: flex;
                gap: 10px;
                justify-content: flex-end;
            }
            
            .auth-modal-btn {
                padding: 10px 20px;
                border: none;
                border-radius: 8px;
                cursor: pointer;
                font-size: 14px;
                transition: all 0.3s;
                text-decoration: none;
                display: inline-block;
            }
            
            .auth-modal-btn.primary {
                background: linear-gradient(135deg, #6b4e8e, #8a5ea0);
                color: white;
            }
            
            .auth-modal-btn.primary:hover {
                transform: translateY(-2px);
                box-shadow: 0 5px 15px rgba(106, 78, 142, 0.3);
            }
            
            .auth-modal-btn:not(.primary) {
                background: #f0f0f0;
                color: #666;
            }
            
            .auth-modal-btn:not(.primary):hover {
                background: #e0e0e0;
            }
            
            @keyframes slideIn {
                from {
                    opacity: 0;
                    transform: translateY(-30px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }
        `;
        document.head.appendChild(style);
        
        // Закрытие по клику на крестик
        modal.querySelector('.auth-modal-close').onclick = closeAuthModal;
        
        // Закрытие по клику вне модального окна
        modal.onclick = function(e) {
            if (e.target === modal) {
                closeAuthModal();
            }
        };
    }
    
    document.getElementById('auth-modal-title').textContent = title;
    document.getElementById('auth-modal-message').textContent = message;
    modal.classList.add('show');
}

function closeAuthModal() {
    const modal = document.getElementById('auth-modal');
    if (modal) {
        modal.classList.remove('show');
    }
}

// Добавляем обработчики для защищенных элементов
document.addEventListener('DOMContentLoaded', () => {
    // Защита административных функций в калькуляторе
    const adminElements = document.querySelectorAll('.requires-admin');
    adminElements.forEach(el => {
        el.addEventListener('click', (e) => {
            if (!CURRENT_USER.isAdmin) {
                e.preventDefault();
                showAuthModal('Доступ запрещен', 'Эта функция доступна только администраторам');
            }
        });
    });
    
    // Защита функций, требующих авторизации
    const authElements = document.querySelectorAll('.requires-auth');
    authElements.forEach(el => {
        el.addEventListener('click', (e) => {
            if (!CURRENT_USER.isAuthenticated) {
                e.preventDefault();
                showAuthModal('Требуется авторизация', 'Для использования этой функции необходимо войти в систему');
            }
        });
    });
});