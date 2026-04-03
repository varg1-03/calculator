// Форматирование валюты
function formatCurrency(amount) {
    return new Intl.NumberFormat('ru-RU', {
        style: 'currency',
        currency: 'RUB',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    }).format(amount).replace(',00', '');
}

// Расчет премии для кассира-продавца
function calculateCashierBonus() {
    const cashTurnover = Number(cashTurnoverInput.value) || 0;
    const speedNormMet = speedNormSelect.value === 'yes';
    const isHighTurnover = currentStoreInfo && currentStoreInfo.is_high_turnover == 1;
    
    if (cashTurnover > 0 && speedNormMet && isHighTurnover) {
        return cashTurnover * 0.001;
    }
    
    return 0;
}

// Расчет премии для УО
function calculateUOBonus() {
    const turnover = Number(turnoverInput.value) || 0;
    return turnover * 0.0015;
}

// Расчет премии для ЗУО
function calculateZUOBonus() {
    const turnover = Number(zuoTurnoverInput.value) || 0;
    const percent = Number(turnoverPercentInput.value) || 0;
    
    if (turnover > 0 && percent >= 97) {
        return turnover * 0.0002;
    }
    
    return 0;
}

// Обновление видимости премиальных полей
function updateBonusFieldsVisibility() {
    const positionId = Number(positionSelect.value);
    
    cashierBonusFields.style.display = 'none';
    uoBonusFields.style.display = 'none';
    zuoBonusFields.style.display = 'none';
    bonusFieldsSection.style.display = 'none';
    
    switch(positionId) {
        case POSITION_IDS.CASHIER:
            cashierBonusFields.style.display = 'block';
            bonusFieldsSection.style.display = 'block';
            break;
        case POSITION_IDS.UO:
            uoBonusFields.style.display = 'block';
            bonusFieldsSection.style.display = 'block';
            break;
        case POSITION_IDS.ZUO:
            zuoBonusFields.style.display = 'block';
            bonusFieldsSection.style.display = 'block';
            break;
    }
}