<?php
include 'db.php';
protect_page();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: dashboard.php');
    exit();
}

verify_csrf();

$id = (int)($_POST['id'] ?? 0);

if (!is_admin() && $id !== current_user_id()) {
    header('Location: profile.php');
    exit();
}

$existing = get_user_by_id($conn, $id);

if (!$existing) {
    header('Location: dashboard.php');
    exit();
}

$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$age = (int)($_POST['age'] ?? 0);
$gender = $_POST['gender'] ?? '';
$role = is_admin() ? ($_POST['role'] ?? $existing['role']) : $existing['role'];
$city = trim($_POST['city'] ?? '');
$state = trim($_POST['state'] ?? '');
$enrollment_id = trim($_POST['enrollment_id'] ?? '');
$specialization = trim($_POST['specialization'] ?? '');
$semester = trim($_POST['semester'] ?? '');
$session_batch = trim($_POST['session_batch'] ?? '');
$designation = trim($_POST['designation'] ?? '');
$department = trim($_POST['department'] ?? '');
$main_subject = trim($_POST['main_subject'] ?? '');
$image = $existing['image'] ?: 'images/default.jpg';

if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || $age < 15 || $age > 90 || !valid_gender($gender) || !valid_role($role)) {
    redirect_with_status(is_admin() ? 'table.php' : 'profile.php', 'error');
}

try {
    $pdo = new PDO('mysql:host=localhost;dbname=formdb;charset=utf8mb4', 'root', '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    $emailCheck = $pdo->prepare('SELECT id FROM users WHERE email = ? AND id != ? LIMIT 1');
    $emailCheck->execute([$email, $id]);

    if ($emailCheck->fetch()) {
        redirect_with_status(is_admin() ? 'table.php' : 'profile.php', 'duplicate_email');
    }

    $uploaded = upload_profile_image('profile_image', $image);

    if ($uploaded === false) {
        redirect_with_status(is_admin() ? 'table.php' : 'profile.php', 'invalid_image');
    }

    $image = $uploaded;

    $stmt = $pdo->prepare(
        'UPDATE users SET
            name = :name, email = :email, age = :age, gender = :gender, role = :role, image = :image,
            enrollment_id = :enrollment_id, specialization = :specialization, semester = :semester, session_batch = :session_batch,
            designation = :designation, department = :department, main_subject = :main_subject, city = :city, ' . user_state_column($conn) . ' = :state
         WHERE id = :id'
    );

    $stmt->execute([
        ':name' => $name,
        ':email' => $email,
        ':age' => $age,
        ':gender' => $gender,
        ':role' => $role,
        ':image' => $image,
        ':enrollment_id' => $enrollment_id,
        ':specialization' => $specialization,
        ':semester' => $semester,
        ':session_batch' => $session_batch,
        ':designation' => $designation,
        ':department' => $department,
        ':main_subject' => $main_subject,
        ':city' => $city,
        ':state' => $state,
        ':id' => $id,
    ]);

    if ($id === current_user_id()) {
        $_SESSION['role'] = $role;
        $_SESSION['name'] = $name;
    }

    redirect_with_status(is_admin() ? 'table.php' : 'profile.php', 'updated');
} catch (PDOException $e) {
    error_log('User update failed: ' . $e->getMessage());
    redirect_with_status(is_admin() ? 'table.php' : 'profile.php', 'error');
}
?>

