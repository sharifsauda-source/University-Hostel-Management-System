<?php
session_start();
include('includes/db_config.php'); // Update path if needed

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login'])) {
    $role = $_POST['role'];
    $userinput = $_POST['userinput'];
    $password = $_POST['password'];

    switch ($role) {
        case 'Admin':
            $table = 'Admin';
            $idField = 'AdminID';
            $nameField = 'AdminName';
            break;
        case 'Student':
            $table = 'Student';
            $idField = 'StudentID';
            $nameField = 'StudentName';
            break;
        case 'Staff':
            $table = 'Staff';
            $idField = 'StaffID';
            $nameField = 'StaffName';
            break;
        default:
            echo "<script>alert('Invalid role selected.');</script>";
            exit();
    }

    $stmt = $conn->prepare("SELECT $idField, $nameField, Password FROM $table WHERE $idField = ? OR $nameField = ?");
    $stmt->bind_param("ss", $userinput, $userinput);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows == 1) {
        $stmt->bind_result($id, $name, $hashed_password);
        $stmt->fetch();

        if (password_verify($password, $hashed_password)) {
            $_SESSION['user_id'] = $id;
            $_SESSION['username'] = $name;
            $_SESSION['role'] = $role;

            if ($role === 'Admin') {
                header("Location: admin_dashboard.php");
            } elseif ($role === 'Student') {
                header("Location: student_dashboard.php");
            } elseif ($role === 'Staff') {
                header("Location: staff_dashboard.php");
            }
            exit();
        } else {
            echo "<script>alert('Incorrect password.');</script>";
        }
    } else {
        echo "<script>alert('User not found.');</script>";
    }

    $stmt->close();
    $conn->close();
}
?>