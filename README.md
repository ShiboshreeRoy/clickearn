# 🚀 ClickEarn - Professional PTC Earning Platform

![PHP](https://img.shields.io/badge/PHP-8.0%2B-777BB4?style=for-the-badge&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-00000F?style=for-the-badge&logo=mysql&logoColor=white)
![Tailwind CSS](https://img.shields.io/badge/Tailwind_CSS-38B2AC?style=for-the-badge&logo=tailwind-css&logoColor=white)
![Status](https://img.shields.io/badge/Status-Production%20Ready-success?style=for-the-badge)

**ClickEarn** is a robust, full-stack "Paid-To-Click" (PTC) web application. It allows users to earn money by viewing advertisements, completing tasks, and referring friends. It features a comprehensive Admin Panel for managing users, finances, and content, simulating a real-world business model similar to *Star-Clicks* or *NeoBux*.

---

## 🌟 Key Features

### 👤 User Panel
* **Secure Authentication:** Registration with Email/PIN and secure Login.
* **Interactive Dashboard:** Real-time balance updates, plan status, and daily stats.
* **Ad Viewing Engine:** Anti-cheat timer system (10s) to ensure ad view validation.
* **Membership System:** Tiered access (Free, Gold, Platinum) with different earning rates.
* **Referral System:** Unique referral links with a 10% commission tracking system.
* **Wallet & Withdrawals:** Support for local payment methods (bKash, Nagad, Rocket) and Crypto.
* **Gamification:** "Spin & Win" daily lucky wheel.
* **Support Center:** Built-in ticketing system for user complaints.

### 🛡️ Admin Panel
* **Financial Overview:** Track total earnings, pending withdrawals, and active users.
* **Task Management:** CRUD (Create, Read, Update, Delete) operations for tasks/ads.
* **User Management:** Ban users, edit balances, and view referral trees.
* **Withdrawal Processing:** Approve or Reject payout requests with one click.
* **Manual Payment Gateway:** Verify Transaction IDs for membership upgrades manually.
* **Announcement System:** Post site-wide news and alerts (Info, Warning, Danger).
* **System Settings:** Configure minimum withdrawal limits and rewards without coding.

---

## 🛠️ Technology Stack

* **Backend:** PHP (Vanilla, PDO for Database Abstraction)
* **Frontend:** HTML5, Tailwind CSS (via CDN)
* **Database:** MySQL / MariaDB
* **Security:**
    * `BCrypt` Password Hashing
    * Prepared Statements (SQL Injection Protection)
    * Session Management
    * CSRF Protection tokens (Basic implementation)

---

## ⚙️ Installation Guide

Follow these steps to set up the project on your local machine (XAMPP/WAMP) or live server.

### 1. Clone the Repository
```bash
git clone [https://github.com/shiboshreeroy/clickearn.git](https://github.com/yourusername/clickearn.git)
cd clickearn
````

### 2\. Database Setup

1.  Open **phpMyAdmin**.
2.  Create a new database named `earning_platform`.
3.  Import the `database.sql` file (provided below) or run the SQL commands manually.

### 3\. Configuration

1.  Open `config.php`.
2.  Update the database credentials if necessary:

<!-- end list -->

```php
$host = 'localhost';
$dbname = 'earning_platform';
$user = 'root'; // Your DB Username
$pass = '';     // Your DB Password
```

### 4\. Run the Project

  * Place the project folder in `htdocs` (XAMPP) or `www` (WAMP).
  * Open your browser and visit: `http://localhost/clickearn/index.php`

-----

## 📂 Database Structure (SQL)

If you don't have the `.sql` file, run this SQL query to generate the full database schema:

```sql
CREATE DATABASE earning_platform;
USE earning_platform;

-- Users Table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    security_pin VARCHAR(4) DEFAULT '0000',
    balance DECIMAL(10, 4) DEFAULT 0.0000,
    role ENUM('user', 'admin') DEFAULT 'user',
    referral_code VARCHAR(20) UNIQUE,
    referrer_id INT DEFAULT NULL,
    membership_level ENUM('free', 'gold', 'platinum') DEFAULT 'free',
    last_daily_bonus DATE DEFAULT NULL,
    last_spin DATE DEFAULT NULL,
    full_name VARCHAR(100),
    phone VARCHAR(20),
    country VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tasks/Ads Table
CREATE TABLE tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    url VARCHAR(255) NOT NULL,
    reward DECIMAL(10, 4) DEFAULT 0.0500,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- History Logs
CREATE TABLE task_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    task_id INT NOT NULL,
    completed_at DATE NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE
);

-- Withdrawals
CREATE TABLE withdrawals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    amount DECIMAL(10, 4) NOT NULL,
    method VARCHAR(50) NOT NULL,
    account_details TEXT NOT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Manual Deposit/Upgrades
CREATE TABLE payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    method VARCHAR(50) NOT NULL,
    transaction_id VARCHAR(100) NOT NULL UNIQUE,
    sender_number VARCHAR(20) NOT NULL,
    plan_type ENUM('gold', 'platinum') NOT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Support Tickets
CREATE TABLE support_tickets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    subject VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    admin_reply TEXT DEFAULT NULL,
    status ENUM('open', 'replied', 'closed') DEFAULT 'open',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Announcements
CREATE TABLE announcements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('info', 'success', 'warning', 'danger') DEFAULT 'info',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Global Settings
CREATE TABLE settings (
    setting_key VARCHAR(50) PRIMARY KEY,
    setting_value VARCHAR(255)
);

-- Default Admin (Pass: admin123)
INSERT INTO users (username, email, password, role) 
VALUES ('SuperAdmin', 'admin@platform.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');
```

-----

## 🔑 Default Credentials

| Role | Email | Password |
| :--- | :--- | :--- |
| **Admin** | `admin@platform.com` | `admin123` |
| **User** | (Register a new account) | (Your Choice) |

-----

## 📸 Project Screenshots

*(Add your screenshots here. Example structure below)*

| **User Dashboard** | **Admin Panel** |
|:---:|:---:|
| \<img src="screenshots/dashboard.png" width="400"\> | \<img src="screenshots/admin.png" width="400"\> |

-----

## 🚀 Future Roadmap

  * [ ] Integration of automated Payment Gateways (Stripe/Coinbase).
  * [ ] Email Verification via SMTP (PHPMailer).
  * [ ] ReCAPTCHA integration on Registration.
  * [ ] Dark Mode toggle for User Dashboard.

-----

## 🤝 Contributing

Contributions, issues, and feature requests are welcome\!

1.  Fork the Project.
2.  Create your Feature Branch (`git checkout -b feature/AmazingFeature`).
3.  Commit your Changes (`git commit -m 'Add some AmazingFeature'`).
4.  Push to the Branch (`git push origin feature/AmazingFeature`).
5.  Open a Pull Request.

-----

## 📝 License

Distributed under the MIT License. See `LICENSE` for more information.

-----

**Developed with ❤️ by Shiboshree Roy**

```
```
