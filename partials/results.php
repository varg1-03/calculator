<div id="placeholder" class="placeholder placeholder-empty">
    <div class="placeholder-icon">📊</div>
    <h3 class="placeholder-title">Расчёт заработной платы</h3>
    <p class="placeholder-text">
        Введите номер магазина, часы<br>и выберите должность
    </p>
</div>

<div id="calculator" class="calculator">
    <div class="results-container">
        <!-- Информация о магазине, должности и часах -->
        <div class="step info-step">
            <div class="info-row">
                <span class="info-label">Магазин:</span>
                <span class="info-value" id="calcStoreNumber">-</span>
            </div>
            <div class="info-row">
                <span class="info-label">Должность:</span>
                <span class="info-value" id="calcPosition">-</span>
            </div>
            <div class="info-row">
                <span class="info-label">Часы:</span>
                <span class="info-value" id="calcHours">-</span>
            </div>
        </div>

        <!-- Часовая ставка -->
        <div class="step rate-step">
            <div class="rate-display">
                <span class="rate-label">Часовая ставка:</span>
                <span class="rate-value" id="baseRate">0 ₽</span>
            </div>
        </div>

        <!-- Оклад и премия отдельно -->
        <div class="salary-breakdown">
            <div class="step base-salary-block">
                <div class="salary-header">
                    <h4>💰 Окладная часть</h4>
                </div>
                <div class="salary-value" id="salaryBase">0 ₽</div>
                <div class="salary-note">За отработанные часы</div>
            </div>

            <div class="step bonus-block" id="bonusBlock">
                <div class="salary-header">
                    <h4>🎁 Ежемесячная премия</h4>
                </div>
                <div class="salary-value" id="bonusAmount">0 ₽</div>
                <div class="salary-note" id="bonusDescription">-</div>
            </div>
        </div>

        <!-- Итоговая сумма -->
        <div class="total-result">
            <div class="total-label">ИТОГО</div>
            <div class="total-value" id="finalSalary">0 ₽</div>
            <div class="total-breakdown" id="totalBreakdown">
                Оклад: 0 ₽ + Премия: 0 ₽
            </div>
        </div>

        <!-- Скрытый старый блок для обратной совместимости -->
        <div style="display: none;">
            <div class="step">
                <h4>Итого: <span id="hourlyTotal">0 ₽</span></h4>
            </div>
        </div>
    </div>
</div>