<?php
require_once '../db.php';

// Проверка прав администратора
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$currentPage = 'stores';
$message = '';
$error = '';

// Получаем данные для фильтров
$regions = mysqli_query($conn, "SELECT * FROM regions ORDER BY region_name");
$tariff_groups = mysqli_query($conn, "SELECT * FROM tariff_groups ORDER BY group_name");

// Обработка добавления нового магазина
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add') {
        $store_number = trim($_POST['store_number']);
        $region_id = intval($_POST['region_id']);
        $tariff_group_id = intval($_POST['tariff_group_id']);
        $is_high_turnover = isset($_POST['is_high_turnover']) ? 1 : 0;
        $has_resort_rate = isset($_POST['has_resort_rate']) ? 1 : 0;
        $resort_multiplier = floatval($_POST['resort_multiplier'] ?? 1.00);
        
        if (!empty($store_number) && $region_id > 0 && $tariff_group_id > 0) {
            // Проверяем уникальность номера магазина
            $checkStmt = mysqli_prepare($conn, "SELECT store_id FROM stores WHERE store_number = ?");
            mysqli_stmt_bind_param($checkStmt, "s", $store_number);
            mysqli_stmt_execute($checkStmt);
            mysqli_stmt_store_result($checkStmt);
            
            if (mysqli_stmt_num_rows($checkStmt) == 0) {
                $stmt = mysqli_prepare($conn, "INSERT INTO stores (store_number, region_id, tariff_group_id, is_high_turnover, has_resort_rate, resort_multiplier) VALUES (?, ?, ?, ?, ?, ?)");
                mysqli_stmt_bind_param($stmt, "siiiid", $store_number, $region_id, $tariff_group_id, $is_high_turnover, $has_resort_rate, $resort_multiplier);
                
                if (mysqli_stmt_execute($stmt)) {
                    $message = "Магазин успешно добавлен";
                    
                    // Логирование
                    $logStmt = mysqli_prepare($conn, "INSERT INTO user_logs (user_id, action, ip_address) VALUES (?, ?, ?)");
                    $action = "Добавлен магазин: $store_number";
                    $ip = $_SERVER['REMOTE_ADDR'];
                    mysqli_stmt_bind_param($logStmt, "iss", $_SESSION['user_id'], $action, $ip);
                    mysqli_stmt_execute($logStmt);
                } else {
                    $error = "Ошибка при добавлении магазина";
                }
            } else {
                $error = "Магазин с таким номером уже существует";
            }
        } else {
            $error = "Заполните все обязательные поля";
        }
    }
    
    // Обработка редактирования
    if ($_POST['action'] === 'edit' && isset($_POST['store_id'])) {
        $store_id = intval($_POST['store_id']);
        $store_number = trim($_POST['store_number']);
        $region_id = intval($_POST['region_id']);
        $tariff_group_id = intval($_POST['tariff_group_id']);
        $is_high_turnover = isset($_POST['is_high_turnover']) ? 1 : 0;
        $has_resort_rate = isset($_POST['has_resort_rate']) ? 1 : 0;
        $resort_multiplier = floatval($_POST['resort_multiplier'] ?? 1.00);
        
        if (!empty($store_number) && $region_id > 0 && $tariff_group_id > 0) {
            // Проверяем уникальность номера магазина (исключая текущий)
            $checkStmt = mysqli_prepare($conn, "SELECT store_id FROM stores WHERE store_number = ? AND store_id != ?");
            mysqli_stmt_bind_param($checkStmt, "si", $store_number, $store_id);
            mysqli_stmt_execute($checkStmt);
            mysqli_stmt_store_result($checkStmt);
            
            if (mysqli_stmt_num_rows($checkStmt) == 0) {
                $stmt = mysqli_prepare($conn, "UPDATE stores SET store_number = ?, region_id = ?, tariff_group_id = ?, is_high_turnover = ?, has_resort_rate = ?, resort_multiplier = ? WHERE store_id = ?");
                mysqli_stmt_bind_param($stmt, "siiiddi", $store_number, $region_id, $tariff_group_id, $is_high_turnover, $has_resort_rate, $resort_multiplier, $store_id);
                
                if (mysqli_stmt_execute($stmt)) {
                    $message = "Магазин успешно обновлен";
                    
                    // Логирование
                    $logStmt = mysqli_prepare($conn, "INSERT INTO user_logs (user_id, action, ip_address) VALUES (?, ?, ?)");
                    $action = "Обновлен магазин ID $store_id: $store_number";
                    $ip = $_SERVER['REMOTE_ADDR'];
                    mysqli_stmt_bind_param($logStmt, "iss", $_SESSION['user_id'], $action, $ip);
                    mysqli_stmt_execute($logStmt);
                } else {
                    $error = "Ошибка при обновлении магазина";
                }
            } else {
                $error = "Магазин с таким номером уже существует";
            }
        } else {
            $error = "Заполните все обязательные поля";
        }
    }
}

// Обработка удаления
if (isset($_GET['delete'])) {
    $store_id = intval($_GET['delete']);
    
    // Проверяем, используется ли магазин (например, в расчетах)
    // Можно добавить проверки при необходимости
    
    $deleteStmt = mysqli_prepare($conn, "DELETE FROM stores WHERE store_id = ?");
    mysqli_stmt_bind_param($deleteStmt, "i", $store_id);
    
    if (mysqli_stmt_execute($deleteStmt)) {
        $message = "Магазин удален";
        
        // Логирование
        $logStmt = mysqli_prepare($conn, "INSERT INTO user_logs (user_id, action, ip_address) VALUES (?, ?, ?)");
        $action = "Удален магазин ID $store_id";
        $ip = $_SERVER['REMOTE_ADDR'];
        mysqli_stmt_bind_param($logStmt, "iss", $_SESSION['user_id'], $action, $ip);
        mysqli_stmt_execute($logStmt);
    } else {
        $error = "Ошибка при удалении магазина";
    }
}

// ПОСТРОЕНИЕ ЗАПРОСА С ФИЛЬТРАЦИЕЙ
$where_conditions = ["1=1"];

if (!empty($_GET['region_id'])) {
    $region_id = intval($_GET['region_id']);
    $where_conditions[] = "s.region_id = $region_id";
}

if (!empty($_GET['tariff_group_id'])) {
    $group_id = intval($_GET['tariff_group_id']);
    $where_conditions[] = "s.tariff_group_id = $group_id";
}

if (isset($_GET['is_high_turnover']) && $_GET['is_high_turnover'] !== '') {
    $is_high = intval($_GET['is_high_turnover']);
    $where_conditions[] = "s.is_high_turnover = $is_high";
}

if (isset($_GET['has_resort_rate']) && $_GET['has_resort_rate'] !== '') {
    $has_resort = intval($_GET['has_resort_rate']);
    $where_conditions[] = "s.has_resort_rate = $has_resort";
}

if (!empty($_GET['search'])) {
    $search = mysqli_real_escape_string($conn, $_GET['search']);
    $where_conditions[] = "(s.store_number LIKE '%$search%')";
}

$where_clause = implode(" AND ", $where_conditions);

// ПОЛУЧАЕМ ДАННЫЕ
$stores = mysqli_query($conn, "
    SELECT 
        s.*,
        r.region_name,
        tg.group_name as tariff_group_name
    FROM stores s
    LEFT JOIN regions r ON s.region_id = r.region_id
    LEFT JOIN tariff_groups tg ON s.tariff_group_id = tg.tariff_group_id
    WHERE $where_clause
    ORDER BY s.store_number
");

// СТАТИСТИКА
$stats_query = mysqli_query($conn, "
    SELECT 
        COUNT(*) as total,
        SUM(is_high_turnover) as high_turnover_count,
        SUM(has_resort_rate) as resort_count
    FROM stores
");
$stats = mysqli_fetch_assoc($stats_query);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Управление магазинами - Админ-панель</title>
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
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
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
        }
        
        td {
            padding: 15px;
            border-bottom: 1px solid #e9ecef;
        }
        
        tr:hover {
            background: #f8f9fa;
        }
        
        .store-number {
            font-weight: 600;
            color: #667eea;
            font-size: 16px;
        }
        
        .badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
            background: #e9ecef;
            color: #495057;
            display: inline-block;
        }
        
        .badge-success {
            background: #28a745;
            color: white;
        }
        
        .badge-warning {
            background: #ffc107;
            color: #212529;
        }
        
        .badge-info {
            background: #17a2b8;
            color: white;
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
        <a href="users.php">👥 Пользователи</a>
        <a href="positions.php">📋 Должности</a>
        <a href="stores.php" class="active">🏪 Магазины</a>
        <a href="rates.php">💰 Часовые ставки</a>
        <a href="fixed_salaries.php">💵 Фиксированные оклады</a>
        <a href="../index.php" class="back-link">← На главную</a>
    </div>
    
    <div class="admin-wrapper">
        <!-- Заголовок -->
        <div class="page-header">
            <h1>🏪 Управление магазинами</h1>
            <button class="btn btn-success" onclick="openAddModal()">➕ Добавить магазин</button>
        </div>
        
        <!-- Сообщения -->
        <?php if ($message): ?>
            <div class="message message-success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="message message-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <!-- Статистика -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-label">Всего магазинов</div>
                <div class="stat-value"><?= number_format($stats['total'], 0, '.', ' ') ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">С высоким ТО</div>
                <div class="stat-value"><?= number_format($stats['high_turnover_count'], 0, '.', ' ') ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">С курортным коэф.</div>
                <div class="stat-value"><?= number_format($stats['resort_count'], 0, '.', ' ') ?></div>
            </div>
        </div>
        
        <!-- Панель фильтров -->
        <div class="filters-panel">
            <form method="GET">
                <div class="filters-grid">
                    <div class="filter-group">
                        <label>Регион</label>
                        <select name="region_id" class="filter-select">
                            <option value="">Все регионы</option>
                            <?php mysqli_data_seek($regions, 0); while ($region = mysqli_fetch_assoc($regions)): ?>
                            <option value="<?= $region['region_id'] ?>" <?= ($_GET['region_id'] ?? '') == $region['region_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($region['region_name']) ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label>Тарифная группа</label>
                        <select name="tariff_group_id" class="filter-select">
                            <option value="">Все группы</option>
                            <?php mysqli_data_seek($tariff_groups, 0); while ($group = mysqli_fetch_assoc($tariff_groups)): ?>
                            <option value="<?= $group['tariff_group_id'] ?>" <?= ($_GET['tariff_group_id'] ?? '') == $group['tariff_group_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($group['group_name']) ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label>Товарооборот</label>
                        <select name="is_high_turnover" class="filter-select">
                            <option value="">Все</option>
                            <option value="1" <?= ($_GET['is_high_turnover'] ?? '') === '1' ? 'selected' : '' ?>>Высокий</option>
                            <option value="0" <?= ($_GET['is_high_turnover'] ?? '') === '0' ? 'selected' : '' ?>>Обычный</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label>Курортный период</label>
                        <select name="has_resort_rate" class="filter-select">
                            <option value="">Все</option>
                            <option value="1" <?= ($_GET['has_resort_rate'] ?? '') === '1' ? 'selected' : '' ?>>Есть</option>
                            <option value="0" <?= ($_GET['has_resort_rate'] ?? '') === '0' ? 'selected' : '' ?>>Нет</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label>Поиск по номеру</label>
                        <input type="text" name="search" class="filter-input" value="<?= htmlspecialchars($_GET['search'] ?? '') ?>" placeholder="Номер магазина">
                    </div>
                </div>
                
                <div class="filter-actions">
                    <button type="submit" class="btn btn-primary">🔍 Применить фильтры</button>
                    <a href="?" class="btn btn-secondary">🔄 Сбросить</a>
                </div>
            </form>
        </div>
        
        <!-- Таблица магазинов -->
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Номер магазина</th>
                        <th>Регион</th>
                        <th>Тарифная группа</th>
                        <th>Товарооборот</th>
                        <th>Курортный период</th>
                        <th>Коэф.</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($stores) > 0): ?>
                        <?php while ($store = mysqli_fetch_assoc($stores)): ?>
                        <tr>
                            <td>#<?= $store['store_id'] ?></td>
                            <td class="store-number"><?= htmlspecialchars($store['store_number']) ?></td>
                            <td><?= htmlspecialchars($store['region_name'] ?? 'Не указан') ?></td>
                            <td><?= htmlspecialchars($store['tariff_group_name'] ?? 'Не указана') ?></td>
                            <td>
                                <?php if ($store['is_high_turnover']): ?>
                                    <span class="badge badge-success">Высокий</span>
                                <?php else: ?>
                                    <span class="badge">Обычный</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($store['has_resort_rate']): ?>
                                    <span class="badge badge-warning">Есть</span>
                                <?php else: ?>
                                    <span class="badge">Нет</span>
                                <?php endif; ?>
                            </td>
                            <td><?= $store['resort_multiplier'] ?>x</td>
                            <td class="actions">
                                <button class="btn btn-primary btn-sm" onclick="openEditModal(<?= $store['store_id'] ?>, '<?= htmlspecialchars(addslashes($store['store_number'])) ?>', <?= $store['region_id'] ?>, <?= $store['tariff_group_id'] ?>, <?= $store['is_high_turnover'] ?>, <?= $store['has_resort_rate'] ?>, <?= $store['resort_multiplier'] ?>)">✏️ Ред.</button>
                                <a href="?delete=<?= $store['store_id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Вы уверены?')">🗑️ Удалить</a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 50px;">
                                <h3 style="color: #95a5a6;">😕 Нет магазинов</h3>
                                <p>Добавьте первый магазин</p>
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
                <h3>➕ Добавить магазин</h3>
                <span class="modal-close" onclick="closeAddModal()">&times;</span>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Номер магазина *</label>
                        <input type="text" name="store_number" class="form-control" required placeholder="Например: 001">
                    </div>
                    
                    <div class="form-group">
                        <label>Регион *</label>
                        <select name="region_id" class="form-control" required>
                            <option value="">Выберите регион</option>
                            <?php mysqli_data_seek($regions, 0); while ($region = mysqli_fetch_assoc($regions)): ?>
                            <option value="<?= $region['region_id'] ?>"><?= htmlspecialchars($region['region_name']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Тарифная группа *</label>
                        <select name="tariff_group_id" class="form-control" required>
                            <option value="">Выберите группу</option>
                            <?php mysqli_data_seek($tariff_groups, 0); while ($group = mysqli_fetch_assoc($tariff_groups)): ?>
                            <option value="<?= $group['tariff_group_id'] ?>"><?= htmlspecialchars($group['group_name']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="checkbox-group">
                        <input type="checkbox" name="is_high_turnover" id="add_is_high_turnover">
                        <label for="add_is_high_turnover">Высокий товарооборот (&gt;300 тыс.)</label>
                    </div>
                    
                    <div class="checkbox-group">
                        <input type="checkbox" name="has_resort_rate" id="add_has_resort_rate" onchange="toggleResortMultiplier(this, 'add_resort_multiplier')">
                        <label for="add_has_resort_rate">Курортный период</label>
                    </div>
                    
                    <div class="form-group" id="add_resort_multiplier" style="display: none;">
                        <label>Курортный коэффициент</label>
                        <input type="number" name="resort_multiplier" class="form-control" step="0.01" min="1" max="3" value="1.00">
                        <div class="help-text">Коэффициент для расчета в курортный период (по умолчанию 1.00)</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeAddModal()">Отмена</button>
                    <button type="submit" class="btn btn-success">Сохранить</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Модальное окно редактирования -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>✏️ Редактировать магазин</h3>
                <span class="modal-close" onclick="closeEditModal()">&times;</span>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="store_id" id="edit_store_id">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Номер магазина *</label>
                        <input type="text" name="store_number" id="edit_store_number" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Регион *</label>
                        <select name="region_id" id="edit_region_id" class="form-control" required>
                            <option value="">Выберите регион</option>
                            <?php mysqli_data_seek($regions, 0); while ($region = mysqli_fetch_assoc($regions)): ?>
                            <option value="<?= $region['region_id'] ?>"><?= htmlspecialchars($region['region_name']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Тарифная группа *</label>
                        <select name="tariff_group_id" id="edit_tariff_group_id" class="form-control" required>
                            <option value="">Выберите группу</option>
                            <?php mysqli_data_seek($tariff_groups, 0); while ($group = mysqli_fetch_assoc($tariff_groups)): ?>
                            <option value="<?= $group['tariff_group_id'] ?>"><?= htmlspecialchars($group['group_name']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="checkbox-group">
                        <input type="checkbox" name="is_high_turnover" id="edit_is_high_turnover">
                        <label for="edit_is_high_turnover">Высокий товарооборот (&gt;300 тыс.)</label>
                    </div>
                    
                    <div class="checkbox-group">
                        <input type="checkbox" name="has_resort_rate" id="edit_has_resort_rate" onchange="toggleResortMultiplier(this, 'edit_resort_multiplier')">
                        <label for="edit_has_resort_rate">Курортный период</label>
                    </div>
                    
                    <div class="form-group" id="edit_resort_multiplier" style="display: none;">
                        <label>Курортный коэффициент</label>
                        <input type="number" name="resort_multiplier" id="edit_resort_multiplier_value" class="form-control" step="0.01" min="1" max="3">
                        <div class="help-text">Коэффициент для расчета в курортный период (по умолчанию 1.00)</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Отмена</button>
                    <button type="submit" class="btn btn-primary">Обновить</button>
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
        
        function openEditModal(id, number, regionId, groupId, isHigh, hasResort, multiplier) {
            document.getElementById('edit_store_id').value = id;
            document.getElementById('edit_store_number').value = number;
            document.getElementById('edit_region_id').value = regionId;
            document.getElementById('edit_tariff_group_id').value = groupId;
            document.getElementById('edit_is_high_turnover').checked = isHigh == 1;
            document.getElementById('edit_has_resort_rate').checked = hasResort == 1;
            
            const resortDiv = document.getElementById('edit_resort_multiplier');
            if (hasResort == 1) {
                resortDiv.style.display = 'block';
                document.getElementById('edit_resort_multiplier_value').value = multiplier;
            } else {
                resortDiv.style.display = 'none';
            }
            
            document.getElementById('editModal').classList.add('show');
        }
        
        function closeEditModal() {
            document.getElementById('editModal').classList.remove('show');
        }
        
        function toggleResortMultiplier(checkbox, divId) {
            const resortDiv = document.getElementById(divId);
            if (checkbox.checked) {
                resortDiv.style.display = 'block';
            } else {
                resortDiv.style.display = 'none';
            }
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