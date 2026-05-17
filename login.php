<?php
include 'db.php';

if (!empty($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit();
}

$error = '';

if (isset($_POST['login'])) {
    verify_csrf();

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
        $error = 'Invalid email or password.';
    } else {
        $stmt = $conn->prepare('SELECT id, name, role, password FROM users WHERE email = ? LIMIT 1');

        if ($stmt) {
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();

            if ($user && password_verify($password, $user['password'])) {
                session_regenerate_id(true);
                $_SESSION['user_id'] = (int)$user['id'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['name'] = $user['name'];
                unset($_SESSION['login_attempts']);

                header('Location: dashboard.php');
                exit();
            }
        }

        $_SESSION['login_attempts'] = ($_SESSION['login_attempts'] ?? 0) + 1;
        if ($_SESSION['login_attempts'] > 5) {
            sleep(1);
        }

        $error = 'Invalid email or password.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | UniSphere</title>
    <link href="bootsrap/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            min-height: 100vh;
            margin: 0;
            display: grid;
            place-items: center;
            background: radial-gradient(circle at top left, rgba(255, 210, 160, 0.20), transparent 28%),
                        radial-gradient(circle at bottom right, rgba(94, 117, 255, 0.22), transparent 25%),
                        linear-gradient(135deg, #080c1e 0%, #111827 55%, #0f172a 100%);
            color: #f8fafc;
            font-family: Inter, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }

        .glass-card {
            width: min(440px, calc(100vw - 32px));
            backdrop-filter: blur(18px);
            -webkit-backdrop-filter: blur(18px);
            border: 1px solid rgba(255, 255, 255, 0.12);
            background: rgba(15, 23, 42, 0.74);
            box-shadow: 0 24px 80px rgba(10, 24, 60, 0.35);
            border-radius: 28px;
            padding: 2.5rem 2rem;
            transition: transform 0.35s ease, box-shadow 0.35s ease;
        }

        .glass-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 36px 110px rgba(10, 24, 60, 0.42);
        }

        .brand-chip {
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
            color: #fff;
            margin-bottom: 1rem;
        }

        .brand-mark {
            width: 44px;
            height: 44px;
            display: inline-grid;
            place-items: center;
            border-radius: 16px;
            background: linear-gradient(135deg, #f59e0b, #fb923c);
            font-size: 1.25rem;
            font-weight: 700;
            color: #111827;
            box-shadow: 0 12px 30px rgba(245, 158, 11, 0.25);
        }

        .glass-title {
            margin-bottom: 1rem;
            font-size: 1.8rem;
            font-weight: 700;
            letter-spacing: -0.03em;
        }

        .glass-subtitle {
            opacity: 0.76;
            margin-bottom: 1.75rem;
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
            border-color: rgba(96, 165, 250, 0.9);
            box-shadow: 0 0 0 0.2rem rgba(96, 165, 250, 0.18);
            background: rgba(255, 255, 255, 0.12);
            color: #fff;
        }

        .input-group-text {
            background: rgba(255, 255, 255, 0.06);
            border: 1px solid rgba(255, 255, 255, 0.12);
            color: #cbd5e1;
        }

        .form-label {
            color: #e2e8f0;
            font-weight: 600;
        }

        .btn-glow {
            background: linear-gradient(135deg, #f59e0b, #fb923c);
            border: none;
            color: #111827;
            box-shadow: 0 14px 26px rgba(245, 158, 11, 0.24);
            transition: transform 0.25s ease, box-shadow 0.25s ease;
        }

        .btn-glow:hover {
            transform: translateY(-1px);
            box-shadow: 0 18px 32px rgba(245, 158, 11, 0.32);
        }

        .help-text {
            color: #cbd5e1;
            font-size: 0.94rem;
            margin-top: 1rem;
        }

        .link-secondary {
            color: #f59e0b;
        }

        .link-secondary:hover {
            color: #ffd966;
        }

        .alert {
            border-radius: 18px;
            background: rgba(248, 113, 113, 0.14);
            border: 1px solid rgba(248, 113, 113, 0.18);
            color: #fee2e2;
        }

        .icon-svg {
            width: 1.25rem;
            height: 1.25rem;
            stroke-width: 1.5;
        }
    </style>
</head>
<body>
    <main class="glass-card">
        <div class="brand-chip">
            <span class="brand-mark">U</span>
            <div>
                <div class="glass-title">UniSphere Login</div>
                <div class="glass-subtitle">Secure access to university profiles and project dashboards.</div>
            </div>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger mb-4"><?php echo e($error); ?></div>
        <?php endif; ?>

        <form method="POST" autocomplete="off">
            <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">

            <div class="mb-3">
                <label class="form-label" for="email">Email Address</label>
                <div class="input-group has-validation">
                    <span class="input-group-text">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" class="icon-svg" aria-hidden="true">
                            <path d="M3 8.5v7.75c0 .966.784 1.75 1.75 1.75h14.5c.966 0 1.75-.784 1.75-1.75V8.5"/>
                            <path d="M3 8.5L12 14l9-5.5"/>
                            <path d="M12 14V4.25"/>
                        </svg>
                    </span>
                    <input id="email" type="email" name="email" class="form-control" placeholder="name@example.com" required>
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label" for="password">Password</label>
                <div class="input-group">
                    <span class="input-group-text">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" class="icon-svg" aria-hidden="true">
                            <rect x="5" y="11" width="14" height="9" rx="2"/>
                            <path d="M8 11V8a4 4 0 0 1 8 0v3"/>
                        </svg>
                    </span>
                    <input id="password" type="password" name="password" class="form-control" placeholder="Enter password" required>
                </div>
            </div>

            <button name="login" class="btn btn-glow w-100 py-2">Login</button>
        </form>

        <p class="help-text text-center mt-4 mb-0">
            New user? <a class="link-secondary text-decoration-none fw-semibold" href="index.php">Create an account</a>
        </p>
    </main>
</body>
</html>
