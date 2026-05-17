<?php
include 'db.php';
protect_page();

$user = get_user_by_id($conn, current_user_id());
$projects = get_user_projects($conn, current_user_id());
$role = strtolower($user['role'] ?? '');
$city = user_field($user, 'city', 'N/A');
$state = user_field($user, 'state', 'N/A');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile | UniSphere</title>
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

        .profile-card {
            padding: 2.5rem;
            border-radius: 28px;
            background: rgba(15, 23, 42, 0.84);
            border: 1px solid rgba(255, 255, 255, 0.08);
            box-shadow: 0 30px 80px rgba(10, 24, 60, 0.2);
        }

        .profile-header {
            display: grid;
            grid-template-columns: auto 1fr;
            gap: 2rem;
            align-items: center;
            margin-bottom: 2.5rem;
        }

        .avatar-container {
            position: relative;
        }

        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid rgba(251, 146, 60, 0.3);
            box-shadow: 0 12px 30px rgba(10, 24, 60, 0.3);
        }

        .profile-info h2 {
            font-size: 2.25rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            color: #f8fafc;
        }

        .profile-email {
            font-size: 1.1rem;
            color: #94a3b8;
            margin-bottom: 1rem;
        }

        .badge-soft {
            display: inline-flex;
            align-items: center;
            padding: 0.5rem 1rem;
            border-radius: 999px;
            background: rgba(251, 146, 60, 0.18);
            color: #fbbf24;
            font-weight: 600;
            letter-spacing: 0.01em;
            font-size: 0.9rem;
        }

        .detail-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
        }

        .detail-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 1.5rem;
            border-radius: 16px;
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(255, 255, 255, 0.06);
        }

        .detail-item span:first-child {
            font-weight: 600;
            color: #cbd5e1;
        }

        .detail-item span:last-child {
            color: #f8fafc;
            font-weight: 500;
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

        .btn-outline-danger.btn-icon {
            color: #fca5a5;
            border-color: rgba(248, 113, 113, 0.3);
        }

        .btn-outline-danger.btn-icon:hover {
            background: rgba(248, 113, 113, 0.1);
            border-color: rgba(248, 113, 113, 0.5);
            transform: translateY(-1px);
        }

        .projects-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 1.5rem;
        }

        .project-card {
            padding: 1.75rem;
            border-radius: 24px;
            background: rgba(15, 23, 42, 0.86);
            border: 1px solid rgba(255, 255, 255, 0.08);
            box-shadow: 0 20px 60px rgba(10, 24, 60, 0.18);
            transition: transform 0.25s ease, box-shadow 0.25s ease;
        }

        .project-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 25px 70px rgba(10, 24, 60, 0.25);
        }

        .project-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }

        .project-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: #f8fafc;
            margin: 0;
        }

        .project-details p {
            margin-bottom: 0.5rem;
            color: #cbd5e1;
            font-size: 0.95rem;
        }

        .project-details strong {
            color: #e2e8f0;
        }

        .project-actions {
            display: flex;
            gap: 0.75rem;
            margin-top: 1.25rem;
            flex-wrap: wrap;
        }

        .no-projects {
            grid-column: 1 / -1;
            text-align: center;
            padding: 3rem;
            border-radius: 24px;
            background: rgba(15, 23, 42, 0.86);
            border: 1px solid rgba(255, 255, 255, 0.08);
        }

        @media (max-width: 768px) {
            .app-shell {
                grid-template-columns: 1fr;
            }

            .sidebar-panel {
                position: static;
                top: auto;
            }

            .profile-header {
                grid-template-columns: 1fr;
                text-align: center;
                gap: 1.5rem;
            }

            .detail-grid {
                grid-template-columns: 1fr;
            }

            .projects-grid {
                grid-template-columns: 1fr;
            }

            .project-actions {
                justify-content: center;
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
                <div class="page-kicker">Profile</div>
                <h1 class="page-title">My Information</h1>
            </div>
            <a href="edit_user.php?id=<?php echo (int)$user['id']; ?>" class="btn btn-warning">Edit Profile</a>
        </div>

        <?php echo status_message(); ?>

        <section class="profile-card">
            <div class="profile-header">
                <div class="avatar-container">
                    <img src="<?php echo e($user['image'] ?: 'images/default.jpg'); ?>" alt="Profile photo" class="profile-avatar">
                </div>
                <div class="profile-info">
                    <h2><?php echo e($user['name']); ?></h2>
                    <p class="profile-email"><?php echo e($user['email']); ?></p>
                    <span class="badge-soft"><?php echo e($user['role']); ?></span>
                </div>
            </div>

            <div class="detail-grid">
                <div class="detail-item">
                    <span>Age</span>
                    <span><?php echo e($user['age']); ?></span>
                </div>
                <div class="detail-item">
                    <span>Gender</span>
                    <span><?php echo e($user['gender']); ?></span>
                </div>
                <div class="detail-item">
                    <span>City</span>
                    <span><?php echo e($city); ?></span>
                </div>
                <div class="detail-item">
                    <span>State</span>
                    <span><?php echo e($state); ?></span>
                </div>

                <?php if ($role === 'student'): ?>
                    <div class="detail-item">
                        <span>Enrollment ID</span>
                        <span><?php echo e($user['enrollment_id'] ?: 'N/A'); ?></span>
                    </div>
                    <div class="detail-item">
                        <span>Specialization</span>
                        <span><?php echo e($user['specialization'] ?: 'N/A'); ?></span>
                    </div>
                    <div class="detail-item">
                        <span>Semester</span>
                        <span><?php echo e($user['semester'] ?: 'N/A'); ?></span>
                    </div>
                    <div class="detail-item">
                        <span>Session Batch</span>
                        <span><?php echo e($user['session_batch'] ?: 'N/A'); ?></span>
                    </div>
                <?php else: ?>
                    <div class="detail-item">
                        <span>Designation</span>
                        <span><?php echo e($user['designation'] ?: 'N/A'); ?></span>
                    </div>
                    <div class="detail-item">
                        <span>Department</span>
                        <span><?php echo e($user['department'] ?: 'N/A'); ?></span>
                    </div>
                    <div class="detail-item">
                        <span>Main Subject</span>
                        <span><?php echo e($user['main_subject'] ?: 'N/A'); ?></span>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <?php if ($role === 'student'): ?>
            <div class="page-header">
                <div>
                    <div class="page-kicker">Projects</div>
                    <h2 class="page-title h4">Academic Project Work</h2>
                </div>
                <a href="add_project.php" class="btn btn-outline-light">Add Project</a>
            </div>

            <section class="projects-grid">
                <?php if ($projects->num_rows > 0): ?>
                    <?php while ($project = $projects->fetch_assoc()): ?>
                        <div class="project-card">
                            <div class="project-header">
                                <h5 class="project-title"><?php echo e($project['project_title']); ?></h5>
                                <span class="badge-soft"><?php echo e($project['status']); ?></span>
                            </div>
                            <div class="project-details">
                                <p><strong>Tech Stack:</strong> <?php echo e($project['tech_stack']); ?></p>
                                <p><strong>Mentor:</strong> <?php echo e($project['mentor_name'] ?: 'N/A'); ?></p>
                                <?php if (!empty($project['remarks'])): ?>
                                    <p><strong>Remarks:</strong> <?php echo e($project['remarks']); ?></p>
                                <?php endif; ?>
                            </div>
                            <div class="project-actions">
                                <?php if (!empty($project['project_file'])): ?>
                                    <a class="btn-icon btn-outline-light" href="<?php echo e($project['project_file']); ?>" target="_blank">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                            <polyline points="14,2 14,8 20,8"/>
                                            <line x1="16" y1="13" x2="8" y2="13"/>
                                            <line x1="16" y1="17" x2="8" y2="17"/>
                                            <polyline points="10,9 9,9 8,9"/>
                                        </svg>
                                        Report
                                    </a>
                                <?php endif; ?>
                                <a class="btn-icon btn-outline-light" href="edit_project.php?id=<?php echo (int)$project['id']; ?>">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                    </svg>
                                    Edit
                                </a>
                                <form action="delete.php" method="POST" class="d-inline" onsubmit="return confirm('Delete this project?')">
                                    <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                                    <input type="hidden" name="project_id" value="<?php echo (int)$project['id']; ?>">
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
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="no-projects">
                        <p class="text-secondary">No projects added yet.</p>
                    </div>
                <?php endif; ?>
            </section>
        <?php endif; ?>
    </main>
</div>

<script src="bootsrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>
