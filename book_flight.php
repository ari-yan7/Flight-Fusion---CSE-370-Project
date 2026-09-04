<?php
require_once('DBconnect.php');
ff_require_passenger();

if (!isset($_GET['flight_id'])) {
    header("Location: search_flights.php");
    exit();
}

$flight_id    = $_GET['flight_id'];
$passenger_id = $_SESSION['passenger_id'];
$pax          = isset($_GET['pax']) ? (int)$_GET['pax'] : 1;
if ($pax < 1) { $pax = 1; }
if ($pax > 6) { $pax = 6; }

$stmt = $conn->prepare("SELECT f.*, dep.airport_code AS dep_code, dep.city AS dep_city,
                               arr.airport_code AS arr_code, arr.city AS arr_city,
                               ac.model, al.airline_name
                        FROM flight f
                        JOIN airport  dep ON f.departure_airport_id = dep.airport_id
                        JOIN airport  arr ON f.arrival_airport_id   = arr.airport_id
                        JOIN aircraft ac  ON f.aircraft_id          = ac.aircraft_id
                        JOIN airline  al  ON ac.airline_id          = al.airline_id
                        WHERE f.flight_id = ?");
$stmt->bind_param("s", $flight_id);
$stmt->execute();
$flight = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$flight) {
    header("Location: search_flights.php?error=nf");
    exit();
}
if ($flight['flight_status'] == 'Cancelled') {
    header("Location: search_flights.php?error=cancelled");
    exit();
}

$taken      = ff_taken_seats($conn, $flight_id);
$all_seats  = ff_seat_list((int)$flight['total_seats']);
$free_seats = array_values(array_diff($all_seats, $taken));

$available = ff_available_seats($conn, $flight_id, (int)$flight['total_seats']);
if (count($free_seats) < $available) { $available = count($free_seats); }

if ($available <= 0) {
    header("Location: join_waitlist.php?flight_id=" . $flight_id);
    exit();
}
if ($pax > $available) { $pax = $available; }

/* my own details, used to pre-fill the first passenger block */
$stmt = $conn->prepare("SELECT * FROM passenger WHERE passenger_id = ?");
$stmt->bind_param("s", $passenger_id);
$stmt->execute();
$me = $stmt->get_result()->fetch_assoc();
$stmt->close();

$my_discount = ff_discount_percent($conn, $passenger_id);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FlightFusion | Book Flight</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<?php require_once('nav.php'); ?>
<main>
    <div class="wide_box">
        <h2 class="page_title">Book Flight <?php echo e($flight['flight_id']); ?></h2>

        <?php if (isset($_GET['error'])) { ?>
            <div class="msg msg_error">
            <?php
                if ($_GET['error'] == 'seat')      echo "One of the seats you picked was just taken. Please choose again.";
                else if ($_GET['error'] == 'dupseat') echo "You picked the same seat for two passengers.";
                else if ($_GET['error'] == 'full')  echo "The flight became full while you were booking.";
                else if ($_GET['error'] == 'nid')   echo "Please give a different NID for every passenger.";
                else echo "The booking could not be completed. Nothing was saved.";
            ?>
            </div>
        <?php } ?>

        <div class="card">
            <h3><?php echo e($flight['airline_name'] . ' - ' . $flight['model']); ?></h3>
            <table>
                <tr><th>Route</th><td><?php echo e($flight['dep_city'] . ' (' . $flight['dep_code'] . ')  ->  ' . $flight['arr_city'] . ' (' . $flight['arr_code'] . ')'); ?></td></tr>
                <tr><th>Departure</th><td><?php echo e($flight['departure_time']); ?></td></tr>
                <tr><th>Arrival</th><td><?php echo e($flight['arrival_time']); ?></td></tr>
                <tr><th>Status</th><td><?php echo e($flight['flight_status']); ?></td></tr>
                <tr><th>Available Seats</th><td><?php echo $available; ?> of <?php echo e($flight['total_seats']); ?></td></tr>
                <tr><th>Base Fare (Economy)</th><td>BDT <?php echo e($flight['base_fare']); ?></td></tr>
                <tr><th>Business Fare (rows 1-<?php echo BUSINESS_ROWS; ?>)</th><td>BDT <?php echo number_format($flight['base_fare'] * BUSINESS_MULTIPLIER, 2); ?></td></tr>
            </table>
        </div>

        <div class="card">
            <h3>How many passengers? (Family &amp; Friends booking)</h3>
            <form action="book_flight.php" method="get">
                <input type="hidden" name="flight_id" value="<?php echo e($flight_id); ?>">
                <label for="pax">Number of passengers in this one reservation:</label>
                <select name="pax" id="pax">
                    <?php for ($i = 1; $i <= 6 && $i <= $available; $i++) { ?>
                        <option value="<?php echo $i; ?>" <?php if ($i == $pax) echo 'selected'; ?>><?php echo $i; ?></option>
                    <?php } ?>
                </select>
                <button type="submit">Set</button>
            </form>
        </div>

        <form action="insert_booking.php" method="post">
            <input type="hidden" name="flight_id" value="<?php echo e($flight_id); ?>">
            <input type="hidden" name="pax" value="<?php echo $pax; ?>">

            <?php for ($i = 0; $i < $pax; $i++) { ?>
            <div class="card">
                <h3>Passenger <?php echo ($i + 1); ?><?php if ($i == 0) echo ' (Primary - you)'; ?></h3>

                <?php if ($i == 0) { ?>
                    <input type="hidden" name="p_existing_0" value="<?php echo e($passenger_id); ?>">
                    <table>
                        <tr><th>Name</th><td><?php echo e($me['firstName'] . ' ' . $me['lastName']); ?></td></tr>
                        <tr><th>NID</th><td><?php echo e($me['NID']); ?></td></tr>
                        <tr><th>Date of Birth</th><td><?php echo e($me['DOB']); ?></td></tr>
                        <tr><th>Verified Discount</th>
                            <td><?php echo $my_discount > 0 ? e($my_discount) . '%' : 'None'; ?></td></tr>
                    </table>
                <?php } else { ?>
                    <div class="passenger_block">
                        <label>First Name:</label>
                        <input type="text" name="p_first_<?php echo $i; ?>" required>

                        <label>Last Name:</label>
                        <input type="text" name="p_last_<?php echo $i; ?>" required>

                        <label>Date of Birth:</label>
                        <input type="date" name="p_dob_<?php echo $i; ?>" required>

                        <label>Gender:</label>
                        <select name="p_gender_<?php echo $i; ?>">
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                            <option value="Other">Other</option>
                        </select>

                        <label>NID (used to match an existing passenger record):</label>
                        <input type="text" name="p_nid_<?php echo $i; ?>" required>

                        <label>Passport No:</label>
                        <input type="text" name="p_passport_<?php echo $i; ?>">

                        <label>Phone Number:</label>
                        <input type="text" name="p_phone_<?php echo $i; ?>">

                        <label>Email:</label>
                        <input type="email" name="p_email_<?php echo $i; ?>">
                    </div>
                <?php } ?>

                <label>Seat:</label>
                <select name="p_seat_<?php echo $i; ?>" required>
                    <?php foreach ($free_seats as $seat) {
                        $class = ff_seat_class($seat);
                        $price = ($class == 'Business')
                                 ? $flight['base_fare'] * BUSINESS_MULTIPLIER
                                 : $flight['base_fare'];
                    ?>
                        <option value="<?php echo e($seat); ?>">
                            <?php echo e($seat . ' - ' . $class . ' - BDT ' . number_format($price, 2)); ?>
                        </option>
                    <?php } ?>
                </select>
            </div>
            <?php } ?>

            <div class="card">
                <h3>Payment</h3>
                <label for="payment_method">Payment Method:</label>
                <select name="payment_method" id="payment_method">
                    <option value="bKash">bKash</option>
                    <option value="Nagad">Nagad</option>
                    <option value="Credit Card">Credit Card</option>
                    <option value="Debit Card">Debit Card</option>
                    <option value="Mobile Banking">Mobile Banking</option>
                    <option value="Bank Transfer">Bank Transfer</option>
                </select>
                <p style="margin-top:12px; color:#444;">
                    Student and Senior Citizen discounts are applied automatically for every
                    passenger whose verification has already been approved. The full fare
                    breakdown is shown on the booking page after payment.
                </p>
                <button type="submit">Confirm Booking &amp; Pay</button>
            </div>
        </form>
    </div>
</main>
</body>
</html>
