<?php
require_once('DBconnect.php');
ff_require_admin();

$sql = "SELECT p.*,
               (SELECT COUNT(*) FROM ticket t WHERE t.passenger_id = p.passenger_id
                  AND t.ticket_status = 'Confirmed') AS tickets,
               (SELECT COUNT(*) FROM booking b WHERE b.passenger_id = p.passenger_id) AS bookings,
               sp.student_id, sp.institution,
               (SELECT ra.total_points FROM reward_account ra WHERE ra.passenger_id = p.passenger_id) AS points
        FROM passenger p
        LEFT JOIN student_passenger sp ON sp.passenger_id = p.passenger_id
        ORDER BY p.passenger_id";
$result = mysqli_query($conn, $sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FlightFusion | Passengers</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<?php require_once('nav.php'); ?>
<main>
    <div class="wide_box">
        <h2 class="page_title">Passenger Information</h2>
        <table>
            <tr>
                <th>ID</th><th>Name</th><th>DOB</th><th>Gender</th><th>NID</th>
                <th>Passport</th><th>Nationality</th><th>Phone</th><th>Email</th>
                <th>Student ID</th><th>Bookings</th><th>Active Tickets</th><th>Points</th>
            </tr>
            <?php while ($row = mysqli_fetch_assoc($result)) { ?>
            <tr>
                <td><?php echo e($row['passenger_id']); ?></td>
                <td><?php echo e($row['firstName'] . ' ' . $row['lastName']); ?></td>
                <td><?php echo e($row['DOB']); ?></td>
                <td><?php echo e($row['gender']); ?></td>
                <td><?php echo e($row['NID']); ?></td>
                <td><?php echo e($row['passport_no']); ?></td>
                <td><?php echo e($row['nationality']); ?></td>
                <td><?php echo e($row['phoneNumber']); ?></td>
                <td><?php echo e($row['email']); ?></td>
                <td><?php echo e($row['student_id']); ?></td>
                <td><?php echo e($row['bookings']); ?></td>
                <td><?php echo e($row['tickets']); ?></td>
                <td><?php echo $row['points'] === NULL ? '0' : e($row['points']); ?></td>
            </tr>
            <?php } ?>
        </table>
    </div>
</main>
</body>
</html>
