<?php
// Проверяем, авторизован ли пользователь
$isLoggedIn = isset($_SESSION['user_id']);
$isAdmin = $isLoggedIn && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
$currentPage = basename($_SERVER['PHP_SELF']);
?>

<nav class="navbar">
    <div class="nav-container">
        <div class="nav-logo">
            <a href="index.php">Калькулятор ЗП</a>
        </div>
        
        <div class="nav-menu">
            <?php if ($isLoggedIn): ?>
                <a href="index.php" class="nav-link <?php echo $currentPage == 'index.php' ? 'active' : ''; ?>">
                    Калькулятор
                </a>
                
                <?php if ($isAdmin): ?>
                <div class="nav-dropdown">
                    <button class="nav-link dropdown-toggle">
                        Админ-панель <span class="arrow">▼</span>
                    </button>
                    <div class="dropdown-menu">
                        <a href="admin/index.php" class="dropdown-item">📊 Дашборд</a>
                        <a href="admin/users.php" class="dropdown-item">👥 Пользователи</a>
                        <a href="admin/positions.php" class="dropdown-item">📋 Должности</a>
                        <a href="admin/stores.php" class="dropdown-item">🏪 Магазины</a>
                        <a href="admin/rates.php" class="dropdown-item">💰 Часовые ставки</a>
                        <a href="admin/fixed_salaries.php" class="dropdown-item">💵 Фиксированные оклады</a>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="nav-user">
                    <span class="user-name"><?php echo htmlspecialchars($_SESSION['user_name'] ?? $_SESSION['username']); ?></span>
                    <?php if ($isAdmin): ?>
                        <span class="admin-badge">Admin</span>
                    <?php endif; ?>
                    <a href="logout.php" class="nav-link logout-btn">Выйти</a>
                </div>
            <?php else: ?>
                <a href="login.php" class="nav-link <?php echo $currentPage == 'login.php' ? 'active' : ''; ?>">Вход</a>
                <a href="register.php" class="nav-link <?php echo $currentPage == 'register.php' ? 'active' : ''; ?>">Регистрация</a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<style>
.navbar {
    background: white;
    border-radius: 15px;
    box-shadow: 0 4px 15px rgba(106, 78, 142, 0.15);
    width: 100%;
    margin-bottom: 20px;
}

.nav-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 15px 30px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.nav-logo a {
    font-size: 24px;
    font-weight: 600;
    color: #6b4e8e;
    text-decoration: none;
    transition: color 0.3s;
}

.nav-logo a:hover {
    color: #8a5ea0;
}

.nav-menu {
    display: flex;
    align-items: center;
    gap: 20px;
    flex-wrap: wrap;
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

.nav-link.active {
    background: #6b4e8e;
    color: white;
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

/* Выпадающее меню */
.nav-dropdown {
    position: relative;
    display: inline-block;
}

.dropdown-toggle {
    background: none;
    border: none;
    cursor: pointer;
    font-size: 15px;
    display: flex;
    align-items: center;
    gap: 5px;
    padding: 8px 16px;
    border-radius: 8px;
    color: #4a2e5e;
    font-weight: 500;
}

.dropdown-toggle:hover {
    background: #f0e6ff;
    color: #6b4e8e;
}

.dropdown-toggle .arrow {
    font-size: 12px;
    transition: transform 0.3s;
}

.nav-dropdown:hover .dropdown-toggle .arrow {
    transform: rotate(180deg);
}

.dropdown-menu {
    position: absolute;
    top: 100%;
    left: 0;
    min-width: 220px;
    background: white;
    border-radius: 12px;
    box-shadow: 0 5px 25px rgba(106, 78, 142, 0.2);
    padding: 10px 0;
    display: none;
    z-index: 1000;
    margin-top: 5px;
}

.nav-dropdown:hover .dropdown-menu {
    display: block;
    animation: fadeIn 0.3s ease;
}

.dropdown-item {
    display: block;
    padding: 12px 20px;
    color: #4a2e5e;
    text-decoration: none;
    transition: background 0.3s;
    font-size: 14px;
}

.dropdown-item:hover {
    background: #f0e6ff;
    color: #6b4e8e;
}

/* Информация о пользователе */
.nav-user {
    display: flex;
    align-items: center;
    gap: 15px;
    padding-left: 20px;
    border-left: 2px solid #e0d4f0;
}

.user-name {
    color: #6b4e8e;
    font-weight: 600;
    font-size: 15px;
}

.logout-btn {
    background: #fff0f0;
    color: #e74c3c;
}

.logout-btn:hover {
    background: #ffe0e0;
    color: #c0392b;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Адаптивность */
@media (max-width: 1024px) {
    .nav-container {
        flex-direction: column;
        gap: 15px;
        padding: 15px;
    }
    
    .nav-menu {
        justify-content: center;
        width: 100%;
    }
}

@media (max-width: 768px) {
    .nav-menu {
        flex-direction: column;
        align-items: stretch;
        gap: 10px;
    }
    
    .nav-link, .dropdown-toggle {
        text-align: center;
        width: 100%;
    }
    
    .nav-user {
        border-left: none;
        padding-left: 0;
        justify-content: center;
        flex-wrap: wrap;
    }
    
    .dropdown-menu {
        position: static;
        box-shadow: none;
        border: 1px solid #e0d4f0;
        margin-top: 5px;
        width: 100%;
    }
    
    .nav-dropdown:hover .dropdown-menu {
        display: none;
    }
    
    .nav-dropdown.active .dropdown-menu {
        display: block;
    }
}
</style>

<script>
// Добавляем обработку для мобильного меню
document.addEventListener('DOMContentLoaded', function() {
    const dropdownToggles = document.querySelectorAll('.dropdown-toggle');
    
    // Определение touch-устройства
    if ('ontouchstart' in window) {
        document.body.classList.add('touch-device');
    }
    
    // Обработка выпадающих меню на touch
    const dropdowns = document.querySelectorAll('.nav-dropdown');
    dropdowns.forEach(dropdown => {
        const toggle = dropdown.querySelector('.dropdown-toggle');
        
        if (toggle) {
            toggle.addEventListener('click', (e) => {
                if (window.innerWidth <= 768) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    // Закрываем другие открытые дропдауны
                    dropdowns.forEach(d => {
                        if (d !== dropdown) d.classList.remove('active');
                    });
                    
                    dropdown.classList.toggle('active');
                }
            });
        }
    });
    
    // Закрытие дропдаунов при клике вне
    document.addEventListener('click', (e) => {
        if (window.innerWidth <= 768) {
            if (!e.target.closest('.nav-dropdown')) {
                dropdowns.forEach(d => d.classList.remove('active'));
            }
        }
    });
});
</script>