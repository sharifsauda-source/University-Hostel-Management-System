<?php
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'Admin') {
    header("Location: index.html");
    exit;
}
include("includes/db_config.php");

// --- Handle Assignment ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['RequestID'], $_POST['AssignedStaffID'])) {
    $requestID    = $_POST['RequestID'];
    $staffID      = $_POST['AssignedStaffID'];
    $adminID      = $_SESSION['user_id'];
    $dateAssigned = date("Y-m-d");

    // Update request status
    $stmt = $conn->prepare("UPDATE MaintenanceRequest SET Status='Assigned' WHERE RequestID=?");
    $stmt->bind_param("i", $requestID);
    $stmt->execute();
    $stmt->close();

    // Insert assignment
    $stmt = $conn->prepare("INSERT INTO MaintenanceAssignment (RequestID, AssignedStaffID, DateAssigned, AssignmentStatus, AssignedBy) VALUES (?, ?, ?, 'Ongoing', ?)");
    $stmt->bind_param("iisi", $requestID, $staffID, $dateAssigned, $adminID);
    $stmt->execute();
    $stmt->close();

    header("Location: assign_maintenance.php");
    exit;
}

// --- Fetch Pending Requests ---
$requests = $conn->query("
    SELECT mr.RequestID, s.StudentName, mr.RoomID, mr.Description, mr.DateSubmitted
    FROM MaintenanceRequest mr
    JOIN Student s ON mr.StudentID = s.StudentID
    WHERE mr.Status = 'Pending'
");

// --- Fetch Staff ---
$staffList = $conn->query("SELECT StaffID, StaffName FROM Staff");

// --- Fetch Ongoing Assignments ---
$assignments = $conn->query("
    SELECT ma.AssignmentID, ma.RequestID, ma.DateAssigned, ma.AssignmentStatus,
           st.StaffName, s.StudentName, mr.RoomID, mr.Description
    FROM MaintenanceAssignment ma
    JOIN Staff st ON ma.AssignedStaffID = st.StaffID
    JOIN MaintenanceRequest mr ON ma.RequestID = mr.RequestID
    JOIN Student s ON mr.StudentID = s.StudentID
    WHERE ma.AssignmentStatus = 'Ongoing'
    ORDER BY ma.DateAssigned DESC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Assign Maintenance Requests</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body {
    background-color: #f5f5dc;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}
h3 {
    color: #7b8d6d;
    margin-bottom: 1rem;
}
.table thead {
    background-color: #c4c8b5;
    color: #fff;
}
.btn-primary {
    background-color: #7b8d6d;
    border-color: #7b8d6d;
}
.btn-primary:hover { background-color: #6a7b5d; }
.btn-secondary {
    background-color: #c4c8b5;
    border-color: #c4c8b5;
    color: #fff;
}
.btn-secondary:hover { background-color: #aeb18d; }
.card {
    background-color: #fff;
    border-radius: 0.5rem;
    padding: 1rem;
    margin-bottom: 2rem;
    box-shadow: 0 2px 6px rgba(0,0,0,0.1);
}
</style>
</head>
<body>
<div class="container mt-4">

    <h3>Assign Maintenance Requests</h3>
    <div class="card">
        <div class="table-responsive">
            <table class="table table-bordered table-striped align-middle mb-0">
                <thead>
                    <tr>
                        <th>Request ID</th>
                        <th>Student</th>
                        <th>Room</th>
                        <th>Description</th>
                        <th>Date Submitted</th>
                        <th>Assign Staff</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($requests && $requests->num_rows > 0): ?>
                    <?php while ($row = $requests->fetch_assoc()): ?>
                        <tr>
                            <td><?=$row['RequestID']?></td>
                            <td><?=$row['StudentName']?></td>
                            <td><?=$row['RoomID']?></td>
                            <td><?=$row['Description']?></td>
                            <td><?=$row['DateSubmitted']?></td>
                            <td>
                                <form method="post" class="d-flex gap-2">
                                    <input type="hidden" name="RequestID" value="<?=$row['RequestID']?>">
                                    <select name="AssignedStaffID" class="form-select" required>
                                        <option value="">Select Staff</option>
                                        <?php while ($staff = $staffList->fetch_assoc()): ?>
                                            <option value="<?=$staff['StaffID']?>"><?=$staff['StaffName']?></option>
                                        <?php endwhile; $staffList->data_seek(0); ?>
                                    </select>
                                    <button type="submit" class="btn btn-primary btn-sm">Assign</button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="6" class="text-center">No pending requests</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <h3>Ongoing Assignments</h3>
    <div class="card">
        <div class="table-responsive">
            <table class="table table-bordered table-striped align-middle mb-0">
                <thead>
                    <tr>
                        <th>Assignment ID</th>
                        <th>Request ID</th>
                        <th>Student</th>
                        <th>Room</th>
                        <th>Description</th>
                        <th>Assigned Staff</th>
                        <th>Date Assigned</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($assignments && $assignments->num_rows > 0): ?>
                    <?php while ($row = $assignments->fetch_assoc()): ?>
                        <tr>
                            <td><?=$row['AssignmentID']?></td>
                            <td><?=$row['RequestID']?></td>
                            <td><?=$row['StudentName']?></td>
                            <td><?=$row['RoomID']?></td>
                            <td><?=$row['Description']?></td>
                            <td><?=$row['StaffName']?></td>
                            <td><?=$row['DateAssigned']?></td>
                            <td><?=$row['AssignmentStatus']?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="8" class="text-center">No ongoing assignments</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <a href="admin_dashboard.php" class="btn btn-secondary mt-3">Back to Dashboard</a>
</div>
</body>
</html>
