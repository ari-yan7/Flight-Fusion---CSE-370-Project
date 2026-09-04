<?php
require_once('DBconnect.php');
ff_require_passenger();

$passenger_id = $_SESSION['passenger_id'];

$stmt = $conn->prepare("SELECT * FROM passenger WHERE passenger_id = ?");
$stmt->bind_param("s", $passenger_id);
$stmt->execute();
$me = $stmt->get_result()->fetch_assoc();
$stmt->close();

$age = date_diff(date_create($me['DOB']), date_create('today'))->y;

$stmt = $conn->prepare("SELECT * FROM discount WHERE passenger_id = ?");
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
    <title>FlightFusion | Discounts</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<?php require_once('nav.php'); ?>
<main>
    <div class="wide_box">
        <h2 class="page_title">Student &amp; Senior Citizen Discounts</h2>
        <p class="page_intro">Apply once, get verified once, and the discount is then taken off
           every new ticket you book automatically.</p>

        <?php if (isset($_GET['msg'])) { ?>
            <div class="msg msg_ok"><?php echo e($_GET['msg']); ?></div>
        <?php } ?>
        <?php if (isset($_GET['error'])) { ?>
            <div class="msg msg_error">
            <?php
                if ($_GET['error'] == 'dup')     echo "You already applied for that discount.";
                else if ($_GET['error'] == 'age') echo "Your date of birth does not meet the age rule for that discount.";
                else if ($_GET['error'] == 'type') echo "Unknown discount type.";
                else if ($_GET['error'] == 'proof') echo "Please fill in your student ID and institution.";
                else echo "The application failed.";
            ?>
            </div>
        <?php } ?>

        <div class="discount_grid">
            <div class="discount_card">
                <div class="percent"><?php echo STUDENT_DISCOUNT_PERCENT; ?>%</div>
                <h4>Student Discount</h4>
                <p>For students up to age <?php echo STUDENT_MAX_AGE; ?>. Student ID and
                   institution are needed as proof.</p>
            </div>
            <div class="discount_card discount_card_senior">
                <div class="percent"><?php echo SENIOR_DISCOUNT_PERCENT; ?>%</div>
                <h4>Senior Citizen Discount</h4>
                <p>For passengers aged <?php echo SENIOR_MIN_AGE; ?> and above, checked against
                   your date of birth.</p>
            </div>
            <div class="discount_card discount_card_age">
                <div class="percent"><?php echo $age; ?></div>
                <h4>Your Age</h4>
                <p>Taken from the date of birth on your passenger record.</p>
            </div>
        </div>

        <div class="card card_blue">
            <h3>How it works</h3>
            <ol class="step_list">
                <li>Apply for the discount you qualify for using the form below.</li>
                <li>An administrator checks your proof and sets the status.</li>
                <li>Once the status is <strong>Verified</strong>, the discount is taken off
                    every new ticket you book automatically.</li>
            </ol>
        </div>

        <div class="card card_green">
            <div class="card_head">
                <h3>My Discount Applications</h3>
                <span class="card_note">Only Verified applications are used at booking</span>
            </div>
            <div class="table_wrap">
            <table>
                <tr><th>Discount ID</th><th>Type</th><th>Percentage</th><th>Status</th></tr>
                <?php if (mysqli_num_rows($rows) > 0) {
                    while ($r = mysqli_fetch_assoc($rows)) { ?>
                <tr>
                    <td><?php echo e($r['discount_id']); ?></td>
                    <td><?php echo e($r['discount_type']); ?></td>
                    <td><?php echo e($r['percentage']); ?>%</td>
                    <td><span class="status status_<?php echo strtolower($r['eligibility']); ?>"><?php echo e($r['eligibility']); ?></span></td>
                </tr>
                <?php } } else { ?>
                <tr><td colspan="4" class="empty_row">You have not applied for any discount.</td></tr>
                <?php } ?>
            </table>
            </div>
        </div>

        <div class="card card_orange">
            <div class="card_head">
                <h3>Apply for a discount</h3>
                <span class="card_note">Leave the student fields empty for a senior application</span>
            </div>
            <form action="insert_discount.php" method="post">
                <label for="discount_type">Discount Type:</label>
                <select name="discount_type" id="discount_type">
                    <option value="Student Discount">Student Discount (<?php echo STUDENT_DISCOUNT_PERCENT; ?>%)</option>
                    <option value="Senior Citizen Discount">Senior Citizen Discount (<?php echo SENIOR_DISCOUNT_PERCENT; ?>%)</option>
                </select>

                <div class="form_grid">
                    <div class="field">
                        <label for="student_id">Student ID (students only):</label>
                        <input type="text" name="student_id" id="student_id" placeholder="e.g. STU2201">
                    </div>
                    <div class="field">
                        <label for="institution">Institution (students only):</label>
                        <input type="text" name="institution" id="institution" placeholder="e.g. BRAC University">
                    </div>
                </div>

                <button type="submit">Submit for Verification</button>
            </form>
        </div>
    </div>
</main>
</body>
</html>
