<?php
include 'db.php';

// Simulate User Role (Can be tied to session in login system)
// Roles: 'admin' or 'clerk'
$user_role = $_GET['role'] ?? 'admin'; 

// ==========================================
// 1. HANDLE RECORD DELETION (Admin Only)
// ==========================================
if (isset($_GET['delete_id']) && $user_role === 'admin') {
    $delete_id = intval($_GET['delete_id']);
    
    // Fetch receipt file to delete it from server storage
    $res = $conn->query("SELECT receipt FROM expenses WHERE id = $delete_id");
    if ($res && $row = $res->fetch_assoc()) {
        if (!empty($row['receipt']) && file_exists("uploads/" . $row['receipt'])) {
            unlink("uploads/" . $row['receipt']);
        }
    }

    $stmt = $conn->prepare("DELETE FROM expenses WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $delete_id);
        $stmt->execute();
        $stmt->close();
    }

    header("Location: financial_tracking.php?role=" . $user_role);
    exit();
}

// ==========================================
// 2. HANDLE ADD & EDIT FORM SUBMISSIONS
// ==========================================
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // A. UPDATE / EDIT EXISTING EXPENSE
    if (isset($_POST['update_expense'])) {
        $edit_id = intval($_POST['edit_id']);
        $amount = $_POST['amount'];
        $expense_date = $_POST['expense_date'];
        $remarks = $_POST['remarks'];
        $expense_head = $_POST['expense_head'];
        $voucher_no = $_POST['voucher_no'];
        $status = $_POST['status'];
        $is_recurring = isset($_POST['is_recurring']) ? 1 : 0;
        $recurring_freq = $_POST['recurring_frequency'] ?? 'None';

        $stmt = $conn->prepare("UPDATE expenses SET amount=?, expense_date=?, remarks=?, expense_head=?, voucher_no=?, status=?, is_recurring=?, recurring_frequency=? WHERE id=?");
        if ($stmt) {
            $stmt->bind_param("dsssssisi", $amount, $expense_date, $remarks, $expense_head, $voucher_no, $status, $is_recurring, $recurring_freq, $edit_id);
            $stmt->execute();
            $stmt->close();
        }

        header("Location: financial_tracking.php?role=" . $user_role);
        exit();
    }

    // B. ADD NEW EXPENSE
    if (isset($_POST['add_expense'])) {
        $amount = $_POST['amount'];
        $expense_date = $_POST['expense_date'];
        $remarks = $_POST['remarks'];
        $expense_head = $_POST['expense_head'] ?? 'General';
        $voucher_no = $_POST['voucher_no'] ?? 'HM/FIN/'.rand(100, 999);
        $status = $_POST['status'] ?? 'Paid';
        $is_recurring = isset($_POST['is_recurring']) ? 1 : 0;
        $recurring_freq = $_POST['recurring_frequency'] ?? 'None';
        
        // Receipt File Upload Handling
        $receipt_name = "";
        if (isset($_FILES['receipt']) && $_FILES['receipt']['error'] == 0) {
            $target_dir = "uploads/";
            if (!is_dir($target_dir)) { mkdir($target_dir, 0777, true); }
            $receipt_name = time() . '_' . basename($_FILES["receipt"]["name"]);
            move_uploaded_file($_FILES["receipt"]["tmp_name"], $target_dir . $receipt_name);
        }

        $stmt = $conn->prepare("INSERT INTO expenses (amount, expense_date, remarks, expense_head, voucher_no, status, receipt, is_recurring, recurring_frequency) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("dssssssis", $amount, $expense_date, $remarks, $expense_head, $voucher_no, $status, $receipt_name, $is_recurring, $recurring_freq);
            $stmt->execute();
            $stmt->close();
        }

        header("Location: financial_tracking.php?role=" . $user_role);
        exit();
    }
}

// ==========================================
// 3. FETCH DASHBOARD & FILTER DATA
// ==========================================
$sanctioned_amount = 3200000;

// Interactive Date Range Filtering
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';

$where_clause = "";
if (!empty($start_date) && !empty($end_date)) {
    $where_clause = " WHERE expense_date BETWEEN '$start_date' AND '$end_date'";
}

// Fetch Total Expenses
$utilized_amount = 0;
if ($total_result = $conn->query("SELECT SUM(amount) AS total FROM expenses" . $where_clause)) {
    if ($total_row = $total_result->fetch_assoc()) {
        $utilized_amount = $total_row['total'] ?? 0;
    }
}

$remaining_balance = $sanctioned_amount - $utilized_amount;
$utilization_rate = $sanctioned_amount > 0 ? round(($utilized_amount / $sanctioned_amount) * 100, 1) : 0;

// Category Budgets
$category_budgets = [
    'Infrastructure (पायाभूत सुविधा)' => 1500000,
    'Maintenance (दुरुस्ती व देखभाल)' => 800000,
    'Academic Supplies (शैक्षणिक साहित्य)' => 600000,
    'Events (कार्यक्रम व उपक्रम)' => 300000
];

// Grouped Expenses by Head
$category_query = $conn->query("SELECT IFNULL(expense_head, 'General') as head, SUM(amount) as total FROM expenses" . $where_clause . " GROUP BY expense_head");
$cat_spent = [];
$cat_labels = [];
$cat_amounts = [];

if ($category_query) {
    while ($row = $category_query->fetch_assoc()) {
        $cat_labels[] = $row['head'];
        $cat_amounts[] = $row['total'];
        $cat_spent[$row['head']] = $row['total'];
    }
}

// Trend Data
$chart_query = $conn->query("SELECT expense_date, SUM(amount) as total_amount FROM expenses" . $where_clause . " GROUP BY expense_date ORDER BY expense_date ASC");
$chart_dates = [];
$chart_amounts = [];
if ($chart_query) {
    while ($row = $chart_query->fetch_assoc()) {
        $chart_dates[] = $row['expense_date'];
        $chart_amounts[] = $row['total_amount'];
    }
}

// All Expenses
$expenses = $conn->query("SELECT * FROM expenses" . $where_clause . " ORDER BY expense_date DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Financial Tracking & Governance (वित्तीय मागोवा व शासन)</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

    <style>
        * { box-sizing: border-box; transition: all 0.2s ease; }
        body { 
            font-family: 'Poppins', sans-serif; 
            margin: 0; padding: 30px; 
            background: linear-gradient(135deg, #111827 0%, #1f2937 100%); 
            color: #f9fafb; min-height: 100vh;
        }

        .dashboard-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; flex-wrap: wrap; gap: 15px; }
        /* Orange Gradient Title */
        .dashboard-title { font-size: 1.8rem; font-weight: 700; background: linear-gradient(90deg, #f97316, #fb923c, #fcc419); -webkit-background-clip: text; -webkit-text-fill-color: transparent; margin: 0; }
        
        .role-badge { background: #374151; padding: 6px 12px; border-radius: 8px; font-size: 0.85rem; color: #f9fafb; border: 1px solid #4b5563; }
        
        .btn { padding: 8px 16px; border-radius: 8px; font-weight: 600; cursor: pointer; border: none; text-decoration: none; display: inline-block; font-size:0.85rem; }
        /* Orange Buttons */
        .btn-export { background: rgba(249, 115, 22, 0.15); border: 1px solid #f97316; color: #f97316; }
        .btn-export:hover { background: #f97316; color: #111827; }
        .btn-pdf { background: rgba(251, 146, 60, 0.2); border: 1px solid #fb923c; color: #fb923c; }
        .btn-edit { background: rgba(245, 158, 11, 0.2); border: 1px solid #f59e0b; color: #f59e0b; margin-left: 4px; }
        .btn-edit:hover { background: #f59e0b; color: #111827; }
        .btn-delete { background: rgba(239, 68, 68, 0.2); border: 1px solid #ef4444; color: #ef4444; margin-left: 4px; }
        .btn-delete:hover { background: #ef4444; color: #ffffff; }

        .cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .card { background: rgba(31, 41, 55, 0.7); backdrop-filter: blur(10px); padding: 20px; border-radius: 16px; border: 1px solid rgba(249, 115, 22, 0.2); }
        .card h3 { margin: 0 0 8px 0; font-size: 0.8rem; color: #9ca3af; text-transform: uppercase; }
        .card p { font-size: 1.6rem; font-weight: 700; margin: 0; }

        /* Orange Progress Bar */
        .progress-bar-bg { background: #374151; height: 8px; border-radius: 10px; margin-top: 10px; overflow: hidden; }
        .progress-bar-fill { height: 100%; background: linear-gradient(90deg, #ea580c, #f97316, #fb923c); border-radius: 10px; }

        .box { background: rgba(31, 41, 55, 0.7); backdrop-filter: blur(10px); padding: 25px; border-radius: 16px; margin-bottom: 30px; border: 1px solid rgba(249, 115, 22, 0.15); }
        .section-title { font-size: 1.1rem; color: #f3f4f6; margin-top: 0; margin-bottom: 20px; }

        form { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; }
        input, select { width: 100%; padding: 10px; font-size: 0.88rem; border-radius: 10px; border: 1px solid #4b5563; background: #111827; color: #f9fafb; }
        input:focus, select:focus { outline: none; border-color: #f97316; }

        /* Orange Submit Button */
        .submit-btn { grid-column: 1 / -1; padding: 12px; font-size: 1rem; font-weight: 600; background: linear-gradient(135deg, #ea580c, #f97316); color: white; border: none; border-radius: 10px; cursor: pointer; }
        .submit-btn:hover { background: linear-gradient(135deg, #c2410c, #ea580c); }

        .badge { padding: 4px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; }
        .badge.paid { background: rgba(34, 197, 94, 0.2); color: #4ade80; border: 1px solid #4ade80; }
        .badge.pending { background: rgba(249, 115, 22, 0.2); color: #fb923c; border: 1px solid #fb923c; }
        .badge.recurring { background: rgba(245, 158, 11, 0.2); color: #f59e0b; border: 1px solid #f59e0b; font-size: 0.7rem; margin-left: 4px; }

        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 12px; text-align: left; }
        /* Orange Accent Table Header */
        th { background: #7c2d12; color: #ffedd5; font-size: 0.8rem; text-transform: uppercase; }
        tr { border-bottom: 1px solid #374151; }

        .cat-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 15px; }
        .cat-card { background: #111827; padding: 15px; border-radius: 12px; border: 1px solid #374151; min-height: 110px; }
        .cat-card div { line-height: 1.5; }

        /* Modal Popup Styling */
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); backdrop-filter: blur(5px); }
        .modal-content { background: #1f2937; margin: 5% auto; padding: 30px; border-radius: 16px; width: 90%; max-width: 600px; border: 1px solid #f97316; }
        .close-btn { float: right; font-size: 1.5rem; cursor: pointer; color: #9ca3af; }
        .close-btn:hover { color: white; }
    </style>
</head>
<body>

    <div class="dashboard-header">
        <h2 class="dashboard-title">Financial Tracking & Governance (वित्तीय मागोवा व शासन)</h2>
        <div>
            <span class="role-badge">👤 Role: <strong><?php echo strtoupper($user_role); ?></strong></span>
            <a href="financial_tracking.php?role=admin" class="btn btn-export" style="padding:4px 8px; font-size:0.75rem;">Switch to Admin</a>
            <a href="financial_tracking.php?role=clerk" class="btn btn-export" style="padding:4px 8px; font-size:0.75rem;">Switch to Clerk</a>
            <button class="btn btn-export" onclick="exportToCSV()">📥 Export CSV</button>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="cards">
        <div class="card"><h3 style="color:#fb923c;">Sanctioned Budget (मंजूर बजेट)</h3><p>₹<?php echo number_format($sanctioned_amount); ?></p></div>
        <div class="card"><h3 style="color:#ef4444;">Utilized Amount (वापरलेली रक्कम)</h3><p>₹<?php echo number_format($utilized_amount); ?></p></div>
        <div class="card"><h3 style="color:#4ade80;">Remaining Balance (शिल्लक रक्कम)</h3><p>₹<?php echo number_format($remaining_balance); ?></p></div>
        <div class="card">
            <h3 style="color:#f59e0b;">Utilization Rate (वापर दर)</h3>
            <p><?php echo $utilization_rate; ?>%</p>
            <div class="progress-bar-bg"><div class="progress-bar-fill" style="width: <?php echo min($utilization_rate, 100); ?>%;"></div></div>
        </div>
    </div>

    <!-- Category Budget Tracker -->
    <div class="box">
        <h3 class="section-title">🎯 Category Budget Allocation (वर्गनिहाय बजेट वाटप)</h3>
        <div class="cat-grid">
            <?php foreach ($category_budgets as $cat => $alloc): 
                $clean_cat = explode(' (', $cat)[0];
                $spent = $cat_spent[$clean_cat] ?? ($cat_spent[$cat] ?? 0);
                $cat_pct = round(($spent / $alloc) * 100, 1);
            ?>
                <div class="cat-card">
                    <div style="display:flex; justify-content:space-between; font-weight:600; font-size:0.85rem;">
                        <span><?php echo $cat; ?></span>
                        <span style="color:#fb923c;"><?php echo $cat_pct; ?>%</span>
                    </div>
                    <div style="font-size:0.75rem; color:#9ca3af; margin:4px 0;">₹<?php echo number_format($spent); ?> / ₹<?php echo number_format($alloc); ?></div>
                    <div class="progress-bar-bg"><div class="progress-bar-fill" style="width: <?php echo min($cat_pct, 100); ?>%;"></div></div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Expenditure Form -->
    <div class="box">
        <h3 class="section-title">➕ Record New Expenditure Entry (नवीन खर्चाची नोंद करा)</h3>
        <form method="POST" action="financial_tracking.php?role=<?php echo $user_role; ?>" enctype="multipart/form-data">
            <input type="number" step="0.01" name="amount" placeholder="Amount (रक्कम ₹)" required>
            <input type="date" name="expense_date" required>
            <select name="expense_head">
                <option value="Infrastructure">Infrastructure (पायाभूत सुविधा)</option>
                <option value="Maintenance">Maintenance (दुरुस्ती व देखभाल)</option>
                <option value="Academic Supplies">Academic Supplies (शैक्षणिक साहित्य)</option>
                <option value="Events">Events (कार्यक्रम व उपक्रम)</option>
                <option value="General">General (सर्वसाधारण)</option>
            </select>
            <input type="text" name="voucher_no" placeholder="Voucher No (व्हाउचर क्र. e.g. HM/FIN/101)">
            <select name="status">
                <option value="Paid">Paid (अदा केले)</option>
                <option value="Pending">Pending (प्रलंबित)</option>
            </select>
            <input type="file" name="receipt" accept="image/*,.pdf" style="color:#9ca3af;">
            
            <div style="display:flex; align-items:center; gap:8px; grid-column: 1 / -1;">
                <input type="checkbox" name="is_recurring" id="is_recurring" style="width:auto;" onchange="toggleRecurringFreq(this)">
                <label for="is_recurring" style="font-size:0.85rem; color:#fb923c;">🔄 Mark as Recurring Expense (नियमित खर्च म्हणून नोंदवा)</label>
                <select name="recurring_frequency" id="recurring_freq_select" style="display:none; width:auto; padding:6px;">
                    <option value="Monthly">Monthly (मासिक)</option>
                    <option value="Quarterly">Quarterly (त्रैमासिक)</option>
                    <option value="Yearly">Yearly (वार्षिक)</option>
                </select>
            </div>

            <input type="text" name="remarks" placeholder="Remarks / Description (तपशील / वर्णन)" required style="grid-column: 1 / -1;">
            <button type="submit" name="add_expense" class="submit-btn">Save Entry (नोंद जतन करा)</button>
        </form>
    </div>

    <!-- Dual Charts -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin-bottom: 30px;">
        <div class="box" style="margin:0;"><h3 class="section-title">📈 Spending Trends (खर्चाचा कल)</h3><canvas id="trendChart" style="max-height:240px;"></canvas></div>
        <div class="box" style="margin:0;"><h3 class="section-title">🍩 Category Breakdown (वर्गनिहाय विभागणी)</h3><canvas id="categoryChart" style="max-height:240px;"></canvas></div>
    </div>

    <!-- Expense Register Table -->
    <div class="box">
        <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px; margin-bottom:15px;">
            <h3 class="section-title" style="margin:0;">📜 Financial Expenditure Register (वित्तीय खर्च नोंदवही)</h3>
            <input type="text" id="searchInput" onkeyup="filterTable()" placeholder="🔍 Live Search (शोधा)..." style="width:220px;">
        </div>

        <table id="expenseTable">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Date (तारीख)</th>
                    <th>Category (वर्ग)</th>
                    <th>Voucher (व्हाउचर)</th>
                    <th>Status (स्थिती)</th>
                    <th>Receipt (पावती)</th>
                    <th>Amount (रक्कम ₹)</th>
                    <th>Action (कृती)</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($expenses && $expenses->num_rows > 0): ?>
                    <?php while ($row = $expenses->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $row['id']; ?></td>
                            <td><?php echo $row['expense_date']; ?></td>
                            <td><?php echo htmlspecialchars($row['expense_head'] ?? 'General'); ?></td>
                            <td><code><?php echo htmlspecialchars($row['voucher_no'] ?? 'N/A'); ?></code></td>
                            <td>
                                <span class="badge <?php echo strtolower($row['status'] ?? 'paid'); ?>">
                                    <?php echo htmlspecialchars($row['status'] ?? 'Paid'); ?>
                                </span>
                                <?php if (!empty($row['is_recurring'])): ?>
                                    <span class="badge recurring">🔄 <?php echo $row['recurring_frequency']; ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($row['receipt'])): ?>
                                    <a href="uploads/<?php echo $row['receipt']; ?>" target="_blank" style="color:#fb923c;">📎 View</a>
                                <?php else: ?>
                                    <span style="color:#6b7280;">None</span>
                                <?php endif; ?>
                            </td>
                            <td style="font-weight:600; color:#fb923c;">₹<?php echo number_format($row['amount'], 2); ?></td>
                            <td>
                                <button class="btn btn-pdf" onclick="generatePDFVoucher('<?php echo $row['voucher_no']; ?>', '<?php echo $row['expense_date']; ?>', '<?php echo $row['expense_head']; ?>', '<?php echo $row['amount']; ?>', '<?php echo htmlspecialchars($row['remarks']); ?>')">📄</button>
                                <button class="btn btn-edit" onclick="openEditModal(<?php echo htmlspecialchars(json_encode($row)); ?>)">✏️ Edit</button>

                                <?php if ($user_role === 'admin'): ?>
                                    <a href="financial_tracking.php?delete_id=<?php echo $row['id']; ?>&role=admin" 
                                       class="btn btn-delete" 
                                       onclick="return confirm('Are you sure you want to delete this record?');">
                                       🗑️
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="8" style="text-align:center; color:#6b7280;">No expenditure records found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Edit Expense Modal Popup -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <span class="close-btn" onclick="closeEditModal()">&times;</span>
            <h3 class="section-title">✏️ Edit Expense Record (नोंद संपादित करा)</h3>
            <form method="POST" action="financial_tracking.php?role=<?php echo $user_role; ?>">
                <input type="hidden" name="edit_id" id="edit_id">
                
                <div>
                    <label style="font-size:0.8rem; color:#9ca3af;">Amount (रक्कम ₹):</label>
                    <input type="number" step="0.01" name="amount" id="edit_amount" required>
                </div>
                <div>
                    <label style="font-size:0.8rem; color:#9ca3af;">Date (तारीख):</label>
                    <input type="date" name="expense_date" id="edit_date" required>
                </div>
                <div>
                    <label style="font-size:0.8rem; color:#9ca3af;">Category (वर्ग):</label>
                    <select name="expense_head" id="edit_head">
                        <option value="Infrastructure">Infrastructure</option>
                        <option value="Maintenance">Maintenance</option>
                        <option value="Academic Supplies">Academic Supplies</option>
                        <option value="Events">Events</option>
                        <option value="General">General</option>
                    </select>
                </div>
                <div>
                    <label style="font-size:0.8rem; color:#9ca3af;">Voucher No:</label>
                    <input type="text" name="voucher_no" id="edit_voucher">
                </div>
                <div>
                    <label style="font-size:0.8rem; color:#9ca3af;">Status (स्थिती):</label>
                    <select name="status" id="edit_status">
                        <option value="Paid">Paid</option>
                        <option value="Pending">Pending</option>
                    </select>
                </div>
                <div style="grid-column: 1 / -1;">
                    <label style="font-size:0.8rem; color:#9ca3af;">Remarks (तपशील):</label>
                    <input type="text" name="remarks" id="edit_remarks" required>
                </div>
                
                <button type="submit" name="update_expense" class="submit-btn">Update Expense (सुधारित करा)</button>
            </form>
        </div>
    </div>

    <!-- Hidden Template for Voucher PDF -->
    <div id="voucher-template" style="display:none; padding:30px; background:#ffffff; color:#000000; font-family:Arial, sans-serif;">
        <h2 style="text-align:center; margin-bottom:5px; color:#ea580c;">SCHOOL FINANCIAL PAYMENT VOUCHER</h2>
        <p style="text-align:center; color:#555; margin-top:0;">Official Audit Document</p>
        <hr style="margin:20px 0; border-color:#ea580c;">
        <p><strong>Voucher No:</strong> <span id="v-no"></span></p>
        <p><strong>Date:</strong> <span id="v-date"></span></p>
        <p><strong>Category Head:</strong> <span id="v-head"></span></p>
        <p><strong>Amount Paid:</strong> ₹<span id="v-amount"></span></p>
        <p><strong>Description:</strong> <span id="v-remarks"></span></p>
        <br><br><br>
        <div style="display:flex; justify-content:space-between; margin-top:40px;">
            <div>_______________________<br><strong>Headmaster Signature</strong></div>
            <div>_______________________<br><strong>Accountant Signature</strong></div>
        </div>
    </div>

    <script>
        function toggleRecurringFreq(checkbox) {
            document.getElementById('recurring_freq_select').style.display = checkbox.checked ? 'inline-block' : 'none';
        }

        function openEditModal(data) {
            document.getElementById('edit_id').value = data.id;
            document.getElementById('edit_amount').value = data.amount;
            document.getElementById('edit_date').value = data.expense_date;
            document.getElementById('edit_head').value = data.expense_head || 'General';
            document.getElementById('edit_voucher').value = data.voucher_no || '';
            document.getElementById('edit_status').value = data.status || 'Paid';
            document.getElementById('edit_remarks').value = data.remarks;
            
            document.getElementById('editModal').style.display = 'block';
        }

        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }

        function filterTable() {
            let input = document.getElementById("searchInput").value.toLowerCase();
            let rows = document.querySelectorAll("#expenseTable tbody tr");
            rows.forEach(row => {
                row.style.display = row.innerText.toLowerCase().includes(input) ? "" : "none";
            });
        }

        function exportToCSV() {
            let table = document.getElementById("expenseTable");
            let csv = Array.from(table.rows).map(row => Array.from(row.cells).slice(0,7).map(cell => '"' + cell.innerText.replace(/"/g, '""') + '"').join(",")).join("\n");
            let link = document.createElement('a');
            link.href = URL.createObjectURL(new Blob([csv], { type: 'text/csv' }));
            link.download = 'Financial_Report.csv';
            link.click();
        }

        function generatePDFVoucher(voucherNo, date, head, amount, remarks) {
            document.getElementById('v-no').innerText = voucherNo;
            document.getElementById('v-date').innerText = date;
            document.getElementById('v-head').innerText = head;
            document.getElementById('v-amount').innerText = parseFloat(amount).toLocaleString('en-IN');
            document.getElementById('v-remarks').innerText = remarks;

            const element = document.getElementById('voucher-template');
            element.style.display = 'block';

            const opt = {
                margin:       0.5,
                filename:     `Voucher_${voucherNo}.pdf`,
                image:        { type: 'jpeg', quality: 0.98 },
                html2canvas:  { scale: 2 },
                jsPDF:        { unit: 'in', format: 'letter', orientation: 'portrait' }
            };

            html2pdf().set(opt).from(element).save().then(() => {
                element.style.display = 'none';
            });
        }

        // Orange Theme Line Chart
        new Chart(document.getElementById('trendChart'), {
            type: 'line',
            data: {
                labels: <?php echo json_encode($chart_dates); ?>,
                datasets: [{
                    label: 'Expenditure (₹)',
                    data: <?php echo json_encode($chart_amounts); ?>,
                    borderColor: '#f97316',
                    backgroundColor: 'rgba(249, 115, 22, 0.2)',
                    fill: true, tension: 0.4, borderWidth: 3
                }]
            },
            options: { responsive: true, plugins: { legend: { display: false } }, scales: { x: { ticks: { color: '#9ca3af' } }, y: { ticks: { color: '#9ca3af' } } } }
        });

        // Orange Theme Doughnut Chart
        new Chart(document.getElementById('categoryChart'), {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode($cat_labels); ?>,
                datasets: [{
                    data: <?php echo json_encode($cat_amounts); ?>,
                    backgroundColor: ['#ea580c', '#f97316', '#fb923c', '#f59e0b', '#fcd34d'],
                    borderColor: '#1f2937', borderWidth: 2
                }]
            },
            options: { 
                responsive: true, 
                maintainAspectRatio: false,
                plugins: { 
                    legend: { 
                        position: 'bottom', 
                        labels: { 
                            color: '#9ca3af',
                            font: { size: 11 },
                            boxWidth: 12,
                            padding: 15
                        } 
                    } 
                } 
            }
        });
    </script>
</body>
</html>