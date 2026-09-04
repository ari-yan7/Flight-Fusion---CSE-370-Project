<?php
require_once('DBconnect.php');
ff_require_admin();

if (isset($_POST['new_aircraft_id']) && isset($_POST['new_departure_airport']) &&
    isset($_POST['new_arrival_airport']) && isset($_POST['new_departure_time']) &&
    isset($_POST['new_arrival_time']) && isset($_POST['new_total_seats'])) {

    $aircraft_id    = $_POST['new_aircraft_id'];
    $dep_airport    = $_POST['new_departure_airport'];
    $arr_airport    = $_POST['new_arrival_airport'];
    $departure_time = str_replace('T', ' ', $_POST['new_departure_time']) . ':00';
    $arrival_time   = str_replace('T', ' ', $_POST['new_arrival_time']) . ':00';
    $flight_status  = $_POST['new_flight_status'];
    $total_seats    = (int)$_POST['new_total_seats'];
    $base_fare      = $_POST['new_base_fare'];

    if ($dep_airport == $arr_airport) {
        header("Location: add_flight.php?error=same");
        exit();
    }
    if (strtotime($arrival_time) <= strtotime($departure_time)) {
        header("Location: add_flight.php?error=time");
        exit();
    }

    $flight_id = ff_next_id($conn, 'flight', 'flight_id', 'FL', 2);

    $sql = "INSERT INTO flight
            (flight_id, aircraft_id, departure_airport_id, arrival_airport_id,
             departure_time, arrival_time, flight_status, total_seats, base_fare)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssssid", $flight_id, $aircraft_id, $dep_airport, $arr_airport,
                                   $departure_time, $arrival_time, $flight_status,
                                   $total_seats, $base_fare);
    $stmt->execute();

    if (mysqli_affected_rows($conn) > 0) {
        $stmt->close();

        $admin_id = $_SESSION['admin_id'];
        $stmt = $conn->prepare("INSERT INTO manages_flight (admin_id, flight_id) VALUES (?, ?)");
        $stmt->bind_param("ss", $admin_id, $flight_id);
        $stmt->execute();
        $stmt->close();

        header("Location: admin_dashboard.php?msg=Flight " . $flight_id . " added successfully");
        exit();
    } else {
        $stmt->close();
        header("Location: add_flight.php?error=db");
        exit();
    }
} else {
    header("Location: add_flight.php");
    exit();
}
?>
