CREATE DATABASE IF NOT EXISTS formdb
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE formdb;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(160) NOT NULL UNIQUE,
    age INT NOT NULL,
    gender ENUM('Male', 'Female') NOT NULL,
    role ENUM('Student', 'Teacher', 'Admin') NOT NULL DEFAULT 'Student',
    password VARCHAR(255) NOT NULL,
    image VARCHAR(255) NOT NULL DEFAULT 'images/default.jpg',
    enrollment_id VARCHAR(80) DEFAULT NULL,
    specialization VARCHAR(120) DEFAULT NULL,
    semester VARCHAR(40) DEFAULT NULL,
    session_batch VARCHAR(40) DEFAULT NULL,
    designation VARCHAR(120) DEFAULT NULL,
    department VARCHAR(120) DEFAULT NULL,
    main_subject VARCHAR(120) DEFAULT NULL,
    city VARCHAR(100) DEFAULT NULL,
    state VARCHAR(100) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS user_projects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    project_title VARCHAR(180) NOT NULL,
    project_description TEXT DEFAULT NULL,
    tech_stack VARCHAR(255) NOT NULL,
    mentor_name VARCHAR(120) DEFAULT NULL,
    start_date DATE DEFAULT NULL,
    submission_date DATE DEFAULT NULL,
    project_file VARCHAR(255) DEFAULT NULL,
    status ENUM('Pending', 'In-Progress', 'Completed') NOT NULL DEFAULT 'Pending',
    remarks TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_user_projects_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE
);

INSERT INTO users
    (name, email, age, gender, role, password, image, designation, department, main_subject, city, state)
VALUES
    ('System Administrator', 'admin@unisphere.local', 30, 'Male', 'Admin',
     '$2y$10$hzRJbzt3GZzKdDo3wSOfnuK7/HmR6WReSUF3A5wZkeUVtD7Pi5KLu',
     'images/default.jpg', 'Portal Administrator', 'IT Department', 'System Management', 'Bhopal', 'Madhya Pradesh')
ON DUPLICATE KEY UPDATE email = email;
