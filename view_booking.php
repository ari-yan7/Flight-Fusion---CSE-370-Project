<?php
require_once('DBconnect.php');
ff_require_passenger();

if (!isset($_GET['id'])) {
    header("Location: my_bookings.php");
    exit();
}

$booking_id   = $_GET['id'];
$passenger_id = $_SESSION['passenger_id'];

$stmt = $conn->prepare("SELECT b.*, p.firstName, p.lastName
                        FROM booking b JOIN passenger p ON b.passenger_id = p.passenger_id
                        WHERE b.booking_id = ?");
$stmt->bind_param("s", $booking_id);
$stmt->execute();
$booking = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$booking) {
    header("Location: my_bookings.php?error=notfound");
    exit();
}

$stmt = $conn->prepare("SELECT COUNT(*) AS c FROM ticket WHERE booking_id = ? AND passenger_id = ?");
$stmt->bind_param("ss", $booking_id, $passenger_id);
$stmt->execute();
$mine = $stmt->get_result()->fetch_assoc()['c'];
$stmt->close();

if ($mine == 0 && $booking['passenger_id'] != $passenger_id) {
    header("Location: my_bookings.php?error=owner");
    exit();
}

$stmt = $conn->prepare("SELECT t.*, p.firstName, p.lastName, p.NID,
                               f.base_fare, f.departure_time, f.arrival_time, f.flight_status,
                               dep.airport_code AS dep_code, arr.airport_code AS arr_code
                        FROM ticket t
                        JOIN passenger p ON t.passenger_id = p.passenger_id
                        JOIN flight f    ON t.flight_id    = f.flight_id
                        JOIN airport dep ON f.departure_airport_id = dep.airport_id
                        JOIN airport arr ON f.arrival_airport_id   = arr.airport_id
                        WHERE t.booking_id = ?");
$stmt->bind_param("s", $booking_id);
$stmt->execute();
$tickets = $stmt->get_result();
$stmt->close();

$stmt = $conn->prepare("SELECT * FROM payment WHERE booking_id = ?");
$stmt->bind_param("s", $booking_id);
$stmt->execute();
$payment = $stmt->get_result()->fetch_assoc();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FlightFusion | Booking <?php echo e($booking_id); ?></title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<?php require_once('nav.php'); ?>
<main>
    <div class="wide_box">
        <h2 class="page_title">Booking <?php echo e($booking_id); ?></h2>

        <?php if (isset($_GET['msg'])) { ?>
            <div class="msg msg_ok"><?php echo e($_GET['msg']); ?></div>
        <?php } ?>

        <div class="card">
            <h3>Reservation</h3>
            <table>
                <tr><th>Booking ID</th><td><?php echo e($booking['booking_id']); ?></td></tr>
                <tr><th>Lead Passenger</th><td><?php echo e($booking['firstName'] . ' ' . $booking['lastName']); ?></td></tr>
                <tr><th>Booking Date</th><td><?php echo e($booking['booking_date']); ?></td></tr>
                <tr><th>Total Amount</th><td>BDT <?php echo e($booking['total_amount']); ?></td></tr>
                <tr><th>Booking Status</th><td><span class="status status_<?php echo strtolower($booking['booking_status']); ?>"><?php echo e($booking['booking_status']); ?></span></td></tr>
                <?php if ($payment) { ?>
                <tr><th>Payment</th><td><?php echo e($payment['payment_id'] . ' - ' . $payment['payment_method'] . ' - ' . $payment['payment_status']); ?></td></tr>
                <?php } ?>
            </table>
        </div>

        <div class="card">
            <h3>Passengers on this reservation</h3>
            <table>
                <tr>
                    <th>Ticket</th>
                    <th>Passenger</th>
                    <th>NID</th>
                    <th>Flight</th>
                    <th>Route</th>
                    <th>Departure</th>
                    <th>Seat</th>
                    <th>Class</th>
                    <th>Original Fare</th>
                    <th>Discount</th>
                    <th>Final Fare</th>
                    <th>Ticket Status</th>
                    <th>Action</th>
                </tr>
                <?php while ($t = mysqli_fetch_assoc($tickets)) {
                    $original = ($t['class'] == 'Business')
                                ? $t['base_fare'] * BUSINESS_MULTIPLIER
                                : $t['base_fare'];
                    $disc = $original - $t['ticket_price'];
                    if ($disc < 0) { $disc = 0; }
                ?>
                <tr>
                    <td><?php echo e($t['ticket_id']); ?></td>
                    <td><?php echo e($t['firstName'] . ' ' . $t['lastName']); ?></td>
                    <td><?php echo e($t['NID']); ?></td>
                    <td><?php echo e($t['flight_id']); ?></td>
                    <td><?php echo e($t['dep_code'] . ' - ' . $t['arr_code']); ?></td>
                    <td><?php echo e($t['departure_time']); ?></td>
                    <td><?php echo e($t['seat_no']); ?></td>
                    <td><?php echo e($t['class']); ?></td>
                    <td><?php echo number_format($original, 2); ?></td>
                    <td><?php echo number_format($disc, 2); ?></td>
                    <td><?php echo number_format($t['ticket_price'], 2); ?></td>
                    <td><span class="status status_<?php echo strtolower($t['ticket_status']); ?>"><?php echo e($t['ticket_status']); ?></span></td>
                    <td>
                        <?php if ($t['passenger_id'] == $passenger_id && $t['ticket_status'] == 'Confirmed') { ?>
                            <a class="btn btn_small" href="seat_swap.php?ticket_id=<?php echo e($t['ticket_id']); ?>">Swap Seat</a>
                        <?php } ?>
                    </td>
                </tr>
                <?php } ?>
            </table>
        </div>

        <a class="btn" href="my_bookings.php">Back to My Bookings</a>
        <?php if ($booking['booking_status'] == 'Confirmed' && $booking['passenger_id'] == $passenger_id) { ?>
            <a class="btn btn_danger" href="cancel_booking.php?id=<?php echo e($booking_id); ?>"
               onclick="return confirm('Cancel this whole reservation?');">Cancel Reservation</a>
        <?php } ?>
    </div>
</main>
</body>
</html>
