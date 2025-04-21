<?php
session_start();

if (!isset($_SESSION['employee_id']) || empty($_SESSION['employee_id'])) {
    echo "Session not set. Redirecting in 2 seconds...";
    header("Refresh: 2; URL=index.php");
    exit();
}

include 'db_config.php';

$employee_id = $_SESSION['employee_id'];
$query = "SELECT * FROM employees WHERE id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $employee_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$employee = mysqli_fetch_assoc($result);

if (!$employee) {
    echo "<p>Error: Employee not found.</p>";
    exit();
}

$profile_picture = (!empty($employee['photo']) && file_exists($employee['photo']))
    ? $employee['photo']
    : 'default.png';

if (!file_exists($profile_picture)) {
    error_log("Profile picture not found: $profile_picture");
    $profile_picture = 'default.png';
}

$user = [
    "name" => htmlspecialchars($employee['name']),
    "avatar" => htmlspecialchars($profile_picture)
];

$leaveRecords = [
    ["date" => "03/07/2021", "type" => "Casual leave", "duration" => "02 (05-06 Jul)", "status" => "Pending"],
    ["date" => "01/07/2022", "type" => "Late entry", "duration" => "01 (06 Jul)", "status" => "Rejected"]
];

$leaveCredits = [
    ["type" => "Casual Leave", "used" => 12, "total" => 15],
    ["type" => "Sick Leave", "used" => 10, "total" => 15],
    ["type" => "Maternity Leave", "used" => 5, "total" => 15],
    ["type" => "Short Leave", "used" => 15, "total" => 15]
];

function getStatusBadge($status) {
    switch ($status) {
        case "Approved": return '<span class="badge bg-success">Approved</span>';
        case "Rejected": return '<span class="badge bg-danger">Rejected</span>';
        default: return '<span class="badge bg-warning text-dark">Pending</span>';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Employee Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        body {
            background-color: #f4f6f9;
            font-family: 'Segoe UI', sans-serif;
        }

        .sidebar {
            width: 260px;
            min-height: 100vh;
            background-color: #212529;
            color: white;
            position: fixed;
            padding: 20px;
        }

        .sidebar h1 {
            font-size: 1.8rem;
        }

        .sidebar a {
            text-align: left;
        }

        .main-content {
            margin-left: 260px;
        }

        .profile-img {
            border: 2px solid #dee2e6;
            object-fit: cover;
        }

        .table-hover tbody tr:hover {
            background-color: #f1f1f1;
        }

        .progress-bar {
            transition: width 0.6s ease;
        }
    </style>

    <script>
        function updateClock() {
            const now = new Date();
            document.getElementById('currentTime').innerText = now.toLocaleTimeString();
        }
        window.onload = function () {
            updateClock();
            setInterval(updateClock, 1000);
        };
    </script>
</head>
<body>

<div class="sidebar">
    <h1 class="text-danger">INKO<span class="text-warning">MOKO</span>.</h1>
    <nav class="mt-4 d-grid gap-2">
        <a href="#" class="btn btn-primary"><i class="bi bi-speedometer2"></i> Overview</a>
        <a href="#" class="btn btn-outline-light"><i class="bi bi-calendar-check"></i> Attendance</a>
        <a href="#" class="btn btn-outline-light"><i class="bi bi-door-open"></i> Leave</a>
        <a href="#" class="btn btn-outline-light"><i class="bi bi-currency-rupee"></i> Payroll</a>
    </nav>
    <a href="logout.php" class="btn btn-danger w-100 mt-4"><i class="bi bi-box-arrow-right"></i> Logout</a>
</div>

<div class="main-content">
    <!-- Header -->
    <header class="d-flex justify-content-between align-items-center p-3 bg-white shadow-sm">
        <div class="input-group w-50">
            <span class="input-group-text"><i class="bi bi-search"></i></span>
            <input type="text" class="form-control" placeholder="Search...">
            <span class="input-group-text"><i class="bi bi-mic"></i></span>
        </div>
        <div class="d-flex align-items-center gap-3">
            <i class="bi bi-bell fs-4"></i>
            <div class="d-flex align-items-center">
                <img src="<?= $user['avatar']; ?>" class="rounded-circle profile-img" width="45" height="45" alt="User Image">
                <div class="ms-2">
                    <div class="fw-semibold"><?= $user['name']; ?></div>
                    <small class="text-muted"><?= date("d M, Y") ?></small>
                </div>
            </div>
        </div>
    </header>

    <!-- Date Banner -->
    <div class="bg-warning text-dark py-2 px-4">
        <div class="d-flex justify-content-between align-items-center">
            <span><i class="bi bi-cloud-sun-fill"></i> Partly Cloudy</span>
            <span class="fw-bold fs-5" id="currentTime"><?= date("h:i A"); ?></span>
        </div>
    </div>

    <!-- Approval Table -->
    <div class="p-4">
        <h3 class="mb-3">📄 Leave Applications</h3>
        <div class="table-responsive">
            <table class="table table-hover table-bordered align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Date of Application</th>
                        <th>Type</th>
                        <th>Duration</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($leaveRecords as $record): ?>
                        <tr>
                            <td><?= htmlspecialchars($record["date"]); ?></td>
                            <td><?= htmlspecialchars($record["type"]); ?></td>
                            <td><?= htmlspecialchars($record["duration"]); ?></td>
                            <td><?= getStatusBadge($record["status"]); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Leave Credits -->
    <div class="p-4">
        <h3 class="mb-3">🧾 Leave Credits</h3>
        <?php foreach ($leaveCredits as $leave): ?>
            <div class="d-flex align-items-center mb-3">
                <div class="w-25 fw-medium"><?= htmlspecialchars($leave["type"]); ?></div>
                <div class="progress w-50" title="<?= $leave['used']; ?>/<?= $leave['total']; ?> used">
                    <div class="progress-bar bg-success" style="width: <?= ($leave["used"] / $leave["total"]) * 100; ?>%" 
                        aria-valuenow="<?= $leave["used"]; ?>" aria-valuemin="0" aria-valuemax="<?= $leave["total"]; ?>"></div>
                </div>
                <div class="ms-3 text-muted small"><?= $leave["used"]; ?>/<?= $leave["total"]; ?></div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

</body>
</html>
