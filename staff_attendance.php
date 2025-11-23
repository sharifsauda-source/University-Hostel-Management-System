<?php
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'Admin') {
    header("Location: index.html");
    exit;
}
include("includes/db_config.php");

$sql = "SELECT sa.AttendanceID, st.StaffName, sa.AttendingDate, sa.AttendanceStatus
        FROM StaffAttendance sa
        JOIN Staff st ON sa.StaffID = st.StaffID
        ORDER BY sa.AttendingDate DESC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Staff Attendance - Admin</title>
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
.card {
    background-color: #fff;
    border-radius: 0.5rem;
    padding: 1rem;
    box-shadow: 0 2px 6px rgba(0,0,0,0.1);
}
.table-striped>tbody>tr:nth-of-type(odd) {
    background-color: #e9ecef;
}
.badge {
    font-size: 0.9em;
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
  <h3>Staff Attendance Records</h3>
  <div class="card">
    <div class="table-responsive">
      <table class="table table-bordered table-striped mb-0">
        <thead class="table-dark">
          <tr>
            <th>ID</th>
            <th>Staff Name</th>
            <th>Date</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
        <?php if ($result && $result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                  <td><?=$row['AttendanceID']?></td>
                  <td><?=htmlspecialchars($row['StaffName'])?></td>
                  <td><?=$row['AttendingDate']?></td>
                  <td>
                    <?php
                    switch ($row['AttendanceStatus']) {
                        case 'Present':
                            echo '<span class="badge bg-success">Present</span>';
                            break;
                        case 'Absent':
                            echo '<span class="badge bg-danger">Absent</span>';
                            break;
                        case 'Late':
                            echo '<span class="badge bg-warning text-dark">Late</span>';
                            break;
                        case 'Leave':
                            echo '<span class="badge bg-info text-dark">Leave</span>';
                            break;
                        default:
                            echo htmlspecialchars($row['AttendanceStatus']);
                    }
                    ?>
                  </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="4" class="text-center">No attendance records found</td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
  <a href="admin_dashboard.php" class="btn btn-secondary mt-3">Back to Dashboard</a>
</div>
</body>
</html>
