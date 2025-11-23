<?php
include("includes/db_config.php");

$studentID = $_POST['StudentID'];
$roomID = $_POST['RoomID'];

// Assign room to student
$stmt = $conn->prepare("UPDATE Student SET RoomID = ?, AllocationDate = CURDATE() WHERE StudentID = ?");
$stmt->bind_param("is", $roomID, $studentID);
$stmt->execute();

// Update occupied count
$conn->query("UPDATE Room SET OccupiedCount = OccupiedCount + 1 WHERE RoomID = $roomID");

header("Location: admin_dashboard.php?msg=Manual allocation successful");
?>
