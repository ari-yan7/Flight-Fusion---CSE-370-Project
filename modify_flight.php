<?php
require_once('DBconnect.php');
ff_require_admin();

if (!isset($_GET['id'])) {
    header("Location: admin_dashboard.php");
    exit();
}

$flight_id = $_GET['id'];

$stmt = $conn->prepare("SELECT * FROM flight WHERE flight_id = ?");
$stmt->bind_param("s", $flight_id);
$stmt->execute();
$result = $stmt->get_result();
$row = mysqli_fetch_assoc($result);
$stmt->close();

if (!$row) {
    header("Location: admin_dashboard.php?msg=Flight not found");
    exit();
}

$occupied  = ff_occupied_seats($conn, $flight_id);
$aircrafts = mysqli_query($conn, "SELECT ac.aircraft_id, ac.model, ac.capacity, al.airline_name
                                  FROM aircraft ac JOIN airline al ON ac.airline_id = al.airline_id");
$airports  = mysqli_query($conn, "SELECT * FROM airport ORDER BY airport_id");
$airports2 = mysqli_query($conn, "SELECT * FROM airport ORDER BY airport_id");
$statuses  = array('Scheduled','Boarding','Delayed','Departed','Arrived','Cancelled','Completed');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FlightFusion | Update Flight</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<?php require_once('nav.php'); ?>
<main>
    <div class="add_flight_box">
        <h2 class="page_title">Update Flight <?php echo e($row['flight_id']); ?></h2>

        <?php if (isset($_GET['error'])) { ?>
            <div class="msg msg_error">
            <?php
                if ($_GET['error'] == 'seats') echo "Total seats cannot be lower than the seats already sold";
                else if ($_GET['error'] == 'same') echo "Origin and destination cannot be the same";
                else if ($_GET['error'] == 'time') echo "Arrival time must be after departure time";
                else echo "Could not update the flight.";
            ?>
            </div>
        <?php } ?>

        <div class="msg msg_info">Seats already sold on this flight: <?php echo $occupied; ?></div>

        <form action="update_flight.php" method="post">
            <input type="hidden" name="flight_id" value="<?php echo e($row['flight_id']); ?>">

            <label for="new_aircraft_id">Aircraft:</label>
            <select name="new_aircraft_id" id="new_aircraft_id">
                <?php while ($ac = mysqli_fetch_assoc($aircrafts)) { ?>
                    <option value="<?php echo e($ac['aircraft_id']); ?>"
                        <?php if ($ac['aircraft_id'] == $row['aircraft_id']) echo 'selected'; ?>>
                        <?php echo e($ac['aircraft_id'] . ' - ' . $ac['airline_name'] . ' - ' . $ac['model']); ?>
                    </option>
                <?php } ?>
            </select>

            <label for="new_departure_airport">Origin Airport:</label>
            <select name="new_departure_airport" id="new_departure_airport">
                <?php while ($ap = mysqli_fetch_assoc($airports)) { ?>
                    <option value="<?php echo e($ap['airport_id']); ?>"
                        <?php if ($ap['airport_id'] == $row['departure_airport_id']) echo 'selected'; ?>>
                        <?php echo e($ap['airport_code'] . ' - ' . $ap['city']); ?>
                    </option>
                <?php } ?>
            </select>

            <label for="new_arrival_airport">Destination Airport:</label>
            <select name="new_arrival_airport" id="new_arrival_airport">
                <?php while ($ap = mysqli_fetch_assoc($airports2)) { ?>
                    <option value="<?php echo e($ap['airport_id']); ?>"
                        <?php if ($ap['airport_id'] == $row['arrival_airport_id']) echo 'selected'; ?>>
                        <?php echo e($ap['airport_code'] . ' - ' . $ap['city']); ?>
                    </option>
                <?php } ?>
            </select>

            <label for="new_departure_time">Departure Time:</label>
            <input type="datetime-local" name="new_departure_time" id="new_departure_time"
                   value="<?php echo e(str_replace(' ', 'T', substr($row['departure_time'], 0, 16))); ?>" required>

            <label for="new_arrival_time">Arrival Time:</label>
            <input type="datetime-local" name="new_arrival_time" id="new_arrival_time"
                   value="<?php echo e(str_replace(' ', 'T', substr($row['arrival_time'], 0, 16))); ?>" required>

            <label for="new_flight_status">Flight Status:</label>
            <select name="new_flight_status" id="new_flight_status">
                <?php foreach ($statuses as $st) { ?>
                    <option value="<?php echo $st; ?>" <?php if ($st == $row['flight_status']) echo 'selected'; ?>><?php echo $st; ?></option>
                <?php } ?>
            </select>

            <label for="new_total_seats">Total Seats:</label>
            <input type="number" name="new_total_seats" id="new_total_seats" min="1"
                   value="<?php echo e($row['total_seats']); ?>" required>

            <label for="new_base_fare">Base Fare (BDT):</label>
            <input type="number" name="new_base_fare" id="new_base_fare" min="0" step="0.01"
                   value="<?php echo e($row['base_fare']); ?>" required>

            <button type="submit">Update Flight</button>
        </form>
    </div>
</main>
</body>
</html>
