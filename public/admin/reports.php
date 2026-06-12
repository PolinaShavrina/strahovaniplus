<?php
require_once dirname(dirname(__DIR__)) . '/config/database.php';

$pdo = getConnection();

// Получение статистики с проверками
$total_clients = $pdo->query("SELECT COUNT(*) as count FROM clients")->fetch()['count'] ?? 0;
$active_contracts = $pdo->query("SELECT COUNT(*) as count FROM contracts WHERE status = 'active'")->fetch()['count'] ?? 0;
$total_referrals = $pdo->query("SELECT COUNT(*) as count FROM referrals")->fetch()['count'] ?? 0;
$total_premium = $pdo->query("SELECT SUM(premium_total) as sum FROM contracts WHERE status = 'active'")->fetch()['sum'] ?? 0;

// Доходы по месяцам
$monthly_revenue = $pdo->query("SELECT DATE_FORMAT(start_date, '%Y-%m') as month, SUM(premium_total) as total
                                 FROM contracts
                                 WHERE status = 'active'
                                 GROUP BY month
                                 ORDER BY month DESC LIMIT 6")->fetchAll();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Отчеты - Страховая компания</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="admin.css?v=<?= time() ?>">
</head>
<body>
    <div class="container">
        <header>
            <h1>Отчеты и аналитика</h1>
            <nav>
                <a href="index.php">Главная</a>
                <a href="clients.php">Клиенты</a>
                <a href="contracts.php">Договоры</a>
                <a href="referrals.php">Направления</a>
                <a href="institutions.php">ЛПУ</a>
                <a href="reports.php">Отчеты</a>
            </nav>
        </header>

        <main>
            <div class="stats">
                <div class="stat-card">
                    <h3>Всего клиентов</h3>
                    <p class="stat-number"><?php echo $total_clients; ?></p>
                </div>
                <div class="stat-card">
                    <h3>Активных договоров</h3>
                    <p class="stat-number"><?php echo $active_contracts; ?></p>
                </div>
                <div class="stat-card">
                    <h3>Выдано направлений</h3>
                    <p class="stat-number"><?php echo $total_referrals; ?></p>
                </div>
                <div class="stat-card">
                    <h3>Общая премия</h3>
                    <p class="stat-number"><?php echo number_format($total_premium, 0, ',', ' '); ?> ₽</p>
                </div>
            </div>

            <div class="recent-activities">
                <h2>Доходы по месяцам</h2>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Месяц</th>
                            <th>Сумма (₽)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(!empty($monthly_revenue)): ?>
                            <?php foreach($monthly_revenue as $revenue): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($revenue['month']); ?></td>
                                <td><?php echo number_format($revenue['total'], 2, ',', ' '); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="2" style="text-align: center;">Нет данных</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>

        <footer>
            <p>&copy; 2026 Страховая медицинская компания. Все права защищены.</p>
        </footer>
    </div>
</body>
</html>