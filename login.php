<?php
session_start();
if (isset($_SESSION["username"])) {
    header("Location: index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FrameWise — Login</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:wght@300;400;500&family=Syne:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/login-style.css">
</head>
<body>

<div class="login-wrap">

    <div class="login-brand">
        <div class="login-brand-name">FrameWise</div>
        <div class="login-brand-sub">Photography Planning Studio</div>
    </div>

    <div class="login-card">
        <div class="login-card-title">Welcome back</div>
        <div class="login-card-sub">Sign in to access your shoot plans</div>

        <?php if (isset($_GET["error"])): ?>
            <?php if ($_GET["error"] === "emptyinput"): ?>
                <div class="login-alert error">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126z"/>
                    </svg>
                    Please fill in all fields.
                </div>
            <?php elseif ($_GET["error"] === "wronglogin"): ?>
                <div class="login-alert error">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126z"/>
                    </svg>
                    Invalid username or password.
                </div>
            <?php elseif ($_GET["error"] === "stmtfailed"): ?>
                <div class="login-alert error">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126z"/>
                    </svg>
                    Something went wrong. Please try again.
                </div>
            <?php elseif ($_GET["error"] === "pwdreset"): ?>
                <div class="login-alert success">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Password updated, you can now sign in.
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <form action="includes/login.inc.php" method="post">

            <div class="field">
                <label for="uid">Email or Username</label>
                <div class="field-inner">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/>
                    </svg>
                    <input type="text" id="uid" name="uid" placeholder="you@example.com" required autocomplete="username">
                </div>
            </div>

            <div class="field">
                <label for="pwd">Password</label>
                <div class="field-inner">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/>
                    </svg>
                    <input type="password" id="pwd" name="pwd" placeholder="••••••••" required autocomplete="current-password">
                    <button type="button" class="field-eye" onclick="togglePwd(this)" aria-label="Toggle password visibility">
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

            <div style="text-align:right; margin-top:-8px; margin-bottom:14px;">
                <a href="includes/forgot-password.php" style="font-size:12px; color:#3b82f6; text-decoration:none;">Forgot password?</a>
            </div>

            <button type="submit" name="submit" class="login-btn">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75"/>
                </svg>
                Sign in
            </button>

        </form>

        <div class="login-divider"><span>New here?</span></div>

        <div class="login-register">
            Don't have an account? <a href="signup.php">Create one</a>
        </div>
    </div>
</div>

<script>
function togglePwd(btn) {
    const input = btn.closest('.field-inner').querySelector('input');
    const show  = btn.querySelector('.eye-show');
    const hide  = btn.querySelector('.eye-hide');
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
</script>

</body>
</html>