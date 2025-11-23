<?php
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'Staff') {
    header("Location: index.html");
    exit;
}
include("includes/db_config.php");

$staffID = $_SESSION['user_id'];

// Fetch staff info
$stmt = $conn->prepare("SELECT StaffName, ShiftID FROM Staff WHERE StaffID=?");
$stmt->bind_param("i", $staffID);
$stmt->execute();
$stmt->bind_result($staffName, $shiftID);
$stmt->fetch();
$stmt->close();

// Fetch contact numbers
$contacts = [];
$stmt = $conn->prepare("SELECT ContactNumber FROM StaffContactNumber WHERE StaffID=?");
$stmt->bind_param("i", $staffID);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) { $contacts[] = $row['ContactNumber']; }
$stmt->close();

// Fetch assigned tasks
$sql = "SELECT mr.RequestID, mr.Description, mr.RoomID, mr.Status, ma.AssignmentStatus
        FROM MaintenanceAssignment ma
        JOIN MaintenanceRequest mr ON ma.RequestID = mr.RequestID
        WHERE ma.AssignedStaffID = ?
        ORDER BY mr.DateSubmitted DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $staffID);
$stmt->execute();
$tasks = $stmt->get_result();
$stmt->close();

// Fetch attendance history (last 7 days)
$stmt = $conn->prepare("SELECT AttendingDate, AttendanceStatus 
                        FROM StaffAttendance 
                        WHERE StaffID=? 
                        ORDER BY AttendingDate DESC LIMIT 7");
$stmt->bind_param("i", $staffID);
$stmt->execute();
$attendance = $stmt->get_result();
$stmt->close();
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Staff Dashboard</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
    body { background-color: #fdf7f0; }
    .container { max-width: 1100px; margin-top: 40px; }
    .card { background-color: #fff9f2; border-radius: 12px; padding: 25px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); margin-bottom: 20px; }
    h4, h5, h6 { color: #2a4d14; }
    .btn-primary { background-color: #4d774e; border-color: #4d774e; }
    .btn-primary:hover { background-color: #3e613c; border-color: #3e613c; }
    .btn-secondary { background-color: #d4c9b1; border-color: #d4c9b1; color: #2a4d14; }
    .btn-secondary:hover { background-color: #c1b395; color: #2a4d14; }
    .badge { font-size: 0.85rem; }
    table th, table td { vertical-align: middle; }
</style>
</head>
<body>
<nav class="navbar navbar-dark bg-dark">
    <div class="container-fluid">
        <span class="navbar-brand">Welcome, <?=htmlspecialchars($staffName)?></span>
        <a href="logout.php" class="btn btn-outline-light">Logout</a>
    </div>
</nav>

<div class="container">

    <!-- Staff Info -->
    <div class="row g-3 mb-4">
        <div class="col-md-4"><div class="card"><h6>Staff ID</h6><h5><?=$staffID?></h5></div></div>
        <div class="col-md-4"><div class="card"><h6>Shift ID</h6><h5><?=$shiftID?></h5></div></div>
        <div class="col-md-4">
            <div class="card">
                <h6>Contact Numbers</h6>
                <?php if ($contacts): ?>
                    <ul class="mb-0"><?php foreach ($contacts as $c): ?><li><?=htmlspecialchars($c)?></li><?php endforeach; ?></ul>
                <?php else: ?>
                    <p class="text-muted mb-0">No contact numbers found</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Assigned Tasks -->
    <div class="card">
        <h4 class="mb-3">Your Assigned Tasks</h4>
        <div class="table-responsive">
        <table class="table table-bordered table-striped">
            <thead class="table-dark">
                <tr>
                    <th>Request ID</th>
                    <th>Room</th>
                    <th>Description</th>
                    <th>Request Status</th>
                    <th>Assignment Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($tasks && $tasks->num_rows > 0): ?>
                <?php while ($row = $tasks->fetch_assoc()): ?>
                <tr>
                    <td><?=$row['RequestID']?></td>
                    <td><?=$row['RoomID']?></td>
                    <td><?=htmlspecialchars($row['Description'])?></td>
                    <td>
                        <?php if ($row['Status'] === 'Assigned'): ?>
                            <span class="badge bg-warning text-dark">Assigned</span>
                        <?php elseif ($row['Status'] === 'Completed'): ?>
                            <span class="badge bg-success">Completed</span>
                        <?php else: ?>
                            <span class="badge bg-secondary"><?=$row['Status']?></span>
                        <?php endif; ?>
                    </td>
                    <td><?=$row['AssignmentStatus']?></td>
                    <td>
                        <?php if ($row['AssignmentStatus'] !== 'Completed'): ?>
                            <form method="post" action="update_task_status.php" class="d-inline">
                                <input type="hidden" name="RequestID" value="<?=$row['RequestID']?>">
                                <button type="submit" name="complete" class="btn btn-success btn-sm">Mark Complete</button>
                            </form>
                        <?php else: ?>
                            <span class="text-muted">Done</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="6" class="text-center">No tasks assigned yet</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
        </div>
    </div>

    <!-- Attendance -->
    <div class="card">
        <h4 class="mb-3">Attendance</h4>
        <form method="post" action="mark_attendance.php" class="d-flex mb-3 gap-2">
            <input type="hidden" name="StaffID" value="<?=$staffID?>">
            <input type="date" name="AttendingDate" class="form-control" value="<?=date('Y-m-d')?>" required>
            <select name="AttendanceStatus" class="form-select" required>
                <option value="">Select Status</option>
                <option value="Present">Present</option>
                <option value="Absent">Absent</option>
                <option value="Late">Late</option>
                <option value="Leave">Leave</option>
            </select>
            <button type="submit" class="btn btn-primary">Submit</button>
        </form>

        <h5>Recent Attendance</h5>
        <div class="table-responsive">
        <table class="table table-bordered table-striped">
            <thead class="table-dark"><tr><th>Date</th><th>Status</th></tr></thead>
            <tbody>
            <?php if ($attendance && $attendance->num_rows > 0): ?>
                <?php while ($row = $attendance->fetch_assoc()): ?>
                <tr>
                    <td><?=$row['AttendingDate']?></td>
                    <td>
                        <?php
                        $status = $row['AttendanceStatus'];
                        if ($status === 'Present') echo '<span class="badge bg-success">Present</span>';
                        elseif ($status === 'Absent') echo '<span class="badge bg-danger">Absent</span>';
                        elseif ($status === 'Late') echo '<span class="badge bg-warning text-dark">Late</span>';
                        elseif ($status === 'Leave') echo '<span class="badge bg-info text-dark">Leave</span>';
                        else echo htmlspecialchars($status);
                        ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="2" class="text-center">No attendance records yet</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
        </div>
    </div>

</div>
</body>
</html>

