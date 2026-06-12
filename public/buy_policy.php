<?php
require_once dirname(__DIR__) . '/config/database.php';
requireLogin();

$pdo = getConnection();
$error = '';
$success = '';

// Получаем список программ
$programs = $pdo->query("SELECT * FROM insurance_programs WHERE is_active = 1")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $program_id = $_POST['program_id'];
        $start_date = $_POST['start_date'];

        // Получаем программу
        $stmt = $pdo->prepare("SELECT * FROM insurance_programs WHERE program_id = ?");
        $stmt->execute([$program_id]);
        $program = $stmt->fetch();

        // Генерируем номера
        $contract_number = 'DMS-' . date('Ymd') . '-' . rand(1000, 9999);
        $policy_number = 'POL-' . date('Ymd') . '-' . rand(10000, 99999);
        $end_date = date('Y-m-d', strtotime($start_date . ' + 1 year'));

        // Создаем договор
        $stmt = $pdo->prepare("INSERT INTO contracts (client_id, program_id, contract_number, policy_number,
                               start_date, end_date, premium_total, insurance_sum, status, payment_status)
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', 'unpaid')");
        $stmt->execute([
            $_SESSION['client_id'],
            $program_id,
            $contract_number,
            $policy_number,
            $start_date,
            $end_date,
            $program['base_price'],
            $program['base_price'] * 10
        ]);

        $success = "Заявка на оформление полиса успешно создана! Номер заявки: " . $contract_number;

    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Оформление полиса - Страховая компания</title>
    <link rel="stylesheet" href="style.css?v=<?= time() ?>">
</head>
<body>
    <div class="container">
        <header>
            <h1>📄 Оформление полиса ДМС</h1>
            <nav>
                <a href="index.php">Главная</a>
                <a href="dashboard.php">Кабинет</a>
                <a href="my_contracts.php">Мои полисы</a>
                <a href="buy_policy.php">Купить полис</a>
                <a href="logout.php">Выйти</a>
            </nav>
        </header>

        <main>
            <?php if ($success): ?>
                <div class="alert success"><?php echo $success; ?></div>
                <a href="dashboard.php" class="btn btn-primary">В личный кабинет</a>
            <?php elseif ($error): ?>
                <div class="alert error"><?php echo $error; ?></div>
            <?php else: ?>
                <div class="form-container">
                    <h2>Выберите программу страхования</h2>
                    <form method="POST" action="">
                        <div class="programs-select">
                            <?php foreach($programs as $program): ?>
                                <div class="program-option">
                                    <input type="radio" name="program_id" value="<?php echo $program['program_id']; ?>"
                                           id="program_<?php echo $program['program_id']; ?>" required>
                                    <label for="program_<?php echo $program['program_id']; ?>">
                                        <h3><?php echo htmlspecialchars($program['program_name']); ?></h3>
                                        <p><?php echo htmlspecialchars($program['description']); ?></p>
                                        <div class="price"><?php echo number_format($program['base_price'], 0); ?> ₽/год</div>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="form-group">
                            <label>Дата начала страхования</label>
                            <input type="date" name="start_date" required min="<?php echo date('Y-m-d'); ?>">
                        </div>

                        <button type="submit" class="btn btn-primary btn-block">Оформить полис</button>
                    </form>
                </div>
            <?php endif; ?>
        </main>

        <footer>
            <p>&copy; 2026 Страховая медицинская компания. Все права защищены.</p>
        </footer>
    </div>
</body>
</html>