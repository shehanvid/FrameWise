<link rel="stylesheet" href="emailotp.css">
<?php
session_start();
    if (isset($_POST["submit"])) {
        $name      = $_POST["name"];
        $email     = $_POST["email"];
        $username  = $_POST["uid"];
        $pwd       = $_POST["pwd"];
        $pwdRepeat = $_POST["pwdrepeat"];

        require_once 'dbh.inc.php';
        require_once 'functions.inc.php';

        $_SESSION["name"]      = $_POST["name"];
        $_SESSION["email"]     = $_POST["email"];
        $_SESSION["uid"]       = $_POST["uid"];
        $_SESSION["pwd"]       = $_POST["pwd"];
        $_SESSION["pwdrepeat"] = $_POST["pwdrepeat"];

        $emptyInput   = emptyInputSignup($name, $email, $username, $pwd, $pwdRepeat);
        $invalidUid   = invalidUid($username);
        $invalidEmail = invalidEmail($email);
        $pwdMatch     = pwdMathch($pwd, $pwdRepeat);
        $uidExists    = uidExists($conn, $username);
        $emailExists  = emailExists($conn, $email);

        if ($emptyInput !== false) {
            header("Location:../signup.php?error=emptyinput");
            exit();
        }
        if ($invalidUid !== false) {
            header("Location:../signup.php?error=invalidUid");
            exit();
        }
        if ($invalidEmail !== false) {
            header("Location:../signup.php?error=invalidEmail");
            exit();
        }
        if ($pwdMatch !== false) {
            header("Location:../signup.php?error=passwordnotmatch");
            exit();
        }
        if ($uidExists !== false) {
            header("Location:../signup.php?error=usernametaken");
            exit();
        }
        if ($emailExists !== false) {
            unset($_SESSION["otp_verified"], $_SESSION["verified_email"],
                  $_SESSION["otp_code"],    $_SESSION["otp_expiry"]);
            header("Location:../signup.php?error=emailtaken");
            exit();
        }



        $otpVerified    = $_SESSION['otp_verified']    ?? false;
        $verifiedEmail  = $_SESSION['verified_email']  ?? '';

        if (!$otpVerified || $verifiedEmail !== $email) {
            header("Location:../signup.php?error=emailnotverified");
            exit();
        }


        createUser($conn, $name, $email, $username, $pwd);
    }
    else {
        header('Location:../login.php');
        exit();
    }