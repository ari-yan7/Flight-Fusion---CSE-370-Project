<?php
require_once('DBconnect.php');
ff_require_admin();

$sql = "SELECT s.*, rt.flight_id, rt.seat_no AS req_seat, tt.seat_no AS tar_seat,
               rp.firstName AS req_first, rp.lastName AS req_last,
               tp.firstName AS tar_first, tp.lastName AS tar_last
        FROM seat_swap_request s
        JOIN ticket rt ON s.requester_ticket_id = rt.ticket_id
        JOIN ticket tt ON s.target_ticket_id    = tt.ticket_id
        JOIN passenger rp ON rt.passenger_id = rp.passenger_id
        JOIN passenger tp ON tt.passenger_id = tp.passenger_id
        ORDER BY s.request_date DESC";
$result = mysqli_query($conn, $sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FlightFusion | Seat Swaps</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<?php require_once('nav.php'); ?>
<main>
    <div class="wide_box">
        <h2 class="page_title">Seat Swap Monitoring</h2>
        <div class="msg msg_info">
            Seat swaps are settled between the two passengers. Staff do not approve them -
            this page is for monitoring only.
        </div>
        <table>
            <tr>
                <th>Swap ID</th><th>Flight</th><th>Requester</th><th>Requester Seat</th>
                <th>Target</th><th>Target Seat</th><th>Requested</th>
                <th>Requester Status</th><th>Target Status</th><th>Swap Status</th>
            </tr>
            <?php while ($row = mysqli_fetch_assoc($result)) { ?>
            <tr>
                <td><?php echo e($row['swap_id']); ?></td>
                <td><?php echo e($row['flight_id']); ?></td>
                <td><?php echo e($row['req_first'] . ' ' . $row['req_last']); ?></td>
                <td><?php echo e($row['req_seat']); ?></td>
                <td><?php echo e($row['tar_first'] . ' ' . $row['tar_last']); ?></td>
                <td><?php echo e($row['tar_seat']); ?></td>
                <td><?php echo e($row['request_date']); ?></td>
                <td><?php echo e($row['requester_status']); ?></td>
                <td><?php echo e($row['target_status']); ?></td>
                <td><span class="status status_<?php echo strtolower($row['swap_status']); ?>"><?php echo e($row['swap_status']); ?></span></td>
            </tr>
            <?php } ?>
        </table>
    </div>
</main>
</body>
</html>
