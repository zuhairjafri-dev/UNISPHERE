<?php
include 'db.php';
protect_page();

$user = get_user_by_id($conn, current_user_id());

if (!$user) {
    session_destroy();
    header('Location: login.php');
    exit();
}

$role = strtolower($user['role']);
$total_users = count_users($conn);
$total_students = count_users($conn, 'Student');
$total_teachers = count_users($conn, 'Teacher');
$total_projects = count_projects($conn);
$my_projects = count_projects($conn, current_user_id());
$completed = count_projects($conn, current_user_id(), 'Completed');
$pending = count_projects($conn, current_user_id(), 'Pending');
$in_progress = count_projects($conn, current_user_id(), 'In-Progress');
$department = user_field($user, 'department', 'N/A');
$designation = user_field($user, 'designation', 'N/A');
$main_subject = user_field($user, 'main_subject', 'N/A');
$recent_projects = get_recent_projects($conn, 5);

$projectStatuses = [];
$techStacks = [];

if ($role === 'admin') {
    try {
        $pdo = new PDO('mysql:host=localhost;dbname=formdb;charset=utf8mb4', 'root', '', [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

        $statusStmt = $pdo->query("SELECT status, COUNT(*) as count FROM user_projects WHERE is_deleted = 0 GROUP BY status");
        $projectStatuses = $statusStmt->fetchAll();

        $techStmt = $pdo->query("SELECT tech_stack, COUNT(*) as count FROM user_projects WHERE is_deleted = 0 AND tech_stack IS NOT NULL AND tech_stack != '' GROUP BY tech_stack ORDER BY count DESC LIMIT 10");
        $techStacks = $techStmt->fetchAll();

    } catch (PDOException $e) {
        error_log('Chart data fetch failed: ' . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | UniSphere</title>
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

        .welcome-card {
            padding: 2rem;
            border-radius: 28px;
            background: rgba(15, 23, 42, 0.84);
            border: 1px solid rgba(255, 255, 255, 0.08);
            box-shadow: 0 30px 80px rgba(10, 24, 60, 0.2);
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

        .welcome-card p {
            color: #94a3b8;
            max-width: 700px;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 1.25rem;
        }

        .stat-card {
            padding: 1.85rem;
            border-radius: 24px;
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(255, 255, 255, 0.08);
            box-shadow: 0 20px 50px rgba(10, 24, 60, 0.14);
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(251, 146, 60, 0.08), transparent 50%);
            pointer-events: none;
        }

        .stat-card h5 {
            margin-bottom: 0.75rem;
            color: #e2e8f0;
            font-weight: 600;
        }

        .stat-card h2,
        .stat-card .stat-text {
            margin: 0;
            color: #fff;
        }

        .stat-card .stat-text {
            line-height: 1.75;
            color: #d1d5db;
        }

        .stat-card:nth-child(odd) {
            border-left: 4px solid rgba(251, 146, 60, 0.9);
        }

        .table-container {
            padding: 1.75rem;
            border-radius: 28px;
            background: rgba(15, 23, 42, 0.86);
            border: 1px solid rgba(255, 255, 255, 0.08);
            box-shadow: 0 30px 90px rgba(10, 24, 60, 0.18);
        }

        .table-container .page-header {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
        }

        .table-container .page-kicker {
            margin-bottom: 0;
        }

        .table-container .page-title {
            font-size: 1.15rem;
            margin-bottom: 0;
        }

        .table-dark {
            background: transparent;
        }

        .table-dark th,
        .table-dark td {
            border-color: rgba(255, 255, 255, 0.08);
            color: #cbd5e1;
        }

        .table-dark tbody tr:hover {
            background: rgba(251, 146, 60, 0.09);
        }

        .badge-soft {
            display: inline-flex;
            align-items: center;
            padding: 0.45rem 0.75rem;
            border-radius: 999px;
            background: rgba(251, 146, 60, 0.18);
            color: #fbbf24;
            font-weight: 600;
            letter-spacing: 0.01em;
        }

        .charts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
        }

        .chart-card {
            padding: 2rem;
            border-radius: 28px;
            background: rgba(15, 23, 42, 0.84);
            border: 1px solid rgba(255, 255, 255, 0.08);
            box-shadow: 0 30px 80px rgba(10, 24, 60, 0.2);
        }

        .chart-card h5 {
            color: #e2e8f0;
            margin-bottom: 1.5rem;
            font-weight: 600;
        }

        .chart-card canvas {
            max-height: 300px;
        }
    </style>
</head>
<body>
<div class="app-shell">
    <?php include 'sidebar.php'; ?>

    <main class="main-content">
        <section class="welcome-card">
            <div class="page-kicker"><?php echo e($user['role']); ?> Dashboard</div>
            <h1 class="page-title">Welcome, <?php echo e($user['name']); ?></h1>
            <p>Manage university profile details, academic projects, and administrative records from one place.</p>
        </section>

        <section class="grid">
        <?php if ($role === 'admin'): ?>
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
        <?php elseif ($role === 'student'): ?>
            <article class="stat-card">
                <h5>Your Projects</h5>
                <h2><?php echo $my_projects; ?></h2>
            </article>
            <article class="stat-card">
                <h5>Completed</h5>
                <h2><?php echo $completed; ?></h2>
            </article>
            <article class="stat-card">
                <h5>In Progress</h5>
                <h2><?php echo $in_progress; ?></h2>
            </article>
            <article class="stat-card">
                <h5>Semester</h5>
                <h2><?php echo e($user['semester'] ?: 'N/A'); ?></h2>
            </article>
            <article class="stat-card">
                <h5>Specialization</h5>
                <h2 class="h4"><?php echo e($user['specialization'] ?: 'N/A'); ?></h2>
            </article>
        <?php else: ?>
            <article class="stat-card">
                <h5>Department</h5>
                <div class="stat-text"><?php echo e($department); ?></div>
            </article>
            <article class="stat-card">
                <h5>Designation</h5>
                <div class="stat-text"><?php echo e($designation); ?></div>
            </article>
            <article class="stat-card">
                <h5>Main Subject</h5>
                <div class="stat-text"><?php echo e($main_subject); ?></div>
            </article>
            <article class="stat-card">
                <h5>Campus Users</h5>
                <h2><?php echo $total_users; ?></h2>
            </article>
        <?php endif; ?>
    </section>

    <?php if ($role === 'admin'): ?>
        <section class="charts-grid">
            <div class="chart-card">
                <h5>Project Status Distribution</h5>
                <canvas id="statusChart"></canvas>
            </div>
            <div class="chart-card">
                <h5>Technology Stack Usage</h5>
                <canvas id="techChart"></canvas>
            </div>
        </section>
    <?php endif; ?>

    <?php if ($role === 'admin' || $role === 'teacher'): ?>
        <section class="table-container mt-4">
            <div class="page-header mb-3">
                <div>
                    <div class="page-kicker">Recent Activity</div>
                    <h2 class="page-title h5">Latest Major Projects</h2>
                </div>
                <a href="projects.php" class="btn btn-sm btn-outline-warning">View All</a>
            </div>

            <div class="table-responsive">
                <table class="table table-dark table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Project</th>
                            <th>Student</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($recent_projects->num_rows > 0): ?>
                            <?php while ($project = $recent_projects->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo e($project['project_title']); ?></td>
                                    <td><?php echo e($project['name']); ?></td>
                                    <td><span class="badge-soft"><?php echo e($project['status']); ?></span></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="3" class="text-center text-secondary">No major projects submitted yet.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    <?php endif; ?>
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
<?php if ($role === 'admin'): ?>
const statusData = <?php echo json_encode($projectStatuses); ?>;
const techData = <?php echo json_encode($techStacks); ?>;

const statusLabels = statusData.map(item => item.status);
const statusCounts = statusData.map(item => parseInt(item.count));

const techLabels = techData.map(item => item.tech_stack);
const techCounts = techData.map(item => parseInt(item.count));

new Chart(document.getElementById('statusChart'), {
    type: 'doughnut',
    data: {
        labels: statusLabels,
        datasets: [{
            data: statusCounts,
            backgroundColor: [
                'rgba(251, 146, 60, 0.8)',
                'rgba(96, 165, 250, 0.8)',
                'rgba(34, 197, 94, 0.8)',
            ],
            borderWidth: 0,
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'bottom',
                labels: {
                    color: '#e2e8f0',
                }
            }
        }
    }
});

new Chart(document.getElementById('techChart'), {
    type: 'bar',
    data: {
        labels: techLabels,
        datasets: [{
            data: techCounts,
            backgroundColor: 'rgba(251, 146, 60, 0.8)',
            borderRadius: 4,
        }]
    },
    options: {
        responsive: true,
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    color: '#e2e8f0',
                },
                grid: {
                    color: 'rgba(255, 255, 255, 0.1)',
                }
            },
            x: {
                ticks: {
                    color: '#e2e8f0',
                },
                grid: {
                    color: 'rgba(255, 255, 255, 0.1)',
                }
            }
        },
        plugins: {
            legend: {
                display: false,
            }
        }
    }
});
<?php endif; ?>
</script>
</body>
</html>
