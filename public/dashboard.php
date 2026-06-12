<?php
require_once dirname(__DIR__) . '/config/database.php';
requireLogin();

$pdo = getConnection();

// Проверяем роль пользователя
if (isAdmin()) {
    // Если админ - перенаправляем в админ-панель
    header('Location: admin/index.php');
    exit();
}

// Далее идет код только для обычных пользователей (клиентов)
// Получаем информацию о клиенте
$stmt = $pdo->prepare("SELECT * FROM clients WHERE client_id = ?");
$stmt->execute([$_SESSION['client_id']]);
$client = $stmt->fetch();

if (!$client) {
    // Если клиент не найден (например, админ без привязки к клиенту)
    header('Location: admin/index.php');
    exit();
}

// Получаем активные полисы
$stmt = $pdo->prepare("SELECT c.*, p.program_name, p.icon
                       FROM contracts c
                       JOIN insurance_programs p ON c.program_id = p.program_id
                       WHERE c.client_id = ? AND c.status IN ('active', 'pending')
                       ORDER BY c.created_at DESC");
$stmt->execute([$_SESSION['client_id']]);
$contracts = $stmt->fetchAll();

// Получаем статистику
$total_contracts = count($contracts);
$active_contracts = count(array_filter($contracts, fn($c) => $c['status'] === 'active'));

// Получаем последние направления
$stmt = $pdo->prepare("SELECT r.*, i.name as institution_name, s.service_name
                       FROM referrals r
                       JOIN medical_institutions i ON r.institution_id = i.institution_id
                       JOIN medical_services s ON r.service_id = s.service_id
                       WHERE r.client_id = ?
                       ORDER BY r.created_at DESC LIMIT 5");
$stmt->execute([$_SESSION['client_id']]);
$recent_referrals = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Личный кабинет - Страховая компания</title>
    <link rel="stylesheet" href="style.css?v=<?= time() ?>">
    <style>
        .user-role-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            margin-left: 10px;
        }
        .role-admin {
            background: #e74c3c;
            color: white;
        }
        .role-client {
            background: #27ae60;
            color: white;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>Личный кабинет</h1>
            <nav>
                <a href="index.php">Главная</a>
                <a href="dashboard.php">Кабинет</a>
                <a href="my_contracts.php">Мои полисы</a>

                <a href="buy_policy.php">Купить полис</a>
                <a href="logout.php">Выйти</a>
            </nav>
        </header>

        <main>
            <div class="welcome-card">
                <h2>
                    Добро пожаловать, <?php echo htmlspecialchars($client['first_name'] . ' ' . $client['last_name']); ?>!
                    <span class="user-role-badge role-client">Клиент</span>
                </h2>
                <p>Ваш личный кабинет для управления страховыми полисами</p>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <h3>Всего полисов</h3>
                    <p class="stat-number"><?php echo $total_contracts; ?></p>
                </div>
                <div class="stat-card">
                    <h3>Активных полисов</h3>
                    <p class="stat-number"><?php echo $active_contracts; ?></p>
                </div>
                <div class="stat-card">
                    <h3>Выдано направлений</h3>
                    <p class="stat-number"><?php echo count($recent_referrals); ?></p>
                </div>
            </div>

            <?php if (!empty($contracts)): ?>
                <div class="section">
                    <h2>Мои полисы</h2>
                    <div class="contracts-grid">
                        <?php foreach($contracts as $contract): ?>
                            <div class="contract-card">
                                <div class="contract-header">
                                    <span class="policy-number">Полис №<?php echo htmlspecialchars($contract['policy_number']); ?></span>
                                    <span class="status <?php echo $contract['status']; ?>"><?php echo $contract['status']; ?></span>
                                </div>
                                <div class="contract-body">
                                    <h3><?php echo htmlspecialchars($contract['program_name']); ?></h3>
                                    <p>Действует с <?php echo $contract['start_date']; ?> по <?php echo $contract['end_date']; ?></p>
                                    <p>Страховая сумма: <?php echo number_format($contract['insurance_sum'], 0); ?> ₽</p>
                                </div>
                                <div class="contract-footer">
                                    <a href="contract_details.php?id=<?php echo $contract['contract_id']; ?>" class="btn btn-small">Подробнее</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <p>У вас пока нет активных полисов</p>
                    <a href="buy_policy.php" class="btn btn-primary">Оформить полис</a>
                </div>
            <?php endif; ?>

            <?php if (!empty($recent_referrals)): ?>
                <div class="section">
                    <h2>Последние направления</h2>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>№ направления</th>
                                <th>Учреждение</th>
                                <th>Услуга</th>
                                <th>Дата выдачи</th>
                                <th>Действительно до</th>
                                <th>Статус</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($recent_referrals as $ref): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($ref['referral_number']); ?></td>
                                <td><?php echo htmlspecialchars($ref['institution_name']); ?></td>
                                <td><?php echo htmlspecialchars($ref['service_name']); ?></td>
                                <td><?php echo $ref['issue_date']; ?></td>
                                <td><?php echo $ref['valid_until']; ?></td>
                                <td><span class="status <?php echo $ref['status']; ?>"><?php echo $ref['status']; ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </main>

        <footer>
            <p>&copy; 2026 Страховая медицинская компания. Все права защищены.</p>
        </footer>
    </div>
</body>
</html>