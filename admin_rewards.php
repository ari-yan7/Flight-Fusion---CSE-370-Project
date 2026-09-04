<?php
require_once('DBconnect.php');
ff_require_admin();
 
$people = mysqli_query($conn, "SELECT passenger_id FROM reward_account");
while ($one = mysqli_fetch_assoc($people)) {
    ff_update_membership($conn, $one['passenger_id']);
}

$accounts = mysqli_query($conn,
    "SELECT ra.*, p.firstName, p.lastName
     FROM reward_account ra JOIN passenger p ON ra.passenger_id = p.passenger_id
     ORDER BY ra.total_points DESC");

$transactions = mysqli_query($conn,
    "SELECT rt.*, ra.passenger_id, p.firstName, p.lastName
     FROM reward_transaction rt
     JOIN reward_account ra ON rt.reward_id = ra.reward_id
     JOIN passenger p ON ra.passenger_id = p.passenger_id
     ORDER BY rt.transaction_date DESC, rt.transaction_id DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FlightFusion | Frequent Flyer</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<?php require_once('nav.php'); ?>
<main>
    <div class="wide_box">
        <h2 class="page_title">Frequent Flyer Monitoring</h2>

        <div class="card">
            <h3>Reward Accounts</h3>
            <table>
                <tr><th>Reward ID</th><th>Passenger</th><th>Opening Payment</th><th>Total Points</th><th>Membership Level</th></tr>
                <?php while ($a = mysqli_fetch_assoc($accounts)) { ?>
                <tr>
                    <td><?php echo e($a['reward_id']); ?></td>
                    <td><?php echo e($a['firstName'] . ' ' . $a['lastName'] . ' (' . $a['passenger_id'] . ')'); ?></td>
                    <td><?php echo e($a['payment_id']); ?></td>
                    <td><?php echo e($a['total_points']); ?></td>
                    <td><?php echo e($a['membership_level']); ?></td>
                </tr>
                <?php } ?>
            </table>
        </div>

        <div class="card">
            <h3>Reward Transactions</h3>
            <table>
                <tr><th>Transaction</th><th>Reward Account</th><th>Passenger</th><th>Points</th><th>Type</th><th>Date</th><th>Details</th></tr>
                <?php while ($t = mysqli_fetch_assoc($transactions)) { ?>
                <tr>
                    <td><?php echo e($t['transaction_id']); ?></td>
                    <td><?php echo e($t['reward_id']); ?></td>
                    <td><?php echo e($t['firstName'] . ' ' . $t['lastName']); ?></td>
                    <td><?php echo e($t['points']); ?></td>
                    <td><?php echo e($t['transaction_type']); ?></td>
                    <td><?php echo e($t['transaction_date']); ?></td>
                    <td><?php echo e($t['description']); ?></td>
                </tr>
                <?php } ?>
            </table>
        </div>
    </div>
</main>
</body>
</html>
