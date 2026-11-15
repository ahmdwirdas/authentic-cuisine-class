<?php
// report-pdf.php - Dedicated Clean Printable / PDF Export Report View
require_once __DIR__ . '/auth.php';
require_admin();
date_default_timezone_set('Asia/Kuala_Lumpur');

$filter_month = sanitize($_GET['month'] ?? ''); // e.g. "2026-11"
$filter_cuisine = sanitize($_GET['cuisine_type'] ?? ''); // e.g. "Haute"
$filter_status = sanitize($_GET['status'] ?? '');

$sql = "SELECT b.id as booking_id, b.booking_date, b.status, b.payment_status,
               c.id as class_id, c.name as class_name, c.cuisine_type, c.instructor, c.schedule, c.price,
               br.name as branch_name,
               cust.name as customer_name, cust.email as customer_email, cust.phone as customer_phone,
               p.transaction_ref, p.payment_method, p.paid_at
        FROM bookings b
        JOIN cuisineclasses c ON b.class_id = c.id
        JOIN branches br ON c.branch_id = br.id
        JOIN customers cust ON b.customer_id = cust.id
        LEFT JOIN payments p ON b.id = p.booking_id
        WHERE 1=1";

$params = [];
if (!empty($filter_month)) {
    $sql .= " AND DATE_FORMAT(b.booking_date, '%Y-%m') = ?";
    $params[] = $filter_month;
}
if (!empty($filter_cuisine)) {
    $sql .= " AND c.cuisine_type = ?";
    $params[] = $filter_cuisine;
}
if (!empty($filter_status)) {
    $sql .= " AND b.status = ?";
    $params[] = $filter_status;
}
$sql .= " ORDER BY b.booking_date DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$records = $stmt->fetchAll();

// Aggregations
$total_bookings = count($records);
$total_revenue = 0;
$confirmed_count = 0;
$paid_count = 0;
foreach ($records as $r) {
    if ($r['status'] === 'Confirmed') {
        $confirmed_count++;
        $total_revenue += (float)$r['price'];
    }
    if ($r['payment_status'] === 'Paid') {
        $paid_count++;
    }
}

$month_label = $filter_month ? date('F Y', strtotime($filter_month . '-01')) : 'All Time';
$cuisine_label = $filter_cuisine ? $filter_cuisine : 'All Cuisines';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report - <?php echo htmlspecialchars($cuisine_label); ?> (<?php echo htmlspecialchars($month_label); ?>)</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 1.5cm;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
            color: #1f2937;
            background: #ffffff;
            margin: 0;
            padding: 20px;
            font-size: 11pt;
            line-height: 1.4;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 2px solid #d97706;
            padding-bottom: 12px;
            margin-bottom: 20px;
        }
        .brand {
            font-size: 20pt;
            font-weight: 800;
            color: #111827;
        }
        .brand span {
            color: #d97706;
        }
        .meta {
            text-align: right;
            font-size: 9pt;
            color: #4b5563;
        }
        .report-title {
            margin: 0 0 4px 0;
            font-size: 15pt;
            color: #111827;
        }
        .filter-badges {
            margin-bottom: 15px;
            display: flex;
            gap: 8px;
        }
        .badge {
            display: inline-block;
            background: #f3f4f6;
            border: 1px solid #e5e7eb;
            color: #1f2937;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 9pt;
            font-weight: 600;
        }
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 12px;
            margin-bottom: 20px;
        }
        .stat-card {
            border: 1px solid #e5e7eb;
            background: #fafafa;
            border-radius: 6px;
            padding: 10px 14px;
        }
        .stat-label {
            font-size: 8.5pt;
            color: #6b7280;
            text-transform: uppercase;
            font-weight: 600;
            display: block;
        }
        .stat-value {
            font-size: 16pt;
            font-weight: 700;
            color: #111827;
            margin-top: 2px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9pt;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #e5e7eb;
            padding: 7px 10px;
            text-align: left;
        }
        th {
            background-color: #f9fafb;
            color: #374151;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 8pt;
            letter-spacing: 0.03em;
        }
        tr:nth-child(even) {
            background-color: #fdfdfd;
        }
        .status-badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 8pt;
            font-weight: 600;
        }
        .status-confirmed { background: #dcfce7; color: #166534; }
        .status-cancelled { background: #fee2e2; color: #991b1b; }
        .status-paid { background: #dbeafe; color: #1e40af; }
        .status-unpaid { background: #fef3c7; color: #92400e; }
        .footer {
            margin-top: 30px;
            padding-top: 10px;
            border-top: 1px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
            font-size: 8pt;
            color: #6b7280;
        }
        .no-print {
            background: #1e293b;
            color: #ffffff;
            padding: 12px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .btn-action {
            background: #d97706;
            color: #ffffff;
            border: none;
            padding: 8px 16px;
            border-radius: 5px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            font-size: 9.5pt;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .btn-action:hover {
            background: #b45309;
        }
        .btn-back {
            background: #334155;
            color: #e2e8f0;
            border: none;
            padding: 8px 14px;
            border-radius: 5px;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
            font-size: 9.5pt;
            margin-right: 8px;
        }
        @media print {
            .no-print {
                display: none !important;
            }
            body {
                padding: 0;
            }
        }
    </style>
</head>
<body>

    <div class="no-print">
        <div>
            <strong>Report Ready for Export / Print</strong>
            <span style="color:#94a3b8; font-size:9pt; margin-left:8px;">Use the button below or press <code>Ctrl+P</code> to save as PDF.</span>
        </div>
        <div>
            <a href="admin-dashboard.php#reports" class="btn-back">← Back to Dashboard</a>
            <button onclick="window.print()" class="btn-action">
                🖨️ Convert to PDF / Print
            </button>
        </div>
    </div>

    <div class="header">
        <div>
            <div class="brand">Authentic <span>Cuisine</span></div>
            <p style="margin:2px 0 0 0; color:#4b5563; font-size:9.5pt;">Comprehensive Culinary Booking & Revenue Report</p>
        </div>
        <div class="meta">
            <div><strong>Generated By:</strong> <?php echo htmlspecialchars($_SESSION['admin_username']); ?> (Admin)</div>
            <div><strong>Generated At:</strong> <?php echo date('d M Y, h:i A'); ?></div>
            <div><strong>Location:</strong> All Academy Branches</div>
        </div>
    </div>

    <h2 class="report-title">Culinary Registrations & Sales Statement</h2>
    <div class="filter-badges">
        <span class="badge">📅 Month: <strong><?php echo htmlspecialchars($month_label); ?></strong></span>
        <span class="badge">🍲 Cuisine Style: <strong><?php echo htmlspecialchars($cuisine_label); ?></strong></span>
        <?php if ($filter_status): ?>
            <span class="badge">Status: <strong><?php echo htmlspecialchars($filter_status); ?></strong></span>
        <?php endif; ?>
    </div>

    <div class="summary-grid">
        <div class="stat-card">
            <span class="stat-label">Total Bookings</span>
            <div class="stat-value"><?php echo $total_bookings; ?></div>
        </div>
        <div class="stat-card">
            <span class="stat-label">Confirmed Bookings</span>
            <div class="stat-value" style="color: #166534;"><?php echo $confirmed_count; ?></div>
        </div>
        <div class="stat-card">
            <span class="stat-label">Settled (Paid)</span>
            <div class="stat-value" style="color: #1e40af;"><?php echo $paid_count; ?></div>
        </div>
        <div class="stat-card">
            <span class="stat-label">Total Confirmed Revenue</span>
            <div class="stat-value" style="color: #d97706;">RM <?php echo number_format($total_revenue, 2); ?></div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 80px;">Booking ID</th>
                <th>Booking Date</th>
                <th>Student Details</th>
                <th>Class Name & Cuisine</th>
                <th>Branch & Chef</th>
                <th>Schedule</th>
                <th>Amount (RM)</th>
                <th>Booking Status</th>
                <th>Payment</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($records)): ?>
                <tr>
                    <td colspan="9" style="text-align: center; padding: 25px; color:#6b7280;">
                        No booking records found for the selected month and cuisine criteria.
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($records as $r): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($r['booking_id']); ?></strong></td>
                        <td><?php echo date('d/m/Y H:i', strtotime($r['booking_date'])); ?></td>
                        <td>
                            <strong><?php echo htmlspecialchars($r['customer_name']); ?></strong><br>
                            <span style="color:#6b7280; font-size:8pt;"><?php echo htmlspecialchars($r['customer_email']); ?></span>
                        </td>
                        <td>
                            <strong><?php echo htmlspecialchars($r['class_name']); ?></strong><br>
                            <span style="color:#4b5563; font-size:8pt;"><?php echo htmlspecialchars($r['cuisine_type']); ?></span>
                        </td>
                        <td>
                            <?php echo htmlspecialchars($r['branch_name']); ?><br>
                            <span style="color:#6b7280; font-size:8pt;"><?php echo htmlspecialchars($r['instructor']); ?></span>
                        </td>
                        <td><?php echo date('d M Y, h:i A', strtotime($r['schedule'])); ?></td>
                        <td><strong><?php echo number_format($r['price'], 2); ?></strong></td>
                        <td>
                            <span class="status-badge status-<?php echo strtolower($r['status']); ?>">
                                <?php echo $r['status']; ?>
                            </span>
                        </td>
                        <td>
                            <span class="status-badge status-<?php echo strtolower($r['payment_status'] ?? 'unpaid'); ?>">
                                <?php echo $r['payment_status'] ?? 'Unpaid'; ?>
                            </span>
                            <?php if (!empty($r['transaction_ref'])): ?>
                                <br><span style="font-size:7.5pt; color:#6b7280;"><?php echo htmlspecialchars($r['transaction_ref']); ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="footer">
        <div>Authentic Cuisine Class Management System &copy; <?php echo date('Y'); ?>. Confidential Document.</div>
        <div>Page 1 of 1 • Generated for Administrative Management</div>
    </div>

</body>
</html>
