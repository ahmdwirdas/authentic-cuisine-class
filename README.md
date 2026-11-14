# 🍳 Authentic Cuisine Class Management System

A web-based culinary class reservation, inquiry, and administrative management system built with PHP and MySQL (PDO).

---

## 📋 Features

- **Public Portal**: Browse authentic culinary classes, view branch details, and submit inquiries.
- **Customer Portal**: Register, book class sessions, review booking statuses, submit ratings, and leave course survey feedback.
- **Admin Dashboard**: Manage culinary classes, physical branches, customer bookings, user feedback, and inquiries.
- **Auto Database Initialization**: The database `cookingcuisine` and all required tables are automatically created and seeded on the very first page visit!

---

## ⚡ Quick Start (2-Minute Setup)

### Prerequisites
Make sure you have either:
- **XAMPP** (Recommended for Windows / Mac) with **Apache** and **MySQL** installed, OR
- **PHP 8.0+** and **MySQL / MariaDB Server** installed.

---

### Step 1: Start MySQL
1. Open **XAMPP Control Panel**.
2. Click **Start** on **MySQL** (and **Apache** if you want to use phpMyAdmin).
   > *Note: Default MySQL credentials are host `127.0.0.1`, user `root`, no password (`""`).*

---

### Step 2: Run the Project

You can run the project using **Method A** (easiest) or **Method B**.

#### Method A: Built-in PHP Server (Easiest)
1. Open a terminal / command prompt inside this project folder:
   ```bash
   cd authentic-cuisine-class-main
   ```
2. Run the start command:
   - **Windows**: double-click `run.bat` or execute:
     ```cmd
     php -S 127.0.0.1:8000
     ```
   - **macOS / Linux**: execute `./run.sh` or:
     ```bash
     php -S 127.0.0.1:8000
     ```
3. Open your browser and navigate to:
   ```
   http://127.0.0.1:8000
   ```

#### Method B: Via XAMPP `htdocs`
1. Copy or clone this folder into your XAMPP web root:
   - **Windows**: `C:\xampp\htdocs\authentic-cuisine-class`
   - **macOS**: `/Applications/XAMPP/htdocs/authentic-cuisine-class`
2. Start both **Apache** and **MySQL** in the XAMPP Control Panel.
3. Open your browser and visit:
   ```
   http://localhost/authentic-cuisine-class/
   ```

---

### Step 3: Automatic Database Linking
**No manual SQL import required!**
When you visit the site for the first time, `db.php` will automatically:
1. Detect your running MySQL server.
2. Create the database `cookingcuisine` if it does not already exist.
3. Import all initial tables and sample data from `database.sql`.

*(Optional)* If you prefer to manually import the database via phpMyAdmin:
1. Open [http://localhost/phpmyadmin/](http://localhost/phpmyadmin/).
2. Create a database named `cookingcuisine`.
3. Go to the **Import** tab and upload `database.sql`.

---

## 🔑 Default Login Credentials

| Role | Email / Username | Password | Access / Notes |
| :--- | :--- | :--- | :--- |
| **Administrator** | `admin` | `admin123` | Full access to Admin Console, Classes, Bookings & Inquiries |
| **Customer (Demo)** | `ahmd@gmail.com` | `password123` | Pre-existing student account with booked classes |
| **New Customer** | *(Register your own)* | *(Your password)* | Click **Register** on the top menu to create a fresh student account |

---

## ⚙️ Configuration (Optional)

If your local MySQL uses custom ports or passwords (other than standard XAMPP defaults), you can configure environment variables or create a `.env` file based on `.env.example`:

| Variable | Default Value | Description |
| :--- | :--- | :--- |
| `DB_HOST` | `127.0.0.1` | MySQL server host |
| `DB_PORT` | `3306` | MySQL server port |
| `DB_NAME` | `cookingcuisine` | Database name |
| `DB_USER` | `root` | Database username |
| `DB_PASS` | `""` *(empty)* | Database password |

---

## 📁 Project Structure

```text
├── admin-dashboard.php       # Administrative command center
├── auth.php                  # Authentication and session handling
├── customer-dashboard.php     # Student portal for bookings and surveys
├── database.sql              # Database schema and initial seed data
├── db.php                    # PDO database connection & auto-migration
├── get_survey_responses.php  # AJAX endpoint for survey feedback
├── hero.png                  # Landing page hero asset
├── index.php                 # Main public landing page
├── login.php                 # Customer & Admin login interface
├── logout.php                # Session destroyer
├── register.php              # Customer registration portal
├── style.css                 # Application styling
├── run.bat                   # 1-click Windows server launcher
├── run.sh                    # 1-click Unix/macOS server launcher
└── README.md                 # Complete setup guide
```

---

## ❓ Troubleshooting

1. **"Database Connection Error: Connection refused" / "Socket access forbidden"**:
   - Ensure MySQL is running in your XAMPP / MariaDB control panel.
   - Verify MySQL is listening on port `3306`.
2. **"PHP is not recognized as an internal or external command"**:
   - Add your PHP installation path (e.g. `C:\xampp\php`) to your Windows System `PATH` environment variable, or use Method B (`htdocs`).
3. **Session expired / Redirect loop**:
   - Clear your browser cookies for `localhost` / `127.0.0.1:8000` or open an Incognito window.
