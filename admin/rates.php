<?php
require_once '../db.php';

// Проверка прав администратора
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

// Подключаем навигацию
$currentPage = 'rates';
$pageTitle = 'Управление часовыми ставками';

// Обработка сохранения изменений
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['updates'])) {
    $success_count = 0;
    $error_count = 0;
    $errors = [];
    $updated_ids = [];
    
    foreach ($_POST['updates'] as $rate_id => $new_rate) {
        if ($new_rate === '') continue;
        
        $rate_id = intval($rate_id);
        $new_rate = intval($new_rate);
        
        if ($new_rate > 0) {
            $stmt = mysqli_prepare($conn, "UPDATE hourly_rates SET hourly_rate = ? WHERE hourly_rate_id = ?");
            mysqli_stmt_bind_param($stmt, "ii", $new_rate, $rate_id);
            
            if (mysqli_stmt_execute($stmt)) {
                $success_count++;
                $updated_ids[] = $rate_id;
            } else {
                $error_count++;
                $errors[] = "Ошибка при обновлении ID $rate_id";
            }
            mysqli_stmt_close($stmt);
        }
    }
    
    // Логирование изменений
    if ($success_count > 0) {
        $action = "Обновлено часовых ставок: $success_count (ID: " . implode(', ', $updated_ids) . ")";
        $logStmt = mysqli_prepare($conn, "INSERT INTO user_logs (user_id, action, ip_address) VALUES (?, ?, ?)");
        $ip = $_SERVER['REMOTE_ADDR'];
        mysqli_stmt_bind_param($logStmt, "iss", $_SESSION['user_id'], $action, $ip);
        mysqli_stmt_execute($logStmt);
        mysqli_stmt_close($logStmt);
    }
    
    $message = "✅ Обновлено ставок: $success_count";
    if ($error_count > 0) {
        $message .= ", ошибок: $error_count";
    }
    
    // Создаем запись в таблице изменений
    $create_table = "CREATE TABLE IF NOT EXISTS `changes_log` (
        `log_id` int(11) NOT NULL AUTO_INCREMENT,
        `change_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `changes_count` int(11) NOT NULL,
        `user_ip` varchar(45) DEFAULT NULL,
        `user_id` int(11) DEFAULT NULL,
        PRIMARY KEY (`log_id`),
        KEY `user_id` (`user_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8";
    mysqli_query($conn, $create_table);
    
    if ($success_count > 0) {
        $log_query = "INSERT INTO changes_log (change_date, changes_count, user_ip, user_id) VALUES (NOW(), ?, ?, ?)";
        $logStmt = mysqli_prepare($conn, $log_query);
        mysqli_stmt_bind_param($logStmt, "isi", $success_count, $ip, $_SESSION['user_id']);
        mysqli_stmt_execute($logStmt);
        mysqli_stmt_close($logStmt);
    }
}

// ПОЛУЧАЕМ ВСЕ ДАННЫЕ ДЛЯ ФИЛЬТРОВ
$regions = mysqli_query($conn, "SELECT * FROM regions ORDER BY region_name");
$regions_array = [];
while ($region = mysqli_fetch_assoc($regions)) {
    $regions_array[] = $region;
}

$positions = mysqli_query($conn, "SELECT * FROM positions ORDER BY position_name");
$positions_array = [];
while ($position = mysqli_fetch_assoc($positions)) {
    $positions_array[] = $position;
}

$tariff_groups = mysqli_query($conn, "SELECT * FROM tariff_groups ORDER BY tariff_group_id");
$tariff_groups_array = [];
while ($group = mysqli_fetch_assoc($tariff_groups)) {
    $tariff_groups_array[] = $group;
}

// ПОСТРОЕНИЕ ЗАПРОСА С ФИЛЬТРАЦИЕЙ
$where_conditions = ["1=1"];

if (!empty($_GET['region_id'])) {
    $region_id = intval($_GET['region_id']);
    $where_conditions[] = "hr.region_id = $region_id";
}

if (!empty($_GET['position_id'])) {
    $position_id = intval($_GET['position_id']);
    $where_conditions[] = "hr.position_id = $position_id";
}

if (!empty($_GET['tariff_group_id'])) {
    $group_id = intval($_GET['tariff_group_id']);
    $where_conditions[] = "hr.tariff_group_id = $group_id";
}

if (!empty($_GET['search'])) {
    $search = mysqli_real_escape_string($conn, $_GET['search']);
    $where_conditions[] = "(r.region_name LIKE '%$search%' OR p.position_name LIKE '%$search%' OR tg.group_name LIKE '%$search%')";
}

if (isset($_GET['min_rate']) && $_GET['min_rate'] !== '') {
    $min_rate = intval($_GET['min_rate']);
    $where_conditions[] = "hr.hourly_rate >= $min_rate";
}

if (isset($_GET['max_rate']) && $_GET['max_rate'] !== '') {
    $max_rate = intval($_GET['max_rate']);
    $where_conditions[] = "hr.hourly_rate <= $max_rate";
}

// СОРТИРОВКА
$order_by = "r.region_name, p.position_name, tg.group_name";
if (!empty($_GET['sort'])) {
    switch ($_GET['sort']) {
        case 'region_asc':
            $order_by = "r.region_name ASC";
            break;
        case 'region_desc':
            $order_by = "r.region_name DESC";
            break;
        case 'position_asc':
            $order_by = "p.position_name ASC";
            break;
        case 'position_desc':
            $order_by = "p.position_name DESC";
            break;
        case 'rate_asc':
            $order_by = "hr.hourly_rate ASC";
            break;
        case 'rate_desc':
            $order_by = "hr.hourly_rate DESC";
            break;
    }
}

$where_clause = implode(" AND ", $where_conditions);

// ПОЛУЧАЕМ ДАННЫЕ
$rates = mysqli_query($conn, "
    SELECT 
        hr.*,
        r.region_name,
        p.position_name,
        tg.group_name as tariff_group_name
    FROM hourly_rates hr
    LEFT JOIN regions r ON hr.region_id = r.region_id
    LEFT JOIN positions p ON hr.position_id = p.position_id
    LEFT JOIN tariff_groups tg ON hr.tariff_group_id = tg.tariff_group_id
    WHERE $where_clause
    ORDER BY $order_by
");

// СТАТИСТИКА
$stats_query = mysqli_query($conn, "
    SELECT 
        COUNT(*) as total,
        AVG(hourly_rate) as avg_rate,
        MIN(hourly_rate) as min_rate,
        MAX(hourly_rate) as max_rate,
        COUNT(DISTINCT region_id) as regions_count,
        COUNT(DISTINCT position_id) as positions_count
    FROM hourly_rates
");
$stats = mysqli_fetch_assoc($stats_query);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title><?= $pageTitle ?> - Админ-панель</title>
    <!-- Не подключаем style.css, используем только свои стили -->
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
        /* Контейнер админ-панели на всю ширину */
        .admin-wrapper {
            max-width: 1600px;
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
        
        /* Шапка */
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 20px;
            width: 100%;
        }
        
        .header h1 {
            font-size: 28px;
            margin-bottom: 10px;
        }
        
        .header p {
            opacity: 0.9;
            font-size: 16px;
        }
        
        /* Статистика */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
            width: 100%;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: transform 0.2s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
        }
        
        .stat-label {
            color: #666;
            font-size: 14px;
            margin-bottom: 10px;
        }
        
        .stat-value {
            font-size: 32px;
            font-weight: bold;
            color: #2c3e50;
        }
        
        .stat-value small {
            font-size: 16px;
            color: #95a5a6;
            font-weight: normal;
        }
        
        /* Панель фильтров */
        .filters-panel {
            background: white;
            padding: 25px;
            border-radius: 12px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            width: 100%;
        }
        
        .filters-title {
            font-size: 18px;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .filters-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
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
            padding: 10px 12px;
            border: 2px solid #e1e8ed;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.2s;
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
            margin-top: 20px;
        }
        
        /* Кнопки сортировки */
        .sort-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 20px;
            width: 100%;
        }
        
        .sort-btn {
            padding: 8px 16px;
            background: white;
            border: 2px solid #e1e8ed;
            border-radius: 20px;
            color: #555;
            text-decoration: none;
            font-size: 14px;
            transition: all 0.2s;
            cursor: pointer;
        }
        
        .sort-btn:hover {
            background: #f8f9fa;
            border-color: #667eea;
        }
        
        .sort-btn.active {
            background: #667eea;
            border-color: #667eea;
            color: white;
        }
        
        /* Панель массового редактирования */
        .bulk-edit-panel {
            background: white;
            padding: 25px;
            border-radius: 12px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border-left: 4px solid #28a745;
            width: 100%;
        }
        
        .bulk-edit-title {
            font-size: 18px;
            font-weight: 600;
            color: #28a745;
            margin-bottom: 15px;
        }
        
        .bulk-edit-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            align-items: end;
        }
        
        .bulk-field {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        
        .bulk-field label {
            font-size: 13px;
            font-weight: 600;
            color: #555;
        }
        
        /* Информационная панель */
        .info-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: white;
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #667eea;
            width: 100%;
        }
        
        .changes-count {
            background: #667eea;
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 500;
        }
        
        /* Таблица */
        .table-container {
            background: white;
            border-radius: 12px;
            overflow: auto;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            width: 100%;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 800px;
        }
        
        th {
            background: #2c3e50;
            color: white;
            padding: 15px;
            text-align: left;
            font-weight: 500;
            font-size: 14px;
            white-space: nowrap;
        }
        
        td {
            padding: 15px;
            border-bottom: 1px solid #e9ecef;
            font-size: 14px;
        }
        
        tr:hover {
            background: #f8f9fa;
        }
        
        tr.highlighted {
            background: #fff3cd;
        }
        
        tr.highlighted:hover {
            background: #ffe69c;
        }
        
        .rate-cell {
            font-weight: 600;
            color: #2c3e50;
        }
        
        .rate-input {
            width: 100px;
            padding: 8px 12px;
            border: 2px solid #e1e8ed;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            text-align: right;
            transition: all 0.2s;
        }
        
        .rate-input:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .rate-input.changed {
            background: #fff3cd;
            border-color: #ffc107;
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
        
        .badge-primary {
            background: #667eea;
            color: white;
        }
        
        .badge-success {
            background: #28a745;
            color: white;
        }
        
        .badge-warning {
            background: #ffc107;
            color: #212529;
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
            background: #6c757d;
            color: white;
        }
        
        .btn-primary {
            background: #667eea;
        }
        
        .btn-primary:hover {
            background: #5a67d8;
        }
        
        .btn-success {
            background: #28a745;
        }
        
        .btn-success:hover {
            background: #218838;
        }
        
        .btn-warning {
            background: #ffc107;
            color: #212529;
        }
        
        .btn-warning:hover {
            background: #e0a800;
        }
        
        .btn-secondary {
            background: #6c757d;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
        }
        
        /* Анимации */
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .animated {
            animation: slideIn 0.3s ease-out;
        }
        
        /* Адаптивность */
        @media (max-width: 768px) {
            .admin-wrapper {
                padding: 10px;
            }
            
            .filters-grid {
                grid-template-columns: 1fr;
            }
            
            .stats-grid {
                grid-template-columns: 1fr 1fr;
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
            
            .bulk-edit-grid {
                grid-template-columns: 1fr;
            }
            
            .table-container {
                overflow-x: auto;
            }
        }
        
        /* Для очень маленьких экранов */
        @media (max-width: 480px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .sort-buttons {
                flex-direction: column;
            }
            
            .sort-btn {
                text-align: center;
            }
        }
        
        /* Стили для сообщений */
        .message-success {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #28a745;
            width: 100%;
        }
        
        .message-error {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #dc3545;
            width: 100%;
        }
        
        /* История изменений */
        .history-panel {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-top: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            width: 100%;
        }
        
        .history-item {
            padding: 10px;
            border-bottom: 1px solid #e9ecef;
            font-size: 13px;
        }
        
        .history-item:last-child {
            border-bottom: none;
        }
        
        .history-date {
            color: #666;
            margin-right: 15px;
        }
        
        /* Подсказки */
        .hint-box {
            background: #e8f4f8;
            padding: 15px;
            border-radius: 8px;
            margin-top: 20px;
            font-size: 13px;
            width: 100%;
        }
    </style>
</head>
<body>
    <!-- Навигация внутри админ-панели -->
    <div class="admin-nav">
        <a href="index.php" <?= $currentPage == 'dashboard' ? 'class="active"' : '' ?>>📊 Дашборд</a>
        <a href="users.php" <?= $currentPage == 'users' ? 'class="active"' : '' ?>>👥 Пользователи</a>
        <a href="positions.php" <?= $currentPage == 'positions' ? 'class="active"' : '' ?>>📋 Должности</a>
        <a href="stores.php" <?= $currentPage == 'stores' ? 'class="active"' : '' ?>>🏪 Магазины</a>
        <a href="rates.php" class="active">💰 Часовые ставки</a>
        <a href="../index.php" class="back-link">← На главную</a>
    </div>
    
    <div class="admin-wrapper">
        <!-- Шапка -->
        <div class="header animated">
            <h1>💰 Управление часовыми ставками</h1>
            <p>Редактирование, фильтрация и массовое обновление ставок для разных регионов и должностей</p>
        </div>
        
        <!-- Статистика -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-label">Всего ставок</div>
                <div class="stat-value"><?= number_format($stats['total'], 0, '.', ' ') ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Средняя ставка</div>
                <div class="stat-value"><?= number_format($stats['avg_rate'], 0, '.', ' ') ?> ₽ <small>/час</small></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Диапазон</div>
                <div class="stat-value"><?= number_format($stats['min_rate'], 0, '.', ' ') ?> - <?= number_format($stats['max_rate'], 0, '.', ' ') ?> ₽</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Регионов / Должностей</div>
                <div class="stat-value"><?= $stats['regions_count'] ?> / <?= $stats['positions_count'] ?></div>
            </div>
        </div>
        
        <!-- Сообщение об успехе -->
        <?php if (isset($message)): ?>
        <div class="message-success">
            <?= htmlspecialchars($message) ?>
        </div>
        <?php endif; ?>
        
        <!-- Панель фильтров -->
        <div class="filters-panel animated">
            <div class="filters-title">
                <span>🔍 Фильтры и поиск</span>
                <span class="badge badge-primary">Найдено: <?= mysqli_num_rows($rates) ?> ставок</span>
            </div>
            
            <form method="GET" id="filterForm">
                <div class="filters-grid">
                    <div class="filter-group">
                        <label>Регион</label>
                        <select name="region_id" class="filter-select">
                            <option value="">Все регионы</option>
                            <?php foreach ($regions_array as $region): ?>
                            <option value="<?= $region['region_id'] ?>" 
                                <?= ($_GET['region_id'] ?? '') == $region['region_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($region['region_name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label>Должность</label>
                        <select name="position_id" class="filter-select">
                            <option value="">Все должности</option>
                            <?php foreach ($positions_array as $position): ?>
                            <option value="<?= $position['position_id'] ?>" 
                                <?= ($_GET['position_id'] ?? '') == $position['position_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($position['position_name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label>Тарифная группа</label>
                        <select name="tariff_group_id" class="filter-select">
                            <option value="">Все группы</option>
                            <?php foreach ($tariff_groups_array as $group): ?>
                            <option value="<?= $group['tariff_group_id'] ?>" 
                                <?= ($_GET['tariff_group_id'] ?? '') == $group['tariff_group_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($group['group_name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label>Поиск по тексту</label>
                        <input type="text" name="search" class="filter-input" 
                               value="<?= htmlspecialchars($_GET['search'] ?? '') ?>" 
                               placeholder="Название региона, должности...">
                    </div>
                    
                    <div class="filter-group">
                        <label>Мин. ставка (₽)</label>
                        <input type="number" name="min_rate" class="filter-input" 
                               value="<?= htmlspecialchars($_GET['min_rate'] ?? '') ?>" 
                               placeholder="От">
                    </div>
                    
                    <div class="filter-group">
                        <label>Макс. ставка (₽)</label>
                        <input type="number" name="max_rate" class="filter-input" 
                               value="<?= htmlspecialchars($_GET['max_rate'] ?? '') ?>" 
                               placeholder="До">
                    </div>
                </div>
                
                <div class="filter-actions">
                    <button type="submit" class="btn btn-primary">🔍 Применить фильтры</button>
                    <a href="?" class="btn btn-secondary">🔄 Сбросить</a>
                </div>
            </form>
        </div>
        
        <!-- Кнопки сортировки -->
        <div class="sort-buttons">
            <a href="?<?= http_build_query(array_merge($_GET, ['sort' => 'region_asc'])) ?>" 
               class="sort-btn <?= ($_GET['sort'] ?? '') == 'region_asc' ? 'active' : '' ?>">
                📍 По региону ↑
            </a>
            <a href="?<?= http_build_query(array_merge($_GET, ['sort' => 'region_desc'])) ?>" 
               class="sort-btn <?= ($_GET['sort'] ?? '') == 'region_desc' ? 'active' : '' ?>">
                📍 По региону ↓
            </a>
            <a href="?<?= http_build_query(array_merge($_GET, ['sort' => 'position_asc'])) ?>" 
               class="sort-btn <?= ($_GET['sort'] ?? '') == 'position_asc' ? 'active' : '' ?>">
                👤 По должности ↑
            </a>
            <a href="?<?= http_build_query(array_merge($_GET, ['sort' => 'position_desc'])) ?>" 
               class="sort-btn <?= ($_GET['sort'] ?? '') == 'position_desc' ? 'active' : '' ?>">
                👤 По должности ↓
            </a>
            <a href="?<?= http_build_query(array_merge($_GET, ['sort' => 'rate_asc'])) ?>" 
               class="sort-btn <?= ($_GET['sort'] ?? '') == 'rate_asc' ? 'active' : '' ?>">
                💰 По ставке ↑
            </a>
            <a href="?<?= http_build_query(array_merge($_GET, ['sort' => 'rate_desc'])) ?>" 
               class="sort-btn <?= ($_GET['sort'] ?? '') == 'rate_desc' ? 'active' : '' ?>">
                💰 По ставке ↓
            </a>
        </div>
        
        <!-- Панель массового редактирования -->
        <div class="bulk-edit-panel animated">
            <div class="bulk-edit-title">📦 Массовое обновление ставок</div>
            <div class="bulk-edit-grid">
                <div class="bulk-field">
                    <label>Изменить на (₽)</label>
                    <input type="number" id="bulkRate" class="filter-input" placeholder="Новая ставка">
                </div>
                <div class="bulk-field">
                    <label>Применить к</label>
                    <select id="bulkScope" class="filter-select">
                        <option value="visible">Ко всем видимым</option>
                        <option value="filtered">Ко всем отфильтрованным</option>
                        <option value="selected">К выбранным</option>
                    </select>
                </div>
                <div class="bulk-field">
                    <label>&nbsp;</label>
                    <button onclick="applyBulkUpdate()" class="btn btn-success">🔄 Применить</button>
                </div>
                <div class="bulk-field">
                    <label>&nbsp;</label>
                    <button onclick="highlightRatesBelow(100)" class="btn btn-warning">⚠️ Ставки < 100</button>
                </div>
            </div>
        </div>
        
        <!-- Информационная панель -->
        <div class="info-bar">
            <span>
                <strong>📊 Показано:</strong> <?= mysqli_num_rows($rates) ?> записей
                <?php if (mysqli_num_rows($rates) > 0): ?>
                (с <?= $stats['regions_count'] ?> регионов, <?= $stats['positions_count'] ?> должностей)
                <?php endif; ?>
            </span>
            <span class="changes-count" id="changedCount">0 изменений</span>
        </div>
        
        <!-- Форма редактирования -->
        <form method="POST" id="ratesForm">
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th width="40">
                                <input type="checkbox" id="selectAll" onclick="toggleAll()">
                            </th>
                            <th>Регион</th>
                            <th>Должность</th>
                            <th>Тарифная группа</th>
                            <th>Текущая ставка</th>
                            <th>Новая ставка (₽/час)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $row_count = 0;
                        while ($rate = mysqli_fetch_assoc($rates)): 
                            $row_count++;
                        ?>
                        <tr id="row-<?= $rate['hourly_rate_id'] ?>" 
                            data-rate="<?= $rate['hourly_rate'] ?>"
                            class="<?= $rate['hourly_rate'] < 100 ? 'highlighted' : '' ?>">
                            <td>
                                <input type="checkbox" class="row-select" value="<?= $rate['hourly_rate_id'] ?>">
                            </td>
                            <td><strong><?= htmlspecialchars($rate['region_name'] ?? 'Не указан') ?></strong></td>
                            <td><?= htmlspecialchars($rate['position_name'] ?? 'Не указана') ?></td>
                            <td>
                                <span class="badge"><?= htmlspecialchars($rate['tariff_group_name'] ?? 'Не указана') ?></span>
                            </td>
                            <td class="rate-cell" id="current-<?= $rate['hourly_rate_id'] ?>">
                                <?= number_format($rate['hourly_rate'], 0, '.', ' ') ?> ₽
                            </td>
                            <td>
                                <input type="number" 
                                       name="updates[<?= $rate['hourly_rate_id'] ?>]" 
                                       id="input-<?= $rate['hourly_rate_id'] ?>"
                                       class="rate-input"
                                       data-original="<?= $rate['hourly_rate'] ?>"
                                       placeholder="Новая ставка"
                                       min="1"
                                       step="1"
                                       onchange="markChanged(this, <?= $rate['hourly_rate_id'] ?>)"
                                       onkeyup="markChanged(this, <?= $rate['hourly_rate_id'] ?>)">
                            </td>
                        </tr>
                        <?php endwhile; ?>
                        
                        <?php if ($row_count == 0): ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 50px;">
                                <h3 style="color: #95a5a6;">😕 Ничего не найдено</h3>
                                <p>Попробуйте изменить параметры фильтрации</p>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <?php if ($row_count > 0): ?>
            <div style="display: flex; gap: 10px; margin: 20px 0; justify-content: space-between;">
                <div style="display: flex; gap: 10px;">
                    <button type="submit" class="btn btn-primary" id="saveBtn">💾 Сохранить изменения</button>
                    <button type="button" class="btn btn-success" onclick="saveSelectedOnly()">✅ Сохранить выбранные</button>
                    <button type="button" class="btn btn-warning" onclick="resetAllChanges()">🔄 Сбросить всё</button>
                </div>
                <div>
                    <span class="badge badge-primary" id="selectedCount">0 выбрано</span>
                </div>
            </div>
            <?php endif; ?>
        </form>
        
        <!-- История последних изменений -->
        <?php
        $history = mysqli_query($conn, "SELECT * FROM changes_log ORDER BY change_date DESC LIMIT 5");
        if (mysqli_num_rows($history) > 0):
        ?>
        <div class="history-panel">
            <h3 style="margin-bottom: 15px;">📋 Последние изменения</h3>
            <?php while ($log = mysqli_fetch_assoc($history)): ?>
            <div class="history-item">
                <span class="history-date"><?= date('d.m.Y H:i', strtotime($log['change_date'])) ?></span>
                <strong>+<?= $log['changes_count'] ?></strong> ставок обновлено
                <small style="color: #999; float: right;">ID: <?= $log['user_id'] ?></small>
            </div>
            <?php endwhile; ?>
        </div>
        <?php endif; ?>
        
        <!-- Подсказки -->
        <div class="hint-box">
            <strong>💡 Горячие клавиши:</strong>
            Ctrl+Enter - сохранить, Ctrl+A - выделить все, Esc - сброс выбранного
        </div>
    </div>

    <script>
        let changedRates = new Set();
        
        // Отметить измененную ставку
        function markChanged(input, rateId) {
            if (input.value && input.value !== '') {
                input.classList.add('changed');
                changedRates.add(rateId);
                document.getElementById(`row-${rateId}`).style.background = '#fff3cd';
            } else {
                input.classList.remove('changed');
                changedRates.delete(rateId);
                document.getElementById(`row-${rateId}`).style.background = '';
            }
            
            updateChangedCount();
        }
        
        // Обновить счетчики
        function updateChangedCount() {
            const count = changedRates.size;
            document.getElementById('changedCount').textContent = count + ' изменений';
            
            const saveBtn = document.getElementById('saveBtn');
            if (saveBtn) {
                saveBtn.innerHTML = count ? '💾 Сохранить ' + count + ' изменений' : '💾 Сохранить изменения';
            }
        }
        
        // Выделить все строки
        function toggleAll() {
            const checkboxes = document.querySelectorAll('.row-select');
            const selectAll = document.getElementById('selectAll');
            
            checkboxes.forEach(cb => {
                cb.checked = selectAll.checked;
            });
            
            updateSelectedCount();
        }
        
        // Обновить счетчик выбранных
        function updateSelectedCount() {
            const selected = document.querySelectorAll('.row-select:checked').length;
            document.getElementById('selectedCount').textContent = selected + ' выбрано';
        }
        
        // Подсветить ставки ниже определенного значения
        function highlightRatesBelow(threshold) {
            const rows = document.querySelectorAll('tbody tr');
            let count = 0;
            
            rows.forEach(row => {
                const rate = parseInt(row.dataset.rate);
                if (rate < threshold) {
                    row.style.background = '#fff3cd';
                    count++;
                }
            });
            
            alert(`Найдено ${count} ставок ниже ${threshold} ₽`);
        }
        
        // Массовое обновление
        function applyBulkUpdate() {
            const newRate = document.getElementById('bulkRate').value;
            const scope = document.getElementById('bulkScope').value;
            
            if (!newRate || newRate < 1) {
                alert('Введите корректную ставку');
                return;
            }
            
            let inputs = [];
            
            if (scope === 'visible') {
                inputs = Array.from(document.querySelectorAll('.rate-input'));
            } else if (scope === 'filtered') {
                // Все видимые строки
                inputs = Array.from(document.querySelectorAll('tbody tr:not([style*="display: none"]) .rate-input'));
            } else if (scope === 'selected') {
                // Только выбранные
                document.querySelectorAll('.row-select:checked').forEach(cb => {
                    const input = document.getElementById(`input-${cb.value}`);
                    if (input) inputs.push(input);
                });
            }
            
            inputs.forEach(input => {
                if (input) {
                    input.value = newRate;
                    const rateId = input.id.replace('input-', '');
                    markChanged(input, parseInt(rateId));
                }
            });
            
            alert(`Применено к ${inputs.length} ставкам`);
        }
        
        // Сохранить только выбранные
        function saveSelectedOnly() {
            const selectedIds = [];
            document.querySelectorAll('.row-select:checked').forEach(cb => {
                selectedIds.push(cb.value);
            });
            
            if (selectedIds.length === 0) {
                alert('Ничего не выбрано');
                return;
            }
            
            // Отключаем все невыбранные поля
            document.querySelectorAll('.rate-input').forEach(input => {
                const id = input.id.replace('input-', '');
                if (!selectedIds.includes(id)) {
                    input.disabled = true;
                }
            });
            
            document.getElementById('ratesForm').submit();
        }
        
        // Сброс всех изменений
        function resetAllChanges() {
            if (!confirm('Сбросить все несохраненные изменения?')) return;
            
            document.querySelectorAll('.rate-input.changed').forEach(input => {
                input.value = '';
                input.classList.remove('changed');
                const rateId = input.id.replace('input-', '');
                changedRates.delete(parseInt(rateId));
                document.getElementById(`row-${rateId}`).style.background = '';
            });
            
            updateChangedCount();
        }
        
        // Инициализация чекбоксов
        document.querySelectorAll('.row-select').forEach(cb => {
            cb.addEventListener('change', updateSelectedCount);
        });
        
        // Горячие клавиши
        document.addEventListener('keydown', function(e) {
            // Ctrl+Enter
            if (e.ctrlKey && e.key === 'Enter') {
                e.preventDefault();
                document.getElementById('ratesForm').submit();
            }
            
            // Ctrl+A
            if (e.ctrlKey && e.key === 'a') {
                e.preventDefault();
                document.querySelectorAll('.row-select').forEach(cb => cb.checked = true);
                updateSelectedCount();
            }
            
            // Escape
            if (e.key === 'Escape') {
                document.querySelectorAll('.row-select').forEach(cb => cb.checked = false);
                updateSelectedCount();
            }
        });
    </script>
</body>
</html>