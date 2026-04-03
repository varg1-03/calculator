<?php
require_once 'db.php';

if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$errors = [];
$success = '';

// Функция для sanitize входных данных
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Санитизация входных данных
    $username = sanitize_input($_POST['username'] ?? '');
    $email = sanitize_input($_POST['email'] ?? '');
    $password = $_POST['password'] ?? ''; // Пароль не санитизируем, чтобы не менять спецсимволы
    $confirm_password = $_POST['confirm_password'] ?? '';
    $full_name = sanitize_input($_POST['full_name'] ?? '');
    
    // Валидация имени пользователя (только английские буквы и цифры)
    if (empty($username)) {
        $errors[] = 'Введите имя пользователя';
    } elseif (strlen($username) < 3) {
        $errors[] = 'Имя пользователя должно быть не менее 3 символов';
    } elseif (strlen($username) > 30) {
        $errors[] = 'Имя пользователя должно быть не более 30 символов';
    } elseif (!preg_match('/^[a-zA-Z0-9]+$/', $username)) {
        $errors[] = 'Имя пользователя может содержать только английские буквы и цифры';
    }
    
    // Валидация email
    if (empty($email)) {
        $errors[] = 'Введите email';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Введите корректный email адрес';
    } elseif (strlen($email) > 100) {
        $errors[] = 'Email слишком длинный';
    }
    
    // Валидация пароля
    if (empty($password)) {
        $errors[] = 'Введите пароль';
    } elseif (strlen($password) < 6) {
        $errors[] = 'Пароль должен быть не менее 6 символов';
    } elseif (strlen($password) > 72) { // Ограничение bcrypt
        $errors[] = 'Пароль должен быть не более 72 символов';
    } elseif (!preg_match('/^[a-zA-Z0-9!@#$%^&*()_+\-=\[\]{};:\'",.<>\/?]+$/', $password)) {
        $errors[] = 'Пароль содержит недопустимые символы';
    }
    
    // Проверка совпадения паролей
    if ($password !== $confirm_password) {
        $errors[] = 'Пароли не совпадают';
    }
    
    // Валидация полного имени (опционально)
    if (!empty($full_name) && strlen($full_name) > 100) {
        $errors[] = 'Полное имя слишком длинное';
    } elseif (!empty($full_name) && !preg_match('/^[a-zA-Zа-яА-ЯёЁ\s\-]+$/', $full_name)) {
        $errors[] = 'Полное имя может содержать только буквы, пробелы и дефисы';
    }
    
    // Проверка на SQL инъекции и XSS в данных
    if (!empty($errors)) {
        // Дополнительная проверка на потенциально опасные паттерны
        $dangerous_patterns = ['/<script/i', '/javascript:/i', '/onclick/i', '/onerror/i', '/onload/i'];
        foreach ($dangerous_patterns as $pattern) {
            if (preg_match($pattern, $username) || preg_match($pattern, $email) || preg_match($pattern, $full_name)) {
                $errors[] = 'Обнаружены потенциально опасные символы';
                break;
            }
        }
    }
    
    // Проверка уникальности с использованием подготовленных выражений
    if (empty($errors)) {
        $checkStmt = mysqli_prepare($conn, "SELECT user_id FROM users WHERE username = ? OR email = ?");
        mysqli_stmt_bind_param($checkStmt, "ss", $username, $email);
        mysqli_stmt_execute($checkStmt);
        mysqli_stmt_store_result($checkStmt);
        
        if (mysqli_stmt_num_rows($checkStmt) > 0) {
            $errors[] = 'Пользователь с таким именем или email уже существует';
        }
        mysqli_stmt_close($checkStmt);
    }
    
    // Регистрация с использованием подготовленных выражений
    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        $insertStmt = mysqli_prepare($conn, "INSERT INTO users (username, email, password, full_name, role) VALUES (?, ?, ?, ?, 'user')");
        mysqli_stmt_bind_param($insertStmt, "ssss", $username, $email, $hashed_password, $full_name);
        
        if (mysqli_stmt_execute($insertStmt)) {
            $success = 'Регистрация успешна! Теперь вы можете войти в систему.';
            
            // Очищаем POST данные после успешной регистрации
            $_POST = array();
        } else {
            $errors[] = 'Ошибка при регистрации. Попробуйте позже.';
        }
        mysqli_stmt_close($insertStmt);
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Регистрация - Калькулятор ЗП</title>
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
            max-width: 450px;
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
        
        .auth-form input.error {
            border-color: #e74c3c;
            background-color: #fff0f0;
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
            margin-top: 10px;
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
        
        .error-list {
            background: #fff0f0;
            color: #e74c3c;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #e74c3c;
        }
        
        .error-list ul {
            margin: 0;
            padding-left: 20px;
        }
        
        .success-message {
            background: #f0fff0;
            color: #27ae60;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
            border-left: 4px solid #27ae60;
        }
        
        .input-hint {
            font-size: 12px;
            color: #8a5ea0;
            margin-top: 5px;
            display: block;
        }
        
        .password-strength {
            height: 4px;
            background: #e0d4f0;
            border-radius: 2px;
            margin-top: 8px;
            overflow: hidden;
        }
        
        .password-strength-bar {
            height: 100%;
            width: 0;
            transition: width 0.3s, background-color 0.3s;
        }
        
        .password-strength-bar.weak {
            background: #e74c3c;
            width: 33.33%;
        }
        
        .password-strength-bar.medium {
            background: #f39c12;
            width: 66.66%;
        }
        
        .password-strength-bar.strong {
            background: #27ae60;
            width: 100%;
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
            <h1 class="auth-title">Регистрация</h1>
            
            <?php if (!empty($errors)): ?>
                <div class="error-list">
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="success-message"><?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>
            
            <form method="POST" class="auth-form" id="registerForm">
                <div class="input-section">
                    <label>Имя пользователя *</label>
                    <input type="text" 
                           name="username" 
                           id="username"
                           required 
                           pattern="[a-zA-Z0-9]+"
                           title="Только английские буквы и цифры"
                           value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username'], ENT_QUOTES, 'UTF-8') : ''; ?>"
                           class="<?php echo in_array('Имя пользователя может содержать только английские буквы и цифры', $errors) ? 'error' : ''; ?>">
                    <span class="input-hint">Только английские буквы и цифры, от 3 до 30 символов</span>
                </div>
                
                <div class="input-section">
                    <label>Email *</label>
                    <input type="email" 
                           name="email" 
                           id="email"
                           required 
                           value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email'], ENT_QUOTES, 'UTF-8') : ''; ?>"
                           class="<?php echo in_array('Введите корректный email адрес', $errors) ? 'error' : ''; ?>">
                    <span class="input-hint">Введите действующий email адрес</span>
                </div>
                
                <div class="input-section">
                    <label>Полное имя (необязательно)</label>
                    <input type="text" 
                           name="full_name" 
                           id="full_name"
                           value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name'], ENT_QUOTES, 'UTF-8') : ''; ?>">
                    <span class="input-hint">Может содержать буквы, пробелы и дефисы</span>
                </div>
                
                <div class="input-section">
                    <label>Пароль *</label>
                    <input type="password" 
                           name="password" 
                           id="password"
                           required 
                           minlength="6"
                           class="<?php echo in_array('Пароль должен быть не менее 6 символов', $errors) ? 'error' : ''; ?>">
                    <div class="password-strength">
                        <div class="password-strength-bar" id="passwordStrength"></div>
                    </div>
                    <span class="input-hint">Минимум 6 символов</span>
                </div>
                
                <div class="input-section">
                    <label>Подтверждение пароля *</label>
                    <input type="password" 
                           name="confirm_password" 
                           id="confirm_password"
                           required>
                    <span class="input-hint" id="passwordMatchHint"></span>
                </div>
                
                <button type="submit" class="auth-btn" id="submitBtn">Зарегистрироваться</button>
            </form>
            
            <div class="auth-links">
                <p>Уже есть аккаунт? <a href="login.php">Войти</a></p>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const password = document.getElementById('password');
        const confirmPassword = document.getElementById('confirm_password');
        const passwordStrength = document.getElementById('passwordStrength');
        const passwordMatchHint = document.getElementById('passwordMatchHint');
        const submitBtn = document.getElementById('submitBtn');
        const form = document.getElementById('registerForm');
        
        // Оценка сложности пароля
        function checkPasswordStrength(pwd) {
            let strength = 0;
            
            if (pwd.length >= 6) strength += 1;
            if (pwd.length >= 8) strength += 1;
            if (pwd.match(/[a-z]/) && pwd.match(/[A-Z]/)) strength += 1;
            if (pwd.match(/\d/)) strength += 1;
            if (pwd.match(/[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/)) strength += 1;
            
            return Math.min(strength, 3);
        }
        
        // Обновление индикатора сложности
        function updatePasswordStrength() {
            const pwd = password.value;
            const strength = checkPasswordStrength(pwd);
            
            passwordStrength.className = 'password-strength-bar';
            
            if (pwd.length === 0) {
                passwordStrength.style.width = '0';
            } else if (strength === 1) {
                passwordStrength.classList.add('weak');
            } else if (strength === 2) {
                passwordStrength.classList.add('medium');
            } else if (strength >= 3) {
                passwordStrength.classList.add('strong');
            }
        }
        
        // Проверка совпадения паролей
        function checkPasswordMatch() {
            if (confirmPassword.value.length === 0) {
                passwordMatchHint.textContent = '';
                return true;
            }
            
            if (password.value === confirmPassword.value) {
                passwordMatchHint.textContent = '✓ Пароли совпадают';
                passwordMatchHint.style.color = '#27ae60';
                return true;
            } else {
                passwordMatchHint.textContent = '✗ Пароли не совпадают';
                passwordMatchHint.style.color = '#e74c3c';
                return false;
            }
        }
        
        // Валидация имени пользователя в реальном времени
        function validateUsername() {
            const username = document.getElementById('username');
            const pattern = /^[a-zA-Z0-9]+$/;
            
            if (username.value.length > 0 && !pattern.test(username.value)) {
                username.setCustomValidity('Только английские буквы и цифры');
                username.classList.add('error');
            } else {
                username.setCustomValidity('');
                username.classList.remove('error');
            }
        }
        
        // Добавляем обработчики событий
        password.addEventListener('input', function() {
            updatePasswordStrength();
            checkPasswordMatch();
        });
        
        confirmPassword.addEventListener('input', checkPasswordMatch);
        
        document.getElementById('username').addEventListener('input', validateUsername);
        
        // Блокировка кнопки отправки при несовпадении паролей
        form.addEventListener('submit', function(e) {
            if (!checkPasswordMatch()) {
                e.preventDefault();
                alert('Пароли не совпадают');
            }
            
            const username = document.getElementById('username');
            const pattern = /^[a-zA-Z0-9]+$/;
            
            if (username.value.length > 0 && !pattern.test(username.value)) {
                e.preventDefault();
                alert('Имя пользователя может содержать только английские буквы и цифры');
            }
        });
        
        // Защита от вставки скриптов
        const inputs = document.querySelectorAll('input[type="text"], input[type="email"]');
        inputs.forEach(input => {
            input.addEventListener('input', function() {
                // Простая проверка на наличие потенциально опасных символов
                if (this.value.includes('<') || this.value.includes('>') || this.value.includes('script')) {
                    this.value = this.value.replace(/[<>]/g, '');
                }
            });
        });
    });
    </script>
</body>
</html>