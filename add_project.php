<?php
include 'db.php';
protect_page();

if (current_user_role() !== 'student') {
    header('Location: dashboard.php');
    exit();
}

$feedback = '';

function sanitize_file_extension($extension) {
    return strtolower(preg_replace('/[^a-z0-9]+/', '', $extension));
}

function generate_secure_filename($extension) {
    $extension = sanitize_file_extension($extension);
    return uniqid('proj_', true) . '_' . bin2hex(random_bytes(6)) . '.' . $extension;
}

function render_alert($type, $message) {
    $allowedTypes = ['success', 'danger', 'warning', 'info'];
    $type = in_array($type, $allowedTypes, true) ? $type : 'info';
    return '<div class="alert alert-' . $type . ' alert-dismissible fade show" role="alert">'
        . htmlspecialchars($message, ENT_QUOTES, 'UTF-8')
        . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>'
        . '</div>';
}

function process_project_report_upload($fieldName) {
    if (empty($_FILES[$fieldName]['name']) || !isset($_FILES[$fieldName])) {
        return '';
    }

    if ($_FILES[$fieldName]['error'] !== UPLOAD_ERR_OK) {
        return false;
    }

    if ($_FILES[$fieldName]['size'] > MAX_PROJECT_FILE_SIZE) {
        return false;
    }

    $allowedMimeTypes = [
        'application/pdf' => 'pdf',
        'application/msword' => 'doc',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
    ];

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = $finfo ? finfo_file($finfo, $_FILES[$fieldName]['tmp_name']) : false;
    if ($finfo) {
        finfo_close($finfo);
    }

    if ($mime === false || !isset($allowedMimeTypes[$mime])) {
        return false;
    }

    if (!is_dir('uploads/projects')) {
        mkdir('uploads/projects', 0755, true);
    }

    $extension = $allowedMimeTypes[$mime];
    $fileName = generate_secure_filename($extension);
    $target = 'uploads/projects/' . $fileName;

    if (!move_uploaded_file($_FILES[$fieldName]['tmp_name'], $target)) {
        return false;
    }

    return $target;
}

if (isset($_POST['save_project'])) {
    verify_csrf();

    $user_id = current_user_id();
    $title = strip_tags(trim($_POST['title'] ?? ''));
    $description = strip_tags(trim($_POST['description'] ?? ''));
    $tech = strip_tags(trim($_POST['tech'] ?? ''));
    $mentor = strip_tags(trim($_POST['mentor_name'] ?? ''));
    $start_date = $_POST['start_date'] ? validate_date($_POST['start_date']) : null;
    $submission_date = $_POST['submission_date'] ? validate_date($_POST['submission_date']) : null;
    $status = $_POST['status'] ?? 'Pending';
    $allowedStatus = ['Pending', 'In-Progress', 'Completed'];

    $title = mb_substr($title, 0, 150);
    $description = mb_substr($description, 0, 1000);
    $tech = mb_substr($tech, 0, 100);
    $mentor = mb_substr($mentor, 0, 100);

    $project_file = process_project_report_upload('project_file');

    if ($title === '' || $tech === '' || !in_array($status, $allowedStatus, true)) {
        $feedback = render_alert('danger', 'Please fill all fields correctly.');
    } elseif (($start_date === null && !empty($_POST['start_date'])) || ($submission_date === null && !empty($_POST['submission_date']))) {
        $feedback = render_alert('danger', 'Please provide valid dates for project start and submission.');
    } elseif ($project_file === false) {
        $feedback = render_alert('danger', 'Only PDF, DOC, or DOCX files under 5 MB are allowed for the project report.');
    } else {
        try {
            $pdo = new PDO('mysql:host=localhost;dbname=formdb;charset=utf8mb4', 'root', '', [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);

            $stmt = $pdo->prepare(
                'INSERT INTO user_projects
                 (user_id, project_title, project_description, tech_stack, mentor_name, start_date, submission_date, project_file, status)
                 VALUES (:user_id, :project_title, :project_description, :tech_stack, :mentor_name, :start_date, :submission_date, :project_file, :status)'
            );

            $stmt->execute([
                ':user_id' => $user_id,
                ':project_title' => $title,
                ':project_description' => $description,
                ':tech_stack' => $tech,
                ':mentor_name' => $mentor,
                ':start_date' => $start_date,
                ':submission_date' => $submission_date,
                ':project_file' => $project_file,
                ':status' => $status,
            ]);

            redirect_with_status('profile.php', 'project_added');
        } catch (PDOException $e) {
            error_log('Project insert failed: ' . $e->getMessage());
            $feedback = render_alert('danger', 'Unable to save project. Please try again.');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Project | UniSphere</title>
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
            border: 1px solid rgba(255, 255, 255, 0.08);
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
            border-color: rgba(96, 165, 250, 0.9);
            box-shadow: 0 0 0 0.2rem rgba(96, 165, 250, 0.18);
            background: rgba(255, 255, 255, 0.12);
            color: #fff;
        }

        .form-control::placeholder {
            color: #94a3b8;
        }

        textarea.form-control {
            resize: vertical;
            min-height: 120px;
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

        .alert {
            border-radius: 18px;
            background: rgba(248, 113, 113, 0.14);
            border: 1px solid rgba(248, 113, 113, 0.18);
            color: #fee2e2;
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
                <div class="page-kicker">Project Tracking</div>
                <h1 class="page-title">Add New Project</h1>
            </div>
            <a href="profile.php" class="btn btn-outline-light">Back to Profile</a>
        </div>

        <section class="form-card">
            <?php echo $feedback; ?>

            <form method="POST" enctype="multipart/form-data" class="row g-3">
                <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">

                <div class="col-md-6">
                    <label class="form-label" for="title">Project Title</label>
                    <input id="title" type="text" name="title" class="form-control" placeholder="Student Attendance System" required>
                </div>

                <div class="col-md-6">
                    <label class="form-label" for="tech">Tech Stack</label>
                    <input id="tech" type="text" name="tech" class="form-control" placeholder="PHP, MySQL, Bootstrap" required>
                </div>

                <div class="col-12">
                    <label class="form-label" for="description">Project Description</label>
                    <textarea id="description" name="description" class="form-control" rows="4" placeholder="Write a short summary of the major project"></textarea>
                </div>

                <div class="col-md-4">
                    <label class="form-label" for="mentor_name">Mentor / Guide Name</label>
                    <input id="mentor_name" type="text" name="mentor_name" class="form-control" placeholder="Faculty guide name">
                </div>

                <div class="col-md-4">
                    <label class="form-label" for="start_date">Start Date</label>
                    <input id="start_date" type="date" name="start_date" class="form-control">
                </div>

                <div class="col-md-4">
                    <label class="form-label" for="submission_date">Submission Date</label>
                    <input id="submission_date" type="date" name="submission_date" class="form-control">
                </div>

                <div class="col-md-6">
                    <label class="form-label" for="status">Status</label>
                    <select id="status" name="status" class="form-select">
                        <option value="Pending">Pending</option>
                        <option value="In-Progress">In-Progress</option>
                        <option value="Completed">Completed</option>
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label" for="project_file">Project Report</label>
                    <input id="project_file" type="file" name="project_file" class="form-control" accept=".pdf,.doc,.docx">
                </div>

                <div class="col-12">
                    <button type="submit" name="save_project" class="btn btn-glow px-4">Save Project</button>
                </div>
            </form>
        </section>
    </main>
</div>
</body>
</html>

