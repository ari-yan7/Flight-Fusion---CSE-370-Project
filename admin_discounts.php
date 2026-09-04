<?php
require_once('DBconnect.php');
ff_require_admin();

$sql = "SELECT d.*, p.firstName, p.lastName, p.DOB, p.NID,
               sp.student_id, sp.institution,
               TIMESTAMPDIFF(YEAR, p.DOB, CURDATE()) AS age
        FROM discount d
        JOIN passenger p ON d.passenger_id = p.passenger_id
        LEFT JOIN student_passenger sp ON sp.passenger_id = p.passenger_id
        ORDER BY FIELD(d.eligibility,'Pending','Verified','Rejected'), d.discount_id";
$result = mysqli_query($conn, $sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FlightFusion | Discount Verification</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<?php require_once('nav.php'); ?>
<main>
    <div class="wide_box">
        <h2 class="page_title">Discount Verification</h2>
        <p class="page_intro">Check each application against the passenger's age and proof,
           then verify or reject it. Only verified discounts are used at booking time.</p>

        <?php if (isset($_GET['msg'])) { ?>
            <div class="msg msg_ok"><?php echo e($_GET['msg']); ?></div>
        <?php } ?>
        <?php if (isset($_GET['error'])) { ?>
            <div class="msg msg_error">
            <?php
                if ($_GET['error'] == 'age') echo "That passenger does not meet the age rule for the discount.";
                else if ($_GET['error'] == 'proof') echo "The student record is incomplete.";
                else echo "The verification failed.";
            ?>
            </div>
        <?php } ?>

        <div class="discount_grid">
            <div class="discount_card">
                <div class="percent"><?php echo STUDENT_DISCOUNT_PERCENT; ?>%</div>
                <h4>Student Discount</h4>
                <p>Age up to <?php echo STUDENT_MAX_AGE; ?>, student ID and institution required</p>
            </div>
            <div class="discount_card discount_card_senior">
                <div class="percent"><?php echo SENIOR_DISCOUNT_PERCENT; ?>%</div>
                <h4>Senior Citizen Discount</h4>
                <p>Age <?php echo SENIOR_MIN_AGE; ?> and above</p>
            </div>
        </div>

        <div class="card card_blue">
            <div class="card_head">
                <h3>Applications</h3>
                <span class="card_note">Pending applications are listed first</span>
            </div>
            <div class="table_wrap">
            <table>
            <tr>
                <th>Discount ID</th><th>Passenger</th><th>Age</th><th>NID</th><th>Type</th>
                <th>Student ID</th><th>Institution</th><th>Percentage</th><th>Status</th><th>Action</th>
            </tr>
            <?php while ($row = mysqli_fetch_assoc($result)) { ?>
            <tr>
                <td><?php echo e($row['discount_id']); ?></td>
                <td><?php echo e($row['firstName'] . ' ' . $row['lastName'] . ' (' . $row['passenger_id'] . ')'); ?></td>
                <td><?php echo e($row['age']); ?></td>
                <td><?php echo e($row['NID']); ?></td>
                <td><?php echo e($row['discount_type']); ?></td>
                <td><?php echo e($row['student_id']); ?></td>
                <td><?php echo e($row['institution']); ?></td>
                <td><?php echo e($row['percentage']); ?>%</td>
                <td><span class="status status_<?php echo strtolower($row['eligibility']); ?>"><?php echo e($row['eligibility']); ?></span></td>
                <td>
                    <?php if ($row['eligibility'] != 'Verified') { ?>
                        <a class="btn btn_small btn_success" href="verify_discount.php?id=<?php echo e($row['discount_id']); ?>&action=verify">Verify</a>
                    <?php } ?>
                    <?php if ($row['eligibility'] != 'Rejected') { ?>
                        <a class="btn btn_small btn_danger" href="verify_discount.php?id=<?php echo e($row['discount_id']); ?>&action=reject">Reject</a>
                    <?php } ?>
                </td>
            </tr>
            <?php } ?>
            </table>
            </div>
        </div>
    </div>
</main>
</body>
</html>
