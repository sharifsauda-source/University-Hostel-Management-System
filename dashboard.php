<?php
session_start();
if (!isset($_SESSION['userid'])) {
    header("Location: index.html");
    exit;
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
</head>
<body>
<div class="container mt-5">
    <h2>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h2>
    <p>You are logged in as: <strong><?php echo htmlspecialchars($_SESSION['role']); ?></strong></p>

    <!-- You can add role-specific buttons here -->
    <?php if ($_SESSION['role'] === 'Admin'): ?>
        <a href="admin_panel.php" class="btn btn-primary">Admin Panel</a>
    <?php elseif ($_SESSION['role'] === 'Student'): ?>
        <a href="student_dashboard.php" class="btn btn-success">Student Dashboard</a>
    <?php elseif ($_SESSION['role'] === 'Staff'): ?>
        <a href="staff_tasks.php" class="btn btn-info">Staff Tasks</a>
    <?php endif; ?>

    <br><br>
    <a href="logout.php" class="btn btn-danger">Logout</a>
</div>
</body>
</html>
