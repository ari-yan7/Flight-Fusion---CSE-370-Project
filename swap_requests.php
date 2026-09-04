<?php
require_once('DBconnect.php');
ff_require_passenger();

$passenger_id = $_SESSION['passenger_id'];

$stmt = $conn->prepare("SELECT s.*, rt.seat_no AS req_seat, rt.flight_id,
                               tt.seat_no AS tar_seat
                        FROM seat_swap_request s
                        JOIN ticket rt ON s.requester_ticket_id = rt.ticket_id
                        JOIN ticket tt ON s.target_ticket_id    = tt.ticket_id
                        WHERE tt.passenger_id = ?
                        ORDER BY s.request_date DESC");
$stmt->bind_param("s", $passenger_id);
$stmt->execute();
$incoming = $stmt->get_result();
$stmt->close();

/* requests I sent */
$stmt = $conn->prepare("SELECT s.*, rt.seat_no AS req_seat, rt.flight_id,
                               tt.seat_no AS tar_seat
                        FROM seat_swap_request s
                        JOIN ticket rt ON s.requester_ticket_id = rt.ticket_id
                        JOIN ticket tt ON s.target_ticket_id    = tt.ticket_id
                        WHERE rt.passenger_id = ?
                        ORDER BY s.request_date DESC");
$stmt->bind_param("s", $passenger_id);
$stmt->execute();
$outgoing = $stmt->get_result();
$stmt->close();

$stmt = $conn->prepare("SELECT t.*, f.departure_time FROM ticket t
                        JOIN flight f ON t.flight_id = f.flight_id
                        WHERE t.passenger_id = ? AND t.ticket_status = 'Confirmed'
                        ORDER BY f.departure_time");
$stmt->bind_param("s", $passenger_id);
$stmt->execute();
$my_tickets = $stmt->get_result();
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
        <h2 class="page_title">Seat Swap</h2>
        <p class="page_intro">Not happy with your seat? Ask another passenger on the same
           flight to trade with you. A swap only happens after both sides agree.</p>

        <?php if (isset($_GET['msg'])) { ?>
            <div class="msg msg_ok"><?php echo e($_GET['msg']); ?></div>
        <?php } ?>
        <?php if (isset($_GET['error'])) { ?>
            <div class="msg msg_error">
            <?php
                if ($_GET['error'] == 'owner')     echo "That ticket does not belong to you.";
                else if ($_GET['error'] == 'self') echo "You cannot swap a seat with yourself.";
                else if ($_GET['error'] == 'ticket') echo "Only confirmed tickets can be swapped.";
                else if ($_GET['error'] == 'notfound') echo "That swap request does not exist.";
                else if ($_GET['error'] == 'expired')  echo "That request is no longer pending.";
                else if ($_GET['error'] == 'flight')   echo "Both passengers must be on the same flight.";
                else if ($_GET['error'] == 'class')    echo "Seats can only be swapped inside the same travel class.";
                else echo "The seat swap failed. No seats were changed.";
            ?>
            </div>
        <?php } ?>

        <div class="card card_blue">
            <div class="card_head">
                <h3>Start a new swap request</h3>
                <span class="card_note">Pick one of your confirmed tickets</span>
            </div>
            <div class="table_wrap">
            <table>
                <tr><th>Ticket</th><th>Flight</th><th>Departure</th><th>Seat</th><th>Class</th><th>Action</th></tr>
                <?php if (mysqli_num_rows($my_tickets) > 0) {
                    while ($t = mysqli_fetch_assoc($my_tickets)) { ?>
                <tr>
                    <td><?php echo e($t['ticket_id']); ?></td>
                    <td><?php echo e($t['flight_id']); ?></td>
                    <td><?php echo e($t['departure_time']); ?></td>
                    <td><span class="seat_badge"><?php echo e($t['seat_no']); ?></span></td>
                    <td><?php echo e($t['class']); ?></td>
                    <td><a class="btn btn_small" href="seat_swap.php?ticket_id=<?php echo e($t['ticket_id']); ?>">Request Swap</a></td>
                </tr>
                <?php } } else { ?>
                <tr><td colspan="6" class="empty_row">You have no confirmed tickets.</td></tr>
                <?php } ?>
            </table>
            </div>
        </div>

        <div class="card card_orange">
            <div class="card_head">
                <h3>Requests waiting for your answer</h3>
                <span class="card_note">Approve or reject each request</span>
            </div>
            <div class="table_wrap">
            <table>
                <tr><th>Swap ID</th><th>Flight</th><th>Their Seat</th><th>Your Seat</th><th>Requested</th><th>Status</th><th>Action</th></tr>
                <?php if (mysqli_num_rows($incoming) > 0) {
                    while ($s = mysqli_fetch_assoc($incoming)) { ?>
                <tr>
                    <td><?php echo e($s['swap_id']); ?></td>
                    <td><?php echo e($s['flight_id']); ?></td>
                    <td><span class="seat_badge seat_badge_grey"><?php echo e($s['req_seat']); ?></span></td>
                    <td><span class="seat_badge"><?php echo e($s['tar_seat']); ?></span></td>
                    <td><?php echo e($s['request_date']); ?></td>
                    <td><span class="status status_<?php echo strtolower($s['swap_status']); ?>"><?php echo e($s['swap_status']); ?></span></td>
                    <td>
                        <?php if ($s['swap_status'] == 'Pending') { ?>
                            <a class="btn btn_small btn_success" href="respond_swap.php?id=<?php echo e($s['swap_id']); ?>&action=approve">Approve</a>
                            <a class="btn btn_small btn_danger"  href="respond_swap.php?id=<?php echo e($s['swap_id']); ?>&action=reject">Reject</a>
                        <?php } ?>
                    </td>
                </tr>
                <?php } } else { ?>
                <tr><td colspan="7" class="empty_row">No incoming requests.</td></tr>
                <?php } ?>
            </table>
            </div>
        </div>

        <div class="card card_green">
            <div class="card_head">
                <h3>Requests you sent</h3>
                <span class="card_note">Waiting for the other passenger</span>
            </div>
            <div class="table_wrap">
            <table>
                <tr><th>Swap ID</th><th>Flight</th><th>Your Seat</th><th>Their Seat</th><th>Requested</th><th>Status</th><th>Action</th></tr>
                <?php if (mysqli_num_rows($outgoing) > 0) {
                    while ($s = mysqli_fetch_assoc($outgoing)) { ?>
                <tr>
                    <td><?php echo e($s['swap_id']); ?></td>
                    <td><?php echo e($s['flight_id']); ?></td>
                    <td><span class="seat_badge"><?php echo e($s['req_seat']); ?></span></td>
                    <td><span class="seat_badge seat_badge_grey"><?php echo e($s['tar_seat']); ?></span></td>
                    <td><?php echo e($s['request_date']); ?></td>
                    <td><span class="status status_<?php echo strtolower($s['swap_status']); ?>"><?php echo e($s['swap_status']); ?></span></td>
                    <td>
                        <?php if ($s['swap_status'] == 'Pending') { ?>
                            <a class="btn btn_small btn_danger" href="respond_swap.php?id=<?php echo e($s['swap_id']); ?>&action=cancel">Cancel</a>
                        <?php } ?>
                    </td>
                </tr>
                <?php } } else { ?>
                <tr><td colspan="7" class="empty_row">You have not sent any request.</td></tr>
                <?php } ?>
            </table>
            </div>
        </div>
    </div>
</main>
</body>
</html>
