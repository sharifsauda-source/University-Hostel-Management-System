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
    $roomID = $_POST['RoomID'];
    $date   = date('Y-m-d');

    $stmt = $conn->prepare("INSERT INTO RoommateRequest (StudentID, RoomID, Rqst_Status, Date_rqsted) VALUES (?, ?, 'Pending', ?)");
    $stmt->bind_param("sis", $studentID, $roomID, $date);

    if ($stmt->execute()) {
        $message = "Roommate request submitted successfully!";
    } else {
        $message = "Error: " . $stmt->error;
    }

    $stmt->close();
}

// Get student gender to filter rooms
$stmt = $conn->prepare("SELECT Gender FROM Student WHERE StudentID=?");
$stmt->bind_param("s", $studentID);
$stmt->execute();
$stmt->bind_result($gender);
$stmt->fetch();
$stmt->close();

// Fetch available rooms for this gender
$rooms = $conn->query("SELECT RoomID, RoomType, Capacity, OccupiedCount FROM Room WHERE GenderType='$gender' AND OccupiedCount < Capacity");
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Roommate Request</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
    body { background-color: #fdf7f0; }
    .container { max-width: 800px; margin-top: 40px; }
    .card { background-color: #fff9f2; border-radius: 12px; padding: 25px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
    h2, h3 { color: #2a4d14; }
    .btn-primary { background-color: #4d774e; border-color: #4d774e; }
    .btn-primary:hover { background-color: #3e613c; border-color: #3e613c; }
    .btn-secondary { background-color: #d4c9b1; border-color: #d4c9b1; color: #2a4d14; }
    .btn-secondary:hover { background-color: #c1b395; color: #2a4d14; }
</style>
</head>
<body>
<div class="container">
    <div class="card">
        <h2 class="mb-4">Submit Roommate Request</h2>

        <?php if($message): ?>
            <div class="alert alert-success"><?=$message?></div>
        <?php endif; ?>

        <form method="post" class="mb-4">
            <div class="mb-3">
                <label class="form-label">Select Room</label>
                <select name="RoomID" class="form-control" required>
                    <?php while ($room = $rooms->fetch_assoc()): ?>
                        <option value="<?=$room['RoomID']?>">
                            Room <?=$room['RoomID']?> (<?=$room['RoomType']?>) - <?=$room['OccupiedCount']?>/<?=$room['Capacity']?> occupied
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <button type="submit" class="btn btn-primary w-100">Submit Request</button>
        </form>

        <h3 class="mb-3">My Requests</h3>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Room</th>
                    <th>Status</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $sql = "SELECT RoomID, Rqst_Status, Date_rqsted FROM RoommateRequest WHERE StudentID=?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("s", $studentID);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($result->num_rows > 0):
                    while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?=$row['RoomID']?></td>
                            <td><?=$row['Rqst_Status']?></td>
                            <td><?=$row['Date_rqsted']?></td>
                        </tr>
                    <?php endwhile;
                else: ?>
                    <tr><td colspan="3" class="text-center">No requests submitted</td></tr>
                <?php endif;
                $stmt->close(); ?>
            </tbody>
        </table>

        <a href="student_dashboard.php" class="btn btn-secondary mt-3">Back to Dashboard</a>
    </div>
</div>
</body>
</html>
