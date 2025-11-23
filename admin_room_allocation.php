<?php
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'Admin') {
    header("Location: index.html");
    exit;
}
include("includes/db_config.php");


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];

        if ($action === 'allocate_all') {
           
            $students = $conn->query("SELECT StudentID, Gender FROM Student WHERE RoomID IS NULL");

            if ($students) {
                $successCount = 0;
                $failCount = 0;
                while ($student = $students->fetch_assoc()) {
                    $studentID = intval($student['StudentID']);
                    $gender = $student['Gender'];

                   
                    $stmt = $conn->prepare("SELECT RoomID, Capacity, OccupiedCount FROM Room WHERE GenderType = ? AND OccupiedCount < Capacity ORDER BY RoomID LIMIT 1");
                    $stmt->bind_param("s", $gender);
                    $stmt->execute();
                    $room = $stmt->get_result()->fetch_assoc();
                    $stmt->close();

                    if ($room) {
                        $roomID = $room['RoomID'];

                        // Allocate room
                        $stmt = $conn->prepare("UPDATE Student SET RoomID = ? WHERE StudentID = ?");
                        $stmt->bind_param("ii", $roomID, $studentID);
                        if ($stmt->execute()) {
                            $stmt->close();

                            // Increment occupied count
                            $stmt = $conn->prepare("UPDATE Room SET OccupiedCount = OccupiedCount + 1 WHERE RoomID = ?");
                            $stmt->bind_param("i", $roomID);
                            $stmt->execute();
                            $stmt->close();

                            $successCount++;
                        } else {
                            $failCount++;
                        }
                    } else {
                        $failCount++;
                    }
                }
                $message = "Bulk allocation done: $successCount students allocated, $failCount failed.";
            } else {
                $error = "No unassigned students found.";
            }
        }
        elseif ($action === 'allocate' && isset($_POST['StudentID'], $_POST['RoomID'])) {
            // Single allocation (same as before)
            $studentID = intval($_POST['StudentID']);
            $roomID = intval($_POST['RoomID']);

            $stmt = $conn->prepare("SELECT Capacity, OccupiedCount, GenderType FROM Room WHERE RoomID = ?");
            $stmt->bind_param("i", $roomID);
            $stmt->execute();
            $room = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            $stmt = $conn->prepare("SELECT Gender FROM Student WHERE StudentID = ?");
            $stmt->bind_param("i", $studentID);
            $stmt->execute();
            $student = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ($room && $student) {
                if ($room['GenderType'] === $student['Gender'] && $room['OccupiedCount'] < $room['Capacity']) {
                    $stmt = $conn->prepare("UPDATE Student SET RoomID = ? WHERE StudentID = ?");
                    $stmt->bind_param("ii", $roomID, $studentID);
                    if ($stmt->execute()) {
                        $stmt->close();
                        $stmt = $conn->prepare("UPDATE Room SET OccupiedCount = OccupiedCount + 1 WHERE RoomID = ?");
                        $stmt->bind_param("i", $roomID);
                        $stmt->execute();
                        $stmt->close();
                        $message = "Room allocated successfully.";
                    } else {
                        $error = "Failed to allocate room.";
                    }
                } else {
                    $error = "Room gender/type mismatch or room full.";
                }
            } else {
                $error = "Invalid room or student.";
            }
        }
        elseif ($action === 'reassign' && isset($_POST['StudentID'], $_POST['NewRoomID'])) {
            $studentID = intval($_POST['StudentID']);
            $newRoomID = intval($_POST['NewRoomID']);

            $stmt = $conn->prepare("SELECT RoomID, Gender FROM Student WHERE StudentID = ?");
            $stmt->bind_param("i", $studentID);
            $stmt->execute();
            $student = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ($student && $student['RoomID'] !== null) {
                $oldRoomID = $student['RoomID'];

                $stmt = $conn->prepare("SELECT Capacity, OccupiedCount, GenderType FROM Room WHERE RoomID = ?");
                $stmt->bind_param("i", $newRoomID);
                $stmt->execute();
                $room = $stmt->get_result()->fetch_assoc();
                $stmt->close();

                if ($room && $room['GenderType'] === $student['Gender'] && $room['OccupiedCount'] < $room['Capacity']) {
                    $stmt = $conn->prepare("UPDATE Student SET RoomID = ? WHERE StudentID = ?");
                    $stmt->bind_param("ii", $newRoomID, $studentID);
                    if ($stmt->execute()) {
                        $stmt->close();

                        $stmt = $conn->prepare("UPDATE Room SET OccupiedCount = OccupiedCount - 1 WHERE RoomID = ?");
                        $stmt->bind_param("i", $oldRoomID);
                        $stmt->execute();
                        $stmt->close();

                        $stmt = $conn->prepare("UPDATE Room SET OccupiedCount = OccupiedCount + 1 WHERE RoomID = ?");
                        $stmt->bind_param("i", $newRoomID);
                        $stmt->execute();
                        $stmt->close();

                        $message = "Room reassigned successfully.";
                    } else {
                        $error = "Failed to reassign room.";
                    }
                } else {
                    $error = "New room gender/type mismatch or room full.";
                }
            } else {
                $error = "Student does not have a current room assignment.";
            }
        }
        elseif ($action === 'unassign' && isset($_POST['StudentID'])) {
            $studentID = intval($_POST['StudentID']);

            $stmt = $conn->prepare("SELECT RoomID FROM Student WHERE StudentID = ?");
            $stmt->bind_param("i", $studentID);
            $stmt->execute();
            $student = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ($student && $student['RoomID'] !== null) {
                $oldRoomID = $student['RoomID'];

                $stmt = $conn->prepare("UPDATE Student SET RoomID = NULL WHERE StudentID = ?");
                $stmt->bind_param("i", $studentID);
                if ($stmt->execute()) {
                    $stmt->close();

                    $stmt = $conn->prepare("UPDATE Room SET OccupiedCount = OccupiedCount - 1 WHERE RoomID = ?");
                    $stmt->bind_param("i", $oldRoomID);
                    $stmt->execute();
                    $stmt->close();

                    $message = "Room unassigned successfully.";
                } else {
                    $error = "Failed to unassign room.";
                }
            } else {
                $error = "Student does not have a room assigned.";
            }
        }
    }
}

// Fetch students without rooms
$sql_unassigned = "SELECT StudentID, StudentName, Gender FROM Student WHERE RoomID IS NULL ORDER BY StudentName";
$result_unassigned = $conn->query($sql_unassigned);

// Fetch students with rooms
$sql_assigned = "SELECT StudentID, StudentName, RoomID, Gender FROM Student WHERE RoomID IS NOT NULL ORDER BY StudentName";
$result_assigned = $conn->query($sql_assigned);
?>

<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Admin Room Allocation</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
  <h2>Room Allocation Dashboard</h2>
  <a href="admin_dashboard.php" class="btn btn-secondary">Back to Admin Dashboard</a>
</div>
 

  <?php if (!empty($message)): ?>
      <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
  <?php endif; ?>

  <?php if (!empty($error)): ?>
      <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <!-- Bulk Allocate All Unassigned Students -->
  <form method="post" action="" class="mb-4">
    <button type="submit" name="action" value="allocate_all" class="btn btn-warning"
      onclick="return confirm('Allocate rooms to ALL unassigned students automatically?');">
      Allocate All Unassigned Students
    </button>
  </form>

  <!-- Section 1: Allocate Rooms -->
  <h4>Allocate Rooms to Unassigned Students</h4>
  <table class="table table-bordered">
    <thead>
      <tr>
        <th>Student ID</th>
        <th>Name</th>
        <th>Gender</th>
        <th>Available Rooms</th>
        <th>Action</th>
      </tr>
    </thead>
    <tbody>
      <?php while ($student = $result_unassigned->fetch_assoc()): ?>
        <tr>
          <form method="post" action="">
            <td><?= $student['StudentID'] ?>
              <input type="hidden" name="StudentID" value="<?= $student['StudentID'] ?>">
            </td>
            <td><?= htmlspecialchars($student['StudentName']) ?></td>
            <td><?= htmlspecialchars($student['Gender']) ?></td>
            <td>
              <select name="RoomID" class="form-select" required>
                <option value="" disabled selected>Select room</option>
                <?php
                $stmt = $conn->prepare("SELECT RoomID, RoomType, Capacity, OccupiedCount FROM Room WHERE GenderType = ? AND OccupiedCount < Capacity ORDER BY RoomID");
                $stmt->bind_param("s", $student['Gender']);
                $stmt->execute();
                $rooms = $stmt->get_result();
                while ($room = $rooms->fetch_assoc()):
                ?>
                  <option value="<?= $room['RoomID'] ?>">
                    Room <?= $room['RoomID'] ?> (<?= htmlspecialchars($room['RoomType']) ?>) - <?= $room['OccupiedCount'] ?>/<?= $room['Capacity'] ?> occupied
                  </option>
                <?php endwhile;
                $stmt->close();
                ?>
              </select>
            </td>
            <td>
              <button type="submit" name="action" value="allocate" class="btn btn-primary">Allocate</button>
            </td>
          </form>
        </tr>
      <?php endwhile; ?>
    </tbody>
  </table>

  <!-- Section 2: Reassign / Unassign Rooms -->
  <h4>Reassign or Unassign Rooms for Assigned Students</h4>
  <table class="table table-bordered">
    <thead>
      <tr>
        <th>Student ID</th>
        <th>Name</th>
        <th>Current Room</th>
        <th>Available Rooms</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php while ($student = $result_assigned->fetch_assoc()): ?>
        <tr>
          <td><?= $student['StudentID'] ?> <input type="hidden" name="StudentID" value="<?= $student['StudentID'] ?>"></td>
          <td><?= htmlspecialchars($student['StudentName']) ?></td>
          <td><?= htmlspecialchars($student['RoomID']) ?></td>
          <td>
            <form method="post" action="" class="d-inline">
              <input type="hidden" name="StudentID" value="<?= $student['StudentID'] ?>">
              <select name="NewRoomID" class="form-select d-inline w-auto" required style="display:inline-block; width:auto;">
                <option value="" disabled selected>Select new room</option>
                <?php
                $stmt = $conn->prepare("SELECT RoomID, RoomType, Capacity, OccupiedCount FROM Room WHERE GenderType = ? AND OccupiedCount < Capacity ORDER BY RoomID");
                $stmt->bind_param("s", $student['Gender']);
                $stmt->execute();
                $rooms = $stmt->get_result();
                while ($room = $rooms->fetch_assoc()):
                ?>
                  <option value="<?= $room['RoomID'] ?>">
                    Room <?= $room['RoomID'] ?> (<?= htmlspecialchars($room['RoomType']) ?>) - <?= $room['OccupiedCount'] ?>/<?= $room['Capacity'] ?> occupied
                  </option>
                <?php endwhile;
                $stmt->close();
                ?>
              </select>
              <button type="submit" name="action" value="reassign" class="btn btn-secondary">Reassign</button>
            </form>

            <form method="post" action="" class="d-inline ms-2">
              <input type="hidden" name="StudentID" value="<?= $student['StudentID'] ?>">
              <button type="submit" name="action" value="unassign" class="btn btn-danger"
                onclick="return confirm('Are you sure you want to unassign this student from their room?');">
                Unassign
              </button>
            </form>
          </td>
        </tr>
      <?php endwhile; ?>
    </tbody>
  </table>
</div>
</body>
</html>
