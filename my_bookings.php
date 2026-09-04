<?php
require_once('DBconnect.php');
ff_require_passenger();

$passenger_id = $_SESSION['passenger_id'];

/* every reservation I lead, plus every reservation where I am a co-traveller */
$sql = "SELECT DISTINCT b.*, p.firstName, p.lastName
        FROM booking b
        JOIN passenger p ON b.passenger_id = p.passenger_id
        LEFT JOIN ticket t ON t.booking_id = b.booking_id
        WHERE b.passenger_id = ? OR t.passenger_id = ?
        ORDER BY b.booking_date DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $passenger_id, $passenger_id);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FlightFusion | My Bookings</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<?php require_once('nav.php'); ?>
<main>
    <div class="wide_box">
        <h2 class="page_title">My Bookings</h2>

        <?php if (isset($_GET['msg'])) { ?>
            <div class="msg msg_ok"><?php echo e($_GET['msg']); ?></div>
        <?php } ?>
        <?php if (isset($_GET['error'])) { ?>
            <div class="msg msg_error">
            <?php
                if ($_GET['error'] == 'notfound') echo "That booking does not exist.";
                else if ($_GET['error'] == 'owner') echo "You can only manage your own reservations.";
                else if ($_GET['error'] == 'already') echo "That booking is already cancelled.";
                else echo "The operation failed. Nothing was changed.";
            ?>
            </div>
        <?php } ?>

        <table>
            <tr>
                <th>Booking ID</th>
                <th>Lead Passenger</th>
                <th>Booking Date</th>
                <th>Passengers</th>
                <th>Total Amount</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
            <?php
            if (mysqli_num_rows($result) > 0) {
                while ($row = mysqli_fetch_assoc($result)) {
                    $bid = $row['booking_id'];
                    $c = mysqli_fetch_assoc(mysqli_query($conn,
                        "SELECT COUNT(*) AS c FROM ticket WHERE booking_id = '" . $conn->real_escape_string($bid) . "'"))['c'];
            ?>
            <tr>
                <td><?php echo e($row['booking_id']); ?></td>
                <td><?php echo e($row['firstName'] . ' ' . $row['lastName']); ?></td>
                <td><?php echo e($row['booking_date']); ?></td>
                <td><?php echo $c; ?></td>
                <td>BDT <?php echo e($row['total_amount']); ?></td>
                <td><span class="status status_<?php echo strtolower($row['booking_status']); ?>"><?php echo e($row['booking_status']); ?></span></td>
                <td>
                    <a class="btn btn_small" href="view_booking.php?id=<?php echo e($row['booking_id']); ?>">View</a>
                    <?php if ($row['booking_status'] == 'Confirmed' && $row['passenger_id'] == $passenger_id) { ?>
                        <a class="btn btn_small btn_danger" href="cancel_booking.php?id=<?php echo e($row['booking_id']); ?>"
                           onclick="return confirm('Cancel booking <?php echo e($row['booking_id']); ?>?');">Cancel</a>
                    <?php } ?>
                </td>
            </tr>
            <?php
                }
            } else {
            ?>
            <tr><td colspan="7">You have no bookings yet.</td></tr>
            <?php } ?>
        </table>

        <a class="btn" href="search_flights.php">Book a New Flight</a>
    </div>
</main>
</body>
</html>
