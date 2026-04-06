<?php
// Проверяем, авторизован ли пользователь
$isLoggedIn = isset($_SESSION['user_id']);
$isAdmin = $isLoggedIn && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
$currentPage = basename($_SERVER['PHP_SELF']);

// Проверяем, нужно ли показывать админ-ссылку (только если в URL есть ?admin)
$showAdminLink = isset($_GET['admin']) && $_GET['admin'] === '1' && $isAdmin;
?>

<nav class="navbar">
    <div class="nav-container">
        <div class="nav-left">
            <div class="nav-logo">
                <a href="index.php">Калькулятор ЗП</a>
            </div>
            
            <!-- Баннер с предупреждением ТОЛЬКО для НЕ авторизованных пользователей (справа от названия) -->
            <?php if (!$isLoggedIn): ?>
            <div class="warning-banner">
                <div class="warning-icon">⚠️</div>
                <div class="warning-text">
                    <strong>Обратите внимание!</strong> Данный калькулятор предназначен только для предварительного расчёта заработной платы. 
                    Он помогает примерно понять, на какой доход можно рассчитывать при условии выполнения показателей, 
                    но не заменяет окончательный расчёт заработной платы.
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="nav-right">
            <?php if ($isLoggedIn): ?>
                <div class="nav-user">
                    <span class="user-name"><?php echo htmlspecialchars($_SESSION['user_name'] ?? $_SESSION['username']); ?></span>
                    <?php if ($isAdmin): ?>
                        <span class="admin-badge">Admin</span>
                    <?php endif; ?>
                    <a href="logout.php" class="nav-link logout-btn">Выйти</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Скрытая админ-панель (доступна только по ссылке) -->
    <?php if ($showAdminLink): ?>
    <div class="admin-panel">
        <div class="admin-panel-container">
            <span class="admin-panel-title">🔧 Админ-панель</span>
            <div class="admin-links">
                <a href="admin/index.php?admin=1" class="admin-link">📊 Дашборд</a>
                <a href="admin/users.php?admin=1" class="admin-link">👥 Пользователи</a>
                <a href="admin/positions.php?admin=1" class="admin-link">📋 Должности</a>
                <a href="admin/stores.php?admin=1" class="admin-link">🏪 Магазины</a>
                <a href="admin/rates.php?admin=1" class="admin-link">💰 Часовые ставки</a>
                <a href="admin/fixed_salaries.php?admin=1" class="admin-link">💵 Фиксированные оклады</a>
            </div>
        </div>
    </div>
    <?php endif; ?>
</nav>

<style>
.navbar {
    background: white;
    border-radius: 15px;
    box-shadow: 0 4px 15px rgba(106, 78, 142, 0.15);
    width: 100%;
    margin-bottom: 20px;
    overflow: hidden;
}

.nav-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 15px 30px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 20px;
}

/* Левая часть с логотипом и баннером */
.nav-left {
    display: flex;
    align-items: center;
    gap: 20px;
    flex: 1;
    flex-wrap: wrap;
}

.nav-logo a {
    font-size: 24px;
    font-weight: 600;
    color: #6b4e8e;
    text-decoration: none;
    transition: color 0.3s;
    white-space: nowrap;
}

.nav-logo a:hover {
    color: #8a5ea0;
}

/* Баннер с предупреждением */
.warning-banner {
    background: linear-gradient(135deg, #fff8e1, #ffecb3);
    border: 1px solid #ffd54f;
    border-radius: 10px;
    padding: 8px 16px;
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 14px;
    color: #856404;
    flex: 1;
    min-width: 250px;
}

.warning-icon {
    font-size: 16px;
    flex-shrink: 0;
}

.warning-text {
    line-height: 1.3;
    flex: 1;
}

.warning-text strong {
    color: #856404;
}

/* Правая часть навигации */
.nav-right {
    display: flex;
    align-items: center;
}

/* Бейдж администратора */
.admin-badge {
    background: #ffc107;
    color: #212529;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 600;
    margin-left: 5px;
}

/* Информация о пользователе */
.nav-user {
    display: flex;
    align-items: center;
    gap: 15px;
}

.user-name {
    color: #6b4e8e;
    font-weight: 600;
    font-size: 15px;
}

.nav-link {
    color: #4a2e5e;
    text-decoration: none;
    font-weight: 500;
    padding: 8px 16px;
    border-radius: 8px;
    transition: all 0.3s;
    font-size: 15px;
}

.nav-link:hover {
    background: #f0e6ff;
    color: #6b4e8e;
}

.logout-btn {
    background: #fff0f0;
    color: #e74c3c;
}

.logout-btn:hover {
    background: #ffe0e0;
    color: #c0392b;
}

/* Скрытая админ-панель */
.admin-panel {
    background: linear-gradient(135deg, #2c3e50, #1a252f);
    border-top: 1px solid #34495e;
    padding: 10px 0;
}

.admin-panel-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 0 30px;
    display: flex;
    align-items: center;
    gap: 30px;
    flex-wrap: wrap;
}

.admin-panel-title {
    color: #ffc107;
    font-weight: 600;
    font-size: 14px;
    letter-spacing: 1px;
}

.admin-links {
    display: flex;
    gap: 20px;
    flex-wrap: wrap;
}

.admin-link {
    color: #ecf0f1;
    text-decoration: none;
    font-size: 13px;
    padding: 5px 10px;
    border-radius: 6px;
    transition: all 0.3s;
}

.admin-link:hover {
    background: rgba(255, 255, 255, 0.1);
    color: #ffc107;
}

/* Адаптивность */
@media (max-width: 1024px) {
    .nav-container {
        flex-direction: column;
        align-items: stretch;
    }
    
    .nav-left {
        flex-direction: column;
        align-items: stretch;
    }
    
    .nav-logo a {
        text-align: center;
        white-space: normal;
    }
    
    .warning-banner {
        width: 100%;
    }
    
    .nav-right {
        justify-content: center;
    }
    
    .admin-panel-container {
        flex-direction: column;
        align-items: stretch;
        gap: 15px;
        padding: 10px 15px;
    }
    
    .admin-links {
        justify-content: center;
    }
}

@media (max-width: 768px) {
    .warning-banner {
        padding: 10px 12px;
        font-size: 14px;
    }
    
    .warning-icon {
        font-size: 14px;
    }
    
    .admin-links {
        gap: 10px;
    }
    
    .admin-link {
        font-size: 11px;
        padding: 4px 8px;
    }
}

@media (max-width: 480px) {
    .nav-container {
        padding: 12px 15px;
    }
    
    .nav-logo a {
        font-size: 20px;
    }
    
    .user-name {
        font-size: 13px;
    }
    
    .nav-link {
        padding: 6px 12px;
        font-size: 13px;
    }
    
    .warning-banner {
        font-size: 12px;
        padding: 8px 10px;
    }
    
    .admin-panel-title {
        font-size: 12px;
        text-align: center;
    }
    
    .admin-links {
        justify-content: center;
    }
}
</style>

<script>
// Добавляем обработку для мобильного меню
document.addEventListener('DOMContentLoaded', function() {
    const dropdownToggles = document.querySelectorAll('.dropdown-toggle');
    
    dropdownToggles.forEach(toggle => {
        toggle.addEventListener('click', function(e) {
            if (window.innerWidth <= 768) {
                e.preventDefault();
                const dropdown = this.closest('.nav-dropdown');
                dropdown.classList.toggle('active');
            }
        });
    });
});
</script>