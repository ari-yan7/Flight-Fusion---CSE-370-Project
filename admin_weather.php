<?php
require_once('DBconnect.php');
ff_require_admin();

$flights = mysqli_query($conn, "SELECT flight_id, flight_status, delay_minutes, delay_reason
                                FROM flight ORDER BY departure_time");

$affected = mysqli_query($conn,
    "SELECT f.*, dep.airport_code AS dep_code, arr.airport_code AS arr_code,
            (SELECT COUNT(*) FROM ticket t WHERE t.flight_id = f.flight_id
               AND t.ticket_status IN ('Confirmed','Used')) AS pax,
            (SELECT COUNT(*) FROM weather_compensation w WHERE w.flight_id = f.flight_id) AS issued
     FROM flight f
     JOIN airport dep ON f.departure_airport_id = dep.airport_id
     JOIN airport arr ON f.arrival_airport_id   = arr.airport_id
     WHERE f.delay_reason IS NOT NULL
     ORDER BY f.departure_time DESC");

$comp = mysqli_query($conn,
    "SELECT w.*, p.firstName, p.lastName
     FROM weather_compensation w
     JOIN passenger p ON w.passenger_id = p.passenger_id
     ORDER BY w.issue_date DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FlightFusion | Weather Guarantee</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<?php require_once('nav.php'); ?>
<main>
    <div class="wide_box">
        <h2 class="page_title">Weather Guarantee</h2>
        <p class="page_intro">Record a weather event on a flight. Every affected passenger is
           compensated automatically with a voucher and bonus miles.</p>

        <?php if (isset($_GET['msg'])) { ?>
            <div class="msg msg_ok"><?php echo e($_GET['msg']); ?></div>
        <?php } ?>
        <?php if (isset($_GET['error'])) { ?>
            <div class="msg msg_error">
            <?php
                if ($_GET['error'] == 'rule') echo "The delay is shorter than " . WEATHER_MIN_DELAY . " minutes, so the guarantee does not apply.";
                else if ($_GET['error'] == 'nopax') echo "That flight has no confirmed passengers.";
                else echo "The compensation run failed. Nothing was issued.";
            ?>
            </div>
        <?php } ?>

        <div class="weather_grid">
            <div class="weather_card">
                <div class="weather_icon">&#9748;</div>
                <h3><?php echo WEATHER_MIN_DELAY; ?> min</h3>
                <p>Delay that triggers the guarantee</p>
            </div>
            <div class="weather_card">
                <div class="weather_icon">&#127783;</div>
                <h3>BDT <?php echo WEATHER_DELAY_VOUCHER; ?></h3>
                <p>Delay voucher, plus <?php echo WEATHER_DELAY_POINTS; ?> bonus miles</p>
            </div>
            <div class="weather_card">
                <div class="weather_icon">&#127786;</div>
                <h3>BDT <?php echo WEATHER_CANCEL_VOUCHER; ?></h3>
                <p>Cancellation voucher, plus <?php echo WEATHER_CANCEL_POINTS; ?> bonus miles</p>
            </div>
        </div>

        <div class="card card_blue">
            <div class="card_head">
                <h3>Record a weather event</h3>
                <span class="card_note">Manual input point for the weather feed</span>
            </div>
            <ul class="rule_list">
                <li>A delay of <?php echo WEATHER_MIN_DELAY; ?> minutes or more, or a weather
                    cancellation, pays every affected passenger automatically.</li>
                <li>No passenger can be paid twice for the same flight.</li>
                <li>A real weather API can later write the same two columns
                    (delay_minutes, delay_reason) and call the same file.</li>
            </ul>

            <form action="issue_compensation.php" method="post">
                <div class="form_grid">
                    <div class="field">
                        <label for="flight_id">Flight:</label>
                        <select name="flight_id" id="flight_id">
                            <?php while ($f = mysqli_fetch_assoc($flights)) { ?>
                                <option value="<?php echo e($f['flight_id']); ?>">
                                    <?php echo e($f['flight_id'] . ' - ' . $f['flight_status']); ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="field">
                        <label for="delay_minutes">Delay in minutes:</label>
                        <input type="number" name="delay_minutes" id="delay_minutes" min="0" value="0" required>
                    </div>
                </div>

                <div class="form_grid">
                    <div class="field">
                        <label for="delay_reason">Reason:</label>
                        <input type="text" name="delay_reason" id="delay_reason" value="Severe Weather" required>
                    </div>
                    <div class="field">
                        <label for="new_status">Set flight status to:</label>
                        <select name="new_status" id="new_status">
                            <option value="Delayed">Delayed</option>
                            <option value="Cancelled">Cancelled</option>
                            <option value="Scheduled">Scheduled</option>
                        </select>
                    </div>
                </div>

                <button type="submit">Save event &amp; issue compensation</button>
            </form>
        </div>

        <div class="card card_orange">
            <div class="card_head">
                <h3>Flights with a recorded weather event</h3>
                <span class="card_note">Passengers and compensations per flight</span>
            </div>
            <div class="table_wrap">
            <table>
                <tr><th>Flight</th><th>Route</th><th>Departure</th><th>Status</th><th>Delay (min)</th>
                    <th>Reason</th><th>Passengers</th><th>Compensations Issued</th></tr>
                <?php while ($a = mysqli_fetch_assoc($affected)) { ?>
                <tr>
                    <td><?php echo e($a['flight_id']); ?></td>
                    <td><?php echo e($a['dep_code'] . ' - ' . $a['arr_code']); ?></td>
                    <td><?php echo e($a['departure_time']); ?></td>
                    <td><span class="status status_<?php echo strtolower($a['flight_status']); ?>"><?php echo e($a['flight_status']); ?></span></td>
                    <td><?php echo e($a['delay_minutes']); ?></td>
                    <td><?php echo e($a['delay_reason']); ?></td>
                    <td><?php echo e($a['pax']); ?></td>
                    <td><?php echo e($a['issued']); ?></td>
                </tr>
                <?php } ?>
            </table>
            </div>
        </div>

        <div class="card card_green">
            <div class="card_head">
                <h3>Issued Compensation</h3>
                <span class="card_note">Newest first</span>
            </div>
            <div class="table_wrap">
            <table>
                <tr><th>Compensation ID</th><th>Passenger</th><th>Flight</th><th>Type</th>
                    <th>Voucher (BDT)</th><th>Bonus Points</th><th>Issued</th></tr>
                <?php while ($c = mysqli_fetch_assoc($comp)) { ?>
                <tr>
                    <td><?php echo e($c['compensation_id']); ?></td>
                    <td><?php echo e($c['firstName'] . ' ' . $c['lastName'] . ' (' . $c['passenger_id'] . ')'); ?></td>
                    <td><?php echo e($c['flight_id']); ?></td>
                    <td><?php echo e($c['compensation_type']); ?></td>
                    <td><?php echo e($c['voucher_amount']); ?></td>
                    <td><?php echo e($c['bonus_points']); ?></td>
                    <td><?php echo e($c['issue_date']); ?></td>
                </tr>
                <?php } ?>
            </table>
            </div>
        </div>
    </div>
</main>
</body>
</html>
