<?php
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'Admin') {
    header("Location: index.html");
    exit;
}
include("includes/db_config.php");

// Function to count items
function fetch_count($conn, $sql, $types = '', $params = []) {
    $stmt = $conn->prepare($sql);
    if ($types) $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();
    return $count;
}

// Stats
$totalStudents  = fetch_count($conn, "SELECT COUNT(*) FROM Student");
$totalRooms     = fetch_count($conn, "SELECT COUNT(*) FROM Room");
$availableRooms = fetch_count($conn, "SELECT COUNT(*) FROM Room WHERE OccupiedCount < Capacity");
$totalStaff     = fetch_count($conn, "SELECT COUNT(*) FROM Staff");
$pendingMaint   = fetch_count($conn, "SELECT COUNT(*) FROM MaintenanceRequest WHERE Status = 'Pending'");
$pendingRmReqs  = fetch_count($conn, "SELECT COUNT(*) FROM RoommateRequest WHERE Rqst_Status = 'Pending'");
$totalShifts    = fetch_count($conn, "SELECT COUNT(*) FROM Shift");
$totalRefunds   = fetch_count($conn, "SELECT COUNT(*) FROM Refund WHERE Amount > 0");

// Total Payments
$stmt = $conn->prepare("SELECT IFNULL(SUM(AmountPaid),0) FROM Payment");
$stmt->execute();
$stmt->bind_result($totalPayments);
$stmt->fetch();
$stmt->close();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Admin Dashboard</title>
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
    .navbar-brand, .navbar span {
      color: #7b8d6d !important;
      font-weight: 600;
    }
    .btn-outline-danger {
      border-radius: 0.5rem;
    }
    .card {
      background: #ffffff;
      border-radius: 1rem;
      border: 1px solid #c4c8b5;
      box-shadow: 0 6px 12px rgba(107,142,35,0.15);
    }
    h3, h5, h6 {
      color: #7b8d6d;
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
    .btn-primary {
      background-color: #7b8d6d;
      border-color: #7b8d6d;
    }
    .btn-primary:hover {
      background-color: #6a7b5d;
      border-color: #6a7b5d;
    }
  </style>
</head>
<body>
<nav class="navbar navbar-expand-lg">
  <div class="container-fluid">
    <a class="navbar-brand" href="#">Hostel Admin</a>
    <div class="d-flex">
      <span class="me-3">Hello, <?=htmlspecialchars($_SESSION['username'] ?? 'Admin')?></span>
      <a class="btn btn-outline-danger" href="logout.php">Logout</a>
    </div>
  </div>
</nav>

<div class="container mt-4">
  <div class="row g-3">
    <div class="col-md-3"><div class="card p-3"><h6>Total Students</h6><h3><?=$totalStudents?></h3></div></div>
    <div class="col-md-3"><div class="card p-3"><h6>Total Rooms</h6><h3><?=$totalRooms?></h3></div></div>
    <div class="col-md-3"><div class="card p-3"><h6>Available Rooms</h6><h3><?=$availableRooms?></h3></div></div>
    <div class="col-md-3"><div class="card p-3"><h6>Total Staff</h6><h3><?=$totalStaff?></h3></div></div>

    <div class="col-md-3"><div class="card p-3"><h6>Pending Maintenance</h6><h3><?=$pendingMaint?></h3></div></div>
    <div class="col-md-3"><div class="card p-3"><h6>Pending Roommate Req</h6><h3><?=$pendingRmReqs?></h3></div></div>
    <div class="col-md-3"><div class="card p-3"><h6>Total Shifts</h6><h3><?=$totalShifts?></h3></div></div>
    <div class="col-md-3"><div class="card p-3"><h6>Refund Requests</h6><h3><?=$totalRefunds?></h3></div></div>

    <div class="col-md-6"><div class="card p-3"><h6>Total Payments</h6><h3><?=number_format((float)$totalPayments,2)?></h3></div></div>
  </div>

  <hr class="my-4">

  <div class="row">
    <div class="col-md-3">
      <div class="list-group">
        <a href="admin_room_allocation.php" class="list-group-item list-group-item-action">Allocate Rooms</a>
        <a href="view_students.php" class="list-group-item list-group-item-action">View Students</a>
        <a href="view_rooms.php" class="list-group-item list-group-item-action">View Rooms</a>
        <a href="manage_staff.php" class="list-group-item list-group-item-action">Manage Staff</a>
        <a href="manage_shifts.php" class="list-group-item list-group-item-action">Manage Shifts</a>
        <a href="approve_roommate_requests.php" class="list-group-item list-group-item-action">Approve Roommate Requests</a>
        <a href="assign_maintenance.php" class="list-group-item list-group-item-action">Assign Maintenance</a>
        <a href="maintenance_requests.php" class="list-group-item list-group-item-action">Maintenance Requests</a>
        <a href="approve_refunds.php" class="list-group-item list-group-item-action">Approve Refunds</a>
        <a href="view_payments.php" class="list-group-item list-group-item-action">Manage Payments</a>
        <a href="admin_visitors.php" class="list-group-item list-group-item-action">Manage Visitors</a>
        <a href="staff_attendance.php" class="list-group-item list-group-item-action">Staff Attendance</a>
      </div>
    </div>

    <div class="col-md-9">
      <div class="card p-3">
        <h5>Welcome to Admin Dashboard</h5>
        <p>
          From here, you can manage students, rooms, staff, shifts, payments, refunds, and maintenance requests.
          Use the menu on the left to navigate.
        </p>
      </div>
    </div>
  </div>
</div>
</body>
</html>
