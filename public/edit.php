<?php
require '../app/Database.php';
$db = Database::getConnection();
$id = $_GET['id'] ?? null;

if (!$id) header("Location: index.php");

$stmt = $db->prepare("SELECT * FROM valid_customers WHERE id = ?");
$stmt->execute([$id]);
$customer = $stmt->fetch(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $db->prepare("UPDATE valid_customers SET first_name=?, last_name=?, email=?, phone=? WHERE id=?");
    $stmt->execute([$_POST['first_name'], $_POST['last_name'], $_POST['email'], $_POST['phone'], $id]);

    // Clear Cache
    $redis = new Redis();
    $redis->connect('redis', 6379);
    $redis->flushAll();

    header("Location: index.php?success=updated");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head><link rel="stylesheet" href="styles.css"><title>Edit Customer</title></head>
<body>
    <div class="main-content">
        <h2>Edit Customer #<?= $id ?></h2>
        <form method="POST">
            <input type="text" name="first_name" value="<?= $customer['first_name'] ?>" required><br><br>
            <input type="text" name="last_name" value="<?= $customer['last_name'] ?>" required><br><br>
            <input type="email" name="email" value="<?= $customer['email'] ?>" required><br><br>
            <input type="text" name="phone" value="<?= $customer['phone'] ?>" required><br><br>
            <button type="submit" class="btn">Update</button>
            <a href="index.php">Back</a>
        </form>
    </div>
</body>
</html>