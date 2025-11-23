<?php
session_start();
include("includes/db_config.php");

// Optional: check if logged-in user is an Admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    die("Access denied.");
}

$sql_students = "SELECT StudentID, Gender FROM Student WHERE RoomID IS NULL";
$result_students = $conn->query($sql_students);

$allocated_count = 0;
$no_room_count = 0;

while ($student = $result_students->fetch_assoc()) {
    $studentID = $student['StudentID'];
    $gender = $student['Gender'];

    // Find a suitable room
    $sql_room = "SELECT RoomID FROM Room 
                 WHERE GenderType = ? AND OccupiedCount < Capacity 
                 ORDER BY Capacity - OccupiedCount DESC LIMIT 1";
    $stmt_room = $conn->prepare($sql_room);
    $stmt_room->bind_param("s", $gender);
    $stmt_room->execute();
    $res_room = $stmt_room->get_result();

    if ($room = $res_room->fetch_assoc()) {
        $roomID = $room['RoomID'];

        // Assign the room to the student
        $update_student = $conn->prepare("UPDATE Student SET RoomID = ?, AllocationDate = CURDATE() WHERE StudentID = ?");
        $update_student->bind_param("is", $roomID, $studentID);
        $update_student->execute();

        // Update occupied count
        $update_room = $conn->prepare("UPDATE Room SET OccupiedCount = OccupiedCount + 1 WHERE RoomID = ?");
        $update_room->bind_param("i", $roomID);
        $update_room->execute();

        $allocated_count++;
    } else {
        $no_room_count++;
    }
}

echo "<h3>Room Allocation Completed</h3>";
echo "<p>✅ $allocated_count students allocated rooms successfully.</p>";
if ($no_room_count > 0) {
    echo "<p>⚠️ $no_room_count students could not be allocated because no rooms are available.</p>";
}
echo '<a href="admin_dashboard.php">Back to Dashboard</a>';
?>
