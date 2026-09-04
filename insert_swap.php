<?php
require_once('DBconnect.php');
ff_require_passenger();

if (!isset($_POST['requester_ticket_id']) || !isset($_POST['target_ticket_id'])) {
    header("Location: swap_requests.php");
    exit();
}

$requester_ticket_id = $_POST['requester_ticket_id'];
$target_ticket_id    = $_POST['target_ticket_id'];
$passenger_id        = $_SESSION['passenger_id'];

if ($requester_ticket_id == $target_ticket_id) {
    header("Location: swap_requests.php?error=self");
    exit();
}

$stmt = $conn->prepare("SELECT * FROM ticket WHERE ticket_id = ?");
$stmt->bind_param("s", $requester_ticket_id);
$stmt->execute();
$mine = $stmt->get_result()->fetch_assoc();
$stmt->close();

$stmt = $conn->prepare("SELECT * FROM ticket WHERE ticket_id = ?");
$stmt->bind_param("s", $target_ticket_id);
$stmt->execute();
$theirs = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$mine || !$theirs) {
    header("Location: swap_requests.php?error=notfound");
    exit();
}
if ($mine['passenger_id'] != $passenger_id) {
    header("Location: swap_requests.php?error=owner");
    exit();
}
/* the whole point of the rule: same flight only */
if ($mine['flight_id'] != $theirs['flight_id']) {
    header("Location: seat_swap.php?ticket_id=" . $requester_ticket_id . "&error=flight");
    exit();
}
if ($mine['ticket_status'] != 'Confirmed' || $theirs['ticket_status'] != 'Confirmed') {
    header("Location: swap_requests.php?error=ticket");
    exit();
}
if ($mine['class'] != $theirs['class']) {
    header("Location: seat_swap.php?ticket_id=" . $requester_ticket_id . "&error=class");
    exit();
}
if ($mine['passenger_id'] == $theirs['passenger_id']) {
    header("Location: swap_requests.php?error=self");
    exit();
}

$stmt = $conn->prepare("SELECT swap_id FROM seat_swap_request
                        WHERE swap_status = 'Pending'
                          AND (requester_ticket_id IN (?, ?) OR target_ticket_id IN (?, ?))");
$stmt->bind_param("ssss", $requester_ticket_id, $target_ticket_id,
                          $requester_ticket_id, $target_ticket_id);
$stmt->execute();
$active = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($active) {
    header("Location: seat_swap.php?ticket_id=" . $requester_ticket_id . "&error=exists");
    exit();
}

$swap_id = ff_next_id($conn, 'seat_swap_request', 'swap_id', 'SSR', 2);

$stmt = $conn->prepare("INSERT INTO seat_swap_request
    (swap_id, requester_ticket_id, target_ticket_id, request_date,
     requester_status, target_status, swap_status)
    VALUES (?, ?, ?, NOW(), 'Approved', 'Pending', 'Pending')");
$stmt->bind_param("sss", $swap_id, $requester_ticket_id, $target_ticket_id);
$stmt->execute();
$stmt->close();

header("Location: swap_requests.php?msg=Swap request " . $swap_id . " sent. Waiting for the other passenger.");
exit();
?>
