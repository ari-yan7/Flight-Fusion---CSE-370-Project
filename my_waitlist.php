<?php
require_once('DBconnect.php');
ff_require_passenger();

$passenger_id = $_SESSION['passenger_id'];

$stmt = $conn->prepare("SELECT w.*, f.departure_time, f.arrival_time, f.flight_status, f.total_seats,
                               dep.airport_code AS dep_code, arr.airport_code AS arr_code
                        FROM waitlist w
                        JOIN flight f    ON w.flight_id = f.flight_id
                        JOIN airport dep ON f.departure_airport_id = dep.airport_id
                        JOIN airport arr ON f.arrival_airport_id   = arr.airport_id
                        WHERE w.passenger_id = ?
                        ORDER BY w.request_date DESC");
$stmt->bind_param("s", $passenger_id);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FlightFusion | My Waitlist</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<?php require_once('nav.php'); ?>
<main>
    <div class="wide_box">
        <h2 class="page_title">My Waitlist</h2>

        <?php if (isset($_GET['msg'])) { ?>
            <div class="msg msg_ok"><?php echo e($_GET['msg']); ?></div>
        <?php } ?>
        <?php if (isset($_GET['error'])) { ?>
            <div class="msg msg_error">
            <?php
                if ($_GET['error'] == 'dup')      echo "You are already on the waitlist for that flight.";
                else if ($_GET['error'] == 'hasseat') echo "You already have a confirmed seat on that flight.";
                else if ($_GET['error'] == 'owner')   echo "That waitlist entry is not yours.";
                else echo "The waitlist operation failed.";
            ?>
            </div>
        <?php } ?>

        <table>
            <tr>
                <th>Waitlist ID</th>
                <th>Flight</th>
                <th>Route</th>
                <th>Departure</th>
                <th>Queue Position</th>
                <th>Requested On</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
            <?php
            if (mysqli_num_rows($result) > 0) {
                while ($row = mysqli_fetch_assoc($result)) {
            ?>
            <tr>
                <td><?php echo e($row['waitlist_id']); ?></td>
                <td><?php echo e($row['flight_id']); ?></td>
                <td><?php echo e($row['dep_code'] . ' - ' . $row['arr_code']); ?></td>
                <td><?php echo e($row['departure_time']); ?></td>
                <td><?php echo e($row['queue_position']); ?></td>
                <td><?php echo e($row['request_date']); ?></td>
                <td><span class="status status_<?php echo strtolower($row['status']); ?>"><?php echo e($row['status']); ?></span></td>
                <td>
                    <?php if ($row['status'] == 'Promoted') { ?>
                        <a class="btn btn_small btn_success" href="book_flight.php?flight_id=<?php echo e($row['flight_id']); ?>">Seat Free - Book Now</a>
                    <?php } ?>
                    <?php if ($row['status'] == 'Waiting' || $row['status'] == 'Promoted') { ?>
                        <a class="btn btn_small btn_danger" href="leave_waitlist.php?id=<?php echo e($row['waitlist_id']); ?>"
                           onclick="return confirm('Leave this waitlist?');">Leave</a>
                    <?php } ?>
                </td>
            </tr>
            <?php
                }
            } else {
            ?>
            <tr><td colspan="8">You are not on any waitlist.</td></tr>
            <?php } ?>
        </table>

        <a class="btn" href="search_flights.php">Back to Flights</a>
    </div>
</main>
</body>
</html>
