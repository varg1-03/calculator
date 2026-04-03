// main.js - основной файл приложения
document.addEventListener('DOMContentLoaded', () => {
    // ============== ИНИЦИАЛИЗАЦИЯ DOM ЭЛЕМЕНТОВ ==============
    
    // Элементы формы (окладная часть)
    const storeInput = document.getElementById('storeNumber');
    const hoursInput = document.getElementById('workedHours');
    const positionSelect = document.getElementById('itemSelect');
    const seasonSelect = document.getElementById('seasonSelect');
    const hoursBlock = document.getElementById('hoursBlock');
    const seasonBlock = document.getElementById('seasonBlock');
    
    // Поля УО (в окладной секции)
    const uoFields = document.getElementById('uoFields');
    const turnoverInput = document.getElementById('actualTurnover');
    const turnoverError = document.getElementById('turnoverError');
    
    // Поля ЗУО (в окладной секции)
    const zuoFields = document.getElementById('zuoFields');
    const zuoTurnoverInput = document.getElementById('zuoTurnover');
    const zuoTurnoverError = document.getElementById('zuoTurnoverError');
    const turnoverPercentInput = document.getElementById('turnoverPercent');
    const turnoverPercentError = document.getElementById('turnoverPercentError');
    
    // Премиальная секция и поля
    const bonusFieldsSection = document.getElementById('bonusFieldsSection');
    
    // Поля кассира-продавца и продавца гастрономии
    const cashierBonusFields = document.getElementById('cashierBonusFields');
    const cashTurnoverInput = document.getElementById('cashTurnover');
    const cashTurnoverError = document.getElementById('cashTurnoverError');
    const speedNormSelect = document.getElementById('speedNormSelect');
    
    // Поля бариста
    const baristaBonusFields = document.getElementById('baristaBonusFields');
    const forgetfulCustomersInput = document.getElementById('forgetfulCustomers');
    const forgetfulCustomersError = document.getElementById('forgetfulCustomersError');
    const theftFactsInput = document.getElementById('theftFacts');
    const theftFactsError = document.getElementById('theftFactsError');
    
    // Поля консультанта КСО
    const consultantBonusFields = document.getElementById('consultantBonusFields');
    const cancelledChecksPercentSelect = document.getElementById('cancelledChecksPercent');
    const cancelledChecksPercentError = document.getElementById('cancelledChecksPercentError');
    const checksPerDaySelect = document.getElementById('checksPerDay');
    const checksPerDayError = document.getElementById('checksPerDayError');
    
    // Поля УО в премиальной секции (индивидуальные задачи)
    const uoBonusFields = document.getElementById('uoBonusFields');
    const expiryCompletion = document.getElementById('expiryCompletion');
    const expiryCompletionError = document.getElementById('expiryCompletionError');
    const expiryErrors = document.getElementById('expiryErrors');
    const expiryErrorsError = document.getElementById('expiryErrorsError');
    const shelfCompletion = document.getElementById('shelfCompletion');
    const shelfCompletionError = document.getElementById('shelfCompletionError');
    const shelfErrors = document.getElementById('shelfErrors');
    const shelfErrorsError = document.getElementById('shelfErrorsError');
    
    // Поля ЗУО в премиальной секции (выполнение плана по группам)
    const zuoBonusFields = document.getElementById('zuoBonusFields');
    const milkProducts = document.getElementById('milkProducts');
    const milkProductsError = document.getElementById('milkProductsError');
    const meatProducts = document.getElementById('meatProducts');
    const meatProductsError = document.getElementById('meatProductsError');
    const otherProducts = document.getElementById('otherProducts');
    const otherProductsError = document.getElementById('otherProductsError');
    
    // Элементы для ошибок
    const storeError = document.getElementById('storeNumberError');
    const hoursError = document.getElementById('workedHoursError');
    const positionError = document.getElementById('positionError');
    
    // Элементы для отображения результатов
    const placeholder = document.getElementById('placeholder');
    const calculator = document.getElementById('calculator');
    const bonusBlock = document.getElementById('bonusBlock');
    const bonusDescription = document.getElementById('bonusDescription');
    
    const calcStoreNumber = document.getElementById('calcStoreNumber');
    const calcPosition = document.getElementById('calcPosition');
    const calcHours = document.getElementById('calcHours');
    const baseRate = document.getElementById('baseRate');
    const salaryBase = document.getElementById('salaryBase');
    const bonusAmount = document.getElementById('bonusAmount');
    const finalSalary = document.getElementById('finalSalary');
    const totalBreakdown = document.getElementById('totalBreakdown');
    
    // Старые элементы для обратной совместимости
    const hourlyTotal = document.getElementById('hourlyTotal');

    // ============== ГЛОБАЛЬНЫЕ ПЕРЕМЕННЫЕ ==============
    
    // Храним информацию о магазине (получаемую с сервера)
    let currentStoreInfo = null;

    // ============== ВСПОМОГАТЕЛЬНЫЕ ФУНКЦИИ ==============
    
    function formatCurrency(amount) {
        return new Intl.NumberFormat('ru-RU', {
            style: 'currency',
            currency: 'RUB',
            minimumFractionDigits: 0,
            maximumFractionDigits: 0
        }).format(amount).replace(',00', '');
    }

    // ============== ФУНКЦИИ ДЛЯ РАБОТЫ С ОШИБКАМИ ==============
    
    function setError(input, errorDiv, message) {
        if (!input || !errorDiv) return;
        input.classList.add('input-error');
        errorDiv.textContent = message;
    }

    function clearError(input, errorDiv) {
        if (!input || !errorDiv) return;
        input.classList.remove('input-error');
        errorDiv.textContent = '';
    }

    function clearAllErrors() {
        clearError(storeInput, storeError);
        clearError(hoursInput, hoursError);
        clearError(positionSelect, positionError);
        clearError(turnoverInput, turnoverError);
        clearError(zuoTurnoverInput, zuoTurnoverError);
        clearError(turnoverPercentInput, turnoverPercentError);
        clearError(cashTurnoverInput, cashTurnoverError);
        clearError(forgetfulCustomersInput, forgetfulCustomersError);
        clearError(theftFactsInput, theftFactsError);
        clearError(cancelledChecksPercentSelect, cancelledChecksPercentError);
        clearError(checksPerDaySelect, checksPerDayError);
        
        // Ошибки для полей УО
        clearError(expiryCompletion, expiryCompletionError);
        clearError(expiryErrors, expiryErrorsError);
        clearError(shelfCompletion, shelfCompletionError);
        clearError(shelfErrors, shelfErrorsError);
        
        // Ошибки для полей ЗУО
        clearError(milkProducts, milkProductsError);
        clearError(meatProducts, meatProductsError);
        clearError(otherProducts, otherProductsError);
    }

    // ============== ФУНКЦИИ ВАЛИДАЦИИ ==============
    
    async function validateStore() {
        clearError(storeInput, storeError);
        
        if (!storeInput.value.trim()) {
            setError(storeInput, storeError, 'Введите номер магазина');
            return false;
        }

        try {
            const response = await fetch('get_store_info.php?store_number=' + encodeURIComponent(storeInput.value));
            const data = await response.json();
            
            if (!data.success) {
                setError(storeInput, storeError, 'Магазин не найден');
                currentStoreInfo = null;
                return false;
            }
            
            currentStoreInfo = data.store;
            
            seasonBlock.style.display = data.store.has_resort_rate == 1 ? 'block' : 'none';
            if (!data.store.has_resort_rate) {
                seasonSelect.value = 'regular';
            }
            
            return true;
        } catch (error) {
            console.error('Ошибка при проверке магазина:', error);
            setError(storeInput, storeError, 'Ошибка проверки магазина');
            currentStoreInfo = null;
            return false;
        }
    }

    function validateHours() {
        clearError(hoursInput, hoursError);
        
        if (hoursBlock.style.display === 'none') return true;
        
        const value = hoursInput.value.trim();
        if (!value) {
            setError(hoursInput, hoursError, 'Введите количество часов');
            return false;
        }
        
        const hours = Number(value);
        if (!Number.isFinite(hours)) {
            setError(hoursInput, hoursError, 'Введите число');
            return false;
        }
        
        if (hours < 1 || hours > 300) {
            setError(hoursInput, hoursError, 'Введите значение от 1 до 300 часов');
            return false;
        }
        
        return true;
    }

    function validatePosition() {
        clearError(positionSelect, positionError);
        
        if (!positionSelect.value) {
            setError(positionSelect, positionError, 'Выберите должность');
            return false;
        }
        
        return true;
    }

    function validateFieldsForPosition(positionId) {
        let isValid = true;
        
        switch(positionId) {
            case POSITION_IDS.UO:
                // Поля УО в окладной части (товарооборот) - обязательны
                const uoValue = turnoverInput ? turnoverInput.value.trim() : '';
                if (!uoValue) {
                    setError(turnoverInput, turnoverError, 'Введите фактический товарооборот');
                    isValid = false;
                } else {
                    const uo = Number(uoValue);
                    if (!Number.isFinite(uo)) {
                        setError(turnoverInput, turnoverError, 'Введите число');
                        isValid = false;
                    } else if (uo < 0) {
                        setError(turnoverInput, turnoverError, 'Товарооборот не может быть отрицательным');
                        isValid = false;
                    } else if (uo > 1000000) {
                        setError(turnoverInput, turnoverError, 'Максимальное значение - 1 000 000 руб');
                        isValid = false;
                    } else {
                        clearError(turnoverInput, turnoverError);
                    }
                }
                
                // Поля премиальной части для УО - необязательные, проверяем только если заполнены
                if (expiryCompletion && expiryCompletion.value.trim()) {
                    const val = Number(expiryCompletion.value);
                    if (!Number.isFinite(val)) {
                        setError(expiryCompletion, expiryCompletionError, 'Введите число');
                        isValid = false;
                    } else if (val < 0 || val > 100) {
                        setError(expiryCompletion, expiryCompletionError, 'Допустимо от 0 до 100%');
                        isValid = false;
                    } else {
                        clearError(expiryCompletion, expiryCompletionError);
                    }
                }
                
                if (expiryErrors && expiryErrors.value.trim()) {
                    const val = Number(expiryErrors.value);
                    if (!Number.isFinite(val)) {
                        setError(expiryErrors, expiryErrorsError, 'Введите число');
                        isValid = false;
                    } else if (val < 0 || val > 100) {
                        setError(expiryErrors, expiryErrorsError, 'Допустимо от 0 до 100%');
                        isValid = false;
                    } else {
                        clearError(expiryErrors, expiryErrorsError);
                    }
                }
                
                if (shelfCompletion && shelfCompletion.value.trim()) {
                    const val = Number(shelfCompletion.value);
                    if (!Number.isFinite(val)) {
                        setError(shelfCompletion, shelfCompletionError, 'Введите число');
                        isValid = false;
                    } else if (val < 0 || val > 100) {
                        setError(shelfCompletion, shelfCompletionError, 'Допустимо от 0 до 100%');
                        isValid = false;
                    } else {
                        clearError(shelfCompletion, shelfCompletionError);
                    }
                }
                
                if (shelfErrors && shelfErrors.value.trim()) {
                    const val = Number(shelfErrors.value);
                    if (!Number.isFinite(val)) {
                        setError(shelfErrors, shelfErrorsError, 'Введите число');
                        isValid = false;
                    } else if (val < 0 || val > 100) {
                        setError(shelfErrors, shelfErrorsError, 'Допустимо от 0 до 100%');
                        isValid = false;
                    } else {
                        clearError(shelfErrors, shelfErrorsError);
                    }
                }
                break;
                
            case POSITION_IDS.ZUO:
                // Поля ЗУО в окладной части (товарооборот и процент) - обязательны
                const zuoValue = zuoTurnoverInput ? zuoTurnoverInput.value.trim() : '';
                if (!zuoValue) {
                    setError(zuoTurnoverInput, zuoTurnoverError, 'Введите фактический товарооборот');
                    isValid = false;
                } else {
                    const zuo = Number(zuoValue);
                    if (!Number.isFinite(zuo)) {
                        setError(zuoTurnoverInput, zuoTurnoverError, 'Введите число');
                        isValid = false;
                    } else if (zuo < 0) {
                        setError(zuoTurnoverInput, zuoTurnoverError, 'Товарооборот не может быть отрицательным');
                        isValid = false;
                    } else if (zuo > 1000000) {
                        setError(zuoTurnoverInput, zuoTurnoverError, 'Максимальное значение - 1 000 000 руб');
                        isValid = false;
                    } else {
                        clearError(zuoTurnoverInput, zuoTurnoverError);
                    }
                }
                
                const percentValue = turnoverPercentInput ? turnoverPercentInput.value.trim() : '';
                if (!percentValue) {
                    setError(turnoverPercentInput, turnoverPercentError, 'Введите процент выполнения');
                    isValid = false;
                } else {
                    const percent = Number(percentValue);
                    if (!Number.isFinite(percent)) {
                        setError(turnoverPercentInput, turnoverPercentError, 'Введите число');
                        isValid = false;
                    } else if (percent < 0 || percent > 200) {
                        setError(turnoverPercentInput, turnoverPercentError, 'Допустимо от 0 до 200%');
                        isValid = false;
                    } else {
                        clearError(turnoverPercentInput, turnoverPercentError);
                    }
                }
                
                // Поля премиальной части для ЗУО - необязательные, проверяем только если заполнены
                if (milkProducts && milkProducts.value.trim()) {
                    const val = Number(milkProducts.value);
                    if (!Number.isFinite(val)) {
                        setError(milkProducts, milkProductsError, 'Введите число');
                        isValid = false;
                    } else if (val < 0 || val > 100) {
                        setError(milkProducts, milkProductsError, 'Допустимо от 0 до 100%');
                        isValid = false;
                    } else {
                        clearError(milkProducts, milkProductsError);
                    }
                }
                
                if (meatProducts && meatProducts.value.trim()) {
                    const val = Number(meatProducts.value);
                    if (!Number.isFinite(val)) {
                        setError(meatProducts, meatProductsError, 'Введите число');
                        isValid = false;
                    } else if (val < 0 || val > 100) {
                        setError(meatProducts, meatProductsError, 'Допустимо от 0 до 100%');
                        isValid = false;
                    } else {
                        clearError(meatProducts, meatProductsError);
                    }
                }
                
                if (otherProducts && otherProducts.value.trim()) {
                    const val = Number(otherProducts.value);
                    if (!Number.isFinite(val)) {
                        setError(otherProducts, otherProductsError, 'Введите число');
                        isValid = false;
                    } else if (val < 0 || val > 100) {
                        setError(otherProducts, otherProductsError, 'Допустимо от 0 до 100%');
                        isValid = false;
                    } else {
                        clearError(otherProducts, otherProductsError);
                    }
                }
                break;
                
            case POSITION_IDS.CASHIER:
            case POSITION_IDS.GASTRONOMY_SELLER:
                // Поля необязательные, но если заполнены - проверяем
                const cashValue = cashTurnoverInput ? cashTurnoverInput.value.trim() : '';
                if (cashValue) {
                    const cash = Number(cashValue);
                    if (!Number.isFinite(cash)) {
                        setError(cashTurnoverInput, cashTurnoverError, 'Введите число');
                        isValid = false;
                    } else if (cash < 0) {
                        setError(cashTurnoverInput, cashTurnoverError, 'Товарооборот не может быть отрицательным');
                        isValid = false;
                    } else if (cash > 1000000) {
                        setError(cashTurnoverInput, cashTurnoverError, 'Максимальное значение - 1 000 000 руб');
                        isValid = false;
                    } else {
                        clearError(cashTurnoverInput, cashTurnoverError);
                    }
                }
                break;
                
            case POSITION_IDS.BARISTA:
                // Поля бариста необязательные, но если заполнены - проверяем
                const forgetfulValue = forgetfulCustomersInput ? forgetfulCustomersInput.value.trim() : '';
                if (forgetfulValue) {
                    const forgetful = Number(forgetfulValue);
                    if (!Number.isFinite(forgetful)) {
                        setError(forgetfulCustomersInput, forgetfulCustomersError, 'Введите число');
                        isValid = false;
                    } else if (forgetful < 0) {
                        setError(forgetfulCustomersInput, forgetfulCustomersError, 'Сумма не может быть отрицательной');
                        isValid = false;
                    } else if (forgetful > 100000) {
                        setError(forgetfulCustomersInput, forgetfulCustomersError, 'Максимальная сумма - 100 000 руб');
                        isValid = false;
                    } else {
                        clearError(forgetfulCustomersInput, forgetfulCustomersError);
                    }
                }
                
                const theftValue = theftFactsInput ? theftFactsInput.value.trim() : '';
                if (theftValue) {
                    const theft = Number(theftValue);
                    if (!Number.isFinite(theft)) {
                        setError(theftFactsInput, theftFactsError, 'Введите число');
                        isValid = false;
                    } else if (theft < 0) {
                        setError(theftFactsInput, theftFactsError, 'Количество не может быть отрицательным');
                        isValid = false;
                    } else if (theft > 100) {
                        setError(theftFactsInput, theftFactsError, 'Максимальное количество - 100 фактов');
                        isValid = false;
                    } else if (!Number.isInteger(theft)) {
                        setError(theftFactsInput, theftFactsError, 'Введите целое число');
                        isValid = false;
                    } else {
                        clearError(theftFactsInput, theftFactsError);
                    }
                }
                break;
                
            case POSITION_IDS.CONSULTANT:
                // Поля консультанта КСО обязательны
                if (!cancelledChecksPercentSelect || !cancelledChecksPercentSelect.value) {
                    setError(cancelledChecksPercentSelect, cancelledChecksPercentError, 'Выберите процент отмененных чеков');
                    isValid = false;
                } else {
                    clearError(cancelledChecksPercentSelect, cancelledChecksPercentError);
                }
                
                if (!checksPerDaySelect || !checksPerDaySelect.value) {
                    setError(checksPerDaySelect, checksPerDayError, 'Выберите количество чеков в день');
                    isValid = false;
                } else {
                    clearError(checksPerDaySelect, checksPerDayError);
                }
                break;
        }
        
        return isValid;
    }

    // ============== ФУНКЦИИ РАСЧЕТА ==============
    
    function calculateUOExtra() {
        const turnover = turnoverInput ? Number(turnoverInput.value) || 0 : 0;
        return turnover * 0.0015;
    }
    
    function calculateZUOExtra() {
        const turnover = zuoTurnoverInput ? Number(zuoTurnoverInput.value) || 0 : 0;
        const percent = turnoverPercentInput ? Number(turnoverPercentInput.value) || 0 : 0;
        
        if (turnover > 0 && percent >= 97) {
            return turnover * 0.0002;
        }
        return 0;
    }
    
    function calculateCashierBonus() {
        const cashTurnover = cashTurnoverInput ? Number(cashTurnoverInput.value) || 0 : 0;
        const speedNormMet = speedNormSelect ? speedNormSelect.value === 'yes' : false;
        const isHighTurnover = currentStoreInfo && currentStoreInfo.is_high_turnover == 1;
        
        if (cashTurnover > 0 && cashTurnover <= 1000000 && speedNormMet && isHighTurnover) {
            return cashTurnover * 0.001;
        }
        return 0;
    }
    
    function calculateBaristaBonus() {
        const forgetfulAmount = forgetfulCustomersInput ? Number(forgetfulCustomersInput.value) || 0 : 0;
        const theftCount = theftFactsInput ? Number(theftFactsInput.value) || 0 : 0;
        
        const validForgetfulAmount = Math.min(forgetfulAmount, 100000);
        
        let part1 = 0;
        if (validForgetfulAmount >= 1000) {
            if (validForgetfulAmount <= 2000) {
                part1 = validForgetfulAmount * 0.2;
            } else if (validForgetfulAmount <= 5000) {
                part1 = validForgetfulAmount * 0.25;
            } else if (validForgetfulAmount >= 5001) {
                part1 = validForgetfulAmount * 0.3;
            }
        }
        
        const part2 = theftCount * 500;
        
        return part1 + part2;
    }
    
    function calculateConsultantBonus(baseSalary) {
        if (!cancelledChecksPercentSelect || !checksPerDaySelect) return 0;
        
        const cancelledPercent = cancelledChecksPercentSelect.value;
        const checksRange = checksPerDaySelect.value;
        
        if (cancelledPercent !== 'low') {
            return 0;
        }
        
        let bonusPercent = 0;
        switch(checksRange) {
            case '1':
                bonusPercent = 0;
                break;
            case '2':
                bonusPercent = 0.35;
                break;
            case '3':
                bonusPercent = 0.40;
                break;
            case '4':
                bonusPercent = 0.45;
                break;
        }
        
        return baseSalary * bonusPercent;
    }
    
    function calculateUOIndividualTasksBonus(baseSalary) {
        let totalBonus = 0;
        let tasksCompleted = [];
        
        // Задача 1: Сроки годности
        if (expiryCompletion && expiryErrors) {
            const comp1 = Number(expiryCompletion.value) || 0;
            const err1 = Number(expiryErrors.value) || 0;
            
            if (comp1 > 96 && err1 < 5) {
                totalBonus += baseSalary * 0.01;
                tasksCompleted.push('сроки годности');
            }
        }
        
        // Задача 2: Проверка товаров на полке
        if (shelfCompletion && shelfErrors) {
            const comp2 = Number(shelfCompletion.value) || 0;
            const err2 = Number(shelfErrors.value) || 0;
            
            if (comp2 > 96 && err2 < 5) {
                totalBonus += baseSalary * 0.01;
                tasksCompleted.push('проверка товаров');
            }
        }
        
        return { bonus: totalBonus, tasks: tasksCompleted };
    }
    
    /**
     * Расчет премии для ЗУО за выполнение плана по группам
     * Премия 10% от оклада если:
     * - молочные продукты ≥ 95%
     * - мясная гастрономия ≥ 95%
     * - остальные группы ≥ 90%
     */
    function calculateZUOGroupBonus(baseSalary) {
        if (!milkProducts || !meatProducts || !otherProducts) return 0;
        
        const milk = Number(milkProducts.value) || 0;
        const meat = Number(meatProducts.value) || 0;
        const other = Number(otherProducts.value) || 0;
        
        // Проверяем условия
        if (milk >= 95 && meat >= 95 && other >= 90) {
            return baseSalary * 0.1; // 10% от оклада
        }
        
        return 0;
    }

    // ============== ФУНКЦИИ УПРАВЛЕНИЯ ИНТЕРФЕЙСОМ ==============
    
    function updateFieldsVisibility() {
    const positionId = Number(positionSelect.value);
    
    // Скрываем все дополнительные поля
    if (uoFields) uoFields.style.display = 'none';
    if (zuoFields) zuoFields.style.display = 'none';
    if (cashierBonusFields) cashierBonusFields.style.display = 'none';
    if (baristaBonusFields) baristaBonusFields.style.display = 'none';
    if (consultantBonusFields) consultantBonusFields.style.display = 'none';
    if (uoBonusFields) uoBonusFields.style.display = 'none';
    if (zuoBonusFields) zuoBonusFields.style.display = 'none';
    if (bonusFieldsSection) bonusFieldsSection.style.display = 'none';
    
    // Показываем нужные поля в зависимости от должности
    switch(positionId) {
        case POSITION_IDS.UO:
            if (uoFields) uoFields.style.display = 'block';
            if (uoBonusFields) uoBonusFields.style.display = 'block';
            if (bonusFieldsSection) bonusFieldsSection.style.display = 'block';
            break;
        case POSITION_IDS.ZUO:
            if (zuoFields) zuoFields.style.display = 'block';
            if (zuoBonusFields) zuoBonusFields.style.display = 'block';
            if (bonusFieldsSection) bonusFieldsSection.style.display = 'block';
            break;
        case POSITION_IDS.CASHIER:
        case POSITION_IDS.GASTRONOMY_SELLER:
            if (cashierBonusFields) cashierBonusFields.style.display = 'block';
            if (bonusFieldsSection) bonusFieldsSection.style.display = 'block';
            break;
        case POSITION_IDS.BARISTA:
            if (baristaBonusFields) baristaBonusFields.style.display = 'block';
            if (bonusFieldsSection) bonusFieldsSection.style.display = 'block';
            break;
        case POSITION_IDS.CONSULTANT:
            if (consultantBonusFields) consultantBonusFields.style.display = 'block';
            if (bonusFieldsSection) bonusFieldsSection.style.display = 'block';
            break;
    }
    
    // Выравниваем колонки после изменения видимости
    setTimeout(alignColumnsHeight, 50);
}

    // ============== ОСНОВНАЯ ФУНКЦИЯ РАСЧЕТА ==============
    
    async function calculate() {
        clearAllErrors();

        if (!validatePosition()) return;
        if (!await validateStore()) return;
        if (!validateHours()) return;

        const positionId = Number(positionSelect.value);
        
        if (!validateFieldsForPosition(positionId)) return;

        const formData = {
            store_number: storeInput.value,
            hours: hoursBlock.style.display !== 'none' ? hoursInput.value : '0',
            position_id: positionSelect.value,
            season: seasonSelect.value
        };

        try {
            const response = await fetch('calculate_salary.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: new URLSearchParams(formData)
            });
            
            const data = await response.json();
            
            if (!data.success) {
                console.error('Ошибка расчета:', data.message || 'Неизвестная ошибка');
                return;
            }

            placeholder.style.display = 'none';
            calculator.style.display = 'block';
            calculator.classList.add('show');
            
            calcStoreNumber.textContent = storeInput.value;
            calcPosition.textContent = positionSelect.options[positionSelect.selectedIndex].text;
            calcHours.textContent = hoursBlock.style.display === 'none' ? '—' : hoursInput.value;
            
            baseRate.textContent = formatCurrency(data.base_rate);
            
            let baseSalary = Math.round(data.total);
            let extraToSalary = 0;
            let bonus = 0;
            let bonusText = '-';
            
            switch(positionId) {
                case POSITION_IDS.UO:
                    extraToSalary = calculateUOExtra();
                    
                    const tasksResult = calculateUOIndividualTasksBonus(baseSalary);
                    if (tasksResult.bonus > 0) {
                        bonus += tasksResult.bonus;
                        bonusText = `Индивидуальные задачи (${tasksResult.tasks.join(', ')})`;
                    }
                    break;
                    
                case POSITION_IDS.ZUO:
                    extraToSalary = calculateZUOExtra();
                    
                    const groupBonus = calculateZUOGroupBonus(baseSalary);
                    if (groupBonus > 0) {
                        bonus += groupBonus;
                        bonusText = 'За выполнение плана по группам (10% от оклада)';
                    }
                    break;
                    
                case POSITION_IDS.CASHIER:
                case POSITION_IDS.GASTRONOMY_SELLER:
                    bonus = calculateCashierBonus();
                    if (bonus > 0) {
                        bonusText = 'За выполнение норматива скорости в магазине с высоким товарооборотом (0.1% от кассы)';
                    }
                    break;
                    
                case POSITION_IDS.BARISTA:
                    bonus = calculateBaristaBonus();
                    if (bonus > 0) {
                        bonusText = 'За выявление забывчивых покупателей и хищений';
                    }
                    break;
                    
                case POSITION_IDS.CONSULTANT:
                    bonus = calculateConsultantBonus(baseSalary);
                    if (bonus > 0) {
                        const checksRange = checksPerDaySelect ? checksPerDaySelect.value : '';
                        let rangeText = '';
                        switch(checksRange) {
                            case '2': rangeText = '200-250 чеков'; break;
                            case '3': rangeText = '250-300 чеков'; break;
                            case '4': rangeText = 'более 300 чеков'; break;
                        }
                        if (checksRange !== '1' && rangeText) {
                            bonusText = `За количество чеков (${rangeText}) при проценте отмен ≤2,5%`;
                        }
                    }
                    break;
            }
            
            const totalSalary = Math.round(baseSalary + extraToSalary);
            bonus = Math.round(bonus);

            salaryBase.textContent = formatCurrency(totalSalary);
            
            if (bonus > 0) {
                bonusBlock.classList.add('show');
                bonusAmount.textContent = formatCurrency(bonus);
                bonusDescription.textContent = bonusText;
            } else {
                bonusBlock.classList.remove('show');
            }
            
            const total = totalSalary + bonus;
            finalSalary.textContent = formatCurrency(total);
            
            if ((positionId === POSITION_IDS.CASHIER || 
                 positionId === POSITION_IDS.GASTRONOMY_SELLER || 
                 positionId === POSITION_IDS.BARISTA || 
                 positionId === POSITION_IDS.CONSULTANT ||
                 positionId === POSITION_IDS.UO ||
                 positionId === POSITION_IDS.ZUO) && bonus > 0) {
                totalBreakdown.innerHTML = `Оклад: ${formatCurrency(totalSalary)} + Премия: ${formatCurrency(bonus)}`;
            } else {
                totalBreakdown.innerHTML = `Оклад: ${formatCurrency(totalSalary)}`;
            }
            
            hourlyTotal.textContent = totalSalary + ' ₽';
            
        } catch (error) {
            console.error('Ошибка при расчете:', error);
        }
    }

    // ============== ОБРАБОТЧИКИ СОБЫТИЙ ==============
    
    positionSelect.addEventListener('change', () => {
        clearAllErrors();
        updateFieldsVisibility();
        
        if (storeInput.value.trim() && positionSelect.value) {
            calculate();
        }
    });

    storeInput.addEventListener('input', () => {
        clearError(storeInput, storeError);
        if (positionSelect.value) {
            calculate();
        }
    });

    storeInput.addEventListener('blur', () => {
        if (positionSelect.value) {
            calculate();
        } else if (!storeInput.value.trim()) {
            setError(storeInput, storeError, 'Введите номер магазина');
        }
    });

    hoursInput.addEventListener('input', () => {
        clearError(hoursInput, hoursError);
        if (storeInput.value.trim() && positionSelect.value) {
            calculate();
        }
    });

    hoursInput.addEventListener('blur', () => {
        if (storeInput.value.trim() && positionSelect.value) {
            calculate();
        } else if (!hoursInput.value.trim() && hoursBlock.style.display !== 'none') {
            setError(hoursInput, hoursError, 'Введите количество часов');
        }
    });

    seasonSelect.addEventListener('change', () => {
        if (storeInput.value.trim() && positionSelect.value) {
            calculate();
        }
    });

    // Обработчики для полей УО (окладная часть)
    if (turnoverInput) {
        turnoverInput.addEventListener('input', () => {
            clearError(turnoverInput, turnoverError);
            if (storeInput.value.trim() && positionSelect.value) {
                calculate();
            }
        });
    }

    // Обработчики для полей ЗУО (окладная часть)
    if (zuoTurnoverInput) {
        zuoTurnoverInput.addEventListener('input', () => {
            clearError(zuoTurnoverInput, zuoTurnoverError);
            if (storeInput.value.trim() && positionSelect.value) {
                calculate();
            }
        });
    }

    if (turnoverPercentInput) {
        turnoverPercentInput.addEventListener('input', () => {
            clearError(turnoverPercentInput, turnoverPercentError);
            if (storeInput.value.trim() && positionSelect.value) {
                calculate();
            }
        });
    }

    // Обработчики для полей кассира и продавца гастрономии
    if (cashTurnoverInput) {
        cashTurnoverInput.addEventListener('input', () => {
            clearError(cashTurnoverInput, cashTurnoverError);
            if (storeInput.value.trim() && positionSelect.value) {
                calculate();
            }
        });
    }

    if (speedNormSelect) {
        speedNormSelect.addEventListener('change', () => {
            if (storeInput.value.trim() && positionSelect.value) {
                calculate();
            }
        });
    }

    // Обработчики для полей бариста
    if (forgetfulCustomersInput) {
        forgetfulCustomersInput.addEventListener('input', () => {
            clearError(forgetfulCustomersInput, forgetfulCustomersError);
            if (storeInput.value.trim() && positionSelect.value) {
                calculate();
            }
        });
    }

    if (theftFactsInput) {
        theftFactsInput.addEventListener('input', () => {
            clearError(theftFactsInput, theftFactsError);
            if (storeInput.value.trim() && positionSelect.value) {
                calculate();
            }
        });
    }

    // Обработчики для полей консультанта КСО
    if (cancelledChecksPercentSelect) {
        cancelledChecksPercentSelect.addEventListener('change', () => {
            clearError(cancelledChecksPercentSelect, cancelledChecksPercentError);
            if (storeInput.value.trim() && positionSelect.value) {
                calculate();
            }
        });
    }

    if (checksPerDaySelect) {
        checksPerDaySelect.addEventListener('change', () => {
            clearError(checksPerDaySelect, checksPerDayError);
            if (storeInput.value.trim() && positionSelect.value) {
                calculate();
            }
        });
    }

    // Обработчики для полей УО в премиальной секции
    if (expiryCompletion) {
        expiryCompletion.addEventListener('input', () => {
            clearError(expiryCompletion, expiryCompletionError);
            if (storeInput.value.trim() && positionSelect.value && Number(positionSelect.value) === POSITION_IDS.UO) {
                calculate();
            }
        });
    }

    if (expiryErrors) {
        expiryErrors.addEventListener('input', () => {
            clearError(expiryErrors, expiryErrorsError);
            if (storeInput.value.trim() && positionSelect.value && Number(positionSelect.value) === POSITION_IDS.UO) {
                calculate();
            }
        });
    }

    if (shelfCompletion) {
        shelfCompletion.addEventListener('input', () => {
            clearError(shelfCompletion, shelfCompletionError);
            if (storeInput.value.trim() && positionSelect.value && Number(positionSelect.value) === POSITION_IDS.UO) {
                calculate();
            }
        });
    }

    if (shelfErrors) {
        shelfErrors.addEventListener('input', () => {
            clearError(shelfErrors, shelfErrorsError);
            if (storeInput.value.trim() && positionSelect.value && Number(positionSelect.value) === POSITION_IDS.UO) {
                calculate();
            }
        });
    }

    // Обработчики для полей ЗУО в премиальной секции
    if (milkProducts) {
        milkProducts.addEventListener('input', () => {
            clearError(milkProducts, milkProductsError);
            if (storeInput.value.trim() && positionSelect.value && Number(positionSelect.value) === POSITION_IDS.ZUO) {
                calculate();
            }
        });
    }

    if (meatProducts) {
        meatProducts.addEventListener('input', () => {
            clearError(meatProducts, meatProductsError);
            if (storeInput.value.trim() && positionSelect.value && Number(positionSelect.value) === POSITION_IDS.ZUO) {
                calculate();
            }
        });
    }

    if (otherProducts) {
        otherProducts.addEventListener('input', () => {
            clearError(otherProducts, otherProductsError);
            if (storeInput.value.trim() && positionSelect.value && Number(positionSelect.value) === POSITION_IDS.ZUO) {
                calculate();
            }
        });
    }

    // Обработчики blur для всех полей
    const blurHandler = (input, positionId) => {
        if (!input) return;
        input.addEventListener('blur', () => {
            if (storeInput.value.trim() && positionSelect.value && Number(positionSelect.value) === positionId) {
                calculate();
            }
        });
    };

    // Применяем blur обработчики
    blurHandler(expiryCompletion, POSITION_IDS.UO);
    blurHandler(expiryErrors, POSITION_IDS.UO);
    blurHandler(shelfCompletion, POSITION_IDS.UO);
    blurHandler(shelfErrors, POSITION_IDS.UO);
    blurHandler(milkProducts, POSITION_IDS.ZUO);
    blurHandler(meatProducts, POSITION_IDS.ZUO);
    blurHandler(otherProducts, POSITION_IDS.ZUO);

    // ============== ИНИЦИАЛИЗАЦИЯ ==============
    
    calculator.style.display = 'none';
    placeholder.style.display = 'block';
    bonusBlock.classList.remove('show');
    // ============== ФУНКЦИЯ ВЫРАВНИВАНИЯ ВЫСОТЫ ==============

    function alignColumnsHeight() {
        const leftColumn = document.querySelector('.left-column');
        const rightColumn = document.querySelector('.right-column');
        const calculator = document.getElementById('calculator');
        const placeholder = document.getElementById('placeholder');
        
        if (!leftColumn || !rightColumn) return;
        
        // Получаем высоту левой колонки
        const leftHeight = leftColumn.offsetHeight;
        
        // Устанавливаем минимальную высоту для правой колонки
        rightColumn.style.minHeight = leftHeight + 'px';
        
        // Если калькулятор видим, растягиваем его содержимое
        if (calculator && calculator.classList.contains('show')) {
            const resultsContainer = document.querySelector('.results-container');
            if (resultsContainer) {
                // Вычисляем доступное пространство
                const containerHeight = rightColumn.clientHeight;
                const infoSteps = document.querySelectorAll('.step:not(.base-salary-block):not(.bonus-block)');
                const salaryBlock = document.querySelector('.base-salary-block');
                const totalBlock = document.querySelector('.total-result');
                
                // Пересчитываем отступы при необходимости
                if (totalBlock) {
                    totalBlock.style.marginTop = 'auto';
                }
            }
        }
    }

    // Добавляем наблюдатель за изменениями в левой колонке
    function observeColumnChanges() {
        const leftColumn = document.querySelector('.left-column');
        if (!leftColumn) return;
        
        // Создаем наблюдатель за изменениями размера
        const resizeObserver = new ResizeObserver(() => {
            alignColumnsHeight();
        });
        
        // Наблюдаем за левой колонкой и всеми её дочерними элементами
        resizeObserver.observe(leftColumn);
        
        // Также наблюдаем за изменениями внутри левой колонки
        const observer = new MutationObserver(() => {
            alignColumnsHeight();
        });
        
        observer.observe(leftColumn, {
            childList: true,
            subtree: true,
            attributes: true,
            attributeFilter: ['style', 'class']
        });
    }

    // Вызываем выравнивание при загрузке и изменении размера окна
    window.addEventListener('load', () => {
        alignColumnsHeight();
        observeColumnChanges();
    });

    window.addEventListener('resize', () => {
        alignColumnsHeight();
    });

    // Добавляем выравнивание после каждого расчета
    const originalCalculate = calculate;
    calculate = async function() {
        await originalCalculate.apply(this, arguments);
        setTimeout(alignColumnsHeight, 50); // Небольшая задержка для завершения рендеринга
    };
});