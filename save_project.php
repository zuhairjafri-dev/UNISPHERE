<?php
include 'db.php';
protect_page();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: add_project.php');
    exit();
}

if (current_user_role() !== 'student') {
    header('Location: dashboard.php');
    exit();
}

verify_csrf();

$title = trim($_POST['title'] ?? '');
$tech = trim($_POST['tech'] ?? ($_POST['stack'] ?? ''));
$status = $_POST['status'] ?? 'Pending';
$allowedStatus = ['Pending', 'In-Progress', 'Completed'];

if ($title === '' || $tech === '' || !in_array($status, $allowedStatus, true)) {
    redirect_with_status('add_project.php', 'error');
}

$stmt = $conn->prepare('INSERT INTO user_projects (user_id, project_title, tech_stack, status) VALUES (?, ?, ?, ?)');
$userId = current_user_id();
$stmt->bind_param('isss', $userId, $title, $tech, $status);

if ($stmt->execute()) {
    redirect_with_status('profile.php', 'project_added');
}

redirect_with_status('add_project.php', 'error');
?>
