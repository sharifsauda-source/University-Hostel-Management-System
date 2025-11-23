<?php
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'Staff') {
    header("Location: index.html");
    exit;
}
include("includes/db_config.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['StaffID'], $_POST['AttendingDate'], $_POST['AttendanceStatus'])) {
    $staffID   = intval($_POST['StaffID']);
    $date      = $_POST['AttendingDate'];
    $status    = $_POST['AttendanceStatus'];

    // Prevent duplicate attendance entry for same day
    $check = $conn->prepare("SELECT AttendanceID FROM StaffAttendance WHERE StaffID=? AND AttendingDate=?");
    $check->bind_param("is", $staffID, $date);
    $check->execute();
    $res = $check->get_result();

    if ($res->num_rows > 0) {
        // Update existing
        $stmt = $conn->prepare("UPDATE StaffAttendance SET AttendanceStatus=? WHERE StaffID=? AND AttendingDate=?");
        $stmt->bind_param("sis", $status, $staffID, $date);
    } else {
        // Insert new
        $stmt = $conn->prepare("INSERT INTO StaffAttendance (StaffID, AttendingDate, AttendanceStatus) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $staffID, $date, $status);
    }

    $stmt->execute();
    $stmt->close();
    header("Location: staff_dashboard.php");
    exit;
} else {
    header("Location: staff_dashboard.php");
    exit;
}
