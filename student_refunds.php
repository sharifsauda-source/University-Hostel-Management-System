<?php
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'Student') {
    header("Location: index.html");
    exit;
}

include("includes/db_config.php");
$studentID = $_SESSION['user_id'];

// Handle new refund request submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_refund'])) {
    $paymentID = $_POST['paymentID'];
    $amount = $_POST['amount'];
    $reason = trim($_POST['reason']);

    $stmt = $conn->prepare("
        INSERT INTO Refund (PaymentID, Amount, Reason, ApprovedBy)
        VALUES (?, ?, ?, NULL)
    ");
    $stmt->bind_param("ids", $paymentID, $amount, $reason);
    $stmt->execute();
    $stmt->close();

    header("Location: student_refunds.php?success=1");
    exit;
}

// Fetch refund history
$stmt = $conn->prepare("
    SELECT r.RefundID, r.PaymentID, r.Amount, r.Reason, r.ApprovedBy
    FROM Refund r
    JOIN Payment p ON r.PaymentID = p.PaymentID
    WHERE p.StudentID = ?
    ORDER BY r.RefundID DESC
");
$stmt->bind_param("s", $studentID);
$stmt->execute();
$result = $stmt->get_result();
$refunds = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Fetch payments for refund dropdown
$stmt = $conn->prepare("SELECT PaymentID, AmountPaid FROM Payment WHERE StudentID = ?");
$stmt->bind_param("s", $studentID);
$stmt->execute();
$result = $stmt->get_result();
$payments = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Refunds - Student Dashboard</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
    body { background-color: #fdf7f0; }
    .container { max-width: 800px; margin-top: 40px; }
    .card { background-color: #fff9f2; border-radius: 12px; padding: 25px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
    h4 { color: #2a4d14; }
    .btn-primary { background-color: #4d774e; border-color: #4d774e; }
    .btn-primary:hover { background-color: #3e613c; border-color: #3e613c; }
    .btn-secondary { background-color: #d4c9b1; border-color: #d4c9b1; color: #2a4d14; }
    .btn-secondary:hover { background-color: #c1b395; color: #2a4d14; }
    .table th, .table td { vertical-align: middle; }
</style>
</head>
<body>

<div class="container">
    <div class="card">
        <?php if(isset($_GET['success'])): ?>
            <div class="alert alert-success">Refund request submitted successfully!</div>
        <?php endif; ?>

        <h4 class="mb-3">Refund History</h4>
        <?php if(count($refunds) > 0): ?>
            <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead class="table-dark">
                    <tr>
                        <th>Refund ID</th>
                        <th>Payment ID</th>
                        <th>Amount</th>
                        <th>Reason</th>
                        <th>Approved By</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($refunds as $r): ?>
                    <tr>
                        <td><?=htmlspecialchars($r['RefundID'])?></td>
                        <td><?=htmlspecialchars($r['PaymentID'])?></td>
                        <td><?=number_format($r['Amount'], 2)?></td>
                        <td><?=htmlspecialchars($r['Reason'])?></td>
                        <td><?=htmlspecialchars($r['ApprovedBy'] ?? '-')?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            </div>
        <?php else: ?>
            <div class="alert alert-info mt-3">No refunds found.</div>
        <?php endif; ?>

        <hr>

        <h4 class="mb-3">Request a Refund</h4>
        <form method="post">
            <input type="hidden" name="request_refund" value="1">
            <div class="mb-3">
                <label class="form-label">Select Payment</label>
                <select name="paymentID" class="form-select" required>
                    <option value="">-- Choose Payment --</option>
                    <?php foreach($payments as $p): ?>
                        <option value="<?=$p['PaymentID']?>">ID: <?=$p['PaymentID']?>, Amount: <?=$p['AmountPaid']?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Refund Amount</label>
                <input type="number" name="amount" class="form-control" min="1" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Reason</label>
                <textarea name="reason" class="form-control" rows="3" required></textarea>
            </div>
            <button type="submit" class="btn btn-primary w-100">Submit Refund Request</button>
        </form>

        <a href="student_dashboard.php" class="btn btn-secondary mt-3 w-100">Back to Dashboard</a>
    </div>
</div>

</body>
</html>
