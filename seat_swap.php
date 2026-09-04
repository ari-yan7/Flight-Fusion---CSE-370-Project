<?php
require_once('DBconnect.php');
ff_require_passenger();

if (!isset($_GET['ticket_id'])) {
    header("Location: swap_requests.php");
    exit();
}

$ticket_id    = $_GET['ticket_id'];
$passenger_id = $_SESSION['passenger_id'];

$stmt = $conn->prepare("SELECT t.*, f.departure_time, f.flight_status
                        FROM ticket t JOIN flight f ON t.flight_id = f.flight_id
                        WHERE t.ticket_id = ?");
$stmt->bind_param("s", $ticket_id);
$stmt->execute();
$my_ticket = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$my_ticket || $my_ticket['passenger_id'] != $passenger_id) {
    header("Location: swap_requests.php?error=owner");
    exit();
}
if ($my_ticket['ticket_status'] != 'Confirmed') {
    header("Location: swap_requests.php?error=ticket");
    exit();
}

$stmt = $conn->prepare("SELECT t.ticket_id, t.seat_no, t.class
                        FROM ticket t
                        WHERE t.flight_id = ? AND t.ticket_status = 'Confirmed'
                          AND t.ticket_id <> ? AND t.class = ?
                        ORDER BY t.seat_no");
$stmt->bind_param("sss", $my_ticket['flight_id'], $ticket_id, $my_ticket['class']);
$stmt->execute();
$others = $stmt->get_result();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FlightFusion | Seat Swap</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<?php require_once('nav.php'); ?>
<main>
    <div class="wide_box">
        <h2 class="page_title">Request a Seat Swap</h2>
        <p class="page_intro">Choose a passenger sitting in the same travel class on the same
           flight. Your seats are only exchanged once they approve the request.</p>

        <?php if (isset($_GET['error'])) { ?>
            <div class="msg msg_error">
            <?php
                if ($_GET['error'] == 'exists')  echo "There is already an active swap request for one of these seats.";
                else if ($_GET['error'] == 'class') echo "Seats can only be swapped inside the same travel class.";
                else if ($_GET['error'] == 'flight') echo "Both passengers must be on the same flight.";
                else echo "The request could not be created.";
            ?>
            </div>
        <?php } ?>

        <div class="card card_blue">
            <h3>Your Seat</h3>
            <div class="seat_summary">
                <div><span>Ticket</span><strong><?php echo e($my_ticket['ticket_id']); ?></strong></div>
                <div><span>Flight</span><strong><?php echo e($my_ticket['flight_id']); ?></strong></div>
                <div><span>Departure</span><strong><?php echo e($my_ticket['departure_time']); ?></strong></div>
                <div><span>Seat</span><span class="seat_badge"><?php echo e($my_ticket['seat_no']); ?></span></div>
                <div><span>Class</span><strong><?php echo e($my_ticket['class']); ?></strong></div>
            </div>
        </div>

        <div class="card card_green">
            <div class="card_head">
                <h3>Choose the seat you want to swap with</h3>
                <span class="card_note">Same flight, same class only</span>
            </div>
            <?php if (mysqli_num_rows($others) > 0) { ?>
            <form action="insert_swap.php" method="post">
                <input type="hidden" name="requester_ticket_id" value="<?php echo e($ticket_id); ?>">

                <label for="target_ticket_id">Passenger on flight <?php echo e($my_ticket['flight_id']); ?>:</label>
                <select name="target_ticket_id" id="target_ticket_id" required>
                    <?php while ($o = mysqli_fetch_assoc($others)) { ?>
                        <option value="<?php echo e($o['ticket_id']); ?>">
                            <?php echo e('Seat ' . $o['seat_no'] . ' (' . $o['class'] . ')'); ?>
                        </option>
                    <?php } ?>
                </select>

                <button type="submit">Send Swap Request</button>
            </form>
            <?php } else { ?>
                <p class="empty_row">There is nobody else with a confirmed
                   <?php echo e($my_ticket['class']); ?> seat on this flight yet.</p>
            <?php } ?>
        </div>

        <a class="btn" href="swap_requests.php">Back to Seat Swap</a>
    </div>
</main>
</body>
</html>
