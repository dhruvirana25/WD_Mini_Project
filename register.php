
<?php
session_start();
require_once "db.php";

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST["username"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    if ($username === "" || $email === "" || $password === "") {
        $message = "Please fill in all fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Please enter a valid email address.";
    } elseif (strlen($password) < 8) {
        $message = "Password must be at least 8 characters.";
    } else {
        $check = mysqli_prepare(
            $conn,
            "SELECT id FROM users WHERE username = ? OR email = ?"
        );

        mysqli_stmt_bind_param($check, "ss", $username, $email);
        mysqli_stmt_execute($check);
        mysqli_stmt_store_result($check);

        if (mysqli_stmt_num_rows($check) > 0) {
            $message = "Username or email already exists.";
        } else {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            $stmt = mysqli_prepare(
                $conn,
                "INSERT INTO users (username, email, password)
                 VALUES (?, ?, ?)"
            );

            mysqli_stmt_bind_param(
                $stmt,
                "sss",
                $username,
                $email,
                $hashedPassword
            );

            if (mysqli_stmt_execute($stmt)) {
                $message = "Registration successful! You can now log in.";
            } else {
                $message = "Registration failed. Please try again.";
            }

            mysqli_stmt_close($stmt);
        }

        mysqli_stmt_close($check);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | PixelXpace</title>
</head>
<body>
    <h2>Create your PixelXpace account</h2>

    <?php if ($message !== ""): ?>
        <p><?= htmlspecialchars($message, ENT_QUOTES, "UTF-8") ?></p>
    <?php endif; ?>

    <form method="POST" action="register.php">
        <label>Username</label><br>
        <input type="text" name="username" required maxlength="100"><br><br>

        <label>Email</label><br>
        <input type="email" name="email" required maxlength="254"><br><br>

        <label>Password</label><br>
        <input type="password" name="password" required minlength="8"><br><br>

        <button type="submit">Create Account</button>
    </form>

    <p>Already have an account?
        <a href="login.php">Log in</a>
    </p>
</body>
</html>
