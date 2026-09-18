<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'sabong_new');

// Connect to database
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8");

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Default beginning balance for the venue report view.
// Used only when no prior day's cash-on-hand is available.
define('DEFAULT_BEGINNING_BALANCE', 0);

// Each event stores its own beginning balance (cash-on-hand carried in).
ensureEventBalanceColumn();

function ensureEventBalanceColumn() {
    global $conn;

    $result = $conn->query("SHOW COLUMNS FROM events LIKE 'beginning_balance'");
    if ($result && $result->num_rows > 0) {
        return true;
    }

    return $conn->query("ALTER TABLE events ADD COLUMN beginning_balance DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER status") !== false;
}

// Previous day's cash-on-hand = that day's net income (fallback to default).
function getPreviousDayCashOnHand($current_date) {
    global $conn;

    $safe_date = $conn->real_escape_string($current_date);
    $result = $conn->query("SELECT event_date FROM events WHERE event_date < '$safe_date' ORDER BY event_date DESC LIMIT 1");

    if (!$result || $result->num_rows === 0) {
        return DEFAULT_BEGINNING_BALANCE;
    }

    $row = $result->fetch_assoc();
    $summary = getDailySummaryForDate($row['event_date']);

    return $summary ? (float) $summary['net_income'] : DEFAULT_BEGINNING_BALANCE;
}

function getEventBeginningBalance($event_id) {
    global $conn;
    $event_id = (int) $event_id;
    $result = $conn->query("SELECT event_date, beginning_balance FROM events WHERE id = $event_id LIMIT 1");

    if (!$result || $result->num_rows === 0) {
        return DEFAULT_BEGINNING_BALANCE;
    }

    $row = $result->fetch_assoc();

    // Auto-carry the previous day's cash-on-hand until a value is explicitly saved.
    if ((float) $row['beginning_balance'] === 0.0) {
        return getPreviousDayCashOnHand($row['event_date']);
    }

    return (float) $row['beginning_balance'];
}

function setEventBeginningBalance($event_id, $amount) {
    global $conn;
    $event_id = (int) $event_id;
    $amount = (float) $amount;

    return $conn->query("UPDATE events SET beginning_balance = $amount WHERE id = $event_id") !== false;
}

// Check if user is logged in
function requireLogin() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: index.php');
        exit;
    }
}

// Check user role
function requireRole($required_role) {
    if (!isset($_SESSION['role'])) {
        header('Location: index.php');
        exit;
    }
    
    if ($_SESSION['role'] !== $required_role && $_SESSION['role'] !== 'admin') {
        die("⛔ Access Denied. You don't have permission to access this page.");
    }
}

// Get today's event
function getTodayEvent() {
    global $conn;
    $today = date('Y-m-d');

    $today_result = $conn->query("SELECT id FROM events WHERE event_date = '$today' LIMIT 1");

    if (!$today_result) {
        die("Database error: " . $conn->error);
    }

    if ($today_result->num_rows > 0) {
        $event_data = $today_result->fetch_assoc();
        return $event_data['id'];
    }

    $latest_result = $conn->query("SELECT event_date FROM events ORDER BY event_date DESC LIMIT 1");
    if ($latest_result && $latest_result->num_rows > 0) {
        $latest_event = $latest_result->fetch_assoc();
        if ($latest_event['event_date'] !== $today) {
            $create_result = $conn->query("INSERT INTO events (event_date, status) VALUES ('$today', 'ongoing')");
            if (!$create_result) {
                die("Error creating event: " . $conn->error);
            }
            return $conn->insert_id;
        }
    }

    $create_result = $conn->query("INSERT INTO events (event_date, status) VALUES ('$today', 'ongoing')");
    if (!$create_result) {
        die("Error creating event: " . $conn->error);
    }

    return $conn->insert_id;
}

// Get today's headcount
function getTodayHeadcount() {
    global $conn;
    $event_id = getTodayEvent();

    $entries_result = $conn->query("SELECT COUNT(*) as count FROM attendance WHERE event_id = $event_id AND type = 'entry'");
    $entries = $entries_result ? (int) $entries_result->fetch_assoc()['count'] : 0;

    $reentries_result = $conn->query("SELECT COUNT(*) as count FROM attendance WHERE event_id = $event_id AND type = 'reentry'");
    $reentries = $reentries_result ? (int) $reentries_result->fetch_assoc()['count'] : 0;

    $exits_result = $conn->query("SELECT COUNT(*) as count FROM attendance WHERE event_id = $event_id AND type = 'exit'");
    $exits = $exits_result ? (int) $exits_result->fetch_assoc()['count'] : 0;

    $inside = $entries + $reentries - $exits;

    return [
        'event_id' => $event_id,
        'entries' => $entries,
        'reentries' => $reentries,
        'exits' => $exits,
        'inside' => max(0, $inside)
    ];
}

function getTodayEventStatus() {
    global $conn;
    $today = date('Y-m-d');
    $result = $conn->query("SELECT status FROM events WHERE event_date = '$today' LIMIT 1");

    if (!$result || $result->num_rows === 0) {
        return 'ongoing';
    }

    $row = $result->fetch_assoc();
    return $row['status'] ?? 'ongoing';
}

function ensureFinanceCategoryTable() {
    global $conn;

    $sql = "CREATE TABLE IF NOT EXISTS finance_categories (
        id INT AUTO_INCREMENT PRIMARY KEY,
        type ENUM('income', 'expense') NOT NULL,
        name VARCHAR(120) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_finance_category (type, name)
    )";

    return $conn->query($sql);
}

ensureFinanceCategoryTable();

function getFinanceCategories($type) {
    global $conn;

    $type = strtolower(trim((string) $type));
    if (!in_array($type, ['income', 'expense'], true)) {
        return [];
    }

    $default_categories = [
        'income' => ['sultada', 'pulta', 'dresser', 'tax_admin', 'parking', 'space_rental', 'other'],
        'expense' => ['amusement_tax', 'permit', 'payroll', 'meals', 'fuel', 'materials', 'utilities', 'labor', 'promo']
    ];

    $result = $conn->query("SELECT name FROM finance_categories WHERE type = '$type' ORDER BY name ASC");
    $custom_categories = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $custom_categories[] = trim((string) $row['name']);
        }
    }

    $all = [];
    foreach ($default_categories[$type] as $category) {
        $all[] = ucfirst(str_replace('_', ' ', $category));
    }

    foreach ($custom_categories as $category) {
        if ($category !== '' && !in_array($category, $all, true)) {
            $all[] = $category;
        }
    }

    return $all;
}

function addFinanceCategory($type, $name) {
    global $conn;

    $type = strtolower(trim((string) $type));
    $name = trim((string) $name);

    if (!in_array($type, ['income', 'expense'], true) || $name === '') {
        return false;
    }

    $safe_type = $conn->real_escape_string($type);
    $safe_name = $conn->real_escape_string($name);

    $result = $conn->query("INSERT IGNORE INTO finance_categories (type, name) VALUES ('$safe_type', '$safe_name')");
    return $result !== false;
}

function getEventByDate($event_date) {
    global $conn;
    $safe_date = $conn->real_escape_string($event_date);
    $result = $conn->query("SELECT id, status FROM events WHERE event_date = '$safe_date' LIMIT 1");

    if (!$result || $result->num_rows === 0) {
        return null;
    }

    return $result->fetch_assoc();
}

function getDailySummaryForDate($event_date) {
    global $conn;

    $event = getEventByDate($event_date);
    if (!$event) {
        return null;
    }

    $event_id = (int) $event['id'];
    $beginning_balance = getEventBeginningBalance($event_id);

    $attendance_result = $conn->query("SELECT type, COUNT(*) as count FROM attendance WHERE event_id = $event_id GROUP BY type");
    $attendance_counts = ['entry' => 0, 'reentry' => 0, 'exit' => 0];
    if ($attendance_result) {
        while ($row = $attendance_result->fetch_assoc()) {
            $type = $row['type'] ?? '';
            if (isset($attendance_counts[$type])) {
                $attendance_counts[$type] = (int) $row['count'];
            }
        }
    }

    $income_result = $conn->query("SELECT category, SUM(amount) as total FROM income WHERE event_id = $event_id GROUP BY category");
    $income_data = [];
    if ($income_result) {
        while ($row = $income_result->fetch_assoc()) {
            $income_data[$row['category']] = (float) $row['total'];
        }
    }

    $expense_result = $conn->query("SELECT category, SUM(amount) as total FROM expenses WHERE event_id = $event_id GROUP BY category");
    $expense_data = [];
    if ($expense_result) {
        while ($row = $expense_result->fetch_assoc()) {
            $expense_data[$row['category']] = (float) $row['total'];
        }
    }

    $summary = buildFinancialSummary($income_data, $expense_data, $beginning_balance);
    $entries = $attendance_counts['entry'];
    $reentries = $attendance_counts['reentry'];
    $exits = $attendance_counts['exit'];

    return [
        'event_date' => $event_date,
        'status' => $event['status'] ?? 'ongoing',
        'entries' => $entries,
        'reentries' => $reentries,
        'exits' => $exits,
        'inside' => max(0, $entries + $reentries - $exits),
        'income' => $income_data,
        'expenses' => $expense_data,
        'beginning_balance' => $summary['beginning_balance'],
        'total_income' => $summary['total_income'],
        'total_expenses' => $summary['total_expenses'],
        'net_income' => $summary['net_income'],
        'gross_cash' => $summary['gross_cash'],
        'direct_expenses' => $summary['direct_expenses'],
        'operational_expenses' => $summary['operational_expenses'],
        'organizer_share' => $summary['organizer_share'],
        'barangay_share' => $summary['barangay_share']
    ];
}

function getRecentDailySummaries($days = 7) {
    global $conn;
    $days = max(1, (int) $days);
    $results = [];
    $start_date = date('Y-m-d', strtotime("-$days days"));
    $end_date = date('Y-m-d');

    $event_result = $conn->query("SELECT event_date, id, status FROM events WHERE event_date BETWEEN '$start_date' AND '$end_date' ORDER BY event_date DESC");
    if (!$event_result) {
        return $results;
    }

    while ($row = $event_result->fetch_assoc()) {
        $date = $row['event_date'];
        $summary = getDailySummaryForDate($date);
        if ($summary) {
            $results[$date] = $summary;
        }
    }

    return $results;
}

function reopenTodayEvent() {
    global $conn;
    $event_id = getTodayEvent();
    $result = $conn->query("UPDATE events SET status = 'ongoing' WHERE id = $event_id");
    return $result;
}

function closeTodayEvent() {
    global $conn;
    $event_id = getTodayEvent();
    $result = $conn->query("UPDATE events SET status = 'closed' WHERE id = $event_id");
    return $result;
}

function buildFinancialSummary(array $income_data, array $expense_data, $beginning_balance = DEFAULT_BEGINNING_BALANCE) {
    $normalize = static function ($value) {
        return is_numeric($value) ? (float) $value : 0.0;
    };

    $normalized_income = array_map($normalize, $income_data);
    $normalized_expenses = array_map($normalize, $expense_data);

    $known_income_categories = ['sultada', 'pulta', 'dresser', 'tax_admin', 'parking', 'space_rental', 'other'];
    $known_expense_categories = ['amusement_tax', 'permit', 'payroll', 'meals', 'fuel', 'materials', 'utilities', 'labor', 'promo'];

    $additional_income_categories = ['dresser', 'tax_admin', 'parking', 'space_rental', 'other'];
    $direct_expense_categories = ['amusement_tax', 'permit', 'payroll', 'meals'];
    $operational_expense_categories = ['fuel', 'materials', 'utilities', 'labor', 'promo'];

    $sultada_net = (float) ($normalized_income['sultada'] ?? 0.0);
    $pulta_total = (float) ($normalized_income['pulta'] ?? 0.0);
    $beginning_balance = (float) $beginning_balance;

    $additional_income = 0.0;
    foreach ($additional_income_categories as $category) {
        $additional_income += (float) ($normalized_income[$category] ?? 0.0);
    }

    foreach ($normalized_income as $category => $amount) {
        if (!in_array($category, $known_income_categories, true)) {
            $additional_income += (float) $amount;
        }
    }

    $direct_expenses = 0.0;
    foreach ($direct_expense_categories as $category) {
        $direct_expenses += (float) ($normalized_expenses[$category] ?? 0.0);
    }

    $operational_expenses = 0.0;
    foreach ($operational_expense_categories as $category) {
        $operational_expenses += (float) ($normalized_expenses[$category] ?? 0.0);
    }

    foreach ($normalized_expenses as $category => $amount) {
        if (!in_array($category, $known_expense_categories, true)) {
            $operational_expenses += (float) $amount;
        }
    }

    $gross_cash = $beginning_balance + $sultada_net + $pulta_total;
    $total_income = $sultada_net + $pulta_total + $additional_income;
    $total_expenses = $direct_expenses + $operational_expenses;
    $income_after_expenses = $gross_cash - $direct_expenses;
    $net_income = $income_after_expenses + $additional_income - $operational_expenses;

    return [
        'beginning_balance' => $beginning_balance,
        'sultada_net' => $sultada_net,
        'pulta_total' => $pulta_total,
        'cash_balanced' => $gross_cash,
        'direct_expenses' => $direct_expenses,
        'additional_income' => $additional_income,
        'operational_expenses' => $operational_expenses,
        'gross' => $gross_cash,
        'gross_cash' => $gross_cash,
        'income_after_expenses' => $income_after_expenses,
        'total_income' => $total_income,
        'total_expenses' => $total_expenses,
        'net_income' => $net_income,
        'organizer_share' => $net_income * 0.60,
        'barangay_share' => $net_income * 0.40,
    ];
}

function getTodayClosingSummary() {
    $financials = getTodayFinancials();
    $summary = buildFinancialSummary($financials['income'], $financials['expenses'], $financials['beginning_balance']);
    $summary['status'] = getTodayEventStatus();

    return $summary;
}

// Get today's financials (for cashier/admin)
function getTodayFinancials() {
    global $conn;
    $event_id = getTodayEvent();
    
    // Get income by category
    $income_result = $conn->query("SELECT category, SUM(amount) as total FROM income WHERE event_id = $event_id GROUP BY category");
    $income_data = [];
    $total_income = 0;
    if ($income_result) {
        while ($row = $income_result->fetch_assoc()) {
            $income_data[$row['category']] = (float) $row['total'];
            $total_income += (float) $row['total'];
        }
    }
    
    // Get expenses by category
    $expense_result = $conn->query("SELECT category, SUM(amount) as total FROM expenses WHERE event_id = $event_id GROUP BY category");
    $expense_data = [];
    $total_expenses = 0;
    if ($expense_result) {
        while ($row = $expense_result->fetch_assoc()) {
            $expense_data[$row['category']] = (float) $row['total'];
            $total_expenses += (float) $row['total'];
        }
    }

    $summary = buildFinancialSummary($income_data, $expense_data, getEventBeginningBalance($event_id));

    return [
        'event_id' => $event_id,
        'income' => $income_data,
        'total_income' => $summary['total_income'],
        'beginning_balance' => $summary['beginning_balance'],
        'sultada_net' => $summary['sultada_net'],
        'pulta_total' => $summary['pulta_total'],
        'gross_cash' => $summary['gross_cash'],
        'additional_income' => $summary['additional_income'],
        'direct_expenses' => $summary['direct_expenses'],
        'operational_expenses' => $summary['operational_expenses'],
        'expenses' => $expense_data,
        'total_expenses' => $summary['total_expenses'],
        'net_income' => $summary['net_income'],
        'dist_60' => $summary['organizer_share'],
        'dist_40' => $summary['barangay_share']
    ];
}

function startDemoDay() {
    global $conn;
    $event_id = getTodayEvent();

    $clear_queries = [
        "DELETE FROM attendance WHERE event_id = $event_id",
        "DELETE FROM income WHERE event_id = $event_id",
        "DELETE FROM expenses WHERE event_id = $event_id"
    ];

    foreach ($clear_queries as $sql) {
        if (!$conn->query($sql)) {
            return false;
        }
    }

    $attendance_types = ['entry', 'entry', 'entry', 'entry', 'entry', 'reentry', 'reentry', 'exit', 'exit'];
    foreach ($attendance_types as $type) {
        $safe_type = $conn->real_escape_string($type);
        if (!$conn->query("INSERT INTO attendance (event_id, type) VALUES ($event_id, '$safe_type')")) {
            return false;
        }
    }

    $income_data = [
        'sultada' => 1200.00,
        'pulta' => 480.00,
        'dresser' => 180.00,
        'tax_admin' => 140.00,
        'parking' => 90.00,
        'space_rental' => 240.00,
        'other' => 60.00
    ];

    foreach ($income_data as $category => $amount) {
        $safe_category = $conn->real_escape_string($category);
        if (!$conn->query("INSERT INTO income (event_id, category, amount) VALUES ($event_id, '$safe_category', $amount)")) {
            return false;
        }
    }

    $expense_data = [
        'amusement_tax' => 180.00,
        'permit' => 120.00,
        'payroll' => 900.00,
        'meals' => 70.00,
        'fuel' => 60.00,
        'materials' => 90.00,
        'utilities' => 110.00,
        'labor' => 140.00,
        'promo' => 75.00
    ];

    foreach ($expense_data as $category => $amount) {
        $safe_category = $conn->real_escape_string($category);
        if (!$conn->query("INSERT INTO expenses (event_id, category, amount) VALUES ($event_id, '$safe_category', $amount)")) {
            return false;
        }
    }

    return true;
}

function resetTodayDemoData() {
    global $conn;
    $event_id = getTodayEvent();

    $queries = [
        "DELETE FROM attendance WHERE event_id = $event_id",
        "DELETE FROM income WHERE event_id = $event_id",
        "DELETE FROM expenses WHERE event_id = $event_id"
    ];

    foreach ($queries as $sql) {
        if (!$conn->query($sql)) {
            return false;
        }
    }

    return true;
}

function replaceTodayFinanceBatch($income_data, $expense_data, $beginning_balance = null) {
    global $conn;
    $event_id = getTodayEvent();

    $conn->begin_transaction();

    $delete_queries = [
        "DELETE FROM income WHERE event_id = $event_id",
        "DELETE FROM expenses WHERE event_id = $event_id"
    ];

    foreach ($delete_queries as $sql) {
        if (!$conn->query($sql)) {
            $conn->rollback();
            return false;
        }
    }

    foreach ($income_data as $category => $amount) {
        $category = trim((string) $category);
        $amount = floatval($amount);

        if ($category !== '' && $amount > 0) {
            $safe_category = $conn->real_escape_string($category);
            $sql = "INSERT INTO income (event_id, category, amount) VALUES ($event_id, '$safe_category', $amount)";
            if (!$conn->query($sql)) {
                $conn->rollback();
                return false;
            }
        }
    }

    foreach ($expense_data as $category => $amount) {
        $category = trim((string) $category);
        $amount = floatval($amount);

        if ($category !== '' && $amount > 0) {
            $safe_category = $conn->real_escape_string($category);
            $sql = "INSERT INTO expenses (event_id, category, amount) VALUES ($event_id, '$safe_category', $amount)";
            if (!$conn->query($sql)) {
                $conn->rollback();
                return false;
            }
        }
    }

    if ($beginning_balance !== null && !setEventBeginningBalance($event_id, $beginning_balance)) {
        $conn->rollback();
        return false;
    }

    $conn->commit();
    return true;
}
?>