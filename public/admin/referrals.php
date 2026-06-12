<?php
require_once dirname(dirname(__DIR__)) . '/config/database.php';
?>
<?php
$pdo = getConnection();

// Получение данных для формы
$contracts = $pdo->query("SELECT contract_id, contract_number FROM contracts WHERE status = 'active'")->fetchAll();
$institutions = $pdo->query("SELECT institution_id, name FROM medical_institutions WHERE is_partner = 1")->fetchAll();
$services = $pdo->query("SELECT service_id, service_name FROM medical_services")->fetchAll();

// Добавление направления
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    try {
        $referral_number = 'REF-' . date('Ymd') . '-' . rand(1000, 9999);
        $sql = "INSERT INTO referrals (contract_id, institution_id, service_id, referral_number, issue_date, valid_until, diagnosis_code, status)
                VALUES (:contract_id, :institution_id, :service_id, :referral_number, :issue_date, :valid_until, :diagnosis_code, 'issued')";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':contract_id' => $_POST['contract_id'],
            ':institution_id' => $_POST['institution_id'],
            ':service_id' => $_POST['service_id'],
            ':referral_number' => $referral_number,
            ':issue_date' => $_POST['issue_date'],
            ':valid_until' => $_POST['valid_until'],
            ':diagnosis_code' => $_POST['diagnosis_code']
        ]);
        $success = "Направление №{$referral_number} успешно выдано!";
    } catch (Exception $e) {
        $error = "Ошибка: " . $e->getMessage();
    }
}

// Получение списка направлений
$sql = "SELECT r.*, c.contract_number, mi.name as institution_name, ms.service_name
        FROM referrals r
        JOIN contracts c ON r.contract_id = c.contract_id
        JOIN medical_institutions mi ON r.institution_id = mi.institution_id
        JOIN medical_services ms ON r.service_id = ms.service_id
        ORDER BY r.created_at DESC";
$referrals = $pdo->query($sql)->fetchAll();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Направления - Страховая компания</title>
    <link rel="stylesheet" href="admin.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>Управление направлениями</h1>
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
                <div class="alert success"><?php echo $success; ?></div>
            <?php endif; ?>

            <div class="page-header">
                <h2>Список направлений</h2>
                <button class="btn btn-primary" onclick="showAddForm()">+ Выдать направление</button>
            </div>

            <table class="data-table">
                <thead>
                    <tr>
                        <th>№ направления</th>
                        <th>Договор</th>
                        <th>ЛПУ</th>
                        <th>Услуга</th>
                        <th>Дата выдачи</th>
                        <th>Действительно до</th>
                        <th>Статус</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($referrals as $referral): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($referral['referral_number']); ?></td>
                        <td><?php echo htmlspecialchars($referral['contract_number']); ?></td>
                        <td><?php echo htmlspecialchars($referral['institution_name']); ?></td>
                        <td><?php echo htmlspecialchars($referral['service_name']); ?></td>
                        <td><?php echo $referral['issue_date']; ?></td>
                        <td><?php echo $referral['valid_until']; ?></td>
                        <td><span class="status <?php echo $referral['status']; ?>"><?php echo $referral['status']; ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </main>

        <footer>
            <p>&copy; 2026 Страховая медицинская компания. Все права защищены.</p>
        </footer>
    </div>

    <!-- Модальное окно добавления направления -->
    <div id="addModal" class="modal" style="display:none;">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h3>Выдача направления</h3>
            <form method="POST" action="">
                <input type="hidden" name="action" value="add">
                <div class="form-group">
                    <label>Договор:</label>
                    <select name="contract_id" required>
                        <option value="">Выберите договор</option>
                        <?php foreach($contracts as $contract): ?>
                            <option value="<?php echo $contract['contract_id']; ?>"><?php echo $contract['contract_number']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Лечебное учреждение:</label>
                    <select name="institution_id" required>
                        <option value="">Выберите ЛПУ</option>
                        <?php foreach($institutions as $institution): ?>
                            <option value="<?php echo $institution['institution_id']; ?>"><?php echo htmlspecialchars($institution['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Медицинская услуга:</label>
                    <select name="service_id" required>
                        <option value="">Выберите услугу</option>
                        <?php foreach($services as $service): ?>
                            <option value="<?php echo $service['service_id']; ?>"><?php echo htmlspecialchars($service['service_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Дата выдачи:</label>
                    <input type="date" name="issue_date" required>
                </div>
                <div class="form-group">
                    <label>Действительно до:</label>
                    <input type="date" name="valid_until" required>
                </div>
                <div class="form-group">
                    <label>Диагноз (код МКБ):</label>
                    <input type="text" name="diagnosis_code">
                </div>
                <button type="submit" class="btn btn-success">Выдать направление</button>
            </form>
        </div>
    </div>

    <script>
        function showAddForm() {
            document.getElementById('addModal').style.display = 'block';
        }

        function closeModal() {
            document.getElementById('addModal').style.display = 'none';
        }

        window.onclick = function(event) {
            const modal = document.getElementById('addModal');
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        }
    </script>
</body>
</html>