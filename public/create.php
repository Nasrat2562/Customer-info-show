<?php
require '../app/Database.php';
require '../app/Validator.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = Database::getConnection();
    $v = new Validator($db);
    $fName = $_POST['first_name']; $lName = $_POST['last_name'];
    $email = $_POST['email']; $phone = $_POST['phone'];

    if ($v->isValid($email, $phone) && !$v->isDuplicate($email)) {
        $stmt = $db->prepare("INSERT INTO valid_customers (first_name, last_name, email, phone) VALUES (?,?,?,?)");
        $stmt->execute([$fName, $lName, $email, $phone]);
        $target = "valid";
    } else {
        $stmt = $db->prepare("INSERT INTO invalid_customers (first_name, last_name, email, phone, error_message) VALUES (?,?,?,?,?)");
        $stmt->execute([$fName, $lName, $email, $phone, "Manual Entry: Failed Validation"]);
        $target = "invalid";
    }

    $redis = new Redis(); $redis->connect('redis', 6379); $redis->flushAll();
    header("Location: index.php?type=$target");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head><link rel="stylesheet" href="styles.css"></head>
<body>
    <div class="main-content">
        <div class="form-container">
            <h2>Add New Customer</h2>
            <form method="POST">
                <div class="form-group"><label>First Name</label><input type="text" name="first_name" required></div>
                <div class="form-group"><label>Last Name</label><input type="text" name="last_name" required></div>
                <div class="form-group"><label>Email</label><input type="text" name="email" required></div>
                <div class="form-group"><label>Phone</label><input type="text" name="phone" required></div>
                <button type="submit" class="btn btn-add">Save Customer</button>
                <a href="index.php">Cancel</a>
            </form>
        </div>
    </div>
</body>
</html>