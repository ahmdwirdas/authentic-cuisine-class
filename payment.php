<?php
// payment.php - Customer Payment Page
require_once __DIR__ . '/auth.php';
require_customer();
date_default_timezone_set('Asia/Kuala_Lumpur');

$customer_id = $_SESSION['customer_id'];
$message = '';
$message_type = 'success';
$booking = null;
$already_paid = false;

// --- Handle Payment Submission ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'make_payment') {
    $booking_id      = sanitize($_POST['booking_id'] ?? '');
    $payment_method  = sanitize($_POST['payment_method'] ?? '');
    $bank_name       = sanitize($_POST['bank_name'] ?? '');
    $card_last4      = sanitize($_POST['card_last4'] ?? '');

    if (empty($booking_id) || empty($payment_method)) {
        $message = "Please select a valid booking and payment method.";
        $message_type = "danger";
    } else {
        // Verify booking belongs to this customer and is Confirmed + Unpaid
        $bStmt = $pdo->prepare("
            SELECT b.*, cl.name as class_name, cl.price, cl.schedule, cl.cuisine_type,
                   br.name as branch_name, br.city
            FROM bookings b
            JOIN cuisineclasses cl ON b.class_id = cl.id
            JOIN branches br ON cl.branch_id = br.id
            WHERE b.id = ? AND b.customer_id = ? AND b.status = 'Confirmed' AND b.payment_status = 'Unpaid'
        ");
        $bStmt->execute([$booking_id, $customer_id]);
        $payBk = $bStmt->fetch();

        if (!$payBk) {
            $message = "Invalid booking or booking is already paid / cancelled.";
            $message_type = "danger";
        } else {
            // Check no existing payment
            $chk = $pdo->prepare("SELECT id FROM payments WHERE booking_id = ?");
            $chk->execute([$booking_id]);
            if ($chk->fetch()) {
                // Already has payment record — just update booking
                $pdo->prepare("UPDATE bookings SET payment_status = 'Paid' WHERE id = ?")->execute([$booking_id]);
            } else {
                $payId  = generate_id('PAY');
                $txRef  = 'TXN-' . strtoupper(substr(md5(uniqid()), 0, 8));
                $method = $payment_method;
                if ($payment_method === 'fpx' && !empty($bank_name))  $method = 'FPX / ' . $bank_name;
                if ($payment_method === 'card' && !empty($card_last4)) $method = 'Card (•••• ' . $card_last4 . ')';
                if ($payment_method === 'ewallet') $method = 'Touch n Go eWallet';

                $pdo->prepare("INSERT INTO payments (id, booking_id, customer_id, amount, payment_method, status, transaction_ref, paid_at)
                               VALUES (?, ?, ?, ?, ?, 'Paid', ?, NOW())")
                    ->execute([$payId, $booking_id, $customer_id, $payBk['price'], $method, $txRef]);

                $pdo->prepare("UPDATE bookings SET payment_status = 'Paid' WHERE id = ?")->execute([$booking_id]);
            }
            $message = "Payment successful! Your booking has been confirmed and paid. ✅";
            header("Location: payment.php?booking_id=" . urlencode($booking_id) . "&paid=1");
            exit;
        }
    }
}

// --- Fetch booking details ---
$booking_id = sanitize($_GET['booking_id'] ?? '');
$just_paid  = isset($_GET['paid']) && $_GET['paid'] === '1';

if (!empty($booking_id)) {
    $bStmt = $pdo->prepare("
        SELECT b.*, cl.name as class_name, cl.price, cl.schedule, cl.cuisine_type,
               cl.instructor, cl.description,
               br.name as branch_name, br.city, br.address,
               c.name as customer_name, c.email as customer_email
        FROM bookings b
        JOIN cuisineclasses cl ON b.class_id = cl.id
        JOIN branches br ON cl.branch_id = br.id
        JOIN customers c ON b.customer_id = c.id
        WHERE b.id = ? AND b.customer_id = ?
    ");
    $bStmt->execute([$booking_id, $customer_id]);
    $booking = $bStmt->fetch();

    if ($booking && $booking['payment_status'] === 'Paid') {
        $already_paid = true;
        // Fetch payment record
        $payRec = $pdo->prepare("SELECT * FROM payments WHERE booking_id = ? ORDER BY paid_at DESC LIMIT 1");
        $payRec->execute([$booking_id]);
        $payment_record = $payRec->fetch();
    }
}

// Fetch unpaid confirmed bookings for this customer (for quick-select if no booking_id)
$unpaid_bookings = $pdo->prepare("
    SELECT b.id, b.booking_date, cl.name as class_name, cl.price, cl.schedule, br.name as branch_name
    FROM bookings b
    JOIN cuisineclasses cl ON b.class_id = cl.id
    JOIN branches br ON cl.branch_id = br.id
    WHERE b.customer_id = ? AND b.status = 'Confirmed' AND b.payment_status = 'Unpaid'
    ORDER BY b.booking_date DESC
");
$unpaid_bookings->execute([$customer_id]);
$unpaid_list = $unpaid_bookings->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Make Payment - Authentic Cuisine Class</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .payment-card {
            max-width: 680px;
            margin: 0 auto;
        }
        .booking-summary {
            background: rgba(255,255,255,0.03);
            border: 1px solid var(--border-color);
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem 0;
            border-bottom: 1px solid rgba(255,255,255,0.05);
            font-size: 0.9rem;
        }
        .summary-row:last-child { border-bottom: none; }
        .summary-row .label { color: var(--text-secondary); }
        .summary-row .value { color: #fff; font-weight: 600; }
        .total-row { font-size: 1.1rem !important; }
        .total-row .value { color: var(--accent-gold-light) !important; font-size: 1.3rem; }
        .method-options {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        .method-option {
            border: 2px solid var(--border-color);
            border-radius: 10px;
            padding: 1.25rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
        }
        .method-option:hover, .method-option.selected {
            border-color: var(--accent-gold);
            background: rgba(212,175,55,0.08);
        }
        .method-option .icon { font-size: 2rem; margin-bottom: 0.5rem; display: block; }
        .method-option .title { font-weight: 600; font-size: 0.9rem; color: #fff; }
        .method-option .sub { font-size: 0.75rem; color: var(--text-secondary); }
        .receipt-box {
            background: rgba(16,185,129,0.06);
            border: 1px solid rgba(16,185,129,0.3);
            border-radius: 10px;
            padding: 2rem;
            text-align: center;
        }
        .receipt-id {
            font-family: monospace;
            font-size: 1.1rem;
            color: var(--accent-gold-light);
            background: rgba(212,175,55,0.1);
            padding: 0.5rem 1rem;
            border-radius: 6px;
            display: inline-block;
            margin: 0.5rem 0;
        }
        @media (max-width: 600px) {
            .method-options { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <header>
        <div class="nav-container">
            <a href="index.php" class="logo">Authentic <span>Cuisine</span></a>
            <ul class="nav-links">
                <li><a href="index.php">Home</a></li>
                <li><a href="customer-dashboard.php">My Dashboard</a></li>
                <li><a href="payment.php" class="active">Payment</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </div>
    </header>

    <div class="container" style="padding: 3rem 1rem;">
        <div class="payment-card">
            <span class="section-subtitle">Secure Checkout</span>
            <h1 style="font-size:2rem; margin-bottom:0.25rem;">Class Payment</h1>
            <p class="text-dim mb-4">Complete your payment to confirm your enrollment in the cuisine class.</p>

            <?php if (!empty($message)): ?>
                <div class="alert alert-<?php echo $message_type; ?>">
                    <span><?php echo $message_type === 'success' ? '✅' : '⚠️'; ?></span>
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <?php if ($just_paid && $already_paid && !empty($payment_record ?? null)): ?>
                <!-- SUCCESS RECEIPT -->
                <div class="receipt-box">
                    <div style="font-size:3rem; margin-bottom:1rem;">🎉</div>
                    <h2 style="color:#10b981; margin-bottom:0.5rem;">Payment Successful!</h2>
                    <p class="text-dim mb-3">Your enrollment has been confirmed. See you in class!</p>
                    <div class="receipt-id"><?php echo htmlspecialchars($payment_record['id']); ?></div>
                    <div class="text-dim" style="font-size:0.8rem; margin-bottom:1.5rem;">
                        Ref: <?php echo htmlspecialchars($payment_record['transaction_ref']); ?><br>
                        <?php echo date('d M Y, h:i A', strtotime($payment_record['paid_at'])); ?>
                    </div>
                    <div class="booking-summary" style="text-align:left; margin-bottom:1.5rem;">
                        <div class="summary-row">
                            <span class="label">Class</span>
                            <span class="value"><?php echo htmlspecialchars($booking['class_name']); ?></span>
                        </div>
                        <div class="summary-row">
                            <span class="label">Branch</span>
                            <span class="value"><?php echo htmlspecialchars($booking['branch_name']); ?></span>
                        </div>
                        <div class="summary-row">
                            <span class="label">Schedule</span>
                            <span class="value"><?php echo date('d M Y, h:i A', strtotime($booking['schedule'])); ?></span>
                        </div>
                        <div class="summary-row">
                            <span class="label">Payment Method</span>
                            <span class="value"><?php echo htmlspecialchars($payment_record['payment_method']); ?></span>
                        </div>
                        <div class="summary-row total-row">
                            <span class="label">Amount Paid</span>
                            <span class="value">RM <?php echo number_format($payment_record['amount'], 2); ?></span>
                        </div>
                    </div>
                    <a href="customer-dashboard.php" class="btn btn-primary" style="width:100%;">← Back to Dashboard</a>
                </div>

            <?php elseif ($already_paid && $booking): ?>
                <!-- ALREADY PAID -->
                <div class="card" style="padding:2rem; text-align:center;">
                    <div style="font-size:2.5rem; margin-bottom:0.75rem;">✅</div>
                    <h2 class="mb-2">Already Paid</h2>
                    <p class="text-dim mb-3">Booking <strong><?php echo $booking['id']; ?></strong> for <strong><?php echo htmlspecialchars($booking['class_name']); ?></strong> has already been paid.</p>
                    <a href="customer-dashboard.php" class="btn btn-gold-outline">Back to Dashboard</a>
                </div>

            <?php elseif ($booking && $booking['payment_status'] === 'Unpaid' && $booking['status'] === 'Confirmed'): ?>
                <!-- PAYMENT FORM -->
                <div class="booking-summary">
                    <h3 style="margin-bottom:1rem; font-size:1rem; color:var(--accent-gold-light);">📋 Booking Summary</h3>
                    <div class="summary-row">
                        <span class="label">Booking ID</span>
                        <span class="value" style="font-family:monospace; font-size:0.85rem;"><?php echo htmlspecialchars($booking['id']); ?></span>
                    </div>
                    <div class="summary-row">
                        <span class="label">Class</span>
                        <span class="value"><?php echo htmlspecialchars($booking['class_name']); ?></span>
                    </div>
                    <div class="summary-row">
                        <span class="label">Cuisine Type</span>
                        <span class="value"><?php echo htmlspecialchars($booking['cuisine_type']); ?></span>
                    </div>
                    <div class="summary-row">
                        <span class="label">Branch</span>
                        <span class="value"><?php echo htmlspecialchars($booking['branch_name']); ?>, <?php echo htmlspecialchars($booking['city']); ?></span>
                    </div>
                    <div class="summary-row">
                        <span class="label">Schedule</span>
                        <span class="value"><?php echo date('D, d M Y', strtotime($booking['schedule'])); ?> at <?php echo date('h:i A', strtotime($booking['schedule'])); ?></span>
                    </div>
                    <div class="summary-row">
                        <span class="label">Instructor</span>
                        <span class="value"><?php echo htmlspecialchars($booking['instructor']); ?></span>
                    </div>
                    <div class="summary-row total-row">
                        <span class="label">Amount Due</span>
                        <span class="value">RM <?php echo number_format($booking['price'], 2); ?></span>
                    </div>
                </div>

                <form method="POST" action="payment.php" id="payment-form" onsubmit="return validatePayment()">
                    <input type="hidden" name="action" value="make_payment">
                    <input type="hidden" name="booking_id" value="<?php echo htmlspecialchars($booking['id']); ?>">
                    <input type="hidden" name="payment_method" id="chosen_method">

                    <h3 class="mb-3">Select Payment Method</h3>
                    <div class="method-options">
                        <div class="method-option" id="opt-fpx" onclick="selectMethod('fpx')">
                            <span class="icon">🏦</span>
                            <div class="title">FPX</div>
                            <div class="sub">Online Banking</div>
                        </div>
                        <div class="method-option" id="opt-card" onclick="selectMethod('card')">
                            <span class="icon">💳</span>
                            <div class="title">Credit / Debit Card</div>
                            <div class="sub">Visa, Mastercard</div>
                        </div>
                        <div class="method-option" id="opt-ewallet" onclick="selectMethod('ewallet')">
                            <span class="icon">📱</span>
                            <div class="title">Touch n Go</div>
                            <div class="sub">eWallet</div>
                        </div>
                    </div>

                    <!-- FPX Fields -->
                    <div id="fpx-fields" style="display:none;" class="card mb-3" style="padding:1.25rem;">
                        <div class="form-group" style="padding:1.25rem;">
                            <label for="f_bank">Select Bank</label>
                            <select name="bank_name" id="f_bank" class="form-control">
                                <option value="">Choose your bank...</option>
                                <option value="Maybank2u">Maybank2u</option>
                                <option value="CIMB Clicks">CIMB Clicks</option>
                                <option value="Public Bank">Public Bank</option>
                                <option value="RHB Bank">RHB Bank</option>
                                <option value="Hong Leong Bank">Hong Leong Bank</option>
                                <option value="AmBank">AmBank</option>
                                <option value="Bank Islam">Bank Islam</option>
                                <option value="Affin Bank">Affin Bank</option>
                            </select>
                        </div>
                    </div>

                    <!-- Card Fields -->
                    <div id="card-fields" style="display:none;" class="card mb-3">
                        <div class="form-grid" style="padding:1.25rem;">
                            <div class="form-group form-grid-full">
                                <label>Cardholder Name</label>
                                <input type="text" name="card_name" id="f_card_name" class="form-control" placeholder="Name as on card">
                            </div>
                            <div class="form-group form-grid-full">
                                <label>Card Number (last 4 digits only)</label>
                                <input type="text" name="card_last4" id="f_card_last4" class="form-control"
                                    placeholder="•••• •••• •••• 1234" maxlength="4" pattern="[0-9]{4}"
                                    oninput="this.value=this.value.replace(/\D/g,'')">
                            </div>
                            <div class="form-group">
                                <label>Expiry (MM/YY)</label>
                                <input type="text" name="card_expiry" class="form-control" placeholder="MM/YY" maxlength="5">
                            </div>
                            <div class="form-group">
                                <label>CVV</label>
                                <input type="password" name="card_cvv" class="form-control" placeholder="•••" maxlength="3">
                            </div>
                        </div>
                    </div>

                    <!-- eWallet Fields -->
                    <div id="ewallet-fields" style="display:none;" class="card mb-3" style="padding:1.25rem;">
                        <div style="padding:1.25rem; text-align:center;">
                            <div style="font-size:3rem; margin-bottom:0.5rem;">📱</div>
                            <p style="color:#fff; font-weight:600;">Touch n Go eWallet</p>
                            <p class="text-dim" style="font-size:0.85rem;">You will be redirected to the TNG eWallet app to complete payment. Click "Pay Now" to proceed.</p>
                        </div>
                    </div>

                    <div id="pay-btn-wrap" style="display:none; margin-top:1rem;">
                        <div style="background:rgba(212,175,55,0.08); border:1px solid rgba(212,175,55,0.3); border-radius:8px; padding:1rem; margin-bottom:1rem; font-size:0.85rem;">
                            🔒 <strong>Secure Payment</strong> — This is a demonstration payment system. No real transactions are processed.
                        </div>
                        <button type="submit" class="btn btn-primary" style="width:100%; padding:0.9rem; font-size:1rem;">
                            💳 Pay RM <?php echo number_format($booking['price'], 2); ?> Now
                        </button>
                    </div>
                </form>

            <?php elseif (!empty($unpaid_list)): ?>
                <!-- No specific booking — show unpaid list -->
                <div class="card" style="padding:1.5rem;">
                    <h3 class="mb-3">Your Unpaid Bookings</h3>
                    <p class="text-dim mb-3" style="font-size:0.9rem;">Select a booking below to proceed with payment.</p>
                    <div style="display:flex; flex-direction:column; gap:0.75rem;">
                        <?php foreach ($unpaid_list as $ub): ?>
                            <div style="display:flex; justify-content:space-between; align-items:center; padding:1rem 1.25rem; background:rgba(255,255,255,0.03); border:1px solid var(--border-color); border-radius:8px;">
                                <div>
                                    <div style="font-weight:600; color:#fff;"><?php echo htmlspecialchars($ub['class_name']); ?></div>
                                    <div class="text-dim" style="font-size:0.8rem;">
                                        <?php echo htmlspecialchars($ub['branch_name']); ?> ·
                                        <?php echo date('d M Y', strtotime($ub['schedule'])); ?> ·
                                        <span style="font-family:monospace;"><?php echo $ub['id']; ?></span>
                                    </div>
                                </div>
                                <div style="text-align:right;">
                                    <div style="color:var(--accent-gold-light); font-weight:700; font-size:1.1rem;">RM <?php echo number_format($ub['price'], 2); ?></div>
                                    <a href="payment.php?booking_id=<?php echo urlencode($ub['id']); ?>" class="btn btn-primary btn-sm" style="margin-top:0.25rem;">Pay Now</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

            <?php else: ?>
                <div class="card text-center" style="padding:4rem 2rem;">
                    <div style="font-size:3rem; margin-bottom:1rem;">✅</div>
                    <h2 class="mb-2">All Paid Up!</h2>
                    <p class="text-dim mb-3">You have no outstanding payments. All your confirmed bookings are settled.</p>
                    <a href="customer-dashboard.php" class="btn btn-gold-outline">Back to Dashboard</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <footer>
        <div class="footer-container">
            <p>&copy; 2026 Authentic Cuisine Class Academy. All rights reserved.</p>
        </div>
    </footer>

    <script>
    function selectMethod(method) {
        // Reset all
        document.querySelectorAll('.method-option').forEach(o => o.classList.remove('selected'));
        document.getElementById('fpx-fields').style.display = 'none';
        document.getElementById('card-fields').style.display = 'none';
        document.getElementById('ewallet-fields').style.display = 'none';

        document.getElementById('opt-' + method).classList.add('selected');
        document.getElementById('chosen_method').value = method;
        document.getElementById(method + '-fields').style.display = 'block';
        document.getElementById('pay-btn-wrap').style.display = 'block';
    }

    function validatePayment() {
        const method = document.getElementById('chosen_method').value;
        if (!method) {
            alert('Please select a payment method.');
            return false;
        }
        if (method === 'fpx') {
            const bank = document.getElementById('f_bank').value;
            if (!bank) { alert('Please select your bank for FPX payment.'); return false; }
        }
        if (method === 'card') {
            const last4 = document.getElementById('f_card_last4').value;
            if (last4.length !== 4) { alert('Please enter the last 4 digits of your card.'); return false; }
        }
        return true;
    }
    </script>
</body>
</html>
