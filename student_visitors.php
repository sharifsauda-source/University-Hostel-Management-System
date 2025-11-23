<?php
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'Student') {
    header("Location: index.html");
    exit;
}

include("includes/db_config.php");
$studentID = $_SESSION['user_id'];
$message = "";


$stmt = $conn->prepare("SELECT RoomID FROM Student WHERE StudentID=?");
$stmt->bind_param("i", $studentID);
$stmt->execute();
$stmt->bind_result($roomID);
$stmt->fetch();
$stmt->close();


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['visitorName'], $_POST['purpose'], $_POST['visitDate'], $_POST['entryTime'])) {
    $visitorName = trim($_POST['visitorName']);
    $purpose     = trim($_POST['purpose']);
    $visitDate   = $_POST['visitDate'];
    $entryTime   = $visitDate . " " . $_POST['entryTime'] . ":00";
    $exitTime    = !empty($_POST['exitTime']) ? $visitDate . " " . $_POST['exitTime'] . ":00" : NULL;


    $stmt = $conn->prepare("INSERT INTO Visitor (VisitorName, Purpose) VALUES (?, ?)");
    $stmt->bind_param("ss", $visitorName, $purpose);
    $stmt->execute();
    $visitorID = $stmt->insert_id;
    $stmt->close();


    $stmt = $conn->prepare("INSERT INTO visitrecord (StudentID, VisitorID, RoomID, VisitDate, EntryTime, ExitTime) 
                            VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iiisss", $studentID, $visitorID, $roomID, $visitDate, $entryTime, $exitTime);
    if ($stmt->execute()) {
        $message = "Visitor added successfully.";
    } else {
        $message = "Error: " . $stmt->error;
    }
    $stmt->close();
}

// --- Fetch visit history ---
$sql = "SELECT v.VisitorName, v.Purpose, vr.VisitDate, vr.EntryTime, vr.ExitTime, vr.RoomID
        FROM visitrecord vr
        JOIN Visitor v ON vr.VisitorID = v.VisitorID
        WHERE vr.StudentID=?
        ORDER BY vr.VisitDate DESC, vr.EntryTime DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $studentID);
$stmt->execute();
$result = $stmt->get_result();
$visits = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Visitor Records - Student Dashboard</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
    body { background-color: #fdf7f0; }
    .container { max-width: 900px; margin-top: 40px; }
    .card { background-color: #fff9f2; border-radius: 12px; padding: 25px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
    h3, h5 { color: #2a4d14; }
    .btn-primary { background-color: #4d774e; border-color: #4d774e; }
    .btn-primary:hover { background-color: #3e613c; border-color: #3e613c; }
    .btn-secondary { background-color: #d4c9b1; border-color: #d4c9b1; color: #2a4d14; }
    .btn-secondary:hover { background-color: #c1b395; color: #2a4d14; }
    .table th, .table td { vertical-align: middle; }
</style>
</head>
<body>

<div class="container">
    <?php if($message): ?>
        <div class="alert alert-info"><?=$message?></div>
    <?php endif; ?>

    <div class="card mb-4">
        <h5>Add New Visitor</h5>
        <form method="post">
            <div class="row mb-2">
                <div class="col-md-4">
                    <label class="form-label">Visitor Name</label>
                    <input type="text" name="visitorName" class="form-control" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Purpose</label>
                    <input type="text" name="purpose" class="form-control" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Visit Date</label>
                    <input type="date" name="visitDate" class="form-control" required>
                </div>
            </div>
            <div class="row mb-2">
                <div class="col-md-6">
                    <label class="form-label">Entry Time</label>
                    <input type="time" name="entryTime" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Exit Time</label>
                    <input type="time" name="exitTime" class="form-control">
                </div>
            </div>
            <button type="submit" class="btn btn-primary mt-2 w-100">Add Visitor</button>
        </form>
    </div>

    <div class="card">
        <h5 class="mb-3">Visitor History</h5>
        <?php if(count($visits) > 0): ?>
        <div class="table-responsive">
        <table class="table table-bordered table-striped">
            <thead class="table-dark">
                <tr>
                    <th>Visitor Name</th>
                    <th>Purpose</th>
                    <th>Visit Date</th>
                    <th>Entry Time</th>
                    <th>Exit Time</th>
                    <th>Room</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($visits as $v): ?>
                <tr>
                    <td><?=htmlspecialchars($v['VisitorName'])?></td>
                    <td><?=htmlspecialchars($v['Purpose'])?></td>
                    <td><?=htmlspecialchars($v['VisitDate'])?></td>
                    <td><?=htmlspecialchars($v['EntryTime'])?></td>
                    <td><?=htmlspecialchars($v['ExitTime'] ?? '-')?></td>
                    <td><?=htmlspecialchars($v['RoomID'])?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <?php else: ?>
            <div class="alert alert-info">No visitors recorded</div>
        <?php endif; ?>

        <a href="student_dashboard.php" class="btn btn-secondary mt-3 w-100">Back to Dashboard</a>
    </div>
</div>

</body>
</html>
