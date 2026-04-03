<?php
require_once '../db.php';

// Проверка прав администратора
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$currentPage = 'positions';
$message = '';
$error = '';

// Обработка добавления новой должности
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add') {
        $position_name = trim($_POST['position_name']);
        $has_fixed_salary = isset($_POST['has_fixed_salary']) ? 1 : 0;
        
        if (!empty($position_name)) {
            $stmt = mysqli_prepare($conn, "INSERT INTO positions (position_name, has_fixed_salary) VALUES (?, ?)");
            mysqli_stmt_bind_param($stmt, "si", $position_name, $has_fixed_salary);
            
            if (mysqli_stmt_execute($stmt)) {
                $message = "Должность успешно добавлена";
                
                // Логирование
                $logStmt = mysqli_prepare($conn, "INSERT INTO user_logs (user_id, action, ip_address) VALUES (?, ?, ?)");
                $action = "Добавлена должность: $position_name";
                $ip = $_SERVER['REMOTE_ADDR'];
                mysqli_stmt_bind_param($logStmt, "iss", $_SESSION['user_id'], $action, $ip);
                mysqli_stmt_execute($logStmt);
            } else {
                $error = "Ошибка при добавлении должности";
            }
        } else {
            $error = "Название должности не может быть пустым";
        }
    }
    
    // Обработка редактирования
    if ($_POST['action'] === 'edit' && isset($_POST['position_id'])) {
        $position_id = intval($_POST['position_id']);
        $position_name = trim($_POST['position_name']);
        $has_fixed_salary = isset($_POST['has_fixed_salary']) ? 1 : 0;
        
        if (!empty($position_name)) {
            $stmt = mysqli_prepare($conn, "UPDATE positions SET position_name = ?, has_fixed_salary = ? WHERE position_id = ?");
            mysqli_stmt_bind_param($stmt, "sii", $position_name, $has_fixed_salary, $position_id);
            
            if (mysqli_stmt_execute($stmt)) {
                $message = "Должность успешно обновлена";
                
                // Логирование
                $logStmt = mysqli_prepare($conn, "INSERT INTO user_logs (user_id, action, ip_address) VALUES (?, ?, ?)");
                $action = "Обновлена должность ID $position_id: $position_name";
                $ip = $_SERVER['REMOTE_ADDR'];
                mysqli_stmt_bind_param($logStmt, "iss", $_SESSION['user_id'], $action, $ip);
                mysqli_stmt_execute($logStmt);
            } else {
                $error = "Ошибка при обновлении должности";
            }
        } else {
            $error = "Название должности не может быть пустым";
        }
    }
}

// Обработка удаления
if (isset($_GET['delete'])) {
    $position_id = intval($_GET['delete']);
    
    // Проверяем, используется ли должность
    $checkStmt = mysqli_prepare($conn, "SELECT COUNT(*) as count FROM hourly_rates WHERE position_id = ?");
    mysqli_stmt_bind_param($checkStmt, "i", $position_id);
    mysqli_stmt_execute($checkStmt);
    $checkResult = mysqli_stmt_get_result($checkStmt);
    $checkRow = mysqli_fetch_assoc($checkResult);
    
    if ($checkRow['count'] == 0) {
        $deleteStmt = mysqli_prepare($conn, "DELETE FROM positions WHERE position_id = ?");
        mysqli_stmt_bind_param($deleteStmt, "i", $position_id);
        
        if (mysqli_stmt_execute($deleteStmt)) {
            $message = "Должность удалена";
            
            // Логирование
            $logStmt = mysqli_prepare($conn, "INSERT INTO user_logs (user_id, action, ip_address) VALUES (?, ?, ?)");
            $action = "Удалена должность ID $position_id";
            $ip = $_SERVER['REMOTE_ADDR'];
            mysqli_stmt_bind_param($logStmt, "iss", $_SESSION['user_id'], $action, $ip);
            mysqli_stmt_execute($logStmt);
        } else {
            $error = "Ошибка при удалении должности";
        }
    } else {
        $error = "Невозможно удалить должность - она используется в ставках";
    }
}

// Получаем список должностей
$positions = mysqli_query($conn, "SELECT * FROM positions ORDER BY position_id");
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Управление должностями - Админ-панель</title>
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
            max-width: 1200px;
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
        
        /* Карточка с формой */
        .form-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .form-card h2 {
            margin-bottom: 20px;
            color: #2c3e50;
            font-size: 20px;
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
            transition: all 0.2s;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .checkbox-group input[type="checkbox"] {
            width: 18px;
            height: 18px;
        }
        
        /* Таблица */
        .table-container {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
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
        
        .badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
            background: #e9ecef;
            color: #495057;
        }
        
        .badge-success {
            background: #28a745;
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
            
            .table-container {
                overflow-x: auto;
            }
        }
    </style>
</head>
<body>
    <!-- Навигация -->
    <div class="admin-nav">
        <a href="index.php">📊 Дашборд</a>
        <a href="users.php">👥 Пользователи</a>
        <a href="positions.php" class="active">📋 Должности</a>
        <a href="stores.php">🏪 Магазины</a>
        <a href="rates.php">💰 Часовые ставки</a>
        <a href="../index.php" class="back-link">← На главную</a>
    </div>
    
    <div class="admin-wrapper">
        <!-- Заголовок -->
        <div class="page-header">
            <h1>📋 Управление должностями</h1>
            <button class="btn btn-success" onclick="openAddModal()">➕ Добавить должность</button>
        </div>
        
        <!-- Сообщения -->
        <?php if ($message): ?>
            <div class="message message-success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="message message-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <!-- Таблица должностей -->
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Название должности</th>
                        <th>Фиксированный оклад</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($positions) > 0): ?>
                        <?php while ($position = mysqli_fetch_assoc($positions)): ?>
                        <tr>
                            <td>#<?= $position['position_id'] ?></td>
                            <td><strong><?= htmlspecialchars($position['position_name']) ?></strong></td>
                            <td>
                                <?php if ($position['has_fixed_salary']): ?>
                                    <span class="badge badge-success">Да</span>
                                <?php else: ?>
                                    <span class="badge">Нет</span>
                                <?php endif; ?>
                            </td>
                            <td class="actions">
                                <button class="btn btn-primary btn-sm" onclick="openEditModal(<?= $position['position_id'] ?>, '<?= htmlspecialchars(addslashes($position['position_name'])) ?>', <?= $position['has_fixed_salary'] ?>)">✏️ Ред.</button>
                                <a href="?delete=<?= $position['position_id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Вы уверены?')">🗑️ Удалить</a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" style="text-align: center; padding: 50px;">
                                <h3 style="color: #95a5a6;">😕 Нет должностей</h3>
                                <p>Добавьте первую должность</p>
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
                <h3>➕ Добавить должность</h3>
                <span class="modal-close" onclick="closeAddModal()">&times;</span>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Название должности *</label>
                        <input type="text" name="position_name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <div class="checkbox-group">
                            <input type="checkbox" name="has_fixed_salary" id="add_has_fixed_salary">
                            <label for="add_has_fixed_salary">Фиксированный оклад</label>
                        </div>
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
                <h3>✏️ Редактировать должность</h3>
                <span class="modal-close" onclick="closeEditModal()">&times;</span>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="position_id" id="edit_position_id">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Название должности *</label>
                        <input type="text" name="position_name" id="edit_position_name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <div class="checkbox-group">
                            <input type="checkbox" name="has_fixed_salary" id="edit_has_fixed_salary">
                            <label for="edit_has_fixed_salary">Фиксированный оклад</label>
                        </div>
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
        
        function openEditModal(id, name, hasFixed) {
            document.getElementById('edit_position_id').value = id;
            document.getElementById('edit_position_name').value = name;
            document.getElementById('edit_has_fixed_salary').checked = hasFixed == 1;
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