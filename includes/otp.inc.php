<?php
session_start();
header('Content-Type: application/json');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require_once __DIR__ . '/../vendor/autoload.php';


define('GMAIL_ADDRESS', 'framewise.noreply@gmail.com');  
define('GMAIL_APP_PWD', 'xgzv ctps bgvc stxr');   


$action = $_POST['action'] ?? '';


if ($action === 'send') {

    $email = trim($_POST['email'] ?? '');

    if (!$email) {
        echo json_encode(['success' => false, 'message' => 'No email provided.']);
        exit();
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Invalid email address.']);
        exit();
    }


    $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    $_SESSION['otp_code']     = $otp;
    $_SESSION['otp_email']    = $email;
    $_SESSION['otp_expiry']   = time() + 600;
    $_SESSION['otp_verified'] = false;

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = GMAIL_ADDRESS;
        $mail->Password   = GMAIL_APP_PWD;
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;

        $mail->setFrom(GMAIL_ADDRESS, 'FrameWise');
        $mail->addAddress($email);
        $mail->Subject = 'FrameWise — Your Email Verification Code';
        $mail->Body    =
            "Hello,\n\n" .
            "Your FrameWise verification code is:\n\n" .
            "  $otp\n\n" .
            "This code expires in 10 minutes.\n" .
            "If you did not request this, please ignore this email.\n\n" .
            "— The FrameWise Team";

        $mail->send();
        echo json_encode(['success' => true, 'message' => 'OTP sent to ' . htmlspecialchars($email)]);

    } catch (Exception $e) {
        error_log("[FrameWise OTP] Mailer error: " . $mail->ErrorInfo . " | OTP: $otp");
        echo json_encode(['success' => false, 'message' => 'Failed to send email. Please try again.']);
    }
    exit();
}


if ($action === 'verify') {

    $enteredOtp   = trim($_POST['otp']   ?? '');
    $enteredEmail = trim($_POST['email'] ?? '');

    if (!$enteredOtp || !$enteredEmail) {
        echo json_encode(['success' => false, 'message' => 'Missing data.']);
        exit();
    }
    if (!isset($_SESSION['otp_code'], $_SESSION['otp_email'], $_SESSION['otp_expiry'])) {
        echo json_encode(['success' => false, 'message' => 'No OTP found. Please request a new one.']);
        exit();
    }
    if ($_SESSION['otp_email'] !== $enteredEmail) {
        echo json_encode(['success' => false, 'message' => 'Email mismatch. Please request a new OTP.']);
        exit();
    }
    if (time() > $_SESSION['otp_expiry']) {
        unset($_SESSION['otp_code'], $_SESSION['otp_email'], $_SESSION['otp_expiry'], $_SESSION['otp_verified']);
        echo json_encode(['success' => false, 'message' => 'OTP has expired. Please request a new one.']);
        exit();
    }
    if ($enteredOtp !== $_SESSION['otp_code']) {
        echo json_encode(['success' => false, 'message' => 'Incorrect OTP. Please try again.']);
        exit();
    }


    $_SESSION['otp_verified']   = true;
    $_SESSION['verified_email'] = $enteredEmail;
    unset($_SESSION['otp_code'], $_SESSION['otp_expiry']);

    echo json_encode(['success' => true, 'message' => 'Email verified successfully!']);
    exit();
}


echo json_encode(['success' => false, 'message' => 'Invalid action.']);