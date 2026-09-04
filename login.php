<?php
require_once('DBconnect.php');

if (isset($_POST['username']) && isset($_POST['password'])) {

    $username = $_POST['username'];
    $password = $_POST['password'];

    if ($username == "" || $password == "") {
        header("Location: index.php?error=empty");
        exit();
    }

    $stmt = $conn->prepare("SELECT*FROM users WHERE username=? AND password=?");
    $stmt->bind_param("ss", $username, $password);
    $stmt->execute();
    $result = $stmt->get_result();

    if (mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);

        $_SESSION['user_id']= $row['user_id'];
        $_SESSION['username']= $row['username'];
        $_SESSION['role']= $row['role'];
        $_SESSION['admin_id']= $row['admin_id'];
        $_SESSION['passenger_id']= $row['passenger_id'];

        if($row['role'] =='admin'){
            header("Location: admin_dashboard.php");
        } else {
            header("Location: home.php");
        }
        exit();
    } else {
        header("Location: index.php?error=wrong");
        exit();
    }
} else {
    header("Location: index.php");
    exit();
}
?>
