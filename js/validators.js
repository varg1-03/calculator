// Функции для работы с ошибками
function setError(input, errorDiv, message) {
    input.classList.add('input-error');
    errorDiv.textContent = message;
}

function clearError(input, errorDiv) {
    input.classList.remove('input-error');
    errorDiv.textContent = '';
}

function clearAllErrors() {
    clearError(storeInput, storeError);
    clearError(hoursInput, hoursError);
    clearError(positionSelect, positionError);
    clearError(cashTurnoverInput, cashTurnoverError);
    clearError(turnoverInput, turnoverError);
    clearError(zuoTurnoverInput, zuoTurnoverError);
    clearError(turnoverPercentInput, turnoverPercentError);
}

// Валидация магазина
async function validateStore() {
    clearError(storeInput, storeError);
    
    if (!storeInput.value.trim()) {
        setError(storeInput, storeError, 'Введите номер магазина');
        return false;
    }

    try {
        const r = await fetch('get_store_info.php?store_number=' + encodeURIComponent(storeInput.value));
        const d = await r.json();
        
        if (!d.success) {
            setError(storeInput, storeError, 'Магазин не найден');
            currentStoreInfo = null;
            return false;
        }
        
        currentStoreInfo = d.store;
        
        seasonBlock.style.display = d.store.has_resort_rate == 1 ? 'block' : 'none';
        if (!d.store.has_resort_rate) {
            seasonSelect.value = 'regular';
        }
        
        return true;
    } catch (error) {
        setError(storeInput, storeError, 'Ошибка проверки магазина');
        currentStoreInfo = null;
        return false;
    }
}

// Валидация часов
function validateHours() {
    clearError(hoursInput, hoursError);
    
    if (hoursBlock.style.display === 'none') return true;
    
    const value = hoursInput.value.trim();
    if (!value) {
        setError(hoursInput, hoursError, 'Введите количество часов');
        return false;
    }
    
    const h = Number(value);
    if (!Number.isFinite(h)) {
        setError(hoursInput, hoursError, 'Введите число');
        return false;
    }
    
    if (h < 1 || h > 300) {
        setError(hoursInput, hoursError, 'Введите значение от 1 до 300 часов');
        return false;
    }
    
    return true;
}

// Валидация должности
function validatePosition() {
    clearError(positionSelect, positionError);
    
    if (!positionSelect.value) {
        setError(positionSelect, positionError, 'Выберите должность');
        return false;
    }
    
    return true;
}

// Валидация премиальных полей
function validateBonusFields(positionId) {
    let isValid = true;
    
    switch(positionId) {
        case POSITION_IDS.CASHIER:
            const cashValue = cashTurnoverInput.value.trim();
            if (cashValue && cashValue !== '') {
                const cash = Number(cashValue);
                if (!Number.isFinite(cash)) {
                    setError(cashTurnoverInput, cashTurnoverError, 'Введите число');
                    isValid = false;
                } else if (cash < 0) {
                    setError(cashTurnoverInput, cashTurnoverError, 'Товарооборот не может быть отрицательным');
                    isValid = false;
                } else {
                    clearError(cashTurnoverInput, cashTurnoverError);
                }
            }
            break;
            
        case POSITION_IDS.UO:
            const uoValue = turnoverInput.value.trim();
            if (uoValue && uoValue !== '') {
                const uo = Number(uoValue);
                if (!Number.isFinite(uo)) {
                    setError(turnoverInput, turnoverError, 'Введите число');
                    isValid = false;
                } else if (uo < 0) {
                    setError(turnoverInput, turnoverError, 'Товарооборот не может быть отрицательным');
                    isValid = false;
                } else {
                    clearError(turnoverInput, turnoverError);
                }
            }
            break;
            
        case POSITION_IDS.ZUO:
            const zuoValue = zuoTurnoverInput.value.trim();
            if (zuoValue && zuoValue !== '') {
                const zuo = Number(zuoValue);
                if (!Number.isFinite(zuo)) {
                    setError(zuoTurnoverInput, zuoTurnoverError, 'Введите число');
                    isValid = false;
                } else if (zuo < 0) {
                    setError(zuoTurnoverInput, zuoTurnoverError, 'Товарооборот не может быть отрицательным');
                    isValid = false;
                } else {
                    clearError(zuoTurnoverInput, zuoTurnoverError);
                }
            }
            
            const percentValue = turnoverPercentInput.value.trim();
            if (percentValue && percentValue !== '') {
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
            break;
    }
    
    return isValid;
}