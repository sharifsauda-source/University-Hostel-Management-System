<?php
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'Admin') { 
    header("Location: index.html"); 
    exit; 
}
include("includes/db_config.php");

// Handle deletion
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['delete']) && !empty($_POST['StudentID'])) {
    $studentID = $_POST['StudentID'];

    $conn->begin_transaction();
    try {
        $stmt1 = $conn->prepare("DELETE FROM studentcontactnumber WHERE StudentID = ?");
        $stmt1->bind_param("s", $studentID); $stmt1->execute();

        $stmt2 = $conn->prepare("DELETE FROM studentemail WHERE StudentID = ?");
        $stmt2->bind_param("s", $studentID); $stmt2->execute();

        $stmt3 = $conn->prepare("DELETE FROM Student WHERE StudentID = ?");
        $stmt3->bind_param("s", $studentID); $stmt3->execute();

        $conn->commit();
        $message = "Student deleted successfully.";
    } catch (Exception $e) {
        $conn->rollback();
        $message = "Error deleting student: " . $e->getMessage();
    }
}

// Fetch students
$sql = "
    SELECT 
        s.StudentID, 
        s.StudentName, 
        s.Gender, 
        s.Department, 
        s.Year, 
        s.RoomID,
        GROUP_CONCAT(DISTINCT se.email ORDER BY se.email SEPARATOR ', ') AS emails,
        GROUP_CONCAT(DISTINCT sc.contactnumber ORDER BY sc.contactnumber SEPARATOR ', ') AS contacts
    FROM Student s
    LEFT JOIN studentemail se ON s.StudentID = se.StudentID
    LEFT JOIN studentcontactnumber sc ON s.StudentID = sc.StudentID
    GROUP BY s.StudentID
    ORDER BY s.StudentID
    LIMIT 200
";
$res = $conn->query($sql);
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Students - NSU Hostel</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body {
    background-color: #f5f5dc;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}
h3 {
    color: #7b8d6d;
}
.card {
    background: #ffffff;
    border-radius: 1rem;
    border: 1px solid #c4c8b5;
    box-shadow: 0 8px 16px rgba(107, 142, 35, 0.2);
}
.table thead {
    background-color: #c4c8b5;
    color: #fff;
}
.table td, .table th {
    vertical-align: middle;
}
.btn-primary {
    background-color: #7b8d6d;
    border-color: #7b8d6d;
}
.btn-primary:hover {
    background-color: #6a7b5d;
    border-color: #6a7b5d;
}
.btn-secondary {
    background-color: #c4c8b5;
    border-color: #c4c8b5;
    color: #fff;
}
.btn-secondary:hover {
    background-color: #aeb18d;
    border-color: #aeb18d;
}
.btn-danger {
    background-color: #c94c4c;
    border-color: #c94c4c;
    color: #fff;
}
.btn-danger:hover {
    background-color: #a93b3b;
    border-color: #a93b3b;
}
.alert-info {
    background-color: #faf8f2;
    border-color: #c4c8b5;
    color: #7b8d6d;
}
</style>
</head>
<body>
<div class="container my-4">
  <div class="card p-4">
    <h3 class="mb-4">Student Records</h3>

    <?php if (!empty($message)): ?>
        <div class="alert alert-info"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <div class="table-responsive">
    <table class="table table-striped table-bordered align-middle">
      <thead>
        <tr>
          <th>ID</th>
          <th>Name</th>
          <th>Gender</th>
          <th>Dept</th>
          <th>Year</th>
          <th>Room</th>
          <th>Email(s)</th>
          <th>Contact(s)</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php while($r = $res->fetch_assoc()): ?>
          <tr>
            <td><?=htmlspecialchars($r['StudentID'])?></td>
            <td><?=htmlspecialchars($r['StudentName'])?></td>
            <td><?=htmlspecialchars($r['Gender'])?></td>
            <td><?=htmlspecialchars($r['Department'])?></td>
            <td><?=htmlspecialchars($r['Year'])?></td>
            <td><?=($r['RoomID'] ? htmlspecialchars($r['RoomID']) : '<span class="text-muted">Unassigned</span>')?></td>
            <td><?=htmlspecialchars($r['emails'] ?? 'N/A')?></td>
            <td><?=htmlspecialchars($r['contacts'] ?? 'N/A')?></td>
            <td>
              <form method="POST" style="display:inline;" 
                    onsubmit="return confirm('Are you sure you want to delete this student?');">
                <input type="hidden" name="StudentID" value="<?=htmlspecialchars($r['StudentID'])?>">
                <button type="submit" name="delete" class="btn btn-danger btn-sm">Delete</button>
              </form>
            </td>
          </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
    </div>

    <a href="admin_dashboard.php" class="btn btn-secondary mt-3">Back to Dashboard</a>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
