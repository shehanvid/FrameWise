<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FrameWise</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:wght@300;400;500&family=Syne:wght@700&display=swap" rel="stylesheet">
    <style>
    *, *::before, *::after {
        box-sizing: border-box;
        margin: 0;
        padding: 0;
    }

    body {
        background: #0a0a0a !important;
        height: 100vh;
        overflow: hidden;
        display: flex;
        align-items: center;
        justify-content: center;
        font-family: 'DM Sans', sans-serif;
        padding: 20px;
    }

    .welcome-page {
        position: relative;
        z-index: 1;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-align: center;
        padding: 40px 20px;
    }

    body::before {
        content: '';
        position: fixed;
        inset: 0;
        background-image:
            linear-gradient(rgba(59,130,246,0.03) 1px, transparent 1px),
            linear-gradient(90deg, rgba(59,130,246,0.03) 1px, transparent 1px);
        background-size: 40px 40px;
        pointer-events: none;
        z-index: 0;
    }

    .app-title {
        font-family: 'Bebas Neue', sans-serif;
        font-size: 82px;
        color: #f0ede8;
        letter-spacing: .06em;
        line-height: 1;
        text-align: center;
        margin-bottom: 10px;
    }

    .tagline {
        font-family: 'DM Sans', sans-serif;
        font-size: 13px;
        color: #4b5563;
        font-weight: 300;
        text-align: center;
        margin-bottom: 36px;
    }

    .btn-wrap {
        display: flex;
        gap: 10px;
    }

    .btn-login {
        background: #3b82f6;
        border: none;
        border-radius: 10px;
        padding: 11px 32px;
        font-size: 13px;
        font-weight: 700;
        color: #fff;
        cursor: pointer;
        text-decoration: none;
        transition: background .2s;
    }

    .btn-login:hover { background: #2563eb; }

    .btn-register {
        background: transparent;
        border: 0.5px solid #2a2a2a;
        border-radius: 10px;
        padding: 11px 32px;
        font-size: 13px;
        font-weight: 700;
        color: #6b7280;
        text-decoration: none;
        transition: all .2s;
    }

    .btn-register:hover {
        border-color: #3b82f6;
        color: #3b82f6;
    }
    </style>
</head>
<body>

<div class="welcome-page">
    <div class="app-title">FRAMEWISE</div>
    <p class="tagline">Plan smarter shoots in one place.</p>
    <div class="btn-wrap">
        <a href="login.php" class="btn-login">Login</a>
        <a href="signup.php" class="btn-register">Create account</a>
    </div>
</div>

</body>
</html>