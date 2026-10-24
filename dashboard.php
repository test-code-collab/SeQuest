<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: index.html");
    exit;
}

$name = htmlspecialchars($_SESSION["user_name"]);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>SeQuest | Dashboard</title>

    <style>

        * {
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }

        body {
            min-height: 100vh;
            margin: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            background: #f5f4fa;
            color: #252338;
        }

        .card {
            width: 420px;
            max-width: 90%;
            padding: 40px;
            text-align: center;
            background: white;
            border-radius: 24px;
            box-shadow: 0 20px 60px rgba(56, 43, 100, 0.10);
        }

        .icon {
            font-size: 45px;
            color: #7565c9;
            margin-bottom: 15px;
        }

        h1 {
            margin-bottom: 10px;
        }

        p {
            color: #858398;
            line-height: 1.6;
        }

        .logout {
            display: inline-block;
            margin-top: 20px;
            padding: 13px 25px;
            border-radius: 11px;
            background: #7565c9;
            color: white;
            text-decoration: none;
            font-weight: bold;
        }

        .logout:hover {
            background: #6252b3;
        }

    </style>

</head>

<body>

    <div class="card">

        <div class="icon">✦</div>

        <h1>
            Welcome, <?= $name ?>!
        </h1>

        <p>
            You are now signed in to SeQuest.
            Your journey starts here.
        </p>

        <a class="logout" href="logout.php">
            Log out
        </a>

    </div>

</body>

</html>