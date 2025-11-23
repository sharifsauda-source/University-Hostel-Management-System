<?php
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'Admin') {
    header("Location: index.html");
    exit;
}

include("includes/db_config.php");
$res = $conn->query("
    SELECT p.PaymentID, p.StudentID, s.StudentName, p.AmountPaid, p.PaymentMethod, p.PaymentDate
    FROM Payment p
    LEFT JOIN Student s ON p.StudentID = s.StudentID
    ORDER BY p.PaymentDate DESC
    LIMIT 200
");
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Payments - Admin</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body {
    background-color: #f9f9f9;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}
h3 {
    color: #4a5a3f;
    margin-bottom: 1rem;
}
.table thead {
    background-color: #4a5a3f;
    color: #fff;
}
.table-striped>tbody>tr:nth-of-type(odd) {
    background-color: #f0f3eb;
}
.btn-secondary {
    background-color: #c4c8b5;
    border-color: #c4c8b5;
    color: #fff;
}
.btn-secondary:hover {
    background-color: #aeb18d;
}
.card {
    background-color: #fff;
    border-radius: 0.5rem;
    padding: 1rem;
    box-shadow: 0 2px 6px rgba(0,0,0,0.1);
}
</style>
</head>
<body>
<div class="container mt-4">
    <h3>Payments</h3>
    <div class="card">
        <div class="table-responsive">
            <table class="table table-bordered table-striped mb-0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Student</th>
                        <th>Amount</th>
                        <th>Method</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($r = $res->fetch_assoc()): ?>
                        <tr>
                            <td><?=htmlspecialchars($r['PaymentID'])?></td>
                            <td><?=htmlspecialchars($r['StudentName'].' ('.$r['StudentID'].')')?></td>
                            <td><?=number_format($r['AmountPaid'],2)?></td>
                            <td><?=htmlspecialchars($r['PaymentMethod'])?></td>
                            <td><?=htmlspecialchars($r['PaymentDate'])?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
    <a href="admin_dashboard.php" class="btn btn-secondary mt-3">Back to Dashboard</a>
</div>
</body>
</html>
