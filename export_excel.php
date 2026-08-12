<?php
require 'config.php';
requireLogin();

$headcount = getTodayHeadcount();
$financials = getTodayFinancials();
$closing = getTodayClosingSummary();

$today = date('Y-m-d');
$formatted_date = date('F d, Y');

// Create CSV content
$csv = "KANTAYOKTOK SPORTING AND COCKPIT ARENA DAILY MONITORING REPORT\n";
$csv .= "Date: " . $formatted_date . "\n";
$csv .= "Generated: " . date('Y-m-d H:i:s') . "\n\n";

// HEADCOUNT
$csv .= "ATTENDANCE / HEADCOUNT\n";
$csv .= "Metric,Count\n";
$csv .= "Total People Entered," . $headcount['entries'] . "\n";
$csv .= "Total People Exited," . $headcount['exits'] . "\n";
$csv .= "Currently Inside Venue," . $headcount['inside'] . "\n\n";

// INCOME
$csv .= "INCOME BREAKDOWN\n";
$csv .= "Category,Amount\n";

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
    $csv .= "\"" . $label . "\"," . number_format($amount, 2) . "\n";
}

$csv .= "TOTAL INCOME," . number_format($financials['total_income'], 2) . "\n\n";

// EXPENSES
$csv .= "EXPENSE BREAKDOWN\n";
$csv .= "Category,Amount\n";

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
    $csv .= "\"" . $label . "\"," . number_format($amount, 2) . "\n";
}

$csv .= "TOTAL EXPENSES," . number_format($financials['total_expenses'], 2) . "\n\n";

// SUMMARY
$csv .= "FINANCIAL SUMMARY\n";
$csv .= "Gross Cash," . number_format($closing['gross_cash'], 2) . "\n";
$csv .= "Total Expenses," . number_format($closing['total_expenses'], 2) . "\n";
$csv .= "NET INCOME," . number_format($closing['net_income'], 2) . "\n\n";

// DAILY CLOSING
$csv .= "DAILY CLOSING REPORT\n";
$csv .= "Organizer Share (60%)," . number_format($closing['organizer_share'], 2) . "\n";
$csv .= "Barangay Share (40%)," . number_format($closing['barangay_share'], 2) . "\n";
$csv .= "Status," . ucfirst($closing['status']) . "\n\n";

// DISTRIBUTION
$csv .= "DISTRIBUTION\n";
$csv .= "60% Share," . number_format($closing['organizer_share'], 2) . "\n";
$csv .= "40% Share," . number_format($closing['barangay_share'], 2) . "\n";

// Output as Excel file
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="Sabong-Daily-Report-' . $today . '.csv"');
echo $csv;
?>