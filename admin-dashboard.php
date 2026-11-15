<?php
// admin-dashboard.php - Administrative Command Center
require_once __DIR__ . '/auth.php';
require_admin();
date_default_timezone_set('Asia/Kuala_Lumpur');
$admin_username = $_SESSION['admin_username'];
$admin_role = $_SESSION['admin_role'];

$message = '';
$message_type = 'success';

// Handle CRUD Operations
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    // --- CLASS CRUD ---
    if ($action === 'add_class') {
        $branch_id = sanitize($_POST['branch_id'] ?? '');
        $name = sanitize($_POST['name'] ?? '');
        $description = sanitize($_POST['description'] ?? '');
        $cuisine_type = sanitize($_POST['cuisine_type'] ?? '');
        $instructor_id = sanitize($_POST['instructor_id'] ?? '');
        $instructor = sanitize($_POST['instructor'] ?? '');
        if (!empty($instructor_id)) {
            $instStmt = $pdo->prepare("SELECT name FROM instructors WHERE id = ?");
            $instStmt->execute([$instructor_id]);
            $instName = $instStmt->fetchColumn();
            if ($instName) $instructor = $instName;
        }
        $schedule = sanitize($_POST['schedule'] ?? '');
        $capacity = (int)($_POST['capacity'] ?? 10);
        $price = (float)($_POST['price'] ?? 0.0);

        if (empty($branch_id) || empty($name) || empty($cuisine_type) || (empty($instructor) && empty($instructor_id)) || empty($schedule)) {
            $message = "Please fill in all required class fields.";
            $message_type = "danger";
        } else {
            $class_id = generate_id('CLS');
            $stmt = $pdo->prepare("INSERT INTO cuisineclasses (id, branch_id, name, description, cuisine_type, instructor_id, instructor, schedule, capacity, price) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            try {
                $stmt->execute([$class_id, $branch_id, $name, $description, $cuisine_type, $instructor_id ?: null, $instructor, $schedule, $capacity, $price]);
                $message = "New class successfully added: " . htmlspecialchars($name);
            } catch (PDOException $e) {
                $message = "Error adding class: " . $e->getMessage();
                $message_type = "danger";
            }
        }
    }
    
    elseif ($action === 'edit_class') {
        $class_id = sanitize($_POST['class_id'] ?? '');
        $branch_id = sanitize($_POST['branch_id'] ?? '');
        $name = sanitize($_POST['name'] ?? '');
        $description = sanitize($_POST['description'] ?? '');
        $cuisine_type = sanitize($_POST['cuisine_type'] ?? '');
        $instructor_id = sanitize($_POST['instructor_id'] ?? '');
        $instructor = sanitize($_POST['instructor'] ?? '');
        if (!empty($instructor_id)) {
            $instStmt = $pdo->prepare("SELECT name FROM instructors WHERE id = ?");
            $instStmt->execute([$instructor_id]);
            $instName = $instStmt->fetchColumn();
            if ($instName) $instructor = $instName;
        }
        $schedule = sanitize($_POST['schedule'] ?? '');
        $capacity = (int)($_POST['capacity'] ?? 10);
        $price = (float)($_POST['price'] ?? 0.0);

        if (empty($class_id) || empty($branch_id) || empty($name) || empty($cuisine_type) || (empty($instructor) && empty($instructor_id)) || empty($schedule)) {
            $message = "Please fill in all required fields.";
            $message_type = "danger";
        } else {
            $stmt = $pdo->prepare("UPDATE cuisineclasses SET branch_id = ?, name = ?, description = ?, cuisine_type = ?, instructor_id = ?, instructor = ?, schedule = ?, capacity = ?, price = ? WHERE id = ?");
            try {
                $stmt->execute([$branch_id, $name, $description, $cuisine_type, $instructor_id ?: null, $instructor, $schedule, $capacity, $price, $class_id]);
                $message = "Class details updated successfully.";
            } catch (PDOException $e) {
                $message = "Error updating class: " . $e->getMessage();
                $message_type = "danger";
            }
        }
    }

    elseif ($action === 'delete_class') {
        $class_id = sanitize($_POST['class_id'] ?? '');
        $stmt = $pdo->prepare("DELETE FROM cuisineclasses WHERE id = ?");
        try {
            $stmt->execute([$class_id]);
            $message = "Class successfully deleted.";
        } catch (PDOException $e) {
            $message = "Error deleting class: " . $e->getMessage();
            $message_type = "danger";
        }
    }

    // --- BRANCH CRUD ---
    elseif ($action === 'add_branch') {
        $name = sanitize($_POST['name'] ?? '');
        $address = sanitize($_POST['address'] ?? '');
        $city = sanitize($_POST['city'] ?? '');
        $phone = sanitize($_POST['phone'] ?? '');

        if (empty($name) || empty($address) || empty($city) || empty($phone)) {
            $message = "All branch details are required.";
            $message_type = "danger";
        } else {
            $branch_id = generate_id('BR');
            $stmt = $pdo->prepare("INSERT INTO branches (id, name, address, city, phone) VALUES (?, ?, ?, ?, ?)");
            try {
                $stmt->execute([$branch_id, $name, $address, $city, $phone]);
                $message = "New branch successfully added: " . htmlspecialchars($name);
            } catch (PDOException $e) {
                $message = "Error adding branch: " . $e->getMessage();
                $message_type = "danger";
            }
        }
    }

    elseif ($action === 'edit_branch') {
        $branch_id = sanitize($_POST['branch_id'] ?? '');
        $name = sanitize($_POST['name'] ?? '');
        $address = sanitize($_POST['address'] ?? '');
        $city = sanitize($_POST['city'] ?? '');
        $phone = sanitize($_POST['phone'] ?? '');

        if (empty($branch_id) || empty($name) || empty($address) || empty($city) || empty($phone)) {
            $message = "All branch details are required.";
            $message_type = "danger";
        } else {
            $stmt = $pdo->prepare("UPDATE branches SET name = ?, address = ?, city = ?, phone = ? WHERE id = ?");
            try {
                $stmt->execute([$name, $address, $city, $phone, $branch_id]);
                $message = "Branch details updated successfully.";
            } catch (PDOException $e) {
                $message = "Error updating branch: " . $e->getMessage();
                $message_type = "danger";
            }
        }
    }

    elseif ($action === 'delete_branch') {
        $branch_id = sanitize($_POST['branch_id'] ?? '');
        $stmt = $pdo->prepare("DELETE FROM branches WHERE id = ?");
        try {
            $stmt->execute([$branch_id]);
            $message = "Branch successfully deleted.";
        } catch (PDOException $e) {
            $message = "Error deleting branch: " . $e->getMessage();
            $message_type = "danger";
        }
    }

    // --- SURVEY QUESTION CRUD ---
    elseif ($action === 'add_question') {
        $question_text = sanitize($_POST['question_text'] ?? '');
        $question_type = sanitize($_POST['question_type'] ?? 'rating');

        if (empty($question_text)) {
            $message = "Question text cannot be empty.";
            $message_type = "danger";
        } else {
            $question_id = generate_id('Q');
            $stmt = $pdo->prepare("INSERT INTO questions (id, question_text, question_type) VALUES (?, ?, ?)");
            try {
                $stmt->execute([$question_id, $question_text, $question_type]);
                $message = "New survey question added successfully.";
            } catch (PDOException $e) {
                $message = "Error adding question: " . $e->getMessage();
                $message_type = "danger";
            }
        }
    }

    elseif ($action === 'edit_question') {
        $question_id = sanitize($_POST['question_id'] ?? '');
        $question_text = sanitize($_POST['question_text'] ?? '');
        $question_type = sanitize($_POST['question_type'] ?? 'rating');

        if (empty($question_id) || empty($question_text)) {
            $message = "Question text and ID are required.";
            $message_type = "danger";
        } else {
            $stmt = $pdo->prepare("UPDATE questions SET question_text = ?, question_type = ? WHERE id = ?");
            try {
                $stmt->execute([$question_text, $question_type, $question_id]);
                $message = "Survey question updated successfully.";
            } catch (PDOException $e) {
                $message = "Error updating question: " . $e->getMessage();
                $message_type = "danger";
            }
        }
    }

    elseif ($action === 'delete_question') {
        $question_id = sanitize($_POST['question_id'] ?? '');
        $stmt = $pdo->prepare("DELETE FROM questions WHERE id = ?");
        try {
            $stmt->execute([$question_id]);
            $message = "Survey question successfully deleted.";
        } catch (PDOException $e) {
            $message = "Error deleting question: " . $e->getMessage();
            $message_type = "danger";
        }
    }

    // --- CANCEL BOOKING (ADMIN FORCE CANCEL) ---
    elseif ($action === 'admin_cancel_booking') {
        $booking_id = sanitize($_POST['booking_id'] ?? '');
        $stmt = $pdo->prepare("UPDATE bookings SET status = 'Cancelled' WHERE id = ?");
        try {
            $stmt->execute([$booking_id]);
            $message = "Booking status successfully marked as Cancelled.";
        } catch (PDOException $e) {
            $message = "Error updating booking: " . $e->getMessage();
            $message_type = "danger";
        }
    }

    // --- RECONFIRM BOOKING (ADMIN CONFIRM) ---
    elseif ($action === 'admin_confirm_booking') {
        $booking_id = sanitize($_POST['booking_id'] ?? '');
        $stmt = $pdo->prepare("UPDATE bookings SET status = 'Confirmed' WHERE id = ?");
        try {
            $stmt->execute([$booking_id]);
            $message = "Booking status successfully marked as Confirmed.";
        } catch (PDOException $e) {
            $message = "Error updating booking: " . $e->getMessage();
            $message_type = "danger";
        }
    }

    // --- UPDATE INQUIRY STATUS ---
    elseif ($action === 'update_inquiry_status') {
        $inquiry_id = sanitize($_POST['inquiry_id'] ?? '');
        $new_status = sanitize($_POST['status'] ?? 'Read');
        $stmt = $pdo->prepare("UPDATE inquiries SET status = ? WHERE id = ?");
        try {
            $stmt->execute([$new_status, $inquiry_id]);
            $message = "Inquiry status updated to " . htmlspecialchars($new_status) . ".";
        } catch (PDOException $e) {
            $message = "Error updating inquiry: " . $e->getMessage();
            $message_type = "danger";
        }
    }

    // --- DELETE INQUIRY ---
    elseif ($action === 'delete_inquiry') {
        $inquiry_id = sanitize($_POST['inquiry_id'] ?? '');
        $stmt = $pdo->prepare("DELETE FROM inquiries WHERE id = ?");
        try {
            $stmt->execute([$inquiry_id]);
            $message = "Inquiry deleted successfully.";
        } catch (PDOException $e) {
            $message = "Error deleting inquiry: " . $e->getMessage();
            $message_type = "danger";
        }
    }

    // --- INSTRUCTOR CRUD ---
    elseif ($action === 'add_instructor') {
        $name = sanitize($_POST['name'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $phone = sanitize($_POST['phone'] ?? '');
        $specialty = sanitize($_POST['specialty'] ?? '');
        $bio = sanitize($_POST['bio'] ?? '');
        $status = sanitize($_POST['status'] ?? 'Active');

        if (empty($name) || empty($email) || empty($phone) || empty($specialty)) {
            $message = "Name, email, phone and culinary specialty are required.";
            $message_type = "danger";
        } else {
            $inst_id = generate_id('INS');
            $stmt = $pdo->prepare("INSERT INTO instructors (id, name, email, phone, specialty, bio, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
            try {
                $stmt->execute([$inst_id, $name, $email, $phone, $specialty, $bio, $status]);
                $message = "New instructor successfully registered: " . htmlspecialchars($name);
            } catch (PDOException $e) {
                $message = "Error registering instructor: " . $e->getMessage();
                $message_type = "danger";
            }
        }
    }

    elseif ($action === 'edit_instructor') {
        $inst_id = sanitize($_POST['instructor_id'] ?? '');
        $name = sanitize($_POST['name'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $phone = sanitize($_POST['phone'] ?? '');
        $specialty = sanitize($_POST['specialty'] ?? '');
        $bio = sanitize($_POST['bio'] ?? '');
        $status = sanitize($_POST['status'] ?? 'Active');

        if (empty($inst_id) || empty($name) || empty($email) || empty($phone) || empty($specialty)) {
            $message = "All core instructor details are required.";
            $message_type = "danger";
        } else {
            $stmt = $pdo->prepare("UPDATE instructors SET name = ?, email = ?, phone = ?, specialty = ?, bio = ?, status = ? WHERE id = ?");
            try {
                $stmt->execute([$name, $email, $phone, $specialty, $bio, $status, $inst_id]);
                // Keep classes table instructor text name in sync
                $pdo->prepare("UPDATE cuisineclasses SET instructor = ? WHERE instructor_id = ?")->execute([$name, $inst_id]);
                $message = "Instructor details updated successfully.";
            } catch (PDOException $e) {
                $message = "Error updating instructor: " . $e->getMessage();
                $message_type = "danger";
            }
        }
    }

    elseif ($action === 'delete_instructor') {
        $inst_id = sanitize($_POST['instructor_id'] ?? '');
        $stmt = $pdo->prepare("DELETE FROM instructors WHERE id = ?");
        try {
            $stmt->execute([$inst_id]);
            $pdo->prepare("UPDATE cuisineclasses SET instructor_id = NULL WHERE instructor_id = ?")->execute([$inst_id]);
            $message = "Instructor successfully deleted.";
        } catch (PDOException $e) {
            $message = "Error deleting instructor: " . $e->getMessage();
            $message_type = "danger";
        }
    }

    // --- FEEDBACK REMARK / ACTION PLAN ---
    elseif ($action === 'save_feedback_remark') {
        $survey_id = sanitize($_POST['survey_id'] ?? '');
        $admin_remark = sanitize($_POST['admin_remark'] ?? '');

        if (empty($survey_id)) {
            $message = "Survey feedback record not specified.";
            $message_type = "danger";
        } else {
            $stmt = $pdo->prepare("UPDATE surveys SET admin_remark = ?, remarked_at = NOW() WHERE id = ?");
            try {
                $stmt->execute([$admin_remark, $survey_id]);
                $message = "Admin action remark saved successfully for feedback #" . htmlspecialchars($survey_id) . "!";
            } catch (PDOException $e) {
                $message = "Error saving feedback remark: " . $e->getMessage();
                $message_type = "danger";
            }
        }
    }

    // --- MANUAL PAYMENT STATUS TOGGLE (ADMIN) ---
    elseif ($action === 'update_payment_status') {
        $booking_id = sanitize($_POST['booking_id'] ?? '');
        $new_status = sanitize($_POST['payment_status'] ?? 'Paid');
        $stmt = $pdo->prepare("UPDATE bookings SET payment_status = ? WHERE id = ?");
        try {
            $stmt->execute([$new_status, $booking_id]);
            if ($new_status === 'Paid') {
                // Check if payment row exists
                $chk = $pdo->prepare("SELECT id FROM payments WHERE booking_id = ?");
                $chk->execute([$booking_id]);
                if (!$chk->fetch()) {
                    $bData = $pdo->prepare("SELECT b.customer_id, c.price FROM bookings b JOIN cuisineclasses c ON b.class_id = c.id WHERE b.id = ?");
                    $bData->execute([$booking_id]);
                    $bd = $bData->fetch();
                    if ($bd) {
                        $payId = generate_id('PAY');
                        $txRef = 'TXN-' . strtoupper(substr(uniqid(), 7));
                        $pdo->prepare("INSERT INTO payments (id, booking_id, customer_id, amount, payment_method, status, transaction_ref, paid_at) VALUES (?, ?, ?, ?, 'Cash / Admin Entry', 'Paid', ?, NOW())")
                            ->execute([$payId, $booking_id, $bd['customer_id'], $bd['price'], $txRef]);
                    }
                }
            }
            $message = "Booking payment status updated to " . htmlspecialchars($new_status) . ".";
        } catch (PDOException $e) {
            $message = "Error updating payment status: " . $e->getMessage();
            $message_type = "danger";
        }
    }
}

// ==========================================
// DATA FETCHING & STATISTICS AGGREGATION
// ==========================================

// Global metrics
$total_bookings = $pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'Confirmed'")->fetchColumn();
$total_customers = $pdo->query("SELECT COUNT(*) FROM customers")->fetchColumn();
$total_classes = $pdo->query("SELECT COUNT(*) FROM cuisineclasses")->fetchColumn();

// Average survey rating calculation
$avg_rating_sql = "SELECT AVG(CAST(response AS DECIMAL(10,2))) 
                   FROM surveys s 
                   JOIN questions q ON s.question_id = q.id 
                   WHERE q.question_type = 'rating'";
$avg_rating = round((float)$pdo->query($avg_rating_sql)->fetchColumn(), 2);

// Latest 5 bookings
$latest_bookings = $pdo->query("SELECT b.id, b.booking_date, b.status, c.name as customer_name, cl.name as class_name 
                                FROM bookings b 
                                JOIN customers c ON b.customer_id = c.id 
                                JOIN cuisineclasses cl ON b.class_id = cl.id 
                                ORDER BY b.booking_date DESC LIMIT 5")->fetchAll();

// Branch Performance (bookings count + branch average ratings)
$branch_stats = $pdo->query("
    SELECT br.id, br.name, 
           (SELECT COUNT(*) FROM bookings bk JOIN cuisineclasses cls ON bk.class_id = cls.id WHERE cls.branch_id = br.id AND bk.status = 'Confirmed') as active_bookings,
           (SELECT AVG(CAST(s.response AS DECIMAL(10,2))) 
            FROM surveys s 
            JOIN bookings b ON s.booking_id = b.id
            JOIN cuisineclasses cls ON b.class_id = cls.id 
            JOIN questions q ON s.question_id = q.id
            WHERE cls.branch_id = br.id
            AND q.question_type = 'rating') as avg_score
    FROM branches br
    ORDER BY active_bookings DESC
")->fetchAll();

// Booking Registry (All Bookings) with filtering
$filter_branch = sanitize($_GET['b_id'] ?? '');
$filter_status = sanitize($_GET['status'] ?? '');
$search_query = sanitize($_GET['search'] ?? '');

$registry_sql = "SELECT b.id as booking_id, b.booking_date, b.status, b.payment_status, c.name as customer_name, c.email as customer_email, cl.name as class_name, cl.cuisine_type, cl.schedule, cl.price, br.name as branch_name 
                 FROM bookings b 
                 JOIN customers c ON b.customer_id = c.id 
                 JOIN cuisineclasses cl ON b.class_id = cl.id 
                 JOIN branches br ON cl.branch_id = br.id 
                 WHERE 1=1";

$registry_params = [];
if (!empty($filter_branch)) {
    $registry_sql .= " AND cl.branch_id = ?";
    $registry_params[] = $filter_branch;
}
if (!empty($filter_status)) {
    $registry_sql .= " AND b.status = ?";
    $registry_params[] = $filter_status;
}
if (!empty($search_query)) {
    $registry_sql .= " AND (c.name LIKE ? OR c.email LIKE ? OR b.id LIKE ?)";
    $search_term = "%$search_query%";
    $registry_params[] = $search_term;
    $registry_params[] = $search_term;
    $registry_params[] = $search_term;
}
$registry_sql .= " ORDER BY b.booking_date DESC";

$stmt = $pdo->prepare($registry_sql);
$stmt->execute($registry_params);
$booking_registry = $stmt->fetchAll();

// Survey Analytics: aggregate rating scores per question
$question_aggregates = $pdo->query("
    SELECT q.id, q.question_text, 
           COUNT(s.id) as total_responses, 
           AVG(CAST(s.response AS DECIMAL(10,2))) as avg_score 
    FROM questions q 
    LEFT JOIN surveys s ON q.id = s.question_id 
    WHERE q.question_type = 'rating' 
    GROUP BY q.id
")->fetchAll();

// Fetch text answers for Q-006 or any textual feedback with admin remarks
$text_feedbacks = $pdo->query("
    SELECT s.id as survey_id, s.response, s.admin_remark, s.remarked_at, s.submitted_at, 
           c.name as customer_name, c.email as customer_email, cl.name as class_name, br.name as branch_name 
    FROM surveys s 
    JOIN customers c ON s.customer_id = c.id 
    JOIN bookings b ON s.booking_id = b.id 
    JOIN cuisineclasses cl ON b.class_id = cl.id 
    JOIN branches br ON cl.branch_id = br.id 
    JOIN questions q ON s.question_id = q.id 
    WHERE q.question_type = 'text' 
    ORDER BY s.submitted_at DESC
")->fetchAll();

// Cuisine popularity statistics (for charts)
$cuisine_popularity = $pdo->query("
    SELECT c.cuisine_type, COUNT(b.id) as booking_count 
    FROM cuisineclasses c 
    LEFT JOIN bookings b ON c.id = b.class_id AND b.status = 'Confirmed' 
    GROUP BY c.cuisine_type
")->fetchAll();

// Inquiries list and stats
$all_inquiries = $pdo->query("SELECT * FROM inquiries ORDER BY submitted_at DESC")->fetchAll();
$new_inquiries_count = $pdo->query("SELECT COUNT(*) FROM inquiries WHERE status = 'New'")->fetchColumn();

// Instructors list & stats
$all_instructors = $pdo->query("
    SELECT i.*, 
           (SELECT COUNT(*) FROM cuisineclasses WHERE instructor_id = i.id OR instructor = i.name) as class_count
    FROM instructors i 
    ORDER BY i.name ASC
")->fetchAll();

// Payments summary list & stats
$total_revenue_paid = $pdo->query("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE status = 'Paid'")->fetchColumn();
$recent_payments = $pdo->query("
    SELECT p.*, c.name as customer_name, cl.name as class_name 
    FROM payments p 
    JOIN customers c ON p.customer_id = c.id 
    JOIN bookings b ON p.booking_id = b.id 
    JOIN cuisineclasses cl ON b.class_id = cl.id 
    ORDER BY p.paid_at DESC LIMIT 10
")->fetchAll();

// Distinct months available for reporting filter
$report_months = $pdo->query("
    SELECT DISTINCT DATE_FORMAT(booking_date, '%Y-%m') as ym 
    FROM bookings 
    ORDER BY ym DESC
")->fetchAll(PDO::FETCH_COLUMN);

// Populate lists for forms
$all_branches = $pdo->query("SELECT * FROM branches ORDER BY name")->fetchAll();
$all_classes = $pdo->query("SELECT c.*, b.name as branch_name FROM cuisineclasses c JOIN branches b ON c.branch_id = b.id ORDER BY c.schedule DESC")->fetchAll();
$all_questions = $pdo->query("SELECT * FROM questions ORDER BY id")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Authentic Cuisine Class</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .crud-tabs {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 0.5rem;
            overflow-x: auto;
        }
        .crud-tab-btn {
            background: transparent;
            border: none;
            color: var(--text-secondary);
            font-family: var(--font-heading);
            font-size: 0.9rem;
            font-weight: 600;
            padding: 0.5rem 1rem;
            cursor: pointer;
            border-radius: 4px;
            transition: all 0.3s;
        }
        .crud-tab-btn.active {
            background: var(--accent-gold-dim);
            color: var(--accent-gold-light);
        }
        .crud-content-panel {
            display: none;
        }
        .crud-content-panel.active {
            display: block;
        }
        .grid-half {
            display: grid;
            grid-template-columns: 1fr 1.2fr;
            gap: 2.5rem;
        }
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }
        .form-grid-full {
            grid-column: span 2;
        }
    </style>
</head>
<body>

    <header>
        <div class="nav-container">
            <a href="index.php" class="logo">Authentic <span>Cuisine</span></a>
            <ul class="nav-links">
                <li><a href="index.php">Home</a></li>
                <li><a href="admin-dashboard.php" class="active">Admin Console</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </div>
    </header>

    <div class="container">
        <div class="flex-between mb-4">
            <div>
                <span class="section-subtitle">Staff Command Operations</span>
                <h1 style="font-size: 2.25rem;">Administrative Management Center</h1>
            </div>
            <div class="text-right">
                <span class="badge badge-info" style="margin-bottom: 0.25rem;"><?php echo htmlspecialchars($admin_role); ?></span>
                <div style="font-size: 0.85rem; color: var(--text-secondary);">Logged in: <strong><?php echo htmlspecialchars($admin_username); ?></strong></div>
            </div>
        </div>

        <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo $message_type; ?>">
                <span><?php echo $message_type === 'success' ? '✅' : '⚠️'; ?></span>
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <div class="dashboard-grid">
            <!-- Sidebar -->
            <div class="sidebar">
                <div class="card" style="padding: 1.5rem; width:100%;">
                    <ul class="sidebar-nav">
                        <li class="sidebar-nav-link active" onclick="switchSection('overview', this)">
                            <span>📊</span> Operations Overview
                        </li>
                        <li class="sidebar-nav-link" onclick="switchSection('registry', this)">
                            <span>📋</span> Booking Registry
                        </li>
                        <li class="sidebar-nav-link" onclick="switchSection('feedback', this)">
                            <span>✍️</span> Survey Feedback
                        </li>
                        <li class="sidebar-nav-link" onclick="switchSection('instructors', this)">
                            <span>👨‍🍳</span> Manage Instructors
                        </li>
                        <li class="sidebar-nav-link" onclick="switchSection('inquiries', this)">
                            <span>📬</span> Chef Inquiries <?php if ($new_inquiries_count > 0): ?><span class="badge badge-warning" style="margin-left:0.5rem; font-size:0.7rem;"><?php echo $new_inquiries_count; ?></span><?php endif; ?>
                        </li>
                        <li class="sidebar-nav-link" onclick="switchSection('payments', this)">
                            <span>💳</span> Payment History
                        </li>
                        <li class="sidebar-nav-link" onclick="switchSection('crud', this)">
                            <span>⚙️</span> Database CRUD
                        </li>
                        <li class="sidebar-nav-link" onclick="switchSection('reports', this)">
                            <span>📈</span> Generate Reports & PDF
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Content Area -->
            <div class="content-area">

                <!-- SECTION 1: OVERVIEW -->
                <section id="section-overview" class="dashboard-section">
                    <h2 class="mb-4">Operational Status Overview</h2>
                    
                    <!-- Stats Metrics row -->
                    <div class="stats-row">
                        <div class="stat-card">
                            <span class="stat-label">Confirmed Bookings</span>
                            <span class="stat-value"><?php echo $total_bookings; ?></span>
                        </div>
                        <div class="stat-card stat-success">
                            <span class="stat-label">Registered Customers</span>
                            <span class="stat-value"><?php echo $total_customers; ?></span>
                        </div>
                        <div class="stat-card stat-info">
                            <span class="stat-label">Cuisine Classes</span>
                            <span class="stat-value"><?php echo $total_classes; ?></span>
                        </div>
                        <div class="stat-card stat-warning">
                            <span class="stat-label">Average Experience Rating</span>
                            <span class="stat-value">⭐ <?php echo $avg_rating > 0 ? $avg_rating : 'N/A'; ?></span>
                        </div>
                        <div class="stat-card" style="cursor: pointer;" onclick="document.querySelector('.sidebar-nav-link[onclick*=\'inquiries\']').click()">
                            <span class="stat-label">Chef Inquiries</span>
                            <span class="stat-value" style="color: var(--accent-gold-light);"><?php echo count($all_inquiries); ?> <?php if ($new_inquiries_count > 0): ?><small style="font-size:0.8rem; color:#ef4444; font-weight:normal;">(<?php echo $new_inquiries_count; ?> new)</small><?php endif; ?></span>
                        </div>
                    </div>

                    <div class="grid-half">
                        <!-- Left: Latest bookings -->
                        <div>
                            <h3 class="mb-3">Recent Bookings Log</h3>
                            <div class="table-responsive">
                                <table style="font-size: 0.85rem;">
                                    <thead>
                                        <tr>
                                            <th>Class / Customer</th>
                                            <th>Date</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($latest_bookings as $lbk): ?>
                                            <tr>
                                                <td>
                                                    <div style="font-weight:600; color:#ffffff;"><?php echo htmlspecialchars($lbk['class_name']); ?></div>
                                                    <span class="text-dim"><?php echo htmlspecialchars($lbk['customer_name']); ?></span>
                                                </td>
                                                <td><?php echo date('d M H:i', strtotime($lbk['booking_date'])); ?></td>
                                                <td>
                                                    <span class="badge badge-<?php echo $lbk['status'] === 'Confirmed' ? 'success' : 'danger'; ?>" style="font-size:0.65rem; padding:0.1rem 0.4rem;">
                                                        <?php echo $lbk['status']; ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Right: Branch performances -->
                        <div>
                            <h3 class="mb-3">Performance by Branch</h3>
                            <div class="table-responsive">
                                <table style="font-size: 0.85rem;">
                                    <thead>
                                        <tr>
                                            <th>Branch</th>
                                            <th class="text-center">Bookings</th>
                                            <th class="text-right">Average Rating</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($branch_stats as $bs): ?>
                                            <tr>
                                                <td style="font-weight:600; color:#ffffff;"><?php echo htmlspecialchars($bs['name']); ?></td>
                                                <td class="text-center"><?php echo $bs['active_bookings']; ?></td>
                                                <td class="text-right text-gold">
                                                    <?php echo $bs['avg_score'] ? '★ ' . round((float)$bs['avg_score'], 1) : 'No reviews'; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- SECTION 2: BOOKING REGISTRY -->
                <section id="section-registry" class="dashboard-section" style="display:none;">
                    <h2 class="mb-2">Customer Booking Registry</h2>
                    <p class="text-dim mb-4" style="font-size:0.95rem;">Monitor and manage class bookings across all branches. Search and update booking status manually.</p>

                    <!-- Search Filter Bar -->
                    <form method="GET" action="admin-dashboard.php" class="filter-bar">
                        <div class="filter-group">
                            <label for="reg_branch">Branch</label>
                            <select name="b_id" id="reg_branch" class="form-control" style="min-width: 150px;">
                                <option value="">All Branches</option>
                                <?php foreach ($all_branches as $br): ?>
                                    <option value="<?php echo $br['id']; ?>" <?php echo $filter_branch === $br['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($br['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label for="reg_status">Status</label>
                            <select name="status" id="reg_status" class="form-control" style="min-width: 120px;">
                                <option value="">All Statuses</option>
                                <option value="Confirmed" <?php echo $filter_status === 'Confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                <option value="Cancelled" <?php echo $filter_status === 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label for="reg_search">Search Customer / ID</label>
                            <input type="text" name="search" id="reg_search" class="form-control" placeholder="Search..." value="<?php echo htmlspecialchars($search_query); ?>" style="min-width: 180px;">
                        </div>
                        <button type="submit" class="btn btn-gold-outline">Apply Filters</button>
                        <?php if (!empty($filter_branch) || !empty($filter_status) || !empty($search_query)): ?>
                            <a href="admin-dashboard.php#registry" class="btn btn-secondary" onclick="window.location.href='admin-dashboard.php#registry'; window.location.reload();">Clear</a>
                        <?php endif; ?>
                    </form>

                    <!-- Registry Table -->
                    <?php if (empty($booking_registry)): ?>
                        <div class="card text-center" style="padding: 4rem 2rem;">
                            <p class="text-dim">No customer bookings found matching criteria.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table style="font-size: 0.9rem;">
                                <thead>
                                    <tr>
                                        <th>Booking ID</th>
                                        <th>Customer Info</th>
                                        <th>Class Detail</th>
                                        <th>Branch</th>
                                        <th>Class Date</th>
                                        <th>Status</th>
                                        <th class="text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($booking_registry as $reg): ?>
                                        <tr>
                                            <td style="font-family:monospace; font-weight:600; font-size:0.85rem;"><?php echo $reg['booking_id']; ?></td>
                                            <td>
                                                <div style="font-weight:600; color:#ffffff;"><?php echo htmlspecialchars($reg['customer_name']); ?></div>
                                                <span class="text-dim" style="font-size:0.75rem;"><?php echo htmlspecialchars($reg['customer_email']); ?></span>
                                            </td>
                                            <td>
                                                <div style="font-weight:600; color:#ffffff;"><?php echo htmlspecialchars($reg['class_name']); ?></div>
                                                <span class="class-type" style="font-size:0.65rem; padding:0.1rem 0.4rem;"><?php echo htmlspecialchars($reg['cuisine_type']); ?></span>
                                            </td>
                                            <td><?php echo htmlspecialchars($reg['branch_name']); ?></td>
                                            <td><?php echo date('d M Y, h:i A', strtotime($reg['schedule'])); ?></td>
                                            <td>
                                                <span class="badge badge-<?php echo $reg['status'] === 'Confirmed' ? 'success' : 'danger'; ?>">
                                                    <?php echo $reg['status']; ?>
                                                </span>
                                            </td>
                                            <td class="text-right">
                                                <?php if ($reg['status'] === 'Confirmed'): ?>
                                                    <form method="POST" action="admin-dashboard.php" style="display:inline-block;" onsubmit="return confirm('Cancel this customer booking?');">
                                                        <input type="hidden" name="action" value="admin_cancel_booking">
                                                        <input type="hidden" name="booking_id" value="<?php echo $reg['booking_id']; ?>">
                                                        <button type="submit" class="btn btn-danger btn-sm">Cancel</button>
                                                    </form>
                                                <?php else: ?>
                                                    <form method="POST" action="admin-dashboard.php" style="display:inline-block;" onsubmit="return confirm('Re-confirm this customer booking?');">
                                                        <input type="hidden" name="action" value="admin_confirm_booking">
                                                        <input type="hidden" name="booking_id" value="<?php echo $reg['booking_id']; ?>">
                                                        <button type="submit" class="btn btn-gold-outline btn-sm">Re-Confirm</button>
                                                    </form>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </section>

                <!-- SECTION 3: SURVEY FEEDBACK -->
                <section id="section-feedback" class="dashboard-section" style="display:none;">
                    <h2 class="mb-2">Survey Response Center</h2>
                    <p class="text-dim mb-4" style="font-size:0.95rem;">Review ratings metrics and textual suggestions submitted by students upon session completions.</p>

                    <!-- Rating question aggregates -->
                    <h3 class="mb-3">Aggregate Customer Satisfaction Ratings</h3>
                    <div class="stats-row" style="margin-bottom: 3rem;">
                        <?php foreach ($question_aggregates as $q_agg): ?>
                            <div class="stat-card" style="padding: 1.25rem;">
                                <span class="stat-label" style="font-size:0.75rem; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:200px;" title="<?php echo htmlspecialchars($q_agg['question_text']); ?>">
                                    <?php echo htmlspecialchars($q_agg['question_text']); ?>
                                </span>
                                <span class="stat-value" style="font-size: 1.75rem; color: var(--accent-gold-light);">
                                    <?php echo $q_agg['avg_score'] ? '★ ' . round((float)$q_agg['avg_score'], 1) : 'No reviews'; ?>
                                </span>
                                <span class="text-dim" style="font-size: 0.75rem;"><?php echo $q_agg['total_responses']; ?> responses total</span>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Text feedbacks list -->
                    <h3 class="mb-3">Customer Suggestions & Comments</h3>
                    <?php if (empty($text_feedbacks)): ?>
                        <div class="card text-center" style="padding: 4rem 2rem;">
                            <p class="text-dim">No suggestions or text comments submitted yet.</p>
                        </div>
                    <?php else: ?>
                        <div style="display: flex; flex-direction: column; gap: 1.25rem;">
                            <?php foreach ($text_feedbacks as $fb): ?>
                                <div class="card" style="padding: 1.5rem; border-color: rgba(255,255,255,0.08);">
                                    <div class="flex-between mb-2" style="font-size: 0.8rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem;">
                                        <div>
                                            <strong><?php echo htmlspecialchars($fb['customer_name']); ?></strong>
                                            <span class="text-dim">·</span>
                                            <span style="color:var(--text-secondary); font-size:0.75rem;"><?php echo htmlspecialchars($fb['customer_email']); ?></span>
                                            <span class="text-dim">on</span>
                                            <span style="color:#ffffff;"><?php echo htmlspecialchars($fb['class_name']); ?></span>
                                            <span class="text-dim">(<?php echo htmlspecialchars($fb['branch_name']); ?>)</span>
                                        </div>
                                        <span class="text-dim"><?php echo date('d M Y, h:i A', strtotime($fb['submitted_at'])); ?></span>
                                    </div>
                                    <p style="font-style: italic; font-size: 0.95rem; color:#f3f4f6; margin-bottom:1rem;">"<?php echo htmlspecialchars($fb['response']); ?>"</p>

                                    <?php if (!empty($fb['admin_remark'])): ?>
                                        <div style="background:rgba(212,175,55,0.08); border-left:3px solid var(--accent-gold); padding:0.75rem 1rem; border-radius:0 6px 6px 0; margin-bottom:0.75rem;">
                                            <div style="font-size:0.75rem; color:var(--accent-gold-light); font-weight:600; margin-bottom:0.25rem;">
                                                ✅ Admin Action / Remark
                                                <span class="text-dim" style="font-weight:normal; margin-left:0.5rem;">(<?php echo date('d M Y, h:i A', strtotime($fb['remarked_at'])); ?>)</span>
                                            </div>
                                            <p style="font-size:0.9rem; color:#f3f4f6; margin:0;"><?php echo nl2br(htmlspecialchars($fb['admin_remark'])); ?></p>
                                        </div>
                                    <?php endif; ?>

                                    <form method="POST" action="admin-dashboard.php" style="margin-top:0.5rem;">
                                        <input type="hidden" name="action" value="save_feedback_remark">
                                        <input type="hidden" name="survey_id" value="<?php echo $fb['survey_id']; ?>">
                                        <div style="display:flex; gap:0.75rem; align-items:flex-end;">
                                            <div style="flex:1;">
                                                <label style="font-size:0.75rem; color:var(--text-secondary); margin-bottom:0.25rem; display:block;">
                                                    <?php echo !empty($fb['admin_remark']) ? '✏️ Update Action / Remark' : '📝 Add Action / Remark'; ?>
                                                </label>
                                                <textarea name="admin_remark" class="form-control" rows="2" placeholder="Describe follow-up action taken for this feedback..."
                                                    style="font-size:0.85rem; resize:vertical;"><?php echo htmlspecialchars($fb['admin_remark'] ?? ''); ?></textarea>
                                            </div>
                                            <button type="submit" class="btn btn-gold-outline btn-sm" style="white-space:nowrap; padding:0.5rem 1rem;">
                                                💾 Save Remark
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </section>

                <!-- SECTION: CHEF INQUIRIES -->
                <section id="section-inquiries" class="dashboard-section" style="display:none;">
                    <div class="flex-between mb-2">
                        <h2>Chef Inquiries & Questions</h2>
                        <span class="text-dim"><?php echo count($all_inquiries); ?> messages received</span>
                    </div>
                    <p class="text-dim mb-4" style="font-size:0.95rem;">Questions and messages submitted via the "Have Questions? Ask Our Chefs" form on the homepage.</p>

                    <?php if (empty($all_inquiries)): ?>
                        <div class="card text-center" style="padding: 4rem 2rem;">
                            <p class="text-dim">No customer inquiries received yet.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Date & ID</th>
                                        <th>Sender Details</th>
                                        <th>Inquiry Message</th>
                                        <th>Status</th>
                                        <th class="text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($all_inquiries as $inq): ?>
                                        <tr>
                                            <td style="font-size: 0.85rem;">
                                                <div style="font-weight:600; color:#ffffff;"><?php echo htmlspecialchars($inq['id']); ?></div>
                                                <span class="text-dim"><?php echo date('d M Y, h:i A', strtotime($inq['submitted_at'])); ?></span>
                                            </td>
                                            <td>
                                                <div style="font-weight:600; color:var(--accent-gold-light);"><?php echo htmlspecialchars($inq['name']); ?></div>
                                                <a href="mailto:<?php echo htmlspecialchars($inq['email']); ?>" style="color:var(--text-secondary); font-size:0.85rem;">
                                                    ✉️ <?php echo htmlspecialchars($inq['email']); ?>
                                                </a>
                                            </td>
                                            <td style="max-width: 380px;">
                                                <div style="font-size:0.9rem; line-height:1.4; color:#f3f4f6; background:rgba(255,255,255,0.03); padding:0.75rem; border-radius:6px; border-left: 3px solid var(--accent-gold);">
                                                    <?php echo nl2br(htmlspecialchars($inq['message'])); ?>
                                                </div>
                                            </td>
                                            <td>
                                                <?php 
                                                    $badge_class = 'info';
                                                    if ($inq['status'] === 'New') $badge_class = 'warning';
                                                    elseif ($inq['status'] === 'Replied') $badge_class = 'success';
                                                ?>
                                                <span class="badge badge-<?php echo $badge_class; ?>">
                                                    <?php echo htmlspecialchars($inq['status']); ?>
                                                </span>
                                            </td>
                                            <td class="text-right">
                                                <form method="POST" action="admin-dashboard.php" style="display:inline-block; margin-right:0.25rem;">
                                                    <input type="hidden" name="action" value="update_inquiry_status">
                                                    <input type="hidden" name="inquiry_id" value="<?php echo $inq['id']; ?>">
                                                    <select name="status" onchange="this.form.submit()" class="form-control" style="font-size:0.75rem; padding:0.25rem 0.5rem; display:inline-block; width:auto; height:auto;">
                                                        <option value="New" <?php echo $inq['status'] === 'New' ? 'selected' : ''; ?>>New</option>
                                                        <option value="Read" <?php echo $inq['status'] === 'Read' ? 'selected' : ''; ?>>Read</option>
                                                        <option value="Replied" <?php echo $inq['status'] === 'Replied' ? 'selected' : ''; ?>>Replied</option>
                                                    </select>
                                                </form>
                                                <a href="mailto:<?php echo urlencode($inq['email']); ?>?subject=Re: Inquiry with Authentic Cuisine Class&body=Dear <?php echo urlencode($inq['name']); ?>,%0D%0A%0D%0AThank you for reaching out to our chefs..." class="btn btn-gold-outline btn-sm" style="margin-right:0.25rem;">Reply</a>
                                                <form method="POST" action="admin-dashboard.php" style="display:inline-block;" onsubmit="return confirm('Delete this inquiry?');">
                                                    <input type="hidden" name="action" value="delete_inquiry">
                                                    <input type="hidden" name="inquiry_id" value="<?php echo $inq['id']; ?>">
                                                    <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </section>

                <!-- SECTION: MANAGE INSTRUCTORS -->
                <section id="section-instructors" class="dashboard-section" style="display:none;">
                    <div class="flex-between mb-2">
                        <div>
                            <span class="section-subtitle">Academy Staff</span>
                            <h2>Manage Culinary Instructors</h2>
                        </div>
                        <span class="text-dim"><?php echo count($all_instructors); ?> instructors registered</span>
                    </div>
                    <p class="text-dim mb-4" style="font-size:0.95rem;">Register new chef instructors, manage their profiles and link them to cuisine classes.</p>

                    <div class="grid-half">
                        <!-- Left: Instructor List -->
                        <div class="table-responsive" style="max-height:600px; overflow-y:auto;">
                            <?php if (empty($all_instructors)): ?>
                                <div class="card text-center" style="padding:3rem;">
                                    <p class="text-dim">No instructors registered yet.</p>
                                </div>
                            <?php else: ?>
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Instructor</th>
                                            <th>Specialty</th>
                                            <th class="text-center">Classes</th>
                                            <th>Status</th>
                                            <th class="text-right">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($all_instructors as $ins): ?>
                                            <tr>
                                                <td>
                                                    <div style="font-weight:600; color:#ffffff;"><?php echo htmlspecialchars($ins['name']); ?></div>
                                                    <div class="text-dim" style="font-size:0.75rem;"><?php echo htmlspecialchars($ins['email']); ?></div>
                                                    <div class="text-dim" style="font-size:0.75rem;">📞 <?php echo htmlspecialchars($ins['phone']); ?></div>
                                                </td>
                                                <td style="font-size:0.85rem;">
                                                    <span class="class-type" style="font-size:0.7rem;"><?php echo htmlspecialchars($ins['specialty']); ?></span>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge badge-info" style="font-size:0.7rem;"><?php echo $ins['class_count']; ?></span>
                                                </td>
                                                <td>
                                                    <span class="badge badge-<?php echo $ins['status'] === 'Active' ? 'success' : 'danger'; ?>" style="font-size:0.7rem;">
                                                        <?php echo $ins['status']; ?>
                                                    </span>
                                                </td>
                                                <td class="text-right">
                                                    <button class="btn btn-gold-outline btn-sm" style="margin-right:0.25rem;"
                                                        onclick="populateEditInstructor(<?php echo htmlspecialchars(json_encode($ins)); ?>)">Edit</button>
                                                    <form method="POST" action="admin-dashboard.php" style="display:inline-block;"
                                                        onsubmit="return confirm('Delete instructor <?php echo htmlspecialchars(addslashes($ins['name'])); ?>?');">
                                                        <input type="hidden" name="action" value="delete_instructor">
                                                        <input type="hidden" name="instructor_id" value="<?php echo $ins['id']; ?>">
                                                        <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php endif; ?>
                        </div>

                        <!-- Right: Add / Edit Form -->
                        <div class="card" style="padding:1.5rem;">
                            <h3 id="inst-form-title" class="mb-3">Register New Instructor</h3>
                            <form method="POST" action="admin-dashboard.php" id="inst-form">
                                <input type="hidden" name="action" value="add_instructor" id="inst-form-action">
                                <input type="hidden" name="instructor_id" id="form-inst-id">

                                <div class="form-grid">
                                    <div class="form-group form-grid-full">
                                        <label for="f_inst_name">Full Name</label>
                                        <input type="text" name="name" id="f_inst_name" class="form-control" required placeholder="e.g. Chef Marcus Lim">
                                    </div>
                                    <div class="form-group">
                                        <label for="f_inst_email">Email Address</label>
                                        <input type="email" name="email" id="f_inst_email" class="form-control" required placeholder="chef@example.com">
                                    </div>
                                    <div class="form-group">
                                        <label for="f_inst_phone">Phone Number</label>
                                        <input type="text" name="phone" id="f_inst_phone" class="form-control" required placeholder="+60 12-345 6789">
                                    </div>
                                    <div class="form-group form-grid-full">
                                        <label for="f_inst_specialty">Culinary Specialty</label>
                                        <select name="specialty" id="f_inst_specialty" class="form-control" required>
                                            <option value="">Select specialty...</option>
                                            <option value="Haute Cuisine">Haute Cuisine</option>
                                            <option value="Vegetarian Cuisine">Vegetarian Cuisine</option>
                                            <option value="Fusion Cuisine">Fusion Cuisine</option>
                                            <option value="Novella Cuisine">Novella Cuisine</option>
                                            <option value="Pastry & Desserts">Pastry & Desserts</option>
                                            <option value="Traditional Malay">Traditional Malay</option>
                                            <option value="Multi-Cuisine">Multi-Cuisine</option>
                                        </select>
                                    </div>
                                    <div class="form-group form-grid-full">
                                        <label for="f_inst_bio">Bio / Profile</label>
                                        <textarea name="bio" id="f_inst_bio" class="form-control" rows="3"
                                            placeholder="Brief background, years of experience, awards..."></textarea>
                                    </div>
                                    <div class="form-group form-grid-full">
                                        <label for="f_inst_status">Status</label>
                                        <select name="status" id="f_inst_status" class="form-control">
                                            <option value="Active">Active</option>
                                            <option value="Inactive">Inactive</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="flex-between mt-3" style="gap:1rem;">
                                    <button type="button" class="btn btn-secondary" onclick="resetInstructorForm()" style="width:35%;">Reset</button>
                                    <button type="submit" class="btn btn-primary" id="inst-submit-btn" style="width:65%;">Register Instructor</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </section>

                <!-- SECTION: PAYMENT HISTORY -->
                <section id="section-payments" class="dashboard-section" style="display:none;">
                    <div class="flex-between mb-2">
                        <div>
                            <span class="section-subtitle">Financial Records</span>
                            <h2>Payment History & Revenue</h2>
                        </div>
                        <div class="stat-card" style="padding:0.75rem 1.5rem; min-width:auto;">
                            <span class="stat-label" style="font-size:0.7rem;">Total Revenue (Paid)</span>
                            <span class="stat-value" style="font-size:1.5rem; color:var(--accent-gold-light);">RM <?php echo number_format($total_revenue_paid, 2); ?></span>
                        </div>
                    </div>
                    <p class="text-dim mb-4" style="font-size:0.95rem;">View all customer payment transactions. Manually toggle payment status for cash or offline payments.</p>

                    <!-- Recent Payments Table -->
                    <h3 class="mb-3">Recent Payment Transactions</h3>
                    <?php if (empty($recent_payments)): ?>
                        <div class="card text-center" style="padding:4rem 2rem;">
                            <p class="text-dim">No payment records found yet.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table style="font-size:0.85rem;">
                                <thead>
                                    <tr>
                                        <th>Payment ID</th>
                                        <th>Transaction Ref</th>
                                        <th>Customer</th>
                                        <th>Class</th>
                                        <th class="text-right">Amount</th>
                                        <th>Method</th>
                                        <th>Status</th>
                                        <th>Date Paid</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_payments as $pay): ?>
                                        <tr>
                                            <td style="font-family:monospace; font-weight:600; color:var(--accent-gold-light); font-size:0.8rem;">
                                                <?php echo htmlspecialchars($pay['id']); ?>
                                            </td>
                                            <td style="font-family:monospace; font-size:0.8rem; color:var(--text-secondary);">
                                                <?php echo htmlspecialchars($pay['transaction_ref']); ?>
                                            </td>
                                            <td style="font-weight:600; color:#ffffff;"><?php echo htmlspecialchars($pay['customer_name']); ?></td>
                                            <td style="color:var(--text-secondary);"><?php echo htmlspecialchars($pay['class_name']); ?></td>
                                            <td class="text-right" style="font-weight:600; color:var(--accent-gold-light);">
                                                RM <?php echo number_format($pay['amount'], 2); ?>
                                            </td>
                                            <td style="font-size:0.8rem;"><?php echo htmlspecialchars($pay['payment_method'] ?? '—'); ?></td>
                                            <td>
                                                <span class="badge badge-<?php echo $pay['status'] === 'Paid' ? 'success' : 'warning'; ?>" style="font-size:0.7rem;">
                                                    <?php echo $pay['status']; ?>
                                                </span>
                                            </td>
                                            <td style="font-size:0.8rem; color:var(--text-secondary);">
                                                <?php echo $pay['paid_at'] ? date('d M Y, h:i A', strtotime($pay['paid_at'])) : '—'; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>

                    <!-- Booking Registry with Payment Status Toggle -->
                    <h3 class="mb-3" style="margin-top:2.5rem;">Booking Payment Status Management</h3>
                    <p class="text-dim mb-3" style="font-size:0.85rem;">Toggle payment status for each confirmed booking. Marking Paid auto-creates a payment record.</p>
                    <div class="table-responsive">
                        <table style="font-size:0.85rem;">
                            <thead>
                                <tr>
                                    <th>Booking ID</th>
                                    <th>Customer</th>
                                    <th>Class</th>
                                    <th class="text-right">Price</th>
                                    <th>Booking Status</th>
                                    <th>Payment</th>
                                    <th class="text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($booking_registry as $reg): ?>
                                    <tr>
                                        <td style="font-family:monospace; font-size:0.8rem;"><?php echo $reg['booking_id']; ?></td>
                                        <td>
                                            <div style="font-weight:600; color:#fff;"><?php echo htmlspecialchars($reg['customer_name']); ?></div>
                                            <div class="text-dim" style="font-size:0.75rem;"><?php echo htmlspecialchars($reg['customer_email']); ?></div>
                                        </td>
                                        <td><?php echo htmlspecialchars($reg['class_name']); ?></td>
                                        <td class="text-right" style="font-weight:600; color:var(--accent-gold-light);">RM <?php echo number_format($reg['price'], 2); ?></td>
                                        <td>
                                            <span class="badge badge-<?php echo $reg['status'] === 'Confirmed' ? 'success' : 'danger'; ?>" style="font-size:0.7rem;">
                                                <?php echo $reg['status']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge badge-<?php echo ($reg['payment_status'] ?? 'Unpaid') === 'Paid' ? 'success' : 'warning'; ?>" style="font-size:0.7rem;">
                                                <?php echo $reg['payment_status'] ?? 'Unpaid'; ?>
                                            </span>
                                        </td>
                                        <td class="text-right">
                                            <form method="POST" action="admin-dashboard.php" style="display:inline-flex; gap:0.25rem; align-items:center;">
                                                <input type="hidden" name="action" value="update_payment_status">
                                                <input type="hidden" name="booking_id" value="<?php echo $reg['booking_id']; ?>">
                                                <select name="payment_status" class="form-control"
                                                    style="font-size:0.75rem; padding:0.2rem 0.4rem; display:inline-block; width:auto; height:auto;">
                                                    <option value="Unpaid" <?php echo ($reg['payment_status'] ?? 'Unpaid') === 'Unpaid' ? 'selected' : ''; ?>>Unpaid</option>
                                                    <option value="Paid" <?php echo ($reg['payment_status'] ?? '') === 'Paid' ? 'selected' : ''; ?>>Paid</option>
                                                </select>
                                                <button type="submit" class="btn btn-gold-outline btn-sm">Set</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </section>

                <!-- SECTION 4: CRUD MANAGEMENT -->
                <section id="section-crud" class="dashboard-section" style="display:none;">
                    <h2 class="mb-2">System Database Management</h2>
                    <p class="text-dim mb-4" style="font-size:0.95rem;">Manage core data collections: culinary branches, course schedules, and survey question items.</p>

                    <!-- Crud Sub-Tabs -->
                    <div class="crud-tabs">
                        <button type="button" class="crud-tab-btn active" onclick="switchCrudTab('classes', this)">Manage Classes</button>
                        <button type="button" class="crud-tab-btn" onclick="switchCrudTab('branches', this)">Manage Branches</button>
                        <button type="button" class="crud-tab-btn" onclick="switchCrudTab('questions', this)">Manage Survey Questions</button>
                    </div>

                    <!-- SUB-PANEL: CLASSES -->
                    <div id="crud-classes" class="crud-content-panel active">
                        <div class="grid-half">
                            <!-- Left: List -->
                            <div class="table-responsive" style="max-height: 550px; overflow-y: auto;">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Class Info</th>
                                            <th>Branch</th>
                                            <th>Price</th>
                                            <th class="text-right">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($all_classes as $acls): ?>
                                            <tr>
                                                <td>
                                                    <div style="font-weight:600; color:#ffffff;"><?php echo htmlspecialchars($acls['name']); ?></div>
                                                    <div class="text-dim" style="font-size:0.75rem;">
                                                        Type: <strong><?php echo $acls['cuisine_type']; ?></strong> | Chef: <?php echo htmlspecialchars($acls['instructor']); ?>
                                                    </div>
                                                </td>
                                                <td style="font-size: 0.85rem;"><?php echo htmlspecialchars($acls['branch_name']); ?></td>
                                                <td style="font-size: 0.85rem;">RM <?php echo number_format($acls['price'], 2); ?></td>
                                                <td class="text-right">
                                                    <button class="btn btn-gold-outline btn-sm" style="margin-right: 0.25rem;" onclick="populateEditClass(<?php echo htmlspecialchars(json_encode($acls)); ?>)">Edit</button>
                                                    <form method="POST" action="admin-dashboard.php" style="display:inline-block;" onsubmit="return confirm('Delete class permanently? This will remove all associated bookings!');">
                                                        <input type="hidden" name="action" value="delete_class">
                                                        <input type="hidden" name="class_id" value="<?php echo $acls['id']; ?>">
                                                        <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Right: Form -->
                            <div class="card" style="padding: 1.5rem;">
                                <h3 id="class-form-title" class="mb-3">Add New Cuisine Class</h3>
                                <form method="POST" action="admin-dashboard.php" id="class-form">
                                    <input type="hidden" name="action" value="add_class" id="class-form-action">
                                    <input type="hidden" name="class_id" id="form-class-id">

                                    <div class="form-group">
                                        <label for="f_class_name">Class Name</label>
                                        <input type="text" name="name" id="f_class_name" class="form-control" required placeholder="e.g. Traditional Malay Haute Pastry">
                                    </div>
                                    <div class="form-group">
                                        <label for="f_class_desc">Description</label>
                                        <textarea name="description" id="f_class_desc" class="form-control" rows="3" placeholder="Brief outline of syllabus..."></textarea>
                                    </div>
                                    <div class="form-grid">
                                        <div class="form-group">
                                            <label for="f_class_branch">Academy Branch</label>
                                            <select name="branch_id" id="f_class_branch" class="form-control" required>
                                                <option value="">Select Branch...</option>
                                                <?php foreach ($all_branches as $br): ?>
                                                    <option value="<?php echo $br['id']; ?>"><?php echo htmlspecialchars($br['name']); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label for="f_class_type">Cuisine Type</label>
                                            <select name="cuisine_type" id="f_class_type" class="form-control" required>
                                                <option value="Haute">Haute Cuisine</option>
                                                <option value="Vegetarian">Vegetarian Cuisine</option>
                                                <option value="Novella">Novella Cuisine</option>
                                                <option value="Fusion">Fusion Cuisine</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="form-grid">
                                        <div class="form-group">
                                            <label for="f_class_chef">Instructor / Chef</label>
                                            <select name="instructor_id" id="f_class_inst_id" class="form-control" onchange="syncInstructorName(this)">
                                                <option value="">— or type manually below —</option>
                                                <?php foreach ($all_instructors as $ins): ?>
                                                    <?php if ($ins['status'] === 'Active'): ?>
                                                    <option value="<?php echo $ins['id']; ?>" data-name="<?php echo htmlspecialchars($ins['name']); ?>">
                                                        <?php echo htmlspecialchars($ins['name']); ?> (<?php echo $ins['specialty']; ?>)
                                                    </option>
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label for="f_class_chef">Manual Chef Name <span class="text-dim" style="font-size:0.75rem;">(if not in roster)</span></label>
                                            <input type="text" name="instructor" id="f_class_chef" class="form-control" placeholder="e.g. Chef Marcus">
                                        </div>
                                    </div>
                                        <div class="form-group">
                                            <label for="f_class_sched">Schedule (Date & Time)</label>
                                            <input type="datetime-local" name="schedule" id="f_class_sched" class="form-control" required>
                                        </div>
                                    </div>
                                    <div class="form-grid">
                                        <div class="form-group">
                                            <label for="f_class_cap">Capacity (Seats)</label>
                                            <input type="number" name="capacity" id="f_class_cap" class="form-control" required min="1" value="12">
                                        </div>
                                        <div class="form-group">
                                            <label for="f_class_price">Price (RM)</label>
                                            <input type="number" name="price" id="f_class_price" class="form-control" required step="0.01" min="0" placeholder="0.00">
                                        </div>
                                    </div>

                                    <div class="flex-between mt-3" style="gap: 1rem;">
                                        <button type="button" class="btn btn-secondary" onclick="resetClassForm()" style="width: 35%;">Reset</button>
                                        <button type="submit" class="btn btn-primary" id="class-submit-btn" style="width: 65%;">Save Class</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- SUB-PANEL: BRANCHES -->
                    <div id="crud-branches" class="crud-content-panel">
                        <div class="grid-half">
                            <!-- Left: List -->
                            <div class="table-responsive">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Branch Detail</th>
                                            <th>City / Phone</th>
                                            <th class="text-right">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($all_branches as $ab): ?>
                                            <tr>
                                                <td>
                                                    <div style="font-weight:600; color:#ffffff;"><?php echo htmlspecialchars($ab['name']); ?></div>
                                                    <span class="text-dim" style="font-size:0.8rem;"><?php echo htmlspecialchars($ab['address']); ?></span>
                                                </td>
                                                <td style="font-size:0.85rem;">
                                                    <div><?php echo htmlspecialchars($ab['city']); ?></div>
                                                    <div class="text-dim" style="font-size:0.75rem;"><?php echo htmlspecialchars($ab['phone']); ?></div>
                                                </td>
                                                <td class="text-right">
                                                    <button class="btn btn-gold-outline btn-sm" style="margin-right: 0.25rem;" onclick="populateEditBranch(<?php echo htmlspecialchars(json_encode($ab)); ?>)">Edit</button>
                                                    <form method="POST" action="admin-dashboard.php" style="display:inline-block;" onsubmit="return confirm('Delete branch permanently? This cascades and removes all associated classes!');">
                                                        <input type="hidden" name="action" value="delete_branch">
                                                        <input type="hidden" name="branch_id" value="<?php echo $ab['id']; ?>">
                                                        <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Right: Form -->
                            <div class="card" style="padding: 1.5rem;">
                                <h3 id="branch-form-title" class="mb-3">Add New Academy Branch</h3>
                                <form method="POST" action="admin-dashboard.php" id="branch-form">
                                    <input type="hidden" name="action" value="add_branch" id="branch-form-action">
                                    <input type="hidden" name="branch_id" id="form-branch-id">

                                    <div class="form-group">
                                        <label for="f_branch_name">Branch Name</label>
                                        <input type="text" name="name" id="f_branch_name" class="form-control" required placeholder="e.g. Bangi Academy">
                                    </div>
                                    <div class="form-group">
                                        <label for="f_branch_addr">Address</label>
                                        <textarea name="address" id="f_branch_addr" class="form-control" rows="2" required placeholder="Full street address..."></textarea>
                                    </div>
                                    <div class="form-grid">
                                        <div class="form-group">
                                            <label for="f_branch_city">City</label>
                                            <input type="text" name="city" id="f_branch_city" class="form-control" required placeholder="e.g. Bangi">
                                        </div>
                                        <div class="form-group">
                                            <label for="f_branch_phone">Phone Number</label>
                                            <input type="text" name="phone" id="f_branch_phone" class="form-control" required placeholder="e.g. +60 3-8925 1111">
                                        </div>
                                    </div>

                                    <div class="flex-between mt-3" style="gap: 1rem;">
                                        <button type="button" class="btn btn-secondary" onclick="resetBranchForm()" style="width: 35%;">Reset</button>
                                        <button type="submit" class="btn btn-primary" id="branch-submit-btn" style="width: 65%;">Save Branch</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- SUB-PANEL: QUESTIONS -->
                    <div id="crud-questions" class="crud-content-panel">
                        <div class="grid-half">
                            <!-- Left: List -->
                            <div class="table-responsive">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Question Text</th>
                                            <th>Type</th>
                                            <th class="text-right">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($all_questions as $aq): ?>
                                            <tr>
                                                <td style="font-weight:600; color:#ffffff; font-size:0.9rem;"><?php echo htmlspecialchars($aq['question_text']); ?></td>
                                                <td>
                                                    <span class="badge badge-<?php echo $aq['question_type'] === 'rating' ? 'info' : 'warning'; ?>" style="font-size:0.7rem;">
                                                        <?php echo $aq['question_type']; ?>
                                                    </span>
                                                </td>
                                                <td class="text-right">
                                                    <button class="btn btn-gold-outline btn-sm" style="margin-right: 0.25rem;" onclick="populateEditQuestion(<?php echo htmlspecialchars(json_encode($aq)); ?>)">Edit</button>
                                                    <form method="POST" action="admin-dashboard.php" style="display:inline-block;" onsubmit="return confirm('Delete question item? This will remove all submitted survey feedback matching this question!');">
                                                        <input type="hidden" name="action" value="delete_question">
                                                        <input type="hidden" name="question_id" value="<?php echo $aq['id']; ?>">
                                                        <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Right: Form -->
                            <div class="card" style="padding: 1.5rem;">
                                <h3 id="question-form-title" class="mb-3">Add Survey Question</h3>
                                <form method="POST" action="admin-dashboard.php" id="question-form">
                                    <input type="hidden" name="action" value="add_question" id="question-form-action">
                                    <input type="hidden" name="question_id" id="form-question-id">

                                    <div class="form-group">
                                        <label for="f_q_text">Question Label</label>
                                        <textarea name="question_text" id="f_q_text" class="form-control" rows="3" required placeholder="e.g. How satisfied were you with the classroom setup?"></textarea>
                                    </div>
                                    <div class="form-group">
                                        <label for="f_q_type">Question Format</label>
                                        <select name="question_type" id="f_q_type" class="form-control">
                                            <option value="rating">Rating Scale (1-5)</option>
                                            <option value="text">Open-Ended Text Feedback</option>
                                        </select>
                                    </div>

                                    <div class="flex-between mt-3" style="gap: 1rem;">
                                        <button type="button" class="btn btn-secondary" onclick="resetQuestionForm()" style="width: 35%;">Reset</button>
                                        <button type="submit" class="btn btn-primary" id="question-submit-btn" style="width: 65%;">Save Question</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- SECTION 5: ANALYTICAL REPORTS -->
                <section id="section-reports" class="dashboard-section" style="display:none;">
                    <h2 class="mb-2">Analytical Insights & PDF Reports</h2>
                    <p class="text-dim mb-4" style="font-size:0.95rem;">Review visual analytics and generate printable PDF reports filtered by month and cuisine type.</p>

                    <!-- PDF Report Generator -->
                    <div class="card mb-4" style="padding:1.5rem; border-color: rgba(212,175,55,0.3);">
                        <h3 class="mb-3" style="color:var(--accent-gold-light);">📄 Generate PDF Report</h3>
                        <div style="display:flex; gap:1rem; flex-wrap:wrap; align-items:flex-end;">
                            <div class="filter-group">
                                <label>Report Month</label>
                                <select id="rpt_month" class="form-control" style="min-width:160px;">
                                    <option value="">All Months</option>
                                    <?php foreach ($report_months as $ym): ?>
                                        <option value="<?php echo $ym; ?>"><?php echo date('F Y', strtotime($ym . '-01')); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="filter-group">
                                <label>Cuisine Type</label>
                                <select id="rpt_cuisine" class="form-control" style="min-width:160px;">
                                    <option value="">All Cuisines</option>
                                    <option value="Haute">Haute Cuisine</option>
                                    <option value="Vegetarian">Vegetarian Cuisine</option>
                                    <option value="Fusion">Fusion Cuisine</option>
                                    <option value="Novella">Novella Cuisine</option>
                                </select>
                            </div>
                            <div class="filter-group">
                                <label>Booking Status</label>
                                <select id="rpt_status" class="form-control" style="min-width:140px;">
                                    <option value="">All Statuses</option>
                                    <option value="Confirmed">Confirmed</option>
                                    <option value="Cancelled">Cancelled</option>
                                </select>
                            </div>
                            <button class="btn btn-primary" onclick="generatePDFReport()" style="white-space:nowrap;">
                                📄 Generate & Print PDF
                            </button>
                        </div>
                        <p class="text-dim mt-2" style="font-size:0.8rem;">
                            Opens a print-ready report page in a new tab. Use your browser's Print dialog (Ctrl+P) and select "Save as PDF".
                        </p>
                    </div>

                    <div class="reports-grid">
                        <!-- Chart 1: Cuisine popularity -->
                        <div class="chart-card">
                            <div class="chart-header">
                                <h3 style="font-size:1.1rem; color:var(--text-primary);">Popularity of Cuisine Styles</h3>
                                <span class="text-dim" style="font-size:0.75rem;">Total confirmed bookings per cuisine category</span>
                            </div>
                            <div class="bar-chart-container">
                                <?php 
                                // Calculate max popularity bookings to scale heights
                                $max_pop = 1;
                                foreach ($cuisine_popularity as $cp) {
                                    if ($cp['booking_count'] > $max_pop) $max_pop = $cp['booking_count'];
                                }
                                ?>
                                <?php foreach ($cuisine_popularity as $cp): ?>
                                    <?php 
                                        $height_pct = round(($cp['booking_count'] / $max_pop) * 100); 
                                        if ($height_pct < 10) $height_pct = 10; // min visibility
                                    ?>
                                    <div class="chart-bar-wrap">
                                        <div class="chart-bar" style="height: <?php echo $height_pct; ?>%;">
                                            <span class="chart-bar-value"><?php echo $cp['booking_count']; ?></span>
                                        </div>
                                        <span class="chart-bar-label"><?php echo htmlspecialchars($cp['cuisine_type']); ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Chart 2: Branch Bookings Comparison -->
                        <div class="chart-card">
                            <div class="chart-header">
                                <h3 style="font-size:1.1rem; color:var(--text-primary);">Branch Attendance Stats</h3>
                                <span class="text-dim" style="font-size:0.75rem;">Number of registrations by academy location</span>
                            </div>
                            <div class="bar-chart-container">
                                <?php 
                                $max_branch_bk = 1;
                                foreach ($branch_stats as $bs) {
                                    if ($bs['active_bookings'] > $max_branch_bk) $max_branch_bk = $bs['active_bookings'];
                                }
                                ?>
                                <?php foreach ($branch_stats as $bs): ?>
                                    <?php 
                                        $height_pct = round(($bs['active_bookings'] / $max_branch_bk) * 100);
                                        if ($height_pct < 10) $height_pct = 10;
                                    ?>
                                    <div class="chart-bar-wrap">
                                        <div class="chart-bar" style="height: <?php echo $height_pct; ?>%; background: linear-gradient(to top, #3b82f6, #60a5fa);">
                                            <span class="chart-bar-value"><?php echo $bs['active_bookings']; ?></span>
                                        </div>
                                        <span class="chart-bar-label" title="<?php echo htmlspecialchars($bs['name']); ?>">
                                            <?php echo htmlspecialchars(explode(' ', $bs['name'])[0]); ?>
                                        </span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Chart 3: Satisfaction Ratings per branch -->
                    <div class="card" style="margin-top:2rem;">
                        <h3 class="mb-2">Average Branch Customer Review Metrics</h3>
                        <p class="text-dim mb-4" style="font-size:0.85rem;">Comparison of customer survey scores (1.0 - 5.0 scale) per location.</p>
                        
                        <div class="bar-chart-container" style="height: 180px;">
                            <?php foreach ($branch_stats as $bs): ?>
                                <?php 
                                    $score = $bs['avg_score'] ? round((float)$bs['avg_score'], 2) : 0.00;
                                    // Scale score out of 5.0
                                    $height_pct = round(($score / 5.0) * 100);
                                    if ($height_pct < 10 && $score > 0) $height_pct = 10;
                                ?>
                                <div class="chart-bar-wrap">
                                    <div class="chart-bar" style="height: <?php echo $height_pct; ?>%; background: linear-gradient(to top, #10b981, #34d399); width:50px;">
                                        <span class="chart-bar-value" style="top:-24px; font-size:0.8rem;"><?php echo $score > 0 ? number_format($score, 2) : 'No reviews'; ?></span>
                                    </div>
                                    <span class="chart-bar-label" style="max-width:120px; font-weight:600;"><?php echo htmlspecialchars($bs['name']); ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </section>

            </div>
        </div>
    </div>

    <footer>
        <div class="footer-container">
            <p>&copy; 2026 Authentic Cuisine Class Academy. All rights reserved.</p>
            <p>Malaysian Culinary Excellence Branches: Bangi | Klang | Damansara | Putrajaya | Shah Alam | Kuala Lumpur</p>
        </div>
    </footer>

    <script>
    // Tab/Section switching
    function switchSection(sectionId, element) {
        document.querySelectorAll('.dashboard-section').forEach(sec => {
            sec.style.display = 'none';
        });
        document.querySelectorAll('.sidebar-nav-link').forEach(link => {
            link.classList.remove('active');
        });
        document.getElementById('section-' + sectionId).style.display = 'block';
        element.classList.add('active');
        
        window.location.hash = sectionId;
    }

    // CRUD sub-tabs switching
    function switchCrudTab(tabId, element) {
        document.querySelectorAll('.crud-content-panel').forEach(panel => {
            panel.classList.remove('active');
        });
        document.querySelectorAll('.crud-tab-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        
        document.getElementById('crud-' + tabId).classList.add('active');
        element.classList.add('active');
    }

    // --- POPULATE FORMS FOR EDIT ---
    function populateEditClass(data) {
        document.getElementById('class-form-action').value = 'edit_class';
        document.getElementById('form-class-id').value = data.id;
        document.getElementById('class-form-title').textContent = 'Edit Cuisine Class (' + data.id + ')';
        document.getElementById('class-submit-btn').textContent = 'Update Class';
        document.getElementById('class-submit-btn').className = 'btn btn-gold-outline';

        document.getElementById('f_class_name').value = data.name;
        document.getElementById('f_class_desc').value = data.description;
        document.getElementById('f_class_branch').value = data.branch_id;
        document.getElementById('f_class_type').value = data.cuisine_type;
        document.getElementById('f_class_chef').value = data.instructor;
        
        // Set instructor dropdown if linked
        const instSel = document.getElementById('f_class_inst_id');
        if (instSel) instSel.value = data.instructor_id || '';
        
        // Convert datetime string (YYYY-MM-DD HH:MM:SS) to YYYY-MM-DDTHH:MM local format
        const dateObj = new Date(data.schedule);
        const tzOffset = dateObj.getTimezoneOffset() * 60000; // offset in milliseconds
        const localISOTime = (new Date(dateObj - tzOffset)).toISOString().slice(0, 16);
        document.getElementById('f_class_sched').value = localISOTime;

        document.getElementById('f_class_cap').value = data.capacity;
        document.getElementById('f_class_price').value = data.price;

        document.getElementById('f_class_name').focus();
    }

    function syncInstructorName(sel) {
        const opt = sel.options[sel.selectedIndex];
        const manualField = document.getElementById('f_class_chef');
        if (opt && opt.dataset.name) {
            manualField.value = opt.dataset.name;
            manualField.required = false;
        } else {
            manualField.required = true;
        }
    }

    function resetClassForm() {
        document.getElementById('class-form-action').value = 'add_class';
        document.getElementById('form-class-id').value = '';
        document.getElementById('class-form-title').textContent = 'Add New Cuisine Class';
        document.getElementById('class-submit-btn').textContent = 'Save Class';
        document.getElementById('class-submit-btn').className = 'btn btn-primary';
        document.getElementById('class-form').reset();
    }

    function populateEditBranch(data) {
        document.getElementById('branch-form-action').value = 'edit_branch';
        document.getElementById('form-branch-id').value = data.id;
        document.getElementById('branch-form-title').textContent = 'Edit Academy Branch (' + data.id + ')';
        document.getElementById('branch-submit-btn').textContent = 'Update Branch';
        document.getElementById('branch-submit-btn').className = 'btn btn-gold-outline';

        document.getElementById('f_branch_name').value = data.name;
        document.getElementById('f_branch_addr').value = data.address;
        document.getElementById('f_branch_city').value = data.city;
        document.getElementById('f_branch_phone').value = data.phone;

        document.getElementById('f_branch_name').focus();
    }

    function resetBranchForm() {
        document.getElementById('branch-form-action').value = 'add_branch';
        document.getElementById('form-branch-id').value = '';
        document.getElementById('branch-form-title').textContent = 'Add New Academy Branch';
        document.getElementById('branch-submit-btn').textContent = 'Save Branch';
        document.getElementById('branch-submit-btn').className = 'btn btn-primary';
        document.getElementById('branch-form').reset();
    }

    function populateEditQuestion(data) {
        document.getElementById('question-form-action').value = 'edit_question';
        document.getElementById('form-question-id').value = data.id;
        document.getElementById('question-form-title').textContent = 'Edit Survey Question (' + data.id + ')';
        document.getElementById('question-submit-btn').textContent = 'Update Question';
        document.getElementById('question-submit-btn').className = 'btn btn-gold-outline';

        document.getElementById('f_q_text').value = data.question_text;
        document.getElementById('f_q_type').value = data.question_type;

        document.getElementById('f_q_text').focus();
    }

    function resetQuestionForm() {
        document.getElementById('question-form-action').value = 'add_question';
        document.getElementById('form-question-id').value = '';
        document.getElementById('question-form-title').textContent = 'Add Survey Question';
        document.getElementById('question-submit-btn').textContent = 'Save Question';
        document.getElementById('question-submit-btn').className = 'btn btn-primary';
        document.getElementById('question-form').reset();
    }

    // --- INSTRUCTOR FORM FUNCTIONS ---
    function populateEditInstructor(data) {
        document.getElementById('inst-form-action').value = 'edit_instructor';
        document.getElementById('form-inst-id').value = data.id;
        document.getElementById('inst-form-title').textContent = 'Edit Instructor (' + data.id + ')';
        document.getElementById('inst-submit-btn').textContent = 'Update Instructor';
        document.getElementById('inst-submit-btn').className = 'btn btn-gold-outline';

        document.getElementById('f_inst_name').value = data.name;
        document.getElementById('f_inst_email').value = data.email;
        document.getElementById('f_inst_phone').value = data.phone;
        document.getElementById('f_inst_specialty').value = data.specialty;
        document.getElementById('f_inst_bio').value = data.bio || '';
        document.getElementById('f_inst_status').value = data.status;

        document.getElementById('f_inst_name').focus();
        document.getElementById('section-instructors').scrollIntoView({behavior:'smooth', block:'start'});
    }

    function resetInstructorForm() {
        document.getElementById('inst-form-action').value = 'add_instructor';
        document.getElementById('form-inst-id').value = '';
        document.getElementById('inst-form-title').textContent = 'Register New Instructor';
        document.getElementById('inst-submit-btn').textContent = 'Register Instructor';
        document.getElementById('inst-submit-btn').className = 'btn btn-primary';
        document.getElementById('inst-form').reset();
    }

    // --- PDF REPORT GENERATOR ---
    function generatePDFReport() {
        const month = document.getElementById('rpt_month').value;
        const cuisine = document.getElementById('rpt_cuisine').value;
        const status = document.getElementById('rpt_status').value;
        let url = 'report-pdf.php?';
        if (month) url += 'month=' + encodeURIComponent(month) + '&';
        if (cuisine) url += 'cuisine_type=' + encodeURIComponent(cuisine) + '&';
        if (status) url += 'status=' + encodeURIComponent(status);
        window.open(url, '_blank');
    }

    // Reload active tab on page refresh
    window.addEventListener('DOMContentLoaded', () => {
        const hash = window.location.hash.substring(1);
        if (hash) {
            // Check if hash matches an main section
            const targetNav = document.querySelector(`.sidebar-nav-link[onclick*='${hash}']`);
            if (targetNav) {
                targetNav.click();
            } else {
                // Check if hash matches a sub-CRUD panel
                const tabList = ['classes', 'branches', 'questions'];
                if (tabList.includes(hash)) {
                    document.querySelector('.sidebar-nav-link[onclick*="crud"]').click();
                    const subTab = document.querySelector(`.crud-tab-btn[onclick*="${hash}"]`);
                    if (subTab) subTab.click();
                }
            }
        }
    });
    </script>
</body>
</html>
