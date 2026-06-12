<?php
require_once dirname(__DIR__) . '/config/database.php';
requireLogin();

$pdo = getConnection();

// Получаем все договоры клиента
$stmt = $pdo->prepare("SELECT c.*, p.program_name, p.icon, p.features
                       FROM contracts c
                       JOIN insurance_programs p ON c.program_id = p.program_id
                       WHERE c.client_id = ?
                       ORDER BY c.created_at DESC");
$stmt->execute([$_SESSION['client_id']]);
$contracts = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Мои полисы - Страховая компания</title>
    <link rel="stylesheet" href="style.css?v=<?= time() ?>">
</head>
<body>
    <div class="container">
        <header>
            <h1>📋 Мои полисы</h1>
            <nav>
                <a href="index.php">Главная</a>
                <a href="dashboard.php">Кабинет</a>
                <a href="my_contracts.php">Мои полисы</a>

                <a href="buy_policy.php">Купить полис</a>
                <a href="logout.php">Выйти</a>
            </nav>
        </header>

        <main>
            <div class="page-header">
                <h2>Все полисы</h2>
                <a href="buy_policy.php" class="btn btn-primary">+ Новый полис</a>
            </div>

            <?php if (empty($contracts)): ?>
                <div class="empty-state">
                    <p>У вас пока нет оформленных полисов</p>
                    <a href="buy_policy.php" class="btn btn-primary">Оформить полис</a>
                </div>
            <?php else: ?>
                <div class="contracts-list">
                    <?php foreach($contracts as $contract): ?>
                        <div class="contract-card full">
                            <div class="contract-status">
                                <span class="status <?php echo $contract['status']; ?>"><?php echo $contract['status']; ?></span>
                                <span class="payment-status <?php echo $contract['payment_status']; ?>">
                                    <?php echo $contract['payment_status'] === 'paid' ? 'Оплачен' : 'Не оплачен'; ?>
                                </span>
                            </div>
                            <div class="contract-info">
                                <h3><?php echo htmlspecialchars($contract['program_name']); ?></h3>
                                <p><strong>Номер полиса:</strong> <?php echo htmlspecialchars($contract['policy_number']); ?></p>
                                <p><strong>Номер договора:</strong> <?php echo htmlspecialchars($contract['contract_number']); ?></p>
                                <p><strong>Период действия:</strong> <?php echo $contract['start_date']; ?> — <?php echo $contract['end_date']; ?></p>
                                <p><strong>Страховая сумма:</strong> <?php echo number_format($contract['insurance_sum'], 2); ?> ₽</p>
                                <p><strong>Стоимость:</strong> <?php echo number_format($contract['premium_total'], 2); ?> ₽</p>
                            </div>
                            <div class="contract-actions">
                                <a href="contract_details.php?id=<?php echo $contract['contract_id']; ?>" class="btn">Подробнее</a>
                                <?php if ($contract['status'] === 'active'): ?>
                                    <a href="my_referrals.php?contract=<?php echo $contract['contract_id']; ?>" class="btn">Направления</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </main>

        <footer>
            <p>&copy; 2026 Страховая медицинская компания. Все права защищены.</p>
        </footer>
    </div>
</body>
</html>