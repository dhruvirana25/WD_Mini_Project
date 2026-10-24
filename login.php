
<?php
session_start();
require_once "db.php";

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    if ($email === "" || $password === "") {
        $message = "Please enter your email and password.";
    } else {
        $stmt = mysqli_prepare(
            $conn,
            "SELECT id, username, password FROM users WHERE email = ?"
        );

        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);

        if (mysqli_stmt_num_rows($stmt) === 1) {
            mysqli_stmt_bind_result($stmt, $id, $username, $hashedPassword);
            mysqli_stmt_fetch($stmt);

            if (password_verify($password, $hashedPassword)) {
                session_regenerate_id(true);

                $_SESSION["user_id"] = $id;
                $_SESSION["username"] = $username;

                header("Location: index.php");
                exit;
            }
        }

        $message = "Invalid email or password.";
        mysqli_stmt_close($stmt);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | PixelXpace</title>
</head>
<body>
    <h2>Welcome back to PixelXpace</h2>

    <?php if ($message !== ""): ?>
        <p><?= htmlspecialchars($message, ENT_QUOTES, "UTF-8") ?></p>
    <?php endif; ?>

    <form method="POST" action="login.php">
        <label>Email</label><br>
        <input type="email" name="email" required><br><br>

        <label>Password</label><br>
        <input type="password" name="password" required><br><br>

        <button type="submit">Log In</button>
    </form>

    <p>Don't have an account?
        <a href="register.php">Create an account</a>
    </p>
</body>
</html>
