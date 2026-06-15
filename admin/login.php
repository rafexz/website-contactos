<?php
require_once __DIR__ . '/queries.php';

if (admin_is_logged_in()) {
    redirect_to('panel.php');
}

$message = get_flash();
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login Admin</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="../assets/css/style.css?v=<?php echo time(); ?>">
</head>
<body class="admin-body">
    <div class="admin-login">
        <h1>Login Admin</h1>

        <?php if ($message): ?>
            <p class="login-message"><?php echo htmlspecialchars($message); ?></p>
        <?php endif; ?>

        <form method="post" action="queries.php">
            <input type="hidden" name="action" value="login">

            <p>
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required>
            </p>

            <p>
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </p>

            <button type="submit">Entrar</button>
        </form>
    </div>
</body>
</html>
