<?php
include 'db.php';
protect_page();

if (!in_array(current_user_role(), ['teacher', 'admin'], true)) {
    header('Location: dashboard.php');
    exit();
}

$project_id = (int)($_GET['id'] ?? 0);
$project = get_project_by_id($conn, $project_id);

if (!$project) {
    header('Location: projects.php');
    exit();
}

$error = '';

if (isset($_POST['save_review'])) {
    verify_csrf();

    $status = $_POST['status'] ?? 'Pending';
    $remarks = trim($_POST['remarks'] ?? '');
    $allowedStatus = ['Pending', 'In-Progress', 'Completed'];

    if (!in_array($status, $allowedStatus, true)) {
        $error = 'Please select a valid status.';
    } else {
        $stmt = $conn->prepare('UPDATE user_projects SET status = ?, remarks = ? WHERE id = ?');
        $stmt->bind_param('ssi', $status, $remarks, $project_id);

        if ($stmt->execute()) {
            redirect_with_status('projects.php', 'review_saved');
        }

        $error = 'Unable to save review.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Review Project | UniSphere</title>
    <link href="bootsrap/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>
<?php include 'sidebar.php'; ?>

<main class="main-content">
    <div class="page-header">
        <div>
            <div class="page-kicker">Project Review</div>
            <h1 class="page-title h3"><?php echo e($project['project_title']); ?></h1>
        </div>
        <a href="projects.php" class="btn btn-outline-light">Back to Projects</a>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo e($error); ?></div>
    <?php endif; ?>

    <section class="grid mb-4">
        <article class="detail-item"><span>Student</span><?php echo e($project['name']); ?></article>
        <article class="detail-item"><span>Enrollment ID</span><?php echo e($project['enrollment_id'] ?: 'N/A'); ?></article>
        <article class="detail-item"><span>Tech Stack</span><?php echo e($project['tech_stack']); ?></article>
        <article class="detail-item"><span>Mentor</span><?php echo e($project['mentor_name'] ?: 'N/A'); ?></article>
        <article class="detail-item"><span>Start Date</span><?php echo e($project['start_date'] ?: 'N/A'); ?></article>
        <article class="detail-item"><span>Submission Date</span><?php echo e($project['submission_date'] ?: 'N/A'); ?></article>
    </section>

    <section class="custom-card mb-4">
        <h2 class="h5 mb-3">Project Description</h2>
        <p class="text-secondary mb-0"><?php echo nl2br(e($project['project_description'] ?: 'No description added.')); ?></p>

        <?php if (!empty($project['project_file'])): ?>
            <a class="btn btn-sm btn-outline-light mt-3" href="<?php echo e($project['project_file']); ?>" target="_blank">Open Report</a>
        <?php endif; ?>
    </section>

    <section class="custom-card">
        <form method="POST" class="row g-3">
            <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">

            <div class="col-md-4">
                <label class="form-label">Project Status</label>
                <select name="status" class="form-select">
                    <?php foreach (['Pending', 'In-Progress', 'Completed'] as $status): ?>
                        <option value="<?php echo $status; ?>" <?php echo $project['status'] === $status ? 'selected' : ''; ?>><?php echo $status; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-12">
                <label class="form-label">Remarks</label>
                <textarea name="remarks" class="form-control" rows="4" placeholder="Add review remarks for student"><?php echo e($project['remarks']); ?></textarea>
            </div>

            <div class="col-12">
                <button type="submit" name="save_review" class="btn btn-warning px-4">Save Review</button>
            </div>
        </form>
    </section>
</main>
</body>
</html>
