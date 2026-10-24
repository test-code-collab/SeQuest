<?php
session_start();

require "db.php";

$message = "";
$messageType = "";

// Make sure the user came through the verification process
if (
    !isset($_SESSION["reset_email"]) ||
    !isset($_SESSION["reset_verified"]) ||
    $_SESSION["reset_verified"] !== true
) {
    header("Location: forgotpass.php");
    exit;
}

$email = $_SESSION["reset_email"];

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $password = $_POST["password"] ?? "";
    $confirmPassword = $_POST["confirmPassword"] ?? "";

    // Check password length
    if (strlen($password) < 8) {

        $message = "Password must be at least 8 characters long.";
        $messageType = "error";

    // Check if passwords match
    } elseif ($password !== $confirmPassword) {

        $message = "Passwords do not match.";
        $messageType = "error";

    } else {

        // Hash the new password
        $passwordHash = password_hash(
            $password,
            PASSWORD_DEFAULT
        );

        // Update the user's password
        $stmt = $conn->prepare(
            "UPDATE users SET password = ? WHERE email = ?"
        );

        if (!$stmt) {

            $message = "Database error: " . $conn->error;
            $messageType = "error";

        } else {

            $stmt->bind_param(
                "ss",
                $passwordHash,
                $email
            );

            if ($stmt->execute()) {

                $stmt->close();

                // Delete the used reset code
                $delete = $conn->prepare(
                    "DELETE FROM password_resets WHERE email = ?"
                );

                if ($delete) {
                    $delete->bind_param("s", $email);
                    $delete->execute();
                    $delete->close();
                }

                // Clear password-reset session information
                unset($_SESSION["reset_email"]);
                unset($_SESSION["reset_verified"]);

                // Send user back to login page
                header("Location: index.html");
                exit;

            } else {

                $message =
                    "Unable to change your password.";

                $messageType = "error";

                $stmt->close();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>SeQuest | Reset Password</title>

    <style>

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
        }

        body {
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            background: #f5f4fa;
            color: #252338;
        }

        .card {
            width: 400px;
            max-width: 90%;
            background: white;
            padding: 40px;
            border-radius: 24px;
            box-shadow:
                0 20px 60px rgba(56, 43, 100, 0.10);
        }

        .logo {
            text-align: center;
            font-size: 28px;
            font-weight: bold;
            margin-bottom: 25px;
        }

        .logo span {
            color: #7565c9;
        }

        h2 {
            text-align: center;
            margin-bottom: 10px;
        }

        .description {
            text-align: center;
            color: #858398;
            font-size: 14px;
            line-height: 1.6;
            margin-bottom: 25px;
        }

        label {
            display: block;
            font-size: 13px;
            font-weight: bold;
            margin-bottom: 8px;
        }

        input {
            width: 100%;
            padding: 14px;
            border: 1px solid #e7e4ef;
            border-radius: 11px;
            outline: none;
            margin-bottom: 18px;
            font-size: 14px;
        }

        input:focus {
            border-color: #7565c9;
        }

        button {
            width: 100%;
            padding: 14px;
            border: none;
            border-radius: 11px;
            background: #7565c9;
            color: white;
            font-weight: bold;
            cursor: pointer;
        }

        button:hover {
            background: #6252b3;
        }

        .message {
            padding: 12px;
            border-radius: 10px;
            margin-bottom: 18px;
            font-size: 13px;
        }

        .error {
            background: #ffe8ec;
            color: #c73f54;
        }

        .success {
            background: #e5f7ed;
            color: #28734f;
        }

    </style>

</head>

<body>

<div class="card">

    <div class="logo">
        Se<span>Quest</span>
    </div>

    <h2>Reset your password</h2>

    <p class="description">
        Create a new password for your SeQuest account.
    </p>

    <?php if ($message): ?>

        <div class="message <?= htmlspecialchars($messageType) ?>">
            <?= htmlspecialchars($message) ?>
        </div>

    <?php endif; ?>

    <form method="POST">

        <label for="password">
            New password
        </label>

        <input
            type="password"
            id="password"
            name="password"
            placeholder="At least 8 characters"
            minlength="8"
            required
        >

        <label for="confirmPassword">
            Confirm new password
        </label>

        <input
            type="password"
            id="confirmPassword"
            name="confirmPassword"
            placeholder="Re-enter your password"
            minlength="8"
            required
        >

        <button type="submit">
            Reset password
        </button>

    </form>

</div>

</body>

</html>