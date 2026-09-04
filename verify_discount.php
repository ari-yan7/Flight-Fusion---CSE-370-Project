<?php
require_once('DBconnect.php');
ff_require_admin();

if (!isset($_GET['id']) || !isset($_GET['action'])) {
    header("Location: admin_discounts.php");
    exit();
}

$discount_id = $_GET['id'];
$action      = $_GET['action'];
$admin_id    = $_SESSION['admin_id'];

$stmt = $conn->prepare("SELECT d.*, p.DOB, TIMESTAMPDIFF(YEAR, p.DOB, CURDATE()) AS age,
                               sp.student_id, sp.institution
                        FROM discount d
                        JOIN passenger p ON d.passenger_id = p.passenger_id
                        LEFT JOIN student_passenger sp ON sp.passenger_id = p.passenger_id
                        WHERE d.discount_id = ?");
$stmt->bind_param("s", $discount_id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$row) {
    header("Location: admin_discounts.php?error=notfound");
    exit();
}

if ($action == 'reject') {
    $stmt = $conn->prepare("UPDATE discount SET eligibility = 'Rejected' WHERE discount_id = ?");
    $stmt->bind_param("s", $discount_id);
    $stmt->execute();
    $stmt->close();
    header("Location: admin_discounts.php?msg=" . $discount_id . " rejected");
    exit();
}

if ($action != 'verify') {
    header("Location: admin_discounts.php");
    exit();
}

if ($row['discount_type'] == 'Senior Citizen Discount' && $row['age'] < SENIOR_MIN_AGE) {
    header("Location: admin_discounts.php?error=age");
    exit();
}
if ($row['discount_type'] == 'Student Discount') {
    if ($row['age'] > STUDENT_MAX_AGE) {
        header("Location: admin_discounts.php?error=age");
        exit();
    }
    if ($row['student_id'] == NULL || $row['institution'] == NULL) {
        header("Location: admin_discounts.php?error=proof");
        exit();
    }
}

$conn->begin_transaction();
try {
    /* the percentage is refreshed from DBconnect.php, so old rows follow new rules */
    $percent = ($row['discount_type'] == 'Student Discount')
               ? STUDENT_DISCOUNT_PERCENT : SENIOR_DISCOUNT_PERCENT;

    $stmt = $conn->prepare("UPDATE discount SET eligibility = 'Verified', percentage = ?
                            WHERE discount_id = ?");
    $stmt->bind_param("ds", $percent, $discount_id);
    $stmt->execute();
    $stmt->close();

    $stmt = $conn->prepare("INSERT IGNORE INTO manages_discount (admin_id, discount_id) VALUES (?, ?)");
    $stmt->bind_param("ss", $admin_id, $discount_id);
    $stmt->execute();
    $stmt->close();

    $conn->commit();
    header("Location: admin_discounts.php?msg=" . $discount_id . " verified at " . $percent . "%");
    exit();
} catch (Exception $ex) {
    $conn->rollback();
    header("Location: admin_discounts.php?error=db");
    exit();
}
?>
