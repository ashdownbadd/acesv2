<?php
$error = $_SESSION['login_error'] ?? null;
if ($error) unset($_SESSION['login_error']);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ACES Login</title>
    <link rel="stylesheet" href="assets/css/variables.css">
    <link rel="stylesheet" href="assets/css/reset.css">
    <link rel="stylesheet" href="assets/css/main.css">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&family=Syne:wght@700;800&family=IBM+Plex+Mono:wght@400;500&display=swap" rel="stylesheet">
</head>

<body class="c-login">

    <div class="c-login__panel u-anim-up">
        <div class="c-login__eyebrow">
            <span class="c-login__line"></span>
            Cooperative Management
            <span class="c-login__line c-login__line--right"></span>
        </div>

        <div class="c-login__logo">ACES</div>
        <div class="c-login__sub">Member Portal</div>

        <?php if ($error): ?>
            <div class="c-login__error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="index.php">
            <input type="hidden" name="action" value="login">

            <div class="c-login__field">
                <label class="c-login__label">Username</label>
                <input class="c-login__input" type="text" name="username" required autofocus>
            </div>

            <div class="c-login__field">
                <label class="c-login__label">Password</label>
                <input class="c-login__input" type="password" name="password" required>
            </div>

            <button type="submit" class="c-login__button">Sign In</button>
        </form>
    </div>

</body>

</html>