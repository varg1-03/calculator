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
    <!-- Дополнительные стили для навигации -->
    <style>
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
        
        .main {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            width: 100%;
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            min-height: 600px;
            margin-top: 20px;
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
    
    <script>
    // Глобальные константы
    
    
    const CURRENT_USER = {
        isAuthenticated: <?php echo $isAuthenticated ? 'true' : 'false'; ?>,
        isAdmin: <?php echo (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin') ? 'true' : 'false'; ?>,
        username: '<?php echo isset($_SESSION['username']) ? addslashes($_SESSION['username']) : ''; ?>'
    };
    </script>
    
    <script src="js/validators.js"></script>
    <script src="js/calculators.js"></script>
    <script src="js/main.js"></script>
    <script src="js/auth.js"></script>
</body>
</html>