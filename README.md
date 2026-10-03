# TechSense: Intelligent IT Problem Analysis and Troubleshooting System

TechSense is a web-based intelligent IT troubleshooting platform designed to help users identify and resolve common computer, network, software, and hardware problems.

## Quick Start (XAMPP)

1. **Deploy to htdocs**:
   Copy or link `techsense` into your XAMPP web root `C:\xampp\htdocs\techsense`.
2. **Import Database**:
   Open phpMyAdmin (`http://localhost/phpmyadmin/`) and import `sql/techsense.sql`.
3. **Launch in Browser**:
   Navigate to: `http://localhost/techsense/`

## Pre-Seeded Accounts
- **Admin**: Username: `admin` | Password: `Admin@123`
- **Regular User**: Username: `testuser` | Password: `User@123`

## Technology Stack
- Backend: PHP 8.x + PDO
- Database: MySQL / MariaDB
- Frontend: HTML5, CSS3, JavaScript (ES6+), Bootstrap 5.3, Bootstrap Icons
- Charts: Chart.js 4.4
- Architecture: Modular MVC-style components, Role-Based Access Control, Rule-based Recommendation Engine
