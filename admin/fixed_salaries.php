<?php
require_once '../db.php';

// Проверка прав администратора
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$currentPage = 'fixed_salaries';
$message = '';
$error = '';

// Получаем данные для фильтров
$regions = mysqli_query($conn, "SELECT * FROM regions ORDER BY region_name");
$tariff_groups = mysqli_query($conn, "SELECT * FROM tariff_groups ORDER BY group_name");

// Обработка добавления нового оклада
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add') {
        $region_id = intval($_POST['region_id']);
        $tariff_group_id = intval($_POST['tariff_group_id']);
        $fixed_salary = intval($_POST['fixed_salary']);
        
        if ($region_id > 0 && $tariff_group_id > 0 && $fixed_salary > 0) {
            // Проверяем, существует ли уже такая запись
            $checkStmt = mysqli_prepare($conn, "SELECT fixed_salary_id FROM fixed_salaries WHERE region_id = ? AND tariff_group_id = ?");
            mysqli_stmt_bind_param($checkStmt, "ii", $region_id, $tariff_group_id);
            mysqli_stmt_execute($checkStmt);
            mysqli_stmt_store_result($checkStmt);
            
            if (mysqli_stmt_num_rows($checkStmt) == 0) {
                $stmt = mysqli_prepare($conn, "INSERT INTO fixed_salaries (region_id, tariff_group_id, fixed_salary) VALUES (?, ?, ?)");
                mysqli_stmt_bind_param($stmt, "iii", $region_id, $tariff_group_id, $fixed_salary);
                
                if (mysqli_stmt_execute($stmt)) {
                    $message = "Фиксированный оклад успешно добавлен";
                    
                    // Логирование
                    $logStmt = mysqli_prepare($conn, "INSERT INTO user_logs (user_id, action, ip_address) VALUES (?, ?, ?)");
                    $action = "Добавлен фиксированный оклад: регион $region_id, группа $tariff_group_id, сумма $fixed_salary";
                    $ip = $_SERVER['REMOTE_ADDR'];
                    mysqli_stmt_bind_param($logStmt, "iss", $_SESSION['user_id'], $action, $ip);
                    mysqli_stmt_execute($logStmt);
                } else {
                    $error = "Ошибка при добавлении оклада";
                }
            } else {
                $error = "Запись для этого региона и тарифной группы уже существует";
            }
        } else {
            $error = "Заполните все поля корректно";
        }
    }
    
    // Обработка редактирования
    if ($_POST['action'] === 'edit' && isset($_POST['salary_id'])) {
        $salary_id = intval($_POST['salary_id']);
        $region_id = intval($_POST['region_id']);
        $tariff_group_id = intval($_POST['tariff_group_id']);
        $fixed_salary = intval($_POST['fixed_salary']);
        
        if ($region_id > 0 && $tariff_group_id > 0 && $fixed_salary > 0) {
            // Проверяем, не существует ли другой записи с такими же регионом и группой
            $checkStmt = mysqli_prepare($conn, "SELECT fixed_salary_id FROM fixed_salaries WHERE region_id = ? AND tariff_group_id = ? AND fixed_salary_id != ?");
            mysqli_stmt_bind_param($checkStmt, "iii", $region_id, $tariff_group_id, $salary_id);
            mysqli_stmt_execute($checkStmt);
            mysqli_stmt_store_result($checkStmt);
            
            if (mysqli_stmt_num_rows($checkStmt) == 0) {
                $stmt = mysqli_prepare($conn, "UPDATE fixed_salaries SET region_id = ?, tariff_group_id = ?, fixed_salary = ? WHERE fixed_salary_id = ?");
                mysqli_stmt_bind_param($stmt, "iiii", $region_id, $tariff_group_id, $fixed_salary, $salary_id);
                
                if (mysqli_stmt_execute($stmt)) {
                    $message = "Фиксированный оклад успешно обновлен";
                    
                    // Логирование
                    $logStmt = mysqli_prepare($conn, "INSERT INTO user_logs (user_id, action, ip_address) VALUES (?, ?, ?)");
                    $action = "Обновлен фиксированный оклад ID $salary_id: регион $region_id, группа $tariff_group_id, сумма $fixed_salary";
                    $ip = $_SERVER['REMOTE_ADDR'];
                    mysqli_stmt_bind_param($logStmt, "iss", $_SESSION['user_id'], $action, $ip);
                    mysqli_stmt_execute($logStmt);
                } else {
                    $error = "Ошибка при обновлении оклада";
                }
            } else {
                $error = "Запись для этого региона и тарифной группы уже существует";
            }
        } else {
            $error = "Заполните все поля корректно";
        }
    }
}

// Обработка удаления
if (isset($_GET['delete'])) {
    $salary_id = intval($_GET['delete']);
    
    $deleteStmt = mysqli_prepare($conn, "DELETE FROM fixed_salaries WHERE fixed_salary_id = ?");
    mysqli_stmt_bind_param($deleteStmt, "i", $salary_id);
    
    if (mysqli_stmt_execute($deleteStmt)) {
        $message = "Фиксированный оклад удален";
        
        // Логирование
        $logStmt = mysqli_prepare($conn, "INSERT INTO user_logs (user_id, action, ip_address) VALUES (?, ?, ?)");
        $action = "Удален фиксированный оклад ID $salary_id";
        $ip = $_SERVER['REMOTE_ADDR'];
        mysqli_stmt_bind_param($logStmt, "iss", $_SESSION['user_id'], $action, $ip);
        mysqli_stmt_execute($logStmt);
    } else {
        $error = "Ошибка при удалении оклада";
    }
}

// ПОСТРОЕНИЕ ЗАПРОСА С ФИЛЬТРАЦИЕЙ
$where_conditions = ["1=1"];

if (!empty($_GET['region_id'])) {
    $region_id = intval($_GET['region_id']);
    $where_conditions[] = "fs.region_id = $region_id";
}

if (!empty($_GET['tariff_group_id'])) {
    $group_id = intval($_GET['tariff_group_id']);
    $where_conditions[] = "fs.tariff_group_id = $group_id";
}

if (isset($_GET['min_salary']) && $_GET['min_salary'] !== '') {
    $min_salary = intval($_GET['min_salary']);
    $where_conditions[] = "fs.fixed_salary >= $min_salary";
}

if (isset($_GET['max_salary']) && $_GET['max_salary'] !== '') {
    $max_salary = intval($_GET['max_salary']);
    $where_conditions[] = "fs.fixed_salary <= $max_salary";
}

$where_clause = implode(" AND ", $where_conditions);

// ПОЛУЧАЕМ ДАННЫЕ
$salaries = mysqli_query($conn, "
    SELECT 
        fs.*,
        r.region_name,
        tg.group_name as tariff_group_name
    FROM fixed_salaries fs
    LEFT JOIN regions r ON fs.region_id = r.region_id
    LEFT JOIN tariff_groups tg ON fs.tariff_group_id = tg.tariff_group_id
    WHERE $where_clause
    ORDER BY r.region_name, tg.group_name
");

// СТАТИСТИКА
$stats_query = mysqli_query($conn, "
    SELECT 
        COUNT(*) as total,
        AVG(fixed_salary) as avg_salary,
        MIN(fixed_salary) as min_salary,
        MAX(fixed_salary) as max_salary
    FROM fixed_salaries
");
$stats = mysqli_fetch_assoc($stats_query);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes">
    <title>Фиксированные оклады - Админ-панель</title>
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
            min-width: 800px;
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
        
        .salary-cell {
            font-weight: 600;
            color: #28a745;
            font-size: 16px;
        }
        
        .actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        
        .badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            background: #e9ecef;
            color: #495057;
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
        <a href="stores.php">🏪 Магазины</a>
        <a href="rates.php">💰 Часовые ставки</a>
        <a href="fixed_salaries.php" class="active">💵 Фиксированные оклады</a>
        <a href="../index.php" class="back-link">← На главную</a>
    </div>
    
    <div class="admin-wrapper">
        <!-- Заголовок -->
        <div class="page-header">
            <h1>💵 Управление фиксированными окладами</h1>
            <button class="btn btn-success" onclick="openAddModal()">➕ Добавить оклад</button>
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
                <div class="stat-label">Всего окладов</div>
                <div class="stat-value"><?= number_format($stats['total'], 0, '.', ' ') ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Средний оклад</div>
                <div class="stat-value"><?= number_format($stats['avg_salary'], 0, '.', ' ') ?> ₽</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Мин/Макс</div>
                <div class="stat-value"><?= number_format($stats['min_salary'], 0, '.', ' ') ?> - <?= number_format($stats['max_salary'], 0, '.', ' ') ?> ₽</div>
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
                        <label>Мин. оклад (₽)</label>
                        <input type="number" name="min_salary" class="filter-input" value="<?= htmlspecialchars($_GET['min_salary'] ?? '') ?>" placeholder="От">
                    </div>
                    
                    <div class="filter-group">
                        <label>Макс. оклад (₽)</label>
                        <input type="number" name="max_salary" class="filter-input" value="<?= htmlspecialchars($_GET['max_salary'] ?? '') ?>" placeholder="До">
                    </div>
                </div>
                
                <div class="filter-actions">
                    <button type="submit" class="btn btn-primary">🔍 Применить фильтры</button>
                    <a href="?" class="btn btn-secondary">🔄 Сбросить</a>
                </div>
            </form>
        </div>
        
        <!-- Таблица окладов -->
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Регион</th>
                        <th>Тарифная группа</th>
                        <th>Фиксированный оклад (₽/мес)</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($salaries) > 0): ?>
                        <?php while ($salary = mysqli_fetch_assoc($salaries)): ?>
                        <tr>
                            <td>#<?= $salary['fixed_salary_id'] ?></td>
                            <td><strong><?= htmlspecialchars($salary['region_name'] ?? 'Не указан') ?></strong></td>
                            <td><?= htmlspecialchars($salary['tariff_group_name'] ?? 'Не указана') ?></td>
                            <td class="salary-cell"><?= number_format($salary['fixed_salary'], 0, '.', ' ') ?> ₽</td>
                            <td class="actions">
                                <button class="btn btn-primary btn-sm" onclick="openEditModal(<?= $salary['fixed_salary_id'] ?>, <?= $salary['region_id'] ?>, <?= $salary['tariff_group_id'] ?>, <?= $salary['fixed_salary'] ?>)">✏️ Ред.</button>
                                <a href="?delete=<?= $salary['fixed_salary_id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Вы уверены?')">🗑️ Удалить</a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" style="text-align: center; padding: 50px;">
                                <h3 style="color: #95a5a6;">😕 Нет фиксированных окладов</h3>
                                <p>Добавьте первый оклад</p>
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
                <h3>➕ Добавить фиксированный оклад</h3>
                <span class="modal-close" onclick="closeAddModal()">&times;</span>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div class="modal-body">
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
                    
                    <div class="form-group">
                        <label>Фиксированный оклад (₽/мес) *</label>
                        <input type="number" name="fixed_salary" class="form-control" required min="1" step="100">
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
                <h3>✏️ Редактировать оклад</h3>
                <span class="modal-close" onclick="closeEditModal()">&times;</span>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="salary_id" id="edit_salary_id">
                <div class="modal-body">
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
                    
                    <div class="form-group">
                        <label>Фиксированный оклад (₽/мес) *</label>
                        <input type="number" name="fixed_salary" id="edit_fixed_salary" class="form-control" required min="1" step="100">
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
        
        function openEditModal(id, regionId, groupId, salary) {
            document.getElementById('edit_salary_id').value = id;
            document.getElementById('edit_region_id').value = regionId;
            document.getElementById('edit_tariff_group_id').value = groupId;
            document.getElementById('edit_fixed_salary').value = salary;
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