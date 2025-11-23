<?php
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'Admin') {
    header("Location: index.html");
    exit;
}

include("includes/db_config.php");
$adminID = $_SESSION['user_id'];

// --- Handle approval/decline ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $refundID = $_POST['refundID'];
    if (isset($_POST['approve_refund'])) {
        $stmt = $conn->prepare("UPDATE Refund SET ApprovedBy=? WHERE RefundID=?");
        $stmt->bind_param("si", $adminID, $refundID);
        $stmt->execute();
        $stmt->close();
        header("Location: approve_refunds.php?success=approved");
        exit;
    }
    if (isset($_POST['decline_refund'])) {
        $stmt = $conn->prepare("DELETE FROM Refund WHERE RefundID=?");
        $stmt->bind_param("i", $refundID);
        $stmt->execute();
        $stmt->close();
        header("Location: approve_refunds.php?success=declined");
        exit;
    }
}

// --- Fetch pending refunds ---
$result = $conn->query("
    SELECT r.RefundID, r.PaymentID, r.Amount, r.Reason, p.StudentID
    FROM Refund r
    JOIN Payment p ON r.PaymentID = p.PaymentID
    WHERE r.ApprovedBy IS NULL
    ORDER BY r.RefundID ASC
");
$pendingRefunds = $result->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Approve Refunds - Admin</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body {
    background-color: #fefcf0;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}
h4 {
    color: #6b7d5c;
    margin-bottom: 1rem;
}
.table thead {
    background-color: #6b7d5c;
    color: #fff;
}
.btn-success {
    background-color: #6b7d5c;
    border-color: #6b7d5c;
}
.btn-success:hover {
    background-color: #5a6b4d;
}
.btn-danger {
    background-color: #c44a4a;
    border-color: #c44a4a;
}
.btn-danger:hover {
    background-color: #a63b3b;
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
    margin-bottom: 2rem;
}
</style>
</head>
<body>
<div class="container mt-4">
    <h4>Pending Refunds</h4>

    <?php if(isset($_GET['success'])): ?>
        <div class="alert alert-<?=$_GET['success'] === 'approved' ? 'success' : 'warning'?>">
            <?=$_GET['success'] === 'approved' ? 'Refund approved successfully!' : 'Refund declined successfully!'?>
        </div>
    <?php endif; ?>

    <div class="card">
    <?php if(count($pendingRefunds) > 0): ?>
        <div class="table-responsive">
            <table class="table table-bordered table-striped mb-0">
                <thead>
                    <tr>
                        <th>Refund ID</th>
                        <th>Student ID</th>
                        <th>Payment ID</th>
                        <th>Amount</th>
                        <th>Reason</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach($pendingRefunds as $r): ?>
                    <tr>
                        <td><?=htmlspecialchars($r['RefundID'])?></td>
                        <td><?=htmlspecialchars($r['StudentID'])?></td>
                        <td><?=htmlspecialchars($r['PaymentID'])?></td>
                        <td><?=htmlspecialchars($r['Amount'])?></td>
                        <td><?=htmlspecialchars($r['Reason'])?></td>
                        <td>
                            <div class="d-flex gap-2">
                                <form method="post" class="m-0">
                                    <input type="hidden" name="refundID" value="<?=$r['RefundID']?>">
                                    <input type="hidden" name="approve_refund" value="1">
                                    <button type="submit" class="btn btn-success btn-sm">Approve</button>
                                </form>
                                <form method="post" class="m-0">
                                    <input type="hidden" name="refundID" value="<?=$r['RefundID']?>">
                                    <input type="hidden" name="decline_refund" value="1">
                                    <button type="submit" class="btn btn-danger btn-sm">Decline</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="alert alert-info mb-0">No pending refunds.</div>
    <?php endif; ?>
    </div>

    <a href="admin_dashboard.php" class="btn btn-secondary mt-3">Back to Dashboard</a>
</div>
</body>
</html>
