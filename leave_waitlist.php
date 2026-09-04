<?php
require_once('DBconnect.php');
ff_require_passenger();

if (!isset($_GET['id'])) {
    header("Location: my_waitlist.php");
    exit();
}

$waitlist_id  = $_GET['id'];
$passenger_id = $_SESSION['passenger_id'];

$stmt = $conn->prepare("SELECT * FROM waitlist WHERE waitlist_id = ?");
$stmt->bind_param("s", $waitlist_id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$row || $row['passenger_id'] != $passenger_id) {
    header("Location: my_waitlist.php?error=owner");
    exit();
}

$conn->begin_transaction();
try {
    $stmt = $conn->prepare("UPDATE waitlist SET status = 'Cancelled' WHERE waitlist_id = ?");
    $stmt->bind_param("s", $waitlist_id);
    $stmt->execute();
    $stmt->close();

    $stmt = $conn->prepare("UPDATE waitlist SET queue_position = queue_position - 1
                            WHERE flight_id = ? AND queue_position > ? AND status IN ('Waiting','Promoted')");
    $stmt->bind_param("si", $row['flight_id'], $row['queue_position']);
    $stmt->execute();
    $stmt->close();

    $conn->commit();
    header("Location: my_waitlist.php?msg=You left the waitlist for flight " . $row['flight_id']);
    exit();

} catch (Exception $ex) {
    $conn->rollback();
    header("Location: my_waitlist.php?error=db");
    exit();
}
?>
