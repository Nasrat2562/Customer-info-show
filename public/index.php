<?php
require_once '../app/Database.php';
$db = Database::getConnection();

$view = $_GET['type'] ?? 'valid';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$table = ($view === 'invalid') ? 'invalid_customers' : 'valid_customers';
$limit = 15;

// --- Redis Caching Integration (Requirement #2) ---
$redis = new Redis();
$redis->connect('redis', 6379);

$countKey = "count_" . $view;
$pageKey = "page_" . $view . "_" . $page;

$total = $redis->get($countKey);
$cachedData = $redis->get($pageKey);

if ($total === false || $cachedData === false) {
    // Cache Miss: Query Database
    $total = $db->query("SELECT COUNT(*) FROM $table")->fetchColumn();
    
    $offset = ($page - 1) * $limit;
    $stmt = $db->prepare("SELECT * FROM $table ORDER BY id DESC LIMIT :limit OFFSET :offset");
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Save to Redis for 60 seconds
    $redis->setex($countKey, 60, $total);
    $redis->setex($pageKey, 60, serialize($rows));
    $source = "MySQL Database";
} else {
    // Cache Hit: Load from Redis
    $rows = unserialize($cachedData);
    $source = "Redis Cache";
}

$totalPages = ceil($total / $limit);
?>
<!DOCTYPE html>
<html>
<head>
    <link rel="stylesheet" href="styles.css">
    <title>DataOps Pro Dashboard</title>
</head>
<body>
    <div class="dashboard-container">
        <div class="sidebar">
            <div class="logo">DataOps Pro</div>
            <a href="index.php?type=valid" class="<?= $view=='valid'?'active':'' ?>">✅ Valid Data</a>
            <a href="index.php?type=invalid" class="<?= $view=='invalid'?'active':'' ?>">❌ Invalid Data</a>
            <hr>
            <a href="create.php" class="btn btn-add">+ Add Customer</a>
        </div>
        
        <div class="main-content">
            <div class="top-bar">
                <h1><?= ucfirst($view) ?> Records</h1>
                <div class="stats-card">
                    <span class="count"><?= number_format($total) ?></span>
                    <span class="label">Total (Source: <?= $source ?>)</span>
                </div>
            </div>

            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th><th>Name</th><th>Email</th><th>Phone</th><th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($rows as $r): ?>
                        <tr>
                            <td>#<?= $r['id'] ?></td>
                            <td><?= htmlspecialchars($r['first_name'] . ' ' . $r['last_name']) ?></td>
                            <td><?= htmlspecialchars($r['email']) ?></td>
                            <td><?= htmlspecialchars($r['phone']) ?></td>
                            <td>
                                <a href="edit.php?id=<?= $r['id'] ?>&type=<?= $view ?>" class="link-edit">Edit</a> | 
                                <a href="delete.php?id=<?= $r['id'] ?>&type=<?= $view ?>" class="link-delete" onclick="return confirm('Delete?')">Delete</a>
                                <?php if($view == 'invalid'): ?>
                                    <br><small class="error-text"><?= $r['error_message'] ?></small>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="pagination">
                <?php if($page > 1): ?> <a href="?type=<?= $view ?>&page=<?= $page-1 ?>">Prev</a> <?php endif; ?>
                <span>Page <?= $page ?> of <?= $totalPages ?></span>
                <?php if($page < $totalPages): ?> <a href="?type=<?= $view ?>&page=<?= $page+1 ?>">Next</a> <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>