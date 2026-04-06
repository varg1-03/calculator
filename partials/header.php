<meta charset="UTF-8">
<title>Калькулятор заработной платы</title>
<link rel="stylesheet" href="style.css">
<style>
    .input-error {
        border: 2px solid #e74c3c !important;
        background-color: #fff6f6;
    }
    .error-message {
        color: #e74c3c;
        font-size: 13px;
        margin-top: 4px;
    }
    .show {
        display: block !important;
    }
    .calculator {
        display: none;
    }
    
    /* ===== ЛАВАНДОВО-ФИАЛКОВАЯ ГАММА ===== */
    
    /* Основной контейнер - десктоп версия */
    .main {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 30px;
        width: 100%;
        max-width: 1400px;
        background: white;
        border-radius: 20px;
        padding: 40px;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        min-height: 600px;
        position: relative;
    }
    
    /* Левая колонка */
    .left-column {
        background: rgba(245, 240, 255, 0.3);
        backdrop-filter: blur(2px);
        border-radius: 20px;
        padding: 25px;
        height: fit-content;
        position: sticky;
        top: 20px;
    }
    
    /* Правая колонка */
    .right-column {
        display: flex;
        flex-direction: column;
        height: fit-content;
        max-height: calc(100vh - 100px);
        overflow-y: auto;
        position: sticky;
        top: 20px;
        padding-right: 5px;
    }
    
    /* ===== МОБИЛЬНАЯ АДАПТАЦИЯ ===== */
    
    /* Планшеты (768px - 1024px) */
    @media (max-width: 1024px) {
        .main {
            grid-template-columns: 1fr;
            gap: 20px;
            padding: 25px;
        }
        
        .left-column {
            position: static;
        }
        
        .right-column {
            position: static;
            max-height: none;
            overflow-y: visible;
        }
        
        .placeholder {
            height: auto;
            min-height: 300px;
        }
        
        .input-group {
            grid-template-columns: 1fr;
            gap: 15px;
        }
    }
    
    /* Телефоны (до 768px) */
    @media (max-width: 768px) {
        /* Общие настройки */
        body {
            padding: 10px;
        }
        
        .page-wrapper {
            padding: 0;
        }
        
        .main {
            padding: 15px;
            border-radius: 15px;
            margin-top: 10px;
            gap: 15px;
        }
        
        /* Левая колонка - поля ввода сверху */
        .left-column {
            padding: 15px;
            order: 1;
            margin-bottom: 0;
        }
        
        /* Правая колонка - результаты снизу */
        .right-column {
            order: 2;
            margin-top: 0;
        }
        
        /* Заголовок */
        .header {
            margin-bottom: 20px;
        }
        
        .header h1 {
            font-size: 22px;
        }
        
        .header p {
            font-size: 13px;
        }
        
        /* Секции формы */
        .form-section {
            padding: 15px;
            margin-bottom: 20px;
        }
        
        .section-title {
            font-size: 14px;
            margin-bottom: 15px;
        }
        
        .section-icon {
            font-size: 16px;
        }
        
        /* Поля ввода */
        .input-section {
            margin-bottom: 12px;
        }
        
        .input-section label {
            font-size: 13px;
            margin-bottom: 5px;
        }
        
        .input-field, .custom-select {
            padding: 10px 12px;
            font-size: 14px;
        }
        
        .salary-note {
            font-size: 11px;
        }
        
        /* Группировка полей */
        .input-group {
            gap: 12px;
        }
        
        /* Подсекции премий */
        .bonus-subsection {
            padding: 12px;
        }
        
        .bonus-subsection-title {
            font-size: 14px;
        }
        
        /* Результаты */
        .results-container {
            gap: 12px;
        }
        
        .step {
            padding: 15px;
        }
        
        .info-row {
            padding: 6px 0;
        }
        
        .info-label {
            font-size: 13px;
        }
        
        .info-value {
            font-size: 14px;
        }
        
        .rate-display {
            flex-direction: column;
            gap: 5px;
            text-align: center;
        }
        
        .rate-label {
            font-size: 13px;
        }
        
        .rate-value {
            font-size: 18px;
        }
        
        .salary-header h4 {
            font-size: 14px;
        }
        
        .salary-value {
            font-size: 22px;
            text-align: center;
        }
        
        .salary-note {
            font-size: 11px;
            text-align: center;
        }
        
        .total-result {
            padding: 20px;
        }
        
        .total-label {
            font-size: 14px;
        }
        
        .total-value {
            font-size: 28px;
        }
        
        .total-breakdown {
            font-size: 12px;
            padding: 8px 12px;
        }
        
        /* Плейсхолдер */
        .placeholder {
            padding: 30px 20px;
            min-height: 250px;
        }
        
        .placeholder-icon {
            font-size: 40px;
        }
        
        .placeholder-title {
            font-size: 18px;
        }
        
        .placeholder-text {
            font-size: 13px;
        }
        
        /* Бейджики */
        .badge {
            font-size: 10px;
            padding: 3px 6px;
        }
        
        /* Кнопки */
        .btn {
            padding: 8px 15px;
            font-size: 13px;
        }
        
        .btn-sm {
            padding: 4px 8px;
            font-size: 11px;
        }
    }
    
    /* Очень маленькие телефоны (до 480px) */
    @media (max-width: 480px) {
        body {
            padding: 5px;
        }
        
        .main {
            padding: 10px;
            border-radius: 12px;
        }
        
        .left-column, .right-column {
            padding: 10px;
        }
        
        .header h1 {
            font-size: 20px;
        }
        
        .form-section {
            padding: 12px;
        }
        
        .input-field, .custom-select {
            padding: 8px 10px;
            font-size: 13px;
        }
        
        .salary-value {
            font-size: 20px;
        }
        
        .total-value {
            font-size: 24px;
        }
        
        .total-result {
            padding: 15px;
        }
        
        .placeholder {
            padding: 20px;
        }
        
        .placeholder-icon {
            font-size: 36px;
        }
        
        .placeholder-title {
            font-size: 16px;
        }
        
        .step {
            padding: 12px;
        }
    }
    
    /* Альбомная ориентация на телефонах */
    @media (max-width: 768px) and (orientation: landscape) {
        .main {
            gap: 10px;
        }
        
        .left-column {
            padding: 10px;
        }
        
        .form-section {
            margin-bottom: 10px;
            padding: 10px;
        }
        
        .input-group {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }
    }
    
    /* Стилизация скроллбара */
    .right-column::-webkit-scrollbar {
        width: 6px;
    }
    
    .right-column::-webkit-scrollbar-track {
        background: #f0e6ff;
        border-radius: 10px;
    }
    
    .right-column::-webkit-scrollbar-thumb {
        background: #6b4e8e;
        border-radius: 10px;
    }
    
    .right-column::-webkit-scrollbar-thumb:hover {
        background: #8a5ea0;
    }
    
    /* Контейнер результатов */
    .results-container {
        display: flex;
        flex-direction: column;
        gap: 15px;
        width: 100%;
    }
    
    /* Плейсхолдер */
    .placeholder {
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        text-align: center;
        height: 400px;
        padding: 40px;
        background: linear-gradient(135deg, #f8f0ff, #ffffff);
        border-radius: 20px;
        border: 2px dashed #d9c5f0;
        transition: all 0.3s ease;
        margin: 0;
    }
    
    .placeholder:hover {
        border-color: #b49fd4;
        background: linear-gradient(135deg, #ffffff, #f8f0ff);
    }
    
    .placeholder-icon {
        font-size: 48px;
        margin-bottom: 20px;
        color: #6b4e8e;
        opacity: 0.5;
    }
    
    .placeholder-title {
        font-size: 24px;
        font-weight: 600;
        color: #4a2e5e;
        margin-bottom: 15px;
    }
    
    .placeholder-text {
        font-size: 16px;
        color: #7a5e9c;
        line-height: 1.6;
    }
    
    /* Калькулятор результатов */
    .calculator {
        display: none;
        width: 100%;
    }
    
    .calculator.show {
        display: block;
    }
    
    /* Общие стили для шагов */
    .step {
        background: white;
        border-radius: 16px;
        padding: 20px;
        box-shadow: 0 4px 12px rgba(106, 78, 142, 0.1);
        border: 1px solid #f0e6ff;
        transition: all 0.3s ease;
    }
    
    .step:hover {
        box-shadow: 0 6px 16px rgba(106, 78, 142, 0.15);
    }
    
    /* Информационный шаг */
    .info-step {
        background: linear-gradient(145deg, #ffffff, #faf5ff);
    }
    
    .info-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 8px 0;
        border-bottom: 1px solid #f0e6ff;
    }
    
    .info-row:last-child {
        border-bottom: none;
    }
    
    .info-label {
        color: #6b4e8e;
        font-weight: 500;
        font-size: 15px;
    }
    
    .info-value {
        color: #2c3e50;
        font-weight: 600;
        font-size: 16px;
    }
    
    /* Шаг со ставкой */
    .rate-step {
        background: linear-gradient(145deg, #f5f0ff, #eae3ff);
    }
    
    .rate-display {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .rate-label {
        color: #6b4e8e;
        font-weight: 500;
        font-size: 15px;
    }
    
    .rate-value {
        color: #2c3e50;
        font-weight: 700;
        font-size: 20px;
    }
    
    /* Блоки оклада и премии */
    .salary-breakdown {
        display: flex;
        flex-direction: column;
        gap: 15px;
    }
    
    .base-salary-block {
        background: linear-gradient(145deg, #f5f0ff, #eae3ff);
        border-left: 4px solid #6b4e8e;
    }
    
    .bonus-block {
        background: linear-gradient(145deg, #f6ecff, #f3e0ff);
        border-left: 4px solid #8a5ea0;
        display: none;
    }
    
    .bonus-block.show {
        display: block;
        animation: slideIn 0.3s ease;
    }
    
    .salary-header {
        margin-bottom: 15px;
    }
    
    .salary-header h4 {
        color: #2c3e50;
        font-size: 16px;
        font-weight: 600;
    }
    
    .salary-value {
        font-size: 28px;
        font-weight: bold;
        margin: 10px 0;
    }
    
    .base-salary-block .salary-value {
        color: #6b4e8e;
    }
    
    .bonus-block .salary-value {
        color: #8a5ea0;
    }
    
    .salary-note {
        color: #9a7eb0;
        font-size: 13px;
        line-height: 1.4;
    }
    
    /* Итоговый результат */
    .total-result {
        background: linear-gradient(135deg, #8a6eb0, #6b4e8e);
        color: white;
        border-radius: 16px;
        padding: 25px;
        text-align: center;
        box-shadow: 0 8px 20px rgba(106, 78, 142, 0.3);
        margin-top: 5px;
    }
    
    .total-label {
        font-size: 16px;
        opacity: 0.9;
        margin-bottom: 10px;
    }
    
    .total-value {
        font-size: 36px;
        font-weight: bold;
        margin: 10px 0;
        text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
    }
    
    .total-breakdown {
        font-size: 14px;
        opacity: 0.9;
        background: rgba(255,255,255,0.15);
        padding: 10px 15px;
        border-radius: 30px;
        display: inline-block;
    }
    
    /* ===== СТИЛИ ДЛЯ ЛЕВОЙ ЧАСТИ (ФОРМА) ===== */
    
    /* Заголовок */
    .header {
        text-align: center;
        margin-bottom: 30px;
    }
    
    .header h1 {
        color: #4a2e5e;
        font-size: 28px;
        margin-bottom: 10px;
        position: relative;
        padding-bottom: 15px;
    }
    
    .header h1:after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 50%;
        transform: translateX(-50%);
        width: 80px;
        height: 4px;
        background: linear-gradient(90deg, #6b4e8e, #8a5ea0);
        border-radius: 2px;
    }
    
    .header p {
        color: #6b4e8e;
        font-size: 15px;
        opacity: 0.8;
    }
    
    /* Секции формы */
    .form-section {
        margin-bottom: 25px;
        padding: 20px;
        border-radius: 16px;
        position: relative;
        transition: all 0.3s ease;
        box-shadow: 0 4px 15px rgba(106, 78, 142, 0.05);
    }
    
    .form-section.salary-section {
        background: linear-gradient(145deg, #f5f0ff, #e6e6ff);
        border: 1px solid #d9c5f0;
    }
    
    .form-section.bonus-section {
        background: linear-gradient(145deg, #f3e8ff, #f0d9ff);
        border: 1px solid #d9b5e8;
        display: none;
    }
    
    .form-section.bonus-section.show {
        display: block;
        animation: fadeIn 0.3s ease;
    }
    
    /* Заголовки секций */
    .section-title {
        font-size: 16px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 20px;
        padding-bottom: 10px;
        border-bottom: 2px solid;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .salary-section .section-title {
        color: #6b4e8e;
        border-bottom-color: #c4b5e0;
    }
    
    .bonus-section .section-title {
        color: #8a5ea0;
        border-bottom-color: #d9b5e8;
    }
    
    .section-icon {
        font-size: 18px;
    }
    
    /* Группы полей */
    .input-group {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 15px;
        margin-bottom: 15px;
    }
    
    /* Поля ввода */
    .input-section {
        margin-bottom: 15px;
    }
    
    .input-section label {
        display: block;
        margin-bottom: 8px;
        color: #4a2e5e;
        font-weight: 500;
        font-size: 14px;
    }
    
    .input-field, .custom-select {
        width: 100%;
        padding: 12px 15px;
        font-size: 15px;
        border: 2px solid #e0d4f0;
        border-radius: 10px;
        background: white;
        transition: all 0.2s ease;
    }
    
    .input-field:focus, .custom-select:focus {
        outline: none;
        border-color: #6b4e8e;
        box-shadow: 0 0 0 3px rgba(106, 78, 142, 0.1);
    }
    
    .input-field:hover, .custom-select:hover {
        border-color: #b4a0d9;
    }
    
    /* Кастомный select */
    .custom-select {
        appearance: none;
        cursor: pointer;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24' fill='none' stroke='%236b4e8e' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 12px center;
        background-size: 16px;
    }
    
    /* Подсказки */
    .salary-note {
        font-size: 12px;
        color: #8a5ea0;
        margin-top: 5px;
        padding-left: 10px;
        border-left: 2px solid #d9c5f0;
    }
    
    /* Подсекции для премий */
    .bonus-subsection {
        background: rgba(255, 255, 255, 0.7);
        border-radius: 12px;
        padding: 15px;
        margin-bottom: 15px;
        border-left: 3px solid #8a5ea0;
    }
    
    .bonus-subsection:last-child {
        margin-bottom: 0;
    }
    
    .bonus-subsection-title {
        color: #6b4e8e;
        font-size: 15px;
        font-weight: 600;
        margin-bottom: 12px;
        padding-bottom: 8px;
        border-bottom: 1px solid #d9b5e8;
    }
    
    /* Анимации */
    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    @keyframes slideIn {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
        /* ===== ПРИНУДИТЕЛЬНАЯ МОБИЛЬНАЯ АДАПТАЦИЯ ===== */
    @media (max-width: 768px) {
        /* Принудительно меняем расположение колонок */
        .main {
            display: flex !important;
            flex-direction: column !important;
            padding: 15px !important;
            gap: 15px !important;
        }
        
        .left-column {
            order: 1 !important;
            width: 100% !important;
            padding: 15px !important;
            margin-bottom: 0 !important;
            position: static !important;
        }
        
        .right-column {
            order: 2 !important;
            width: 100% !important;
            margin-top: 0 !important;
            position: static !important;
            max-height: none !important;
            overflow-y: visible !important;
        }
        
        /* Исправляем группу полей - теперь они в столбик */
        .input-group {
            display: flex !important;
            flex-direction: column !important;
            gap: 15px !important;
            margin-bottom: 15px !important;
        }
        
        /* Каждое поле занимает всю ширину */
        .input-group .input-section {
            width: 100% !important;
            margin-bottom: 0 !important;
        }
        
        /* Уменьшаем отступы */
        .form-section {
            padding: 15px !important;
        }
        
        /* Результаты на всю ширину */
        .results-container {
            width: 100% !important;
        }
        
        .step {
            width: 100% !important;
            box-sizing: border-box !important;
        }
        
        /* Информационные строки в столбец */
        .info-row {
            flex-direction: column !important;
            align-items: flex-start !important;
            gap: 5px !important;
        }
        
        .info-label {
            width: 100% !important;
        }
        
        /* Часовая ставка по центру */
        .rate-display {
            flex-direction: column !important;
            text-align: center !important;
            gap: 8px !important;
        }
        
        /* Плейсхолдер */
        .placeholder {
            height: auto !important;
            min-height: 250px !important;
            padding: 30px 20px !important;
        }
        
        /* Уменьшаем размер шрифта для подписей */
        .input-section label {
            font-size: 13px !important;
            margin-bottom: 5px !important;
        }
        
        /* Делаем поля ввода более компактными */
        .input-field, .custom-select {
            padding: 10px 12px !important;
            font-size: 14px !important;
        }
    }
    
    /* Очень маленькие телефоны */
    @media (max-width: 480px) {
        .main {
            padding: 10px !important;
        }
        
        .left-column, .right-column {
            padding: 10px !important;
        }
        
        .form-section {
            padding: 12px !important;
        }
        
        .input-field, .custom-select {
            padding: 8px 10px !important;
            font-size: 13px !important;
        }
        
        .salary-value {
            font-size: 22px !important;
        }
        
        .total-value {
            font-size: 26px !important;
        }
        
        .total-result {
            padding: 15px !important;
        }
        
        /* Уменьшаем отступы между полями */
        .input-group {
            gap: 12px !important;
        }
        
        .input-section {
            margin-bottom: 10px !important;
        }
    }
    
    /* Для экранов где текст "Фактически отработанные часы" может переноситься */
    @media (max-width: 380px) {
        .input-section label {
            font-size: 12px !important;
            white-space: normal !important;
            word-break: break-word !important;
        }
        
        .input-field, .custom-select {
            padding: 8px 10px !important;
            font-size: 12px !important;
        }
    }
</style>