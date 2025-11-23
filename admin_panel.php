<?php
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'Admin') { header("Location: index.html"); exit; }
include("includes/db_config.php");

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = intval($_POST['student_id']);
    // pick room by gender if student has gender set
    $gender = $_POST['gender'] ?? null;

    // Use transaction to avoid race conditions
    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("SELECT RoomID FROM Room WHERE GenderType = ? AND OccupiedCount < Capacity LIMIT 1 FOR UPDATE");
        $stmt->bind_param("s", $gender);
        $stmt->execute();
        $stmt->bind_result($room_id);
        if ($stmt->fetch()) {
            $stmt->close();

            $u1 = $conn->prepare("UPDATE Student SET RoomID = ? WHERE StudentID = ?");
            $u1->bind_param("ii", $room_id, $student_id);
            $u1->execute();

            $u2 = $conn->prepare("UPDATE Room SET OccupiedCount = OccupiedCount + 1 WHERE RoomID = ?");
            $u2->bind_param("i", $room_id);
            $u2->execute();

            $conn->commit();
            $msg = "Room $room_id allocated to student $student_id.";
        } else {
            $stmt->close();
            $conn->rollback();
            $msg = "No available room found for gender $gender.";
        }
    } catch (Exception $e) {
        $conn->rollback();
        $msg = "Error: " . $e->getMessage();
    }
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Allocate Room</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-4">
<div class="container">
  <h3>Allocate Room</h3>
  <?php if ($msg): ?>
    <div class="alert alert-info"><?=$msg?></div>
  <?php endif; ?>
  <form method="post" class="row g-3 w-50">
    <div class="col-6">
      <label>Student ID</label>
      <input name="student_id" class="form-control" required>
    </div>
    <div class="col-6">
      <label>Gender</label>
      <select name="gender" class="form-control" required>
        <option value="M">Male</option>
        <option value="F">Female</option>
      </select>
    </div>
    <div class="col-12">
      <button class="btn btn-primary">Find & Allocate</button>
      <a href="admin_dashboard.php" class="btn btn-secondary">Back</a>
    </div>
  </form>
</div>
</body>
</html>
