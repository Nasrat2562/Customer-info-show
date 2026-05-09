<?php
require_once '../app/Database.php';
$db = Database::getConnection();

// Get statistics
$stats = [];
$stats['total_duplicates'] = $db->query("SELECT COUNT(*) FROM duplicate_attempts")->fetchColumn();
$stats['email_duplicates'] = $db->query("SELECT COUNT(*) FROM duplicate_attempts WHERE duplicate_type = 'email'")->fetchColumn();
$stats['phone_duplicates'] = $db->query("SELECT COUNT(*) FROM duplicate_attempts WHERE duplicate_type = 'phone'")->fetchColumn();
$stats['both_duplicates'] = $db->query("SELECT COUNT(*) FROM duplicate_attempts WHERE duplicate_type = 'both'")->fetchColumn();

// Get duplicate attempts with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

$stmt = $db->prepare("
    SELECT d.*, 
           CONCAT(v.first_name, ' ', v.last_name) as original_name,
           v.email as original_email,
           v.phone as original_phone
    FROM duplicate_attempts d
    LEFT JOIN valid_customers v ON d.original_record_id = v.id
    ORDER BY d.attempted_at DESC
    LIMIT :limit OFFSET :offset
");
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$duplicates = $stmt->fetchAll();

$total = $stats['total_duplicates'];
$totalPages = ceil($total / $limit);
?>
<!DOCTYPE html>
<html>
<head>
    <link rel="stylesheet" href="styles.css">
    <title>Duplicate Attempts | DataOps Pro</title>
    <style>
        .duplicate-stats {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-box {
            background: white;
            padding: 20px;
            border-radius: 12px;
            text-align: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .stat-box h3 {
            font-size: 28px;
            margin-bottom: 10px;
        }
        .stat-box.email { border-top: 4px solid #3498db; }
        .stat-box.phone { border-top: 4px solid #e67e22; }
        .stat-box.both { border-top: 4px solid #e74c3c; }
        .stat-box.total { border-top: 4px solid #2ecc71; }
        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: bold;
        }
        .badge-email { background: #3498db; color: white; }
        .badge-phone { background: #e67e22; color: white; }
        .badge-both { background: #e74c3c; color: white; }
        .original-ref {
            font-size: 12px;
            color: #7f8c8d;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <div class="sidebar">
            <div class="logo">DataOps Pro</div>
            <a href="index.php?type=valid">✅ Valid Data</a>
            <a href="index.php?type=invalid">❌ Invalid Data</a>
            <a href="duplicates.php" class="active">🔄 Duplicate Attempts</a>
            <hr>
            <a href="create.php" class="btn btn-add">+ Add Customer</a>
        </div>
        
        <div class="main-content">
            <div class="top-bar">
                <h1>Duplicate Attempts Monitor</h1>
            </div>

            <!-- Statistics Cards -->
            <div class="duplicate-stats">
                <div class="stat-box total">
                    <h3><?= number_format($stats['total_duplicates']) ?></h3>
                    <p>Total Duplicate Attempts</p>
                </div>
                <div class="stat-box email">
                    <h3><?= number_format($stats['email_duplicates']) ?></h3>
                    <p>Email Duplicates</p>
                </div>
                <div class="stat-box phone">
                    <h3><?= number_format($stats['phone_duplicates']) ?></h3>
                    <p>Phone Duplicates</p>
                </div>
                <div class="stat-box both">
                    <h3><?= number_format($stats['both_duplicates']) ?></h3>
                    <p>Both Email & Phone</p>
                </div>
            </div>

            <!-- Duplicate Attempts Table -->
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Attempted Customer</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Duplicate Type</th>
                            <th>Original Record</th>
                            <th>Attempted At</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($duplicates)): ?>
                            <tr>
                                <td colspan="7" style="text-align: center;">No duplicate attempts recorded yet</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach($duplicates as $dup): ?>
                                <tr>
                                    <td>#<?= $dup['id'] ?></td>
                                    <td><?= htmlspecialchars($dup['first_name'] . ' ' . $dup['last_name']) ?></td>
                                    <td><?= htmlspecialchars($dup['email']) ?></td>
                                    <td><?= htmlspecialchars($dup['phone']) ?></td>
                                    <td>
                                        <span class="badge badge-<?= $dup['duplicate_type'] ?>">
                                            <?= ucfirst($dup['duplicate_type']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($dup['original_record_id']): ?>
                                            <strong>ID: <?= $dup['original_record_id'] ?></strong><br>
                                            <div class="original-ref">
                                                <?= htmlspecialchars($dup['original_name'] ?? 'N/A') ?><br>
                                                <small><?= htmlspecialchars($dup['original_email'] ?? '') ?></small><br>
                                                <small><?= htmlspecialchars($dup['original_phone'] ?? '') ?></small>
                                            </div>
                                        <?php else: ?>
                                            N/A
                                        <?php endif; ?>
                                    </td>
                                    <td><?= date('Y-m-d H:i:s', strtotime($dup['attempted_at'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="pagination">
                <?php if($page > 1): ?> 
                    <a href="?page=<?= $page-1 ?>">Prev</a> 
                <?php endif; ?>
                <span>Page <?= $page ?> of <?= $totalPages ?></span>
                <?php if($page < $totalPages): ?> 
                    <a href="?page=<?= $page+1 ?>">Next</a> 
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>