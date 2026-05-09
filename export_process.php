<?php
require_once 'app/Database.php';
$start = microtime(true);
$db = Database::getConnection();

$batchSize = 100000;
$total = $db->query("SELECT COUNT(*) FROM valid_customers")->fetchColumn();
$parts = ceil($total / $batchSize);

if (!is_dir('data/exports')) mkdir('data/exports', 0777, true);

for ($i = 0; $i < $parts; $i++) {
    $offset = $i * $batchSize;
    $stmt = $db->prepare("SELECT * FROM valid_customers LIMIT $batchSize OFFSET $offset");
    $stmt->execute();
    
    $file = fopen("data/exports/part" . ($i+1) . ".csv", "w");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($file, $row);
    }
    fclose($file);
    echo "💾 Exported Part " . ($i+1) . "\n";
}

$end = microtime(true);
echo "⏱️ Total Process Execution Time: " . round($end - $start, 2) . " seconds\n";