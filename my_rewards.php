<?php
require_once('DBconnect.php');
ff_require_passenger();

$passenger_id = $_SESSION['passenger_id'];
$reward_id    = ff_reward_account($conn, $passenger_id, NULL);

ff_update_membership($conn, $passenger_id);

$stmt = $conn->prepare("SELECT * FROM reward_account WHERE reward_id = ?");
$stmt->bind_param("s", $reward_id);
$stmt->execute();
$account = $stmt->get_result()->fetch_assoc();
$stmt->close();

$stmt = $conn->prepare("SELECT * FROM reward_transaction WHERE reward_id = ?
                        ORDER BY transaction_date DESC, transaction_id DESC");
$stmt->bind_param("s", $reward_id);
$stmt->execute();
$history = $stmt->get_result();
$stmt->close();
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
        <h2 class="page_title">Frequent Flyer Rewards</h2>

        <?php if (isset($_GET['msg'])) { ?>
            <div class="msg msg_ok"><?php echo e($_GET['msg']); ?></div>
        <?php } ?>
        <?php if (isset($_GET['error'])) { ?>
            <div class="msg msg_error">
            <?php
                if ($_GET['error'] == 'points') echo "You do not have enough points for that reward.";
                else if ($_GET['error'] == 'reward') echo "Unknown reward option.";
                else echo "The redemption failed. Your points were not touched.";
            ?>
            </div>
        <?php } ?>

        <div class="stat_grid">
            <div class="stat_box"><h3><?php echo e($account['total_points']); ?></h3><p>Points Balance</p></div>
            <div class="stat_box"><h3><?php echo e($account['membership_level']); ?></h3><p>Membership Level</p></div>
            <div class="stat_box"><h3><?php echo e($account['reward_id']); ?></h3><p>Reward Account</p></div>
        </div>

        <div class="card">
            <h3>How points are earned</h3>
            <p>You earn <?php echo POINTS_RATE * 100; ?> points for every 100 BDT actually paid,
               for every passenger on your reservation. Points are taken back automatically
               if a booking is cancelled.</p>
        </div>

        <div class="card">
            <h3>How the membership level works</h3>
            <p>Your level depends on how many tickets you have bought, not on your points
               balance. You need <?php echo GOLD_TRIPS; ?> tickets for Gold and
               <?php echo PLATINUM_TRIPS; ?> tickets for Platinum. Redeeming your points
               spends the points only - it never lowers your level.</p>
        </div>

        <div class="card">
            <h3>Redeem your points</h3>
            <form action="redeem_reward.php" method="post">
                <label for="reward_type">Reward:</label>
                <select name="reward_type" id="reward_type">
                    <option value="Free Ticket">Free Ticket Voucher - <?php echo REDEEM_FREE_TICKET; ?> points</option>
                    <option value="Upgrade">Cabin Upgrade - <?php echo REDEEM_UPGRADE; ?> points</option>
                    <option value="Lounge Access">Lounge Access - <?php echo REDEEM_LOUNGE; ?> points</option>
                </select>
                <button type="submit">Redeem</button>
            </form>
        </div>

        <div class="card">
            <h3>Transaction History</h3>
            <table>
                <tr><th>Transaction ID</th><th>Date</th><th>Type</th><th>Points</th><th>Details</th></tr>
                <?php if (mysqli_num_rows($history) > 0) {
                    while ($h = mysqli_fetch_assoc($history)) { ?>
                <tr>
                    <td><?php echo e($h['transaction_id']); ?></td>
                    <td><?php echo e($h['transaction_date']); ?></td>
                    <td><?php echo e($h['transaction_type']); ?></td>
                    <td><?php echo e($h['points']); ?></td>
                    <td><?php echo e($h['description']); ?></td>
                </tr>
                <?php } } else { ?>
                <tr><td colspan="5">No reward activity yet.</td></tr>
                <?php } ?>
            </table>
        </div>
    </div>
</main>
</body>
</html>
