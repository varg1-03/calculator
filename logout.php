<?php
require_once 'db.php';

// Логируем выход
if (isset($_SESSION['user_id'])) {
    $logStmt = mysqli_prepare($conn, "INSERT INTO user_logs (user_id, action, ip_address) VALUES (?, 'logout', ?)");
    $ip = $_SERVER['REMOTE_ADDR'];
    mysqli_stmt_bind_param($logStmt, "is", $_SESSION['user_id'], $ip);
    mysqli_stmt_execute($logStmt);
}

// Уничтожаем сессию
$_SESSION = array();
session_destroy();

// Удаляем cookie сессии
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

header('Location: index.php');
exit;
?>