<?php
require_once('DBconnect.php');
ff_require_admin();

$total_flights   = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM flight"))['c'];
$total_bookings  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM booking WHERE booking_status='Confirmed'"))['c'];
$total_passenger = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM passenger"))['c'];
$total_waitlist  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM waitlist WHERE status='Waiting'"))['c'];
$total_swaps     = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM seat_swap_request WHERE swap_status='Pending'"))['c'];
$total_comp      = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM weather_compensation"))['c'];

$sql = "SELECT f.*, dep.airport_code AS dep_code, arr.airport_code AS arr_code,
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
    <title>FlightFusion | Admin Dashboard</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<?php require_once('nav.php'); ?>
<main>
    <div class="wide_box">
        <h2 class="page_title">Admin Dashboard - <?php echo e($_SESSION['username']); ?></h2>

        <?php if (isset($_GET['error']) && $_GET['error'] == 'denied') { ?>
            <div class="msg msg_error">Administrators cannot use passenger booking pages.</div>
        <?php } ?>
        <?php if (isset($_GET['msg'])) { ?>
            <div class="msg msg_ok"><?php echo e($_GET['msg']); ?></div>
        <?php } ?>

        <div class="stat_grid">
            <div class="stat_box"><h3><?php echo $total_flights; ?></h3><p>Flights</p></div>
            <div class="stat_box"><h3><?php echo $total_bookings; ?></h3><p>Confirmed Bookings</p></div>
            <div class="stat_box"><h3><?php echo $total_passenger; ?></h3><p>Passengers</p></div>
            <div class="stat_box"><h3><?php echo $total_waitlist; ?></h3><p>Waiting</p></div>
            <div class="stat_box"><h3><?php echo $total_swaps; ?></h3><p>Pending Swaps</p></div>
            <div class="stat_box"><h3><?php echo $total_comp; ?></h3><p>Compensations</p></div>
        </div>

        <div class="card">
            <h3>Monitoring</h3>
            <div class="link_grid">
                <a href="show_flights.php">Flight Schedule</a>
                <a href="admin_bookings.php">Bookings &amp; Tickets</a>
                <a href="admin_passengers.php">Passengers</a>
                <a href="admin_waitlist.php">Waitlists</a>
                <a href="admin_swaps.php">Seat Swaps</a>
                <a href="admin_rewards.php">Frequent Flyer</a>
                <a href="admin_discounts.php">Discount Verification</a>
                <a href="admin_weather.php">Weather Guarantee</a>
            </div>
        </div>

        <h3 class="page_title">Flight List</h3>
        <table>
            <tr>
                <th>Flight ID</th>
                <th>Airline / Aircraft</th>
                <th>From</th>
                <th>To</th>
                <th>Departure</th>
                <th>Arrival</th>
                <th>Status</th>
                <th>Total</th>
                <th>Occupied</th>
                <th>Available</th>
                <th>Base Fare</th>
                <th>Action</th>
            </tr>
            <?php
            if (mysqli_num_rows($result) > 0) {
                while ($row = mysqli_fetch_assoc($result)) {
                    $occupied  = ff_occupied_seats($conn, $row['flight_id']);
                    $available = $row['total_seats'] - $occupied;
            ?>
            <tr>
                <td><?php echo e($row['flight_id']); ?></td>
                <td><?php echo e($row['airline_name'] . ' / ' . $row['model']); ?></td>
                <td><?php echo e($row['dep_code']); ?></td>
                <td><?php echo e($row['arr_code']); ?></td>
                <td><?php echo e($row['departure_time']); ?></td>
                <td><?php echo e($row['arrival_time']); ?></td>
                <td><span class="status status_<?php echo strtolower($row['flight_status']); ?>"><?php echo e($row['flight_status']); ?></span></td>
                <td><?php echo e($row['total_seats']); ?></td>
                <td><?php echo $occupied; ?></td>
                <td><?php echo $available; ?></td>
                <td><?php echo e($row['base_fare']); ?></td>
                <td>
                    <a class="btn btn_small" href="modify_flight.php?id=<?php echo e($row['flight_id']); ?>">Modify</a>
                    <a class="btn btn_small btn_danger" href="delete_flight.php?id=<?php echo e($row['flight_id']); ?>"
                       onclick="return confirm('Delete flight <?php echo e($row['flight_id']); ?>?');">Delete</a>
                </td>
            </tr>
            <?php
                }
            } else {
            ?>
            <tr><td colspan="12">No flights found.</td></tr>
            <?php } ?>
        </table>

        <a class="btn" href="add_flight.php">Add Flight</a>
    </div>
</main>
</body>
</html>
