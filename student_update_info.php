<?php
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'Student') {
    header("Location: index.html");
    exit;
}

include("includes/db_config.php");
$studentID = $_SESSION['user_id'];
$message = "";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $street = $_POST['street'] ?? '';
    $city = $_POST['city'] ?? '';
    $district = $_POST['district'] ?? '';
    $postal = $_POST['postal'] ?? '';
    $department = $_POST['department'] ?? '';
    $year = $_POST['year'] ?? '';

    // Update main student info
    $stmt = $conn->prepare("UPDATE Student SET Street=?, City=?, District=?, PostalCode=?, Department=?, Year=? WHERE StudentID=?");
    $stmt->bind_param("sssssis", $street, $city, $district, $postal, $department, $year, $studentID);
    $stmt->execute();
    $stmt->close();

    // Update contact numbers
    $conn->query("DELETE FROM studentcontactnumber WHERE StudentID='$studentID'");
    if (!empty($_POST['contact_numbers'])) {
        $stmt = $conn->prepare("INSERT INTO studentcontactnumber(StudentID, ContactNumber) VALUES (?, ?)");
        foreach ($_POST['contact_numbers'] as $number) {
            $number = trim($number);
            if ($number !== '') {
                $stmt->bind_param("ss", $studentID, $number);
                $stmt->execute();
            }
        }
        $stmt->close();
    }

    // Update emails
    $conn->query("DELETE FROM studentemail WHERE StudentID='$studentID'");
    if (!empty($_POST['emails'])) {
        $stmt = $conn->prepare("INSERT INTO studentemail(StudentID, Email) VALUES (?, ?)");
        foreach ($_POST['emails'] as $email) {
            $email = trim($email);
            if ($email !== '') {
                $stmt->bind_param("ss", $studentID, $email);
                $stmt->execute();
            }
        }
        $stmt->close();
    }

    $message = "Profile updated successfully!";
}

// Fetch current student info
$stmt = $conn->prepare("SELECT StudentName, Gender, DateOfBirth, Street, City, District, PostalCode, Department, Year, CGPA, RoomID FROM Student WHERE StudentID=?");
$stmt->bind_param("s", $studentID);
$stmt->execute();
$stmt->bind_result($studentName, $gender, $dob, $street, $city, $district, $postal, $department, $year, $cgpa, $roomID);
$stmt->fetch();
$stmt->close();

// Fetch contact numbers
$contactNumbers = [];
$result = $conn->query("SELECT ContactNumber FROM studentcontactnumber WHERE StudentID='$studentID'");
while ($row = $result->fetch_assoc()) $contactNumbers[] = $row['ContactNumber'];

// Fetch emails
$emails = [];
$result = $conn->query("SELECT Email FROM studentemail WHERE StudentID='$studentID'");
while ($row = $result->fetch_assoc()) $emails[] = $row['Email'];
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Update Profile - Student Dashboard</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
    body { background-color: #fdf7f0; }
    .container { max-width: 800px; margin-top: 40px; }
    .card { background-color: #fff9f2; border-radius: 12px; padding: 25px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
    h2, h5 { color: #2a4d14; }
    .btn-primary { background-color: #4d774e; border-color: #4d774e; }
    .btn-primary:hover { background-color: #3e613c; border-color: #3e613c; }
    .btn-secondary { background-color: #d4c9b1; border-color: #d4c9b1; color: #2a4d14; }
    .btn-secondary:hover { background-color: #c1b395; color: #2a4d14; }
    .remove-btn { cursor: pointer; color: red; margin-left: 8px; font-weight: bold; }
    input::placeholder { color: #999; }
</style>
</head>
<body>
<div class="container">
    <div class="card">
        <h2 class="mb-4">Update Profile</h2>
        <?php if($message): ?>
            <div class="alert alert-success"><?=$message?></div>
        <?php endif; ?>

        <form method="post">
            <div class="mb-3 row">
                <div class="col-md-6">
                    <label class="form-label">Student ID</label>
                    <input type="text" class="form-control" value="<?=$studentID?>" readonly>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Name</label>
                    <input type="text" class="form-control" value="<?=htmlspecialchars($studentName)?>" readonly>
                </div>
            </div>

            <div class="mb-3 row">
                <div class="col-md-4">
                    <label class="form-label">Gender</label>
                    <input type="text" class="form-control" value="<?=$gender?>" readonly>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Date of Birth</label>
                    <input type="date" class="form-control" value="<?=$dob?>" readonly>
                </div>
                <div class="col-md-4">
                    <label class="form-label">CGPA</label>
                    <input type="text" class="form-control" value="<?=$cgpa?>" readonly>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Room</label>
                <input type="text" class="form-control" value="<?=$roomID ?: 'Not Assigned'?>" readonly>
            </div>

            <hr>
            <h5>Address & Academic Info</h5>
            <div class="mb-3">
                <label class="form-label">Street</label>
                <input type="text" name="street" class="form-control" value="<?=htmlspecialchars($street)?>" required>
            </div>
            <div class="mb-3 row">
                <div class="col-md-4">
                    <label class="form-label">City</label>
                    <input type="text" name="city" class="form-control" value="<?=htmlspecialchars($city)?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">District</label>
                    <input type="text" name="district" class="form-control" value="<?=htmlspecialchars($district)?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Postal Code</label>
                    <input type="text" name="postal" class="form-control" value="<?=htmlspecialchars($postal)?>" required>
                </div>
            </div>
            <div class="mb-3 row">
                <div class="col-md-6">
                    <label class="form-label">Department</label>
                    <input type="text" name="department" class="form-control" value="<?=htmlspecialchars($department)?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Year</label>
                    <input type="number" name="year" class="form-control" value="<?=$year?>">
                </div>
            </div>

            <hr>
            <h5>Contact Numbers</h5>
            <div id="contact-container">
                <?php foreach($contactNumbers as $number): ?>
                    <div class="mb-2 d-flex align-items-center">
                        <input type="text" name="contact_numbers[]" class="form-control me-2" value="<?=htmlspecialchars($number)?>">
                        <span class="remove-btn" onclick="this.parentElement.remove()">✖</span>
                    </div>
                <?php endforeach; ?>
                <div class="mb-2 d-flex align-items-center">
                    <input type="text" name="contact_numbers[]" class="form-control me-2" placeholder="Add new number">
                    <span class="remove-btn" onclick="this.parentElement.remove()">✖</span>
                </div>
            </div>
            <button type="button" class="btn btn-secondary btn-sm mb-3" onclick="addContact()">Add Number</button>

            <h5>Emails</h5>
            <div id="email-container">
                <?php foreach($emails as $email): ?>
                    <div class="mb-2 d-flex align-items-center">
                        <input type="email" name="emails[]" class="form-control me-2" value="<?=htmlspecialchars($email)?>">
                        <span class="remove-btn" onclick="this.parentElement.remove()">✖</span>
                    </div>
                <?php endforeach; ?>
                <div class="mb-2 d-flex align-items-center">
                    <input type="email" name="emails[]" class="form-control me-2" placeholder="Add new email">
                    <span class="remove-btn" onclick="this.parentElement.remove()">✖</span>
                </div>
            </div>
            <button type="button" class="btn btn-secondary btn-sm mb-3" onclick="addEmail()">Add Email</button>

            <button type="submit" class="btn btn-primary w-100">Update Profile</button>
        </form>
    </div>
</div>

<script>
function addContact() {
    const container = document.getElementById('contact-container');
    const div = document.createElement('div');
    div.className = 'mb-2 d-flex align-items-center';
    div.innerHTML = '<input type="text" name="contact_numbers[]" class="form-control me-2" placeholder="Add new number"> <span class="remove-btn" onclick="this.parentElement.remove()">✖</span>';
    container.appendChild(div);
}
function addEmail() {
    const container = document.getElementById('email-container');
    const div = document.createElement('div');
    div.className = 'mb-2 d-flex align-items-center';
    div.innerHTML = '<input type="email" name="emails[]" class="form-control me-2" placeholder="Add new email"> <span class="remove-btn" onclick="this.parentElement.remove()">✖</span>';
    container.appendChild(div);
}
</script>
</body>
</html>
``
