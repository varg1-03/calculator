<?php
require_once 'db.php';

// Если уже авторизован, перенаправляем на главную
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';

// Функция для sanitize входных данных
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

// Защита от брутфорса - проверка количества попыток
if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = 0;
    $_SESSION['last_attempt_time'] = time();
}

// Сброс счетчика через 15 минут
if (time() - $_SESSION['last_attempt_time'] > 900) {
    $_SESSION['login_attempts'] = 0;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Проверка на слишком много попыток
    if ($_SESSION['login_attempts'] >= 5) {
        $error = 'Слишком много неудачных попыток входа. Попробуйте через 15 минут.';
    } else {
        $username = sanitize_input($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (empty($username) || empty($password)) {
            $error = 'Заполните все поля';
            $_SESSION['login_attempts']++;
            $_SESSION['last_attempt_time'] = time();
        } else {
            // Ищем пользователя с использованием подготовленного выражения
            $stmt = mysqli_prepare($conn, "SELECT user_id, username, password, full_name, role, is_active FROM users WHERE (username = ? OR email = ?)");
            mysqli_stmt_bind_param($stmt, "ss", $username, $username);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $user = mysqli_fetch_assoc($result);
            
            if ($user && $user['is_active'] == 1 && password_verify($password, $user['password'])) {
                // Успешная авторизация - сбрасываем счетчик
                $_SESSION['login_attempts'] = 0;
                
                // Регенерируем ID сессии для защиты от фиксации сессии
                session_regenerate_id(true);
                
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['user_name'] = $user['full_name'] ?? $user['username'];
                $_SESSION['user_role'] = $user['role'];
                
                // Обновляем время последнего входа
                $updateStmt = mysqli_prepare($conn, "UPDATE users SET last_login = NOW() WHERE user_id = ?");
                mysqli_stmt_bind_param($updateStmt, "i", $user['user_id']);
                mysqli_stmt_execute($updateStmt);
                
                // Логируем вход
                $logStmt = mysqli_prepare($conn, "INSERT INTO user_logs (user_id, action, ip_address) VALUES (?, 'login', ?)");
                $ip = $_SERVER['REMOTE_ADDR'];
                mysqli_stmt_bind_param($logStmt, "is", $user['user_id'], $ip);
                mysqli_stmt_execute($logStmt);
                
                header('Location: index.php');
                exit;
            } else {
                $error = 'Неверное имя пользователя или пароль';
                $_SESSION['login_attempts']++;
                $_SESSION['last_attempt_time'] = time();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Вход в систему - Калькулятор ЗП</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* Стили для страниц авторизации */
        body {
            margin: 0;
            padding: 20px;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .page-wrapper {
            flex: 1;
            display: flex;
            flex-direction: column;
            max-width: 1400px;
            margin: 0 auto;
            width: 100%;
        }
        
        .auth-container {
            max-width: 400px;
            margin: 50px auto;
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 10px 30px rgba(106, 78, 142, 0.2);
            width: 100%;
            box-sizing: border-box;
        }
        
        .auth-title {
            text-align: center;
            color: #6b4e8e;
            margin-bottom: 30px;
            font-size: 28px;
        }
        
        .auth-form .input-section {
            margin-bottom: 20px;
        }
        
        .auth-form label {
            display: block;
            margin-bottom: 8px;
            color: #4a2e5e;
            font-weight: 500;
        }
        
        .auth-form input {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e0d4f0;
            border-radius: 10px;
            font-size: 16px;
            transition: all 0.3s;
            box-sizing: border-box;
        }
        
        .auth-form input:focus {
            border-color: #6b4e8e;
            outline: none;
            box-shadow: 0 0 0 3px rgba(106, 78, 142, 0.1);
        }
        
        .auth-btn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #6b4e8e, #8a5ea0);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .auth-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(106, 78, 142, 0.3);
        }
        
        .auth-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
        
        .auth-links {
            text-align: center;
            margin-top: 20px;
        }
        
        .auth-links a {
            color: #6b4e8e;
            text-decoration: none;
        }
        
        .auth-links a:hover {
            text-decoration: underline;
        }
        
        .error-message {
            background: #fff0f0;
            color: #e74c3c;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
            border-left: 4px solid #e74c3c;
        }
        
        .attempts-left {
            font-size: 12px;
            color: #8a5ea0;
            text-align: right;
            margin-top: 5px;
        }
        
        /* Стили для навигации */
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
        
        @media (max-width: 768px) {
            .nav-container {
                flex-direction: column;
                gap: 15px;
                padding: 15px;
            }
            
            .nav-menu {
                flex-direction: column;
                align-items: stretch;
                gap: 10px;
                width: 100%;
            }
            
            .nav-link {
                text-align: center;
                width: 100%;
            }
            
            .auth-container {
                margin: 20px auto;
                padding: 30px 20px;
            }
        }
    </style>
</head>
<body>
    <div class="page-wrapper">
        <?php include 'partials/navbar.php'; ?>
        
        <div class="auth-container">
            <h1 class="auth-title">Вход в систему</h1>
            
            <?php if ($error): ?>
                <div class="error-message"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>
            
            <?php if ($_SESSION['login_attempts'] > 0): ?>
                <div class="attempts-left">
                    Осталось попыток: <?php echo 5 - $_SESSION['login_attempts']; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" class="auth-form" id="loginForm">
                <div class="input-section">
                    <label>Имя пользователя или Email</label>
                    <input type="text" 
                           name="username" 
                           required 
                           value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username'], ENT_QUOTES, 'UTF-8') : ''; ?>"
                           <?php echo $_SESSION['login_attempts'] >= 5 ? 'disabled' : ''; ?>>
                </div>
                
                <div class="input-section">
                    <label>Пароль</label>
                    <input type="password" 
                           name="password" 
                           required
                           <?php echo $_SESSION['login_attempts'] >= 5 ? 'disabled' : ''; ?>>
                </div>
                
                <button type="submit" class="auth-btn" <?php echo $_SESSION['login_attempts'] >= 5 ? 'disabled' : ''; ?>>
                    Войти
                </button>
            </form>
            
            <div class="auth-links">
                <p>Нет аккаунта? <a href="register.php">Зарегистрироваться</a></p>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('loginForm');
        
        // Защита от вставки скриптов
        const inputs = document.querySelectorAll('input[type="text"]');
        inputs.forEach(input => {
            input.addEventListener('input', function() {
                // Удаляем потенциально опасные символы
                this.value = this.value.replace(/[<>]/g, '');
            });
        });
        
        // Дополнительная проверка на клиентской стороне
        form.addEventListener('submit', function(e) {
            const username = document.querySelector('input[name="username"]').value;
            
            // Проверка на очень длинные строки (защита от DoS)
            if (username.length > 100) {
                e.preventDefault();
                alert('Имя пользователя слишком длинное');
            }
        });
    });
    </script>
</body>
</html>