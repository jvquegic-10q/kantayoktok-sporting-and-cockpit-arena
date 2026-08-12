<?php
require 'config.php';
requireLogin();

header('Content-Type: application/json');

$action = $_GET['action'] ?? $_POST['action'] ?? '';

// ADD ENTRY/EXIT/RE-ENTRY (Guard)
if ($action === 'add_entry') {
    $type = strtolower(trim((string) ($_POST['type'] ?? 'entry')));
    $quantity_raw = $_POST['quantity'] ?? 1;
    $quantity = (int) $quantity_raw;
    $event_id = getTodayEvent();

    if (!in_array($type, ['entry', 'reentry', 'exit'], true)) {
        echo json_encode(['success' => false, 'error' => 'Invalid entry type.']);
    } elseif ($quantity < 1) {
        echo json_encode(['success' => false, 'error' => 'Quantity must be at least 1.']);
    } elseif (getTodayEventStatus() === 'closed') {
        echo json_encode(['success' => false, 'error' => 'This day has already been closed. Reset demo data to start again.']);
    } else {
        $headcount = getTodayHeadcount();

        if ($type === 'exit' && $headcount['inside'] < $quantity) {
            echo json_encode(['success' => false, 'error' => 'Not enough people are currently inside to record that many exits.']);
        } else {
            $success = true;
            for ($i = 0; $i < $quantity; $i++) {
                if (!$conn->query("INSERT INTO attendance (event_id, type) VALUES ($event_id, '$type')")) {
                    $success = false;
                    break;
                }
            }

            if ($success) {
                $headcount = getTodayHeadcount();
                echo json_encode([
                    'success' => true,
                    'inside' => $headcount['inside'],
                    'entries' => $headcount['entries'],
                    'reentries' => $headcount['reentries'],
                    'exits' => $headcount['exits']
                ]);
            } else {
                echo json_encode(['success' => false, 'error' => $conn->error]);
            }
        }
    }
}

// RECORD INCOME 
elseif ($action === 'record_income') {
    $category = trim((string) ($_POST['category'] ?? ''));
    $amount_raw = $_POST['amount'] ?? 0;
    $amount = is_numeric($amount_raw) ? floatval($amount_raw) : NAN;
    $event_id = getTodayEvent();

    if (getTodayEventStatus() === 'closed') {
        echo json_encode(['success' => false, 'error' => 'This day has already been closed.']);
    } elseif ($category !== '' && is_finite($amount) && $amount >= 0 && $amount <= 9999999999) {
        $safe_category = $conn->real_escape_string($category);
        $result = $conn->query("INSERT INTO income (event_id, category, amount) VALUES ($event_id, '$safe_category', $amount)");

        if ($result) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => $conn->error]);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid amount or category. Use a valid non-negative value.']);
    }
}

// RECORD EXPENSE
elseif ($action === 'record_expense') {
    $category = trim((string) ($_POST['category'] ?? ''));
    $amount_raw = $_POST['amount'] ?? 0;
    $amount = is_numeric($amount_raw) ? floatval($amount_raw) : NAN;
    $event_id = getTodayEvent();

    if (getTodayEventStatus() === 'closed') {
        echo json_encode(['success' => false, 'error' => 'This day has already been closed.']);
    } elseif ($category !== '' && is_finite($amount) && $amount >= 0 && $amount <= 9999999999) {
        $safe_category = $conn->real_escape_string($category);
        $result = $conn->query("INSERT INTO expenses (event_id, category, amount) VALUES ($event_id, '$safe_category', $amount)");

        if ($result) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => $conn->error]);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid amount or category. Use a valid non-negative value.']);
    }
}

// RESET DEMO DATA (Admin only)
elseif ($action === 'reset_demo_data') {
    requireRole('admin');

    if (resetTodayDemoData()) {
        echo json_encode([
            'success' => true,
            'message' => 'Demo records for today have been reset.'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'Unable to reset demo records: ' . $conn->error
        ]);
    }
}

elseif ($action === 'start_demo_day') {
    requireRole('admin');

    if (startDemoDay()) {
        echo json_encode([
            'success' => true,
            'message' => 'Demo day has been started for today.'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'Unable to start demo day: ' . $conn->error
        ]);
    }
}

// SAVE MULTIPLE FINANCE ENTRIES AT ONCE (Cashier)
elseif ($action === 'save_finance_batch') {
    requireRole('cashier');

    if (getTodayEventStatus() === 'closed') {
        echo json_encode(['success' => false, 'error' => 'This day has already been closed.']);
    } else {
        $income_data = json_decode($_POST['income'] ?? '[]', true);
        $expense_data = json_decode($_POST['expense'] ?? '[]', true);

        if (!is_array($income_data)) {
            $income_data = [];
        }
        if (!is_array($expense_data)) {
            $expense_data = [];
        }

        if (replaceTodayFinanceBatch($income_data, $expense_data)) {
            echo json_encode(['success' => true, 'message' => 'Finance records saved successfully.']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Unable to save finance records.']);
        }
    }
}

elseif ($action === 'close_day') {
    requireRole('admin');

    if (closeTodayEvent()) {
        echo json_encode(['success' => true, 'message' => 'Today has been closed successfully.']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Unable to close the day.']);
    }
}

// GET STATS (for auto-update on dashboard)
elseif ($action === 'get_stats') {
    $headcount = getTodayHeadcount();
    $financials = getTodayFinancials();
    $closing = getTodayClosingSummary();
    
    echo json_encode([
        'success' => true,
        'headcount' => $headcount,
        'financials' => $financials,
        'closing' => $closing
    ]);
}

else {
    echo json_encode(['success' => false, 'error' => 'Unknown action']);
}

$conn->close();
?>