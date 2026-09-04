<?php
require_once('DBconnect.php');
ff_require_passenger();

if (!isset($_POST['discount_type'])) {
    header("Location: my_discounts.php");
    exit();
}

$discount_type = $_POST['discount_type'];
$student_id    = isset($_POST['student_id'])  ? $_POST['student_id']  : '';
$institution   = isset($_POST['institution']) ? $_POST['institution'] : '';
$passenger_id  = $_SESSION['passenger_id'];

$stmt = $conn->prepare("SELECT DOB FROM passenger WHERE passenger_id = ?");
$stmt->bind_param("s", $passenger_id);
$stmt->execute();
$me = $stmt->get_result()->fetch_assoc();
$stmt->close();

$age = date_diff(date_create($me['DOB']), date_create('today'))->y;

if ($discount_type == 'Student Discount') {
    if ($student_id == '' || $institution == '') {
        header("Location: my_discounts.php?error=proof");
        exit();
    }
    if ($age > STUDENT_MAX_AGE) {
        header("Location: my_discounts.php?error=age");
        exit();
    }
    $percentage = STUDENT_DISCOUNT_PERCENT;
} else if ($discount_type == 'Senior Citizen Discount') {
    if ($age < SENIOR_MIN_AGE) {
        header("Location: my_discounts.php?error=age");
        exit();
    }
    $percentage = SENIOR_DISCOUNT_PERCENT;
} else {
    header("Location: my_discounts.php?error=type");
    exit();
}

$stmt = $conn->prepare("SELECT discount_id FROM discount
                        WHERE passenger_id = ? AND discount_type = ?");
$stmt->bind_param("ss", $passenger_id, $discount_type);
$stmt->execute();
$dup = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($dup) {
    header("Location: my_discounts.php?error=dup");
    exit();
}

$conn->begin_transaction();
try {
    $discount_id = ff_next_id($conn, 'discount', 'discount_id', 'DC', 2);

    /* status is ALWAYS Pending here - only an admin can set Verified */
    $stmt = $conn->prepare("INSERT INTO discount
        (discount_id, passenger_id, discount_type, percentage, eligibility)
        VALUES (?, ?, ?, ?, 'Pending')");
    $stmt->bind_param("sssd", $discount_id, $passenger_id, $discount_type, $percentage);
    $stmt->execute();
    $stmt->close();

    if ($discount_type == 'Student Discount') {
        $stmt = $conn->prepare("INSERT IGNORE INTO student_passenger
            (passenger_id, student_id, institution) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $passenger_id, $student_id, $institution);
        $stmt->execute();
        $stmt->close();
    } else {
        $stmt = $conn->prepare("INSERT IGNORE INTO senior_citizen_passenger (passenger_id) VALUES (?)");
        $stmt->bind_param("s", $passenger_id);
        $stmt->execute();
        $stmt->close();
    }

    $conn->commit();
    header("Location: my_discounts.php?msg=Application " . $discount_id . " submitted. An administrator will verify it.");
    exit();

} catch (Exception $ex) {
    $conn->rollback();
    header("Location: my_discounts.php?error=db");
    exit();
}
?>
