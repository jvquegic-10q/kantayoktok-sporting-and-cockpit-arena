<?php
require 'config.php';
requireLogin();
requireRole('admin');

$headcount = getTodayHeadcount();
$financials = getTodayFinancials();
$closing = getTodayClosingSummary();
$recent_summaries = getRecentDailySummaries(7);
$selected_date = $_GET['history_date'] ?? date('Y-m-d');
$history_summary = getDailySummaryForDate($selected_date);
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Admin Dashboard - Kantayoktok Sporting and Cockpit Arena</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="admin.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="header-left">
                <h1>🐓 Admin Dashboard</h1>
                <p style="color: #dfe9ff;">Complete Daily Monitoring</p>
            </div>
            <div class="header-right">
                <div class="user-info">
                    <p>Administrator</p>
                    <p><strong><?php echo $_SESSION['full_name']; ?></strong></p>
                    <p><?php echo date('F d, Y'); ?></p>
                    <p style="margin-top: 0.5rem; font-size: 12px;">
                        Last updated: <span id="last-updated">now</span>
                    </p>
                    <p style="margin-top: 0.5rem;">
                        <span class="status-pill <?php echo getTodayEventStatus() === 'closed' ? 'status-closed' : 'status-ongoing'; ?>" id="event-status">
                            <?php echo ucfirst(getTodayEventStatus()); ?>
                        </span>
                    </p>
                </div>
                <div style="display:flex;align-items:center;gap:0.75rem;flex-wrap:wrap;">
                    <button type="button" class="refresh-btn" onclick="window.location.reload()">↻ Refresh</button>
                    <a href="logout.php" class="logout-btn">🚪 Logout</a>
                </div>
            </div>
        </div>

        <!-- KEY METRICS -->
        <div class="metrics">
            <div class="metric-card card-inside">
                <h3>People Inside</h3>
                <div class="metric-value" id="metric-inside"><?php echo number_format($headcount['inside']); ?></div>
                <div class="metric-subtext">Entries: <span id="metric-entries"><?php echo number_format($headcount['entries']); ?></span> | Exits: <span id="metric-exits"><?php echo number_format($headcount['exits']); ?></span></div>
            </div>

            <div class="metric-card card-in">
                <h3>Gross Cash</h3>
                <div class="metric-value" id="metric-gross">₱<?php echo number_format($financials['gross_cash'], 0); ?></div>
            </div>

            <div class="metric-card card-out">
                <h3>Direct + Ops Expenses</h3>
                <div class="metric-value" id="metric-expenses">₱<?php echo number_format(($financials['direct_expenses'] + $financials['operational_expenses']), 0); ?></div>
            </div>

            <div class="metric-card card-profit">
                <h3>Net Profit</h3>
                <div class="metric-value" id="metric-profit">₱<?php echo number_format($financials['net_income'], 0); ?></div>
            </div>
        </div>

        <!-- DETAILED REPORTS -->
        <div class="sections">
            <!-- INCOME BREAKDOWN -->
            <div class="section">
                <h2>💵 Income Breakdown</h2>
                <div id="income-breakdown">
                    <?php
                    $income_labels = [
                        'sultada' => 'Sultada (Bet)',
                        'pulta' => 'Pulta (Entry)',
                        'dresser' => 'Dresser',
                        'tax_admin' => 'Tax/Admin',
                        'parking' => 'Parking',
                        'space_rental' => 'Space Rental',
                        'other' => 'Other'
                    ];
                    
                    foreach ($income_labels as $key => $label) {
                        $amount = $financials['income'][$key] ?? 0;
                        echo "<div class='category-row'>";
                        echo "<div class='category-label'>$label</div>";
                        echo "<div class='category-value income-$key'>₱" . number_format($amount, 2) . "</div>";
                        echo "</div>";
                    }
                    ?>
                </div>
                <div class="total-row">
                    <div class="category-label">TOTAL INCOME</div>
                    <div class="category-value" id="income-total">₱<?php echo number_format($financials['total_income'], 2); ?></div>
                </div>
            </div>

            <!-- EXPENSE BREAKDOWN -->
            <div class="section">
                <h2>📉 Expense Breakdown</h2>
                <div id="expense-breakdown">
                    <?php
                    $expense_labels = [
                        'amusement_tax' => 'Amusement Tax',
                        'permit' => 'Permit',
                        'payroll' => 'Payroll',
                        'meals' => 'Meals',
                        'fuel' => 'Fuel',
                        'materials' => 'Materials',
                        'utilities' => 'Utilities',
                        'labor' => 'Labor',
                        'promo' => 'Promo'
                    ];
                    
                    foreach ($expense_labels as $key => $label) {
                        $amount = $financials['expenses'][$key] ?? 0;
                        echo "<div class='category-row'>";
                        echo "<div class='category-label'>$label</div>";
                        echo "<div class='category-value expense-$key'>₱" . number_format($amount, 2) . "</div>";
                        echo "</div>";
                    }
                    ?>
                </div>
                <div class="total-row">
                    <div class="category-label">TOTAL EXPENSES</div>
                    <div class="category-value" id="expense-total">₱<?php echo number_format($financials['total_expenses'], 2); ?></div>
                </div>
            </div>
        </div>

        <!-- DAILY CLOSING SUMMARY -->
        <div class="section" style="background: #fffdf5;">
            <h2>📌 Daily Closing Summary</h2>
            <div class="closing-summary">
                <div class="closing-card">
                    <div class="closing-label">Beginning Balance</div>
                    <div class="closing-value closing-positive" id="close-beginning">₱<?php echo number_format($closing['beginning_balance'], 2); ?></div>
                </div>
                <div class="closing-card">
                    <div class="closing-label">Sultada Net</div>
                    <div class="closing-value closing-positive" id="close-sultada">₱<?php echo number_format($closing['sultada_net'], 2); ?></div>
                </div>
                <div class="closing-card">
                    <div class="closing-label">Pulta</div>
                    <div class="closing-value closing-positive" id="close-pulta">₱<?php echo number_format($closing['pulta_total'], 2); ?></div>
                </div>
                <div class="closing-card">
                    <div class="closing-label">Cash Balanced</div>
                    <div class="closing-value closing-positive" id="close-balanced">₱<?php echo number_format($closing['cash_balanced'], 2); ?></div>
                </div>
                <div class="closing-card">
                    <div class="closing-label">Direct Expenses</div>
                    <div class="closing-value closing-negative" id="close-direct">₱<?php echo number_format($closing['direct_expenses'], 2); ?></div>
                </div>
                <div class="closing-card">
                    <div class="closing-label">Gross</div>
                    <div class="closing-value closing-positive" id="close-gross">₱<?php echo number_format($closing['gross'], 2); ?></div>
                </div>
                <div class="closing-card">
                    <div class="closing-label">Less Expenses</div>
                    <div class="closing-value closing-negative" id="close-less">₱<?php echo number_format($closing['direct_expenses'], 2); ?></div>
                </div>
                <div class="closing-card">
                    <div class="closing-label">Income</div>
                    <div class="closing-value closing-warning" id="close-income">₱<?php echo number_format($closing['income_after_expenses'], 2); ?></div>
                </div>
                <div class="closing-card">
                    <div class="closing-label">Additional Income</div>
                    <div class="closing-value closing-positive" id="close-additional">₱<?php echo number_format($closing['additional_income'], 2); ?></div>
                </div>
                <div class="closing-card">
                    <div class="closing-label">Operational Expenses</div>
                    <div class="closing-value closing-negative" id="close-operational">₱<?php echo number_format($closing['operational_expenses'], 2); ?></div>
                </div>
                <div class="closing-card">
                    <div class="closing-label">Net Income</div>
                    <div class="closing-value closing-warning" id="close-net">₱<?php echo number_format($closing['net_income'], 2); ?></div>
                </div>
                <div class="closing-card">
                    <div class="closing-label">Status</div>
                    <div class="closing-value" style="font-size: 22px; color: <?php echo $closing['status'] === 'closed' ? '#8a6d00' : '#1e7e34'; ?>" id="close-status">
                        <?php echo ucfirst($closing['status']); ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- RECENT HISTORY -->
        <div class="section history-section">
            <div class="history-panel">
                <div class="history-panel-head">
                    <div>
                        <p class="history-kicker">Live daily snapshots</p>
                        <h2>🗓️ Recent 7-Day Records</h2>
                    </div>
                    <div class="history-badge">Last 7 days</div>
                </div>

                <div class="history-toolbar">
                    <form method="GET" class="history-form">
                        <label for="history_date">View day:</label>
                        <select id="history_date" name="history_date" onchange="this.form.submit()">
                            <?php foreach (array_keys($recent_summaries) as $date): ?>
                                <option value="<?php echo htmlspecialchars($date, ENT_QUOTES); ?>" <?php echo $selected_date === $date ? 'selected' : ''; ?>><?php echo htmlspecialchars($date, ENT_QUOTES); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                </div>

                <?php if ($history_summary): ?>
                    <div class="history-meta">
                        <div class="history-meta-item">
                            <span class="history-meta-label">Selected day</span>
                            <strong><?php echo htmlspecialchars($history_summary['event_date'], ENT_QUOTES); ?></strong>
                        </div>
                        <div class="history-meta-item">
                            <span class="history-meta-label">Status</span>
                            <strong><?php echo ucfirst($history_summary['status'] ?? 'ongoing'); ?></strong>
                        </div>
                        <div class="history-meta-item">
                            <span class="history-meta-label">Net income</span>
                            <strong>₱<?php echo number_format($history_summary['net_income'], 2); ?></strong>
                        </div>
                    </div>

                    <div class="history-grid">
                        <div class="history-card">
                            <div class="history-label">Date</div>
                            <div class="history-value"><?php echo htmlspecialchars($history_summary['event_date'], ENT_QUOTES); ?></div>
                        </div>
                        <div class="history-card">
                            <div class="history-label">Inside</div>
                            <div class="history-value"><?php echo number_format($history_summary['inside']); ?></div>
                        </div>
                        <div class="history-card">
                            <div class="history-label">Entries</div>
                            <div class="history-value"><?php echo number_format($history_summary['entries']); ?></div>
                        </div>
                        <div class="history-card">
                            <div class="history-label">Re-entries</div>
                            <div class="history-value"><?php echo number_format($history_summary['reentries']); ?></div>
                        </div>
                        <div class="history-card">
                            <div class="history-label">Exits</div>
                            <div class="history-value"><?php echo number_format($history_summary['exits']); ?></div>
                        </div>
                        <div class="history-card">
                            <div class="history-label">Net Income</div>
                            <div class="history-value">₱<?php echo number_format($history_summary['net_income'], 2); ?></div>
                        </div>
                    </div>
                <?php else: ?>
                    <p class="empty-state">No record found for that date yet.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- ACTIONS -->
        <div class="section">
            <div class="buttons">
                <button class="btn-soft btn-primary" onclick="exportPDF()">📄 Export PDF</button>
                <button class="btn-soft btn-primary" onclick="exportExcel()">📊 Export Excel</button>
                <button class="btn-soft btn-warning" onclick="startDemoDay()">▶ Start Demo Day</button>
                <button class="btn-soft btn-primary" onclick="closeDay()">🔒 Close Day</button>
                <button class="btn-soft btn-danger" onclick="resetDemoData()">🧹 Reset Demo Records</button>
            </div>
        </div>
    </div>

    <script>
    // Auto-refresh every 5 seconds
    async function updateDashboard() {
        try {
            const response = await fetch('api.php?action=get_stats');
            const data = await response.json();
            
            if (data.success) {
                const hc = data.headcount;
                const fin = data.financials;
                const clos = data.closing || {};
                
                // Update headcount metrics
                document.getElementById('metric-inside').textContent = (hc.inside || 0).toLocaleString('en-US');
                document.getElementById('metric-entries').textContent = (hc.entries || 0).toLocaleString('en-US');
                document.getElementById('metric-exits').textContent = (hc.exits || 0).toLocaleString('en-US');
                
                // Update financial metrics
                document.getElementById('metric-gross').textContent = '₱' + (fin.gross_cash || 0).toLocaleString('en-US', {maximumFractionDigits: 0});
                document.getElementById('metric-expenses').textContent = '₱' + ((fin.direct_expenses || 0) + (fin.operational_expenses || 0)).toLocaleString('en-US', {maximumFractionDigits: 0});
                document.getElementById('metric-profit').textContent = '₱' + (fin.net_income || 0).toLocaleString('en-US', {maximumFractionDigits: 0});
                
                // Update income categories
                for (const [key, value] of Object.entries(fin.income)) {
                    const element = document.querySelector('.income-' + key);
                    if (element) {
                        element.textContent = '₱' + value.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                    }
                }
                document.getElementById('income-total').textContent = '₱' + fin.total_income.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                
                // Update expense categories
                for (const [key, value] of Object.entries(fin.expenses)) {
                    const element = document.querySelector('.expense-' + key);
                    if (element) {
                        element.textContent = '₱' + value.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                    }
                }
                document.getElementById('expense-total').textContent = '₱' + fin.total_expenses.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                
                // Update closing summary if available
                if (clos && Object.keys(clos).length > 0) {
                    document.getElementById('close-beginning').textContent = '₱' + (clos.beginning_balance || 0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                    document.getElementById('close-sultada').textContent = '₱' + (clos.sultada_net || 0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                    document.getElementById('close-pulta').textContent = '₱' + (clos.pulta_total || 0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                    document.getElementById('close-balanced').textContent = '₱' + ((clos.cash_balanced ?? clos.gross ?? 0)).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                    document.getElementById('close-direct').textContent = '₱' + (clos.direct_expenses || 0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                    document.getElementById('close-gross').textContent = '₱' + ((clos.gross_cash ?? clos.gross ?? 0)).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                    document.getElementById('close-less').textContent = '₱' + (clos.direct_expenses || 0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                    document.getElementById('close-income').textContent = '₱' + (clos.income_after_expenses || 0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                    document.getElementById('close-additional').textContent = '₱' + (clos.additional_income || 0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                    document.getElementById('close-operational').textContent = '₱' + (clos.operational_expenses || 0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                    document.getElementById('close-net').textContent = '₱' + (clos.net_income || 0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                    document.getElementById('close-status').textContent = (clos.status || 'ongoing').toUpperCase();
                }
                
                // Update last updated timestamp
                const now = new Date();
                document.getElementById('last-updated').textContent = now.toLocaleTimeString();
            }
        } catch (error) {
            console.error('Error updating dashboard:', error);
        }
    }

    // Initial update on page load
    updateDashboard();
    
    // Auto-refresh every 5 seconds
    setInterval(updateDashboard, 5000);

    function refreshDashboardView() {
        window.location.reload();
    }

    async function startDemoDay() {
        try {
            const response = await fetch('api.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'action=start_demo_day'
            });
            const data = await response.json();

            if (data.success) {
                alert(data.message || 'Demo day started successfully.');
                window.location.reload();
            } else {
                alert(data.error || 'Unable to start demo day.');
            }
        } catch (error) {
            console.error('Unable to start demo day', error);
            alert('Unable to start demo day.');
        }
    }

    function exportPDF() {
        window.location.href = 'export_pdf.php';
    }

    function exportExcel() {
        window.location.href = 'export_excel.php';
    }

    function resetDemoData() {
        if (!confirm('This will clear all attendance, income, and expense records for today. Continue?')) {
            return;
        }

        fetch('api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: 'action=reset_demo_data'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message || 'Demo data has been reset.');
                location.reload();
            } else {
                alert(data.error || 'Unable to reset demo data.');
            }
        })
        .catch(() => {
            alert('Failed to reset demo data.');
        });
    }

    function closeDay() {
        if (!confirm('Finalize today\'s report and lock the day for additional entries?')) {
            return;
        }

        fetch('api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: 'action=close_day'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message || 'Day closed successfully.');
                location.reload();
            } else {
                alert(data.error || 'Unable to close the day.');
            }
        })
        .catch(() => {
            alert('Failed to close the day.');
        });
    }
    </script>
</body>
</html>