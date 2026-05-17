<?php
include 'db.php';
protect_page();

if (current_user_role() !== 'student') {
    header('Location: dashboard.php');
    exit();
}

$project_id = (int)($_GET['id'] ?? 0);
$project = get_project_by_id($conn, $project_id);

if (!$project || (int)$project['user_id'] !== current_user_id()) {
    header('Location: profile.php');
    exit();
}

$error = '';

if (isset($_POST['update_project'])) {
    verify_csrf();

    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $tech = trim($_POST['tech'] ?? '');
    $mentor = trim($_POST['mentor_name'] ?? '');
    $start_date = $_POST['start_date'] ?: null;
    $submission_date = $_POST['submission_date'] ?: null;
    $status = $_POST['status'] ?? 'Pending';
    $allowedStatus = ['Pending', 'In-Progress', 'Completed'];
    $project_file = upload_project_file('project_file', $project['project_file'] ?? '');

    if ($title === '' || $tech === '' || !in_array($status, $allowedStatus, true)) {
        $error = 'Please fill all required fields correctly.';
    } elseif ($project_file === false) {
        $error = 'Only PDF, DOC, and DOCX files are allowed.';
    } else {
        try {
            $pdo = new PDO('mysql:host=localhost;dbname=formdb;charset=utf8mb4', 'root', '', [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);

            $stmt = $pdo->prepare(
                'UPDATE user_projects
                 SET project_title = :title, project_description = :description, tech_stack = :tech, mentor_name = :mentor,
                     start_date = :start_date, submission_date = :submission_date, project_file = :project_file, status = :status
                 WHERE id = :id AND user_id = :user_id'
            );

            $stmt->execute([
                ':title' => $title,
                ':description' => $description,
                ':tech' => $tech,
                ':mentor' => $mentor,
                ':start_date' => $start_date,
                ':submission_date' => $submission_date,
                ':project_file' => $project_file,
                ':status' => $status,
                ':id' => $project_id,
                ':user_id' => $project['user_id'],
            ]);

            redirect_with_status('profile.php', 'project_updated');
        } catch (PDOException $e) {
            $error = 'Unable to update project.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Project | UniSphere</title>
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
            margin-bottom: 0.85rem;
            font-weight: 700;
        }

        .page-title {
            font-size: clamp(2rem, 2.5vw, 2.75rem);
            margin-bottom: 0.85rem;
            color: #f8fafc;
        }

        .btn-warning {
            background: linear-gradient(135deg, #f59e0b, #fb923c);
            border: none;
            color: #111827;
            transition: transform 0.25s ease, box-shadow 0.25s ease;
        }

        .btn-warning:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 20px rgba(245, 158, 11, 0.3);
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
            padding: 2.5rem;
            border-radius: 28px;
            background: rgba(15, 23, 42, 0.84);
            border: 1px solid rgba(255, 255, 255, 0.08);
            box-shadow: 0 30px 80px rgba(10, 24, 60, 0.2);
        }

        .form-label {
            color: #cbd5e1;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .form-control,
        .form-select {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: #e2e8f0;
            border-radius: 12px;
            padding: 0.75rem 1rem;
            transition: border-color 0.25s ease, box-shadow 0.25s ease;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: rgba(251, 146, 60, 0.5);
            box-shadow: 0 0 0 0.2rem rgba(251, 146, 60, 0.1);
            background: rgba(255, 255, 255, 0.08);
        }

        .form-control::placeholder {
            color: #64748b;
        }

        .alert {
            border-radius: 12px;
            border: none;
            padding: 1rem 1.25rem;
        }

        .alert-danger {
            background: rgba(239, 68, 68, 0.1);
            color: #fca5a5;
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
                <div class="page-kicker">Major Project</div>
                <h1 class="page-title h3">Edit Project</h1>
            </div>
            <a href="profile.php" class="btn btn-outline-light">Back to Profile</a>
        </div>

        <?php echo status_message(); ?>

        <section class="form-card">
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo e($error); ?></div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" class="row g-3">
                <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">

                <div class="col-md-6">
                    <label class="form-label">Project Title</label>
                    <input type="text" name="title" class="form-control" value="<?php echo e($project['project_title']); ?>" required>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Tech Stack</label>
                    <input type="text" name="tech" class="form-control" value="<?php echo e($project['tech_stack']); ?>" required>
                </div>

                <div class="col-12">
                    <label class="form-label">Project Description</label>
                    <textarea name="description" class="form-control" rows="4"><?php echo e($project['project_description']); ?></textarea>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Mentor / Guide Name</label>
                    <input type="text" name="mentor_name" class="form-control" value="<?php echo e($project['mentor_name']); ?>">
                </div>

                <div class="col-md-4">
                    <label class="form-label">Start Date</label>
                    <input type="date" name="start_date" class="form-control" value="<?php echo e($project['start_date']); ?>">
                </div>

                <div class="col-md-4">
                    <label class="form-label">Submission Date</label>
                    <input type="date" name="submission_date" class="form-control" value="<?php echo e($project['submission_date']); ?>">
                </div>

                <div class="col-md-6">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <?php foreach (['Pending', 'In-Progress', 'Completed'] as $status): ?>
                            <option value="<?php echo $status; ?>" <?php echo $project['status'] === $status ? 'selected' : ''; ?>><?php echo $status; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Project Report</label>
                    <input type="file" name="project_file" class="form-control" accept=".pdf,.doc,.docx">
                    <?php if (!empty($project['project_file'])): ?>
                        <a class="small text-warning d-inline-block mt-2" href="<?php echo e($project['project_file']); ?>" target="_blank">Current report</a>
                    <?php endif; ?>
                </div>

                <div class="col-12">
                    <button type="submit" name="update_project" class="btn btn-warning px-4">Update Project</button>
                </div>
            </form>
        </section>
    </main>
</div>

<script src="bootsrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>
