<?php
require_once('DBconnect.php');
ff_require_admin();

if (isset($_GET['id'])) {

    $flight_id = $_GET['id'];

       otherwise the foreign keys of ticket / waitlist would break */
    $stmt = $conn->prepare("SELECT COUNT(*) AS c FROM ticket
                            WHERE flight_id = ? AND ticket_status = 'Confirmed'");
    $stmt->bind_param("s", $flight_id);
    $stmt->execute();
    $live = $stmt->get_result()->fetch_assoc()['c'];
    $stmt->close();

    if ($live > 0) {
        $stmt = $conn->prepare("UPDATE flight SET flight_status = 'Cancelled' WHERE flight_id = ?");
        $stmt->bind_param("s", $flight_id);
        $stmt->execute();
        $stmt->close();
        header("Location: admin_dashboard.php?msg=Flight " . $flight_id . " has sold tickets, so it was set to Cancelled instead of deleted");
        exit();
    }

    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("DELETE FROM manages_flight WHERE flight_id = ?");
        $stmt->bind_param("s", $flight_id); $stmt->execute(); $stmt->close();

        $stmt = $conn->prepare("DELETE FROM waitlist WHERE flight_id = ?");
        $stmt->bind_param("s", $flight_id); $stmt->execute(); $stmt->close();

        $stmt = $conn->prepare("DELETE FROM ticket WHERE flight_id = ?");
        $stmt->bind_param("s", $flight_id); $stmt->execute(); $stmt->close();

        $stmt = $conn->prepare("DELETE FROM flight WHERE flight_id = ?");
        $stmt->bind_param("s", $flight_id); $stmt->execute(); $stmt->close();

        $conn->commit();
        header("Location: admin_dashboard.php?msg=Flight " . $flight_id . " deleted");
        exit();
    } catch (Exception $ex) {
        $conn->rollback();
        header("Location: admin_dashboard.php?msg=Flight could not be deleted because other records depend on it");
        exit();
    }
} else {
    header("Location: admin_dashboard.php");
    exit();
}
?>
