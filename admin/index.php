<?php
require_once '../db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$currentPage = 'dashboard';

// Получаем статистику
$users_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM users"))['count'];
$positions_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM positions"))['count'];
$stores_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM stores"))['count'];
$rates_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM hourly_rates"))['count'];
$fixed_salaries_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM fixed_salaries"))['count'];

// Активность за последние 24 часа
$recent_logs = mysqli_query($conn, "
    SELECT l.*, u.username 
    FROM user_logs l 
    LEFT JOIN users u ON l.user_id = u.user_id 
    WHERE l.created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
    ORDER BY l.created_at DESC 
    LIMIT 10
");

// Статистика по дням (для графика)
$daily_stats = mysqli_query($conn, "
    SELECT 
        DATE(created_at) as date,
        COUNT(*) as actions_count
    FROM user_logs 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    GROUP BY DATE(created_at)
    ORDER BY date DESC
");
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Админ-панель - Дашборд</title>
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
        
        /* Контейнер на всю ширину */
        .admin-wrapper {
            max-width: 1600px;
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
        .dashboard-header {
            margin-bottom: 30px;
        }
        
        .dashboard-header h1 {
            font-size: 32px;
            color: #2c3e50;
            margin-bottom: 10px;
        }
        
        .dashboard-header p {
            color: #666;
            font-size: 16px;
        }
        
        /* Сетка статистики */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
            width: 100%;
        }
        
        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            position: relative;
            overflow: hidden;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(106, 78, 142, 0.15);
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #667eea, #764ba2);
        }
        
        .stat-icon {
            font-size: 32px;
            margin-bottom: 15px;
            color: #6b4e8e;
        }
        
        .stat-label {
            color: #666;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 10px;
        }
        
        .stat-number {
            font-size: 36px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 15px;
        }
        
        .stat-link {
            margin-top: auto;
            color: #667eea;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        
        .stat-link:hover {
            color: #5a67d8;
            text-decoration: underline;
        }
        
        /* Две колонки для основного контента */
        .dashboard-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
            width: 100%;
        }
        
        /* Карточки */
        .dashboard-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            width: 100%;
        }
        
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .card-header h3 {
            color: #2c3e50;
            font-size: 18px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .card-header .badge {
            background: #e9ecef;
            color: #495057;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }
        
        /* Список активности */
        .activity-list {
            list-style: none;
        }
        
        .activity-item {
            display: flex;
            align-items: flex-start;
            gap: 15px;
            padding: 12px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .activity-item:last-child {
            border-bottom: none;
        }
        
        .activity-icon {
            width: 36px;
            height: 36px;
            background: #f0e6ff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #6b4e8e;
            font-size: 18px;
        }
        
        .activity-content {
            flex: 1;
        }
        
        .activity-user {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 4px;
        }
        
        .activity-action {
            color: #666;
            font-size: 14px;
            margin-bottom: 4px;
        }
        
        .activity-time {
            color: #999;
            font-size: 12px;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        /* График */
        .chart-container {
            margin-top: 20px;
        }
        
        .chart-bar {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
        }
        
        .chart-label {
            width: 100px;
            font-size: 13px;
            color: #666;
        }
        
        .chart-bar-fill {
            height: 30px;
            background: linear-gradient(90deg, #667eea, #764ba2);
            border-radius: 6px;
            min-width: 4px;
            transition: width 0.3s;
        }
        
        .chart-value {
            font-size: 13px;
            color: #666;
            font-weight: 500;
        }
        
        /* Быстрые действия */
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        
        .quick-action-btn {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 12px;
            text-decoration: none;
            color: #2c3e50;
            transition: all 0.3s;
        }
        
        .quick-action-btn:hover {
            background: #f0e6ff;
            transform: translateY(-3px);
        }
        
        .quick-action-icon {
            font-size: 24px;
            color: #6b4e8e;
        }
        
        .quick-action-text {
            font-size: 14px;
            font-weight: 500;
            text-align: center;
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
        
        /* Адаптивность */
        @media (max-width: 1024px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
        }
        
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
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (max-width: 480px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .quick-actions {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Навигация -->
    <div class="admin-nav">
        <a href="index.php" class="active">📊 Дашборд</a>
        <a href="users.php">👥 Пользователи</a>
        <a href="positions.php">📋 Должности</a>
        <a href="stores.php">🏪 Магазины</a>
        <a href="rates.php">💰 Часовые ставки</a>
        <a href="../index.php" class="back-link">← На главную</a>
    </div>
    
    <div class="admin-wrapper">
        <!-- Заголовок -->
        <div class="dashboard-header">
            <h1>📊 Панель управления</h1>
            <p>Добро пожаловать, <?= htmlspecialchars($_SESSION['user_name'] ?? $_SESSION['username']) ?>! Сегодня <?= date('d.m.Y') ?></p>
        </div>
        
        <!-- Статистика -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">👥</div>
                <div class="stat-label">Пользователи</div>
                <div class="stat-number"><?= $users_count ?></div>
                <a href="users.php" class="stat-link">Управление пользователями →</a>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">📋</div>
                <div class="stat-label">Должности</div>
                <div class="stat-number"><?= $positions_count ?></div>
                <a href="positions.php" class="stat-link">Управление должностями →</a>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">🏪</div>
                <div class="stat-label">Магазины</div>
                <div class="stat-number"><?= $stores_count ?></div>
                <a href="stores.php" class="stat-link">Управление магазинами →</a>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">💰</div>
                <div class="stat-label">Часовые ставки</div>
                <div class="stat-number"><?= $rates_count ?></div>
                <a href="rates.php" class="stat-link">Управление ставками →</a>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">💵</div>
                <div class="stat-label">Фиксированные оклады</div>
                <div class="stat-number"><?= $fixed_salaries_count ?></div>
                <a href="fixed_salaries.php" class="stat-link">Управление окладами →</a>
            </div>
        </div>
        
        <!-- Две колонки -->
        <div class="dashboard-grid">
            <!-- Левая колонка: активность -->
            <div class="dashboard-card">
                <div class="card-header">
                    <h3>📋 Последняя активность (24 часа)</h3>
                    <span class="badge"><?= mysqli_num_rows($recent_logs) ?> действий</span>
                </div>
                
                <ul class="activity-list">
                    <?php if (mysqli_num_rows($recent_logs) > 0): ?>
                        <?php while ($log = mysqli_fetch_assoc($recent_logs)): ?>
                        <li class="activity-item">
                            <div class="activity-icon">
                                <?php
                                $icon = '👤';
                                if (strpos($log['action'], 'login') !== false) $icon = '🔑';
                                if (strpos($log['action'], 'update') !== false) $icon = '✏️';
                                if (strpos($log['action'], 'create') !== false) $icon = '➕';
                                if (strpos($log['action'], 'delete') !== false) $icon = '🗑️';
                                echo $icon;
                                ?>
                            </div>
                            <div class="activity-content">
                                <div class="activity-user"><?= htmlspecialchars($log['username'] ?? 'Система') ?></div>
                                <div class="activity-action"><?= htmlspecialchars($log['action']) ?></div>
                                <div class="activity-time">
                                    <span>🕒</span>
                                    <?= date('d.m.Y H:i', strtotime($log['created_at'])) ?>
                                </div>
                            </div>
                        </li>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <li style="text-align: center; padding: 30px; color: #999;">
                            Нет активности за последние 24 часа
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
            
            <!-- Правая колонка: статистика и быстрые действия -->
            <div class="dashboard-card">
                <div class="card-header">
                    <h3>📊 Статистика за 7 дней</h3>
                </div>
                
                <div class="chart-container">
                    <?php 
                    $max_actions = 0;
                    $chart_data = [];
                    while ($stat = mysqli_fetch_assoc($daily_stats)) {
                        $chart_data[] = $stat;
                        if ($stat['actions_count'] > $max_actions) {
                            $max_actions = $stat['actions_count'];
                        }
                    }
                    
                    if (!empty($chart_data)):
                        foreach ($chart_data as $stat):
                            $percent = $max_actions > 0 ? ($stat['actions_count'] / $max_actions) * 100 : 0;
                    ?>
                    <div class="chart-bar">
                        <div class="chart-label"><?= date('d.m', strtotime($stat['date'])) ?></div>
                        <div class="chart-bar-fill" style="width: <?= $percent ?>%;"></div>
                        <div class="chart-value"><?= $stat['actions_count'] ?></div>
                    </div>
                    <?php 
                        endforeach;
                    else:
                    ?>
                    <p style="text-align: center; color: #999; padding: 20px;">Нет данных за последние 7 дней</p>
                    <?php endif; ?>
                </div>
                
                <div class="card-header" style="margin-top: 30px;">
                    <h3>⚡ Быстрые действия</h3>
                </div>
                
                <div class="quick-actions">
                    <a href="users.php?action=add" class="quick-action-btn">
                        <span class="quick-action-icon">👤</span>
                        <span class="quick-action-text">Новый пользователь</span>
                    </a>
                    <a href="positions.php?action=add" class="quick-action-btn">
                        <span class="quick-action-icon">📋</span>
                        <span class="quick-action-text">Новая должность</span>
                    </a>
                    <a href="stores.php?action=add" class="quick-action-btn">
                        <span class="quick-action-icon">🏪</span>
                        <span class="quick-action-text">Новый магазин</span>
                    </a>
                    <a href="rates.php" class="quick-action-btn">
                        <span class="quick-action-icon">💰</span>
                        <span class="quick-action-text">Массовое обновление ставок</span>
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Дополнительная информация -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin-top: 20px;">
            <div class="dashboard-card">
                <h3 style="margin-bottom: 15px;">ℹ️ Информация о системе</h3>
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <td style="padding: 8px 0; color: #666;">Версия PHP:</td>
                        <td style="padding: 8px 0; font-weight: 500;"><?= phpversion() ?></td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 0; color: #666;">Сервер:</td>
                        <td style="padding: 8px 0; font-weight: 500;"><?= $_SERVER['SERVER_SOFTWARE'] ?? 'N/A' ?></td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 0; color: #666;">База данных:</td>
                        <td style="padding: 8px 0; font-weight: 500;">MySQL</td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 0; color: #666;">Ваш IP:</td>
                        <td style="padding: 8px 0; font-weight: 500;"><?= $_SERVER['REMOTE_ADDR'] ?></td>
                    </tr>
                </table>
            </div>
            
            <div class="dashboard-card">
                <h3 style="margin-bottom: 15px;">📌 Полезные ссылки</h3>
                <ul style="list-style: none;">
                    <li style="margin-bottom: 10px;">
                        <a href="../index.php" style="color: #667eea; text-decoration: none;">→ Перейти к калькулятору</a>
                    </li>
                    <li style="margin-bottom: 10px;">
                        <a href="../register.php" style="color: #667eea; text-decoration: none;">→ Регистрация нового пользователя</a>
                    </li>
                    <li style="margin-bottom: 10px;">
                        <a href="../logout.php" style="color: #dc3545; text-decoration: none;">→ Выйти из системы</a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</body>
</html>