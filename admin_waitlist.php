<?php

require_once('DBconnect.php');
ff_require_admin();

$sql = "SELECT w.*, p.firstName, p.lastName, f.total_seats, f.departure_time, f.flight_status
        FROM waitlist w
        JOIN passenger p ON w.passenger_id = p.passenger_id
        JOIN flight f    ON w.flight_id    = f.flight_id
        ORDER BY w.flight_id, w.queue_position";
$result = mysqli_query($conn, $sql);

$flights = mysqli_query($conn, "SELECT flight_id, total_seats FROM flight ORDER BY flight_id");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FlightFusion | Waitlists</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<?php require_once('nav.php'); ?>
<main>
    <div class="wide_box">
        <h2 class="page_title">Waitlist Monitoring</h2>

        <?php if (isset($_GET['msg'])) { ?>
            <div class="msg msg_ok"><?php echo e($_GET['msg']); ?></div>
        <?php } ?>

        <div class="card">
            <h3>Run the promotion check</h3>
            <p>The queue moves by itself whenever a booking is cancelled. Use this only if you
               added seats to an aircraft manually.</p>
            <form action="promote_waitlist.php" method="post">
                <label for="flight_id">Flight:</label>
                <select name="flight_id" id="flight_id">
                    <?php while ($f = mysqli_fetch_assoc($flights)) { ?>
                        <option value="<?php echo e($f['flight_id']); ?>"><?php echo e($f['flight_id']); ?></option>
                    <?php } ?>
                </select>
                <button type="submit">Check for free seats</button>
            </form>
        </div>

        <table>
            <tr>
                <th>Waitlist ID</th><th>Flight</th><th>Departure</th><th>Passenger</th>
                <th>Queue Position</th><th>Requested</th><th>Available Seats Now</th><th>Status</th>
            </tr>
            <?php while ($row = mysqli_fetch_assoc($result)) {
                $free = ff_available_seats($conn, $row['flight_id'], (int)$row['total_seats']); ?>
            <tr>
                <td><?php echo e($row['waitlist_id']); ?></td>
                <td><?php echo e($row['flight_id']); ?></td>
                <td><?php echo e($row['departure_time']); ?></td>
                <td><?php echo e($row['firstName'] . ' ' . $row['lastName'] . ' (' . $row['passenger_id'] . ')'); ?></td>
                <td><?php echo e($row['queue_position']); ?></td>
                <td><?php echo e($row['request_date']); ?></td>
                <td><?php echo $free; ?></td>
                <td><span class="status status_<?php echo strtolower($row['status']); ?>"><?php echo e($row['status']); ?></span></td>
            </tr>
            <?php } ?>
        </table>
    </div>
</main>
</body>
</html>
