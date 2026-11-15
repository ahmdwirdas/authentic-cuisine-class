<?php
// customer-dashboard.php - Student Portal
require_once __DIR__ . '/auth.php';
require_login();
date_default_timezone_set('Asia/Kuala_Lumpur');
$customer_id = $_SESSION['customer_id'];
$customer_name = $_SESSION['customer_name'];

$message = '';
$message_type = 'success'; // success, danger, warning

// Handle Bookings Action
if (isset($_POST['action']) && $_POST['action'] === 'book') {
    $class_id = sanitize($_POST['class_id'] ?? '');
    
    // Validate Class
    $stmt = $pdo->prepare("SELECT c.*, b.name as branch_name FROM cuisineclasses c JOIN branches b ON c.branch_id = b.id WHERE c.id = ?");
    $stmt->execute([$class_id]);
    $class = $stmt->fetch();
    
    if (!$class) {
        $message = "Class not found.";
        $message_type = "danger";
    } else {
        // Check if class is in the past
        if (strtotime($class['schedule']) < time()) {
            $message = "You cannot book a class that has already occurred.";
            $message_type = "danger";
        } else {
            // Check if already booked
            $stmt = $pdo->prepare("SELECT id, status FROM bookings WHERE customer_id = ? AND class_id = ?");
            $stmt->execute([$customer_id, $class_id]);
            $existing = $stmt->fetch();
            
            if ($existing && $existing['status'] === 'Confirmed') {
                $message = "You have already booked this class.";
                $message_type = "warning";
            } else {
                // Check Capacity
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE class_id = ? AND status = 'Confirmed'");
                $stmt->execute([$class_id]);
                $booked_count = $stmt->fetchColumn();
                
                if ($booked_count >= $class['capacity']) {
                    $message = "Sorry, this class is fully booked.";
                    $message_type = "danger";
                } else {
                    if ($existing && $existing['status'] === 'Cancelled') {
                        // Re-confirm existing cancelled booking
                        $stmt = $pdo->prepare("UPDATE bookings SET status = 'Confirmed', booking_date = NOW() WHERE id = ?");
                        $stmt->execute([$existing['id']]);
                        $message = "Class booking successfully restored!";
                    } else {
                        // Create new booking
                        $booking_id = generate_id('BK');
                        $stmt = $pdo->prepare("INSERT INTO bookings (id, customer_id, class_id, status) VALUES (?, ?, ?, 'Confirmed')");
                        $stmt->execute([$booking_id, $customer_id, $class_id]);
                        $message = "Successfully booked class: " . htmlspecialchars($class['name']) . "!";
                    }
                    $message_type = "success";
                }
            }
        }
    }
}

// Handle Cancellations Action
if (isset($_POST['action']) && $_POST['action'] === 'cancel') {
    $booking_id = sanitize($_POST['booking_id'] ?? '');
    
    // Check ownership and class schedule
    $stmt = $pdo->prepare("SELECT b.*, c.schedule FROM bookings b JOIN cuisineclasses c ON b.class_id = c.id WHERE b.id = ? AND b.customer_id = ?");
    $stmt->execute([$booking_id, $customer_id]);
    $booking = $stmt->fetch();
    
    if (!$booking) {
        $message = "Booking details not found.";
        $message_type = "danger";
    } elseif (strtotime($booking['schedule']) < time()) {
        $message = "You cannot cancel a class that has already taken place.";
        $message_type = "danger";
    } else {
        $stmt = $pdo->prepare("UPDATE bookings SET status = 'Cancelled' WHERE id = ?");
        $stmt->execute([$booking_id]);
        $message = "Booking successfully cancelled.";
        $message_type = "success";
    }
}

// Handle Survey Submission Action
if (isset($_POST['action']) && $_POST['action'] === 'submit_survey') {
    $booking_id = sanitize($_POST['booking_id'] ?? '');
    $responses = $_POST['responses'] ?? []; // format: [question_id => response]

    // Verify booking matches customer and is in the past
    $stmt = $pdo->prepare("SELECT b.*, c.schedule FROM bookings b JOIN cuisineclasses c ON b.class_id = c.id WHERE b.id = ? AND b.customer_id = ? AND b.status = 'Confirmed'");
    $stmt->execute([$booking_id, $customer_id]);
    $booking = $stmt->fetch();

    if (!$booking || strtotime($booking['schedule']) > time()) {
        $message = "Invalid booking details for survey submission.";
        $message_type = "danger";
    } else {
        // Ensure no existing survey for this booking
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM surveys WHERE booking_id = ?");
        $stmt->execute([$booking_id]);
        if ($stmt->fetchColumn() > 0) {
            $message = "You have already completed feedback for this class.";
            $message_type = "warning";
        } else {
            // Write each question response
            $pdo->beginTransaction();
            try {
                $stmt = $pdo->prepare("INSERT INTO surveys (id, customer_id, booking_id, question_id, response) VALUES (?, ?, ?, ?, ?)");
                foreach ($responses as $q_id => $resp) {
                    $srv_id = generate_id('SRV');
                    $stmt->execute([$srv_id, $customer_id, $booking_id, sanitize($q_id), sanitize($resp)]);
                }
                $pdo->commit();
                $message = "Thank you! Your satisfaction feedback has been recorded.";
                $message_type = "success";
            } catch (PDOException $e) {
                $pdo->rollBack();
                $message = "Failed to submit survey answers: " . $e->getMessage();
                $message_type = "danger";
            }
        }
    }
}

// Handle Survey Edit Action
if (isset($_POST['action']) && $_POST['action'] === 'edit_survey') {
    $booking_id = sanitize($_POST['booking_id'] ?? '');
    $responses = $_POST['responses'] ?? [];

    $pdo->beginTransaction();
    try {
        // Update each response
        $stmt = $pdo->prepare("UPDATE surveys SET response = ?, submitted_at = NOW() WHERE booking_id = ? AND question_id = ? AND customer_id = ?");
        foreach ($responses as $q_id => $resp) {
            $stmt->execute([sanitize($resp), $booking_id, sanitize($q_id), $customer_id]);
        }
        $pdo->commit();
        $message = "Your survey response has been updated successfully.";
        $message_type = "success";
    } catch (PDOException $e) {
        $pdo->rollBack();
        $message = "Failed to update feedback: " . $e->getMessage();
        $message_type = "danger";
    }
}

// Handle Survey Deletion Action
if (isset($_POST['action']) && $_POST['action'] === 'delete_survey') {
    $booking_id = sanitize($_POST['booking_id'] ?? '');

    $stmt = $pdo->prepare("DELETE FROM surveys WHERE booking_id = ? AND customer_id = ?");
    if ($stmt->execute([$booking_id, $customer_id])) {
        $message = "Survey feedback successfully deleted.";
        $message_type = "success";
    } else {
        $message = "Failed to delete survey feedback.";
        $message_type = "danger";
    }
}

// Load static lists for filters
$branches = $pdo->query("SELECT * FROM branches ORDER BY name")->fetchAll();
$cuisine_types = ['Haute', 'Vegetarian', 'Novella', 'Fusion'];

// Apply Filters for Class Search
$filter_branch = sanitize($_GET['branch_id'] ?? '');
$filter_cuisine = sanitize($_GET['cuisine_type'] ?? '');

$sql = "SELECT c.*, b.name as branch_name, 
        (SELECT COUNT(*) FROM bookings WHERE class_id = c.id AND status = 'Confirmed') as booked_seats 
        FROM cuisineclasses c 
        JOIN branches b ON c.branch_id = b.id 
        WHERE c.schedule > NOW()";

$params = [];
if (!empty($filter_branch)) {
    $sql .= " AND c.branch_id = ?";
    $params[] = $filter_branch;
}
if (!empty($filter_cuisine)) {
    $sql .= " AND c.cuisine_type = ?";
    $params[] = $filter_cuisine;
}
$sql .= " ORDER BY c.schedule ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$available_classes = $stmt->fetchAll();

// Load Customer Bookings (all)
$stmt = $pdo->prepare("SELECT b.id as booking_id, b.booking_date, b.status, b.payment_status, c.id as class_id, c.name as class_name, c.cuisine_type, c.instructor, c.schedule, c.price, br.name as branch_name 
                       FROM bookings b 
                       JOIN cuisineclasses c ON b.class_id = c.id 
                       JOIN branches br ON c.branch_id = br.id 
                       WHERE b.customer_id = ? 
                       ORDER BY c.schedule DESC");
$stmt->execute([$customer_id]);
$my_bookings = $stmt->fetchAll();

// Split bookings into active future ones and completed past ones
$active_bookings = [];
$completed_bookings = [];
foreach ($my_bookings as $bk) {
    if (strtotime($bk['schedule']) >= time()) {
        $active_bookings[] = $bk;
    } else {
        $completed_bookings[] = $bk;
    }
}

// Check which past bookings have surveys submitted
$surveyed_booking_ids = [];
if (!empty($completed_bookings)) {
    $b_ids = array_column($completed_bookings, 'booking_id');
    $placeholders = str_repeat('?,', count($b_ids) - 1) . '?';
    $stmt = $pdo->prepare("SELECT DISTINCT booking_id FROM surveys WHERE booking_id IN ($placeholders) AND customer_id = ?");
    $stmt->execute(array_merge($b_ids, [$customer_id]));
    $surveyed_booking_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
}

// Load Survey Questions for rendering dynamic forms
$questions = $pdo->query("SELECT * FROM questions ORDER BY id")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - Authentic Cuisine Class</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .survey-q-box {
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-sm);
            padding: 1.25rem;
            margin-bottom: 1.25rem;
        }
        .survey-q-text {
            font-size: 0.95rem;
            font-weight: 600;
            color: #ffffff;
            margin-bottom: 0.75rem;
        }
        .rating-labels {
            display: flex;
            gap: 1.5rem;
        }
    </style>
</head>
<body>

    <header>
        <div class="nav-container">
            <a href="index.php" class="logo">Authentic <span>Cuisine</span></a>
            <ul class="nav-links">
                <li><a href="index.php">Home</a></li>
                <li><a href="customer-dashboard.php" class="active">Dashboard</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </div>
    </header>

    <div class="container">
        <div class="flex-between mb-4">
            <div>
                <span class="section-subtitle">Student Portal</span>
                <h1 style="font-size: 2.25rem;">Welcome back, <?php echo htmlspecialchars($customer_name); ?></h1>
            </div>
            <a href="logout.php" class="btn btn-secondary btn-sm">Sign Out</a>
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
                <div class="card" style="padding: 1.5rem; width: 100%;">
                    <div class="stat-label" style="margin-bottom: 0.5rem;">Account Email</div>
                    <div style="font-weight: 600; font-size: 0.95rem; word-break: break-all; margin-bottom: 1.5rem;">
                        <?php echo htmlspecialchars($_SESSION['customer_email']); ?>
                    </div>
                    <ul class="sidebar-nav">
                        <li class="sidebar-nav-link active" onclick="switchSection('catalogue', this)">
                            <span>🍽️</span> Class Catalogue
                        </li>
                        <li class="sidebar-nav-link" onclick="switchSection('bookings', this)">
                            <span>📅</span> My Schedule
                        </li>
                        <li class="sidebar-nav-link" onclick="switchSection('surveys', this)">
                            <span>✍️</span> Feedback Center
                        </li>
                        <li class="sidebar-nav-link" style="margin-top:0.5rem;">
                            <a href="payment.php" style="display:flex; align-items:center; gap:0.5rem; color:inherit; text-decoration:none; width:100%;">
                                <span>💳</span> Make a Payment
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Content Area -->
            <div class="content-area">
                
                <!-- Section 1: Class Catalogue -->
                <section id="section-catalogue" class="dashboard-section">
                    <h2 class="mb-2">Cooking Class Catalogue</h2>
                    <p class="text-dim mb-4" style="font-size: 0.95rem;">Select and register for upcoming culinary classes across our Malaysia locations.</p>

                    <!-- Filter Bar -->
                    <form method="GET" action="customer-dashboard.php" class="filter-bar">
                        <div class="filter-group">
                            <label for="branch_id">Branch</label>
                            <select name="branch_id" id="branch_id" class="form-control" style="min-width: 200px;">
                                <option value="">All Branches</option>
                                <?php foreach ($branches as $br): ?>
                                    <option value="<?php echo $br['id']; ?>" <?php echo $filter_branch === $br['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($br['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label for="cuisine_type">Cuisine Style</label>
                            <select name="cuisine_type" id="cuisine_type" class="form-control" style="min-width: 150px;">
                                <option value="">All Cuisines</option>
                                <?php foreach ($cuisine_types as $c_t): ?>
                                    <option value="<?php echo $c_t; ?>" <?php echo $filter_cuisine === $c_t ? 'selected' : ''; ?>>
                                        <?php echo $c_t; ?> Cuisine
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-gold-outline" style="padding: 0.75rem 1.5rem;">Apply Filters</button>
                        <?php if (!empty($filter_branch) || !empty($filter_cuisine)): ?>
                            <a href="customer-dashboard.php" class="btn btn-secondary" style="padding: 0.75rem 1.5rem;">Clear</a>
                        <?php endif; ?>
                    </form>

                    <!-- Classes Grid -->
                    <?php if (empty($available_classes)): ?>
                        <div class="card text-center" style="padding: 4rem 2rem;">
                            <p class="text-dim">No matching culinary classes available for booking right now.</p>
                            <?php if (!empty($filter_branch) || !empty($filter_cuisine)): ?>
                                <p style="margin-top: 1rem;">
                                    <a href="customer-dashboard.php" class="btn btn-gold-outline btn-sm">Reset Filters</a>
                                </p>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="classes-grid">
                            <?php foreach ($available_classes as $cls): ?>
                                <?php 
                                    $available_seats = $cls['capacity'] - $cls['booked_seats']; 
                                    $is_booked_by_me = false;
                                    // Check if this class is in customer's current bookings
                                    foreach ($my_bookings as $my_bk) {
                                        if ($my_bk['class_id'] === $cls['id'] && $my_bk['status'] === 'Confirmed') {
                                            $is_booked_by_me = true;
                                            break;
                                        }
                                    }
                                ?>
                                <div class="class-card">
                                    <div>
                                        <div class="class-header">
                                            <span class="class-type"><?php echo htmlspecialchars($cls['cuisine_type']); ?></span>
                                            <span class="class-price">RM <?php echo number_format($cls['price'], 2); ?></span>
                                        </div>
                                        <h3 class="class-title"><?php echo htmlspecialchars($cls['name']); ?></h3>
                                        <p class="class-desc"><?php echo htmlspecialchars($cls['description']); ?></p>
                                    </div>
                                    <div>
                                        <div class="class-details">
                                            <span>📍 <?php echo htmlspecialchars($cls['branch_name']); ?></span>
                                            <span>👨‍🍳 <?php echo htmlspecialchars($cls['instructor']); ?></span>
                                            <span>📅 <?php echo date('d M Y, h:i A', strtotime($cls['schedule'])); ?></span>
                                            <span style="font-weight: 600; color: <?php echo $available_seats > 0 ? 'var(--success)' : 'var(--danger)'; ?>">
                                                🪑 <?php echo $available_seats > 0 ? "$available_seats seats left" : "Fully Booked"; ?>
                                            </span>
                                        </div>
                                        
                                        <?php if ($is_booked_by_me): ?>
                                            <button type="button" class="btn btn-secondary" style="width: 100%;" disabled>Already Booked</button>
                                        <?php elseif ($available_seats <= 0): ?>
                                            <button type="button" class="btn btn-secondary" style="width: 100%;" disabled>Fully Booked</button>
                                        <?php else: ?>
                                            <form method="POST" action="customer-dashboard.php" onsubmit="return confirm('Confirm booking for: <?php echo addslashes($cls['name']); ?>?');">
                                                <input type="hidden" name="action" value="book">
                                                <input type="hidden" name="class_id" value="<?php echo $cls['id']; ?>">
                                                <button type="submit" class="btn btn-primary" style="width: 100%;">Book Class</button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </section>

                <!-- Section 2: My Bookings -->
                <section id="section-bookings" class="dashboard-section" style="display: none;">
                    <h2 class="mb-2">My Cooking Schedule</h2>
                    <p class="text-dim mb-4" style="font-size: 0.95rem;">Track, view, or cancel registrations for your upcoming classes.</p>

                    <?php if (empty($active_bookings)): ?>
                        <div class="card text-center" style="padding: 4rem 2rem;">
                            <p class="text-dim">You don't have any upcoming culinary classes scheduled.</p>
                            <button class="btn btn-gold-outline mt-4" onclick="document.querySelector('.sidebar-nav-link[onclick*=\'catalogue\']').click()">Browse Catalog</button>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Class Detail</th>
                                        <th>Branch & Instructor</th>
                                        <th>Date & Time</th>
                                        <th>Price</th>
                                        <th>Status</th>
                                        <th>Payment</th>
                                        <th class="text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($active_bookings as $bk): ?>
                                        <tr>
                                            <td>
                                                <div style="font-weight: 600; color: #ffffff;"><?php echo htmlspecialchars($bk['class_name']); ?></div>
                                                <span class="class-type" style="font-size: 0.7rem; padding: 0.1rem 0.4rem;"><?php echo htmlspecialchars($bk['cuisine_type']); ?></span>
                                            </td>
                                            <td>
                                                <div style="font-size: 0.9rem;"><?php echo htmlspecialchars($bk['branch_name']); ?></div>
                                                <div class="text-dim" style="font-size: 0.8rem;">Chef: <?php echo htmlspecialchars($bk['instructor']); ?></div>
                                            </td>
                                            <td style="font-size: 0.9rem;">
                                                <?php echo date('d M Y', strtotime($bk['schedule'])); ?><br>
                                                <span class="text-dim"><?php echo date('h:i A', strtotime($bk['schedule'])); ?></span>
                                            </td>
                                            <td>RM <?php echo number_format($bk['price'], 2); ?></td>
                                            <td>
                                                <span class="badge badge-<?php echo $bk['status'] === 'Confirmed' ? 'success' : 'danger'; ?>">
                                                    <?php echo $bk['status']; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php $pStatus = $bk['payment_status'] ?? 'Unpaid'; ?>
                                                <span class="badge badge-<?php echo $pStatus === 'Paid' ? 'success' : 'warning'; ?>" style="font-size:0.7rem;">
                                                    <?php echo $pStatus; ?>
                                                </span>
                                            </td>
                                            <td class="text-right" style="white-space:nowrap;">
                                                <?php if ($bk['status'] === 'Confirmed'): ?>
                                                    <?php if (($bk['payment_status'] ?? 'Unpaid') === 'Unpaid'): ?>
                                                        <a href="payment.php?booking_id=<?php echo urlencode($bk['booking_id']); ?>"
                                                           class="btn btn-primary btn-sm" style="margin-right:0.25rem;">💳 Pay</a>
                                                    <?php endif; ?>
                                                    <form method="POST" action="customer-dashboard.php" style="display:inline-block;" onsubmit="return confirm('Are you sure you want to cancel booking for: <?php echo addslashes($bk['class_name']); ?>?');">
                                                        <input type="hidden" name="action" value="cancel">
                                                        <input type="hidden" name="booking_id" value="<?php echo $bk['booking_id']; ?>">
                                                        <button type="submit" class="btn btn-danger btn-sm">Cancel</button>
                                                    </form>
                                                <?php else: ?>
                                                    <span class="text-dim" style="font-size: 0.85rem;">No actions</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </section>

                <!-- Section 3: Feedback Center -->
                <section id="section-surveys" class="dashboard-section" style="display: none;">
                    <h2 class="mb-2">Feedback Center</h2>
                    <p class="text-dim mb-4" style="font-size: 0.95rem;">Review your completed sessions. Help us maintain quality by sharing satisfaction reports.</p>

                    <?php if (empty($completed_bookings)): ?>
                        <div class="card text-center" style="padding: 4rem 2rem;">
                            <p class="text-dim">You don't have any past completed classes yet.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Class Detail</th>
                                        <th>Branch</th>
                                        <th>Date & Time</th>
                                        <th>Booking Status</th>
                                        <th>Satisfaction Survey</th>
                                        <th class="text-right">Feedback Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($completed_bookings as $bk): ?>
                                        <?php 
                                            $has_survey = in_array($bk['booking_id'], $surveyed_booking_ids); 
                                        ?>
                                        <tr>
                                            <td>
                                                <div style="font-weight: 600; color: #ffffff;"><?php echo htmlspecialchars($bk['class_name']); ?></div>
                                                <div class="text-dim" style="font-size: 0.8rem;">Chef: <?php echo htmlspecialchars($bk['instructor']); ?></div>
                                            </td>
                                            <td style="font-size: 0.9rem;"><?php echo htmlspecialchars($bk['branch_name']); ?></td>
                                            <td style="font-size: 0.9rem;">
                                                <?php echo date('d M Y', strtotime($bk['schedule'])); ?>
                                            </td>
                                            <td>
                                                <span class="badge badge-<?php echo $bk['status'] === 'Confirmed' ? 'success' : 'danger'; ?>">
                                                    <?php echo $bk['status']; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($bk['status'] === 'Cancelled'): ?>
                                                    <span class="text-dim" style="font-size: 0.85rem;">N/A (Cancelled)</span>
                                                <?php elseif ($has_survey): ?>
                                                    <span class="badge badge-success">Submitted</span>
                                                <?php else: ?>
                                                    <span class="badge badge-warning">Pending</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-right">
                                                <?php if ($bk['status'] === 'Confirmed'): ?>
                                                    <?php if (!$has_survey): ?>
                                                        <button class="btn btn-primary btn-sm" onclick="openSurveyModal('<?php echo $bk['booking_id']; ?>', '<?php echo htmlspecialchars(addslashes($bk['class_name'])); ?>')">
                                                            Fill Survey
                                                        </button>
                                                    <?php else: ?>
                                                        <button class="btn btn-gold-outline btn-sm" style="margin-right: 0.25rem;" onclick="openEditSurveyModal('<?php echo $bk['booking_id']; ?>', '<?php echo htmlspecialchars(addslashes($bk['class_name'])); ?>')">
                                                            Edit
                                                        </button>
                                                        <form method="POST" action="customer-dashboard.php" style="display:inline-block;" onsubmit="return confirm('Are you sure you want to permanently delete your feedback responses for this class?');">
                                                            <input type="hidden" name="action" value="delete_survey">
                                                            <input type="hidden" name="booking_id" value="<?php echo $bk['booking_id']; ?>">
                                                            <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                                        </form>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <span class="text-dim" style="font-size: 0.85rem;">No actions</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </section>

            </div>
        </div>
    </div>

    <!-- Survey Submission Modal -->
    <div id="surveyModal" class="modal-backdrop" style="display: none;">
        <div class="modal-content">
            <button class="modal-close" onclick="closeSurveyModal()">&times;</button>
            <span class="section-subtitle">Customer Satisfaction Survey</span>
            <h2 id="modal-class-title" style="margin-bottom: 1.5rem; font-size: 1.5rem;">Class Name</h2>
            
            <form method="POST" action="customer-dashboard.php">
                <input type="hidden" name="action" value="submit_survey">
                <input type="hidden" name="booking_id" id="modal-booking-id">

                <?php foreach ($questions as $q): ?>
                    <div class="survey-q-box">
                        <div class="survey-q-text"><?php echo htmlspecialchars($q['question_text']); ?></div>
                        
                        <?php if ($q['question_type'] === 'rating'): ?>
                            <div class="rating-labels">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <label class="rating-label">
                                        <input type="radio" name="responses[<?php echo $q['id']; ?>]" value="<?php echo $i; ?>" required>
                                        <span><?php echo $i; ?></span>
                                    </label>
                                <?php endfor; ?>
                            </div>
                        <?php else: ?>
                            <textarea name="responses[<?php echo $q['id']; ?>]" class="form-control" rows="3" placeholder="Write your comments here..."></textarea>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>

                <button type="submit" class="btn btn-primary" style="width: 100%;">Submit Feedback Report</button>
            </form>
        </div>
    </div>

    <!-- Survey Edit Modal -->
    <div id="editSurveyModal" class="modal-backdrop" style="display: none;">
        <div class="modal-content">
            <button class="modal-close" onclick="closeEditSurveyModal()">&times;</button>
            <span class="section-subtitle">Modify Satisfaction Survey</span>
            <h2 id="edit-modal-class-title" style="margin-bottom: 1.5rem; font-size: 1.5rem;">Class Name</h2>
            
            <form method="POST" action="customer-dashboard.php" id="editSurveyForm">
                <input type="hidden" name="action" value="edit_survey">
                <input type="hidden" name="booking_id" id="edit-modal-booking-id">

                <?php foreach ($questions as $q): ?>
                    <div class="survey-q-box">
                        <div class="survey-q-text"><?php echo htmlspecialchars($q['question_text']); ?></div>
                        
                        <?php if ($q['question_type'] === 'rating'): ?>
                            <div class="rating-labels">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <label class="rating-label">
                                        <input type="radio" name="responses[<?php echo $q['id']; ?>]" value="<?php echo $i; ?>" id="edit-q-<?php echo $q['id']; ?>-<?php echo $i; ?>" required>
                                        <span><?php echo $i; ?></span>
                                    </label>
                                <?php endfor; ?>
                            </div>
                        <?php else: ?>
                            <textarea name="responses[<?php echo $q['id']; ?>]" id="edit-q-text-<?php echo $q['id']; ?>" class="form-control" rows="3" placeholder="Write your comments here..."></textarea>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>

                <button type="submit" class="btn btn-primary" style="width: 100%;">Update Feedback Report</button>
            </form>
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
        // Hide all sections
        document.querySelectorAll('.dashboard-section').forEach(sec => {
            sec.style.display = 'none';
        });
        
        // Remove active class from nav
        document.querySelectorAll('.sidebar-nav-link').forEach(link => {
            link.classList.remove('active');
        });
        
        // Show selected section & make nav active
        document.getElementById('section-' + sectionId).style.display = 'block';
        element.classList.add('active');

        // Store active section in URL hash for reload persistence
        window.location.hash = sectionId;
    }

    // Modal Helpers
    function openSurveyModal(bookingId, className) {
        document.getElementById('modal-booking-id').value = bookingId;
        document.getElementById('modal-class-title').textContent = className;
        document.getElementById('surveyModal').style.display = 'flex';
    }

    function closeSurveyModal() {
        document.getElementById('surveyModal').style.display = 'none';
    }

    function openEditSurveyModal(bookingId, className) {
        document.getElementById('edit-modal-booking-id').value = bookingId;
        document.getElementById('edit-modal-class-title').textContent = className;

        // Fetch current responses from server asynchronously to fill the edit form
        fetch('get_survey_responses.php?booking_id=' + bookingId)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Clear previous settings
                    document.getElementById('editSurveyForm').reset();
                    
                    data.responses.forEach(item => {
                        const qType = item.question_type;
                        const qId = item.question_id;
                        const resp = item.response;

                        if (qType === 'rating') {
                            const radioBtn = document.getElementById(`edit-q-${qId}-${resp}`);
                            if (radioBtn) {
                                radioBtn.checked = true;
                            }
                        } else {
                            const textInput = document.getElementById(`edit-q-text-${qId}`);
                            if (textInput) {
                                textInput.value = resp;
                            }
                        }
                    });
                    document.getElementById('editSurveyModal').style.display = 'flex';
                } else {
                    alert("Error loading survey answers: " + data.message);
                }
            })
            .catch(error => {
                console.error("Error loading responses:", error);
                alert("Could not load current survey responses.");
            });
    }

    function closeEditSurveyModal() {
        document.getElementById('editSurveyModal').style.display = 'none';
    }

    // Reload active tab based on URL hash
    window.addEventListener('DOMContentLoaded', () => {
        const hash = window.location.hash.substring(1);
        if (hash) {
            const targetNav = document.querySelector(`.sidebar-nav-link[onclick*='${hash}']`);
            if (targetNav) {
                targetNav.click();
            }
        }
    });
    </script>
</body>
</html>
