# UniSphere

UniSphere is a university portal project made using PHP, MySQL, Bootstrap, HTML and CSS.

## About Project

In this project students, teachers and admin can login according to their role.

Main modules:

- Student registration and login
- Teacher registration and login
- Admin dashboard
- Student profile
- Teacher profile
- Student major project records
- Major project report upload
- Teacher/Admin project review and remarks
- Admin user records
- Admin analytics
- Change password

## Database

Database name: `formdb`

Main tables:

- `users`
- `user_projects`

The `users` table stores student, teacher and admin details.

The `user_projects` table stores student major project details. One student can add multiple projects.

Project details include title, description, technology stack, mentor name, dates, status, report file and remarks.

## How To Run

1. Start Apache and MySQL in XAMPP.
2. Keep the project folder inside `C:\xampp\htdocs`.
3. Open browser and visit:

```text
http://localhost/newproject_v2/login.php
```

4. Import or create the required database tables in phpMyAdmin.

## Project Files

- `index.php` - registration page
- `login.php` - login page
- `dashboard.php` - main dashboard
- `profile.php` - user profile page
- `add_project.php` - add student project
- `edit_project.php` - update student project
- `projects.php` - teacher/admin project list
- `review_project.php` - teacher/admin project review
- `table.php` - admin user records
- `admin/analytics.php` - admin analytics page
- `db.php` - database connection
- `style.css` - project styling

## Submitted By

Name:  
Enrollment No:  
Course:  
Semester:
