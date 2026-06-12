<?php
require_once dirname(__DIR__) . '/config/database.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo = getConnection();

        // Проверка существования email
        $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->execute([$_POST['email']]);
        if ($stmt->fetch()) {
            throw new Exception('Email уже зарегистрирован');
        }

        // Начинаем транзакцию
        $pdo->beginTransaction();

        // Создаем клиента
        $stmt = $pdo->prepare("INSERT INTO clients (last_name, first_name, middle_name, birth_date, passport_series, passport_number, phone, email, address)
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $_POST['last_name'],
            $_POST['first_name'],
            $_POST['middle_name'],
            $_POST['birth_date'],
            $_POST['passport_series'],
            $_POST['passport_number'],
            $_POST['phone'],
            $_POST['email'],
            $_POST['address']
        ]);

        $client_id = $pdo->lastInsertId();

        // Создаем пользователя
        $password_hash = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (email, password_hash, role, client_id) VALUES (?, ?, 'client', ?)");
        $stmt->execute([$_POST['email'], $password_hash, $client_id]);

        $pdo->commit();

        $success = "Регистрация успешна! Теперь вы можете войти.";

    } catch (Exception $e) {
        $pdo->rollBack();
        $error = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Регистрация - Страховая компания</title>
    <link rel="stylesheet" href="style.css?v=<?= time() ?>">
</head>
<body>
    <div class="auth-container">
        <div class="auth-box">
            <h1>Регистрация</h1>
            <p>Заполните форму для создания аккаунта в СтрахованиеPlus</p>

            <?php if ($error): ?>
                <div class="alert error"><?php echo $error; ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert success"><?php echo $success; ?></div>
                <a href="login.php" class="btn btn-primary">Войти</a>
            <?php else: ?>
                <form method="POST" action="">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Фамилия *</label>
                            <input type="text" name="last_name" required>
                        </div>
                        <div class="form-group">
                            <label>Имя *</label>
                            <input type="text" name="first_name" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Отчество</label>
                        <input type="text" name="middle_name">
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Дата рождения *</label>
                            <input type="date" name="birth_date" required>
                        </div>
                        <div class="form-group">
                            <label>Телефон *</label>
                            <input type="tel" name="phone" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Серия паспорта</label>
                            <input type="text" name="passport_series">
                        </div>
                        <div class="form-group">
                            <label>Номер паспорта</label>
                            <input type="text" name="passport_number">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Email *</label>
                        <input type="email" name="email" required>
                    </div>

                    <div class="form-group">
                        <label>Адрес</label>
                        <textarea name="address"></textarea>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Пароль *</label>
                            <input style = "margin-bottom: 10px" type="password" name="password" required minlength="6">
                            <button style = "background-color: #00004d; color: white; border: none; padding: 15px; border-radius: 5px;" type = "button" class = "submit">Показать пароль</button>
                        </div>
                        <div class="form-group">
                            <label>Подтвердите Пароль *</label>
                            <input style = " margin-bottom: 10px" type="password" name="password1" required minlength="6">
                            <button style = "background-color: #00004d; color: white; border: none; padding: 15px; border-radius: 5px;" type = "button" class = "submit1">Показать пароль</button>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block">Зарегистрироваться</button>
                </form>

                <p class="auth-link">Уже есть аккаунт? <a href="login.php">Войти</a></p>
            <?php endif; ?>
        </div>
    </div>
<script>
    let pass = document.getElementsByName('password')[0];
    let pass1 = document.getElementsByName('password1')[0];
    let btn = document.getElementsByClassName('submit')[0];
    let btn1 = document.getElementsByClassName('submit1')[0];

    btn.addEventListener('click', function(e) {
        e.preventDefault(); // это метод объекта события в JavaScript, который используется для отмены стандартного действия браузера, которое обычно выполняется автоматически при возникновении определённого события
        if (pass.type === "password") {
            pass.type = "text";
            btn.textContent = "Скрыть пароль";
        } else {
            pass.type = "password";
            btn.textContent = "Показать пароль";
        }
    });

    btn1.addEventListener('click', function(e) {
        e.preventDefault();
        if (pass1.type === "password") {
            pass1.type = "text";
            btn1.textContent = "Скрыть пароль";
        } else {
            pass1.type = "password";
            btn1.textContent = "Показать пароль";
        }
    });

    document.querySelector('form').addEventListener('submit', function(event) {
        if (pass.value !== pass1.value) {
            alert('Пароли не совпадают!');
            event.preventDefault();
            return false;
        }
    });
</script>
</body>
</html>