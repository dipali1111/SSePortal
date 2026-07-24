<?php
session_start();
require_once __DIR__ . '/includes/functions.php';

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($username === 'hm' && $password === 'hm1234') {
        $_SESSION['user_id'] = 1;
        $_SESSION['role'] = 'HM';
        $_SESSION['full_name'] = 'Head Master';
        header('Location: /SSePortal/hm/dashboard.php');
        exit;
    }

    $message = 'Invalid login credentials.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>समृद्ध शाळा ई-पोर्टल | HM Login</title>
    <style>
        :root {
            --navy: #0b1f3a;
            --royal: #2563eb;
            --orange: #f97316;
            --cyan: #06b6d4;
            --white: #ffffff;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            background:
                radial-gradient(circle at top left, rgba(6,182,212,0.22), transparent 22%),
                radial-gradient(circle at right, rgba(37,99,235,0.28), transparent 25%),
                linear-gradient(135deg, #07162c, #0a2342 55%, #102f5f);
            color: var(--white);
            display: grid;
            place-items: center;
            padding: 16px;
        }
        body::before {
            content: "";
            position: fixed;
            inset: 0;
            background-image: linear-gradient(rgba(255,255,255,0.06) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,0.06) 1px, transparent 1px);
            background-size: 26px 26px;
            pointer-events: none;
        }
        .card {
            position: relative;
            z-index: 1;
            width: min(460px, 100%);
            background: rgba(255,255,255,0.08);
            backdrop-filter: blur(14px);
            border: 1px solid rgba(255,255,255,0.18);
            border-radius: 22px;
            padding: 28px;
            box-shadow: 0 18px 50px rgba(0,0,0,0.35), inset 0 1px 0 rgba(255,255,255,0.15);
        }
        .eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 10px;
            border-radius: 999px;
            background: rgba(249, 115, 22, 0.18);
            color: #ffd8bd;
            font-size: 12px;
            letter-spacing: 0.05em;
            margin-bottom: 14px;
            text-transform: uppercase;
        }
        h2 { margin: 0 0 8px; font-size: 1.65rem; }
        .sub { margin: 0 0 20px; color: #d3e3ff; }
        form { display: grid; gap: 12px; }
        input, button {
            width: 100%;
            padding: 12px 14px;
            border-radius: 12px;
            font-size: 14px;
        }
        input {
            background: rgba(255,255,255,0.08);
            border: 1px solid rgba(255,255,255,0.18);
            color: var(--white);
        }
        input::placeholder { color: #b7c9e6; }
        button {
            border: none;
            cursor: pointer;
            background: linear-gradient(135deg, var(--royal), var(--orange));
            color: var(--white);
            font-weight: 800;
            box-shadow: 0 10px 25px rgba(249,115,22,0.35);
        }
        .note { color: #bfe2ff; font-size: 13px; margin-top: 4px; }
        .error { color: #ffb5b5; font-weight: bold; }
    </style>
</head>
<body>
    <div class="card">
        <div class="eyebrow">Samruddh Shala E-Portal</div>
        <h2>मुख्याध्यापक डॅशबोर्ड<br>Head Master Dashboard</h2>
        <p class="sub">जिल्हा परिषद शाळा • Zilla Parishad School</p>
        <p class="note">Use username: hm and password: hm1234</p>
        <?php if ($message !== '') { ?><p class="error"><?= e($message) ?></p><?php } ?>
        <form method="post">
            <input type="text" name="username" placeholder="Username" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit">Login</button>
        </form>
    </div>
</body>
</html>
