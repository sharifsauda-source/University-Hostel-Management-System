<?php
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'Admin') { header("Location: index.html"); exit; }
include("includes/db_config.php");
$res = $conn->query("SELECT RoomID, RoomType, Capacity, OccupiedCount, GenderType, TotalFee FROM Room ORDER BY RoomID");
?>
<!doctype html><html><head><meta charset="utf-8"><title>Rooms</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head><body class="p-4">
<div class="container">
  <h3>Rooms</h3>
  <table class="table table-hover">
    <thead><tr><th>ID</th><th>Type</th><th>Capacity</th><th>Occupied</th><th>Gender</th><th>Fee</th></tr></thead>
    <tbody>
      <?php while($r = $res->fetch_assoc()): ?>
      <tr>
        <td><?=htmlspecialchars($r['RoomID'])?></td>
        <td><?=htmlspecialchars($r['RoomType'])?></td>
        <td><?=htmlspecialchars($r['Capacity'])?></td>
        <td><?=htmlspecialchars($r['OccupiedCount'])?></td>
        <td><?=htmlspecialchars($r['GenderType'])?></td>
        <td><?=number_format($r['TotalFee'],2)?></td>
      </tr>
      <?php endwhile; ?>
    </tbody>
  </table>
  <a href="admin_dashboard.php" class="btn btn-secondary">Back</a>
</div>
</body></html>
