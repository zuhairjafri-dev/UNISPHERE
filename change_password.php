<?php
include 'db.php';
protect_page();

$error = '';
$success = '';

if (isset($_POST['change'])) {
    verify_csrf();

    $old = trim($_POST['old_password'] ?? '');
    $new = trim($_POST['new_password'] ?? '');
    $confirm = trim($_POST['confirm_password'] ?? '');
    $user = get_user_by_id($conn, current_user_id());

    if (!$user || !password_verify($old, $user['password'])) {
        $error = 'Old password is incorrect.';
    } elseif (strlen($new) < 6) {
        $error = 'New password must be at least 6 characters.';
    } elseif ($new !== $confirm) {
        $error = 'New password and confirmation do not match.';
    } elseif ($new === $old) {
        $error = 'New password must be different from the old password.';
    } else {
        $hashed = password_hash($new, PASSWORD_DEFAULT);
        $stmt = $conn->prepare('UPDATE users SET password = ? WHERE id = ?');
        $userId = current_user_id();
        $stmt->bind_param('si', $hashed, $userId);

        if ($stmt->execute()) {
            $success = 'Password updated successfully.';
        } else {
            $error = 'Unable to update password. Please try again.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password | UniSphere</title>
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
            margin-bottom: 1rem;
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
            font-size: clamp(1.5rem, 2.5vw, 2.5rem);
            margin: 0;
            color: #f8fafc;
        }

        .glass-card {
            padding: 2rem;
            border-radius: 28px;
            background: rgba(15, 23, 42, 0.86);
            border: 1px solid rgba(251, 146, 60, 0.22);
            border-left: 6px solid rgba(251, 146, 60, 0.95);
            box-shadow: 0 30px 80px rgba(10, 24, 60, 0.24);
            max-width: 760px;
            width: 100%;
            margin-inline: auto;
            backdrop-filter: blur(18px);
            -webkit-backdrop-filter: blur(18px);
        }

        .glass-card h2 {
            margin-top: 0;
            margin-bottom: 1.25rem;
            color: #f8fafc;
            font-size: 1.75rem;
            letter-spacing: 0.01em;
        }

        .form-stack {
            display: grid;
            gap: 1.25rem;
        }

        .form-label {
            color: #e2e8f0;
            font-weight: 600;
            letter-spacing: 0.01em;
            margin-bottom: 0.35rem;
            display: block;
        }

        .form-control {
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.12);
            color: #f8fafc;
            transition: border-color 0.25s ease, box-shadow 0.25s ease;
        }

        .form-control:focus {
            border-color: rgba(251, 146, 60, 0.85);
            box-shadow: 0 0 0 0.2rem rgba(251, 146, 60, 0.18);
            background: rgba(255, 255, 255, 0.12);
            color: #fff;
        }

        .form-note {
            color: #cbd5e1;
            font-size: 0.95rem;
            margin-top: -0.4rem;
        }

        .btn-warning {
            background: linear-gradient(135deg, #f59e0b, #fb923c);
            border: none;
            color: #111827;
            font-weight: 700;
            transition: transform 0.25s ease, box-shadow 0.25s ease;
        }

        .btn-warning:hover {
            transform: translateY(-1px);
            box-shadow: 0 12px 28px rgba(245, 158, 11, 0.32);
        }

        .alert {
            border-radius: 18px;
            background: rgba(248, 113, 113, 0.14);
            border: 1px solid rgba(248, 113, 113, 0.18);
            color: #fee2e2;
        }

        .alert-success {
            border-radius: 18px;
            background: rgba(34, 197, 94, 0.14);
            border: 1px solid rgba(34, 197, 94, 0.18);
            color: #d1fae5;
        }

        @media (max-width: 992px) {
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
                <div class="page-kicker">Security</div>
                <h1 class="page-title">Change Password</h1>
            </div>
        </div>

        <section class="glass-card">
            <?php if ($error): ?>
                <div class="alert alert-danger mb-4"><?php echo e($error); ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success mb-4"><?php echo e($success); ?></div>
            <?php endif; ?>

            <h2>Update your account security</h2>

            <form method="POST" class="form-stack" novalidate>
                <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">

                <div>
                    <label class="form-label" for="old_password">Old Password</label>
                    <input id="old_password" type="password" name="old_password" class="form-control" minlength="6" required>
                </div>

                <div>
                    <label class="form-label" for="new_password">New Password</label>
                    <input id="new_password" type="password" name="new_password" class="form-control" minlength="6" required>
                    <p class="form-note">At least 6 characters; use letters, numbers, and symbols.</p>
                </div>

                <div>
                    <label class="form-label" for="confirm_password">Confirm New Password</label>
                    <input id="confirm_password" type="password" name="confirm_password" class="form-control" minlength="6" required>
                </div>

                <button name="change" class="btn btn-warning w-100 py-2">Save New Password</button>
            </form>
        </section>
    </main>
</div>

<script src="bootsrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>
