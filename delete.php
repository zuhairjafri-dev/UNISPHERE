<?php
include 'db.php';
protect_page();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: dashboard.php');
    exit();
}

verify_csrf();

if (isset($_POST['project_id'])) {
    $project_id = (int)$_POST['project_id'];
    $user_id = current_user_id();

    $stmt = $conn->prepare('UPDATE user_projects SET is_deleted = 1 WHERE id = ? AND user_id = ?');
    $stmt->bind_param('ii', $project_id, $user_id);
    $stmt->execute();

    logUserActivity($user_id, 'deleted_project');

    redirect_with_status('profile.php', 'deleted');
}

if (isset($_POST['id'])) {
    if (!is_admin()) {
        header('Location: dashboard.php');
        exit();
    }

    $id = (int)$_POST['id'];

    if ($id === current_user_id()) {
        redirect_with_status('table.php', 'error');
    }

    $stmt = $conn->prepare('DELETE FROM users WHERE id = ?');
    $stmt->bind_param('i', $id);

    if ($stmt->execute()) {
        redirect_with_status('table.php', 'deleted');
    }

    redirect_with_status('table.php', 'error');
}

header('Location: profile.php');
exit();
?>
