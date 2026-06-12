<?php
session_start();
define('BASE_URL', '/FrameWise/');
header('Content-Type: application/json');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require_once __DIR__ . '/../vendor/autoload.php';
require_once 'dbh.inc.php';

define('GMAIL_ADDRESS', 'framewise.noreply@gmail.com');
define('GMAIL_APP_PWD',  'xgzv ctps bgvc stxr');

$action = $_POST['action'] ?? '';

if ($action === 'send_reset') {

    $email = trim($_POST['email'] ?? '');

    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Enter a valid email address.']);
        exit();
    }

    $stmt = mysqli_stmt_init($conn);
    mysqli_stmt_prepare($stmt, "SELECT usersId FROM users WHERE usersEmail = ?");
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);

    if (!mysqli_fetch_assoc($res)) {
        echo json_encode(['success' => false, 'message' => 'No account found with that email.']);
        exit();
    }

    $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    $_SESSION['reset_otp']    = $otp;
    $_SESSION['reset_email']  = $email;
    $_SESSION['reset_expiry'] = time() + 600;
    $_SESSION['reset_step']   = 2;

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
        $mail->Subject = 'FrameWise — Password Reset Code';
        $mail->Body    =
            "Hello,\n\n" .
            "Your password reset code is:\n\n" .
            "  $otp\n\n" .
            "This code expires in 10 minutes.\n" .
            "If you didn't request this, just ignore this email.\n\n" .
            "— The FrameWise Team";

        $mail->send();
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        unset($_SESSION['reset_otp'], $_SESSION['reset_email'], $_SESSION['reset_expiry'], $_SESSION['reset_step']);
        echo json_encode(['success' => false, 'message' => 'Failed to send email. Please try again.']);
    }
    exit();
}

if ($action === 'verify_reset') {

    header('Content-Type: text/html');

    $otp   = trim($_POST['otp']   ?? '');
    $email = $_SESSION['reset_email'] ?? '';

    if (!isset($_SESSION['reset_otp'], $_SESSION['reset_expiry'])) {
        header('Location: ' . BASE_URL . 'includes/forgot-password.php?error=badotp');
        exit();
    }
    if (time() > $_SESSION['reset_expiry']) {
        unset($_SESSION['reset_otp'], $_SESSION['reset_email'], $_SESSION['reset_expiry'], $_SESSION['reset_step']);
        header('Location: ' . BASE_URL . 'includes/forgot-password.php?error=badotp');
        exit();
    }
    if ($otp !== $_SESSION['reset_otp']) {
        header('Location: ' . BASE_URL . 'includes/forgot-password.php?error=badotp');
        exit();
    }

    unset($_SESSION['reset_otp'], $_SESSION['reset_expiry']);
    $_SESSION['reset_verified']  = true;
    $_SESSION['reset_step']      = 3;
    $_SESSION['reset_step_time'] = time();
    $_SESSION['reset_step_used'] = false;

    header('Location: ' . BASE_URL . 'includes/forgot-password.php');
    exit();
}

if ($action === 'update_pwd') {

    header('Content-Type: text/html');

    if (empty($_SESSION['reset_verified']) || empty($_SESSION['reset_email'])) {
        header('Location: ' . BASE_URL . 'includes/forgot-password.php');
        exit();
    }

    $pwd       = $_POST['pwd']       ?? '';
    $pwdrepeat = $_POST['pwdrepeat'] ?? '';

    if (strlen($pwd) < 6) {
        header('Location: ' . BASE_URL . 'includes/forgot-password.php?error=tooshort');
        exit();
    }
    if ($pwd !== $pwdrepeat) {
        header('Location: ' . BASE_URL . 'includes/forgot-password.php?error=pwdmatch');
        exit();
    }

    $hashed = password_hash($pwd, PASSWORD_DEFAULT);
    $email  = $_SESSION['reset_email'];

    $stmt = mysqli_stmt_init($conn);
    mysqli_stmt_prepare($stmt, "UPDATE users SET usersPwd = ? WHERE usersEmail = ?");
    mysqli_stmt_bind_param($stmt, "ss", $hashed, $email);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    unset($_SESSION['reset_verified'], $_SESSION['reset_email'], $_SESSION['reset_step'], $_SESSION['reset_step_time']);

    header('Location: ' . BASE_URL . 'login.php?error=pwdreset');
    exit();
}

echo json_encode(['success' => false, 'message' => 'Invalid action.']);