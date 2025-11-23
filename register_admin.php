<?php
include("includes/db_config.php");
$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $AdminID = $_POST['AdminID'];
    $AdminName = $_POST['AdminName'];
    $ContactInfo = $_POST['ContactInfo'];
    $Role = $_POST['Role'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $stmt = $conn->prepare("INSERT INTO Admin (AdminID, AdminName, ContactInfo, Role, Password) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("issss", $AdminID, $AdminName, $ContactInfo, $Role, $password);

    if ($stmt->execute()) {
        $message = "Admin registered successfully.";
    } else {
        $message = "Error: " . $stmt->error;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Register Admin</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
</head>
<body>
<div class="container mt-5">
    <h2 class="text-center">Register Admin (Private)</h2>
    <?php if (!empty($message)): ?>
        <div class="alert alert-info"><?= $message ?></div>
    <?php endif; ?>
    <form method="POST" class="w-75 mx-auto border p-4 rounded shadow">
        <div class="form-group mb-3">
            <label>Admin ID</label>
            <input type="text" name="AdminID" class="form-control" required>
        </div>
        <div class="form-group mb-3">
            <label>Name</label>
            <input type="text" name="AdminName" class="form-control" required>
        </div>
        <div class="form-group mb-3">
            <label>Contact Info</label>
            <input type="text" name="ContactInfo" class="form-control" required>
        </div>
        <div class="form-group mb-3">
            <label>Role</label>
            <input type="text" name="Role" class="form-control" value="Admin" required>
        </div>
        <div class="form-group mb-3">
            <label>Password</label>
            <input type="password" name="password" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-primary w-100">Register Admin</button>
    </form>
</div>
<script src="js/bootstrap.min.js"></script>
</body>
</html>
