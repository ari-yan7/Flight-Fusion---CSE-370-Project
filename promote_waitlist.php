<?php
require_once('DBconnect.php');
ff_require_admin();

if (!isset($_POST['flight_id'])) {
    header("Location: admin_waitlist.php");
    exit();
}

$flight_id = $_POST['flight_id'];
$promoted  = ff_promote_waitlist($conn, $flight_id);

if ($promoted > 0) {
    /* the admin who ran it now manages those waitlist rows (Manages_Waitlist) */
    $admin_id = $_SESSION['admin_id'];
    $stmt = $conn->prepare("SELECT waitlist_id FROM waitlist
                            WHERE flight_id = ? AND status = 'Promoted'");
    $stmt->bind_param("s", $flight_id);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($r = $res->fetch_assoc()) {
        $ins = $conn->prepare("INSERT IGNORE INTO manages_waitlist (admin_id, waitlist_id) VALUES (?, ?)");
        $ins->bind_param("ss", $admin_id, $r['waitlist_id']);
        $ins->execute();
        $ins->close();
    }
    $stmt->close();

    header("Location: admin_waitlist.php?msg=" . $promoted . " passenger(s) promoted on flight " . $flight_id);
} else {
    header("Location: admin_waitlist.php?msg=No free seat on flight " . $flight_id . ", nobody was promoted");
}
exit();
?>
