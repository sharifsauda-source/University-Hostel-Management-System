<?php
session_start();
include("includes/db_config.php");

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'Admin') {
    header("Location: index.html");
    exit;
}

if (isset($_POST['StudentID']) && isset($_POST['NewRoomID'])) {
    $studentID = intval($_POST['StudentID']);
    $newRoomID = intval($_POST['NewRoomID']);

    // Fetch old room ID
    $stmt = $conn->prepare("SELECT RoomID FROM Student WHERE StudentID = ?");
    $stmt->bind_param("i", $studentID);
    $stmt->execute();
    $stmt->bind_result($oldRoomID);
    $stmt->fetch();
    $stmt->close();

    $conn->begin_transaction();

    try {
        // Update student with new room
        $stmt = $conn->prepare("UPDATE Student SET RoomID = ? WHERE StudentID = ?");
        $stmt->bind_param("ii", $newRoomID, $studentID);
        $stmt->execute();
        $stmt->close();

        // Decrease count from old room (if exists)
        if ($oldRoomID) {
            $stmt = $conn->prepare("UPDATE Room SET OccupiedCount = OccupiedCount - 1 WHERE RoomID = ?");
            $stmt->bind_param("i", $oldRoomID);
            $stmt->execute();
            $stmt->close();
        }

        // Increase count for new room
        $stmt = $conn->prepare("UPDATE Room SET OccupiedCount = OccupiedCount + 1 WHERE RoomID = ?");
        $stmt->bind_param("i", $newRoomID);
        $stmt->execute();
        $stmt->close();

        $conn->commit();

        header("Location: admin_dashboard.php?success=Room reassigned successfully");
        exit;
    } catch (Exception $e) {
        $conn->rollback();
        header("Location: admin_dashboard.php?error=Failed to reassign room");
        exit;
    }
} else {
    header("Location: admin_dashboard.php?error=Invalid request");
    exit;
}
?>
