<?php
require_once('DBconnect.php');
ff_require_admin();

if (!isset($_POST['flight_id'])) {
    header("Location: admin_weather.php");
    exit();
}

$flight_id     = $_POST['flight_id'];
$delay_minutes = (int)$_POST['delay_minutes'];
$delay_reason  = $_POST['delay_reason'];
$new_status    = $_POST['new_status'];
$admin_id      = $_SESSION['admin_id'];

$conn->begin_transaction();
try {
    /* 1. record the event on the flight */
    $stmt = $conn->prepare("UPDATE flight SET delay_minutes = ?, delay_reason = ?, flight_status = ?
                            WHERE flight_id = ?");
    $stmt->bind_param("isss", $delay_minutes, $delay_reason, $new_status, $flight_id);
    $stmt->execute();
    $stmt->close();

    /* 2. does the guarantee apply? */
    $qualifies_delay  = ($delay_minutes >= WEATHER_MIN_DELAY);
    $qualifies_cancel = ($new_status == 'Cancelled');

    if (!$qualifies_delay && !$qualifies_cancel) {
        $conn->commit();
        header("Location: admin_weather.php?error=rule");
        exit();
    }

    if ($qualifies_cancel) {
        $type    = 'Cancellation Refund Voucher';
        $voucher = WEATHER_CANCEL_VOUCHER;
        $bonus   = WEATHER_CANCEL_POINTS;
    } else {
        $type    = 'Delay Compensation Voucher';
        $voucher = WEATHER_DELAY_VOUCHER;
        $bonus   = WEATHER_DELAY_POINTS;
    }

    $stmt = $conn->prepare("SELECT DISTINCT t.passenger_id
                            FROM ticket t
                            WHERE t.flight_id = ? AND t.ticket_status IN ('Confirmed','Used')
                              AND t.passenger_id NOT IN
                                  (SELECT w.passenger_id FROM weather_compensation w
                                   WHERE w.flight_id = ?)");
    $stmt->bind_param("ss", $flight_id, $flight_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $passengers = array();
    while ($r = $res->fetch_assoc()) { $passengers[] = $r['passenger_id']; }
    $stmt->close();

    if (count($passengers) == 0) {
        $conn->commit();
        header("Location: admin_weather.php?msg=Event saved. Nobody new was eligible on flight " . $flight_id);
        exit();
    }

    foreach ($passengers as $pid) {
        $compensation_id = ff_next_id($conn, 'weather_compensation', 'compensation_id', 'WC', 2);

        $stmt = $conn->prepare("INSERT INTO weather_compensation
            (compensation_id, passenger_id, flight_id, compensation_type,
             voucher_amount, bonus_points, issue_date)
            VALUES (?, ?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("ssssdi", $compensation_id, $pid, $flight_id, $type, $voucher, $bonus);
        $stmt->execute();
        $stmt->close();

        ff_add_points($conn, $pid, $bonus, 'Bonus', NULL,
                      'Weather guarantee for flight ' . $flight_id);

        $stmt = $conn->prepare("INSERT IGNORE INTO manages_weather_compensation
                                (admin_id, compensation_id) VALUES (?, ?)");
        $stmt->bind_param("ss", $admin_id, $compensation_id);
        $stmt->execute();
        $stmt->close();
    }

    $conn->commit();
    header("Location: admin_weather.php?msg=" . count($passengers) . " passenger(s) compensated on flight " . $flight_id);
    exit();

} catch (Exception $ex) {
    $conn->rollback();
    header("Location: admin_weather.php?error=db");
    exit();
}
?>
