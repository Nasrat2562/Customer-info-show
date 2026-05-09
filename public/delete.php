<?php
require '../app/Database.php';
$db = Database::getConnection();

$id = $_GET['id'] ?? null;
$view = $_GET['type'] ?? 'valid';
$table = ($view === 'invalid') ? 'invalid_customers' : 'valid_customers';

if ($id) {
    $stmt = $db->prepare("DELETE FROM $table WHERE id = ?");
    $stmt->execute([$id]);

    // Clear Cache
    $redis = new Redis();
    $redis->connect('redis', 6379);
    $redis->flushAll();
}

header("Location: index.php?type=$view&success=deleted");