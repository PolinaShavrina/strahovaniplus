<?php
require_once dirname(dirname(__DIR__)) . '/config/database.php';

$pdo = getConnection();

// Получение списка клиентов
$clients = $pdo->query("SELECT client_id, last_name, first_name, middle_name, phone, email FROM clients ORDER BY last_name ASC")->fetchAll();

// Получение списка программ
$programs = $pdo->query("SELECT program_id, program_name, base_price FROM insurance_programs WHERE is_active = 1")->fetchAll();

// Добавление договора
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    try {
        $contract_number = 'DMS-' . date('Ymd') . '-' . rand(1000, 9999);
        $policy_number = 'POL-' . date('Ymd') . '-' . rand(100000, 999999);

        $sql = "INSERT INTO contracts (client_id, program_id, contract_number, policy_number, start_date, end_date, premium_total, insurance_sum, status, payment_status)
                VALUES (:client_id, :program_id, :contract_number, :policy_number, :start_date, :end_date, :premium_total, :insurance_sum, 'pending', 'unpaid')";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':client_id' => $_POST['client_id'],
            ':program_id' => $_POST['program_id'],
            ':contract_number' => $contract_number,
            ':policy_number' => $policy_number,
            ':start_date' => $_POST['start_date'],
            ':end_date' => $_POST['end_date'],
            ':premium_total' => $_POST['premium_total'],
            ':insurance_sum' => $_POST['insurance_sum']
        ]);
        $success = "Договор №{$contract_number} успешно создан! Полис №{$policy_number}";
    } catch (Exception $e) {
        $error = "Ошибка: " . $e->getMessage();
    }
}

// Обновление статуса договора
if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $contract_id = $_GET['id'];

    if ($action === 'activate') {
        try {
            $stmt = $pdo->prepare("UPDATE contracts SET status = 'active' WHERE contract_id = ?");
            $stmt->execute([$contract_id]);
            $success = "Договор активирован!";
        } catch (Exception $e) {
            $error = "Ошибка: " . $e->getMessage();
        }
    } elseif ($action === 'terminate') {
        try {
            $stmt = $pdo->prepare("UPDATE contracts SET status = 'terminated' WHERE contract_id = ?");
            $stmt->execute([$contract_id]);
            $success = "Договор расторгнут!";
        } catch (Exception $e) {
            $error = "Ошибка: " . $e->getMessage();
        }
    } elseif ($action === 'delete') {
        try {
            $check = $pdo->prepare("SELECT COUNT(*) FROM referrals WHERE contract_id = ?");
            $check->execute([$contract_id]);
            if ($check->fetchColumn() > 0) {
                $error = "Невозможно удалить договор, так как по нему есть выданные направления!";
            } else {
                $stmt = $pdo->prepare("DELETE FROM contracts WHERE contract_id = ?");
                $stmt->execute([$contract_id]);
                $success = "Договор удален!";
            }
        } catch (Exception $e) {
            $error = "Ошибка: " . $e->getMessage();
        }
    }
}

// Получение списка договоров
$search = isset($_GET['search']) ? $_GET['search'] : '';

$sql = "SELECT
        c.*,
        CONCAT(cl.last_name, ' ', cl.first_name) as client_name,
        ip.program_name
        FROM contracts c
        JOIN clients cl ON c.client_id = cl.client_id
        JOIN insurance_programs ip ON c.program_id = ip.program_id";

if (!empty($search)) {
    $sql .= " WHERE c.contract_number LIKE :search OR c.policy_number LIKE :search OR cl.last_name LIKE :search OR cl.first_name LIKE :search";
    $sql .= " ORDER BY c.created_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':search' => "%$search%"]);
} else {
    $sql .= " ORDER BY c.created_at DESC";
    $stmt = $pdo->query($sql);
}
$contracts = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Договоры - Страховая компания</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="admin.css?v=<?= time() ?>">
    <style>
        .status.active { background: #d4edda; color: #155724; padding: 4px 8px; border-radius: 4px; display: inline-block; }
        .status.pending { background: #fff3cd; color: #856404; padding: 4px 8px; border-radius: 4px; display: inline-block; }
        .status.terminated, .status.expired { background: #f8d7da; color: #721c24; padding: 4px 8px; border-radius: 4px; display: inline-block; }
        .action-buttons { display: flex; gap: 5px; flex-wrap: wrap; }
        .btn-warning { background: #ffc107; color: #000; }
        .btn-warning:hover { background: #e0a800; }
        .btn-info { background: #17a2b8; color: white; }
        .btn-info:hover { background: #138496; }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>📄 Управление договорами</h1>
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

            <div class="page-header">
                <h2>Список договоров</h2>
                <button class="btn btn-primary" onclick="showAddForm()">+ Оформить договор</button>
            </div>

            <div class="search-box">
                <form method="GET" action="" style="display: flex; gap: 10px;">
                    <input type="text" name="search" class="search-input" placeholder="Поиск по номеру договора, полиса или клиенту..."
                           value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit" class="btn btn-primary">🔍 Найти</button>
                    <?php if (!empty($search)): ?>
                        <a href="contracts.php" class="btn">❌ Сбросить</a>
                    <?php endif; ?>
                </form>
            </div>

            <table class="data-table">
                <thead>
                    <tr>
                        <th>№ договора</th>
                        <th>№ полиса</th>
                        <th>Клиент</th>
                        <th>Программа</th>
                        <th>Дата начала</th>
                        <th>Дата окончания</th>
                        <th>Премия</th>
                        <th>Статус</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($contracts) > 0): ?>
                        <?php foreach($contracts as $contract): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($contract['contract_number']); ?></td>
                            <td><?php echo htmlspecialchars($contract['policy_number']); ?></td>
                            <td><?php echo htmlspecialchars($contract['client_name'] ?? '—'); ?></td>
                            <td><?php echo htmlspecialchars($contract['program_name']); ?></td>
                            <td><?php echo date('d.m.Y', strtotime($contract['start_date'])); ?></td>
                            <td><?php echo date('d.m.Y', strtotime($contract['end_date'])); ?></td>
                            <td><?php echo number_format($contract['premium_total'], 2, ',', ' '); ?> ₽</td>
                            <td>
                                <span class="status <?php echo $contract['status']; ?>">
                                    <?php
                                    $status_labels = [
                                        'active' => '✅ Активен',
                                        'pending' => '⏳ Ожидает',
                                        'terminated' => '❌ Расторгнут',
                                        'expired' => '⌛ Истек'
                                    ];
                                    echo $status_labels[$contract['status']] ?? $contract['status'];
                                    ?>
                                </span>
                            </td>
                            <td class="action-buttons">
                                <a href="contract_details.php?id=<?php echo $contract['contract_id']; ?>" class="btn btn-small btn-info">📋 Детали</a>
                                <?php if ($contract['status'] === 'pending'): ?>
                                    <a href="?action=activate&id=<?php echo $contract['contract_id']; ?>" class="btn btn-small btn-success" onclick="return confirm('Активировать договор?')">✅ Активировать</a>
                                    <a href="?action=delete&id=<?php echo $contract['contract_id']; ?>" class="btn btn-small btn-danger" onclick="return confirm('Удалить договор?')">🗑 Удалить</a>
                                <?php elseif ($contract['status'] === 'active'): ?>
                                    <a href="?action=terminate&id=<?php echo $contract['contract_id']; ?>" class="btn btn-small btn-warning" onclick="return confirm('Расторгнуть договор?')">⛔ Расторгнуть</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" style="text-align: center; padding: 40px;">
                                <?php if (!empty($search)): ?>
                                    🤔 Договоры не найдены по запросу "<?php echo htmlspecialchars($search); ?>"
                                <?php else: ?>
                                    📭 Договоры отсутствуют. Нажмите "Оформить договор"
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </main>

        <footer>
            <p>&copy; 2026 Страховая медицинская компания. Все права защищены.</p>
        </footer>
    </div>

    <!-- Модальное окно добавления договора -->
    <div id="addModal" class="modal" style="display:none;">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h3>📝 Оформление нового договора</h3>
            <form method="POST" action="" onsubmit="return validateContractForm()">
                <input type="hidden" name="action" value="add">
                <div class="form-group">
                    <label>Клиент *:</label>
                    <select name="client_id" id="client_id" required>
                        <option value="">Выберите клиента</option>
                        <?php foreach($clients as $client): ?>
                            <option value="<?php echo $client['client_id']; ?>">
                                <?php echo htmlspecialchars($client['last_name'] . ' ' . $client['first_name'] . ' (' . $client['phone'] . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Программа страхования *:</label>
                    <select name="program_id" id="program_id" required>
                        <option value="">Выберите программу</option>
                        <?php foreach($programs as $program): ?>
                            <option value="<?php echo $program['program_id']; ?>" data-price="<?php echo $program['base_price']; ?>">
                                <?php echo htmlspecialchars($program['program_name']); ?>
                                (<?php echo number_format($program['base_price'], 2, ',', ' '); ?> ₽)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Дата начала *:</label>
                    <input type="date" name="start_date" id="start_date" required>
                </div>
                <div class="form-group">
                    <label>Дата окончания *:</label>
                    <input type="date" name="end_date" id="end_date" required>
                </div>
                <div class="form-group">
                    <label>Страховая премия (₽) *:</label>
                    <input type="number" step="0.01" name="premium_total" id="premium_total" required placeholder="0.00">
                </div>
                <div class="form-group">
                    <label>Страховая сумма (₽) *:</label>
                    <input type="number" step="0.01" name="insurance_sum" id="insurance_sum" required placeholder="0.00">
                </div>
                <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px;">
                    <button type="button" class="btn" onclick="closeModal()">Отмена</button>
                    <button type="submit" class="btn btn-success">✅ Оформить договор</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function showAddForm() {
            document.getElementById('addModal').style.display = 'block';
            const form = document.querySelector('#addModal form');
            if (form) form.reset();
        }

        function closeModal() {
            document.getElementById('addModal').style.display = 'none';
        }

        function validateContractForm() {
            const startDate = document.getElementById('start_date').value;
            const endDate = document.getElementById('end_date').value;
            const premium = document.getElementById('premium_total').value;
            const insuranceSum = document.getElementById('insurance_sum').value;
            const clientId = document.getElementById('client_id').value;
            const programId = document.getElementById('program_id').value;

            if (!clientId) { alert('Пожалуйста, выберите клиента'); return false; }
            if (!programId) { alert('Пожалуйста, выберите программу страхования'); return false; }
            if (!startDate || !endDate) { alert('Пожалуйста, укажите даты'); return false; }
            if (new Date(startDate) >= new Date(endDate)) { alert('Дата окончания должна быть позже даты начала'); return false; }
            if (!premium || premium <= 0) { alert('Страховая премия должна быть больше 0'); return false; }
            if (!insuranceSum || insuranceSum <= 0) { alert('Страховая сумма должна быть больше 0'); return false; }
            if (parseFloat(premium) >= parseFloat(insuranceSum)) { alert('Страховая премия не может быть больше или равна страховой сумме'); return false; }
            return true;
        }

        window.onclick = function(event) {
            const modal = document.getElementById('addModal');
            if (event.target === modal) modal.style.display = 'none';
        }

        document.addEventListener('DOMContentLoaded', function() {
            const today = new Date().toISOString().split('T')[0];
            const startDateInput = document.getElementById('start_date');
            if (startDateInput) {
                startDateInput.min = today;
                startDateInput.addEventListener('change', function() {
                    const endDateInput = document.getElementById('end_date');
                    if (endDateInput) {
                        endDateInput.min = this.value;
                        if (endDateInput.value && endDateInput.value <= this.value) endDateInput.value = '';
                    }
                });
            }

            // Автозаполнение суммы премии при выборе программы
            const programSelect = document.getElementById('program_id');
            const premiumInput = document.getElementById('premium_total');
            if (programSelect && premiumInput) {
                programSelect.addEventListener('change', function() {
                    const selectedOption = this.options[this.selectedIndex];
                    const price = selectedOption.getAttribute('data-price');
                    if (price && price > 0) premiumInput.value = price;
                });
            }
        });
    </script>
</body>
</html>