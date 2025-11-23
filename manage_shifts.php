<?php
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'Admin') {
    header("Location: index.html");
    exit;
}
include("includes/db_config.php");


if (isset($_POST['add'])) {
    $date = $_POST['date'];
    $timeSlot = $_POST['time_slot'];
    $createdBy = $_SESSION['user_id'];

    $stmt = $conn->prepare("INSERT INTO Shift (Date, TimeSlot, CreatedBy) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $date, $timeSlot, $createdBy);
    $stmt->execute();
    $stmt->close();

    header("Location: manage_shifts.php");
    exit;
}

if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM Shift WHERE ShiftID=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();

    header("Location: manage_shifts.php");
    exit;
}


$result = $conn->query("SELECT * FROM Shift ORDER BY Date, TimeSlot");
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Manage Shifts</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body {
    background-color: #f5f5dc;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}
h2 {
    color: #7b8d6d;
    margin-bottom: 1.5rem;
}
.card {
    background: #ffffff;
    border-radius: 1rem;
    border: 1px solid #c4c8b5;
    box-shadow: 0 8px 16px rgba(107, 142, 35, 0.2);
    padding: 2rem;
    margin-bottom: 2rem;
}
.table thead {
    background-color: #c4c8b5;
    color: #fff;
}
.btn-primary {
    background-color: #7b8d6d;
    border-color: #7b8d6d;
}
.btn-primary:hover { background-color: #6a7b5d; }
.btn-secondary {
    background-color: #c4c8b5; border-color: #c4c8b5; color: #fff;
}
.btn-secondary:hover { background-color: #aeb18d; }
.btn-danger { background-color: #c94c4c; border-color: #c94c4c; color: #fff; }
.btn-danger:hover { background-color: #a93b3b; }
</style>
</head>
<body class="container my-4">

<div class="card">
    <h2>Manage Shifts</h2>
    <form method="post" class="row g-3 mb-4">
        <div class="col-md-4">
            <input type="date" name="date" class="form-control" required>
        </div>
        <div class="col-md-4">
            <input type="text" name="time_slot" placeholder="Time Slot (e.g. Morning, 09:00-13:00)" class="form-control" required>
        </div>
        <div class="col-md-4">
            <button type="submit" name="add" class="btn btn-primary w-100">Add Shift</button>
        </div>
    </form>

    <div class="table-responsive">
    <table class="table table-bordered align-middle">
        <thead>
            <tr>
                <th>Shift ID</th>
                <th>Date</th>
                <th>Time Slot</th>
                <th>Created By</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php while($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?= $row['ShiftID'] ?></td>
                <td><?= htmlspecialchars($row['Date']) ?></td>
                <td><?= htmlspecialchars($row['TimeSlot']) ?></td>
                <td><?= htmlspecialchars($row['CreatedBy']) ?></td>
                <td>
                    <a href="?delete=<?= $row['ShiftID'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this shift?')">Delete</a>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    </div>

    <a href="admin_dashboard.php" class="btn btn-secondary mt-3">Back to Dashboard</a>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
