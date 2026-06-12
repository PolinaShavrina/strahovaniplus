<?php
require_once dirname(dirname(__DIR__)) . '/config/database.php';
$pdo = getConnection();

// Добавление клиента (только физ. лица, согласно структуре БД)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    try {
        $sql = "INSERT INTO clients (last_name, first_name, middle_name, birth_date, passport_series, passport_number, phone, email, address)
                VALUES (:last_name, :first_name, :middle_name, :birth_date, :passport_series, :passport_number, :phone, :email, :address)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':last_name' => $_POST['last_name'] ?? '',
            ':first_name' => $_POST['first_name'] ?? '',
            ':middle_name' => $_POST['middle_name'] ?? null,
            ':birth_date' => $_POST['birth_date'] ?? null,
            ':passport_series' => $_POST['passport_series'] ?? null,
            ':passport_number' => $_POST['passport_number'] ?? null,
            ':phone' => $_POST['phone'] ?? null,
            ':email' => $_POST['email'] ?? null,
            ':address' => $_POST['address'] ?? null
        ]);
        $success = "Клиент успешно добавлен!";
    } catch (Exception $e) {
        $error = "Ошибка: " . $e->getMessage();
    }
}

// Удаление клиента
if (isset($_GET['delete'])) {
    try {
        $check = $pdo->prepare("SELECT COUNT(*) FROM contracts WHERE client_id = ?");
        $check->execute([$_GET['delete']]);
        if ($check->fetchColumn() > 0) {
            $error = "Невозможно удалить клиента, так как у него есть договоры!";
        } else {
            $stmt = $pdo->prepare("DELETE FROM clients WHERE client_id = ?");
            $stmt->execute([$_GET['delete']]);
            $success = "Клиент удален!";
        }
    } catch (Exception $e) {
        $error = "Ошибка удаления: " . $e->getMessage();
    }
}

// Поиск
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

if (!empty($search)) {
    $sql = "SELECT * FROM clients WHERE
            last_name LIKE :search OR
            first_name LIKE :search OR
            phone LIKE :search OR
            email LIKE :search
            ORDER BY client_id DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':search' => "%$search%"]);
} else {
    $sql = "SELECT * FROM clients ORDER BY client_id DESC";
    $stmt = $pdo->query($sql);
}
$clients = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Клиенты - Страховая компания</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="admin.css?v=<?= time() ?>">
</head>
<body>
    <div class="container">
        <header>
            <h1>👥 Управление клиентами</h1>
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
                <h2>Список клиентов</h2>
                <button class="btn btn-primary" onclick="showAddForm()">+ Добавить клиента</button>
            </div>

            <div class="search-box">
                <form method="GET" action="" style="display: flex; gap: 10px;">
                    <input type="text" name="search" class="search-input" placeholder="Поиск по фамилии, имени, телефону или email..."
                           value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit" class="btn btn-primary">Найти</button>
                    <?php if (!empty($search)): ?>
                        <a href="clients.php" class="btn">Сбросить</a>
                    <?php endif; ?>
                </form>
            </div>

            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Фамилия</th>
                        <th>Имя</th>
                        <th>Отчество</th>
                        <th>Телефон</th>
                        <th>Email</th>
                        <th>Дата рождения</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($clients) > 0): ?>
                        <?php foreach($clients as $client): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($client['client_id'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($client['last_name'] ?? '—'); ?></td>
                            <td><?php echo htmlspecialchars($client['first_name'] ?? '—'); ?></td>
                            <td><?php echo htmlspecialchars($client['middle_name'] ?? '—'); ?></td>
                            <td><?php echo htmlspecialchars($client['phone'] ?? '—'); ?></td>
                            <td><?php echo htmlspecialchars($client['email'] ?? '—'); ?></td>
                            <td><?php echo !empty($client['birth_date']) ? date('d.m.Y', strtotime($client['birth_date'])) : '—'; ?></td>
                            <td>
                                <a href="client_details.php?id=<?php echo $client['client_id']; ?>" class="btn btn-small">📋 Просмотр</a>
                                <a href="?delete=<?php echo $client['client_id']; ?>" class="btn btn-small btn-danger" onclick="return confirm('Удалить клиента?')">🗑 Удалить</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 40px;">
                                <?php if (!empty($search)): ?>
                                    Клиенты не найдены по запросу "<?php echo htmlspecialchars($search); ?>"
                                <?php else: ?>
                                    Клиенты отсутствуют. Нажмите "Добавить клиента"
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

    <!-- Модальное окно добавления -->
    <div id="addModal" class="modal" style="display:none;">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h3>Добавление нового клиента</h3>
            <form method="POST" action="">
                <input type="hidden" name="action" value="add">
                <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="form-group">
                        <label>Фамилия *:</label>
                        <input type="text" name="last_name" required>
                    </div>
                    <div class="form-group">
                        <label>Имя *:</label>
                        <input type="text" name="first_name" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>Отчество:</label>
                    <input type="text" name="middle_name">
                </div>
                <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="form-group">
                        <label>Дата рождения:</label>
                        <input type="date" name="birth_date">
                    </div>
                    <div class="form-group">
                        <label>Телефон:</label>
                        <input type="tel" name="phone">
                    </div>
                </div>
                <div class="form-group">
                    <label>Email:</label>
                    <input type="email" name="email">
                </div>
                <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="form-group">
                        <label>Серия паспорта:</label>
                        <input type="text" name="passport_series" placeholder="0000">
                    </div>
                    <div class="form-group">
                        <label>Номер паспорта:</label>
                        <input type="text" name="passport_number" placeholder="000000">
                    </div>
                </div>
                <div class="form-group">
                    <label>Адрес:</label>
                    <textarea name="address" rows="2"></textarea>
                </div>
                <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px;">
                    <button type="button" class="btn" onclick="closeModal()">Отмена</button>
                    <button type="submit" class="btn btn-success">💾 Сохранить</button>
                </div>
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