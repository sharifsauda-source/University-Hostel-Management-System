<?php
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'Admin') {
    header("Location: index.html");
    exit;
}

include("includes/db_config.php");


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $studentID = $_POST['StudentID'];
    $roomID    = $_POST['RoomID'];
    $action    = $_POST['action'];

    if ($action === 'approve') {
        $stmt = $conn->prepare("UPDATE RoommateRequest SET Rqst_Status='Approved' WHERE StudentID=? AND RoomID=?");
        $stmt->bind_param("si", $studentID, $roomID);
        $stmt->execute();
        $stmt->close();

        $stmt = $conn->prepare("UPDATE Student SET RoomID=? WHERE StudentID=?");
        $stmt->bind_param("is", $roomID, $studentID);
        $stmt->execute();
        $stmt->close();

        $stmt = $conn->prepare("UPDATE Room SET OccupiedCount = OccupiedCount + 1 WHERE RoomID=?");
        $stmt->bind_param("i", $roomID);
        $stmt->execute();
        $stmt->close();
    } elseif ($action === 'reject') {
        $stmt = $conn->prepare("UPDATE RoommateRequest SET Rqst_Status='Rejected' WHERE StudentID=? AND RoomID=?");
        $stmt->bind_param("si", $studentID, $roomID);
        $stmt->execute();
        $stmt->close();
    }

    header("Location: approve_roommate_requests.php");
    exit;
}


$sql = "SELECT rr.StudentID, rr.RoomID, rr.Date_rqsted, rr.Rqst_Status,
               s.StudentName, s.Gender, r.RoomType
        FROM RoommateRequest rr
        JOIN Student s ON rr.StudentID = s.StudentID
        JOIN Room r ON rr.RoomID = r.RoomID
        WHERE rr.Rqst_Status = 'Pending'";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Approve Roommate Requests</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body {
    background-color: #f5f5dc;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}
h3 {
    color: #7b8d6d;
    margin-bottom: 1.5rem;
}
.table thead {
    background-color: #c4c8b5;
    color: #fff;
}
.btn-success {
    background-color: #7b8d6d;
    border-color: #7b8d6d;
}
.btn-success:hover { background-color: #6a7b5d; }
.btn-danger {
    background-color: #c94c4c;
    border-color: #c94c4c;
}
.btn-danger:hover { background-color: #a93b3b; }
.btn-secondary {
    background-color: #c4c8b5;
    border-color: #c4c8b5;
    color: #fff;
}
.btn-secondary:hover { background-color: #aeb18d; }
</style>
</head>
<body>

<div class="container mt-4">
    <h3>Pending Roommate Requests</h3>
    <div class="card p-3 shadow-sm">
        <div class="table-responsive">
        <table class="table table-bordered table-striped align-middle mb-0">
            <thead>
                <tr>
                    <th>Student ID</th>
                    <th>Name</th>
                    <th>Gender</th>
                    <th>Requested Room</th>
                    <th>Room Type</th>
                    <th>Date Requested</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($result && $result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?=htmlspecialchars($row['StudentID'])?></td>
                        <td><?=htmlspecialchars($row['StudentName'])?></td>
                        <td><?=htmlspecialchars($row['Gender'])?></td>
                        <td><?=htmlspecialchars($row['RoomID'])?></td>
                        <td><?=htmlspecialchars($row['RoomType'])?></td>
                        <td><?=htmlspecialchars($row['Date_rqsted'])?></td>
                        <td><?=htmlspecialchars($row['Rqst_Status'])?></td>
                        <td>
                            <form method="post" class="d-flex gap-1">
                                <input type="hidden" name="StudentID" value="<?=$row['StudentID']?>">
                                <input type="hidden" name="RoomID" value="<?=$row['RoomID']?>">
                                <button type="submit" name="action" value="approve" class="btn btn-success btn-sm">Approve</button>
                                <button type="submit" name="action" value="reject" class="btn btn-danger btn-sm">Reject</button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="8" class="text-center">No pending roommate requests</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
        </div>
    </div>
    <a href="admin_dashboard.php" class="btn btn-secondary mt-3">Back to Dashboard</a>
</div>

</body>
</html>
