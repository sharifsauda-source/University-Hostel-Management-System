<?php
include("includes/db_config.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $StaffID = $_POST['StaffID'];
    $StaffName = $_POST['StaffName'];
    $ShiftID = $_POST['ShiftID'];
    $Password = $_POST['Password'];

    $stmt = $conn->prepare("INSERT INTO Staff (StaffID, StaffName, ShiftID, Password) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isis", $StaffID, $StaffName, $ShiftID, $Password);

    if ($stmt->execute()) {
        echo "<script>alert('Staff registered successfully'); window.location.href='index.html';</script>";
    } else {
        echo "Error: " . $stmt->error;
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Register Staff</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
</head>
<body>
<div class="container mt-5">
    <h2>Staff Registration</h2>
    <form method="post">
        <input type="number" name="StaffID" placeholder="Staff ID" class="form-control" required><br>
        <input type="text" name="StaffName" placeholder="Name" class="form-control" required><br>
        <input type="number" name="ShiftID" placeholder="Shift ID" class="form-control" required><br>
        <input type="password" name="Password" placeholder="Password" class="form-control" required><br>
        <button type="submit" class="btn btn-info">Register Staff</button>
    </form>
</div>
</body>
</html>
