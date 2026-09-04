<?php
require_once('DBconnect.php');
ff_require_passenger();

if (!isset($_POST['flight_id']) || !isset($_POST['pax'])) {
    header("Location: search_flights.php");
    exit();
}

$flight_id      = $_POST['flight_id'];
$pax            = (int)$_POST['pax'];
$payment_method = $_POST['payment_method'];
$primary_id     = $_SESSION['passenger_id'];

$seats = array();
for ($i = 0; $i < $pax; $i++) {
    if (!isset($_POST['p_seat_' . $i])) {
        header("Location: book_flight.php?flight_id=" . $flight_id . "&pax=" . $pax . "&error=seat");
        exit();
    }
    $seats[$i] = $_POST['p_seat_' . $i];
}
if (count(array_unique($seats)) != count($seats)) {
    header("Location: book_flight.php?flight_id=" . $flight_id . "&pax=" . $pax . "&error=dupseat");
    exit();
}
$stmt = $conn->prepare("SELECT * FROM flight WHERE flight_id = ?");
$stmt->bind_param("s", $flight_id);
$stmt->execute();
$flight = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$flight || $flight['flight_status'] == 'Cancelled') {
    header("Location: search_flights.php?error=cancelled");
    exit();
}
if (ff_available_seats($conn, $flight_id, (int)$flight['total_seats']) < $pax) {
    header("Location: book_flight.php?flight_id=" . $flight_id . "&error=full");
    exit();
}

$taken = ff_taken_seats($conn, $flight_id);
foreach ($seats as $s) {
    if (in_array($s, $taken)) {
        header("Location: book_flight.php?flight_id=" . $flight_id . "&pax=" . $pax . "&error=seat");
        exit();
    }
}
$nids = array();
for ($i = 1; $i < $pax; $i++) {
    $nids[] = $_POST['p_nid_' . $i];
}
if (count($nids) != count(array_unique($nids))) {
    header("Location: book_flight.php?flight_id=" . $flight_id . "&pax=" . $pax . "&error=nid");
    exit();
}

$conn->begin_transaction();
try {
    $booking_id   = ff_next_id($conn, 'booking', 'booking_id', 'BK', 2);
    $booking_date = date('Y-m-d H:i:s');

    $stmt = $conn->prepare("INSERT INTO booking
        (booking_id, passenger_id, booking_date, total_amount, booking_status)
        VALUES (?, ?, ?, 0, 'Pending')");
    $stmt->bind_param("sss", $booking_id, $primary_id, $booking_date);
    $stmt->execute();
    $stmt->close();

    $total_amount    = 0;
    $ticket_owners   = array();   /* passenger_id => amount paid, for reward points */

    for ($i = 0; $i < $pax; $i++) {

        /* --- who is this passenger? --- */
        if ($i == 0) {
            $pid = $primary_id;
        } else {
            $nid = $_POST['p_nid_' . $i];

            $stmt = $conn->prepare("SELECT passenger_id FROM passenger WHERE NID = ?");
            $stmt->bind_param("s", $nid);
            $stmt->execute();
            $found = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ($found) {
                $pid = $found['passenger_id'];
            } else {
                $pid = ff_next_id($conn, 'passenger', 'passenger_id', 'PSG', 2);

                $first  = $_POST['p_first_' . $i];
                $last   = $_POST['p_last_' . $i];
                $dob    = $_POST['p_dob_' . $i];
                $gender = $_POST['p_gender_' . $i];
                $pass   = $_POST['p_passport_' . $i];
                $phone  = $_POST['p_phone_' . $i];
                $email  = $_POST['p_email_' . $i];
                $nation = 'Bangladeshi';

                $stmt = $conn->prepare("INSERT INTO passenger
                    (passenger_id, firstName, lastName, DOB, gender, passport_no, nationality, phoneNumber, email, NID)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssssssssss", $pid, $first, $last, $dob, $gender,
                                                $pass, $nation, $phone, $email, $nid);
                $stmt->execute();
                $stmt->close();

                $stmt = $conn->prepare("INSERT INTO regular_passenger (passenger_id) VALUES (?)");
                $stmt->bind_param("s", $pid);
                $stmt->execute();
                $stmt->close();
            }
        }

        $stmt = $conn->prepare("SELECT COUNT(*) AS c FROM ticket
                                WHERE flight_id = ? AND passenger_id = ? AND ticket_status = 'Confirmed'");
        $stmt->bind_param("ss", $flight_id, $pid);
        $stmt->execute();
        $already = $stmt->get_result()->fetch_assoc()['c'];
        $stmt->close();
        if ($already > 0) {
            throw new Exception("Passenger " . $pid . " already has a seat on this flight");
        }

        /* --- fare, class and the automatic discount --- */
        $seat_no = $seats[$i];
        $class   = ff_seat_class($seat_no);
        $fare    = ($class == 'Business')
                   ? $flight['base_fare'] * BUSINESS_MULTIPLIER
                   : $flight['base_fare'];

        $percent      = ff_discount_percent($conn, $pid);   /* 0 when not verified */
        $discount     = $fare * $percent / 100;
        $final_fare   = $fare - $discount;
        $total_amount = $total_amount + $final_fare;

        $ticket_id = ff_next_id($conn, 'ticket', 'ticket_id', 'TK', 2);
        $stmt = $conn->prepare("INSERT INTO ticket
            (ticket_id, booking_id, passenger_id, flight_id, seat_no, class, ticket_price, ticket_status)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'Confirmed')");
        $stmt->bind_param("ssssssd", $ticket_id, $booking_id, $pid, $flight_id,
                                     $seat_no, $class, $final_fare);
        $stmt->execute();
        $stmt->close();

        if (isset($ticket_owners[$pid])) {
            $ticket_owners[$pid] = $ticket_owners[$pid] + $final_fare;
        } else {
            $ticket_owners[$pid] = $final_fare;
        }
    }

    $stmt = $conn->prepare("UPDATE booking SET total_amount = ?, booking_status = 'Confirmed'
                            WHERE booking_id = ?");
    $stmt->bind_param("ds", $total_amount, $booking_id);
    $stmt->execute();
    $stmt->close();

    $payment_id = ff_next_id($conn, 'payment', 'payment_id', 'PM', 2);
    $stmt = $conn->prepare("INSERT INTO payment
        (payment_id, booking_id, amount, payment_method, payment_date, payment_status)
        VALUES (?, ?, ?, ?, NOW(), 'Completed')");
    $stmt->bind_param("ssds", $payment_id, $booking_id, $total_amount, $payment_method);
    $stmt->execute();
    $stmt->close();

    foreach ($ticket_owners as $pid => $paid) {
        $points = (int)floor($paid * POINTS_RATE);
        if ($points > 0) {
            ff_add_points($conn, $pid, $points, 'Earned', $payment_id,
                          'Points earned on booking ' . $booking_id);
        }
    }

    
    $stmt = $conn->prepare("UPDATE waitlist SET status = 'Booked'
                            WHERE flight_id = ? AND passenger_id = ? AND status IN ('Waiting','Promoted')");
    $stmt->bind_param("ss", $flight_id, $primary_id);
    $stmt->execute();
    $stmt->close();

    $conn->commit();
    header("Location: view_booking.php?id=" . $booking_id . "&msg=Booking confirmed");
    exit();

} catch (Exception $ex) {
    $conn->rollback();
    header("Location: book_flight.php?flight_id=" . $flight_id . "&pax=" . $pax . "&error=db");
    exit();
}
?>
