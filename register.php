<?php

require_once "db.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    die("Invalid request.");
}

$name = trim($_POST["name"] ?? "");
$email = trim($_POST["email"] ?? "");
$password = $_POST["password"] ?? "";
$confirmPassword = $_POST["confirmPassword"] ?? "";

// Check required fields
if (
    $name === "" ||
    $email === "" ||
    $password === "" ||
    $confirmPassword === ""
) {
    die("Please fill in all fields.");
}

// Check email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    die("Please enter a valid email address.");
}

// Check password length
if (strlen($password) < 8) {
    die("Password must be at least 8 characters.");
}

// Check passwords
if ($password !== $confirmPassword) {
    die("Passwords do not match.");
}

// Check if email already exists
$check = $conn->prepare(
    "SELECT id FROM users WHERE email = ?"
);

if (!$check) {
    die("Database error: " . $conn->error);
}

$check->bind_param("s", $email);
$check->execute();

$result = $check->get_result();

if ($result->num_rows > 0) {
    $check->close();
    die("An account with this email already exists.");
}

$check->close();

// Hash password
$hashedPassword = password_hash(
    $password,
    PASSWORD_DEFAULT
);

// Insert user
$stmt = $conn->prepare(
    "INSERT INTO users (name, email, password)
     VALUES (?, ?, ?)"
);

if (!$stmt) {
    die("Database error: " . $conn->error);
}

$stmt->bind_param(
    "sss",
    $name,
    $email,
    $hashedPassword
);

if ($stmt->execute()) {

    $stmt->close();
    $conn->close();

    header("Location: index.html?registered=1");
    exit;

} else {

    die("Registration failed: " . $stmt->error);
}

?>