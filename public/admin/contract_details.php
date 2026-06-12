<?php
require_once dirname(dirname(__DIR__)) . '/config/database.php';
requireLogin();
isAdmin(); // Проверка, что пользователь - администратор

$pdo = getConnection();
$contract_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($contract_id <= 0) {
    header('Location: contracts.php');
    exit;
}

// Получение детальной информации о договоре с JOIN всех связанных таблиц
$sql = "SELECT
            c.*,
            CONCAT(cl.last_name, ' ', cl.first_name, ' ', COALESCE(cl.middle_name, '')) as client_full_name,
            cl.passport_series,
            cl.passport_number,
            cl.phone as client_phone,
            cl.email as client_email,
            cl.address as client_address,
            cl.birth_date as client_birth_date,
            ip.program_name,
            ip.description as program_description,
            ip.base_price as program_base_price,
            ip.coverage_details
        FROM contracts c
        JOIN clients cl ON c.client_id = cl.client_id
        JOIN insurance_programs ip ON c.program_id = ip.program_id
        WHERE c.contract_id = ?";

$stmt = $pdo->prepare($sql);
$stmt->execute([$contract_id]);
$contract = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$contract) {
    header('Location: contracts.php');
    exit;
}

// Получение списка направлений по этому договору
$referrals_sql = "SELECT
                    r.*,
                    mi.name as institution_name,
                    ms.service_name,
                    ms.service_category
                  FROM referrals r
                  JOIN medical_institutions mi ON r.institution_id = mi.institution_id
                  JOIN medical_services ms ON r.service_id = ms.service_id
                  WHERE r.contract_id = ?
                  ORDER BY r.created_at DESC";
$referrals_stmt = $pdo->prepare($referrals_sql);
$referrals_stmt->execute([$contract_id]);
$referrals = $referrals_stmt->fetchAll();

// Получение информации о застрахованных лицах (если есть)
$insured_sql = "SELECT * FROM insured_persons WHERE contract_id = ?";
$insured_stmt = $pdo->prepare($insured_sql);
$insured_stmt->execute([$contract_id]);
$insured_persons = $insured_stmt->fetchAll();

// Обработка изменения статуса через форму
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_status'])) {
    try {
        $new_status = $_POST['status'];
        $allowed_statuses = ['pending', 'active', 'terminated', 'expired'];
        if (in_array($new_status, $allowed_statuses)) {
            $update_stmt = $pdo->prepare("UPDATE contracts SET status = ? WHERE contract_id = ?");
            $update_stmt->execute([$new_status, $contract_id]);
            $success = "Статус договора успешно изменён на '" . $new_status . "'";
            // Обновляем данные после изменения
            $stmt->execute([$contract_id]);
            $contract = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            $error = "Недопустимый статус";
        }
    } catch (Exception $e) {
        $error = "Ошибка: " . $e->getMessage();
    }
}

// Обработка добавления застрахованного лица
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_insured'])) {
    try {
        $insured_sql = "INSERT INTO insured_persons (contract_id, last_name, first_name, middle_name, birth_date, relationship)
                        VALUES (?, ?, ?, ?, ?, ?)";
        $insured_stmt = $pdo->prepare($insured_sql);
        $insured_stmt->execute([
            $contract_id,
            $_POST['last_name'],
            $_POST['first_name'],
            $_POST['middle_name'] ?: null,
            $_POST['birth_date'],
            $_POST['relationship']
        ]);
        $success = "Застрахованное лицо успешно добавлено!";
        // Обновляем список
        $insured_stmt = $pdo->prepare($insured_sql);
        $insured_stmt->execute([$contract_id]);
        $insured_persons = $insured_stmt->fetchAll();
    } catch (Exception $e) {
        $error = "Ошибка при добавлении: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Детали договора №<?php echo htmlspecialchars($contract['contract_number']); ?> - Страховая компания</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="admin.css?v=<?= time() ?>">
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
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
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
            word-break: break-word;
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
        .status-badge.terminated { background: #f8d7da; color: #721c24; }
        .status-badge.expired { background: #e2e3e5; color: #383d41; }
        .payment-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
        }
        .payment-badge.paid { background: #d4edda; color: #155724; }
        .payment-badge.unpaid { background: #f8d7da; color: #721c24; }
        .payment-badge.partial { background: #fff3cd; color: #856404; }
        .btn-edit {
            background: #ffc107;
            color: #000;
        }
        .btn-edit:hover {
            background: #e0a800;
        }
        .insured-card {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            border-left: 4px solid #667eea;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>Детали договора страхования</h1>
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
            <?php if (isset($success)): ?>
                <div class="alert success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            <?php if (isset($error)): ?>
                <div class="alert error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <!-- Кнопка назад -->
            <div style="margin-bottom: 20px;">
                <a href="contracts.php" class="btn">← Назад к списку договоров</a>
            </div>

            <!-- Основная информация о договоре -->
            <div class="detail-section">
                <h3>Основная информация о договоре</h3>
                <div class="detail-grid">
                    <div class="detail-item">
                        <label>Номер договора:</label>
                        <div class="value"><strong><?php echo htmlspecialchars($contract['contract_number']); ?></strong></div>
                    </div>
                    <div class="detail-item">
                        <label>Номер полиса:</label>
                        <div class="value"><strong><?php echo htmlspecialchars($contract['policy_number']); ?></strong></div>
                    </div>
                    <div class="detail-item">
                        <label>Статус договора:</label>
                        <div class="value">
                            <span class="status-badge <?php echo $contract['status']; ?>">
                                <?php
                                $status_labels = [
                                    'active' => '✅ Активен',
                                    'pending' => '⏳ Ожидает активации',
                                    'terminated' => '❌ Расторгнут',
                                    'expired' => '⌛ Истек'
                                ];
                                echo $status_labels[$contract['status']] ?? $contract['status'];
                                ?>
                            </span>
                        </div>
                    </div>
                    <div class="detail-item">
                        <label>Статус оплаты:</label>
                        <div class="value">
                            <span class="payment-badge <?php echo $contract['payment_status']; ?>">
                                <?php
                                $payment_labels = [
                                    'paid' => '✅ Оплачен',
                                    'unpaid' => '❌ Не оплачен',
                                    'partial' => '⚠ Частичная оплата'
                                ];
                                echo $payment_labels[$contract['payment_status']] ?? $contract['payment_status'];
                                ?>
                            </span>
                        </div>
                    </div>
                    <div class="detail-item">
                        <label>Дата начала:</label>
                        <div class="value"><?php echo date('d.m.Y', strtotime($contract['start_date'])); ?></div>
                    </div>
                    <div class="detail-item">
                        <label>Дата окончания:</label>
                        <div class="value"><?php echo date('d.m.Y', strtotime($contract['end_date'])); ?></div>
                    </div>
                    <div class="detail-item">
                        <label>Страховая премия:</label>
                        <div class="value"><?php echo number_format($contract['premium_total'], 2, ',', ' '); ?> ₽</div>
                    </div>
                    <div class="detail-item">
                        <label>Страховая сумма:</label>
                        <div class="value"><?php echo number_format($contract['insurance_sum'], 2, ',', ' '); ?> ₽</div>
                    </div>
                    <div class="detail-item">
                        <label>Дата создания:</label>
                        <div class="value"><?php echo date('d.m.Y H:i', strtotime($contract['created_at'])); ?></div>
                    </div>
                    <div class="detail-item">
                        <label>Дата оплаты:</label>
                        <div class="value"><?php echo $contract['payment_date'] ? date('d.m.Y', strtotime($contract['payment_date'])) : '—'; ?></div>
                    </div>
                </div>

                <!-- Форма изменения статуса -->
                <div style="margin-top: 25px; padding-top: 20px; border-top: 1px solid #ecf0f1;">
                    <h4>Изменить статус договора</h4>
                    <form method="POST" action="" style="display: flex; gap: 10px; align-items: flex-end;">
                        <div class="form-group" style="margin-bottom: 0;">
                            <select name="status" style="padding: 8px 12px;">
                                <option value="pending" <?php echo $contract['status'] == 'pending' ? 'selected' : ''; ?>>Ожидает активации</option>
                                <option value="active" <?php echo $contract['status'] == 'active' ? 'selected' : ''; ?>>Активировать</option>
                                <option value="terminated" <?php echo $contract['status'] == 'terminated' ? 'selected' : ''; ?>>Расторгнуть</option>
                                <option value="expired" <?php echo $contract['status'] == 'expired' ? 'selected' : ''; ?>>Истек</option>
                            </select>
                        </div>
                        <button type="submit" name="change_status" class="btn btn-primary">Применить</button>
                    </form>
                </div>
            </div>

            <!-- Информация о клиенте -->
            <div class="detail-section">
                <h3>👤 Информация о клиенте</h3>
                <div class="detail-grid">
                    <div class="detail-item">
                        <label>ФИО:</label>
                        <div class="value"><?php echo htmlspecialchars($contract['client_full_name']); ?></div>
                    </div>
                    <div class="detail-item">
                        <label>Дата рождения:</label>
                        <div class="value"><?php echo $contract['client_birth_date'] ? date('d.m.Y', strtotime($contract['client_birth_date'])) : '—'; ?></div>
                    </div>
                    <div class="detail-item">
                        <label>Телефон:</label>
                        <div class="value"><?php echo htmlspecialchars($contract['client_phone'] ?? '—'); ?></div>
                    </div>
                    <div class="detail-item">
                        <label>Email:</label>
                        <div class="value"><?php echo htmlspecialchars($contract['client_email'] ?? '—'); ?></div>
                    </div>
                    <div class="detail-item">
                        <label>Паспорт:</label>
                        <div class="value"><?php echo htmlspecialchars($contract['passport_series'] ?? ''); ?> <?php echo htmlspecialchars($contract['passport_number'] ?? ''); ?></div>
                    </div>
                    <div class="detail-item">
                        <label>Адрес:</label>
                        <div class="value"><?php echo htmlspecialchars($contract['client_address'] ?? '—'); ?></div>
                    </div>
                </div>
                <div style="margin-top: 15px;">
                    <a href="client_details.php?id=<?php echo $contract['client_id']; ?>" class="btn btn-small">Подробнее о клиенте →</a>
                </div>
            </div>

            <!-- Информация о программе страхования -->
            <div class="detail-section">
                <h3>📦 Программа страхования</h3>
                <div class="detail-grid">
                    <div class="detail-item">
                        <label>Название программы:</label>
                        <div class="value"><strong><?php echo htmlspecialchars($contract['program_name']); ?></strong></div>
                    </div>
                    <div class="detail-item">
                        <label>Базовая стоимость:</label>
                        <div class="value"><?php echo number_format($contract['program_base_price'], 2, ',', ' '); ?> ₽</div>
                    </div>
                    <div class="detail-item">
                        <label>Описание:</label>
                        <div class="value"><?php echo nl2br(htmlspecialchars($contract['program_description'] ?? '—')); ?></div>
                    </div>
                    <div class="detail-item">
                        <label>Покрытие:</label>
                        <div class="value"><?php echo nl2br(htmlspecialchars($contract['coverage_details'] ?? '—')); ?></div>
                    </div>
                </div>
            </div>

            <!-- Застрахованные лица -->
            <div class="detail-section">
                <h3>👨‍👩‍Застрахованные лица</h3>
                <?php if (count($insured_persons) > 0): ?>
                    <?php foreach($insured_persons as $insured): ?>
                        <div class="insured-card">
                            <div class="detail-grid" style="gap: 10px;">
                                <div class="detail-item">
                                    <label>ФИО:</label>
                                    <div class="value"><?php echo htmlspecialchars($insured['last_name'] . ' ' . $insured['first_name'] . ' ' . ($insured['middle_name'] ?? '')); ?></div>
                                </div>
                                <div class="detail-item">
                                    <label>Дата рождения:</label>
                                    <div class="value"><?php echo date('d.m.Y', strtotime($insured['birth_date'])); ?></div>
                                </div>
                                <div class="detail-item">
                                    <label>Степень родства:</label>
                                    <div class="value"><?php echo htmlspecialchars($insured['relationship'] ?? '—'); ?></div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="color: #7f8c8d; text-align: center; padding: 20px;">Нет добавленных застрахованных лиц</p>
                <?php endif; ?>

                <!-- Форма добавления застрахованного лица -->
                <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #ecf0f1;">
                    <h4>➕ Добавить застрахованное лицо</h4>
                    <form method="POST" action="" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                        <input type="hidden" name="add_insured" value="1">
                        <div class="form-group" style="margin-bottom: 0;">
                            <label>Фамилия *</label>
                            <input type="text" name="last_name" required>
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label>Имя *</label>
                            <input type="text" name="first_name" required>
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label>Отчество</label>
                            <input type="text" name="middle_name">
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label>Дата рождения *</label>
                            <input type="date" name="birth_date" required>
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label>Степень родства</label>
                            <select name="relationship">
                                <option value="">Выберите...</option>
                                <option value="Супруг(а)">Супруг(а)</option>
                                <option value="Ребенок">Ребенок</option>
                                <option value="Родитель">Родитель</option>
                                <option value="Иное">Иное</option>
                            </select>
                        </div>
                        <div style="display: flex; align-items: flex-end;">
                            <button type="submit" class="btn btn-success">Добавить</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Направления по договору -->
            <div class="detail-section">
                <h3>Направления по договору</h3>
                <?php if (count($referrals) > 0): ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>№ направления</th>
                                <th>ЛПУ</th>
                                <th>Услуга</th>
                                <th>Дата выдачи</th>
                                <th>Действительно до</th>
                                <th>Статус</th>
                                <th>Диагноз</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($referrals as $ref): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($ref['referral_number']); ?></td>
                                    <td><?php echo htmlspecialchars($ref['institution_name']); ?></td>
                                    <td><?php echo htmlspecialchars($ref['service_name']); ?></td>
                                    <td><?php echo date('d.m.Y', strtotime($ref['issue_date'])); ?></td>
                                    <td><?php echo date('d.m.Y', strtotime($ref['valid_until'])); ?></td>
                                    <td>
                                        <span class="status <?php echo $ref['status']; ?>">
                                            <?php
                                            $ref_status = [
                                                'issued' => 'Выдано',
                                                'used' => 'Использовано',
                                                'cancelled' => 'Отменено',
                                                'expired' => 'Просрочено'
                                            ];
                                            echo $ref_status[$ref['status']] ?? $ref['status'];
                                            ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($ref['diagnosis_code'] ?? '—'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p style="color: #7f8c8d; text-align: center; padding: 20px;">По данному договору ещё нет выданных направлений</p>
                <?php endif; ?>

                <?php if ($contract['status'] === 'active'): ?>
                    <div style="margin-top: 20px;">
                        <a href="referrals.php?contract_id=<?php echo $contract_id; ?>" class="btn btn-primary">+ Выдать новое направление</a>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Финансовая информация -->
            <div class="detail-section">
                <h3>💰 Финансовая информация</h3>
                <div class="detail-grid">
                    <div class="detail-item">
                        <label>Страховая премия:</label>
                        <div class="value"><?php echo number_format($contract['premium_total'], 2, ',', ' '); ?> ₽</div>
                    </div>
                    <div class="detail-item">
                        <label>Страховая сумма:</label>
                        <div class="value"><?php echo number_format($contract['insurance_sum'], 2, ',', ' '); ?> ₽</div>
                    </div>
                    <div class="detail-item">
                        <label>Статус оплаты:</label>
                        <div class="value">
                            <span class="payment-badge <?php echo $contract['payment_status']; ?>">
                                <?php echo $payment_labels[$contract['payment_status']] ?? $contract['payment_status']; ?>
                            </span>
                        </div>
                    </div>
                    <div class="detail-item">
                        <label>Дата оплаты:</label>
                        <div class="value"><?php echo $contract['payment_date'] ? date('d.m.Y', strtotime($contract['payment_date'])) : '—'; ?></div>
                    </div>
                </div>
            </div>
        </main>

        <footer>
            <p>&copy; 2026 Страховая медицинская компания. Все права защищены.</p>
        </footer>
    </div>
</body>
</html>