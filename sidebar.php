<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$current_page = basename($_SERVER['PHP_SELF']);
$current_path = str_replace('\\', '/', $_SERVER['PHP_SELF']);
$role = strtolower($_SESSION['role'] ?? '');

function nav_active($page) {
    global $current_page, $current_path;

    if ($page === 'admin/analytics.php') {
        return substr($current_path, -strlen('/admin/analytics.php')) === '/admin/analytics.php' ? 'active' : '';
    }

    return $current_page === $page ? 'active' : '';
}

$base = substr($current_path, -strlen('/admin/analytics.php')) === '/admin/analytics.php' ? '../' : '';
?>

<aside class="sidebar-panel">
    <div class="sidebar-brand">
        <a href="<?php echo $base; ?>dashboard.php" class="brand-link">
            <span class="brand-mark">U</span>
            <div>
                <span class="brand-title">UniSphere</span>
                <span class="brand-subtitle">Campus portal</span>
            </div>
        </a>
    </div>

    <nav class="sidebar-menu">
        <a href="<?php echo $base; ?>dashboard.php" class="sidebar-link <?php echo nav_active('dashboard.php'); ?>">
            Dashboard
        </a>

        <a href="<?php echo $base; ?>profile.php" class="sidebar-link <?php echo nav_active('profile.php'); ?>">
            My Profile
        </a>

        <?php if ($role === 'student'): ?>
            <a href="<?php echo $base; ?>add_project.php" class="sidebar-link <?php echo nav_active('add_project.php'); ?>">
                Add Project
            </a>
        <?php endif; ?>

        <?php if ($role === 'teacher' || $role === 'admin'): ?>
            <a href="<?php echo $base; ?>projects.php" class="sidebar-link <?php echo nav_active('projects.php'); ?>">
                Major Projects
            </a>
        <?php endif; ?>

        <?php if ($role === 'admin'): ?>
            <a href="<?php echo $base; ?>table.php" class="sidebar-link <?php echo nav_active('table.php'); ?>">
                User Records
            </a>

            <a href="<?php echo $base; ?>admin/analytics.php" class="sidebar-link <?php echo nav_active('admin/analytics.php'); ?>">
                Analytics
            </a>
        <?php endif; ?>

        <a href="<?php echo $base; ?>change_password.php" class="sidebar-link <?php echo nav_active('change_password.php'); ?>">
            Change Password
        </a>
    </nav>

    <div class="sidebar-footer">
        <a href="<?php echo $base; ?>logout.php" class="logout-link">Logout</a>
    </div>
</aside>
