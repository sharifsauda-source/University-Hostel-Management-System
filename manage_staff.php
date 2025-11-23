<?php
session_start();
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'Admin') {
    header("Location: index.html");
    exit;
}

include("includes/db_config.php");


if (isset($_POST['add_staff'])) {
    $name       = trim($_POST['name']);
    $gender     = $_POST['gender'];
    $shift_id   = intval($_POST['shift_id']);
    $password   = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role       = $_POST['role'] ?? '';
    $supervisor_id = !empty($_POST['supervisor_id']) ? intval($_POST['supervisor_id']) : null;
    $contacts   = $_POST['contact_numbers'] ?? [];


    $stmt = $conn->prepare("INSERT INTO Staff (StaffName, Gender, ShiftID, Password) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssis", $name, $gender, $shift_id, $password);
    $stmt->execute();
    $staff_id = $conn->insert_id;
    $stmt->close();

    if (!empty($contacts)) {
        $stmt = $conn->prepare("INSERT INTO StaffContactNumber (StaffID, ContactNumber) VALUES (?, ?)");
        foreach ($contacts as $number) {
            if (trim($number) !== "") {
                $stmt->bind_param("is", $staff_id, $number);
                $stmt->execute();
            }
        }
        $stmt->close();
    }

 
    if (!empty($supervisor_id)) {
        $remarks = "Initial assignment";
        $stmt = $conn->prepare("INSERT INTO Supervise (SupervisorID, SubordinateID, StartDate, Remarks) VALUES (?, ?, CURDATE(), ?)");
        $stmt->bind_param("iis", $supervisor_id, $staff_id, $remarks);
        $stmt->execute();
        $stmt->close();
    }

    if ($role === "Warden") {
        $stmt = $conn->prepare("INSERT INTO Warden (StaffID, OfficeNumber, FloorAssigned) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $staff_id, $_POST['office_number'], $_POST['floor_assigned']);
        $stmt->execute(); $stmt->close();
    } elseif ($role === "Cleaner") {
        $stmt = $conn->prepare("INSERT INTO Cleaner (StaffID, CleaningArea) VALUES (?, ?)");
        $stmt->bind_param("is", $staff_id, $_POST['cleaning_area']);
        $stmt->execute(); $stmt->close();
    } elseif ($role === "Technician") {
        $stmt = $conn->prepare("INSERT INTO Technician (StaffID, SpecializedArea) VALUES (?, ?)");
        $stmt->bind_param("is", $staff_id, $_POST['specialized_area']);
        $stmt->execute(); $stmt->close();
    }
}

// GET SUPERVISORS
$supervisors = $conn->query("SELECT StaffID, StaffName FROM Staff ORDER BY StaffName ASC");

// GET STAFF LIST
$staff_list = $conn->query("
    SELECT s.StaffID, s.StaffName, s.Gender, s.ShiftID,
        CASE 
            WHEN w.StaffID IS NOT NULL THEN 'Warden'
            WHEN c.StaffID IS NOT NULL THEN 'Cleaner'
            WHEN t.StaffID IS NOT NULL THEN 'Technician'
            ELSE 'General Staff'
        END AS Role,
        sup.StaffName AS Supervisor
    FROM Staff s
    LEFT JOIN Warden w ON s.StaffID = w.StaffID
    LEFT JOIN Cleaner c ON s.StaffID = c.StaffID
    LEFT JOIN Technician t ON s.StaffID = t.StaffID
    LEFT JOIN Supervise sv ON s.StaffID = sv.SubordinateID
    LEFT JOIN Staff sup ON sv.SupervisorID = sup.StaffID
    ORDER BY s.StaffID DESC
");

// Fetch contacts
$contacts_result = $conn->query("SELECT StaffID, ContactNumber FROM StaffContactNumber");
$staff_contacts = [];
while ($row = $contacts_result->fetch_assoc()) {
    $staff_contacts[$row['StaffID']][] = $row['ContactNumber'];
}

// --- DELETE STAFF ---
if (isset($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);

    // Delete role-specific table entries first to avoid foreign key issues
    $conn->query("DELETE FROM Warden WHERE StaffID = $delete_id");
    $conn->query("DELETE FROM Cleaner WHERE StaffID = $delete_id");
    $conn->query("DELETE FROM Technician WHERE StaffID = $delete_id");

    // Delete contacts
    $conn->query("DELETE FROM StaffContactNumber WHERE StaffID = $delete_id");

    // Delete supervision relations
    $conn->query("DELETE FROM Supervise WHERE SupervisorID = $delete_id OR SubordinateID = $delete_id");

    // Finally, delete from Staff
    $conn->query("DELETE FROM Staff WHERE StaffID = $delete_id");

    // Redirect to prevent resubmission
    header("Location: manage_staff.php");
    exit;
}

?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Manage Staff - Admin</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body {
    background-color: #f5f5dc;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}
h2 {
    color: #7b8d6d;
}
.card {
    background: #ffffff;
    border-radius: 1rem;
    border: 1px solid #c4c8b5;
    box-shadow: 0 8px 16px rgba(107, 142, 35, 0.2);
}
.table thead {
    background-color: #c4c8b5;
    color: #fff;
}
.btn-primary {
    background-color: #7b8d6d;
    border-color: #7b8d6d;
}
.btn-primary:hover { background-color: #6a7b5d; }
.btn-secondary {
    background-color: #c4c8b5; border-color: #c4c8b5; color: #fff;
}
.btn-secondary:hover { background-color: #aeb18d; }
.btn-danger { background-color: #c94c4c; border-color: #c94c4c; color: #fff; }
.btn-danger:hover { background-color: #a93b3b; }
</style>
<script>
function addContactField() {
    const container = document.getElementById("contact-container");
    const input = document.createElement("input");
    input.type = "text"; input.name = "contact_numbers[]";
    input.className = "form-control mt-1";
    input.placeholder = "Contact Number";
    container.appendChild(input);
}
</script>
</head>
<body class="container my-4">

<div class="card p-4 mb-4">
    <h2>Manage Staff</h2>
    <h5 class="mt-3">Add New Staff</h5>
    <form method="post">
        <div class="row g-3 mb-2">
            <div class="col-md-3"><input type="text" name="name" class="form-control" placeholder="Staff Name" required></div>
            <div class="col-md-2">
                <select name="gender" class="form-control" required>
                    <option value="">Gender</option><option>Male</option><option>Female</option><option>Other</option>
                </select>
            </div>
            <div class="col-md-2"><input type="number" name="shift_id" class="form-control" placeholder="Shift ID" required></div>
            <div class="col-md-2"><input type="password" name="password" class="form-control" placeholder="Password" required></div>
            <div class="col-md-3">
                <select name="role" class="form-control">
                    <option value="">General Staff</option>
                    <option value="Warden">Warden</option>
                    <option value="Cleaner">Cleaner</option>
                    <option value="Technician">Technician</option>
                </select>
            </div>
        </div>

        <div class="row g-3 mb-2">
            <div class="col-md-6" id="contact-container">
                <input type="text" name="contact_numbers[]" class="form-control" placeholder="Contact Number">
            </div>
            <div class="col-md-2">
                <button type="button" class="btn btn-secondary" onclick="addContactField()">+ Add More</button>
            </div>
        </div>

        <div class="row g-3 mt-2">
            <div class="col-md-4">
                <select name="supervisor_id" class="form-control">
                    <option value="">No Supervisor</option>
                    <?php while ($sup = $supervisors->fetch_assoc()): ?>
                        <option value="<?=$sup['StaffID']?>"><?=$sup['StaffName']?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" name="add_staff" class="btn btn-primary w-100">Add</button>
            </div>
        </div>
    </form>
</div>

<div class="card p-3">
    <h5>Staff List</h5>
    <div class="table-responsive">
    <table class="table table-bordered align-middle">
        <thead>
            <tr>
                <th>Staff ID</th><th>Name</th><th>Gender</th><th>Shift</th><th>Role</th><th>Supervisor</th><th>Contacts</th><th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($staff = $staff_list->fetch_assoc()): ?>
                <tr>
                    <td><?=$staff['StaffID']?></td>
                    <td><?=$staff['StaffName']?></td>
                    <td><?=$staff['Gender']?></td>
                    <td><?=$staff['ShiftID']?></td>
                    <td><?=$staff['Role']?></td>
                    <td><?=$staff['Supervisor'] ?: '—'?></td>
                    <td>
                        <?php 
                        if (!empty($staff_contacts[$staff['StaffID']])) {
                            echo implode("<br>", array_map('htmlspecialchars', $staff_contacts[$staff['StaffID']]));
                        } else { echo "—"; }
                        ?>
                    </td>
                    <td>
                        <a href="?delete=<?=$staff['StaffID']?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure?')">Delete</a>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    </div>
</div>

<a href="admin_dashboard.php" class="btn btn-secondary mt-3">Back to Dashboard</a>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
