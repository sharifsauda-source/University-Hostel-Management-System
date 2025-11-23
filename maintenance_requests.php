<?php
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'Admin') {
    header("Location: index.html");
    exit;
}
include("includes/db_config.php");

//
$sql = "SELECT mr.RequestID, s.StudentName, mr.RoomID, mr.Description, mr.Status, mr.DateSubmitted,
               ma.AssignmentStatus, st.StaffName AS AssignedStaff
        FROM MaintenanceRequest mr
        JOIN Student s ON mr.StudentID = s.StudentID
        LEFT JOIN MaintenanceAssignment ma ON mr.RequestID = ma.RequestID
        LEFT JOIN Staff st ON ma.AssignedStaffID = st.StaffID
        ORDER BY mr.DateSubmitted DESC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Maintenance Requests</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
  <div class="container-fluid">

<div class="container mt-4">
  <h3>All Maintenance Requests</h3>
  <table class="table table-bordered table-striped mt-3">
    <thead class="table-dark">
      <tr>
        <th>Request ID</th>
        <th>Student</th>
        <th>Room</th>
        <th>Description</th>
        <th>Status</th>
        <th>Assigned Staff</th>
        <th>Date Submitted</th>
      </tr>
    </thead>
    <tbody>
    <?php if ($result && $result->num_rows > 0): ?>
        <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
              <td><?=$row['RequestID']?></td>
              <td><?=htmlspecialchars($row['StudentName'])?></td>
              <td><?=$row['RoomID']?></td>
              <td><?=htmlspecialchars($row['Description'])?></td>
              <td>
                <?php if ($row['Status'] === 'Pending'): ?>
                    <span class="badge bg-warning text-dark">Pending</span>
                <?php elseif ($row['Status'] === 'Assigned'): ?>
                    <span class="badge bg-primary">Assigned</span>
                <?php elseif ($row['Status'] === 'Completed'): ?>
                    <span class="badge bg-success">Completed</span>
                <?php else: ?>
                    <span class="badge bg-secondary"><?=$row['Status']?></span>
                <?php endif; ?>
              </td>
              <td><?= $row['AssignedStaff'] ? htmlspecialchars($row['AssignedStaff']) : '<span class="text-muted">Not Assigned</span>' ?></td>
              <td><?=$row['DateSubmitted']?></td>
            </tr>
        <?php endwhile; ?>
    <?php else: ?>
        <tr><td colspan="7" class="text-center">No requests found</td></tr>
    <?php endif; ?>
    </tbody>
  </table>
<a href="admin_dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
</div>
</body>
</html>
