<?php require_once('DBconnect.php'); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FlightFusion | Register</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<?php require_once('nav.php'); ?>
<main>
    <div class="add_flight_box">
        <h2 class="page_title">Create Passenger Account</h2>

        <?php if (isset($_GET['error'])) { ?>
            <div class="msg msg_error">
            <?php
                if($_GET['error'] == 'username') {
                    echo "That username is already taken";
                }
                else if ($_GET['error'] == 'nid') {
                     echo "That NID is already registered";
                }
                else{
                     echo "Registration failed. Please try again.";
                }
            ?>
            </div>
        <?php } ?>

        <form action="insert_passenger.php" method="post">
            <label for="new_first_name">First Name:</label>
            <input type="text" name="new_first_name" id="new_first_name" required>
            <label for="new_last_name">Last Name:</label>
            <input type="text" name="new_last_name" id="new_last_name" required>
            <label for="new_dob">Date of Birth:</label>
            <input type="date" name="new_dob" id="new_dob" required>
            <label for="new_gender">Gender:</label>
            <select name="new_gender" id="new_gender">
                <option value="Male">Male</option>
                <option value="Female">Female</option>
                <option value="Other">Other</option>
            </select>

            <label for="new_nid">NID:</label>
            <input type="text" name="new_nid" id="new_nid" required>
            <label for="new_passport">Passport No:</label>
            <input type="text" name="new_passport" id="new_passport">
            <label for="new_nationality">Nationality:</label>
            <input type="text" name="new_nationality" id="new_nationality" value="Bangladeshi" required>
        <label for="new_phone">Phone Number:</label>
            <input type="text" name="new_phone" id="new_phone" required>
            <label for="new_email">Email:</label>
            <input type="email" name="new_email" id="new_email" required>
            <label for="new_username">Username:</label>
            <input type="text" name="new_username" id="new_username" required>
            <label for="new_password">Password:</label>
            <input type="password" name="new_password" id="new_password" required>

            <button type="submit">Register</button>
        </form>
    </div>
</main>
</body>
</html>
