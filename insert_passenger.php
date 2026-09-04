<?php
require_once('DBconnect.php');

if (isset($_POST['new_username']) && isset($_POST['new_password']) && isset($_POST['new_nid'])) {

    $first_name  = $_POST['new_first_name'];
    $last_name   = $_POST['new_last_name'];
    $dob         = $_POST['new_dob'];
    $gender      = $_POST['new_gender'];
    $nid         = $_POST['new_nid'];
    $passport    = isset($_POST['new_passport']) ? $_POST['new_passport'] : '';
    $nationality = $_POST['new_nationality'];
    $phone       = $_POST['new_phone'];
    $email       = $_POST['new_email'];
    $username    = $_POST['new_username'];
    $password    = $_POST['new_password'];

    $stmt = $conn->prepare("SELECT user_id FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    if (mysqli_num_rows($stmt->get_result()) > 0) {
        $stmt->close();
        header("Location: register.php?error=username");
        exit();
    }
    $stmt->close();

    /* NID must be free */
    $stmt = $conn->prepare("SELECT passenger_id FROM passenger WHERE NID = ?");
    $stmt->bind_param("s", $nid);
    $stmt->execute();
    if (mysqli_num_rows($stmt->get_result()) > 0) {
        $stmt->close();
        header("Location: register.php?error=nid");
        exit();
    }
    $stmt->close();

    $conn->begin_transaction();
    try {
        $passenger_id = ff_next_id($conn, 'passenger', 'passenger_id', 'PSG', 2);

        $stmt = $conn->prepare("INSERT INTO passenger
            (passenger_id, firstName, lastName, DOB, gender, passport_no, nationality, phoneNumber, email, NID)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssssss", $passenger_id, $first_name, $last_name, $dob, $gender,
                                        $passport, $nationality, $phone, $email, $nid);
        $stmt->execute();
        $stmt->close();

        $stmt = $conn->prepare("INSERT INTO regular_passenger (passenger_id) VALUES (?)");
        $stmt->bind_param("s", $passenger_id);
        $stmt->execute();
        $stmt->close();

        $stmt = $conn->prepare("INSERT INTO users (username, password, role, admin_id, passenger_id)
                                VALUES (?, ?, 'passenger', NULL, ?)");
        $stmt->bind_param("sss", $username, $password, $passenger_id);
        $stmt->execute();
        $stmt->close();

        ff_reward_account($conn, $passenger_id, NULL);

        $conn->commit();
        header("Location: index.php?registered=1");
        exit();
    } catch (Exception $ex) {
        $conn->rollback();
        header("Location: register.php?error=db");
        exit();
    }
} else {
    header("Location: register.php");
    exit();
}
?>
