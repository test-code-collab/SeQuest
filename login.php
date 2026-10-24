<?php

session_start();

require_once "db.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: index.html");
    exit;
}

$email = trim($_POST["email"] ?? "");
$password = $_POST["password"] ?? "";

if ($email === "" || $password === "") {
    header("Location: index.html?error=missing");
    exit;
}

/* Find account */

$stmt = $conn->prepare(
    "SELECT id, name, email, password
     FROM users
     WHERE email = ?
     LIMIT 1"
);

$stmt->bind_param("s", $email);
$stmt->execute();

$result = $stmt->get_result();

$user = $result->fetch_assoc();

$stmt->close();

/* Check account */

if (!$user || !password_verify($password, $user["password"])) {
    header("Location: index.html?error=invalid");
    exit;
}

/* Create login session */

$_SESSION["user_id"] = $user["id"];
$_SESSION["user_name"] = $user["name"];
$_SESSION["user_email"] = $user["email"];

header("Location: dashboard.php");
exit;