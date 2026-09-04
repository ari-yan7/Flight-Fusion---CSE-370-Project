<?php
require_once('DBconnect.php');
ff_require_passenger();

if (!isset($_GET['flight_id'])) {
    header("Location: search_flights.php");
    exit();
}

$flight_id    = $_GET['flight_id'];
$passenger_id = $_SESSION['passenger_id'];

$stmt = $conn->prepare("SELECT * FROM flight WHERE flight_id = ?");
$stmt->bind_param("s", $flight_id);
$stmt->execute();
$flight = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$flight) {
    header("Location: search_flights.php?error=nf");
    exit();
}
if ($flight['flight_status'] == 'Cancelled') {
    header("Location: search_flights.php?error=cancelled");
    exit();
}

if (ff_available_seats($conn, $flight_id, (int)$flight['total_seats']) > 0) {
    header("Location: book_flight.php?flight_id=" . $flight_id);
    exit();
}

$stmt = $conn->prepare("SELECT waitlist_id FROM waitlist
                        WHERE flight_id = ? AND passenger_id = ? AND status IN ('Waiting','Promoted')");
$stmt->bind_param("ss", $flight_id, $passenger_id);
$stmt->execute();
$dup = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($dup) {
    header("Location: my_waitlist.php?error=dup");
    exit();
}

$stmt = $conn->prepare("SELECT COUNT(*) AS c FROM ticket
                        WHERE flight_id = ? AND passenger_id = ? AND ticket_status = 'Confirmed'");
$stmt->bind_param("ss", $flight_id, $passenger_id);
$stmt->execute();
$has_seat = $stmt->get_result()->fetch_assoc()['c'];
$stmt->close();

if ($has_seat > 0) {
    header("Location: my_waitlist.php?error=hasseat");
    exit();
}

$conn->begin_transaction();
try {
    $stmt = $conn->prepare("SELECT COALESCE(MAX(queue_position), 0) + 1 AS nextpos
                            FROM waitlist WHERE flight_id = ?");
    $stmt->bind_param("s", $flight_id);
    $stmt->execute();
    $position = (int)$stmt->get_result()->fetch_assoc()['nextpos'];
    $stmt->close();

    $waitlist_id = ff_next_id($conn, 'waitlist', 'waitlist_id', 'WL', 2);

    $stmt = $conn->prepare("INSERT INTO waitlist
        (waitlist_id, passenger_id, flight_id, queue_position, request_date, status)
        VALUES (?, ?, ?, ?, NOW(), 'Waiting')");
    $stmt->bind_param("sssi", $waitlist_id, $passenger_id, $flight_id, $position);
    $stmt->execute();
    $stmt->close();

    $conn->commit();
    header("Location: my_waitlist.php?msg=You joined the waitlist for flight " . $flight_id . " at position " . $position);
    exit();

} catch (Exception $ex) {
    $conn->rollback();
    header("Location: my_waitlist.php?error=db");
    exit();
}
?>
