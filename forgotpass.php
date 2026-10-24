<?php
session_start();

require_once __DIR__ . "/db.php";
require_once __DIR__ . "/vendor/autoload.php";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$message = "";
$messageType = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST["email"] ?? "");

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Please enter a valid email address.";
        $messageType = "error";
    } else {
        // Find the account in the MySQL database.
        $stmt = $conn->prepare(
            "SELECT id, name, email FROM users WHERE email = ? LIMIT 1"
        );

        if (!$stmt) {
            error_log("User lookup prepare failed: " . $conn->error);
            $message = "A database error occurred. Please try again.";
            $messageType = "error";
        } else {
            $stmt->bind_param("s", $email);

            if (!$stmt->execute()) {
                error_log("User lookup execute failed: " . $stmt->error);
                $message = "A database error occurred. Please try again.";
                $messageType = "error";
                $stmt->close();
            } else {
                $result = $stmt->get_result();
                $user = $result->fetch_assoc();
                $stmt->close();

                if (!$user) {
                    // Avoid revealing whether an email is registered.
                    $message = "If an account exists with that email, a verification code has been sent.";
                    $messageType = "success";
                } else {
                    // Generate a random six-digit code and hash it for storage.
                    $code = (string) random_int(100000, 999999);
                    $codeHash = password_hash($code, PASSWORD_DEFAULT);
                    $expiresAt = date("Y-m-d H:i:s", time() + 600);

                    $mail = new PHPMailer(true);

                    try {
                        // Gmail SMTP configuration.
                        $mail->isSMTP();
                        $mail->Host = "smtp.gmail.com";
                        $mail->SMTPAuth = true;

                        // Replace these two placeholders with your own credentials.
                        // Use a Google App Password, not your normal Gmail password.
                        $senderEmail = "fortestingonlyincoding@gmail.com";
                        $appPassword = "ibpsflyacfrvsevy";

                        $mail->Username = $senderEmail;
                        $mail->Password = $appPassword;
                        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                        $mail->Port = 587;
                        $mail->CharSet = "UTF-8";

                        // The sender must match the Gmail SMTP username.
                        $mail->setFrom($senderEmail, "SeQuest");

                        // Send the code to the email associated with the account.
                        $mail->addAddress($user["email"], $user["name"]);

                        $safeName = htmlspecialchars(
                            $user["name"],
                            ENT_QUOTES | ENT_SUBSTITUTE,
                            "UTF-8"
                        );

                        $mail->isHTML(true);
                        $mail->Subject = "SeQuest Password Reset Code";
                        $mail->Body = "
                            <div style='font-family:Arial,sans-serif;max-width:500px;margin:auto;padding:30px;background:#f7f6fb;border-radius:15px;text-align:center;'>
                                <h2 style='color:#7565c9;'>SeQuest</h2>
                                <p>Hello {$safeName}!</p>
                                <p>Someone requested a password reset for your SeQuest account.</p>
                                <p>Your verification code is:</p>
                                <h1 style='letter-spacing:8px;color:#7565c9;font-size:32px;'>{$code}</h1>
                                <p>This code will expire in <strong>10 minutes</strong>.</p>
                                <p style='color:#888;font-size:13px;'>If you did not request this, you can safely ignore this email.</p>
                            </div>
                        ";
                        $mail->AltBody =
                            "Hello " . $user["name"] .
                            ", your SeQuest verification code is: " . $code .
                            ". This code expires in 10 minutes.";

                        // Save the hashed code before sending the email.
                        $conn->begin_transaction();

                        $delete = $conn->prepare(
                            "DELETE FROM password_resets WHERE email = ?"
                        );
                        if (!$delete) {
                            throw new RuntimeException("Could not prepare reset-code cleanup.");
                        }
                        $delete->bind_param("s", $email);
                        if (!$delete->execute()) {
                            throw new RuntimeException("Could not delete previous reset codes.");
                        }
                        $delete->close();

                        $insert = $conn->prepare(
                            "INSERT INTO password_resets (email, code_hash, expires_at, attempts)
                             VALUES (?, ?, ?, 0)"
                        );
                        if (!$insert) {
                            throw new RuntimeException("Could not prepare reset-code storage.");
                        }
                        $insert->bind_param("sss", $email, $codeHash, $expiresAt);
                        if (!$insert->execute()) {
                            throw new RuntimeException("Could not save the verification code.");
                        }
                        $insert->close();

                        $conn->commit();

                        // Send the email.
                        $mail->send();

                        $_SESSION["reset_email"] = $email;
                        header("Location: verify-code.php");
                        exit;
                    } catch (Throwable $e) {
                        try {
                            $conn->rollback();
                        } catch (Throwable $rollbackError) {
                            error_log("Reset-code rollback failed: " . $rollbackError->getMessage());
                        }

                        // Log technical details privately; do not show credentials or server details.
                        error_log("SeQuest password reset error: " . $e->getMessage());
                        if ($e instanceof Exception) {
                            error_log("PHPMailer details: " . $mail->ErrorInfo);
                        }

                        $message = "We couldn't send your verification code. Please check your Gmail settings and try again.";
                        $messageType = "error";
                    }
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SeQuest | Forgot Password</title>
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
            padding: 20px;
            background: #f5f4fa;
            color: #252338;
        }

        .card {
            width: 400px;
            max-width: 100%;
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
            transition: background 0.2s;
        }

        button:hover {
            background: #6252b3;
        }

        .message {
            padding: 12px;
            border-radius: 10px;
            margin-bottom: 18px;
            font-size: 13px;
            line-height: 1.5;
            overflow-wrap: anywhere;
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

        .back:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <main class="card">
        <div class="logo">Se<span>Quest</span></div>

        <h2>Forgot your password?</h2>
        <p class="description">
            Enter the email associated with your SeQuest account and we'll send you a verification code.
        </p>

        <?php if ($message !== ""): ?>
            <div class="message <?= htmlspecialchars($messageType, ENT_QUOTES, "UTF-8") ?>" role="status">
                <?= htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8") ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="forgotpass.php">
            <label for="email">Email address</label>
            <input
                type="email"
                id="email"
                name="email"
                placeholder="you@example.com"
                required
                autocomplete="email"
                value="<?= htmlspecialchars($_POST["email"] ?? "", ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8") ?>"
            >
            <button type="submit">Send verification code</button>
        </form>

        <a class="back" href="index.html">← Back to Sign in</a>
    </main>
</body>
</html>

