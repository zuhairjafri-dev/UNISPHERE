<?php
include 'db.php';
protect_page();

$id = (int)($_GET['id'] ?? 0);

if (!is_admin() && $id !== current_user_id()) {
    header('Location: profile.php');
    exit();
}

$user = get_user_by_id($conn, $id);

if (!$user) {
    header('Location: dashboard.php');
    exit();
}

$user_role = strtolower($user['role'] ?? 'student');
$city = user_field($user, 'city');
$state = user_field($user, 'state');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile | UniSphere</title>
    <link href="bootsrap/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            margin: 0;
            min-height: 100vh;
            background: radial-gradient(circle at top left, rgba(255, 147, 0, 0.18), transparent 22%),
                        radial-gradient(circle at bottom right, rgba(96, 165, 250, 0.14), transparent 26%),
                        linear-gradient(135deg, #060812 0%, #0a1223 42%, #0f172a 100%);
            color: #e2e8f0;
            font-family: Inter, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }

        .app-shell {
            display: grid;
            grid-template-columns: 280px minmax(0, 1fr);
            gap: 1.5rem;
            padding: 2rem;
            max-width: 1400px;
            margin: 0 auto;
        }

        .sidebar-panel {
            position: sticky;
            top: 2rem;
            align-self: start;
            background: rgba(15, 23, 42, 0.88);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 28px;
            padding: 1.75rem 1.5rem;
            box-shadow: 0 40px 120px rgba(10, 24, 60, 0.22);
        }

        .brand-link {
            display: flex;
            align-items: center;
            gap: 0.9rem;
            text-decoration: none;
            color: #f8fafc;
        }

        .brand-mark {
            width: 44px;
            height: 44px;
            display: grid;
            place-items: center;
            border-radius: 16px;
            background: linear-gradient(135deg, #f59e0b, #fb923c);
            font-weight: 700;
            color: #111827;
        }

        .brand-title {
            display: block;
            font-size: 1.05rem;
            font-weight: 700;
        }

        .brand-subtitle {
            display: block;
            margin-top: 0.25rem;
            color: #94a3b8;
            font-size: 0.9rem;
        }

        .sidebar-menu {
            display: grid;
            gap: 0.55rem;
            margin-top: 2rem;
        }

        .sidebar-link {
            display: block;
            padding: 0.95rem 1.1rem;
            border-radius: 18px;
            color: #cbd5e1;
            text-decoration: none;
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid transparent;
            transition: transform 0.22s ease, background 0.22s ease, border-color 0.22s ease;
        }

        .sidebar-link:hover,
        .sidebar-link.active {
            background: rgba(251, 146, 60, 0.16);
            color: #fff;
            border-color: rgba(251, 146, 60, 0.28);
            transform: translateX(2px);
        }

        .sidebar-footer {
            margin-top: 2rem;
        }

        .logout-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            padding: 0.95rem 1rem;
            border-radius: 18px;
            color: #f59e0b;
            text-decoration: none;
            border: 1px solid rgba(245, 158, 11, 0.2);
            background: rgba(248, 113, 113, 0.06);
            transition: background 0.25s ease, transform 0.25s ease;
        }

        .logout-link:hover {
            background: rgba(245, 146, 30, 0.14);
            transform: translateY(-1px);
        }

        .main-content {
            display: grid;
            gap: 1.5rem;
        }

        .page-header {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .page-kicker {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: #fbbf24;
            font-size: 0.95rem;
            text-transform: uppercase;
            letter-spacing: 0.14em;
            font-weight: 700;
        }

        .page-title {
            font-size: clamp(1.75rem, 2.5vw, 2.5rem);
            margin: 0;
            color: #f8fafc;
        }

        .btn-outline-light {
            color: #e2e8f0;
            border-color: rgba(255, 255, 255, 0.2);
            transition: background 0.25s ease, border-color 0.25s ease;
        }

        .btn-outline-light:hover {
            background: rgba(255, 255, 255, 0.1);
            border-color: rgba(255, 255, 255, 0.4);
        }

        .form-card {
            padding: 2rem;
            border-radius: 28px;
            background: rgba(15, 23, 42, 0.84);
            border: 1px solid rgba(251, 146, 60, 0.22);
            border-left: 6px solid rgba(251, 146, 60, 0.95);
            box-shadow: 0 30px 80px rgba(10, 24, 60, 0.2);
        }

        .form-control,
        .form-select {
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.12);
            color: #f8fafc;
            transition: border-color 0.25s ease, box-shadow 0.25s ease;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: rgba(251, 146, 60, 0.85);
            box-shadow: 0 0 0 0.2rem rgba(251, 146, 60, 0.18);
            background: rgba(255, 255, 255, 0.12);
            color: #fff;
        }

        .form-control::placeholder {
            color: #94a3b8;
        }

        .form-label {
            color: #e2e8f0;
            font-weight: 600;
        }

        .btn-warning {
            background: linear-gradient(135deg, #f59e0b, #fb923c);
            border: none;
            color: #111827;
            transition: transform 0.25s ease, box-shadow 0.25s ease;
        }

        .btn-warning:hover {
            transform: translateY(-1px);
            box-shadow: 0 10px 28px rgba(245, 158, 11, 0.3);
        }

        @media (max-width: 768px) {
            .app-shell {
                grid-template-columns: 1fr;
            }

            .sidebar-panel {
                position: static;
                top: auto;
            }
        }
    </style>
</head>
<body>
<div class="app-shell">
    <?php include 'sidebar.php'; ?>

    <main class="main-content">
        <div class="page-header">
            <div>
                <div class="page-kicker">Edit Record</div>
                <h1 class="page-title">Update Information</h1>
            </div>
            <a href="<?php echo is_admin() ? 'table.php' : 'profile.php'; ?>" class="btn btn-outline-light">Cancel</a>
        </div>

        <section class="form-card">
            <form action="update_user.php" method="POST" enctype="multipart/form-data" class="row g-3">
                <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                <input type="hidden" name="id" value="<?php echo (int)$user['id']; ?>">

                <div class="col-md-6">
                    <label class="form-label">Full Name</label>
                    <input type="text" name="name" class="form-control" value="<?php echo e($user['name']); ?>" required>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Email Address</label>
                    <input type="email" name="email" class="form-control" value="<?php echo e($user['email']); ?>" required>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Age</label>
                    <input type="number" name="age" min="15" max="90" class="form-control" value="<?php echo e($user['age']); ?>" required>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Gender</label>
                    <select name="gender" class="form-select">
                        <option value="Male" <?php echo $user['gender'] === 'Male' ? 'selected' : ''; ?>>Male</option>
                        <option value="Female" <?php echo $user['gender'] === 'Female' ? 'selected' : ''; ?>>Female</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label">City</label>
                    <input type="text" name="city" class="form-control" value="<?php echo e($city); ?>">
                </div>

                <div class="col-md-3">
                    <label class="form-label">State</label>
                    <input type="text" name="state" class="form-control" value="<?php echo e($state); ?>">
                </div>

                <?php if (is_admin()): ?>
                    <div class="col-md-4">
                        <label class="form-label">Role</label>
                        <select name="role" class="form-select">
                            <option value="Student" <?php echo $user['role'] === 'Student' ? 'selected' : ''; ?>>Student</option>
                            <option value="Teacher" <?php echo $user['role'] === 'Teacher' ? 'selected' : ''; ?>>Teacher</option>
                            <option value="Admin" <?php echo $user['role'] === 'Admin' ? 'selected' : ''; ?>>Admin</option>
                        </select>
                    </div>
                <?php else: ?>
                    <input type="hidden" name="role" value="<?php echo e($user['role']); ?>">
                <?php endif; ?>

                <div class="col-md-4">
                    <label class="form-label"><?php echo $user_role === 'teacher' ? 'Faculty ID' : 'Enrollment ID'; ?></label>
                    <input type="text" name="enrollment_id" class="form-control" value="<?php echo e($user['enrollment_id']); ?>">
                </div>

                <div class="col-md-4">
                    <label class="form-label">Specialization</label>
                    <input type="text" name="specialization" class="form-control" value="<?php echo e($user['specialization']); ?>">
                </div>

                <div class="col-md-4">
                    <label class="form-label">Semester</label>
                    <input type="text" name="semester" class="form-control" value="<?php echo e($user['semester']); ?>">
                </div>

                <div class="col-md-4">
                    <label class="form-label">Session Batch</label>
                    <input type="text" name="session_batch" class="form-control" value="<?php echo e($user['session_batch']); ?>">
                </div>

                <div class="col-md-4">
                    <label class="form-label">Designation</label>
                    <input type="text" name="designation" class="form-control" value="<?php echo e($user['designation']); ?>">
                </div>

                <div class="col-md-4">
                    <label class="form-label">Department</label>
                    <input type="text" name="department" class="form-control" value="<?php echo e($user['department']); ?>">
                </div>

                <div class="col-md-4">
                    <label class="form-label">Main Subject</label>
                    <input type="text" name="main_subject" class="form-control" value="<?php echo e($user['main_subject']); ?>">
                </div>

                <div class="col-md-8">
                    <label class="form-label">Profile Image</label>
                    <input type="file" name="profile_image" class="form-control" accept="image/png,image/jpeg,image/gif,image/webp">
                </div>

                <div class="col-12">
                    <button type="submit" class="btn btn-warning px-4">Save Changes</button>
                </div>
            </form>
        </section>
    </main>
</div>

<script src="bootsrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>

