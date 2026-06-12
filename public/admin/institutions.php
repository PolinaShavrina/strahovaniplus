<?php
require_once dirname(dirname(__DIR__)) . '/config/database.php';
?>
<?php
$pdo = getConnection();

// Добавление ЛПУ
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    try {
        $sql = "INSERT INTO medical_institutions (name, address, phone, email, license_number, is_partner)
                VALUES (:name, :address, :phone, :email, :license, :is_partner)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':name' => $_POST['name'],
            ':address' => $_POST['address'],
            ':phone' => $_POST['phone'],
            ':email' => $_POST['email'],
            ':license' => $_POST['license_number'],
            ':is_partner' => isset($_POST['is_partner']) ? 1 : 0
        ]);
        $success = "ЛПУ успешно добавлено!";
    } catch (Exception $e) {
        $error = "Ошибка: " . $e->getMessage();
    }
}

// Получение списка ЛПУ
$institutions = $pdo->query("SELECT * FROM medical_institutions ORDER BY created_at DESC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>ЛПУ - Страховая компания</title>
    <link rel="stylesheet" href="admin.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>Лечебно-профилактические учреждения</h1>
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
                <h2>Список ЛПУ</h2>
                <button class="btn btn-primary" onclick="showAddForm()">+ Добавить ЛПУ</button>
            </div>

            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Название</th>
                        <th>Адрес</th>
                        <th>Телефон</th>
                        <th>Партнер</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($institutions as $inst): ?>
                    <tr>
                        <td><?php echo $inst['institution_id']; ?></td>
                        <td><?php echo htmlspecialchars($inst['name']); ?></td>
                        <td><?php echo htmlspecialchars($inst['address']); ?></td>
                        <td><?php echo htmlspecialchars($inst['phone']); ?></td>
                        <td><?php echo $inst['is_partner'] ? '✅ Да' : '❌ Нет'; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </main>

        <footer>
            <p>&copy; 2026 Страховая медицинская компания. Все права защищены.</p>
        </footer>
    </div>

    <!-- Модальное окно -->
    <div id="addModal" class="modal" style="display:none;">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h3>Добавление ЛПУ</h3>
            <form method="POST" action="">
                <input type="hidden" name="action" value="add">
                <div class="form-group">
                    <label>Название:</label>
                    <input type="text" name="name" required>
                </div>
                <div class="form-group">
                    <label>Адрес:</label>
                    <textarea name="address"></textarea>
                </div>
                <div class="form-group">
                    <label>Телефон:</label>
                    <input type="text" name="phone">
                </div>
                <div class="form-group">
                    <label>Email:</label>
                    <input type="email" name="email">
                </div>
                <div class="form-group">
                    <label>Номер лицензии:</label>
                    <input type="text" name="license_number">
                </div>
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="is_partner" checked> Партнер ДМС
                    </label>
                </div>
                <button type="submit" class="btn btn-success">Сохранить</button>
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