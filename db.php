<?php
$host = 'localhost';
$dbname = 'calculator';
$username = 'root';
$password = '';

$conn = mysqli_connect($host, $username, $password, $dbname);

if (!$conn) {
    die("Ошибка подключения к БД: " . mysqli_connect_error());
}

mysqli_set_charset($conn, "utf8");

// Запуск сессии для авторизации
session_start();
?>