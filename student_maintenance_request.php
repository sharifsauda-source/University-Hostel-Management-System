<?php 
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'Student') {
    header("Location: index.html");
    exit;
}
include("includes/db_config.php");

$studentID = $_SESSION['user_id'];
$message = "";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $description   = trim($_POST['description']);
    $dateSubmitted = date("Y-m-d");
    $status        = "Pending";

    if (!empty($description)) {
        // Fetch RoomID for this student
        $roomQuery = $conn->prepare("SELECT RoomID FROM Student WHERE StudentID=?");
        $roomQuery->bind_param("i", $studentID);
        $roomQuery->execute();
        $roomResult = $roomQuery->get_result();
        $roomRow    = $roomResult->fetch_assoc();
        $roomID     = $roomRow['RoomID'] ?? null;
        $roomQuery->close();

        // Insert Maintenance Request
        $stmt = $conn->prepare("INSERT INTO MaintenanceRequest (StudentID, RoomID, Description, Status, DateSubmitted) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("iisss", $studentID, $roomID, $description, $status, $dateSubmitted);

        if ($stmt->execute()) {
            $message = "<div class='alert alert-success'>Request submitted successfully!</div>";
        } else {
            $message = "<div class='alert alert-danger'>Error: Could not submit request. {$stmt->error}</div>";
        }
        $stmt->close();
    } else {
        $message = "<div class='alert alert-warning'>Please enter a description.</div>";
    }
}

// Fetch student requests history
$stmt = $conn->prepare("SELECT RequestID, RoomID, Description, Status, DateSubmitted FROM MaintenanceRequest WHERE StudentID=? ORDER BY DateSubmitted DESC");
$stmt->bind_param("i", $studentID);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Maintenance Request</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
    body { background-color: #fdf7f0; }
    .container { max-width: 800px; margin-top: 40px; }
    .card { background-color: #fff9f2; border-radius: 12px; padding: 25px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
    h2, h4 { color: #2a4d14; }
    .btn-primary { background-color: #4d774e; border-color: #4d774e; }
    .btn-primary:hover { background-color: #3e613c; border-color: #3e613c; }
    .btn-secondary { background-color: #d4c9b1; border-color:rgb(184, 161, 114); color: #2a4d14; }
    .btn-secondary:hover { background-color: #c1b395; color: #2a4d14; }
    .badge-status { font-weight: bold; }
    .badge-pending { background-color: #ffc107; color: #212529; }
    .badge-declined { background-color: #dc3545; }
    .badge-approved, .badge-completed {
    background-color: #28a745; /* green background */
    color: #fff; /* white text for contrast */}
    .badge-assigned {
    background-color:rgb(46, 149, 209); /* green background */
    color: #fff; /* white text for contrast */
}

</style>
</head>
<body>

<div class="container">
    <div class="card">
        <h2 class="mb-4">Submit Maintenance Request</h2>

        <?=$message?>

        <form method="post" class="mb-4">
            <div class="mb-3">
                <label for="description" class="form-label">Describe the issue here</label>
                <textarea name="description" id="description" class="form-control" rows="3" required></textarea>
            </div>
            <button type="submit" class="btn btn-primary w-100">Submit Request</button>
        </form>

        <h4 class="mb-3">Your Previous Requests</h4>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Request ID</th>
                    <th>Room ID</th>
                    <th>Description</th>
                    <th>Status</th>
                    <th>Date Submitted</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): 
                   $statusClass = '';
                   switch(strtolower($row['Status'])) {
                       case 'pending': $statusClass = 'badge-pending'; break;
                       case 'approved': 
                       case 'completed': $statusClass = 'badge-approved'; break;
                       case 'assigned': $statusClass = 'badge-assigned'; break;
                       case 'declined': $statusClass = 'badge-declined'; break;
                   }
                   
                ?>
                    <tr>
                        <td><?=htmlspecialchars($row['RequestID'])?></td>
                        <td><?=htmlspecialchars($row['RoomID'])?></td>
                        <td><?=htmlspecialchars($row['Description'])?></td>
                        <td><span class="badge badge-status <?=$statusClass?>"><?=htmlspecialchars($row['Status'])?></span></td>
                        <td><?=htmlspecialchars($row['DateSubmitted'])?></td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="5" class="text-center">No requests submitted yet.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>

        <a href="student_dashboard.php" class="btn btn-secondary mt-3">Back to Dashboard</a>
    </div>
</div>

</body>
</html>
