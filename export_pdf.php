<?php
require 'config.php';
requireLogin();

$headcount = getTodayHeadcount();
$financials = getTodayFinancials();
$closing = getTodayClosingSummary();

$today = date('Y-m-d');
$formatted_date = date('F d, Y');

// Generate HTML content
$html = "
<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Kantayoktok Sporting and Cockpit Arena Daily Report</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
        .header { text-align: center; border-bottom: 2px solid #333; padding-bottom: 10px; margin-bottom: 20px; }
        h1 { margin: 0; color: #333; }
        .subtitle { color: #666; font-size: 14px; }
        .date { color: #666; font-size: 12px; margin-top: 10px; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th { background: #f0f0f0; padding: 10px; text-align: left; border: 1px solid #ddd; font-weight: bold; }
        td { padding: 10px; border: 1px solid #ddd; }
        .amount { text-align: right; }
        .total-row { background: #f9f9f9; font-weight: bold; }
        .section-title { font-size: 14px; font-weight: bold; color: #333; margin-top: 20px; margin-bottom: 10px; background: #f0f0f0; padding: 5px; }
        .summary-section { margin: 20px 0; }
        .summary-row { display: flex; justify-content: space-between; padding: 10px; border-bottom: 1px solid #ddd; }
        .summary-label { font-weight: bold; }
        .summary-value { text-align: right; font-weight: bold; }
        .profit-section { background: #fffbf0; padding: 15px; border-radius: 5px; margin: 20px 0; }
        .distribution { display: flex; justify-content: space-around; margin: 15px 0; }
        .dist-item { text-align: center; }
        .dist-label { font-size: 12px; color: #666; }
        .dist-value { font-size: 20px; font-weight: bold; color: #ffc107; }
    </style>
</head>
<body>
    <div class='header'>
        <h1>🐓 Kantayoktok Sporting and Cockpit Arena - Daily Monitoring Report</h1>
        <div class='subtitle'>Complete Financial & Attendance Summary</div>
        <div class='date'>Date: " . $formatted_date . "</div>
    </div>

    <!-- HEADCOUNT SECTION -->
    <div class='summary-section'>
        <div class='section-title'>📊 ATTENDANCE / HEADCOUNT</div>
        <table>
            <tr>
                <th>Metric</th>
                <th class='amount'>Count</th>
            </tr>
            <tr>
                <td>Total People Entered</td>
                <td class='amount'>" . $headcount['entries'] . "</td>
            </tr>
            <tr>
                <td>Total People Exited</td>
                <td class='amount'>" . $headcount['exits'] . "</td>
            </tr>
            <tr class='total-row'>
                <td>Currently Inside Venue</td>
                <td class='amount'>" . $headcount['inside'] . "</td>
            </tr>
        </table>
    </div>

    <!-- INCOME SECTION -->
    <div class='summary-section'>
        <div class='section-title'>💵 INCOME BREAKDOWN</div>
        <table>
            <tr>
                <th>Category</th>
                <th class='amount'>Amount</th>
            </tr>";

$income_labels = [
    'sultada' => 'Sultada (Bet)',
    'pulta' => 'Pulta (Wristband/Entry)',
    'dresser' => 'Dresser Fees',
    'tax_admin' => 'Tax/Admin (per Manok)',
    'parking' => 'Parking Fee',
    'space_rental' => 'Space Rental',
    'other' => 'Other Income'
];

foreach ($income_labels as $key => $label) {
    $amount = $financials['income'][$key] ?? 0;
    $html .= "<tr><td>$label</td><td class='amount'>₱" . number_format($amount, 2) . "</td></tr>";
}

$html .= "
            <tr class='total-row'>
                <td>TOTAL INCOME</td>
                <td class='amount'>₱" . number_format($financials['total_income'], 2) . "</td>
            </tr>
        </table>
    </div>

    <!-- EXPENSE SECTION -->
    <div class='summary-section'>
        <div class='section-title'>📉 EXPENSE BREAKDOWN</div>
        <table>
            <tr>
                <th>Category</th>
                <th class='amount'>Amount</th>
            </tr>";

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
    $amount = $financials['expenses'][$key] ?? 0;
    $html .= "<tr><td>$label</td><td class='amount'>₱" . number_format($amount, 2) . "</td></tr>";
}

$html .= "
            <tr class='total-row'>
                <td>TOTAL EXPENSES</td>
                <td class='amount'>₱" . number_format($financials['total_expenses'], 2) . "</td>
            </tr>
        </table>
    </div>

    <!-- FINANCIAL SUMMARY -->
    <div class='profit-section'>
        <div class='section-title'>📈 FINANCIAL SUMMARY</div>
        <div class='summary-row'>
            <span class='summary-label'>Gross Cash:</span>
            <span class='summary-value'>₱" . number_format($closing['gross_cash'], 2) . "</span>
        </div>
        <div class='summary-row'>
            <span class='summary-label'>Total Expenses:</span>
            <span class='summary-value'>₱" . number_format($closing['total_expenses'], 2) . "</span>
        </div>
        <div class='summary-row' style='border-bottom: 3px solid #333; font-size: 16px;'>
            <span class='summary-label'>NET INCOME:</span>
            <span class='summary-value' style='color: #ffc107;'>₱" . number_format($closing['net_income'], 2) . "</span>
        </div>
    </div>

    <!-- DAILY CLOSING -->
    <div class='summary-section'>
        <div class='section-title'>📌 DAILY CLOSING REPORT</div>
        <table>
            <tr>
                <th>Item</th>
                <th class='amount'>Amount</th>
            </tr>
            <tr>
                <td>Organizer Share (60%)</td>
                <td class='amount'>₱" . number_format($closing['organizer_share'], 2) . "</td>
            </tr>
            <tr>
                <td>Barangay Share (40%)</td>
                <td class='amount'>₱" . number_format($closing['barangay_share'], 2) . "</td>
            </tr>
            <tr>
                <td>Status</td>
                <td class='amount'>" . ucfirst($closing['status']) . "</td>
            </tr>
        </table>
    </div>

    <!-- DISTRIBUTION -->
    <div class='summary-section'>
        <div class='section-title'>💰 DISTRIBUTION</div>
        <div class='distribution'>
            <div class='dist-item'>
                <div class='dist-label'>60% Share</div>
                <div class='dist-value'>₱" . number_format($closing['organizer_share'], 2) . "</div>
            </div>
            <div class='dist-item'>
                <div class='dist-label'>40% Share</div>
                <div class='dist-value'>₱" . number_format($closing['barangay_share'], 2) . "</div>
            </div>
        </div>
    </div>

    <div style='text-align: center; margin-top: 30px; color: #999; font-size: 12px;'>
        <p>Generated: " . date('Y-m-d H:i:s') . "</p>
        <p>Kantayoktok Sporting and Cockpit Arena Daily Monitoring System</p>
    </div>

</body>
</html>";

// Output as file download
header('Content-Type: text/html; charset=utf-8');
header('Content-Disposition: attachment; filename="Kantayoktok-Daily-Report-' . $today . '.html"');
echo $html;
?>