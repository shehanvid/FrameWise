<?php
session_start();
if (isset($_SESSION["username"])) {
    header("Location: index.php");
    exit();
}

// Determine which fields to clear based on error
$err = $_GET["error"] ?? "";
$val = [
    'name'      => htmlspecialchars($_SESSION["name"]      ?? ''),
    'email'     => htmlspecialchars($_SESSION["email"]     ?? ''),
    'uid'       => htmlspecialchars($_SESSION["uid"]       ?? ''),
];

// Clear the offending field so user re-enters it
if ($err === "invalidEmail" || $err === "emailtaken")   $val["email"] = "";
if ($err === "invalidUid"   || $err === "usernametaken") $val["uid"]  = "";
if ($err === "passwordnotmatch") { /* passwords always cleared */ }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FrameWise — Register</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:wght@300;400;500&family=Syne:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/signup.css">
</head>
<body>

<div class="login-wrap">

    <div class="login-brand">
        <div class="login-brand-name">FrameWise</div>
        <div class="login-brand-sub">Photography Planning Studio</div>
    </div>

    <div class="login-card">
        <div class="login-card-title">Get started</div>
        <div class="login-card-sub">Create your free FrameWise account</div>

        <?php if ($err): ?>
            <?php
            $alertMap = [
                "emptyinput"      => "Please fill in all fields.",
                "invalidUid"      => "Username is invalid. Use letters, numbers and underscores only.",
                "invalidEmail"    => "That doesn't look like a valid email address.",
                "passwordnotmatch"=> "Passwords do not match — please try again.",
                "stmtfailed"      => "Something went wrong on our end. Please try again.",
                "usernametaken"   => "That username is already taken. Try another.",
                "emailtaken"      => "An account with that email already exists.",
            ];
            $isSuccess = ($err === "none");
            $msg = $isSuccess ? "Account created — you can now log in." : ($alertMap[$err] ?? "");
            if ($msg): ?>
            <div class="login-alert <?= $isSuccess ? 'success' : 'error' ?>">
                <?php if ($isSuccess): ?>
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                <?php else: ?>
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126z"/>
                    </svg>
                <?php endif; ?>
                <?= $msg ?>
            </div>
            <?php endif; ?>
        <?php endif; ?>

        <form action="includes/signup.inc.php" method="post">

            <!-- Name -->
            <div class="field">
                <label for="name">Full Name</label>
                <div class="field-inner">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/>
                    </svg>
                    <input type="text" id="name" name="name"
                           placeholder="Jane Smith"
                           value="<?= $val['name'] ?>"
                           required autocomplete="name">
                </div>
            </div>

            <!-- Email -->
            <div class="field">
                <label for="email">Email Address</label>
                <div class="field-inner">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/>
                    </svg>
                    <input type="text" id="email" name="email"
                           placeholder="you@example.com"
                           value="<?= $val['email'] ?>"
                           required autocomplete="email"
                           class="<?= in_array($err, ['invalidEmail','emailtaken']) ? 'field-error' : '' ?>">
                </div>
            </div>

            <!-- Username -->
            <div class="field">
                <label for="uid">Username</label>
                <div class="field-inner">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12a4.5 4.5 0 11-9 0 4.5 4.5 0 019 0zm0 0c0 1.657 1.007 3 2.25 3S21 13.657 21 12a9 9 0 10-2.636 6.364M16.5 12V8.25"/>
                    </svg>
                    <input type="text" id="uid" name="uid"
                           placeholder="jane_smith"
                           value="<?= $val['uid'] ?>"
                           required autocomplete="username"
                           class="<?= in_array($err, ['invalidUid','usernametaken']) ? 'field-error' : '' ?>">
                </div>
            </div>

            <!-- Password -->
            <div class="field">
                <label for="pwd">Password</label>
                <div class="field-inner">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/>
                    </svg>
                    <input type="password" id="pwd" name="pwd"
                           placeholder="••••••••"
                           required autocomplete="new-password"
                           class="<?= ($err === 'passwordnotmatch') ? 'field-error' : '' ?>">
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

            <!-- Repeat Password -->
            <div class="field">
                <label for="pwdrepeat">Confirm Password</label>
                <div class="field-inner">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z"/>
                    </svg>
                    <input type="password" id="pwdrepeat" name="pwdrepeat"
                           placeholder="••••••••"
                           required autocomplete="new-password"
                           class="<?= ($err === 'passwordnotmatch') ? 'field-error' : '' ?>">
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

            <!-- Password strength indicator -->
            <div class="pwd-strength" id="pwd-strength" style="display:none;">
                <div class="pwd-strength-bar">
                    <div class="pwd-strength-fill" id="pwd-fill"></div>
                </div>
                <span class="pwd-strength-label" id="pwd-label"></span>
            </div>

            <button type="submit" name="submit" class="login-btn" style="margin-top:16px;">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zM4 19.235v-.11a6.375 6.375 0 0112.75 0v.109A12.318 12.318 0 0110.374 21c-2.331 0-4.512-.645-6.374-1.766z"/>
                </svg>
                Create Account
            </button>

        </form>

        <div class="login-divider"><span>Already have an account?</span></div>

        <div class="login-register">
            <a href="login.php">Sign in instead</a>
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

// Password strength meter
document.getElementById('pwd').addEventListener('input', function() {
    const val = this.value;
    const wrap = document.getElementById('pwd-strength');
    const fill = document.getElementById('pwd-fill');
    const lbl  = document.getElementById('pwd-label');

    if (!val.length) { wrap.style.display = 'none'; return; }
    wrap.style.display = 'flex';

    let score = 0;
    if (val.length >= 8)              score++;
    if (/[A-Z]/.test(val))            score++;
    if (/[0-9]/.test(val))            score++;
    if (/[^A-Za-z0-9]/.test(val))     score++;

    const levels = [
        { pct: '25%', color: '#ef4444', text: 'Weak' },
        { pct: '50%', color: '#f59e0b', text: 'Fair' },
        { pct: '75%', color: '#3b82f6', text: 'Good' },
        { pct: '100%',color: '#22c55e', text: 'Strong' },
    ];
    const lvl = levels[score - 1] || levels[0];
    fill.style.width     = lvl.pct;
    fill.style.background = lvl.color;
    lbl.textContent      = lvl.text;
    lbl.style.color      = lvl.color;
});
</script>

</body>
</html>