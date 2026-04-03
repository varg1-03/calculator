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
    
    /* Сброс и базовые настройки */
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }
    
    /* Для изображений и медиа */
    img, video, iframe {
        max-width: 100%;
        height: auto;
    }
    
    /* Основной контейнер */
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
        word-break: break-word;
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
        word-break: break-word;
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
    
    .bonus-subsection .input-group {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 15px;
        margin-bottom: 10px;
    }
    
    /* Large Desktop (>1400px) */
    @media (min-width: 1400px) {
        .main {
            max-width: 1600px;
            padding: 50px;
        }
        
        .input-field, .custom-select {
            padding: 16px 20px;
            font-size: 16px;
        }
    }
    
    /* Tablet Landscape (900px - 1024px) */
    @media (max-width: 1024px) and (min-width: 901px) {
        .main {
            grid-template-columns: 1fr;
            gap: 25px;
            padding: 30px;
        }
        
        .left-column {
            position: static;
        }
        
        .right-column {
            position: static;
            max-height: none;
            overflow-y: visible;
        }
        
        .input-group {
            grid-template-columns: 1fr 1fr;
        }
    }
    
    /* Tablet Portrait (768px - 900px) */
    @media (max-width: 900px) and (min-width: 769px) {
        .main {
            grid-template-columns: 1fr;
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
        
        .input-group {
            grid-template-columns: 1fr;
        }
    }
    
    /* Mobile Large (481px - 768px) */
    @media (max-width: 768px) {
        .main {
            grid-template-columns: 1fr;
            gap: 20px;
            padding: 15px;
        }
        
        .left-column {
            position: static;
            padding: 15px;
            width: 100%;
            margin-bottom: 10px;
        }
        
        .right-column {
            position: static;
            max-height: none;
            overflow-y: visible;
            padding: 0;
            width: 100%;
        }
        
        .results-container {
            width: 100%;
            gap: 12px;
        }
        
        .step {
            padding: 15px;
            margin: 0;
            width: 100%;
        }
        
        .salary-value {
            font-size: 24px;
        }
        
        .total-result {
            padding: 20px;
            margin-top: 5px;
            width: 100%;
        }
        
        .total-value {
            font-size: 28px;
        }
        
        .info-row {
            flex-direction: row;
            justify-content: space-between;
            flex-wrap: wrap;
        }
        
        .info-label {
            width: auto;
        }
        
        .input-group {
            grid-template-columns: 1fr;
            gap: 12px;
        }
        
        .input-field, .custom-select {
            padding: 14px;
            font-size: 16px;
            width: 100%;
        }
        
        .btn, .nav-link, .dropdown-toggle {
            min-height: 44px;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
        }
        
        .placeholder {
            height: auto;
            min-height: 300px;
            padding: 30px 20px;
            width: 100%;
        }
        
        .placeholder-steps {
            flex-direction: column;
            gap: 10px;
            width: 100%;
        }
        
        .placeholder-step {
            width: 100%;
            justify-content: center;
        }
        
        .bonus-subsection .input-group {
            grid-template-columns: 1fr;
            gap: 10px;
        }
    }
    
    /* Mobile Small (<480px) */
    @media (max-width: 480px) {
        .main {
            padding: 10px;
            gap: 15px;
        }
        
        .left-column {
            padding: 12px;
        }
        
        .right-column {
            padding: 0;
        }
        
        .header h1 {
            font-size: 20px;
        }
        
        .header p {
            font-size: 14px;
        }
        
        .form-section {
            padding: 12px;
        }
        
        .section-title {
            font-size: 16px;
            margin-bottom: 12px;
        }
        
        .input-field, .custom-select {
            padding: 12px;
            font-size: 15px;
        }
        
        .step {
            padding: 12px;
        }
        
        .salary-value {
            font-size: 22px;
        }
        
        .total-value {
            font-size: 24px;
        }
        
        .total-result {
            padding: 15px;
        }
        
        .total-breakdown {
            font-size: 13px;
            padding: 8px 12px;
        }
        
        .info-row {
            flex-direction: column;
            align-items: flex-start;
            gap: 4px;
        }
        
        .info-label {
            font-size: 14px;
        }
        
        .info-value {
            font-size: 16px;
        }
        
        .rate-display {
            flex-direction: column;
            align-items: flex-start;
            gap: 5px;
        }
        
        .rate-value {
            font-size: 18px;
        }
        
        .placeholder-icon-secondary {
            display: none;
        }
    }
    
    /* Очень маленькие экраны (<360px) */
    @media (max-width: 360px) {
        .main {
            padding: 8px;
        }
        
        .left-column {
            padding: 10px;
        }
        
        .header h1 {
            font-size: 18px;
        }
        
        .salary-value {
            font-size: 20px;
        }
        
        .total-value {
            font-size: 22px;
        }
        
        .badge {
            font-size: 11px;
            padding: 3px 6px;
        }
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
    
    /* Для таблиц на мобильных */
    .table-container {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        width: 100%;
    }
</style>