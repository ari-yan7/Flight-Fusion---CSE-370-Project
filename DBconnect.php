<?php


session_start();

$servername = "localhost";
$username   = "root";
$password   = "";
$dbname     = "flightfusion";

$conn = new mysqli($servername, $username, $password);

if ($conn->connect_error) {
    die("Connection Failed: " . $conn->connect_error);
} else {
    mysqli_select_db($conn, $dbname);
}
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);


define('POINTS_RATE',        0.10);  
define('BUSINESS_ROWS',      2);      
define('BUSINESS_MULTIPLIER', 3.0);  

define('STUDENT_DISCOUNT_PERCENT', 20); 
define('SENIOR_DISCOUNT_PERCENT',  10);
define('SENIOR_MIN_AGE',           60);
define('STUDENT_MAX_AGE',          30);

define('REDEEM_FREE_TICKET', 5000);
define('REDEEM_UPGRADE',     2000);
define('REDEEM_LOUNGE',      1000);


define('GOLD_TRIPS',     2);  
define('PLATINUM_TRIPS', 4);   

define('WEATHER_MIN_DELAY',      120);  
define('WEATHER_DELAY_VOUCHER',  1500);  
define('WEATHER_DELAY_POINTS',   200);
define('WEATHER_CANCEL_VOUCHER', 3000);  
define('WEATHER_CANCEL_POINTS',  300);
 
function e($value) {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

/* Build the next string id, e.g. FL01 -> FL11, PSG09 -> PSG13 */
function ff_next_id($conn, $table, $column, $prefix, $width) {
    $start = strlen($prefix) + 1;
    $sql = "SELECT MAX(CAST(SUBSTRING($column, $start) AS UNSIGNED)) AS mx
            FROM $table WHERE $column LIKE '$prefix%'";
    $result = mysqli_query($conn, $sql);
    $row = mysqli_fetch_assoc($result);
    $next = ((int)$row['mx']) + 1;
    return $prefix . str_pad($next, $width, '0', STR_PAD_LEFT);
}


function ff_seat_list($total_seats) {
    $letters = array('A','B','C','D','E','F');
    $seats = array();
    $count = 0;
    $row   = 1;
    while ($count < $total_seats) {
        for ($i = 0; $i < 6 && $count < $total_seats; $i++) {
            $seats[] = $row . $letters[$i];
            $count++;
        }
        $row++;
    }
    return $seats;
}


function ff_seat_class($seat_no) {
    $row = (int)$seat_no;
    return ($row <= BUSINESS_ROWS) ? 'Business' : 'Economy';
}


function ff_taken_seats($conn, $flight_id) {
    $taken = array();
    $stmt = $conn->prepare("SELECT seat_no FROM ticket
                            WHERE flight_id = ? AND ticket_status IN ('Confirmed','Used')");
    $stmt->bind_param("s", $flight_id);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $taken[] = $row['seat_no'];
    }
    $stmt->close();
    return $taken;
}


function ff_occupied_seats($conn, $flight_id) {
    $stmt = $conn->prepare("SELECT COUNT(*) AS c FROM ticket
                            WHERE flight_id = ? AND ticket_status IN ('Confirmed','Used')");
    $stmt->bind_param("s", $flight_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    $stmt->close();
    return (int)$row['c'];
}

function ff_available_seats($conn, $flight_id, $total_seats) {
    return $total_seats - ff_occupied_seats($conn, $flight_id);
}


function ff_discount_percent($conn, $passenger_id) {
    $stmt = $conn->prepare("SELECT MAX(percentage) AS p FROM discount
                            WHERE passenger_id = ? AND eligibility = 'Verified'");
    $stmt->bind_param("s", $passenger_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    $stmt->close();
    return $row['p'] === NULL ? 0 : (float)$row['p'];
}

function ff_trip_count($conn, $passenger_id) {
    $stmt = $conn->prepare("SELECT COUNT(*) AS c FROM ticket
                            WHERE passenger_id = ? AND ticket_status IN ('Confirmed','Used')");
    $stmt->bind_param("s", $passenger_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return (int)$row['c'];
}


function ff_membership_level($conn, $passenger_id) {
    $trips = ff_trip_count($conn, $passenger_id);
    if ($trips >= PLATINUM_TRIPS) {
        return 'Platinum';
    } else if ($trips >= GOLD_TRIPS) {
        return 'Gold';
    } else {
        return 'Silver';
    }
}

function ff_update_membership($conn, $passenger_id) {
    $level = ff_membership_level($conn, $passenger_id);
    $stmt = $conn->prepare("UPDATE reward_account SET membership_level = ?
                            WHERE passenger_id = ?");
    $stmt->bind_param("ss", $level, $passenger_id);
    $stmt->execute();
    $stmt->close();
    return $level;
}


function ff_reward_account($conn, $passenger_id, $payment_id = NULL) {
    $stmt = $conn->prepare("SELECT reward_id FROM reward_account WHERE passenger_id = ?");
    $stmt->bind_param("s", $passenger_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        $stmt->close();
        return $row['reward_id'];
    }
    $stmt->close();

    $reward_id = ff_next_id($conn, 'reward_account', 'reward_id', 'RA', 2);
    $stmt = $conn->prepare("INSERT INTO reward_account
        (reward_id, passenger_id, payment_id, total_points, membership_level)
        VALUES (?, ?, ?, 0, 'Silver')");
    $stmt->bind_param("sss", $reward_id, $passenger_id, $payment_id);
    $stmt->execute();
    $stmt->close();
    return $reward_id;
}


function ff_add_points($conn, $passenger_id, $points, $type, $payment_id = NULL, $description = NULL) {
    $reward_id = ff_reward_account($conn, $passenger_id, $payment_id);

    $transaction_id = ff_next_id($conn, 'reward_transaction', 'transaction_id', 'RT', 2);
    $stmt = $conn->prepare("INSERT INTO reward_transaction
        (transaction_id, reward_id, points, transaction_type, transaction_date, description)
        VALUES (?, ?, ?, ?, NOW(), ?)");
    $stmt->bind_param("ssiss", $transaction_id, $reward_id, $points, $type, $description);
    $stmt->execute();
    $stmt->close();

    $stmt = $conn->prepare("UPDATE reward_account SET total_points = total_points + ?
                            WHERE reward_id = ?");
    $stmt->bind_param("is", $points, $reward_id);
    $stmt->execute();
    $stmt->close();

    /* The level follows the booking history, not the balance. */
    ff_update_membership($conn, $passenger_id);

    return $reward_id;
}

function ff_promote_waitlist($conn, $flight_id) {
    $stmt = $conn->prepare("SELECT total_seats FROM flight WHERE flight_id = ?");
    $stmt->bind_param("s", $flight_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $flight = $res->fetch_assoc();
    $stmt->close();
    if (!$flight) { return 0; }

    $free = ff_available_seats($conn, $flight_id, (int)$flight['total_seats']);
    if ($free <= 0) { return 0; }

    $stmt = $conn->prepare("SELECT waitlist_id FROM waitlist
                            WHERE flight_id = ? AND status = 'Waiting'
                            ORDER BY queue_position ASC LIMIT ?");
    $stmt->bind_param("si", $flight_id, $free);
    $stmt->execute();
    $res = $stmt->get_result();
    $ids = array();
    while ($row = $res->fetch_assoc()) { $ids[] = $row['waitlist_id']; }
    $stmt->close();

    foreach ($ids as $wid) {
        $stmt = $conn->prepare("UPDATE waitlist SET status = 'Promoted' WHERE waitlist_id = ?");
        $stmt->bind_param("s", $wid);
        $stmt->execute();
        $stmt->close();
    }
    return count($ids);
}

function ff_is_logged_in() { return isset($_SESSION['user_id']); }
function ff_is_admin()     { return isset($_SESSION['role']) && $_SESSION['role'] == 'admin'; }
function ff_is_passenger() { return isset($_SESSION['role']) && $_SESSION['role'] == 'passenger'; }

function ff_require_login() {
    if (!ff_is_logged_in()) { header("Location: index.php?error=login"); exit(); }
}
function ff_require_admin() {
    ff_require_login();
    if (!ff_is_admin()) { header("Location: home.php?error=denied"); exit(); }
}
function ff_require_passenger() {
    ff_require_login();
    if (!ff_is_passenger()) { header("Location: admin_dashboard.php?error=denied"); exit(); }
}
?>
