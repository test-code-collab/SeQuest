<?php
session_start();

require "db.php";

$message = "";
$messageType = "";

if (!isset($_SESSION["reset_email"])) {
    header("Location: forgotpass.php");
    exit;
}

$email = $_SESSION["reset_email"];

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $code = trim($_POST["code"] ?? "");

    if (!preg_match("/^[0-9]{6}$/", $code)) {

        $message = "Please enter the 6-digit verification code.";
        $messageType = "error";

    } else {

        $stmt = $conn->prepare(
            "SELECT id, code_hash, expires_at, attempts
             FROM password_resets
             WHERE email = ?
             ORDER BY id DESC
             LIMIT 1"
        );

        $stmt->bind_param("s", $email);
        $stmt->execute();

        $result = $stmt->get_result();
        $reset = $result->fetch_assoc();

        $stmt->close();

        if (!$reset) {

            $message = "No verification code was found. Please request a new one.";
            $messageType = "error";

        } elseif (strtotime($reset["expires_at"]) < time()) {

            $message = "This verification code has expired. Please request a new one.";
            $messageType = "error";

        } elseif (!password_verify($code, $reset["code_hash"])) {

            $message = "Incorrect verification code.";
            $messageType = "error";

        } else {

            $_SESSION["reset_verified"] = true;

            header("Location: reset-password.php");
            exit;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>SeQuest | Verify Code</title>

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
            box-shadow: 0 20px 60px rgba(56, 43, 100, 0.10);
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
            font-size: 16px;
            text-align: center;
            letter-spacing: 5px;
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

        .back {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: #7565c9;
            text-decoration: none;
            font-size: 13px;
        }

    </style>

</head>

<body>

<div class="card">

    <div class="logo">
        Se<span>Quest</span>
    </div>

    <h2>Enter verification code</h2>

    <p class="description">
        We sent a 6-digit verification code to your email.
        Enter it below to continue resetting your password.
    </p>

    <?php if ($message): ?>

        <div class="message <?= htmlspecialchars($messageType) ?>">
            <?= htmlspecialchars($message) ?>
        </div>

    <?php endif; ?>

    <form method="POST">

        <label for="code">
            Verification code
        </label>

        <input
            type="text"
            id="code"
            name="code"
            placeholder="123456"
            maxlength="6"
            inputmode="numeric"
            autocomplete="one-time-code"
            required
        >

        <button type="submit">
            Verify code
        </button>

    </form>

    <a class="back" href="forgotpass.php">
        ← Request a new code
    </a>

</div>

</body>

</html>