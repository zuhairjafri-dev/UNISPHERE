<?php
include 'db.php';
require_admin();

$search = trim($_GET['search'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;
$like = '%' . $search . '%';

if ($search !== '') {
    $countStmt = $conn->prepare('SELECT COUNT(*) AS total FROM users WHERE name LIKE ? OR email LIKE ? OR role LIKE ?');
    $countStmt->bind_param('sss', $like, $like, $like);
    $countStmt->execute();
    $totalRows = (int)$countStmt->get_result()->fetch_assoc()['total'];

    $stmt = $conn->prepare('SELECT id, name, email, role, image FROM users WHERE name LIKE ? OR email LIKE ? OR role LIKE ? ORDER BY id DESC LIMIT ? OFFSET ?');
    $stmt->bind_param('sssii', $like, $like, $like, $limit, $offset);
} else {
    $totalRows = count_users($conn);
    $stmt = $conn->prepare('SELECT id, name, email, role, image FROM users ORDER BY id DESC LIMIT ? OFFSET ?');
    $stmt->bind_param('ii', $limit, $offset);
}

$stmt->execute();
$users = $stmt->get_result();
$totalPages = max(1, (int)ceil($totalRows / $limit));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Records | UniSphere</title>
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

        .form-control {
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.12);
            color: #f8fafc;
            transition: border-color 0.25s ease, box-shadow 0.25s ease;
        }

        .form-control:focus {
            border-color: rgba(96, 165, 250, 0.9);
            box-shadow: 0 0 0 0.2rem rgba(96, 165, 250, 0.18);
            background: rgba(255, 255, 255, 0.12);
            color: #fff;
        }

        .form-control::placeholder {
            color: #94a3b8;
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

        .avatar-sm {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid rgba(255, 255, 255, 0.1);
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

        .btn-outline-danger.btn-icon {
            color: #fca5a5;
            border-color: rgba(248, 113, 113, 0.3);
        }

        .btn-outline-danger.btn-icon:hover {
            background: rgba(248, 113, 113, 0.1);
            border-color: rgba(248, 113, 113, 0.5);
            transform: translateY(-1px);
        }

        .pagination {
            justify-content: center;
        }

        .page-link {
            color: #cbd5e1;
            background: rgba(255, 255, 255, 0.05);
            border-color: rgba(255, 255, 255, 0.08);
            transition: background 0.2s ease, color 0.2s ease;
        }

        .page-link:hover {
            color: #fff;
            background: rgba(251, 146, 60, 0.2);
            border-color: rgba(251, 146, 60, 0.3);
        }

        .page-item.active .page-link {
            background: linear-gradient(135deg, #f59e0b, #fb923c);
            border-color: #f59e0b;
            color: #111827;
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
    </style>
</head>
<body>
<div class="app-shell">
    <?php include 'sidebar.php'; ?>

    <main class="main-content">
        <div class="page-header">
            <div>
                <div class="page-kicker">Admin</div>
                <h1 class="page-title">User Management</h1>
            </div>
            <span class="badge-soft"><?php echo $totalRows; ?> records</span>
        </div>

        <?php echo status_message(); ?>

        <form method="GET" class="mb-3">
            <div class="row g-2">
                <div class="col-md-7">
                    <input type="text" name="search" value="<?php echo e($search); ?>" class="form-control" placeholder="Search by name, email, or role">
                </div>
                <div class="col-md-auto">
                    <button class="btn btn-warning" type="submit">Search</button>
                </div>
                <?php if ($search !== ''): ?>
                    <div class="col-md-auto">
                        <a href="table.php" class="btn btn-outline-light">Clear</a>
                    </div>
                <?php endif; ?>
            </div>
        </form>

        <section class="table-container">
            <div class="table-responsive">
                <table class="table table-dark table-hover align-middle">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($users->num_rows > 0): ?>
                            <?php while ($row = $users->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-3">
                                            <img src="<?php echo e($row['image'] ?: 'images/default.jpg'); ?>" class="avatar-sm" alt="User photo">
                                            <span><?php echo e($row['name']); ?></span>
                                        </div>
                                    </td>
                                    <td><?php echo e($row['email']); ?></td>
                                    <td><span class="badge-soft"><?php echo e($row['role']); ?></span></td>
                                    <td class="text-end">
                                        <a href="edit_user.php?id=<?php echo (int)$row['id']; ?>" class="btn-icon btn-outline-light">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                            </svg>
                                            Edit
                                        </a>
                                        <?php if ((int)$row['id'] !== current_user_id()): ?>
                                            <form action="delete.php" method="POST" class="d-inline" onsubmit="return confirm('Delete this user account?')">
                                                <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                                                <input type="hidden" name="id" value="<?php echo (int)$row['id']; ?>">
                                                <button class="btn-icon btn-outline-danger" type="submit">
                                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                                        <path d="M3 6h18"/>
                                                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                                                        <path d="M10 11v6"/>
                                                        <path d="M14 11v6"/>
                                                    </svg>
                                                    Delete
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="4" class="text-center text-secondary">No records found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <?php if ($totalPages > 1): ?>
            <nav class="mt-3">
                <ul class="pagination">
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        <?php endif; ?>
    </main>
</div>

<script src="bootsrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>
