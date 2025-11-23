<?php
include("includes/db_config.php");
error_reporting(E_ALL);
ini_set('display_errors', 1);

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $StudentID   = $_POST['StudentID']; 
    $StudentName = $_POST['StudentName'];
    $Gender      = $_POST['Gender'];
    $DateOfBirth = $_POST['DateOfBirth'];
    $Street      = $_POST['Street'];
    $City        = $_POST['City'];
    $District    = $_POST['District'];
    $PostalCode  = $_POST['PostalCode'];
    $Department  = $_POST['Department'];
    $Year        = $_POST['Year'];
    $CGPA        = $_POST['CGPA'];
    $Password    = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $sql = "SELECT RoomID FROM Room 
            WHERE GenderType = ? AND OccupiedCount < Capacity
            ORDER BY Capacity - OccupiedCount DESC LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $Gender);
    $stmt->execute();
    $result = $stmt->get_result();
    $room_id = null;

    if ($row = $result->fetch_assoc()) {
        $room_id = $row['RoomID'];
    }

    if ($room_id === null) {
        $stmt = $conn->prepare("INSERT INTO Student 
            (StudentID, StudentName, Gender, DateOfBirth, Street, City, District, PostalCode, Department, Year, CGPA, RoomID, AllocationDate, Password) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NULL, CURDATE(), ?)");
        $stmt->bind_param("sssssssssids", 
            $StudentID, $StudentName, $Gender, $DateOfBirth, $Street, $City, $District, $PostalCode, 
            $Department, $Year, $CGPA, $Password);
    } else {
        $stmt = $conn->prepare("INSERT INTO Student 
            (StudentID, StudentName, Gender, DateOfBirth, Street, City, District, PostalCode, Department, Year, CGPA, RoomID, AllocationDate, Password) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURDATE(), ?)");
        $stmt->bind_param("sssssssssidis", 
            $StudentID, $StudentName, $Gender, $DateOfBirth, $Street, $City, $District, $PostalCode, 
            $Department, $Year, $CGPA, $room_id, $Password);
    }

    if ($stmt->execute()) {
        if ($room_id !== null) {
            $update = $conn->prepare("UPDATE Room SET OccupiedCount = OccupiedCount + 1 WHERE RoomID = ?");
            $update->bind_param("i", $room_id);
            $update->execute();
            $message = "✅ Registration successful. Assigned to Room ID: $room_id";
        } else {
            $message = "✅ Registration successful, but no room available right now.";
        }

        if (!empty($_POST['ContactNumber'])) {
            foreach ($_POST['ContactNumber'] as $contact) {
                if (!empty(trim($contact))) {
                    $stmtContact = $conn->prepare("INSERT INTO studentcontactnumber (StudentID, ContactNumber) VALUES (?, ?)");
                    $stmtContact->bind_param("is", $StudentID, $contact);
                    $stmtContact->execute();
                }
            }
        }

        if (!empty($_POST['Email'])) {
            foreach ($_POST['Email'] as $email) {
                if (!empty(trim($email))) {
                    $stmtEmail = $conn->prepare("INSERT INTO studentemail (StudentID, Email) VALUES (?, ?)");
                    $stmtEmail->bind_param("is", $StudentID, $email);
                    $stmtEmail->execute();
                }
            }
        }

    } else {
        $message = "❌ Error: " . $stmt->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student Registration - NSU Hostel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
    <style>
        body {
            background-color: #f5f5dc; /* Beige */
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .card {
            background: #ffffff;
            border-radius: 1rem;
            box-shadow: 0 8px 16px rgba(107, 142, 35, 0.2);
            border: 1px solid #c4c8b5;
        }
        h2 {
            color: #7b8d6d;
        }
        .btn-primary {
            background-color: #7b8d6d;
            border-color: #7b8d6d;
            transition: background-color 0.3s ease;
        }
        .btn-primary:hover {
            background-color: #6a7b5d;
            border-color: #6a7b5d;
        }
        .form-control, .form-select {
            border: 1.5px solid #c4c8b5;
            border-radius: 0.5rem;
            background-color: #faf8f2;
            transition: border-color 0.3s ease, background-color 0.3s ease;
        }
        .form-control:focus, .form-select:focus {
            border-color: #7b8d6d;
            box-shadow: 0 0 5px rgba(123, 141, 109, 0.5);
            background-color: #ffffff;
        }
        a.link-primary {
            color: #7b8d6d;
            font-weight: 600;
        }
        a.link-primary:hover {
            color: #6a7b5d;
            text-decoration: none;
        }
    </style>
</head>
<body>
<div class="container d-flex flex-column justify-content-center align-items-center" style="min-height: 100vh;">
    <div class="card p-4 w-100" style="max-width: 800px;">
        <h2 class="text-center mb-4 fw-bold">Student Registration</h2>

        <?php if (!empty($message)): ?>
            <div class="alert alert-info"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <form method="POST" class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Student ID</label>
                <input type="text" name="StudentID" class="form-control" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Name</label>
                <input type="text" name="StudentName" class="form-control" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Gender</label>
                <select name="Gender" class="form-select" required>
                    <option value="">Select</option>
                    <option value="M">Male</option>
                    <option value="F">Female</option>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">Date of Birth</label>
                <input type="date" name="DateOfBirth" class="form-control" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Street</label>
                <input type="text" name="Street" class="form-control" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">City</label>
                <input type="text" name="City" class="form-control" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">District</label>
                <input type="text" name="District" class="form-control" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Postal Code</label>
                <input type="text" name="PostalCode" class="form-control" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Department</label>
                <input type="text" name="Department" class="form-control" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Year</label>
                <input type="number" name="Year" class="form-control" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">CGPA</label>
                <input type="number" step="0.01" name="CGPA" class="form-control" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" required>
            </div>

            <!-- Contact Numbers -->
            <div class="col-md-6">
                <label class="form-label">Contact Numbers</label>
                <div id="contactFields">
                    <input type="text" name="ContactNumber[]" class="form-control mb-2">
                </div>
                <button type="button" class="btn btn-sm btn-secondary" onclick="addContact()">+ Add More</button>
            </div>

            <!-- Emails -->
            <div class="col-md-6">
                <label class="form-label">Emails</label>
                <div id="emailFields">
                    <input type="email" name="Email[]" class="form-control mb-2">
                </div>
                <button type="button" class="btn btn-sm btn-secondary" onclick="addEmail()">+ Add More</button>
            </div>

            <div class="col-12">
                <button type="submit" class="btn btn-primary w-100">Register</button>
                <p class="mt-3 text-center">
                    Already registered? <a href="index.html" class="link-primary">Login here</a>
                </p>
            </div>
        </form>
    </div>
</div>

<script>
function addContact() {
    let div = document.createElement("div");
    div.innerHTML = '<input type="text" name="ContactNumber[]" class="form-control mb-2">';
    document.getElementById("contactFields").appendChild(div);
}
function addEmail() {
    let div = document.createElement("div");
    div.innerHTML = '<input type="email" name="Email[]" class="form-control mb-2">';
    document.getElementById("emailFields").appendChild(div);
}
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
