<?php
include 'db_config.php';
session_start();
$errors = [];

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["register"])) {
    // Initialize errors array
    $errors = [];
    
    // Get and sanitize inputs
    $name = trim($_POST["name"]);
    $department = trim($_POST["department"]);
    $email = trim($_POST["email"]);
    $password = trim($_POST["password"]);

    // Validate required fields
    if (empty($name) || empty($email) || empty($password) || empty($department)) {
        $errors[] = "All fields are required for registration.";
    }
    
    // Validate image upload
    $imageValid = false;
    if (empty($_FILES["image"]["name"])) {
        $errors[] = "Profile picture is required.";
    } else {
        // Check for upload errors
        if ($_FILES["image"]["error"] !== UPLOAD_ERR_OK) {
            $errors[] = "Error uploading profile picture: " . $_FILES["image"]["error"];
        } else {
            // Validate image type
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
            $detectedType = mime_content_type($_FILES["image"]["tmp_name"]);
            
            if (!in_array($detectedType, $allowedTypes)) {
                $errors[] = "Only JPG, PNG, and GIF images are allowed.";
            }
            
            // Validate file size (2MB max)
            if ($_FILES["image"]["size"] > 2097152) {
                $errors[] = "Image size must be less than 2MB.";
            } else {
                $imageValid = true;
            }
        }
    }

    // Only proceed if no errors
    if (empty($errors)) {
        // Check if email exists
        $checkQuery = "SELECT id FROM employees WHERE email = ?";
        $stmt = mysqli_prepare($conn, $checkQuery);
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);

        if (mysqli_stmt_num_rows($stmt) > 0) {
            $errors[] = "Email already exists. Please use another email.";
        } elseif ($imageValid) {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // Prepare upload directory
            $target_dir = "uploads/";
            if (!is_dir($target_dir)) {
                mkdir($target_dir, 0755, true);
            }

            // Generate safe filename
            $extension = strtolower(pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION));
            $imageName = uniqid() . '_' . preg_replace('/[^a-z0-9]/', '_', strtolower($name)) . '.' . $extension;
            $target_file = $target_dir . $imageName;

            // Move uploaded file
            if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
                // Insert into database
                $query = "INSERT INTO employees (name, email, password, department, photo) VALUES (?, ?, ?, ?, ?)";
                $stmt = mysqli_prepare($conn, $query);
                mysqli_stmt_bind_param($stmt, "sssss", $name, $email, $hashed_password, $department, $target_file);

                if (mysqli_stmt_execute($stmt)) {
                    $_SESSION["employee_id"] = mysqli_insert_id($conn);
                    header("Location: /HR_management/employee_dashboard.php");
                    exit();
                } else {
                    // Delete the uploaded file if DB insert failed
                    unlink($target_file);
                    $errors[] = "Registration failed. Please try again.";
                }
            } else {
                $errors[] = "Failed to upload profile picture.";
            }
        }
    }
    
    // If we got here, there were errors
    $_SESSION['errors'] = $errors;
    header("Location: login.php"); // Redirect back to form
    exit();
} 
elseif (isset($_POST["login"])) {
        $email = trim($_POST["email"]);
        $password = trim($_POST["password"]);

        if (empty($email) || empty($password)) {
            $errors[] = "Both email and password are required.";
        } else {
            $query = "SELECT * FROM employees WHERE email = ?";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "s", $email);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $user = mysqli_fetch_assoc($result);

            if ($user && password_verify($password, $user["password"])) {
                $_SESSION["employee_id"] = $user["id"];
                header("Location: employee_dashboard.php");
                exit();
            } else {
                $errors[] = "Invalid email or password.";
            }
        }
    }

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login & Signup</title>
    <link rel="stylesheet" href="login.css">
</head>
<body>

    <div class="container">
        <div class="left-section">
            <img src="https://media1.thehungryjpeg.com/thumbs2/ori_72694_6d95c93dab08c07b9c5a967af71fe1ed9d161f07_new-job.jpg" alt="Background">
        </div>

        <div class="right-section">
            <?php if (!empty($errors)): ?>
                <div class="error">
                    <?php foreach ($errors as $error) echo "<p>$error</p>"; ?>
                </div>
            <?php endif; ?>

            <div id="login-form">
                <h2>Log in to your account</h2>
                <p>Don't have an account? <a href="#" onclick="toggleForm()">Sign up</a></p>

                <form method="post">
                    <label>Email address</label>
                    <input type="email" name="email" required>

                    <label>Password</label>
                    <input type="password" name="password" required>

                    <button type="submit" name="login">Log in</button>
                </form>
            </div>

            <div id="signup-form" class="hidden">
                <h2>Create an account</h2>
                <p>Already have an account? <a href="#" onclick="toggleForm()">Log in</a></p>

                <form method="post" enctype="multipart/form-data">
                    <label>Name</label>
                    <input type="text" name="name" required>
                    <label>Department</label>
                    <input type="text" name="department" required>
                    <label>Email address</label>
                    <input type="email" name="email" required>

                    <label>Password</label>
                    <input type="password" name="password" required>
                    <label>Upload Profile Picture:</label>
                      <input type="file" name="image" accept="image/*" required>

                    <button type="submit" name="register">Create account</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        function toggleForm() {
            document.getElementById("login-form").classList.toggle("hidden");
            document.getElementById("signup-form").classList.toggle("hidden");
        }
    </script>

</body>
</html>
