<?php
require_once('DBconnect.php');
ff_require_admin();

$sql = "SELECT f.*, dep.airport_name AS dep_name, dep.city AS dep_city,
               arr.airport_name AS arr_name, arr.city AS arr_city,
               ac.model, al.airline_name
        FROM flight f
        JOIN airport  dep ON f.departure_airport_id = dep.airport_id
        JOIN airport  arr ON f.arrival_airport_id   = arr.airport_id
        JOIN aircraft ac  ON f.aircraft_id          = ac.aircraft_id
        JOIN airline  al  ON ac.airline_id          = al.airline_id
        ORDER BY f.departure_time ASC";
$result = mysqli_query($conn, $sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FlightFusion | Flight Schedule</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<?php require_once('nav.php'); ?>
<main>
    <div class="wide_box">
        <h2 class="page_title">Flight Schedule &amp; Ticket Availability</h2>
        <table>
            <tr>
                <th>Flight ID</th>
                <th>Airline</th>
                <th>Origin</th>
                <th>Destination</th>
                <th>Departure Time</th>
                <th>Arrival Time</th>
                <th>Flight Status</th>
                <th>Total Seats</th>
                <th>Occupied Seats</th>
                <th>Available Seats</th>
            </tr>
            <?php
            if (mysqli_num_rows($result) > 0) {
                while ($row = mysqli_fetch_array($result)) {
                    $occupied  = ff_occupied_seats($conn, $row['flight_id']);
                    $available = $row['total_seats'] - $occupied;
            ?>
            <tr>
                <td><?php echo e($row['flight_id']); ?></td>
                <td><?php echo e($row['airline_name']); ?></td>
                <td><?php echo e($row['dep_city'] . ' (' . $row['departure_airport_id'] . ')'); ?></td>
                <td><?php echo e($row['arr_city'] . ' (' . $row['arrival_airport_id'] . ')'); ?></td>
                <td><?php echo e($row['departure_time']); ?></td>
                <td><?php echo e($row['arrival_time']); ?></td>
                <td><span class="status status_<?php echo strtolower($row['flight_status']); ?>"><?php echo e($row['flight_status']); ?></span></td>
                <td><?php echo e($row['total_seats']); ?></td>
                <td><?php echo $occupied; ?></td>
                <td><?php echo $available; ?></td>
            </tr>
            <?php
                }
            } else {
            ?>
            <tr><td colspan="10">No flights found.</td></tr>
            <?php } ?>
        </table>
    </div>
</main>
</body>
</html>
