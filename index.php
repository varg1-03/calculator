<?php
require 'db.php';

// Проверяем, авторизован ли пользователь
$isAuthenticated = isset($_SESSION['user_id']);

$positions = mysqli_query($conn, "SELECT * FROM positions ORDER BY position_name");
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <?php include 'partials/header.php'; ?>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes">
    <style>
        /* Только основные стили для обертки, без дублирования */
        body {
            margin: 0;
            padding: 20px;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .page-wrapper {
            flex: 1;
            display: flex;
            flex-direction: column;
            max-width: 1400px;
            margin: 0 auto;
            width: 100%;
        }
        
        /* Мобильная адаптация для body */
        @media (max-width: 768px) {
            body {
                padding: 10px;
            }
        }
        
        @media (max-width: 480px) {
            body {
                padding: 5px;
            }
        }
    </style>
</head>
<body>
    <div class="page-wrapper">
        <?php include 'partials/navbar.php'; ?>
        
        <div class="main">
            <div class="left-column">
                <?php include 'partials/salary-form.php'; ?>
            </div>
            <div class="right-column">
                <?php include 'partials/results.php'; ?>
            </div>
        </div>
    </div>
    
    <?php include 'partials/footer.php'; ?>
</body>
</html>