<?php
require_once('DBconnect.php');
ff_require_admin();

if (isset($_POST['flight_id'])) {

    $flight_id      = $_POST['flight_id'];
    $aircraft_id    = $_POST['new_aircraft_id'];
    $dep_airport    = $_POST['new_departure_airport'];
    $arr_airport    = $_POST['new_arrival_airport'];
    $departure_time = str_replace('T', ' ', $_POST['new_departure_time']) . ':00';
    $arrival_time   = str_replace('T', ' ', $_POST['new_arrival_time']) . ':00';
    $flight_status  = $_POST['new_flight_status'];
    $total_seats    = (int)$_POST['new_total_seats'];
    $base_fare      = $_POST['new_base_fare'];

    if ($dep_airport == $arr_airport) {
        header("Location: modify_flight.php?id=" . $flight_id . "&error=same");
        exit();
    }
    if (strtotime($arrival_time) <= strtotime($departure_time)) {
        header("Location: modify_flight.php?id=" . $flight_id . "&error=time");
        exit();
    }
    /* never shrink a flight below the number of tickets already sold */
    if ($total_seats < ff_occupied_seats($conn, $flight_id)) {
        header("Location: modify_flight.php?id=" . $flight_id . "&error=seats");
        exit();
    }

    $sql = "UPDATE flight SET aircraft_id = ?, departure_airport_id = ?, arrival_airport_id = ?,
                   departure_time = ?, arrival_time = ?, flight_status = ?, total_seats = ?, base_fare = ?
            WHERE flight_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssssids", $aircraft_id, $dep_airport, $arr_airport, $departure_time,
                                   $arrival_time, $flight_status, $total_seats, $base_fare, $flight_id);

    if ($stmt->execute()) {
        $stmt->close();
        /* extra seats may free up room for the waitlist */
        ff_promote_waitlist($conn, $flight_id);
        header("Location: admin_dashboard.php?msg=Flight " . $flight_id . " updated");
        exit();
    } else {
        $stmt->close();
        header("Location: modify_flight.php?id=" . $flight_id . "&error=db");
        exit();
    }
} else {
    header("Location: admin_dashboard.php");
    exit();
}
?>
