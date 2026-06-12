<?php
require_once dirname(__DIR__) . '/config/database.php';
requireLogin();

// Если администратор, перенаправляем в админ-версию
if (isAdmin()) {
    header('Location: ../admin/contract_details.php?id=' . (int)($_GET['id'] ?? 0));
    exit;
}

$pdo = getConnection();
$contract_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($contract_id <= 0) {
    header('Location: dashboard.php');
    exit;
}

// Проверка принадлежности договора текущему клиенту
$check_sql = "SELECT client_id FROM contracts WHERE contract_id = ?";
$check_stmt = $pdo->prepare($check_sql);
$check_stmt->execute([$contract_id]);
$contract_owner = $check_stmt->fetch(PDO::FETCH_ASSOC);

if (!$contract_owner || $contract_owner['client_id'] != $_SESSION['client_id']) {
    header('Location: dashboard.php');
    exit;
}

// Получение детальной информации о договоре
$sql = "SELECT
            c.*,
            ip.program_name,
            ip.description as program_description,
            ip.coverage_details
        FROM contracts c
        JOIN insurance_programs ip ON c.program_id = ip.program_id
        WHERE c.contract_id = ? AND c.client_id = ?";

$stmt = $pdo->prepare($sql);
$stmt->execute([$contract_id, $_SESSION['client_id']]);
$contract = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$contract) {
    header('Location: dashboard.php');
    exit;
}

// Получение направлений
$referrals_sql = "SELECT
                    r.*,
                    mi.name as institution_name,
                    ms.service_name
                  FROM referrals r
                  JOIN medical_institutions mi ON r.institution_id = mi.institution_id
                  JOIN medical_services ms ON r.service_id = ms.service_id
                  WHERE r.contract_id = ?
                  ORDER BY r.created_at DESC";
$referrals_stmt = $pdo->prepare($referrals_sql);
$referrals_stmt->execute([$contract_id]);
$referrals = $referrals_stmt->fetchAll();

// Получение застрахованных лиц
$insured_sql = "SELECT * FROM insured_persons WHERE contract_id = ?";
$insured_stmt = $pdo->prepare($insured_sql);
$insured_stmt->execute([$contract_id]);
$insured_persons = $insured_stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Детали полиса - Страховая компания</title>
    <link rel="stylesheet" href="style.css?v=<?= time() ?>">
    <style>
        .detail-section {
            background: white;
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .detail-section h3 {
            color: #2c3e50;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #667eea;
        }
        .detail-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }
        .detail-item {
            margin-bottom: 15px;
        }
        .detail-item label {
            font-weight: 600;
            color: #7f8c8d;
            display: block;
            margin-bottom: 5px;
            font-size: 13px;
        }
        .detail-item .value {
            font-size: 16px;
            color: #2c3e50;
        }
        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 500;
        }
        .status-badge.active { background: #d4edda; color: #155724; }
        .status-badge.pending { background: #fff3cd; color: #856404; }
        .payment-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
        }
        .payment-badge.paid { background: #d4edda; color: #155724; }
        .payment-badge.unpaid { background: #f8d7da; color: #721c24; }
        .print-btn {
            background: #6c757d;
            color: white;
            margin-left: 10px;
        }
        @media print {
            header, nav, footer, .no-print, .btn, .action-buttons {
                display: none;
            }
            .detail-section {
                box-shadow: none;
                padding: 0;
                margin-bottom: 20px;
            }
            body {
                background: white;
            }
            .container {
                padding: 0;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <header class="no-print">
            <h1>📄 Детали полиса ДМС</h1>
            <nav>
                <a href="index.php">Главная</a>
                <a href="dashboard.php">Кабинет</a>
                <a href="my_contracts.php">Мои полисы</a>
                <a href="buy_policy.php">Купить полис</a>
                <a href="logout.php">Выйти</a>
            </nav>
        </header>

        <main>
            <div style="margin-bottom: 20px;" class="no-print">
                <a href="my_contracts.php" class="btn">← Назад к моим полисам</a>
                <button onclick="window.print()" class="btn print-btn">🖨 Распечатать</button>
            </div>

            <div class="detail-section">
                <h3>📋 Информация о полисе</h3>
                <div class="detail-grid">
                    <div class="detail-item">
                        <label>Номер полиса:</label>
                        <div class="value"><strong><?php echo htmlspecialchars($contract['policy_number']); ?></strong></div>
                    </div>
                    <div class="detail-item">
                        <label>Номер договора:</label>
                        <div class="value"><?php echo htmlspecialchars($contract['contract_number']); ?></div>
                    </div>
                    <div class="detail-item">
                        <label>Статус:</label>
                        <div class="value">
                            <span class="status-badge <?php echo $contract['status']; ?>">
                                <?php echo $status_labels[$contract['status']] ?? $contract['status']; ?>
                            </span>
                        </div>
                    </div>
                    <div class="detail-item">
                        <label>Период действия:</label>
                        <div class="value"><?php echo date('d.m.Y', strtotime($contract['start_date'])); ?> — <?php echo date('d.m.Y', strtotime($contract['end_date'])); ?></div>
                    </div>
                    <div class="detail-item">
                        <label>Страховая сумма:</label>
                        <div class="value"><?php echo number_format($contract['insurance_sum'], 2, ',', ' '); ?> ₽</div>
                    </div>
                    <div class="detail-item">
                        <label>Стоимость полиса:</label>
                        <div class="value"><?php echo number_format($contract['premium_total'], 2, ',', ' '); ?> ₽</div>
                    </div>
                    <div class="detail-item">
                        <label>Статус оплаты:</label>
                        <div class="value">
                            <span class="payment-badge <?php echo $contract['payment_status']; ?>">
                                <?php echo $contract['payment_status'] === 'paid' ? 'Оплачен' : 'Не оплачен'; ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="detail-section">
                <h3>📦 Программа страхования: <?php echo htmlspecialchars($contract['program_name']); ?></h3>
                <div class="detail-item">
                    <label>Описание программы:</label>
                    <div class="value"><?php echo nl2br(htmlspecialchars($contract['program_description'] ?? '—')); ?></div>
                </div>
                <div class="detail-item">
                    <label>Что входит в покрытие:</label>
                    <div class="value"><?php echo nl2br(htmlspecialchars($contract['coverage_details'] ?? '—')); ?></div>
                </div>
            </div>

            <?php if (count($insured_persons) > 0): ?>
            <div class="detail-section">
                <h3>👨‍👩‍👧 Застрахованные лица</h3>
                <?php foreach($insured_persons as $insured): ?>
                    <div style="padding: 10px; border-bottom: 1px solid #ecf0f1;">
                        <strong><?php echo htmlspecialchars($insured['last_name'] . ' ' . $insured['first_name'] . ' ' . ($insured['middle_name'] ?? '')); ?></strong>
                        (<?php echo htmlspecialchars($insured['relationship'] ?? 'Страхователь'); ?>)<br>
                        Дата рождения: <?php echo date('d.m.Y', strtotime($insured['birth_date'])); ?>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <?php if (count($referrals) > 0): ?>
            <div class="detail-section">
                <h3>📋 Выданные направления</h3>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>№ направления</th>
                            <th>Медицинское учреждение</th>
                            <th>Услуга</th>
                            <th>Действительно до</th>
                            <th>Статус</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($referrals as $ref): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($ref['referral_number']); ?></td>
                            <td><?php echo htmlspecialchars($ref['institution_name']); ?></td>
                            <td><?php echo htmlspecialchars($ref['service_name']); ?></td>
                            <td><?php echo date('d.m.Y', strtotime($ref['valid_until'])); ?></td>
                            <td><span class="status <?php echo $ref['status']; ?>"><?php echo $ref['status']; ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </main>

        <footer class="no-print">
            <p>&copy; 2026 Страховая медицинская компания. Все права защищены.</p>
        </footer>
    </div>
</body>
</html>