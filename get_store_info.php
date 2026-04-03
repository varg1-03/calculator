<?php
require 'db.php';

$store_number = intval($_GET['store_number'] ?? 0);

if (!$store_number) {
    echo json_encode(['success' => false, 'error' => 'Не указан номер магазина']);
    exit;
}

$stmt = mysqli_prepare($conn, "SELECT store_id, store_number, has_resort_rate, is_high_turnover FROM stores WHERE store_number = ?");
mysqli_stmt_bind_param($stmt, "i", $store_number);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$store = mysqli_fetch_assoc($result);

if (!$store) {
    echo json_encode(['success' => false, 'error' => 'Магазин не найден']);
    exit;
}

echo json_encode([
    'success' => true,
    'store' => $store
]);
?>