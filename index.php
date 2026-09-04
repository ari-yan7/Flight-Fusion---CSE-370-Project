<?php require_once('DBconnect.php'); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
	 <link rel="stylesheet" href="css/style.css" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Fredoka:wght@300..700&display=swap"
      rel="stylesheet"
    />
    <title>FlightFusion | Login</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<?php require_once('nav.php'); ?>
<main>
    <div class="login_box">
        <h2 class="page_title">Login</h2>

        <?php if (isset($_GET['error'])) { ?>
            <div class="msg msg_error">
            <?php
                if ($_GET['error'] == 'wrong')      echo "Wrong username or password";
                else if ($_GET['error'] == 'empty') echo "Please fill in both fields";
                else if ($_GET['error'] == 'login') echo "Please log in first";
                else                                echo "Login failed";
            ?>
            </div>
        <?php } ?>

        <?php if (isset($_GET['registered'])) { ?>
            <div class="msg msg_ok">Account created. You can log in now.</div>
        <?php } ?>

        <form action="login.php" method="post">
            <label for="username">Username:</label>
            <input type="text" name="username" id="username" required>

            <label for="password">Password:</label>
            <input type="password" name="password" id="password" required>

            <button type="submit">Login</button>
        </form>

        <p style="color:white; margin-top:15px;">
            No account? <a href="register.php" style="color:#ffd479;">Register as a passenger</a>
        </p>
    </div>
</main>
</body>
</html>
