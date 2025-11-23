<?php
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'Student') {
    header("Location: index.html");
    exit;
}

include("includes/db_config.php");
$studentID = $_SESSION['user_id'];

// Handle new payment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = $_POST['amount'];
    $method = $_POST['method'];
    $date = date('Y-m-d H:i:s'); // Payment date
    $issuedBy = null;
    $receiptDate = null;

    $stmt = $conn->prepare("
        INSERT INTO Payment (StudentID, AmountPaid, PaymentMethod, PaymentDate, ReceiptIssuedBy, ReceiptDate)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("sdssss", $studentID, $amount, $method, $date, $issuedBy, $receiptDate);
    $stmt->execute();
    $stmt->close();

    header("Location: student_payments.php?success=1");
    exit;
}

// Fetch payment history
$stmt = $conn->prepare("
    SELECT PaymentID, AmountPaid, PaymentMethod, PaymentDate
    FROM Payment
    WHERE StudentID = ?
    ORDER BY PaymentDate DESC
");
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
<title>My Payments - Student Dashboard</title>
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
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">Payment submitted successfully!</div>
        <?php endif; ?>

        <h4 class="mb-3">Payment History</h4>
        <?php if (count($payments) > 0): ?>
        <div class="table-responsive">
        <table class="table table-bordered table-striped">
            <thead class="table-dark">
                <tr>
                    <th>Payment ID</th>
                    <th>Amount Paid</th>
                    <th>Method</th>
                    <th>Payment Date</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($payments as $p): ?>
                <tr>
                    <td><?=htmlspecialchars($p['PaymentID'])?></td>
                    <td><?=number_format($p['AmountPaid'], 2)?></td>
                    <td><?=htmlspecialchars($p['PaymentMethod'])?></td>
                    <td><?=htmlspecialchars($p['PaymentDate'])?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <?php else: ?>
            <div class="alert alert-info mt-3">No payments found.</div>
        <?php endif; ?>

        <hr>

        <h4 class="mb-3">Make a Payment</h4>
        <form method="post">
            <div class="mb-3">
                <label class="form-label">Amount</label>
                <input type="number" name="amount" class="form-control" min="1" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Payment Method</label>
                <select name="method" class="form-select" required>
                    <option value="">Select Method</option>
                    <option value="Cash">Cash</option>
                    <option value="Card">Card</option>
                    <option value="Online Transfer">Online Transfer</option>
                    <option value="Bank Deposit">Bank Deposit</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary w-100">Submit Payment</button>
        </form>

        <a href="student_dashboard.php" class="btn btn-secondary mt-3 w-100">Back to Dashboard</a>
    </div>
</div>

</body>
</html>
