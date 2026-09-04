<?php
require_once('DBconnect.php');
ff_require_passenger();

$passenger_id = $_SESSION['passenger_id'];

$stmt = $conn->prepare("SELECT w.*, f.flight_status, f.delay_minutes, f.delay_reason,
                               dep.airport_code AS dep_code, arr.airport_code AS arr_code
                        FROM weather_compensation w
                        JOIN flight f    ON w.flight_id = f.flight_id
                        JOIN airport dep ON f.departure_airport_id = dep.airport_id
                        JOIN airport arr ON f.arrival_airport_id   = arr.airport_id
                        WHERE w.passenger_id = ?
                        ORDER BY w.issue_date DESC");
$stmt->bind_param("s", $passenger_id);
$stmt->execute();
$rows = $stmt->get_result();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FlightFusion | Weather Vouchers</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<?php require_once('nav.php'); ?>
<main>
    <div class="wide_box">
        <h2 class="page_title">Weather Guarantee</h2>
        <p class="page_intro">Bad weather is not your fault, so you never have to claim anything.
           If a flight is delayed or cancelled by weather, the compensation is added by itself.</p>

        <div class="weather_grid">
            <div class="weather_card">
                <div class="weather_icon">&#9748;</div>
                <h3><?php echo WEATHER_MIN_DELAY; ?> min</h3>
                <p>Delay needed before the guarantee starts</p>
            </div>
            <div class="weather_card">
                <div class="weather_icon">&#127783;</div>
                <h3>BDT <?php echo WEATHER_DELAY_VOUCHER; ?></h3>
                <p>Voucher for a weather delay, plus <?php echo WEATHER_DELAY_POINTS; ?> bonus miles</p>
            </div>
            <div class="weather_card">
                <div class="weather_icon">&#127786;</div>
                <h3>BDT <?php echo WEATHER_CANCEL_VOUCHER; ?></h3>
                <p>Voucher if the flight is cancelled, plus <?php echo WEATHER_CANCEL_POINTS; ?> bonus miles</p>
            </div>
        </div>

        <div class="card card_blue">
            <h3>The promise</h3>
            <ul class="rule_list">
                <li>A delay of <?php echo WEATHER_MIN_DELAY; ?> minutes or more caused by weather pays
                    BDT <?php echo WEATHER_DELAY_VOUCHER; ?> and <?php echo WEATHER_DELAY_POINTS; ?> bonus miles.</li>
                <li>A flight cancelled because of weather pays
                    BDT <?php echo WEATHER_CANCEL_VOUCHER; ?> and <?php echo WEATHER_CANCEL_POINTS; ?> bonus miles.</li>
                <li>The compensation is issued automatically. There is no form to fill in.</li>
                <li>Nobody is paid twice for the same flight.</li>
            </ul>
        </div>

        <div class="card card_green">
            <div class="card_head">
                <h3>My Compensation</h3>
                <span class="card_note">Vouchers and bonus miles already issued to you</span>
            </div>
            <div class="table_wrap">
            <table>
                <tr>
                    <th>Compensation ID</th>
                    <th>Flight</th>
                    <th>Route</th>
                    <th>Reason</th>
                    <th>Type</th>
                    <th>Voucher (BDT)</th>
                    <th>Bonus Miles</th>
                    <th>Issued</th>
                </tr>
                <?php if (mysqli_num_rows($rows) > 0) {
                    while ($r = mysqli_fetch_assoc($rows)) { ?>
                <tr>
                    <td><?php echo e($r['compensation_id']); ?></td>
                    <td><?php echo e($r['flight_id']); ?></td>
                    <td><?php echo e($r['dep_code'] . ' - ' . $r['arr_code']); ?></td>
                    <td><?php echo e($r['delay_reason'] . ($r['delay_minutes'] > 0 ? ' (' . $r['delay_minutes'] . ' min)' : '')); ?></td>
                    <td><?php echo e($r['compensation_type']); ?></td>
                    <td><?php echo e($r['voucher_amount']); ?></td>
                    <td><?php echo e($r['bonus_points']); ?></td>
                    <td><?php echo e($r['issue_date']); ?></td>
                </tr>
                <?php } } else { ?>
                <tr><td colspan="8" class="empty_row">No weather compensation has been issued to you.</td></tr>
                <?php } ?>
            </table>
            </div>
        </div>
    </div>
</main>
</body>
</html>
