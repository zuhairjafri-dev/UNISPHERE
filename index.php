<?php
include 'db.php';

if (!empty($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit();
}

$error = '';

if (isset($_POST['submit'])) {
    verify_csrf();

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $age = (int)($_POST['age'] ?? 0);
    $gender = $_POST['gender'] ?? '';
    $role = $_POST['role'] ?? '';
    $password = $_POST['password'] ?? '';
    $cpassword = $_POST['cpassword'] ?? '';
    $allowedRoles = ['Student', 'Teacher'];

    if ($password !== $cpassword) {
        $error = 'Passwords do not match.';
    } elseif ($age < 15 || $age > 90) {
        $error = 'Please enter a valid age.';
    } elseif (!valid_gender($gender) || !in_array($role, $allowedRoles, true) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please select a valid role.';
    } else {
        $check = $conn->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $check->bind_param('s', $email);
        $check->execute();

        if ($check->get_result()->num_rows > 0) {
            $error = 'An account with this email already exists.';
        } else {
            $image = upload_profile_image('image');

            if ($image === false) {
                $error = 'Only JPG, PNG, GIF, and WEBP profile images are allowed.';
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $enrollment_id = trim($_POST['enrollment_id'] ?? '');
                $specialization = trim($_POST['specialization'] ?? '');
                $semester = trim($_POST['semester'] ?? '');
                $session_batch = trim($_POST['session_batch'] ?? '');
                $designation = trim($_POST['designation'] ?? '');
                $department = trim($_POST['department'] ?? '');
                $main_subject = trim($_POST['main_subject'] ?? '');

                if (column_exists($conn, 'users', 'plain_password')) {
                    $plain_password = '';
                    $stmt = $conn->prepare(
                        'INSERT INTO users
                        (name, email, age, gender, role, password, plain_password, image, enrollment_id, specialization, semester, session_batch, designation, department, main_subject)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
                    );
                    $stmt->bind_param(
                        'ssissssssssssss',
                        $name,
                        $email,
                        $age,
                        $gender,
                        $role,
                        $hash,
                        $plain_password,
                        $image,
                        $enrollment_id,
                        $specialization,
                        $semester,
                        $session_batch,
                        $designation,
                        $department,
                        $main_subject
                    );
                } else {
                    $stmt = $conn->prepare(
                        'INSERT INTO users
                        (name, email, age, gender, role, password, image, enrollment_id, specialization, semester, session_batch, designation, department, main_subject)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
                    );
                    $stmt->bind_param(
                        'ssisssssssssss',
                        $name,
                        $email,
                        $age,
                        $gender,
                        $role,
                        $hash,
                        $image,
                        $enrollment_id,
                        $specialization,
                        $semester,
                        $session_batch,
                        $designation,
                        $department,
                        $main_subject
                    );
                }

                if ($stmt->execute()) {
                    redirect_with_status('login.php', 'created');
                }

                $error = 'Registration failed. Please try again.';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | UniSphere</title>
    <link href="bootsrap/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body class="auth-page">
    <main class="auth-card">
        <div class="page-header mb-4">
            <div>
                <div class="page-kicker">Create Account</div>
                <h1 class="page-title h3">Join UniSphere</h1>
            </div>
            <a href="login.php" class="btn btn-outline-light btn-sm">Login</a>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo e($error); ?></div>
        <?php endif; ?>

        <form action="" method="POST" autocomplete="off" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Full Name</label>
                    <input type="text" name="name" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Email Address</label>
                    <input type="email" name="email" class="form-control" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Age</label>
                    <input type="number" name="age" min="15" max="90" class="form-control" required>
                </div>
                <div class="col-md-8">
                    <label class="form-label">Gender</label>
                    <div class="d-flex gap-2 gender-selection">
                        <div class="w-50">
                            <input type="radio" name="gender" value="Male" id="male" required>
                            <label for="male" class="gender-label">Male</label>
                        </div>
                        <div class="w-50">
                            <input type="radio" name="gender" value="Female" id="female">
                            <label for="female" class="gender-label">Female</label>
                        </div>
                    </div>
                </div>
                <div class="col-12">
                    <label class="form-label">Role</label>
                    <select name="role" id="roleSelect" class="form-select" required>
                        <option value="" disabled selected>Select role</option>
                        <option value="Student">Student</option>
                        <option value="Teacher">Teacher</option>
                    </select>
                </div>
            </div>

            <div id="studentFields" class="dynamic-fields mt-3">
                <div class="page-kicker">Student Academic Details</div>
                <div class="row g-3">
                    <div class="col-md-6"><input type="text" name="enrollment_id" class="form-control" placeholder="Enrollment ID"></div>
                    <div class="col-md-6"><input type="text" name="specialization" class="form-control" placeholder="Specialization"></div>
                    <div class="col-md-6"><input type="text" name="semester" class="form-control" placeholder="Semester"></div>
                    <div class="col-md-6"><input type="text" name="session_batch" class="form-control" placeholder="Session batch"></div>
                </div>
            </div>

            <div id="teacherFields" class="dynamic-fields mt-3">
                <div class="page-kicker">Faculty Details</div>
                <div class="row g-3">
                    <div class="col-md-4"><input type="text" name="designation" class="form-control" placeholder="Designation"></div>
                    <div class="col-md-4"><input type="text" name="department" class="form-control" placeholder="Department"></div>
                    <div class="col-md-4"><input type="text" name="main_subject" class="form-control" placeholder="Main subject"></div>
                </div>
            </div>

            <div class="row g-3 mt-1">
                <div class="col-md-6">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control" minlength="6" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Confirm Password</label>
                    <input type="password" name="cpassword" class="form-control" minlength="6" required>
                </div>
                <div class="col-12">
                    <label class="form-label">Profile Image</label>
                    <input type="file" name="image" class="form-control" accept="image/png,image/jpeg,image/gif,image/webp">
                </div>
            </div>

            <button type="submit" name="submit" class="btn btn-warning w-100 mt-4 py-2">Create Account</button>
        </form>
    </main>

    <script>
        const roleSelect = document.getElementById('roleSelect');
        const studentFields = document.getElementById('studentFields');
        const teacherFields = document.getElementById('teacherFields');

        roleSelect.addEventListener('change', function () {
            studentFields.style.display = this.value === 'Student' ? 'block' : 'none';
            teacherFields.style.display = this.value === 'Teacher' ? 'block' : 'none';
        });
    </script>
</body>
</html>
