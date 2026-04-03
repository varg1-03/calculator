<?php
require 'db.php';

header('Content-Type: application/json');

/* входные данные */
$store_number = trim($_POST['store_number'] ?? '');
$position_id  = intval($_POST['position_id'] ?? 0);
$hours        = floatval($_POST['hours'] ?? 0);
$season       = $_POST['season'] ?? 'regular';

if ($store_number === '' || !$position_id) {
    echo json_encode(['success' => false, 'error' => 'Некорректные данные']);
    exit;
}

/* ─────────────────────────────
   1. МАГАЗИН
───────────────────────────── */
$store = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT
        store_id,
        store_number,
        region_id,
        tariff_group_id,
        is_high_turnover,
        has_resort_rate,
        resort_multiplier
    FROM stores
    WHERE store_number = '" . mysqli_real_escape_string($conn, $store_number) . "'
    LIMIT 1
"));

if (!$store) {
    echo json_encode(['success' => false, 'error' => 'Магазин не найден']);
    exit;
}

/* ─────────────────────────────
   2. ДОЛЖНОСТЬ
───────────────────────────── */
$position = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT
        position_name,
        has_fixed_salary
    FROM positions
    WHERE position_id = $position_id
    LIMIT 1
"));

if (!$position) {
    echo json_encode(['success' => false, 'error' => 'Должность не найдена']);
    exit;
}

/* ─────────────────────────────
   3. КУРОРТНЫЙ КОЭФФИЦИЕНТ
───────────────────────────── */
$resortMultiplier = 1.00;

if (
    $season === 'resort'
    && (int)$store['has_resort_rate'] === 1
    && (float)$store['resort_multiplier'] > 0
) {
    $resortMultiplier = (float)$store['resort_multiplier'];
}

/* ─────────────────────────────
   4. РЕЗУЛЬТАТ
───────────────────────────── */
$result = [
    'success'        => true,
    'salary_type'    => '',
    'base_rate'      => 0,
    'final_rate'     => 0,
    'multiplier'     => $resortMultiplier,
    'total'          => 0,
    'position_name'  => $position['position_name']
];

/* ─────────────────────────────
   5. ФИКСИРОВАННАЯ ЗП
   has_fixed_salary = 1
   is_high_turnover = 1
───────────────────────────── */
if (
    (int)$position['has_fixed_salary'] === 1
    && (int)$store['is_high_turnover'] === 1
) {
    if ($hours <= 0 || $hours > 300) {
        echo json_encode([
            'success' => false,
            'error'   => 'Количество часов должно быть от 1 до 300'
        ]);
        exit;
    }

    $fixed = mysqli_fetch_assoc(mysqli_query($conn, "
        SELECT fixed_salary
        FROM fixed_salaries
        WHERE region_id = {$store['region_id']}
          AND tariff_group_id = {$store['tariff_group_id']}
        LIMIT 1
    "));

    if (!$fixed) {
        echo json_encode([
            'success' => false,
            'error'   => 'Фиксированный оклад не найден'
        ]);
        exit;
    }

    $fixedBase      = (float)$fixed['fixed_salary'];
    $effectiveFixed = $fixedBase * $resortMultiplier;
    $hourRate       = $effectiveFixed / 168;
    $total          = round($hourRate * $hours);

    $result['salary_type'] = 'Фиксированная (по часам)';
    $result['base_rate']   = round($fixedBase / 168);
    $result['final_rate']  = $hourRate;
    $result['total']       = $total;

    echo json_encode($result);
    exit;
}

/* ─────────────────────────────
   6. ПОЧАСОВАЯ ОПЛАТА (ОБЫЧНАЯ)
───────────────────────────── */
$rate = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT hourly_rate
    FROM hourly_rates
    WHERE
        region_id = {$store['region_id']}
        AND tariff_group_id = {$store['tariff_group_id']}
        AND position_id = $position_id
    LIMIT 1
"));

if (!$rate) {
    echo json_encode([
        'success' => false,
        'error'   => 'Почасовая ставка не найдена'
    ]);
    exit;
}

$baseRate  = (float)$rate['hourly_rate'];
$finalRate = $baseRate * $resortMultiplier;
$total     = $finalRate * $hours;

$result['salary_type'] = 'Почасовая оплата';
$result['base_rate']   = round($baseRate, 2);
$result['final_rate']  = round($finalRate, 2);
$result['total']       = round($total, 2);

echo json_encode($result);
exit;

