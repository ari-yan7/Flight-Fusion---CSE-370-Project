<?php
require_once('DBconnect.php');
ff_require_passenger();

if (!isset($_GET['id']) || !isset($_GET['action'])) {
    header("Location: swap_requests.php");
    exit();
}

$swap_id      = $_GET['id'];
$action       = $_GET['action'];
$passenger_id = $_SESSION['passenger_id'];

$stmt = $conn->prepare("SELECT s.*, rt.passenger_id AS req_pid, rt.seat_no AS req_seat,
                               rt.class AS req_class, rt.flight_id AS req_flight,
                               tt.passenger_id AS tar_pid, tt.seat_no AS tar_seat,
                               tt.class AS tar_class, tt.flight_id AS tar_flight,
                               rt.ticket_status AS req_status, tt.ticket_status AS tar_status
                        FROM seat_swap_request s
                        JOIN ticket rt ON s.requester_ticket_id = rt.ticket_id
                        JOIN ticket tt ON s.target_ticket_id    = tt.ticket_id
                        WHERE s.swap_id = ?");
$stmt->bind_param("s", $swap_id);
$stmt->execute();
$swap = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$swap) {
    header("Location: swap_requests.php?error=notfound");
    exit();
}
if ($swap['swap_status'] != 'Pending') {
    header("Location: swap_requests.php?error=expired");
    exit();
}

/* the requester may cancel, the target may approve or reject - nobody else */
if ($action == 'cancel') {
    if ($swap['req_pid'] != $passenger_id) {
        header("Location: swap_requests.php?error=owner");
        exit();
    }
    $stmt = $conn->prepare("UPDATE seat_swap_request SET swap_status = 'Cancelled' WHERE swap_id = ?");
    $stmt->bind_param("s", $swap_id);
    $stmt->execute();
    $stmt->close();
    header("Location: swap_requests.php?msg=Swap request " . $swap_id . " cancelled");
    exit();
}

if ($swap['tar_pid'] != $passenger_id) {
    header("Location: swap_requests.php?error=owner");
    exit();
}

if ($action == 'reject') {
    $stmt = $conn->prepare("UPDATE seat_swap_request
                            SET target_status = 'Rejected', swap_status = 'Rejected'
                            WHERE swap_id = ?");
    $stmt->bind_param("s", $swap_id);
    $stmt->execute();
    $stmt->close();
    header("Location: swap_requests.php?msg=Swap request " . $swap_id . " rejected");
    exit();
}

if ($action != 'approve') {
    header("Location: swap_requests.php");
    exit();
}

if ($swap['req_flight'] != $swap['tar_flight']) {
    header("Location: swap_requests.php?error=flight");
    exit();
}
if($swap['req_status'] != 'Confirmed' || $swap['tar_status'] != 'Confirmed') {
    header("Location: swap_requests.php?error=ticket");
    exit();
}
if($swap['req_class'] != $swap['tar_class']) {
    header("Location: swap_requests.php?error=class");
    exit();
}

$conn->begin_transaction();
try {
    $seat_a  = $swap['req_seat'];
    $class_a = $swap['req_class'];
    $seat_b  = $swap['tar_seat'];
    $class_b = $swap['tar_class'];
    $temp    = 'TMP';
    $stmt = $conn->prepare("UPDATE ticket SET seat_no = ? WHERE ticket_id = ?");
    $stmt->bind_param("ss", $temp, $swap['requester_ticket_id']);
    $stmt->execute();
    $stmt->close();

    $stmt = $conn->prepare("UPDATE ticket SET seat_no = ?, class = ? WHERE ticket_id = ?");
    $stmt->bind_param("sss", $seat_a, $class_a, $swap['target_ticket_id']);
    $stmt->execute();
    $stmt->close();

    $stmt =$conn->prepare("UPDATE ticket SET seat_no = ?, class = ? WHERE ticket_id = ?");
    $stmt->bind_param("sss", $seat_b, $class_b, $swap['requester_ticket_id']);
    $stmt->execute();
    $stmt->close();

    $stmt = $conn->prepare("UPDATE seat_swap_request
                            SET target_status = 'Approved', requester_status = 'Approved',
                                swap_status = 'Completed'
                            WHERE swap_id = ?");
    $stmt->bind_param("s", $swap_id);
    $stmt->execute();
    $stmt->close();

    $conn->commit();
    header("Location: swap_requests.php?msg=Seats swapped. You are now in seat " . $seat_a);
    exit();

} catch (Exception $ex) {
    $conn->rollback();
    header("Location: swap_requests.php?error=db");
    exit();
}
?>
