<?php
require 'config.php';
requireLogin();

$headcount = getTodayHeadcount();
$financials = getTodayFinancials();
$closing = getTodayClosingSummary();

$today = date('Y-m-d');
$formatted_date = date('F d, Y');

$income_labels = [
    'sultada' => 'Sultada (Bet)',
    'pulta' => 'Pulta (Wristband/Entry)',
    'dresser' => 'Dresser Fees',
    'tax_admin' => 'Tax/Admin (per Manok)',
    'parking' => 'Parking Fee',
    'space_rental' => 'Space Rental',
    'other' => 'Other Income'
];

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

$style = "
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; color: #111; }
        table { border-collapse: collapse; width: 100%; font-size: 12px; }
        th, td { border: 1px solid #333; padding: 6px 8px; vertical-align: top; }
        .title { font-size: 22px; font-weight: bold; text-align: left; }
        .header-row { background: #f5d94d; font-weight: bold; }
        .subhead { background: #9bd3ff; font-weight: bold; }
        .section { background: #d7f0ff; font-weight: bold; }
        .muted { color: #555; }
        .amount { text-align: right; }
        .total { background: #f5d94d; font-weight: bold; }
        .footer { font-weight: bold; text-align: center; padding-top: 12px; }
    </style>
";

$html = "
<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Kantayoktok Sporting and Cockpit Arena Daily Monitoring Report</title>
    {$style}
</head>
<body>
    <table>
        <tr>
            <td colspan='3' class='title'>KANTAYOKTOK SPORTING AND COCKPIT ARENA</td>
        </tr>
        <tr>
            <td colspan='3' class='muted'>Daily Monitoring Report</td>
        </tr>
        <tr>
            <td class='header-row'>Date</td>
            <td colspan='2'>{$formatted_date}</td>
        </tr>
        <tr>
            <td class='header-row'>Generated</td>
            <td colspan='2'>" . date('Y-m-d H:i:s') . "</td>
        </tr>
    </table>
    <br>

    <table>
        <tr class='section'>
            <td colspan='3'>ATTENDANCE / HEADCOUNT</td>
        </tr>
        <tr class='subhead'>
            <th>Metric</th>
            <th>Count</th>
            <th></th>
        </tr>
        <tr>
            <td>Total People Entered</td>
            <td class='amount'>{$headcount['entries']}</td>
            <td></td>
        </tr>
        <tr>
            <td>Total People Exited</td>
            <td class='amount'>{$headcount['exits']}</td>
            <td></td>
        </tr>
        <tr>
            <td>Currently Inside Venue</td>
            <td class='amount'>{$headcount['inside']}</td>
            <td></td>
        </tr>
    </table>
    <br>

    <table>
        <tr class='section'>
            <td colspan='3'>INCOME BREAKDOWN</td>
        </tr>
        <tr class='subhead'>
            <th>Category</th>
            <th>Amount</th>
            <th></th>
        </tr>
";

foreach ($income_labels as $key => $label) {
    $amount = $financials['income'][$key] ?? 0;
    $html .= "<tr><td>{$label}</td><td class='amount'>₱" . number_format($amount, 2) . "</td><td></td></tr>";
}

$html .= "
        <tr class='total'>
            <td>Total Income</td>
            <td class='amount'>₱" . number_format($financials['total_income'], 2) . "</td>
            <td></td>
        </tr>
    </table>
    <br>

    <table>
        <tr class='section'>
            <td colspan='3'>EXPENSE BREAKDOWN</td>
        </tr>
        <tr class='subhead'>
            <th>Category</th>
            <th>Amount</th>
            <th></th>
        </tr>
";

foreach ($expense_labels as $key => $label) {
    $amount = $financials['expenses'][$key] ?? 0;
    $html .= "<tr><td>{$label}</td><td class='amount'>₱" . number_format($amount, 2) . "</td><td></td></tr>";
}

$html .= "
        <tr class='total'>
            <td>Total Expenses</td>
            <td class='amount'>₱" . number_format($financials['total_expenses'], 2) . "</td>
            <td></td>
        </tr>
    </table>
    <br>

    <table>
        <tr class='section'>
            <td colspan='3'>FINANCIAL SUMMARY</td>
        </tr>
        <tr>
            <td>Gross Cash</td>
            <td class='amount'>₱" . number_format($closing['gross_cash'], 2) . "</td>
            <td></td>
        </tr>
        <tr>
            <td>Total Expenses</td>
            <td class='amount'>₱" . number_format($closing['total_expenses'], 2) . "</td>
            <td></td>
        </tr>
        <tr class='total'>
            <td>Net Income</td>
            <td class='amount'>₱" . number_format($closing['net_income'], 2) . "</td>
            <td></td>
        </tr>
        <tr>
            <td>Organizer Share (60%)</td>
            <td class='amount'>₱" . number_format($closing['organizer_share'], 2) . "</td>
            <td></td>
        </tr>
        <tr>
            <td>Barangay Share (40%)</td>
            <td class='amount'>₱" . number_format($closing['barangay_share'], 2) . "</td>
            <td></td>
        </tr>
        <tr>
            <td>Status</td>
            <td colspan='2'>" . ucfirst($closing['status']) . "</td>
        </tr>
    </table>
    <div class='footer'>Prepared by: Kantayoktok Management</div>
</body>
</html>
";

header('Content-Type: application/vnd.ms-excel; charset=utf-8');
header('Content-Disposition: attachment; filename="Kantayoktok-Daily-Report-' . $today . '.xls"');
echo $html;
?>