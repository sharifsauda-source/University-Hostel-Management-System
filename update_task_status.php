<?php
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'Staff') {
    header("Location: index.html");
    exit;
}
include("includes/db_config.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['RequestID'], $_POST['complete'])) {
    $requestID = intval($_POST['RequestID']);
    $staffID   = $_SESSION['user_id'];


    $stmt = $conn->prepare("UPDATE MaintenanceAssignment 
                            SET AssignmentStatus='Completed' 
                            WHERE RequestID=? AND AssignedStaffID=?");
    if (!$stmt) {
        die("Error: " . $conn->error);
    }
    $stmt->bind_param("ii", $requestID, $staffID);
    $stmt->execute();
    $stmt->close();

    
    $stmt = $conn->prepare("UPDATE MaintenanceRequest 
                            SET Status='Completed' 
                            WHERE RequestID=?");
    if (!$stmt) {
        die("Error: " . $conn->error);
    }
    $stmt->bind_param("i", $requestID);
    $stmt->execute();
    $stmt->close();


    header("Location: staff_dashboard.php");
    exit;
} else {
    header("Location: staff_dashboard.php");
    exit;
}
