<?php
require_once '../db.php';

// Проверка прав администратора
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$currentPage = 'users';
$message = '';
$error = '';

// Обработка добавления нового пользователя
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add') {
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        $full_name = trim($_POST['full_name'] ?? '');
        $role = $_POST['role'] ?? 'user';
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        // Валидация
        $errors = [];
        
        if (empty($username)) {
            $errors[] = "Имя пользователя обязательно";
        } elseif (strlen($username) < 3) {
            $errors[] = "Имя пользователя должно быть не менее 3 символов";
        } elseif (!preg_match('/^[a-zA-Z0-9]+$/', $username)) {
            $errors[] = "Имя пользователя может содержать только английские буквы и цифры";
        }
        
        if (empty($email)) {
            $errors[] = "Email обязателен";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Некорректный email";
        }
        
        if (empty($password)) {
            $errors[] = "Пароль обязателен";
        } elseif (strlen($password) < 6) {
            $errors[] = "Пароль должен быть не менее 6 символов";
        }
        
        // Проверка уникальности
        if (empty($errors)) {
            $checkStmt = mysqli_prepare($conn, "SELECT user_id FROM users WHERE username = ? OR email = ?");
            mysqli_stmt_bind_param($checkStmt, "ss", $username, $email);
            mysqli_stmt_execute($checkStmt);
            mysqli_stmt_store_result($checkStmt);
            
            if (mysqli_stmt_num_rows($checkStmt) > 0) {
                $errors[] = "Пользователь с таким именем или email уже существует";
            }
            mysqli_stmt_close($checkStmt);
        }
        
        if (empty($errors)) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            $stmt = mysqli_prepare($conn, "INSERT INTO users (username, email, password, full_name, role, is_active) VALUES (?, ?, ?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt, "sssssi", $username, $email, $hashed_password, $full_name, $role, $is_active);
            
            if (mysqli_stmt_execute($stmt)) {
                $message = "Пользователь успешно добавлен";
                
                // Логирование
                $logStmt = mysqli_prepare($conn, "INSERT INTO user_logs (user_id, action, ip_address) VALUES (?, ?, ?)");
                $action = "Добавлен пользователь: $username";
                $ip = $_SERVER['REMOTE_ADDR'];
                mysqli_stmt_bind_param($logStmt, "iss", $_SESSION['user_id'], $action, $ip);
                mysqli_stmt_execute($logStmt);
            } else {
                $error = "Ошибка при добавлении пользователя";
            }
        } else {
            $error = implode("<br>", $errors);
        }
    }
    
    // Обработка редактирования
    if ($_POST['action'] === 'edit' && isset($_POST['user_id'])) {
        $user_id = intval($_POST['user_id']);
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $full_name = trim($_POST['full_name'] ?? '');
        $role = $_POST['role'] ?? 'user';
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        // Валидация
        $errors = [];
        
        if (empty($username)) {
            $errors[] = "Имя пользователя обязательно";
        } elseif (strlen($username) < 3) {
            $errors[] = "Имя пользователя должно быть не менее 3 символов";
        } elseif (!preg_match('/^[a-zA-Z0-9]+$/', $username)) {
            $errors[] = "Имя пользователя может содержать только английские буквы и цифры";
        }
        
        if (empty($email)) {
            $errors[] = "Email обязателен";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Некорректный email";
        }
        
        // Проверка уникальности (исключая текущего пользователя)
        if (empty($errors)) {
            $checkStmt = mysqli_prepare($conn, "SELECT user_id FROM users WHERE (username = ? OR email = ?) AND user_id != ?");
            mysqli_stmt_bind_param($checkStmt, "ssi", $username, $email, $user_id);
            mysqli_stmt_execute($checkStmt);
            mysqli_stmt_store_result($checkStmt);
            
            if (mysqli_stmt_num_rows($checkStmt) > 0) {
                $errors[] = "Пользователь с таким именем или email уже существует";
            }
            mysqli_stmt_close($checkStmt);
        }
        
        // Если указан новый пароль
        if (!empty($_POST['new_password'])) {
            if (strlen($_POST['new_password']) < 6) {
                $errors[] = "Новый пароль должен быть не менее 6 символов";
            } else {
                $hashed_password = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
                $updatePass = true;
            }
        }
        
        if (empty($errors)) {
            if (isset($updatePass)) {
                $stmt = mysqli_prepare($conn, "UPDATE users SET username = ?, email = ?, full_name = ?, role = ?, is_active = ?, password = ? WHERE user_id = ?");
                mysqli_stmt_bind_param($stmt, "ssssisi", $username, $email, $full_name, $role, $is_active, $hashed_password, $user_id);
            } else {
                $stmt = mysqli_prepare($conn, "UPDATE users SET username = ?, email = ?, full_name = ?, role = ?, is_active = ? WHERE user_id = ?");
                mysqli_stmt_bind_param($stmt, "ssssii", $username, $email, $full_name, $role, $is_active, $user_id);
            }
            
            if (mysqli_stmt_execute($stmt)) {
                $message = "Пользователь успешно обновлен";
                
                // Логирование
                $logStmt = mysqli_prepare($conn, "INSERT INTO user_logs (user_id, action, ip_address) VALUES (?, ?, ?)");
                $action = "Обновлен пользователь ID $user_id: $username";
                $ip = $_SERVER['REMOTE_ADDR'];
                mysqli_stmt_bind_param($logStmt, "iss", $_SESSION['user_id'], $action, $ip);
                mysqli_stmt_execute($logStmt);
            } else {
                $error = "Ошибка при обновлении пользователя";
            }
        } else {
            $error = implode("<br>", $errors);
        }
    }
}

// Обработка удаления
if (isset($_GET['delete'])) {
    $user_id = intval($_GET['delete']);
    
    // Не даем удалить самого себя
    if ($user_id == $_SESSION['user_id']) {
        $error = "Вы не можете удалить自己的 аккаунт";
    } else {
        $deleteStmt = mysqli_prepare($conn, "DELETE FROM users WHERE user_id = ?");
        mysqli_stmt_bind_param($deleteStmt, "i", $user_id);
        
        if (mysqli_stmt_execute($deleteStmt)) {
            $message = "Пользователь удален";
            
            // Логирование
            $logStmt = mysqli_prepare($conn, "INSERT INTO user_logs (user_id, action, ip_address) VALUES (?, ?, ?)");
            $action = "Удален пользователь ID $user_id";
            $ip = $_SERVER['REMOTE_ADDR'];
            mysqli_stmt_bind_param($logStmt, "iss", $_SESSION['user_id'], $action, $ip);
            mysqli_stmt_execute($logStmt);
        } else {
            $error = "Ошибка при удалении пользователя";
        }
    }
}

// Обработка блокировки/разблокировки
if (isset($_GET['toggle_active'])) {
    $user_id = intval($_GET['toggle_active']);
    
    // Получаем текущий статус
    $statusStmt = mysqli_prepare($conn, "SELECT is_active FROM users WHERE user_id = ?");
    mysqli_stmt_bind_param($statusStmt, "i", $user_id);
    mysqli_stmt_execute($statusStmt);
    $statusResult = mysqli_stmt_get_result($statusStmt);
    $user = mysqli_fetch_assoc($statusResult);
    
    if ($user) {
        $new_status = $user['is_active'] ? 0 : 1;
        
        $updateStmt = mysqli_prepare($conn, "UPDATE users SET is_active = ? WHERE user_id = ?");
        mysqli_stmt_bind_param($updateStmt, "ii", $new_status, $user_id);
        
        if (mysqli_stmt_execute($updateStmt)) {
            $message = "Статус пользователя изменен";
            
            // Логирование
            $logStmt = mysqli_prepare($conn, "INSERT INTO user_logs (user_id, action, ip_address) VALUES (?, ?, ?)");
            $action = "Изменен статус пользователя ID $user_id на " . ($new_status ? 'активен' : 'заблокирован');
            $ip = $_SERVER['REMOTE_ADDR'];
            mysqli_stmt_bind_param($logStmt, "iss", $_SESSION['user_id'], $action, $ip);
            mysqli_stmt_execute($logStmt);
        } else {
            $error = "Ошибка при изменении статуса";
        }
    }
}

// ПОСТРОЕНИЕ ЗАПРОСА С ФИЛЬТРАЦИЕЙ
$where_conditions = ["1=1"];

if (!empty($_GET['role'])) {
    $role = mysqli_real_escape_string($conn, $_GET['role']);
    $where_conditions[] = "role = '$role'";
}

if (isset($_GET['is_active']) && $_GET['is_active'] !== '') {
    $is_active = intval($_GET['is_active']);
    $where_conditions[] = "is_active = $is_active";
}

if (!empty($_GET['search'])) {
    $search = mysqli_real_escape_string($conn, $_GET['search']);
    $where_conditions[] = "(username LIKE '%$search%' OR email LIKE '%$search%' OR full_name LIKE '%$search%')";
}

$where_clause = implode(" AND ", $where_conditions);

// ПОЛУЧАЕМ ДАННЫЕ
$users = mysqli_query($conn, "
    SELECT 
        user_id,
        username,
        email,
        full_name,
        role,
        is_active,
        created_at,
        last_login
    FROM users
    WHERE $where_clause
    ORDER BY user_id DESC
");

// СТАТИСТИКА
$stats_query = mysqli_query($conn, "
    SELECT 
        COUNT(*) as total,
        SUM(role = 'admin') as admins,
        SUM(role = 'user') as users,
        SUM(is_active = 1) as active,
        SUM(is_active = 0) as inactive,
        SUM(last_login >= DATE_SUB(NOW(), INTERVAL 24 HOUR)) as online_today
    FROM users
");
$stats = mysqli_fetch_assoc($stats_query);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes">
    <title>Управление пользователями - Админ-панель</title>
    <style>
        /* Сброс стилей */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
            background: #f0f2f5;
            min-height: 100vh;
        }
        /* В админских файлах (users.php, stores.php и т.д.) добавить: */

        /* Адаптивные таблицы */
        .table-container {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            margin: 0 -1px;
            border-radius: 12px;
        }

        table {
            min-width: 800px;
        }

        /* Адаптация для планшетов */
        @media (max-width: 900px) {
            .table-container {
                border: 1px solid #e9ecef;
            }
            
            table {
                min-width: 700px;
            }
            
            th, td {
                padding: 12px 10px;
                font-size: 13px;
                white-space: nowrap;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .admin-nav {
                flex-direction: column;
                align-items: stretch;
            }
            
            .admin-nav a {
                text-align: center;
                padding: 12px;
            }
        }

        /* Адаптация для мобильных */
        @media (max-width: 768px) {
            .admin-wrapper {
                padding: 10px;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
                gap: 10px;
            }
            
            .stat-card {
                padding: 15px;
            }
            
            .stat-value {
                font-size: 24px;
            }
            
            .filter-actions {
                flex-direction: column;
            }
            
            .filter-actions .btn {
                width: 100%;
            }
            
            .modal-content {
                width: 95%;
                margin: 10px;
            }
            
            .actions {
                flex-direction: column;
                gap: 5px;
            }
            
            .actions .btn {
                width: 100%;
            }
        }

        @media (max-width: 480px) {
            table {
                min-width: 600px;
            }
            
            th, td {
                padding: 10px 8px;
                font-size: 12px;
            }
            
            .page-header {
                flex-direction: column;
                align-items: stretch;
            }
            
            .page-header .btn {
                width: 100%;
            }
        }
        /* Контейнер */
        .admin-wrapper {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
            width: 100%;
        }
        
        /* Навигация */
        .admin-nav {
            background: white;
            border-radius: 12px;
            padding: 15px 25px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            display: flex;
            gap: 20px;
            align-items: center;
            flex-wrap: wrap;
            width: 100%;
        }
        
        .admin-nav a {
            color: #4a2e5e;
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 8px;
            transition: all 0.3s;
            font-weight: 500;
        }
        
        .admin-nav a:hover {
            background: #f0e6ff;
        }
        
        .admin-nav a.active {
            background: #6b4e8e;
            color: white;
        }
        
        .back-link {
            margin-left: auto;
            color: #667eea !important;
        }
        
        /* Заголовок */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .page-header h1 {
            font-size: 28px;
            color: #2c3e50;
        }
        
        /* Кнопки */
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-primary {
            background: #667eea;
            color: white;
        }
        
        .btn-primary:hover {
            background: #5a67d8;
        }
        
        .btn-success {
            background: #28a745;
            color: white;
        }
        
        .btn-success:hover {
            background: #218838;
        }
        
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        
        .btn-danger:hover {
            background: #c82333;
        }
        
        .btn-warning {
            background: #ffc107;
            color: #212529;
        }
        
        .btn-sm {
            padding: 5px 10px;
            font-size: 12px;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
        }
        
        /* Сообщения */
        .message {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .message-success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }
        
        .message-error {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }
        
        /* Статистика */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .stat-label {
            color: #666;
            font-size: 14px;
            margin-bottom: 10px;
        }
        
        .stat-value {
            font-size: 28px;
            font-weight: bold;
            color: #2c3e50;
        }
        
        .stat-value small {
            font-size: 14px;
            color: #95a5a6;
            font-weight: normal;
        }
        
        /* Панель фильтров */
        .filters-panel {
            background: white;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .filters-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        
        .filter-group label {
            font-size: 13px;
            font-weight: 600;
            color: #555;
        }
        
        .filter-input, .filter-select {
            padding: 8px 12px;
            border: 2px solid #e1e8ed;
            border-radius: 8px;
            font-size: 14px;
            width: 100%;
        }
        
        .filter-input:focus, .filter-select:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .filter-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        /* Таблица */
        .table-container {
            background: white;
            border-radius: 12px;
            overflow: auto;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 1000px;
        }
        
        th {
            background: #2c3e50;
            color: white;
            padding: 15px;
            text-align: left;
            font-weight: 500;
            white-space: nowrap;
        }
        
        td {
            padding: 15px;
            border-bottom: 1px solid #e9ecef;
        }
        
        tr:hover {
            background: #f8f9fa;
        }
        
        .username {
            font-weight: 600;
            color: #667eea;
        }
        
        .badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
            display: inline-block;
        }
        
        .badge-admin {
            background: #6b4e8e;
            color: white;
        }
        
        .badge-user {
            background: #17a2b8;
            color: white;
        }
        
        .badge-active {
            background: #28a745;
            color: white;
        }
        
        .badge-inactive {
            background: #dc3545;
            color: white;
        }
        
        .badge-online {
            background: #ffc107;
            color: #212529;
        }
        
        .actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        
        /* Модальное окно */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
        }
        
        .modal.show {
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .modal-content {
            background: white;
            border-radius: 12px;
            max-width: 500px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
        }
        
        .modal-header {
            padding: 20px;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .modal-header h3 {
            color: #2c3e50;
        }
        
        .modal-close {
            font-size: 24px;
            cursor: pointer;
            color: #999;
        }
        
        .modal-close:hover {
            color: #666;
        }
        
        .modal-body {
            padding: 20px;
        }
        
        .modal-footer {
            padding: 20px;
            border-top: 1px solid #e9ecef;
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #555;
            font-weight: 500;
        }
        
        .form-control {
            width: 100%;
            padding: 10px 12px;
            border: 2px solid #e1e8ed;
            border-radius: 8px;
            font-size: 14px;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
        }
        
        .checkbox-group input[type="checkbox"] {
            width: 18px;
            height: 18px;
        }
        
        .help-text {
            font-size: 12px;
            color: #6c757d;
            margin-top: 4px;
        }
        
        /* Адаптивность */
        @media (max-width: 768px) {
            .admin-wrapper {
                padding: 10px;
            }
            
            .admin-nav {
                flex-direction: column;
                align-items: stretch;
            }
            
            .admin-nav a {
                text-align: center;
            }
            
            .back-link {
                margin-left: 0;
            }
            
            .page-header {
                flex-direction: column;
                align-items: stretch;
            }
            
            .filters-grid {
                grid-template-columns: 1fr;
            }
            
            .stats-grid {
                grid-template-columns: 1fr 1fr;
            }
        }
        
        @media (max-width: 480px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Навигация -->
    <div class="admin-nav">
        <a href="index.php">📊 Дашборд</a>
        <a href="users.php" class="active">👥 Пользователи</a>
        <a href="positions.php">📋 Должности</a>
        <a href="stores.php">🏪 Магазины</a>
        <a href="rates.php">💰 Часовые ставки</a>
        <a href="fixed_salaries.php">💵 Фиксированные оклады</a>
        <a href="../index.php" class="back-link">← На главную</a>
    </div>
    
    <div class="admin-wrapper">
        <!-- Заголовок -->
        <div class="page-header">
            <h1>👥 Управление пользователями</h1>
            <button class="btn btn-success" onclick="openAddModal()">➕ Добавить пользователя</button>
        </div>
        
        <!-- Сообщения -->
        <?php if ($message): ?>
            <div class="message message-success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="message message-error"><?= $error ?></div>
        <?php endif; ?>
        
        <!-- Статистика -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-label">Всего пользователей</div>
                <div class="stat-value"><?= $stats['total'] ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Администраторы</div>
                <div class="stat-value"><?= $stats['admins'] ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Обычные пользователи</div>
                <div class="stat-value"><?= $stats['users'] ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Активные / Заблок.</div>
                <div class="stat-value"><?= $stats['active'] ?> / <?= $stats['inactive'] ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Онлайн сегодня</div>
                <div class="stat-value"><?= $stats['online_today'] ?> <small>чел.</small></div>
            </div>
        </div>
        
        <!-- Панель фильтров -->
        <div class="filters-panel">
            <form method="GET">
                <div class="filters-grid">
                    <div class="filter-group">
                        <label>Роль</label>
                        <select name="role" class="filter-select">
                            <option value="">Все роли</option>
                            <option value="admin" <?= ($_GET['role'] ?? '') == 'admin' ? 'selected' : '' ?>>Администраторы</option>
                            <option value="user" <?= ($_GET['role'] ?? '') == 'user' ? 'selected' : '' ?>>Пользователи</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label>Статус</label>
                        <select name="is_active" class="filter-select">
                            <option value="">Все</option>
                            <option value="1" <?= ($_GET['is_active'] ?? '') === '1' ? 'selected' : '' ?>>Активные</option>
                            <option value="0" <?= ($_GET['is_active'] ?? '') === '0' ? 'selected' : '' ?>>Заблокированные</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label>Поиск</label>
                        <input type="text" name="search" class="filter-input" value="<?= htmlspecialchars($_GET['search'] ?? '') ?>" placeholder="Имя, email...">
                    </div>
                </div>
                
                <div class="filter-actions">
                    <button type="submit" class="btn btn-primary">🔍 Применить фильтры</button>
                    <a href="?" class="btn btn-secondary">🔄 Сбросить</a>
                </div>
            </form>
        </div>
        
        <!-- Таблица пользователей -->
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Имя пользователя</th>
                        <th>Email</th>
                        <th>Полное имя</th>
                        <th>Роль</th>
                        <th>Статус</th>
                        <th>Дата регистрации</th>
                        <th>Последний вход</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($users) > 0): ?>
                        <?php while ($user = mysqli_fetch_assoc($users)): 
                            $isCurrentUser = ($user['user_id'] == $_SESSION['user_id']);
                        ?>
                        <tr>
                            <td>#<?= $user['user_id'] ?></td>
                            <td class="username">
                                <?= htmlspecialchars($user['username']) ?>
                                <?php if ($isCurrentUser): ?>
                                    <span class="badge badge-warning" style="margin-left: 5px;">Вы</span>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($user['email']) ?></td>
                            <td><?= htmlspecialchars($user['full_name'] ?: '-') ?></td>
                            <td>
                                <?php if ($user['role'] == 'admin'): ?>
                                    <span class="badge badge-admin">Админ</span>
                                <?php else: ?>
                                    <span class="badge badge-user">Пользователь</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($user['is_active']): ?>
                                    <span class="badge badge-active">Активен</span>
                                <?php else: ?>
                                    <span class="badge badge-inactive">Заблокирован</span>
                                <?php endif; ?>
                                
                                <?php if ($user['last_login'] && strtotime($user['last_login']) > strtotime('-24 hours')): ?>
                                    <span class="badge badge-online" style="margin-left: 5px;">Online</span>
                                <?php endif; ?>
                            </td>
                            <td><?= $user['created_at'] ? date('d.m.Y', strtotime($user['created_at'])) : '-' ?></td>
                            <td><?= $user['last_login'] ? date('d.m.Y H:i', strtotime($user['last_login'])) : 'Никогда' ?></td>
                            <td class="actions">
                                <button class="btn btn-primary btn-sm" onclick="openEditModal(<?= $user['user_id'] ?>, '<?= htmlspecialchars(addslashes($user['username'])) ?>', '<?= htmlspecialchars(addslashes($user['email'])) ?>', '<?= htmlspecialchars(addslashes($user['full_name'])) ?>', '<?= $user['role'] ?>', <?= $user['is_active'] ?>)">✏️ Ред.</button>
                                
                                <?php if (!$isCurrentUser): ?>
                                    <a href="?toggle_active=<?= $user['user_id'] ?>" class="btn btn-warning btn-sm" onclick="return confirm('Изменить статус пользователя?')">
                                        <?= $user['is_active'] ? '🔒 Заблокировать' : '🔓 Разблокировать' ?>
                                    </a>
                                    <a href="?delete=<?= $user['user_id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Вы уверены? Это действие нельзя отменить.')">🗑️ Удалить</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" style="text-align: center; padding: 50px;">
                                <h3 style="color: #95a5a6;">😕 Нет пользователей</h3>
                                <p>Попробуйте изменить параметры фильтрации</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Модальное окно добавления -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>➕ Добавить пользователя</h3>
                <span class="modal-close" onclick="closeAddModal()">&times;</span>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Имя пользователя *</label>
                        <input type="text" name="username" class="form-control" required pattern="[a-zA-Z0-9]+" title="Только английские буквы и цифры">
                        <div class="help-text">Только английские буквы и цифры, от 3 символов</div>
                    </div>
                    
                    <div class="form-group">
                        <label>Email *</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Полное имя</label>
                        <input type="text" name="full_name" class="form-control">
                    </div>
                    
                    <div class="form-group">
                        <label>Пароль *</label>
                        <input type="password" name="password" class="form-control" required minlength="6">
                        <div class="help-text">Минимум 6 символов</div>
                    </div>
                    
                    <div class="form-group">
                        <label>Роль</label>
                        <select name="role" class="form-control">
                            <option value="user">Пользователь</option>
                            <option value="admin">Администратор</option>
                        </select>
                    </div>
                    
                    <div class="checkbox-group">
                        <input type="checkbox" name="is_active" id="add_is_active" checked>
                        <label for="add_is_active">Активный аккаунт</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeAddModal()">Отмена</button>
                    <button type="submit" class="btn btn-success">Создать</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Модальное окно редактирования -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>✏️ Редактировать пользователя</h3>
                <span class="modal-close" onclick="closeEditModal()">&times;</span>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="user_id" id="edit_user_id">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Имя пользователя *</label>
                        <input type="text" name="username" id="edit_username" class="form-control" required pattern="[a-zA-Z0-9]+" title="Только английские буквы и цифры">
                    </div>
                    
                    <div class="form-group">
                        <label>Email *</label>
                        <input type="email" name="email" id="edit_email" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Полное имя</label>
                        <input type="text" name="full_name" id="edit_full_name" class="form-control">
                    </div>
                    
                    <div class="form-group">
                        <label>Новый пароль (оставьте пустым, если не хотите менять)</label>
                        <input type="password" name="new_password" class="form-control" minlength="6">
                        <div class="help-text">Минимум 6 символов</div>
                    </div>
                    
                    <div class="form-group">
                        <label>Роль</label>
                        <select name="role" id="edit_role" class="form-control">
                            <option value="user">Пользователь</option>
                            <option value="admin">Администратор</option>
                        </select>
                    </div>
                    
                    <div class="checkbox-group">
                        <input type="checkbox" name="is_active" id="edit_is_active">
                        <label for="edit_is_active">Активный аккаунт</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Отмена</button>
                    <button type="submit" class="btn btn-primary">Сохранить изменения</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function openAddModal() {
            document.getElementById('addModal').classList.add('show');
        }
        
        function closeAddModal() {
            document.getElementById('addModal').classList.remove('show');
        }
        
        function openEditModal(id, username, email, fullName, role, isActive) {
            document.getElementById('edit_user_id').value = id;
            document.getElementById('edit_username').value = username;
            document.getElementById('edit_email').value = email;
            document.getElementById('edit_full_name').value = fullName || '';
            document.getElementById('edit_role').value = role;
            document.getElementById('edit_is_active').checked = isActive == 1;
            
            document.getElementById('editModal').classList.add('show');
        }
        
        function closeEditModal() {
            document.getElementById('editModal').classList.remove('show');
        }
        
        // Закрытие по клику вне модального окна
        window.onclick = function(event) {
            const addModal = document.getElementById('addModal');
            const editModal = document.getElementById('editModal');
            
            if (event.target === addModal) {
                closeAddModal();
            }
            if (event.target === editModal) {
                closeEditModal();
            }
        }
    </script>
</body>
</html>