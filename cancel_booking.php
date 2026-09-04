<?php
require_once('DBconnect.php');
ff_require_passenger();

if (!isset($_GET['id'])) {
    header("Location: my_bookings.php");
    exit();
}

$booking_id   = $_GET['id'];
$passenger_id = $_SESSION['passenger_id'];

$stmt = $conn->prepare("SELECT * FROM booking WHERE booking_id = ?");
$stmt->bind_param("s", $booking_id);
$stmt->execute();
$booking = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$booking) {
    header("Location: my_bookings.php?error=notfound");
    exit();
}

if ($booking['passenger_id'] != $passenger_id) {
    header("Location: my_bookings.php?error=owner");
    exit();
}
if ($booking['booking_status'] == 'Cancelled') {
    header("Location: my_bookings.php?error=already");
    exit();
}

$conn->begin_transaction();
try {
   
    $stmt = $conn->prepare("SELECT ticket_id, passenger_id, flight_id, ticket_price
                            FROM ticket WHERE booking_id = ? AND ticket_status = 'Confirmed'");
    $stmt->bind_param("s", $booking_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $rows = array();
    while ($r = $res->fetch_assoc()) { $rows[] = $r; }
    $stmt->close();

    $flights = array();
    foreach ($rows as $r) { $flights[$r['flight_id']] = true; }

    
    foreach ($rows as $r) {
        $stmt = $conn->prepare("UPDATE seat_swap_request SET swap_status = 'Cancelled'
                                WHERE swap_status = 'Pending'
                                  AND (requester_ticket_id = ? OR target_ticket_id = ?)");
        $stmt->bind_param("ss", $r['ticket_id'], $r['ticket_id']);
        $stmt->execute();
        $stmt->close();
    }

    /* cancel tickets */
    $stmt = $conn->prepare("UPDATE ticket SET ticket_status = 'Cancelled' WHERE booking_id = ?");
    $stmt->bind_param("s", $booking_id);
    $stmt->execute();
    $stmt->close();

    /* cancel booking */
    $stmt = $conn->prepare("UPDATE booking SET booking_status = 'Cancelled' WHERE booking_id = ?");
    $stmt->bind_param("s", $booking_id);
    $stmt->execute();
    $stmt->close();

    /* refund payment */
    $stmt = $conn->prepare("UPDATE payment SET payment_status = 'Refunded' WHERE booking_id = ?");
    $stmt->bind_param("s", $booking_id);
    $stmt->execute();
    $stmt->close();

    /* take back the points that were earned for these tickets */
    foreach ($rows as $r) {
        $points = (int)floor($r['ticket_price'] * POINTS_RATE);
        if ($points > 0) {
            ff_add_points($conn, $r['passenger_id'], -$points, 'Reversed', NULL,
                          'Points reversed for cancelled booking ' . $booking_id);
        }
    }

    /* seats are free now - move the waitlist forward */
    foreach (array_keys($flights) as $fid) {
        ff_promote_waitlist($conn, $fid);
    }

    $conn->commit();
    header("Location: my_bookings.php?msg=Booking " . $booking_id . " cancelled and refunded");
    exit();

} catch (Exception $ex) {
    $conn->rollback();
    header("Location: my_bookings.php?error=db");
    exit();
}
?>
