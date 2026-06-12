<?php
require_once dirname(__DIR__) . '/config/database.php';
$pdo = getConnection();
$programs = $pdo->query("SELECT * FROM insurance_programs WHERE is_active = 1 LIMIT 4")->fetchAll();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Страховая медицинская компания</title>
    <link rel="stylesheet" href="style.css?v=<?= time() ?>">
</head>
<style>
  h1 span:hover {
    transform: translateY(2px);
    box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
    cursor: pointer;
    padding: 10px;
    border-radius: 5px;
    background-color: #00004d;
  }
</style>
<body>
    <header class="main-header">
        <div class="container">
            <h1>Страховая медицинская компания<br>
                <span>СтрахованиеPlus</span></h1>
            <nav>
                <a href="index.php">Главная</a>
                <?php if (isLoggedIn()): ?>
                    <a href="dashboard.php">Личный кабинет</a>
                    <a href="logout.php">Выйти (<?php echo htmlspecialchars($_SESSION['user_name'] ?? ''); ?>)</a>
                <?php else: ?>
                    <a href="login.php">Войти</a>
                    <a href="register.php">Регистрация</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>

    <main>
            <div class="hero-slider-container">
                <div class="slideshow-container">
                    <div class="slideshow-item active" style="background-image: url('../assets/1.jpg');"></div>
                    <div class="slideshow-item" style="background-image: url('../assets/image4.png');"></div>
                    <div class="slideshow-item" style="background-image: url('../assets/image3.png');"></div>
                </div>

                <!-- Затемнение для читаемости текста -->
                <div class="slider-overlay"></div>

                <div class="container slider-content">
                    <h2>Ваше здоровье - наша забота</h2>
                    <p style="color: white;">Добровольное медицинское страхование от ведущей компании</p>
                    <?php if (!isLoggedIn()): ?>
                        <a href="register.php" class="btn btn-large">Оформить полис</a>
                    <?php else: ?>
                        <a href="buy_policy.php" class="btn btn-large">Выбрать программу</a>
                    <?php endif; ?>
                </div>

                <!-- Кнопки навигации -->
                <button class="slider-btn prev-btn">❮</button>
                <button class="slider-btn next-btn">❯</button>

                <!-- Индикаторы (точки) -->
                <div class="slider-dots">
                    <span class="dot active"></span>
                    <span class="dot"></span>
                    <span class="dot"></span>
                </div>
        </div>

        <section class="programs">
            <div class="container">
                <h2>Популярные программы</h2>
                <div class="programs-grid">
                    <?php foreach($programs as $program): ?>
                        <div class="program-card">
                            <div class="program-icon"></div>
                            <h3><?php echo htmlspecialchars($program['program_name']); ?></h3>
                            <p style="color: #000084; font-size:25px" font-size:25px"><?php echo htmlspecialchars($program['description']); ?></p>
                            <div class="price">от <?php echo number_format($program['base_price'], 0); ?> ₽/год</div>
                            <a href="buy_policy.php?program_id=<?php echo $program['program_id']; ?>" class="btn">Подробнее</a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    </main>

    <footer>
        <div class="container">
            <p>&copy; 2026 Страховая медицинская компания. Все права защищены.</p>
        </div>
    </footer>
    <script>
document.addEventListener('DOMContentLoaded', function() {
    // Получаем все элементы
    const slides = document.querySelectorAll('.slideshow-item');
    const prevBtn = document.querySelector('.prev-btn');
    const nextBtn = document.querySelector('.next-btn');
    const dots = document.querySelectorAll('.dot');

    let currentSlide = 0;
    let slideInterval;
    const intervalTime = 5000; // 5 секунд на слайд

    // Функция показа определенного слайда
    function showSlide(index) {
        // Убираем активный класс у всех слайдов
        slides.forEach(slide => {
            slide.classList.remove('active');
        });

        // Убираем активный класс у всех точек
        dots.forEach(dot => {
            dot.classList.remove('active');
        });

        // Добавляем активный класс текущему слайду и точке
        slides[index].classList.add('active');
        dots[index].classList.add('active');

        currentSlide = index;
    }

    // Следующий слайд
    function nextSlide() {
        let newIndex = currentSlide + 1;
        if (newIndex >= slides.length) {
            newIndex = 0;
        }
        showSlide(newIndex);
    }

    // Предыдущий слайд
    function prevSlide() {
        let newIndex = currentSlide - 1;
        if (newIndex < 0) {
            newIndex = slides.length - 1;
        }
        showSlide(newIndex);
    }

    // Сброс таймера автопрокрутки
    function resetInterval() {
        clearInterval(slideInterval);
        slideInterval = setInterval(nextSlide, intervalTime);
    }

    // Обработчики кнопок
    if (prevBtn && nextBtn) {
        prevBtn.addEventListener('click', () => {
            prevSlide();
            resetInterval();
        });

        nextBtn.addEventListener('click', () => {
            nextSlide();
            resetInterval();
        });
    }

    // Обработчики точек
    dots.forEach((dot, index) => {
        dot.addEventListener('click', () => {
            showSlide(index);
            resetInterval();
        });
    });

    // Запускаем автопрокрутку
    slideInterval = setInterval(nextSlide, intervalTime);

    // Пауза при наведении на слайдер
    const sliderContainer = document.querySelector('.hero-slider-container');
    if (sliderContainer) {
        sliderContainer.addEventListener('mouseenter', () => {
            clearInterval(slideInterval);
        });

        sliderContainer.addEventListener('mouseleave', () => {
            slideInterval = setInterval(nextSlide, intervalTime);
        });
    }
});
</script>
</body>
</html>