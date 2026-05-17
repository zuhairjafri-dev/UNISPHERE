<?php
include 'db.php';
protect_page();

if (!in_array(current_user_role(), ['teacher', 'admin'], true)) {
    header('Location: dashboard.php');
    exit();
}

$perPage = 10;
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $perPage;

$status_filter = $_GET['status'] ?? '';
$tech_filter = $_GET['tech'] ?? '';
$search = trim($_GET['search'] ?? '');

$allowedStatus = ['Pending', 'In-Progress', 'Completed'];
$has_status = in_array($status_filter, $allowedStatus, true);
$has_tech = $tech_filter !== '';
$has_search = $search !== '';
$like = '%' . $search . '%';

try {
    $pdo = new PDO('mysql:host=localhost;dbname=formdb;charset=utf8mb4', 'root', '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    // Fetch distinct tech stacks for filter
    $techStmt = $pdo->query('SELECT DISTINCT tech_stack FROM user_projects WHERE tech_stack IS NOT NULL AND tech_stack != "" ORDER BY tech_stack');
    $techStacks = $techStmt->fetchAll(PDO::FETCH_COLUMN);

    // Build WHERE clause
    $where = ['p.is_deleted = 0'];
    $params = [];

    if ($has_status) {
        $where[] = 'p.status = :status';
        $params[':status'] = $status_filter;
    }

    if ($has_tech) {
        $where[] = 'p.tech_stack = :tech';
        $params[':tech'] = $tech_filter;
    }

    if ($has_search) {
        $where[] = '(p.project_title LIKE :search OR u.name LIKE :search OR p.tech_stack LIKE :search)';
        $params[':search'] = $like;
    }

    $whereClause = 'WHERE ' . implode(' AND ', $where);

    // Count total records
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM user_projects p JOIN users u ON u.id = p.user_id $whereClause");
    $countStmt->execute($params);
    $totalRecords = $countStmt->fetchColumn();
    $totalPages = ceil($totalRecords / $perPage);

    // Fetch records with pagination
    $sql = "SELECT p.*, u.name, u.email, u.enrollment_id
            FROM user_projects p
            JOIN users u ON u.id = p.user_id
            $whereClause
            ORDER BY p.id DESC
            LIMIT :limit OFFSET :offset";

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $projects = $stmt->fetchAll();

} catch (PDOException $e) {
    error_log('Database error: ' . $e->getMessage());
    $projects = [];
    $totalPages = 0;
    $techStacks = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Major Projects | UniSphere</title>
    <link href="bootsrap/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.25/jspdf.plugin.autotable.min.js"></script>
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

        .custom-card {
            padding: 1.75rem;
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
            box-shadow: 0 8px 20px rgba(245, 158, 11, 0.3);
        }

        .table-container {
            padding: 1.75rem;
            border-radius: 28px;
            background: rgba(15, 23, 42, 0.86);
            border: 1px solid rgba(255, 255, 255, 0.08);
            box-shadow: 0 30px 90px rgba(10, 24, 60, 0.18);
        }

        .table-dark {
            background: transparent;
            border-collapse: separate;
            border-spacing: 0;
        }

        .table-dark th,
        .table-dark td {
            border-color: rgba(255, 255, 255, 0.08);
            color: #cbd5e1;
            padding: 1rem 1.25rem;
            vertical-align: middle;
        }

        .table-dark thead th {
            background: rgba(255, 255, 255, 0.05);
            border-bottom: 2px solid rgba(255, 255, 255, 0.12);
            font-weight: 600;
            color: #f8fafc;
        }

        .table-dark tbody tr {
            transition: background 0.2s ease;
        }

        .table-dark tbody tr:hover {
            background: rgba(251, 146, 60, 0.09);
        }

        .table-dark tbody tr:nth-child(even) {
            background: rgba(255, 255, 255, 0.02);
        }

        .btn-icon {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border-radius: 12px;
            font-size: 0.875rem;
            font-weight: 500;
            text-decoration: none;
            transition: transform 0.2s ease, background 0.2s ease;
        }

        .btn-icon svg {
            width: 1rem;
            height: 1rem;
        }

        .btn-outline-light.btn-icon {
            color: #e2e8f0;
            border-color: rgba(255, 255, 255, 0.2);
        }

        .btn-outline-light.btn-icon:hover {
            background: rgba(255, 255, 255, 0.1);
            border-color: rgba(255, 255, 255, 0.4);
            transform: translateY(-1px);
        }

        @media (max-width: 992px) {
            .app-shell {
                grid-template-columns: 1fr;
            }

            .sidebar-panel {
                position: static;
                top: auto;
            }

            .table-responsive {
                overflow-x: auto;
            }
        }

        .pagination {
            margin: 0;
        }

        .page-link {
            color: #e2e8f0;
            background-color: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            margin: 0 2px;
            transition: background-color 0.25s ease, border-color 0.25s ease;
        }

        .page-link:hover {
            color: #fff;
            background-color: rgba(251, 146, 60, 0.2);
            border-color: rgba(251, 146, 60, 0.3);
        }

        .page-item.active .page-link {
            background-color: rgba(251, 146, 60, 0.3);
            border-color: rgba(251, 146, 60, 0.5);
            color: #fff;
        }

        .page-item.disabled .page-link {
            color: #64748b;
            background-color: rgba(255, 255, 255, 0.02);
            border-color: rgba(255, 255, 255, 0.05);
        }
    </style>
</head>
<body>
<div class="app-shell">
    <?php include 'sidebar.php'; ?>

    <main class="main-content">
        <div class="page-header">
            <div>
                <div class="page-kicker">Review</div>
                <h1 class="page-title">Student Major Projects</h1>
            </div>
            <span class="badge-soft"><?php echo $totalRecords; ?> records</span>
        </div>

        <?php echo status_message(); ?>

        <form method="GET" class="custom-card mb-3">
            <div class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Search</label>
                    <input type="text" name="search" class="form-control" value="<?php echo e($search); ?>" placeholder="Project, student, or tech">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All Status</option>
                        <?php foreach ($allowedStatus as $status): ?>
                            <option value="<?php echo $status; ?>" <?php echo $status_filter === $status ? 'selected' : ''; ?>><?php echo $status; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Tech Stack</label>
                    <select name="tech" class="form-select">
                        <option value="">All Tech Stacks</option>
                        <?php foreach ($techStacks as $tech): ?>
                            <option value="<?php echo e($tech); ?>" <?php echo $tech_filter === $tech ? 'selected' : ''; ?>><?php echo e($tech); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <button class="btn btn-warning w-100" type="submit">Apply Filter</button>
                </div>
            </div>
        </form>

        <section class="table-container">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <div class="page-kicker">Project Records</div>
                    <h2 class="page-title h4">Filtered Results</h2>
                </div>
                <div class="d-flex gap-2">
                    <button id="exportExcel" class="btn btn-outline-light">Export to Excel</button>
                    <button id="exportPDF" class="btn btn-outline-light">Export to PDF</button>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-dark table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Project</th>
                            <th>Student</th>
                            <th>Tech Stack</th>
                            <th>Status</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($projects) > 0): ?>
                            <?php foreach ($projects as $project): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo e($project['project_title']); ?></strong>
                                        <div class="text-secondary small"><?php echo e($project['mentor_name'] ?: 'No mentor assigned'); ?></div>
                                    </td>
                                    <td>
                                        <?php echo e($project['name']); ?>
                                        <div class="text-secondary small"><?php echo e($project['enrollment_id'] ?: $project['email']); ?></div>
                                    </td>
                                    <td><?php echo e($project['tech_stack']); ?></td>
                                    <td><span class="badge-soft"><?php echo e($project['status']); ?></span></td>
                                    <td class="text-end">
                                        <a class="btn-icon btn-outline-light" href="review_project.php?id=<?php echo (int)$project['id']; ?>">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                                <path d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0z"/>
                                                <path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                            </svg>
                                            Review
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center text-secondary">No project records found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <?php if ($totalPages > 1): ?>
            <nav aria-label="Pagination" class="mt-4">
                <ul class="pagination justify-content-center">
                    <?php
                    $queryParams = $_GET;
                    unset($queryParams['page']); // Remove page from params to rebuild

                    $baseUrl = '?' . http_build_query(array_merge($queryParams, ['page' => '']));

                    if ($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="<?php echo $baseUrl . ($page - 1); ?>" aria-label="Previous">
                                <span aria-hidden="true">&laquo;</span>
                            </a>
                        </li>
                    <?php endif;

                    for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                        <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                            <a class="page-link" href="<?php echo $baseUrl . $i; ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor;

                    if ($page < $totalPages): ?>
                        <li class="page-item">
                            <a class="page-link" href="<?php echo $baseUrl . ($page + 1); ?>" aria-label="Next">
                                <span aria-hidden="true">&raquo;</span>
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        <?php endif; ?>
    </main>
</div>

<script src="bootsrap/js/bootstrap.bundle.min.js"></script>
<script>
document.getElementById('exportExcel').addEventListener('click', function() {
    const table = document.querySelector('.table');
    const wb = XLSX.utils.table_to_book(table, {sheet: "Projects"});
    XLSX.writeFile(wb, 'projects.xlsx');
});

document.getElementById('exportPDF').addEventListener('click', function() {
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF();

    doc.autoTable({
        html: '.table',
        theme: 'grid',
        styles: {
            fontSize: 8,
            cellPadding: 2,
        },
        headStyles: {
            fillColor: [251, 146, 60],
            textColor: 255,
        },
        alternateRowStyles: {
            fillColor: [245, 245, 245],
        },
    });

    doc.save('projects.pdf');
});
</script>
</body>
</html>
