<?php
session_start();
define('BASE_URL', '/FrameWise/');
if (isset($_SESSION["username"])) {
    header("Location: index.php");
    exit();
}
$step = $_SESSION["reset_step"] ?? 1;

if ($step === 3) {
    $stepTime = $_SESSION["reset_step_time"] ?? 0;
    if (empty($_SESSION["reset_verified"]) || (time() - $stepTime) > 600) {
        unset($_SESSION["reset_step"], $_SESSION["reset_email"], $_SESSION["reset_verified"], $_SESSION["reset_step_time"]);
        $step = 1;
    } elseif ($_SESSION['reset_step_used'] ?? false) {

        unset($_SESSION["reset_step"], $_SESSION["reset_email"], $_SESSION["reset_verified"], 
              $_SESSION["reset_step_time"], $_SESSION["reset_step_used"]);
        $step = 1;
    } else {
        $_SESSION['reset_step_used'] = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FrameWise — Reset Password</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:wght@300;400;500&family=Syne:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/login-style.css">
</head>
<body>

<div class="login-wrap">

    <div class="login-brand">
        <div class="login-brand-name">FrameWise<span class="login-brand-dot">.</span></div>
        <div class="login-brand-sub">Photography Planning Studio</div>
    </div>

    <div class="login-card">

        <?php if ($step === 1): ?>
            <div class="login-card-title">Forgot password?</div>
            <div class="login-card-sub">Enter your email and we'll send a code</div>

            <div id="fp-msg"></div>

            <?php if (isset($_GET["error"]) && $_GET["error"] === "notfound"): ?>
                <div class="login-alert error">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126z"/>
                    </svg>
                    No account found with that email.
                </div>
            <?php elseif (isset($_GET["error"]) && $_GET["error"] === "emailfail"): ?>
                <div class="login-alert error">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126z"/>
                    </svg>
                    Failed to send email. Please try again.
                </div>
            <?php endif; ?>

            <div class="field">
                <label for="fp-email">Email address</label>
                <div class="field-inner">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/>
                    </svg>
                    <input type="email" id="fp-email" placeholder="you@example.com" autocomplete="email">
                </div>
            </div>

            <button class="login-btn" id="send-otp-btn" onclick="sendOtp()">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/>
                </svg>
                Send code
            </button>

        <?php elseif ($step === 2): ?>
            <div class="login-card-title">Check your inbox</div>
            <div class="login-card-sub">We sent a 6-digit code to <?= htmlspecialchars($_SESSION["reset_email"] ?? '') ?></div>

            <?php if (isset($_GET["error"]) && $_GET["error"] === "badotp"): ?>
                <div class="login-alert error">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126z"/>
                    </svg>
                    Wrong or expired code. Try again.
                </div>
            <?php endif; ?>

            <form action="<?= BASE_URL ?>includes/reset.inc.php" method="post">
                <input type="hidden" name="action" value="verify_reset">

                <div class="field">
                    <label for="otp-input">Verification code</label>
                    <div class="field-inner">
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M7.864 4.243A7.5 7.5 0 0119.5 10.5c0 2.92-.556 5.709-1.568 8.268M5.742 6.364A7.465 7.465 0 004.5 10.5a7.464 7.464 0 01-1.15 3.993m1.989 3.559A11.209 11.209 0 008.25 10.5a3.75 3.75 0 117.5 0c0 .527-.021 1.049-.064 1.565M12 10.5a14.94 14.94 0 01-3.6 9.75m6.633-4.596a18.666 18.666 0 01-2.485 5.33"/>
                        </svg>
                        <input type="text" id="otp-input" name="otp" placeholder="123456" maxlength="6" autocomplete="one-time-code">
                    </div>
                </div>

                <button type="submit" class="login-btn">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Verify code
                </button>
            </form>

        <?php elseif ($step === 3): ?>
            
            <div class="login-card-title">New password</div>
            <div class="login-card-sub">Pick something you haven't used before</div>

            <?php if (isset($_GET["error"]) && $_GET["error"] === "pwdmatch"): ?>
                <div class="login-alert error">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126z"/>
                    </svg>
                    Passwords don't match.
                </div>
            <?php elseif (isset($_GET["error"]) && $_GET["error"] === "tooshort"): ?>
                <div class="login-alert error">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126z"/>
                    </svg>
                    Password must be at least 6 characters.
                </div>
            <?php elseif (isset($_GET["error"]) && $_GET["error"] === "none"): ?>
                <div class="login-alert success">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Password updated successfully! Redirecting…
                </div>
            <?php endif; ?>
            

            <form action="<?= BASE_URL ?>includes/reset.inc.php" method="post">
                <input type="hidden" name="action" value="update_pwd">

                <div class="field">
                    <label for="new-pwd">New password</label>
                    <div class="field-inner">
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/>
                        </svg>
                        <input type="password" id="new-pwd" name="pwd" placeholder="••••••••" required autocomplete="new-password">
                        <button type="button" class="field-eye" onclick="togglePwd(this)">
                            <svg class="eye-show" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            <svg class="eye-hide" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" style="display:none;">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="field">
                    <label for="new-pwd2">Confirm password</label>
                    <div class="field-inner">
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/>
                        </svg>
                        <input type="password" id="new-pwd2" name="pwdrepeat" placeholder="••••••••" required autocomplete="new-password">
                        <button type="button" class="field-eye" onclick="togglePwd(this)">
                            <svg class="eye-show" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            <svg class="eye-hide" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" style="display:none;">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <button type="submit" class="login-btn">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/>
                    </svg>
                    Update password
                </button>
            </form>

        <?php endif; ?>

        <div class="login-divider"><span>remembered it?</span></div>
        <div class="login-register"><a href="<?= BASE_URL ?>login.php">Back to login</a></div>

    </div>
</div>

<script>
function togglePwd(btn) {
    const input = btn.closest('.field-inner').querySelector('input');
    const show = btn.querySelector('.eye-show');
    const hide = btn.querySelector('.eye-hide');
    if (input.type === 'password') {
        input.type = 'text';
        show.style.display = 'none';
        hide.style.display = 'block';
    } else {
        input.type = 'password';
        show.style.display = 'block';
        hide.style.display = 'none';
    }
}

function sendOtp() {
    const email = document.getElementById('fp-email').value.trim();
    const msg = document.getElementById('fp-msg');
    const btn = document.getElementById('send-otp-btn');

    if (!email) {
        msg.innerHTML = '<div class="login-alert error"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126z"/></svg>Please enter your email.</div>';
        return;
    }

    btn.disabled = true;
    btn.style.opacity = '0.6';

    fetch('<?= BASE_URL ?>includes/reset.inc.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=send_reset&email=' + encodeURIComponent(email)
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            window.location.reload();
        } else {
            msg.innerHTML = '<div class="login-alert error"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126z"/></svg>' + data.message + '</div>';
            btn.disabled = false;
            btn.style.opacity = '1';
        }
    });
}
</script>

</body>
</html>