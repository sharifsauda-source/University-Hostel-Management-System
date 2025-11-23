<?php
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'Student') {
    header("Location: index.html");
    exit;
}

include("includes/db_config.php");
$studentID = $_SESSION['user_id'];

// Fetch student info
$stmt = $conn->prepare("
    SELECT StudentName, Gender, DateOfBirth, Street, City, District, PostalCode,
           Department, Year, CGPA, RoomID, AllocationDate
    FROM Student WHERE StudentID=?
");
$stmt->bind_param("s", $studentID);
$stmt->execute();
$stmt->bind_result($studentName, $gender, $dob, $street, $city, $district, $postal, $department, $year, $cgpa, $roomID, $allocationDate);
$stmt->fetch();
$stmt->close();

// Financial details
$totalPaid = $totalRefund = $remainingBalance = $netPaid = $totalFee = 0;
if ($roomID) {
    // Room fee
    $stmt = $conn->prepare("SELECT TotalFee FROM Room WHERE RoomID=?");
    $stmt->bind_param("i", $roomID);
    $stmt->execute();
    $stmt->bind_result($totalFee);
    $stmt->fetch();
    $stmt->close();

    // Payments
    $stmt = $conn->prepare("SELECT IFNULL(SUM(AmountPaid),0) FROM Payment WHERE StudentID=?");
    $stmt->bind_param("s", $studentID);
    $stmt->execute();
    $stmt->bind_result($totalPaid);
    $stmt->fetch();
    $stmt->close();

    // Refunds
    $stmt = $conn->prepare("
        SELECT IFNULL(SUM(r.Amount),0) 
        FROM Refund r
        JOIN Payment p ON r.PaymentID = p.PaymentID
        WHERE p.StudentID = ?
    ");
    $stmt->bind_param("s", $studentID);
    $stmt->execute();
    $stmt->bind_result($totalRefund);
    $stmt->fetch();
    $stmt->close();

    $netPaid = $totalPaid - $totalRefund;
    $remainingBalance = $totalFee - $netPaid;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Student Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f5f5dc; /* Beige */
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .navbar {
            background-color: #ffffff;
            border-bottom: 2px solid #c4c8b5;
            box-shadow: 0 4px 8px rgba(107,142,35,0.1);
        }
        .navbar-brand {
            color: #7b8d6d !important;
            font-weight: 600;
        }
        .btn-outline-light, .btn-outline-danger {
            border-radius: 0.5rem;
        }
        h4, h5, h6 {
            color: #7b8d6d;
        }
        .card {
            background: #ffffff;
            border-radius: 1rem;
            border: 1px solid #c4c8b5;
            box-shadow: 0 6px 12px rgba(107,142,35,0.15);
        }
        .list-group-item {
            border: 1px solid #c4c8b5;
            background-color: #faf8f2;
            transition: all 0.3s ease;
        }
        .list-group-item:hover {
            background-color: #ffffff;
            border-color: #7b8d6d;
            color: #7b8d6d;
        }
        a {
            color: #7b8d6d;
            font-weight: 500;
        }
        a:hover {
            color: #6a7b5d;
            text-decoration: none;
        }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg">
    <div class="container-fluid">
        <span class="navbar-brand">Welcome, <?=htmlspecialchars($studentName)?></span>
        <a href="logout.php" class="btn btn-outline-danger">Logout</a>
    </div>
</nav>

<div class="container mt-4">

    <!-- Profile Info -->
    <h4 class="mb-3">Profile Information</h4>
    <div class="row g-3">
        <div class="col-md-3"><div class="card p-3"><h6>Student ID</h6><h5><?=$studentID?></h5></div></div>
        <div class="col-md-3"><div class="card p-3"><h6>Gender</h6><h5><?=$gender?></h5></div></div>
        <div class="col-md-3"><div class="card p-3"><h6>DOB</h6><h5><?=$dob?></h5></div></div>
        <div class="col-md-3"><div class="card p-3"><h6>CGPA</h6><h5><?=$cgpa?></h5></div></div>
    </div>

    <div class="row g-3 mt-2">
        <div class="col-md-4"><div class="card p-3"><h6>Department</h6><h5><?=$department?></h5></div></div>
        <div class="col-md-4"><div class="card p-3"><h6>Year</h6><h5><?=$year?></h5></div></div>
        <div class="col-md-4"><div class="card p-3"><h6>Room</h6><h5><?=$roomID ?: 'Not Assigned'?></h5></div></div>
    </div>

    <!-- Finance Info -->
    <h4 class="mt-4 mb-3">Finance Information</h4>
    <div class="row g-3">
        <div class="col-md-4"><div class="card p-3"><h6>Total Fee</h6><h5><?=$totalFee?></h5></div></div>
        <div class="col-md-4"><div class="card p-3"><h6>Net Paid</h6><h5><?=$netPaid?></h5></div></div>
        <div class="col-md-4"><div class="card p-3"><h6>Remaining Balance</h6><h5><?=$remainingBalance?></h5></div></div>
    </div>

    <!-- Quick Actions -->
    <hr class="my-4">
    <h4 class="mb-3">Menu</h4>
    <div class="list-group">
        <a href="student_roommate_request.php" class="list-group-item list-group-item-action">Submit Roommate Request</a>
        <a href="student_maintenance_request.php" class="list-group-item list-group-item-action">Submit Maintenance Request</a>
        <a href="student_payments.php" class="list-group-item list-group-item-action">View Payments / Make Payment</a>
        <a href="student_refunds.php" class="list-group-item list-group-item-action">View Refunds</a>
        <a href="student_visitors.php" class="list-group-item list-group-item-action">Manage Visitors</a>
        <a href="student_update_info.php" class="list-group-item list-group-item-action">Update Profile Information</a>
    </div>

</div>
</body>
</html>
