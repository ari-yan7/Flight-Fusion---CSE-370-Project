<?php
require_once('DBconnect.php');
ff_require_passenger();

if (!isset($_POST['reward_type'])) {
    header("Location: my_rewards.php");
    exit();
}

$reward_type  = $_POST['reward_type'];
$passenger_id = $_SESSION['passenger_id'];

/* the cost of every reward comes from DBconnect.php only */
if      ($reward_type == 'Free Ticket')   { $cost = REDEEM_FREE_TICKET; }
else if ($reward_type == 'Upgrade')       { $cost = REDEEM_UPGRADE; }
else if ($reward_type == 'Lounge Access') { $cost = REDEEM_LOUNGE; }
else {
    header("Location: my_rewards.php?error=reward");
    exit();
}

$reward_id = ff_reward_account($conn, $passenger_id, NULL);

$stmt = $conn->prepare("SELECT total_points FROM reward_account WHERE reward_id = ?");
$stmt->bind_param("s", $reward_id);
$stmt->execute();
$balance = (int)$stmt->get_result()->fetch_assoc()['total_points'];
$stmt->close();

if ($balance < $cost) {
    header("Location: my_rewards.php?error=points");
    exit();
}

$conn->begin_transaction();
try {
    ff_add_points($conn, $passenger_id, -$cost, 'Redeemed', NULL, 'Redeemed: ' . $reward_type);
    $conn->commit();
    header("Location: my_rewards.php?msg=" . $cost . " points redeemed for " . $reward_type);
    exit();
} catch (Exception $ex) {
    $conn->rollback();
    header("Location: my_rewards.php?error=db");
    exit();
}
?>
