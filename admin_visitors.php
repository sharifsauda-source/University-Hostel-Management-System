<?php
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'Admin') {
    header("Location: index.html");
    exit;
}
include("includes/db_config.php");

$sql = "SELECT vr.StudentID, s.StudentName, vr.RoomID, v.VisitorName, v.Purpose,
               vr.VisitDate, vr.EntryTime, vr.ExitTime
        FROM visitrecord vr
        JOIN Student s ON vr.StudentID = s.StudentID
        JOIN Visitor v ON vr.VisitorID = v.VisitorID
        ORDER BY vr.VisitDate DESC, vr.EntryTime DESC";

$result = $conn->query($sql);


?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Visitor Records - Admin</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body {
    background-color: #f8f9fa;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}
h3 {
    color: #3e4e3b;
    margin-bottom: 1rem;
}
.table thead {
    background-color: #3e4e3b;
    color: #fff;
}
.table-striped>tbody>tr:nth-of-type(odd) {
    background-color: #e9ecef;
}
.card {
    background-color: #fff;
    border-radius: 0.5rem;
    padding: 1rem;
    box-shadow: 0 2px 6px rgba(0,0,0,0.1);
}
.btn-secondary {
    background-color: #6c757d;
    border-color: #6c757d;
    color: #fff;
}
.btn-secondary:hover {
    background-color: #5a6268;
}
</style>
</head>
<body>
<div class="container mt-4">
    <h3>All Visitor Records</h3>
    <div class="card">
        <div class="table-responsive">
            <table class="table table-bordered table-striped mb-0">
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Room</th>
                        <th>Visitor</th>
                        <th>Purpose</th>
                        <th>Visit Date</th>
                        <th>Entry Time</th>
                        <th>Exit Time</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result && $result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?=htmlspecialchars($row['StudentName'])." (".$row['StudentID'].")"?></td>
                                <td><?=htmlspecialchars($row['RoomID'])?></td>
                                <td><?=htmlspecialchars($row['VisitorName'])?></td>
                                <td><?=htmlspecialchars($row['Purpose'])?></td>
                                <td><?=htmlspecialchars($row['VisitDate'])?></td>
                                <td><?=htmlspecialchars($row['EntryTime'])?></td>
                                <td><?=htmlspecialchars($row['ExitTime'] ?: '-')?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="7" class="text-center">No visitor records found</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <a href="admin_dashboard.php" class="btn btn-secondary mt-3">Back to Dashboard</a>
</div>
</body>
</html>
