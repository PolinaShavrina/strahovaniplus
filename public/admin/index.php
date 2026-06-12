<?php
require_once dirname(dirname(__DIR__)) . '/config/database.php';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Страховая медицинская компания - ДМС</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="admin.css?v=<?= time()?>">
</head>
<body>
    <div class="container">
        <header>
            <h1>СтрахованиеPlus</h1>
            <nav>
                <a href="../index.php">Основная страница</a>
                <a href="index.php">Главная</a>
                <a href="clients.php">Клиенты</a>
                <a href="contracts.php">Договоры</a>
                <a href="referrals.php">Направления</a>
                <a href="institutions.php">ЛПУ</a>
                <a href="reports.php">Отчеты</a>
            </nav>
        </header>

        <main>
            <div class="dashboard">
                <div class="stats">
                    <div class="stat-card">
                        <h3>Всего клиентов</h3>
                        <p class="stat-number">
                            <?php
                            try {
                                $pdo = getConnection();
                                $stmt = $pdo->query("SELECT COUNT(*) as count FROM clients");
                                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                                echo $row['count'] ?? 0;
                            } catch (Exception $e) {
                                echo "0";
                            }
                            ?>
                        </p>
                    </div>
                    <div class="stat-card">
                        <h3>Активных договоров</h3>
                        <p class="stat-number">
                            <?php
                            try {
                                $pdo = getConnection();
                                $stmt = $pdo->query("SELECT COUNT(*) as count FROM contracts WHERE status = 'active'");
                                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                                echo $row['count'] ?? 0;
                            } catch (Exception $e) {
                                echo "0";
                            }
                            ?>
                        </p>
                    </div>
                    <div class="stat-card">
                        <h3>Выданных направлений</h3>
                        <p class="stat-number">
                            <?php
                            try {
                                $pdo = getConnection();
                                $stmt = $pdo->query("SELECT COUNT(*) as count FROM referrals WHERE status = 'issued'");
                                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                                echo $row['count'] ?? 0;
                            } catch (Exception $e) {
                                echo "0";
                            }
                            ?>
                        </p>
                    </div>
                    <div class="stat-card">
                        <h3>Партнеров ЛПУ</h3>
                        <p class="stat-number">
                            <?php
                            try {
                                $pdo = getConnection();
                                $stmt = $pdo->query("SELECT COUNT(*) as count FROM medical_institutions WHERE is_partner = 1");
                                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                                echo $row['count'] ?? 0;
                            } catch (Exception $e) {
                                echo "0";
                            }
                            ?>
                        </p>
                    </div>
                </div>

                <div class="recent-activities">
                    <h2>Последние договоры</h2>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>№ договора</th>
                                <th>Клиент</th>
                                <th>Программа</th>
                                <th>Дата начала</th>
                                <th>Статус</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            try {
                                $pdo = getConnection();
                                $sql = "SELECT c.contract_number, c.policy_number, c.start_date, c.status,
                                       CONCAT(cl.last_name, ' ', cl.first_name) as client_name,
                                       ip.program_name
                                       FROM contracts c
                                       JOIN clients cl ON c.client_id = cl.client_id
                                       JOIN insurance_programs ip ON c.program_id = ip.program_id
                                       ORDER BY c.created_at DESC LIMIT 5";
                                $stmt = $pdo->query($sql);
                                while($row = $stmt->fetch(PDO::FETCH_ASSOC)):
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['contract_number']); ?></td>
                                <td><?php echo htmlspecialchars($row['client_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['program_name']); ?></td>
                                <td><?php echo date('d.m.Y', strtotime($row['start_date'])); ?></td>
                                <td><span class="status <?php echo htmlspecialchars($row['status']); ?>">
                                    <?php
                                    $status_labels = ['active' => 'Активен', 'pending' => 'Ожидает', 'terminated' => 'Расторгнут', 'expired' => 'Истек'];
                                    echo $status_labels[$row['status']] ?? $row['status'];
                                    ?>
                                </span></td>
                            </tr>
                            <?php
                                endwhile;
                            } catch (Exception $e) {
                                echo "<tr><td colspan='5'>Ошибка загрузки данных: " . $e->getMessage() . "</td></tr>";
                            }
                            ?>
                        </tbody>
                     </div>
            </div>
        </main>

        <footer>
            <p>&copy; 2026 Страховая медицинская компания. Все права защищены.</p>
        </footer>
    </div>
    <script src="js/main.js"></script>
</body>
</html>