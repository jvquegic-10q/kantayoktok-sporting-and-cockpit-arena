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

$html = "
<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Kantayoktok Sporting and Cockpit Arena | Daily Monitoring Report</title>
    <style>
        @page { size: A4 portrait; margin: 14mm; }
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            color: #111;
            background: #fff;
        }
        .sheet {
            width: 100%;
            max-width: 780px;
            margin: 0 auto;
            border: 2px solid #111;
            padding: 0;
            background: #fff;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
        }
        th, td {
            border: 1px solid #111;
            padding: 6px 8px;
            vertical-align: top;
            text-align: left;
        }
        .title-bar {
            background: #f4d748;
            font-weight: bold;
            font-size: 15px;
            text-align: center;
            min-height: 36px;
        }
        .sub-title {
            background: #5db9eb;
            font-weight: bold;
            text-align: center;
        }
        .section-title {
            background: #f3d65d;
            font-weight: bold;
            text-align: left;
        }
        .blue-row { background: #9dd9f9; }
        .light-row { background: #f5f5f5; }
        .muted { color: #333; }
        .amount { text-align: right; }
        .net { background: #f4d748; font-weight: bold; }
        .signature { text-align: center; padding-top: 20px; font-weight: bold; }
        .small { font-size: 11px; }
        .right-box {
            width: 42%;
            vertical-align: top;
            border-left: 2px solid #111;
        }
    </style>
</head>
<body>
    <div class='sheet'>
        <table>
            <tr>
                <td class='title-bar' style='width: 35%;'>DATE</td>
                <td class='title-bar' style='width: 30%;'>KANTAYOKTOK</td>
                <td class='title-bar' style='width: 35%;'>DAILY MONITORING</td>
            </tr>
            <tr>
                <td class='sub-title' style='font-size: 11px;'>" . date('d-M') . "</td>
                <td class='sub-title' style='font-size: 11px;'>" . $formatted_date . "</td>
                <td class='sub-title' style='font-size: 11px;'>" . date('Y') . "</td>
            </tr>
        </table>

        <table>
            <tr class='section-title'>
                <td colspan='3'>BEGINNING BALANCE (PREVIOUS DAY'S COH)</td>
            </tr>
            <tr>
                <td>Sultada Net</td>
                <td class='amount'>₱" . number_format($financials['income']['sultada'] ?? 0, 2) . "</td>
                <td></td>
            </tr>
            <tr>
                <td>Pulta</td>
                <td class='amount'>₱" . number_format($financials['income']['pulta'] ?? 0, 2) . "</td>
                <td></td>
            </tr>
            <tr>
                <td>Cash Balanced</td>
                <td class='amount'>₱" . number_format($closing['gross_cash'] ?? 0, 2) . "</td>
                <td></td>
            </tr>
        </table>

        <table>
            <tr class='section-title'>
                <td colspan='3'>EXPENSES</td>
            </tr>
";

foreach ($expense_labels as $key => $label) {
    $amount = $financials['expenses'][$key] ?? 0;
    $html .= "<tr><td>{$label}</td><td class='amount'>₱" . number_format($amount, 2) . "</td><td></td></tr>";
}

$html .= "
            <tr class='net'>
                <td>Total Expenses</td>
                <td class='amount'>₱" . number_format($financials['total_expenses'], 2) . "</td>
                <td></td>
            </tr>
        </table>

        <table>
            <tr class='section-title'>
                <td colspan='3'>INCOME</td>
            </tr>
";

foreach ($income_labels as $key => $label) {
    $amount = $financials['income'][$key] ?? 0;
    $html .= "<tr><td>{$label}</td><td class='amount'>₱" . number_format($amount, 2) . "</td><td></td></tr>";
}

$html .= "
            <tr class='net'>
                <td>Total Income</td>
                <td class='amount'>₱" . number_format($financials['total_income'], 2) . "</td>
                <td></td>
            </tr>
        </table>

        <table>
            <tr class='section-title'>
                <td colspan='3'>DISTRIBUTION</td>
            </tr>
            <tr>
                <td>60% Kantayoktok</td>
                <td class='amount'>₱" . number_format($closing['organizer_share'], 2) . "</td>
                <td></td>
            </tr>
            <tr>
                <td>40% Brgy. Inapaka</td>
                <td class='amount'>₱" . number_format($closing['barangay_share'], 2) . "</td>
                <td></td>
            </tr>
        </table>

        <table>
            <tr class='net'>
                <td>NET INCOME</td>
                <td class='amount'>₱" . number_format($closing['net_income'], 2) . "</td>
            </tr>
            <tr>
                <td colspan='2' class='signature'>ALENA OLANO</td>
            </tr>
            <tr>
                <td colspan='2' class='small signature'>(OIC)</td>
            </tr>
            <tr>
                <td colspan='2' class='signature' style='padding-top: 18px;'>AUTHORIZED SIGNATURE</td>
            </tr>
            <tr>
                <td colspan='2' class='small signature'>(LIQUIDATOR)</td>
            </tr>
        </table>
    </div>

    <script>
        window.onload = function () {
            setTimeout(function () {
                window.print();
            }, 200);
        };
    </script>
</body>
</html>
";

header('Content-Type: text/html; charset=utf-8');
header('Content-Disposition: attachment; filename="Kantayoktok-Daily-Report-' . $today . '.html"');
echo $html;
?>