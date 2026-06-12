<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Каталог</title>
</head>
<style>
    /* Стили для слайдера */
    .slider {
        position: relative;
        min-height: 400px; /* Задайте нужную высоту */
    }
    
    .cards {
        display: none; /* Все карточки скрыты по умолчанию */
        width: 100%;
    }
    
    .cards:first-child {
        display: block; /* Показываем первую */
    }
    
    .prev-button,
    .next-button {
        position: absolute;
        top: 50%;
        transform: translateY(-50%);
        cursor: pointer;
        background: rgba(0,0,0,0.5);
        color: white;
        border: none;
        padding: 10px 15px;
        font-size: 20px;
        z-index: 10;
    }
    
    .prev-button {
        left: 10px;
    }
    
    .next-button {
        right: 10px;
    }
    
    /* Чтобы кнопки были над слайдером */
    .card {
        position: relative;
    }
</style>
<body>
    <div class="main">
        <div class="wrapper">
            <div class="card">
                <div class = "slider">
                    <div class="cards">
                        <img src="/med_site/assets/image.png">
                        <h2>Приёмы врачей</h2>
                        <p>Консультации специалистов<br>
                            различных направлений</p>
                    </div>
                    <div class="cards">
                        <img src="/med_site/assets/e814507b9477c9b00fee976320f20600.jpg">
                        <h2>Анализы</h2>
                        <p>От общего анализа крови и биохимии<br>
                            до диагностики на онкомаркеры<br>
                            и других анализов</p>
                    </div>
                    <div class="cards">
                        <img src="/med_site/assets/image.png">
                        <h2>Исследования</h2>
                        <p>КТ, МРТ, УЗИ, рентген, флюорография <br>
                            и прочие исследования</p>
                    </div>
                    <div class="cards">
                        <img src="/med_site/assets/e814507b9477c9b00fee976320f20600.jpg">
                        <h2>Стоматология</h2>
                        <p>Первичный осмотр, диагностика<br>
                            и лечение кариеса, пульпита и других заболеваний</p>
                    </div>
                    <div class="cards">
                        <img src="/med_site/assets/image.png">
                        <h2>Экстренная госпитализация</h2>
                        <p>Вызов скорой помощи и пребывание<br>
                            в стационаре</p>
                    </div>
                    <div class="cards">
                        <img src="/med_site/assets/e814507b9477c9b00fee976320f20600.jpg">
                        <h2>Аптека «Ригла»</h2>
                        <p>Оплата 80% стоимости лекарств, БикЮ
                            назначенных врачомй</p>
                    </div>
                    
                </div>
                <button class="prev-button" aria-label="Посмотреть предыдущий слайд">&lt;</button>
                <button class="next-button" aria-label="Посмотреть следующий слайд">&gt</button>
            </div>
            <div class="program_dms">
                <div class="region-switch">
                    <button class="region-btn active" data-region="msk">Для Москвы и области</button>
                    <button class="region-btn" data-region="regions">Для регионов</button>
                </div>

                <div class="plans">
                    <!-- Базовый -->
                    <div class="card" data-plan="base">
                        <h3>Базовый</h3>
                        <div class="features">Поликлиника</div>
                        <div class="price-container">
                            <span class="price-value">43 050</span>
                            <span class="currency">₽</span>
                        </div>
                        <button>Оформить</button>
                        <button class="link-btn">Подробнее о покрытии</button>
                    </div>

                    <!-- Стандарт -->
                    <div class="card" data-plan="standart">
                        <h3>Стандарт</h3>
                        <div class="features">Поликлиника, Экстренная госпитализация</div>
                        <div class="price-container">
                            <span class="price-value">65 050</span>
                            <span class="currency">₽</span>
                        </div>
                        <button>Оформить</button>
                        <button class="link-btn">Подробнее о покрытии</button>
                    </div>

                    <!-- Оптимал -->
                    <div class="card" data-plan="optimal">
                        <h3>Оптимал</h3>
                        <div class="features">Поликлиника, Экстренная госпитализация, Стоматология</div>
                        <div class="price-container">
                            <span class="price-value">75 120</span>
                            <span class="currency">₽</span>
                        </div>
                        <button>Оформить</button>
                        <button class="link-btn">Подробнее о покрытии</button>
                    </div>

                    <!-- Премиум -->
                    <div class="card" data-plan="premium">
                        <h3>Премиум</h3>
                        <div class="features">Поликлиника, Экстренная госпитализация, Стоматология, Аптека</div>
                        <div class="price-container">
                            <span class="price-value">78 120</span>
                            <span class="currency">₽</span>
                        </div>
                        <button>Оформить</button>
                        <button class="link-btn">Подробнее о покрытии</button>
                    </div>
                </div>

                <div class="small-note">
                    * При переключении региона цены меняются плавно (с лёгкой анимацией)
                </div>
            </div>
        </div>
    </div>
<script src="js/script.js"></script>
</body>
</html>