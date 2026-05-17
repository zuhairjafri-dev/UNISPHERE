<?php

function e($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function current_user_id() {
    return (int)($_SESSION['user_id'] ?? 0);
}

function current_user_role() {
    return strtolower($_SESSION['role'] ?? '');
}

function is_admin() {
    return current_user_role() === 'admin';
}

function require_admin() {
    protect_page();

    if (!is_admin()) {
        $path = str_replace('\\', '/', $_SERVER['PHP_SELF'] ?? '');
        $target = strpos($path, '/admin/') !== false ? '../dashboard.php' : 'dashboard.php';
        header('Location: ' . $target);
        exit();
    }
}

define('MAX_PROJECT_FILE_SIZE', 5 * 1024 * 1024);

function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function verify_csrf() {
    $postedToken = $_POST['csrf_token'] ?? '';

    if (!$postedToken || !hash_equals($_SESSION['csrf_token'] ?? '', $postedToken)) {
        http_response_code(403);
        die('Invalid request token.');
    }
}

function validate_date($value) {
    $date = DateTime::createFromFormat('Y-m-d', $value);
    return $date && $date->format('Y-m-d') === $value ? $value : null;
}

function redirect_with_status($path, $status) {
    header('Location: ' . $path . '?status=' . urlencode($status));
    exit();
}

function status_message() {
    $messages = [
        'created' => ['success', 'Record created successfully.'],
        'updated' => ['success', 'Changes saved successfully.'],
        'deleted' => ['success', 'Record deleted successfully.'],
        'project_added' => ['success', 'Project added successfully.'],
        'project_updated' => ['success', 'Project updated successfully.'],
        'review_saved' => ['success', 'Project review saved successfully.'],
        'password_changed' => ['success', 'Password updated successfully.'],
        'error' => ['danger', 'Something went wrong. Please try again.'],
        'invalid_image' => ['warning', 'Only JPG, PNG, GIF, and WEBP images are allowed.'],
        'invalid_file' => ['warning', 'Only PDF, DOC, and DOCX project files are allowed.'],
        'duplicate_email' => ['warning', 'An account with this email already exists.'],
    ];

    $status = $_GET['status'] ?? '';

    if (!isset($messages[$status])) {
        return '';
    }

    [$type, $text] = $messages[$status];
    return '<div class="alert alert-' . $type . ' alert-dismissible fade show" role="alert">'
        . e($text)
        . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>'
        . '</div>';
}

function upload_profile_image($fieldName, $fallback = 'images/default.jpg') {
    if (empty($_FILES[$fieldName]['name']) || $_FILES[$fieldName]['error'] !== UPLOAD_ERR_OK) {
        return $fallback;
    }

    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp',
    ];

    $mime = mime_content_type($_FILES[$fieldName]['tmp_name']);

    if (!isset($allowed[$mime])) {
        return false;
    }

    if (!is_dir('uploads')) {
        mkdir('uploads', 0777, true);
    }

    $fileName = time() . '_' . bin2hex(random_bytes(5)) . '.' . $allowed[$mime];
    $target = 'uploads/' . $fileName;

    if (!move_uploaded_file($_FILES[$fieldName]['tmp_name'], $target)) {
        return false;
    }

    return $target;
}

function upload_project_file($fieldName, $fallback = '') {
    if (empty($_FILES[$fieldName]['name']) || $_FILES[$fieldName]['error'] !== UPLOAD_ERR_OK) {
        return $fallback;
    }

    if ($_FILES[$fieldName]['size'] > MAX_PROJECT_FILE_SIZE) {
        return false;
    }

    $allowed = [
        'application/pdf' => 'pdf',
        'application/msword' => 'doc',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
    ];

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($_FILES[$fieldName]['tmp_name']);

    if ($mime === false) {
        $mime = mime_content_type($_FILES[$fieldName]['tmp_name']);
    }

    if (!isset($allowed[$mime])) {
        return false;
    }

    if (!is_dir('uploads/projects')) {
        mkdir('uploads/projects', 0755, true);
    }

    $fileName = time() . '_' . bin2hex(random_bytes(5)) . '.' . $allowed[$mime];
    $target = 'uploads/projects/' . $fileName;

    if (!move_uploaded_file($_FILES[$fieldName]['tmp_name'], $target)) {
        return false;
    }

    return $target;
}

function valid_role($role) {
    return in_array($role, ['Student', 'Teacher', 'Admin'], true);
}

function valid_gender($gender) {
    return in_array($gender, ['Male', 'Female'], true);
}

function clear_sensitive_session() {
    $_SESSION = [];

    if (session_status() === PHP_SESSION_ACTIVE && ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }

    if (session_status() === PHP_SESSION_ACTIVE) {
        session_destroy();
    }
}

function checkUserRole($required_role) {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    $requiredRole = strtolower(trim((string)$required_role));
    $currentRole = strtolower(trim((string)($_SESSION['user_role'] ?? $_SESSION['role'] ?? '')));

    $allowedRoles = ['student', 'teacher', 'admin'];
    if ($requiredRole === '' || !in_array($requiredRole, $allowedRoles, true)) {
        clear_sensitive_session();
        header('Location: index.php?error=unauthorized');
        exit();
    }

    if (empty($_SESSION['user_id']) || $currentRole === '') {
        clear_sensitive_session();
        header('Location: index.php?error=unauthorized');
        exit();
    }

    if (empty($_SESSION['session_regenerated']) || $_SESSION['session_regenerated'] !== true) {
        session_regenerate_id(true);
        $_SESSION['session_regenerated'] = true;
    }

    if ($currentRole !== $requiredRole) {
        clear_sensitive_session();
        header('Location: index.php?error=unauthorized');
        exit();
    }
}

function user_field($user, $key, $fallback = '') {
    if (isset($user[$key]) && $user[$key] !== '') {
        return $user[$key];
    }

    $legacyKey = ucfirst($key);

    if (isset($user[$legacyKey]) && $user[$legacyKey] !== '') {
        return $user[$legacyKey];
    }

    return $fallback;
}

function column_exists($conn, $table, $column) {
    $stmt = $conn->prepare(
        'SELECT COUNT(*) AS total
         FROM INFORMATION_SCHEMA.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE()
           AND TABLE_NAME = ?
           AND COLUMN_NAME = ?'
    );
    $stmt->bind_param('ss', $table, $column);
    $stmt->execute();

    return (int)$stmt->get_result()->fetch_assoc()['total'] > 0;
}

function user_state_column($conn) {
    return column_exists($conn, 'users', 'state') ? 'state' : 'State';
}

function get_user_by_id($conn, $id) {
    $stmt = $conn->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

function logUserActivity($user_id, $action) {
    try {
        $pdo = new PDO('mysql:host=localhost;dbname=formdb;charset=utf8mb4', 'root', '', [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

        $stmt = $pdo->prepare(
            'INSERT INTO activity_logs (user_id, action, created_at) VALUES (:user_id, :action, NOW())'
        );
        $stmt->execute([
            ':user_id' => (int)$user_id,
            ':action' => $action,
        ]);
    } catch (PDOException $e) {
        error_log('Activity logging failed: ' . $e->getMessage());
    }
}

function get_user_projects($conn, $userId) {
    $stmt = $conn->prepare('SELECT * FROM user_projects WHERE user_id = ? AND is_deleted = 0 ORDER BY id DESC');
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    return $stmt->get_result();
}

function get_project_by_id($conn, $projectId) {
    $stmt = $conn->prepare(
        'SELECT p.*, u.name, u.email, u.enrollment_id, u.specialization, u.semester
         FROM user_projects p
         JOIN users u ON u.id = p.user_id
         WHERE p.id = ? AND p.is_deleted = 0'
    );
    $stmt->bind_param('i', $projectId);
    $stmt->execute();

    return $stmt->get_result()->fetch_assoc();
}

function get_recent_projects($conn, $limit = 5) {
    $stmt = $conn->prepare(
        'SELECT p.*, u.name
         FROM user_projects p
         JOIN users u ON u.id = p.user_id
         WHERE p.is_deleted = 0
         ORDER BY p.id DESC
         LIMIT ?'
    );
    $stmt->bind_param('i', $limit);
    $stmt->execute();

    return $stmt->get_result();
}

function count_projects($conn, $userId = null, $status = null) {
    if ($userId !== null && $status !== null) {
        $stmt = $conn->prepare('SELECT COUNT(*) AS total FROM user_projects WHERE user_id = ? AND status = ? AND is_deleted = 0');
        $stmt->bind_param('is', $userId, $status);
    } elseif ($userId !== null) {
        $stmt = $conn->prepare('SELECT COUNT(*) AS total FROM user_projects WHERE user_id = ? AND is_deleted = 0');
        $stmt->bind_param('i', $userId);
    } elseif ($status !== null) {
        $stmt = $conn->prepare('SELECT COUNT(*) AS total FROM user_projects WHERE status = ? AND is_deleted = 0');
        $stmt->bind_param('s', $status);
    } else {
        $stmt = $conn->prepare('SELECT COUNT(*) AS total FROM user_projects WHERE is_deleted = 0');
    }

    $stmt->execute();
    return (int)$stmt->get_result()->fetch_assoc()['total'];
}

function count_users($conn, $role = null) {
    if ($role) {
        $stmt = $conn->prepare('SELECT COUNT(*) AS total FROM users WHERE role = ?');
        $stmt->bind_param('s', $role);
    } else {
        $stmt = $conn->prepare('SELECT COUNT(*) AS total FROM users');
    }

    $stmt->execute();
    return (int)$stmt->get_result()->fetch_assoc()['total'];
}

?>
