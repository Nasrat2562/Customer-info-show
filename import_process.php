<?php
require_once 'app/Database.php';
require_once 'app/Validator.php';

$file = 'data/1M-customers.txt';
$threads = 4; 
$pids = [];

echo "🚀 Starting Parallel Import (4 Threads)...\n";

for ($i = 0; $i < $threads; $i++) {
    $pid = pcntl_fork();
    if ($pid == -1) die("Could not fork");

    if ($pid) {
        $pids[] = $pid;
    } else {
        $db = Database::getConnection();
        $v = new Validator($db);
        $handle = fopen($file, 'r');
        $rowNum = 0;
        $db->beginTransaction();

        while (($data = fgetcsv($handle, 0, "\t")) !== false) {
            if ($rowNum % $threads == $i) {
                $email = $data[6] ?? '';
                $phone = $data[5] ?? '';

                if ($v->isValid($email, $phone) && !$v->isDuplicate($email)) {
                    $stmt = $db->prepare("INSERT INTO valid_customers (first_name, last_name, email, phone, ip) VALUES (?,?,?,?,?)");
                    $stmt->execute([$data[0], $data[1], $email, $phone, $data[7] ?? '']);
                } else {
                    $stmt = $db->prepare("INSERT INTO invalid_customers (first_name, last_name, email, phone, error_message) VALUES (?,?,?,?,?)");
                    $stmt->execute([$data[0]??'', $data[1]??'', $email, $phone, "Check Format/Duplicate"]);
                }
            }
            if ($rowNum % 2500 == 0) { $db->commit(); $db->beginTransaction(); }
            $rowNum++;
        }
        $db->commit();
        exit(0);
    }
}

foreach ($pids as $pid) { pcntl_waitpid($pid, $status); }
echo "✅ Import Finished Successfully!\n";