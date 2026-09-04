<?php
require_once('DBconnect.php');

$origin      = isset($_GET['origin'])      ? $_GET['origin']      : '';
$destination = isset($_GET['destination']) ? $_GET['destination'] : '';
$travel_date = isset($_GET['travel_date']) ? $_GET['travel_date'] : '';

$sql = "SELECT f.*, dep.airport_code AS dep_code, dep.city AS dep_city,
               arr.airport_code AS arr_code, arr.city AS arr_city,
               ac.model, al.airline_name
        FROM flight f
        JOIN airport  dep ON f.departure_airport_id = dep.airport_id
        JOIN airport  arr ON f.arrival_airport_id   = arr.airport_id
        JOIN aircraft ac  ON f.aircraft_id          = ac.aircraft_id
        JOIN airline  al  ON ac.airline_id          = al.airline_id
        WHERE 1 = 1";
$types  = "";
$params = array();

if ($origin != '')      { $sql .= " AND f.departure_airport_id = ?"; $types .= "s"; $params[] = $origin; }
if ($destination != '') { $sql .= " AND f.arrival_airport_id = ?";   $types .= "s"; $params[] = $destination; }
if ($travel_date != '') { $sql .= " AND DATE(f.departure_time) = ?"; $types .= "s"; $params[] = $travel_date; }

$sql .= " ORDER BY f.departure_time ASC";

$stmt = $conn->prepare($sql);
if ($types != "") { $stmt->bind_param($types, ...$params); }
$stmt->execute();
$result = $stmt->get_result();

$airports  = mysqli_query($conn, "SELECT * FROM airport ORDER BY airport_id");
$airports2 = mysqli_query($conn, "SELECT * FROM airport ORDER BY airport_id");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FlightFusion | Flights</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<?php require_once('nav.php'); ?>
<main>
    <div class="wide_box">
        <h2 class="page_title">Flight Schedule &amp; Ticket Availability</h2>

        <?php if (isset($_GET['msg'])) { ?>
            <div class="msg msg_ok"><?php echo e($_GET['msg']); ?></div>
        <?php } ?>
        <?php if (isset($_GET['error'])) { ?>
            <div class="msg msg_error">
            <?php
                if ($_GET['error'] == 'full')      echo "That flight is full. You can join the waitlist.";
                else if ($_GET['error'] == 'dup')  echo "You are already on the waitlist for that flight.";
                else if ($_GET['error'] == 'login') echo "Please log in as a passenger first.";
                else if ($_GET['error'] == 'cancelled') echo "That flight is cancelled.";
                else echo "Something went wrong.";
            ?>
            </div>
        <?php } ?>

        <div class="card">
            <h3>Search</h3>
            <form action="search_flights.php" method="get">
                <label for="origin">Origin:</label>
                <select name="origin" id="origin">
                    <option value="">Any</option>
                    <?php while ($ap = mysqli_fetch_assoc($airports)) { ?>
                        <option value="<?php echo e($ap['airport_id']); ?>" <?php if ($ap['airport_id'] == $origin) echo 'selected'; ?>>
                            <?php echo e($ap['airport_code'] . ' - ' . $ap['city']); ?>
                        </option>
                    <?php } ?>
                </select>

                <label for="destination">Destination:</label>
                <select name="destination" id="destination">
                    <option value="">Any</option>
                    <?php while ($ap = mysqli_fetch_assoc($airports2)) { ?>
                        <option value="<?php echo e($ap['airport_id']); ?>" <?php if ($ap['airport_id'] == $destination) echo 'selected'; ?>>
                            <?php echo e($ap['airport_code'] . ' - ' . $ap['city']); ?>
                        </option>
                    <?php } ?>
                </select>

                <label for="travel_date">Date:</label>
                <input type="date" name="travel_date" id="travel_date" value="<?php echo e($travel_date); ?>">

                <button type="submit">Search Flights</button>
            </form>
        </div>

        <table>
            <tr>
                <th>Flight</th>
                <th>Airline</th>
                <th>Origin</th>
                <th>Destination</th>
                <th>Departure</th>
                <th>Arrival</th>
                <th>Status</th>
                <th>Total</th>
                <th>Occupied</th>
                <th>Available</th>
                <th>Base Fare</th>
                <th>Action</th>
            </tr>
            <?php
            if (mysqli_num_rows($result) > 0) {
                while ($row = mysqli_fetch_assoc($result)) {
                    $occupied  = ff_occupied_seats($conn, $row['flight_id']);
                    $available = $row['total_seats'] - $occupied;
            ?>
            <tr>
                <td><?php echo e($row['flight_id']); ?></td>
                <td><?php echo e($row['airline_name']); ?></td>
                <td><?php echo e($row['dep_city'] . ' (' . $row['dep_code'] . ')'); ?></td>
                <td><?php echo e($row['arr_city'] . ' (' . $row['arr_code'] . ')'); ?></td>
                <td><?php echo e($row['departure_time']); ?></td>
                <td><?php echo e($row['arrival_time']); ?></td>
                <td><span class="status status_<?php echo strtolower($row['flight_status']); ?>"><?php echo e($row['flight_status']); ?></span></td>
                <td><?php echo e($row['total_seats']); ?></td>
                <td><?php echo $occupied; ?></td>
                <td><?php echo $available; ?></td>
                <td><?php echo e($row['base_fare']); ?></td>
                <td>
                <?php
                if (ff_is_passenger()) {
                    if ($row['flight_status'] == 'Cancelled') {
                        echo '<span class="status status_cancelled">Cancelled</span>';
                    } else if ($available > 0) { ?>
                        <a class="btn btn_small" href="book_flight.php?flight_id=<?php echo e($row['flight_id']); ?>">Book</a>
                    <?php } else { ?>
                        <a class="btn btn_small btn_success" href="join_waitlist.php?flight_id=<?php echo e($row['flight_id']); ?>">Join Waitlist</a>
                    <?php }
                } else if (ff_is_admin()) {
                    echo '<span class="status">Monitoring only</span>';
                } else { ?>
                    <a class="btn btn_small" href="index.php?error=login">Login to book</a>
                <?php } ?>
                </td>
            </tr>
            <?php
                }
            } else {
            ?>
            <tr><td colspan="12">No flights match your search.</td></tr>
            <?php } ?>
        </table>
    </div>
</main>
</body>
</html>
