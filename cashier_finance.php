<?php
require 'config.php';
requireLogin();
requireRole('cashier');

$financials = getTodayFinancials();
$day_status = getTodayEventStatus();
$guard_entry_count = getTodayHeadcount()['entries'];
$default_pulta_qty = max(0, (int) $guard_entry_count);
$default_pulta_total = $default_pulta_qty * 100;
?>

<!DOCTYPE html>
<html>
<head>
    <title>Finance Recording - Kantayoktok Sporting and Cockpit Arena</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="cashier.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="header-left">
                <h1>💰 Finance Recording</h1>
                <p class="user-info">Logged in as: <strong><?php echo $_SESSION['full_name']; ?></strong></p>
                <p style="font-size: 12px; color: #999; margin-top: 0.5rem;">Last updated: <span id="last-updated">now</span></p>
            </div>
            <div class="header-right" style="display:flex;align-items:center;gap:0.75rem;flex-wrap:wrap;">
                <p class="status-badge <?php echo ($day_status === 'closed') ? 'status-closed' : 'status-open'; ?>"><?php echo ($day_status === 'closed') ? 'Day Closed' : 'Day Open'; ?></p>
                <button type="button" class="refresh-btn" onclick="window.location.reload()">↻ Refresh</button>
                <a href="logout.php" class="logout-btn">🚪 Logout</a>
            </div>
        </div>

        <div class="section">
            <div class="summary-inline">
                <div class="mini-box">
                    <div class="mini-label">Gross Cash</div>
                    <div class="mini-value positive" id="gross-cash">₱<?php echo number_format($financials['gross_cash'], 2); ?></div>
                </div>
                <div class="mini-box">
                    <div class="mini-label">Direct + Ops</div>
                    <div class="mini-value negative" id="direct-ops">₱<?php echo number_format(($financials['direct_expenses'] + $financials['operational_expenses']), 2); ?></div>
                </div>
                <div class="mini-box">
                    <div class="mini-label">Net</div>
                    <div class="mini-value" style="color: #d97706;" id="net-value">₱<?php echo number_format($financials['net_income'], 2); ?></div>
                </div>
            </div>
        </div>

        <div class="section">
            <div class="helper-text" style="font-size: 14px; color: #475569; margin-top: 0;">
                The Pulta (entry) quantity automatically syncs with guard entries. Update other amounts and save to finalize today's financial record.
            </div>
        </div>

        <form id="financeForm" onsubmit="event.preventDefault(); saveFinanceList();">
            <div class="section">
                <h2>🏁 Beginning Balance</h2>
                <div class="record-row">
                    <div class='record-label'>Beginning Balance (Previous day COH)</div>
                    <input class='record-input' type='number' name='beginning_balance' min='0' step='0.01' value='<?php echo number_format(DEFAULT_BEGINNING_BALANCE, 2, '.', ''); ?>' placeholder='0.00'>
                </div>
            </div>

            <div class="section">
                <h2>💵 Income Categories</h2>
                <div class="helper-text">Only amounts above zero will be saved.</div>
                <div class="record-list">
                    <?php
                    $income_labels = [
                        'sultada' => ['label' => 'Sultada (Bet)', 'type' => 'total'],
                        'pulta' => ['label' => 'Pulta (Wristband/Entry)', 'type' => 'qty_rate'],
                        'dresser' => ['label' => 'Dresser Fees', 'type' => 'total'],
                        'tax_admin' => ['label' => 'Tax/Admin (per Manok)', 'type' => 'qty_rate'],
                        'parking' => ['label' => 'Parking Fee', 'type' => 'qty_rate'],
                        'space_rental' => ['label' => 'Space Rental', 'type' => 'qty_rate'],
                        'other' => ['label' => 'Other Income', 'type' => 'total']
                    ];
                    foreach ($income_labels as $key => $meta) {
                        $label = $meta['label'];
                        $type = $meta['type'];
                        $value = isset($financials['income'][$key]) ? number_format($financials['income'][$key], 2, '.', '') : '';
                        echo "<div class='record-row'>";
                        echo "<div class='record-label'>$label</div>";

                        if ($type === 'qty_rate') {
                            $default_rate = ($key === 'pulta') ? '100' : '';
                            $default_qty = ($key === 'pulta') ? htmlspecialchars((string) $default_pulta_qty, ENT_QUOTES) : '';
                            $default_total = ($key === 'pulta') ? number_format($default_pulta_total, 2, '.', '') : $value;
                            echo "<div class='record-amount-group'>";
                            echo "<div class='record-inline-field'><label>Qty</label><input class='record-input qty-input' type='number' min='0' step='1' data-row='$key' data-kind='qty' placeholder='0' value='" . $default_qty . "' " . ($key === 'pulta' ? 'readonly' : '') . "></div>";
                            echo "<div class='record-inline-field'><label>Rate</label><input class='record-input rate-input' type='number' min='0' step='0.01' data-row='$key' data-kind='rate' placeholder='0.00' value='" . htmlspecialchars($default_rate, ENT_QUOTES) . "'></div>";
                            echo "<div class='record-inline-field'><label>Total</label><input class='record-input total-input' type='number' name='income[$key]' min='0' step='0.01' value='" . htmlspecialchars($default_total, ENT_QUOTES) . "' placeholder='0.00'></div>";
                            echo "</div>";
                        } else {
                            echo "<input class='record-input' type='number' name='income[$key]' min='0' step='0.01' value='" . htmlspecialchars($value, ENT_QUOTES) . "' placeholder='0.00'>";
                        }

                        echo "</div>";
                    }
                    ?>
                </div>
            </div>

            <div class="section">
                <h2>📉 Expense Categories</h2>
                <div class="helper-text">Edit any amount and save to replace all current expense totals for today.</div>
                <div class="record-list">
                    <?php
                    $expense_labels = [
                        'amusement_tax' => 'Amusement Tax (10%)',
                        'permit' => 'Special Permit',
                        'payroll' => 'Payroll',
                        'meals' => 'Meals',
                        'fuel' => 'Fuel',
                        'materials' => 'Materials',
                        'utilities' => 'Utilities',
                        'labor' => 'Labor',
                        'promo' => 'Promo'
                    ];
                    foreach ($expense_labels as $key => $label) {
                        $value = isset($financials['expenses'][$key]) ? number_format($financials['expenses'][$key], 2, '.', '') : '';
                        echo "<div class='record-row'>";
                        echo "<div class='record-label'>$label</div>";
                        echo "<input class='record-input' type='number' name='expense[$key]' min='0' step='0.01' value='" . htmlspecialchars($value, ENT_QUOTES) . "' placeholder='0.00'>";
                        echo "</div>";
                    }
                    ?>
                </div>

                <div class="save-bar">
                    <button class="secondary-btn" type="button" onclick="resetForm()">↺ Reset Values</button>
                    <button class="save-btn" type="submit">💾 Save All Records</button>
                </div>
            </div>
        </form>

        <div class="section">
            <h2>📊 Today's Summary</h2>
            
            <div class="summary-box">
                <div class="summary-item">
                    <div class="summary-label">Total Income</div>
                    <div class="summary-value positive" id="total-income">₱<?php echo number_format($financials['total_income'], 2); ?></div>
                </div>
                <div class="summary-item">
                    <div class="summary-label">Total Expenses</div>
                    <div class="summary-value negative" id="total-expenses">₱<?php echo number_format($financials['total_expenses'], 2); ?></div>
                </div>
            </div>

            <div class="summary-box">
                <div class="summary-item" style="grid-column: 1 / -1;">
                    <div class="summary-label">Net Income</div>
                    <div class="summary-value" style="color: #ffc107; font-size: 24px;" id="final-net">₱<?php echo number_format($financials['net_income'], 2); ?></div>
                </div>
            </div>
        </div>

    </div>

    <script>
    function syncQtyRateTotal(rowKey) {
        const qtyInput = document.querySelector(`.qty-input[data-row="${rowKey}"]`);
        const rateInput = document.querySelector(`.rate-input[data-row="${rowKey}"]`);
        const totalInput = document.querySelector(`.total-input[name="income[${rowKey}]"]`);

        if (!qtyInput || !rateInput || !totalInput) return;

        const qty = Number(qtyInput.value || 0);
        const rate = Number(rateInput.value || 0);
        totalInput.value = (qty * rate).toFixed(2);
    }

    document.addEventListener('input', function (event) {
        const target = event.target;
        if (target.matches('.qty-input, .rate-input')) {
            const rowKey = target.dataset.row;
            if (rowKey === 'pulta' && target.classList.contains('rate-input') && target.value === '') {
                target.value = '100';
            }
            syncQtyRateTotal(rowKey);
        }
    });

    // Auto-refresh every 5 seconds
    async function autoRefresh() {
        try {
            const response = await fetch('api.php?action=get_stats');
            const data = await response.json();
            
            if (data.success) {
                const hc = data.headcount;
                const fin = data.financials;
                
                // Update Pulta Qty (auto-sync with guard entries)
                const pultaQtyInput = document.querySelector('.qty-input[data-row="pulta"]');
                if (pultaQtyInput) {
                    const currentQty = Number(pultaQtyInput.value) || 0;
                    const newQty = hc.entries || 0;
                    
                    if (currentQty !== newQty) {
                        pultaQtyInput.value = newQty;
                        syncQtyRateTotal('pulta');
                    }
                }
                
                // Update summary totals
                document.getElementById('gross-cash').textContent = '₱' + (fin.gross_cash || 0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                document.getElementById('direct-ops').textContent = '₱' + ((fin.direct_expenses || 0) + (fin.operational_expenses || 0)).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                document.getElementById('net-value').textContent = '₱' + (fin.net_income || 0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                
                document.getElementById('total-income').textContent = '₱' + fin.total_income.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                document.getElementById('total-expenses').textContent = '₱' + fin.total_expenses.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                document.getElementById('final-net').textContent = '₱' + fin.net_income.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                
                // Update timestamp
                const now = new Date();
                document.getElementById('last-updated').textContent = now.toLocaleTimeString();
            }
        } catch (error) {
            console.error('Error refreshing data:', error);
        }
    }

    // Refresh on page load and every 5 seconds
    autoRefresh();
    setInterval(autoRefresh, 5000);

    function resetForm() {
        const form = document.getElementById('financeForm');
        form.querySelectorAll('input').forEach(input => {
            input.value = input.defaultValue || '';
        });

        const pultaQty = document.querySelector('.qty-input[data-row="pulta"]');
        const pultaRate = document.querySelector('.rate-input[data-row="pulta"]');
        const pultaTotal = document.querySelector('.total-input[name="income[pulta]"]');

        if (pultaQty) {
            pultaQty.value = '<?php echo (int) $default_pulta_qty; ?>';
        }
        if (pultaRate) {
            pultaRate.value = '100';
        }
        if (pultaTotal) {
            pultaTotal.value = '<?php echo number_format($default_pulta_total, 2, '.', ''); ?>';
        }
    }

    let isSavingFinance = false;

    async function saveFinanceList() {
        const form = document.getElementById('financeForm');
        const dayStatus = '<?php echo $day_status; ?>';
        const saveButton = form?.querySelector('.save-btn');

        if (isSavingFinance) return;

        if (dayStatus === 'closed') {
            alert('This day has already been closed. Please contact the admin to reopen or reset the demo data.');
            return;
        }

        isSavingFinance = true;
        if (saveButton) {
            saveButton.disabled = true;
            saveButton.textContent = 'Saving...';
        }

        try {
            const incomeInputs = form.querySelectorAll('input[name^="income["]');
            const expenseInputs = form.querySelectorAll('input[name^="expense["]');
            const income = {};
            const expense = {};

            let hasValidEntry = false;

            incomeInputs.forEach(input => {
                const value = Number(input.value);
                if (input.value.trim() !== '' && Number.isFinite(value) && value >= 0) {
                    if (value > 0) {
                        income[input.name.replace(/^income\[|\]$/g, '')] = value;
                        hasValidEntry = true;
                    }
                } else if (input.value.trim() !== '') {
                    alert('Please enter valid non-negative numbers for income records.');
                    input.focus();
                    throw new Error('Invalid income value');
                }
            });

            expenseInputs.forEach(input => {
                const value = Number(input.value);
                if (input.value.trim() !== '' && Number.isFinite(value) && value >= 0) {
                    if (value > 0) {
                        expense[input.name.replace(/^expense\[|\]$/g, '')] = value;
                        hasValidEntry = true;
                    }
                } else if (input.value.trim() !== '') {
                    alert('Please enter valid non-negative numbers for expense records.');
                    input.focus();
                    throw new Error('Invalid expense value');
                }
            });

            if (!hasValidEntry) {
                alert('Please enter at least one amount before saving.');
                return;
            }

            const confirmed = confirm('Save these values as today\'s official financial record? This will replace the current daily totals.');
            if (!confirmed) {
                return;
            }

            const response = await fetch('api.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `action=save_finance_batch&income=${encodeURIComponent(JSON.stringify(income))}&expense=${encodeURIComponent(JSON.stringify(expense))}`
            });

            const data = await response.json();
            if (data.success) {
                alert('✓ Records saved successfully!');
                location.reload();
            } else {
                alert(data.error || 'Failed to save records.');
            }
        } catch (error) {
            console.error('Unable to save finance records:', error);
            alert('A network error occurred while saving.');
        } finally {
            isSavingFinance = false;
            if (saveButton) {
                saveButton.disabled = false;
                saveButton.textContent = '💾 Save All Records';
            }
        }
    }
    </script>
</body>
</html>