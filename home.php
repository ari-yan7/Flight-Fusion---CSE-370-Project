<?php
require_once('DBconnect.php');
ff_require_passenger();

$passenger_id = $_SESSION['passenger_id'];

$stmt = $conn->prepare("SELECT * FROM passenger WHERE passenger_id = ?");
$stmt->bind_param("s", $passenger_id);
$stmt->execute();
$me = $stmt->get_result()->fetch_assoc();
$stmt->close();

/* small counters for the dashboard tiles */
$stmt = $conn->prepare("SELECT COUNT(*) AS c FROM booking
                        WHERE passenger_id = ? AND booking_status = 'Confirmed'");
$stmt->bind_param("s", $passenger_id);
$stmt->execute();
$bookings = $stmt->get_result()->fetch_assoc()['c'];
$stmt->close();

$stmt = $conn->prepare("SELECT COUNT(*) AS c FROM waitlist
                        WHERE passenger_id = ? AND status IN ('Waiting','Promoted')");
$stmt->bind_param("s", $passenger_id);
$stmt->execute();
$waiting = $stmt->get_result()->fetch_assoc()['c'];
$stmt->close();

ff_update_membership($conn, $passenger_id);

$stmt = $conn->prepare("SELECT total_points, membership_level FROM reward_account
                        WHERE passenger_id = ?");
$stmt->bind_param("s", $passenger_id);
$stmt->execute();
$reward = $stmt->get_result()->fetch_assoc();
$stmt->close();
$points = $reward ? (int)$reward['total_points'] : 0;
$level  = $reward ? $reward['membership_level'] : 'Silver';

/* seat swap requests waiting for MY answer */
$stmt = $conn->prepare("SELECT COUNT(*) AS c
                        FROM seat_swap_request s
                        JOIN ticket t ON s.target_ticket_id = t.ticket_id
                        WHERE t.passenger_id = ? AND s.swap_status = 'Pending'");
$stmt->bind_param("s", $passenger_id);
$stmt->execute();
$pending_swaps = $stmt->get_result()->fetch_assoc()['c'];
$stmt->close();

$stmt = $conn->prepare("SELECT w.*, f.flight_id FROM waitlist w
                        JOIN flight f ON w.flight_id = f.flight_id
                        WHERE w.passenger_id = ? AND w.status = 'Promoted'");
$stmt->bind_param("s", $passenger_id);
$stmt->execute();
$promoted = $stmt->get_result();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FlightFusion | Home</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<?php require_once('nav.php'); ?>
<main>
    <div class="wide_box">
        <h2 class="page_title">Welcome, <?php echo e($me['firstName'] . ' ' . $me['lastName']); ?></h2>

        <?php if (isset($_GET['error']) && $_GET['error'] == 'denied') { ?>
            <div class="msg msg_error">You are not allowed to open the admin area.</div>
        <?php } ?>

        <?php while ($row = mysqli_fetch_assoc($promoted)) { ?>
            <div class="msg msg_ok">
                Good news - a seat opened up on flight <?php echo e($row['flight_id']); ?>.
                <a class="btn btn_small" href="book_flight.php?flight_id=<?php echo e($row['flight_id']); ?>">Book it now</a>
            </div>
        <?php } ?>

        <?php if ($pending_swaps > 0) { ?>
            <div class="msg msg_info">
                You have <?php echo $pending_swaps; ?> seat swap request(s) waiting for your answer.
                <a class="btn btn_small" href="swap_requests.php">Review</a>
            </div>
        <?php } ?>

        <div class="stat_grid">
            <div class="stat_box"><h3><?php echo $bookings; ?></h3><p>Confirmed Bookings</p></div>
            <div class="stat_box"><h3><?php echo $waiting; ?></h3><p>Waitlist Entries</p></div>
            <div class="stat_box"><h3><?php echo $points; ?></h3><p>Reward Points</p></div>
            <div class="stat_box"><h3><?php echo e($level); ?></h3><p>Membership Level</p></div>
        </div>

        <div class="card">
            <h3>What would you like to do?</h3>
            <div class="link_grid">
                <a href="search_flights.php">Search Flights</a>
                <a href="my_bookings.php">My Bookings</a>
                <a href="my_waitlist.php">My Waitlist</a>
                <a href="swap_requests.php">Seat Swap</a>
                <a href="my_rewards.php">Frequent Flyer</a>
                <a href="my_discounts.php">Discounts</a>
                <a href="my_vouchers.php">Weather Vouchers</a>
            </div>
        </div>

        <div class="card">
            <h3>My Details</h3>
            <table>
                <tr><th>Passenger ID</th><td><?php echo e($me['passenger_id']); ?></td></tr>
                <tr><th>Name</th><td><?php echo e($me['firstName'] . ' ' . $me['lastName']); ?></td></tr>
                <tr><th>Date of Birth</th><td><?php echo e($me['DOB']); ?></td></tr>
                <tr><th>Gender</th><td><?php echo e($me['gender']); ?></td></tr>
                <tr><th>NID</th><td><?php echo e($me['NID']); ?></td></tr>
                <tr><th>Passport No</th><td><?php echo e($me['passport_no']); ?></td></tr>
                <tr><th>Nationality</th><td><?php echo e($me['nationality']); ?></td></tr>
                <tr><th>Phone</th><td><?php echo e($me['phoneNumber']); ?></td></tr>
                <tr><th>Email</th><td><?php echo e($me['email']); ?></td></tr>
            </table>
        </div>
    </div>
</main>
</body>
</html>
