<!-- ОКЛАДНАЯ ЧАСТЬ (обязательные поля) -->
<div class="form-section salary-section">
    <div class="section-title">
         Окладная часть (обязательно)
    </div>
    
    <div class="input-group">
        <div class="input-section">
            <label>Номер магазина *</label>
            <input type="text" id="storeNumber" class="input-field">
            <div id="storeNumberError" class="error-message"></div>
        </div>
        <div class="input-section" id="hoursBlock">
            <label>Фактически отработанные часы *</label>
            <input type="number" id="workedHours" class="input-field" min="1" max="300">
            <div id="workedHoursError" class="error-message"></div>
        </div>
    </div>

    <div class="input-section">
        <label>Должность *</label>
        <select id="itemSelect" class="custom-select">
            <option value="">-- Выберите должность --</option>
            <?php while ($p = mysqli_fetch_assoc($positions)): ?>
                <option value="<?= $p['position_id'] ?>">
                    <?= htmlspecialchars($p['position_name']) ?>
                </option>
            <?php endwhile; ?>
        </select>
        <div id="positionError" class="error-message"></div>
    </div>

    <!-- Дополнительные поля, влияющие на оклад (для УО и ЗУО) -->
    <div id="uoFields" style="display:none;">
        <div class="input-section">
            <label>Фактический товарооборот *</label>
            <input type="number" id="actualTurnover" class="input-field" min="0" max="1000000" step="1000">
            <div id="turnoverError" class="error-message"></div>
            <small class="salary-note">Максимум 1 000 000 руб. Влияет на оклад (0.15% от товарооборота)</small>
        </div>
    </div>
    
    <div id="zuoFields" style="display:none;">
        <div class="input-section">
            <label>Фактический товарооборот *</label>
            <input type="number" id="zuoTurnover" class="input-field" min="0" max="1000000" step="1000">
            <div id="zuoTurnoverError" class="error-message"></div>
            <small class="salary-note">Максимум 1 000 000 руб.</small>
        </div>
        
        <div class="input-section">
            <label>Выполнение товарооборота, % *</label>
            <input type="number" id="turnoverPercent" class="input-field" min="0" max="200" step="0.1">
            <div id="turnoverPercentError" class="error-message"></div>
            <small class="salary-note">0.02% от товарооборота при выполнении ≥97%</small>
        </div>
    </div>

    <div class="input-section" id="seasonBlock" style="display:none">
        <label>Период расчёта</label>
        <select id="seasonSelect" class="custom-select">
            <option value="regular">Обычный период</option>
            <option value="resort">Курортный период</option>
        </select>
    </div>
</div>

<!-- ПРЕМИАЛЬНАЯ ЧАСТЬ (для должностей с отдельными премиями) -->
<div class="form-section bonus-section" id="bonusFieldsSection" style="display:none;">
    <div class="section-title">
         Премиальная часть (заполняется при наличии)
    </div>
    
    <!-- Поля для кассира-продавца и продавца гастрономии -->
    <div id="cashierBonusFields" style="display:none;">
        <div class="input-section">
            <label>Товарооборот по кассе, руб</label>
            <input type="number" id="cashTurnover" class="input-field" min="0" max="1000000" step="1000">
            <div id="cashTurnoverError" class="error-message"></div>
            <small class="salary-note">Максимум 1 000 000 руб. Влияет на размер премии (0.1% от суммы)</small>
        </div>

        <div class="input-section">
            <label>Выполнен норматив скорости?</label>
            <select id="speedNormSelect" class="custom-select">
                <option value="no">Нет</option>
                <option value="yes">Да</option>
            </select>
            <small class="salary-note">Обязательное условие для получения премии</small>
        </div>
    </div>
    
    <!-- Поля для бариста -->
    <div id="baristaBonusFields" style="display:none;">
        <div class="input-section">
            <label>Выявление забывчивых покупателей (сумма возмещенного ущерба), руб</label>
            <input type="number" id="forgetfulCustomers" class="input-field" min="0" max="100000" step="100">
            <div id="forgetfulCustomersError" class="error-message"></div>
            <small class="salary-note">Сумма возмещенного ущерба от забывчивых покупателей (максимум 100 000 руб)</small>
        </div>

        <div class="input-section">
            <label>Выявление хищений/злоупотреблений (количество фактов)</label>
            <input type="number" id="theftFacts" class="input-field" min="0" max="100" step="1">
            <div id="theftFactsError" class="error-message"></div>
            <small class="salary-note">Количество выявленных фактов с суммой ≤ 2500 руб. Каждый факт = 500 руб премии</small>
        </div>
    </div>
    
    <!-- Поля для консультанта КСО -->
    <div id="consultantBonusFields" style="display:none;">
        <div class="input-section">
            <label>Процент отмененных чеков *</label>
            <select id="cancelledChecksPercent" class="custom-select">
                <option value="">-- Выберите значение --</option>
                <option value="low">≤ 2,5%</option>
                <option value="high">> 2,5%</option>
            </select>
            <div id="cancelledChecksPercentError" class="error-message"></div>
            <small class="salary-note">Премия начисляется только если процент ≤ 2,5%</small>
        </div>

        <div class="input-section">
            <label>Количество чеков в день *</label>
            <select id="checksPerDay" class="custom-select">
                <option value="">-- Выберите диапазон --</option>
                <option value="1">Меньше 200 чеков</option>
                <option value="2">От 200 до 250 чеков</option>
                <option value="3">От 250 до 300 чеков</option>
                <option value="4">Больше или равно 300 чеков</option>
            </select>
            <div id="checksPerDayError" class="error-message"></div>
            <small class="salary-note">Влияет на процент премии от оклада</small>
        </div>
    </div>
    
    <!-- Поля для Управляющего (индивидуальные задачи) -->
    <div id="uoBonusFields" style="display:none;">
        <div class="bonus-subsection">
            <h4 class="bonus-subsection-title">Сроки годности</h4>
            <div class="input-group">
                <div class="input-section">
                    <label>Процент выполнения задачи</label>
                    <input type="number" id="expiryCompletion" class="input-field" min="0" max="100" step="0.1">
                    <div id="expiryCompletionError" class="error-message"></div>
                </div>
                <div class="input-section">
                    <label>Процент нарушений/ошибок</label>
                    <input type="number" id="expiryErrors" class="input-field" min="0" max="100" step="0.1">
                    <div id="expiryErrorsError" class="error-message"></div>
                </div>
            </div>
            <small class="salary-note">Премия 1% от оклада если выполнение >96% и ошибок <5%</small>
        </div>
        
        <div class="bonus-subsection">
            <h4 class="bonus-subsection-title">Проверка товаров на полке</h4>
            <div class="input-group">
                <div class="input-section">
                    <label>Процент выполнения задачи</label>
                    <input type="number" id="shelfCompletion" class="input-field" min="0" max="100" step="0.1">
                    <div id="shelfCompletionError" class="error-message"></div>
                </div>
                <div class="input-section">
                    <label>Процент нарушений/ошибок</label>
                    <input type="number" id="shelfErrors" class="input-field" min="0" max="100" step="0.1">
                    <div id="shelfErrorsError" class="error-message"></div>
                </div>
            </div>
            <small class="salary-note">Премия 1% от оклада если выполнение >96% и ошибок <5%</small>
        </div>
    </div>
    
    <!-- Поля для Заместителя управляющего (выполнение плана по группам) -->
    <div id="zuoBonusFields" style="display:none;">
        <div class="bonus-subsection">
            <h4 class="bonus-subsection-title">Укажите выполнение плана товарооборота по АЛ по группам:</h4>
            
            <div class="input-section">
                <label>Молочные продукты, %</label>
                <input type="number" id="milkProducts" class="input-field" min="0" max="100" step="0.1">
                <div id="milkProductsError" class="error-message"></div>
            </div>
            
            <div class="input-section">
                <label>Мясная гастрономия, %</label>
                <input type="number" id="meatProducts" class="input-field" min="0" max="100" step="0.1">
                <div id="meatProductsError" class="error-message"></div>
            </div>
            
            <div class="input-section">
                <label>Остальные группы, %</label>
                <input type="number" id="otherProducts" class="input-field" min="0" max="100" step="0.1">
                <div id="otherProductsError" class="error-message"></div>
            </div>
            
            <small class="salary-note">Премия 10% от оклада если молочные ≥95%, мясная ≥95% и остальные ≥90%</small>
        </div>
    </div>
</div>