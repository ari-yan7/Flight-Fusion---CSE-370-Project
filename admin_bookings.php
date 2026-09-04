<?php
require_once('DBconnect.php');
ff_require_admin();

$sql = "SELECT b.*, p.firstName, p.lastName, pay.payment_id, pay.payment_method, pay.payment_status,
               (SELECT COUNT(*) FROM ticket t WHERE t.booking_id = b.booking_id) AS pax
        FROM booking b
        JOIN passenger p ON b.passenger_id = p.passenger_id
        LEFT JOIN payment pay ON pay.booking_id = b.booking_id
        ORDER BY b.booking_date DESC";
$bookings = mysqli_query($conn, $sql);

$tickets = mysqli_query($conn,
    "SELECT t.*, p.firstName, p.lastName, f.departure_time
     FROM ticket t
     JOIN passenger p ON t.passenger_id = p.passenger_id
     JOIN flight f    ON t.flight_id    = f.flight_id
     ORDER BY t.ticket_id");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FlightFusion | Bookings</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<?php require_once('nav.php'); ?>
<main>
    <div class="wide_box">
        <h2 class="page_title">Bookings &amp; Tickets (monitoring)</h2>

        <div class="card">
            <h3>Reservations</h3>
            <table>
                <tr><th>Booking</th><th>Lead Passenger</th><th>Date</th><th>Passengers</th>
                    <th>Total</th><th>Booking Status</th><th>Payment</th><th>Payment Status</th></tr>
                <?php while ($b = mysqli_fetch_assoc($bookings)) { ?>
                <tr>
                    <td><?php echo e($b['booking_id']); ?></td>
                    <td><?php echo e($b['firstName'] . ' ' . $b['lastName']); ?></td>
                    <td><?php echo e($b['booking_date']); ?></td>
                    <td><?php echo e($b['pax']); ?></td>
                    <td><?php echo e($b['total_amount']); ?></td>
                    <td><span class="status status_<?php echo strtolower($b['booking_status']); ?>"><?php echo e($b['booking_status']); ?></span></td>
                    <td><?php echo e($b['payment_method']); ?></td>
                    <td><?php echo e($b['payment_status']); ?></td>
                </tr>
                <?php } ?>
            </table>
        </div>

        <div class="card">
            <h3>Tickets</h3>
            <table>
                <tr><th>Ticket</th><th>Booking</th><th>Passenger</th><th>Flight</th>
                    <th>Departure</th><th>Seat</th><th>Class</th><th>Price</th><th>Status</th></tr>
                <?php while ($t = mysqli_fetch_assoc($tickets)) { ?>
                <tr>
                    <td><?php echo e($t['ticket_id']); ?></td>
                    <td><?php echo e($t['booking_id']); ?></td>
                    <td><?php echo e($t['firstName'] . ' ' . $t['lastName']); ?></td>
                    <td><?php echo e($t['flight_id']); ?></td>
                    <td><?php echo e($t['departure_time']); ?></td>
                    <td><?php echo e($t['seat_no']); ?></td>
                    <td><?php echo e($t['class']); ?></td>
                    <td><?php echo e($t['ticket_price']); ?></td>
                    <td><span class="status status_<?php echo strtolower($t['ticket_status']); ?>"><?php echo e($t['ticket_status']); ?></span></td>
                </tr>
                <?php } ?>
            </table>
        </div>
    </div>
</main>
</body>
</html>
