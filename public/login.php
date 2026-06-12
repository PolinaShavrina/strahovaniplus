<?php
require_once dirname(__DIR__) . '/config/database.php';

if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo = getConnection();

        $stmt = $pdo->prepare("SELECT u.*, c.first_name, c.last_name
                               FROM users u
                               LEFT JOIN clients c ON u.client_id = c.client_id
                               WHERE u.email = ?");
        $stmt->execute([$_POST['email']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($_POST['password'], $user['password_hash'])) {
            if (!$user['is_active']) {
                throw new Exception('Аккаунт заблокирован');
            }

            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['client_id'] = $user['client_id'];
            $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];

            // Обновляем время последнего входа
            $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE user_id = ?");
            $stmt->execute([$user['user_id']]);

            if ($user['role'] === 'admin') {
                header('Location: admin/index.php');
            } else {
                header('Location: dashboard.php');
            }
            exit;
        } else {
            throw new Exception('Неверный email или пароль');
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Вход - Страховая компания</title>
    <link rel="stylesheet" href="style.css?v=<?= time() ?>">
</head>
<body>
    <div class="auth-container">
        <div class="auth-box">
            <h1>Вход в систему</h1>
            <p>Добро пожаловать в Страховую компанию СтрахованиеPlus!</p>

            <?php if ($error): ?>
                <div class="alert error"><?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" required>
                </div>

                <div class="form-group">
                    <label>Пароль</label>
                    <input type="password" name="password" required>
                </div>

                <button type="submit" class="btn btn-primary btn-block">Войти</button>
            </form>

            <p class="auth-link">Нет аккаунта? <a href="register.php">Зарегистрироваться</a></p>
        </div>
    </div>
</body>
</html>