<?php
require_once('DBconnect.php');
ff_require_admin();

$aircrafts = mysqli_query($conn, "SELECT ac.aircraft_id, ac.model, ac.capacity, al.airline_name
                                  FROM aircraft ac JOIN airline al ON ac.airline_id = al.airline_id");
$airports  = mysqli_query($conn, "SELECT * FROM airport ORDER BY airport_id");
$airports2 = mysqli_query($conn, "SELECT * FROM airport ORDER BY airport_id");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FlightFusion | Add Flight</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<?php require_once('nav.php'); ?>
<main>
    <div class="add_flight_box">
        <h2 class="page_title">Add New Flight</h2>

        <?php if (isset($_GET['error'])) { ?>
            <div class="msg msg_error">
            <?php
                if ($_GET['error'] == 'same')  echo "Origin and destination cannot be the same";
                else if ($_GET['error'] == 'time') echo "Arrival time must be after departure time";
                else echo "Could not add the flight. Please check the values.";
            ?>
            </div>
        <?php } ?>

        <form action="insert_flight.php" method="post">
            <label for="new_aircraft_id">Aircraft:</label>
            <select name="new_aircraft_id" id="new_aircraft_id" required>
                <?php while ($ac = mysqli_fetch_assoc($aircrafts)) { ?>
                    <option value="<?php echo e($ac['aircraft_id']); ?>">
                        <?php echo e($ac['aircraft_id'] . ' - ' . $ac['airline_name'] . ' - ' . $ac['model'] . ' (' . $ac['capacity'] . ' seats)'); ?>
                    </option>
                <?php } ?>
            </select>

            <label for="new_departure_airport">Origin Airport:</label>
            <select name="new_departure_airport" id="new_departure_airport" required>
                <?php while ($ap = mysqli_fetch_assoc($airports)) { ?>
                    <option value="<?php echo e($ap['airport_id']); ?>"><?php echo e($ap['airport_code'] . ' - ' . $ap['city']); ?></option>
                <?php } ?>
            </select>

            <label for="new_arrival_airport">Destination Airport:</label>
            <select name="new_arrival_airport" id="new_arrival_airport" required>
                <?php while ($ap = mysqli_fetch_assoc($airports2)) { ?>
                    <option value="<?php echo e($ap['airport_id']); ?>"><?php echo e($ap['airport_code'] . ' - ' . $ap['city']); ?></option>
                <?php } ?>
            </select>

            <label for="new_departure_time">Departure Time:</label>
            <input type="datetime-local" name="new_departure_time" id="new_departure_time" required>

            <label for="new_arrival_time">Arrival Time:</label>
            <input type="datetime-local" name="new_arrival_time" id="new_arrival_time" required>

            <label for="new_flight_status">Flight Status:</label>
            <select name="new_flight_status" id="new_flight_status">
                <option value="Scheduled">Scheduled</option>
                <option value="Boarding">Boarding</option>
                <option value="Delayed">Delayed</option>
                <option value="Departed">Departed</option>
                <option value="Arrived">Arrived</option>
                <option value="Cancelled">Cancelled</option>
                <option value="Completed">Completed</option>
            </select>

            <label for="new_total_seats">Total Seats:</label>
            <input type="number" name="new_total_seats" id="new_total_seats" min="1" required>

            <label for="new_base_fare">Base Fare (BDT):</label>
            <input type="number" name="new_base_fare" id="new_base_fare" min="0" step="0.01" required>

            <button type="submit">Add Flight</button>
        </form>
    </div>
</main>
</body>
</html>
