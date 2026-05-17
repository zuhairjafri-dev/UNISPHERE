<?php
include '../db.php';
require_admin();

$total_users = count_users($conn);
$total_students = count_users($conn, 'Student');
$total_teachers = count_users($conn, 'Teacher');
$total_projects = count_projects($conn);
$completed_projects = count_projects($conn, null, 'Completed');
$pending_projects = count_projects($conn, null, 'Pending');
$in_progress_projects = count_projects($conn, null, 'In-Progress');

$project_denominator = max(1, $total_projects);
$completed_percent = round(($completed_projects / $project_denominator) * 100);
$pending_percent = round(($pending_projects / $project_denominator) * 100);
$progress_percent = round(($in_progress_projects / $project_denominator) * 100);

$student_percent = round(($total_students / max(1, $total_users)) * 100);
$teacher_percent = round(($total_teachers / max(1, $total_users)) * 100);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Analytics | UniSphere</title>
    <link href="../bootsrap/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../style.css">
</head>
<body>
<?php include '../sidebar.php'; ?>

<main class="main-content">
    <div class="page-header">
        <div>
            <div class="page-kicker">Admin</div>
            <h1 class="page-title h3">Analytics Overview</h1>
        </div>
    </div>

    <section class="grid mb-4">
        <article class="stat-card">
            <h5>Total Users</h5>
            <h2><?php echo $total_users; ?></h2>
        </article>
        <article class="stat-card">
            <h5>Students</h5>
            <h2><?php echo $total_students; ?></h2>
        </article>
        <article class="stat-card">
            <h5>Teachers</h5>
            <h2><?php echo $total_teachers; ?></h2>
        </article>
        <article class="stat-card">
            <h5>Total Projects</h5>
            <h2><?php echo $total_projects; ?></h2>
        </article>
    </section>

    <section class="grid">
        <article class="panel">
            <h2 class="h5 mb-3">Project Status</h2>

            <div class="metric-row">
                <div class="d-flex justify-content-between mb-1">
                    <span>Completed</span>
                    <strong><?php echo $completed_projects; ?></strong>
                </div>
                <div class="progress"><div class="progress-bar bg-success" style="width: <?php echo $completed_percent; ?>%"></div></div>
            </div>

            <div class="metric-row">
                <div class="d-flex justify-content-between mb-1">
                    <span>In Progress</span>
                    <strong><?php echo $in_progress_projects; ?></strong>
                </div>
                <div class="progress"><div class="progress-bar bg-primary" style="width: <?php echo $progress_percent; ?>%"></div></div>
            </div>

            <div class="metric-row">
                <div class="d-flex justify-content-between mb-1">
                    <span>Pending</span>
                    <strong><?php echo $pending_projects; ?></strong>
                </div>
                <div class="progress"><div class="progress-bar bg-warning" style="width: <?php echo $pending_percent; ?>%"></div></div>
            </div>
        </article>

        <article class="panel">
            <h2 class="h5 mb-3">Role Distribution</h2>

            <div class="metric-row">
                <div class="d-flex justify-content-between mb-1">
                    <span>Students</span>
                    <strong><?php echo $student_percent; ?>%</strong>
                </div>
                <div class="progress"><div class="progress-bar" style="width: <?php echo $student_percent; ?>%"></div></div>
            </div>

            <div class="metric-row">
                <div class="d-flex justify-content-between mb-1">
                    <span>Teachers</span>
                    <strong><?php echo $teacher_percent; ?>%</strong>
                </div>
                <div class="progress"><div class="progress-bar bg-success" style="width: <?php echo $teacher_percent; ?>%"></div></div>
            </div>
        </article>
    </section>
</main>
</body>
</html>
