<?php
session_start();

// Jika sudah login
if (isset($_SESSION['login']) && $_SESSION['role'] === 'admin') {
    header("Location: dashboard.php");
    exit;
}

$error = '';
$username_input = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $username_input = htmlspecialchars($username);

    // Hardcode login
    if ($username === 'admin' && $password === '123456') {
        $_SESSION['login'] = true;
        $_SESSION['id'] = 1;
        $_SESSION['username'] = 'admin';
        $_SESSION['role'] = 'admin';

        header("Location: dashboard.php");
        exit;
    } else {
        $error = '⚠️ Username atau password salah.';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Admin Login ┃ MinangMaknyus</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet" />
    <style>
        :root { --main: #2d3748; --accent: #4a5568; --highlight: #4299e1; }
        * { box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        body {
            margin: 0; padding: 20px; min-height: 100vh;
            display: flex; align-items: center; justify-content: center;
            background-color: var(--main);
            color: #fff;
        }
        .login-box {
            background: var(--accent); border-radius: 12px; padding: 40px;
            width: 100%; max-width: 400px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.3);
            text-align: center;
        }
        .logo { width: 80px; height: 80px; margin-bottom: 20px; }
        h1 { margin: 0; font-size: 2rem; font-weight: 700; }
        .subtitle { font-size: 1rem; color: #cbd5e0; margin: 8px 0 24px; }
        .form-group { margin-bottom: 15px; }
        input[type="text"], input[type="password"] {
            width: 100%; padding: 12px 14px;
            border: 2px solid #4a5568; border-radius: 8px; font-size: 15px;
            background-color: #2d3748; color: #fff;
        }
        input:focus { border-color: var(--highlight); outline: none; }
        button {
            width: 100%; padding: 14px 0; margin-top: 10px;
            background-color: var(--highlight); color: #fff;
            border: none; border-radius: 8px; font-size: 16px; font-weight: 600;
            cursor: pointer; transition: background-color 0.2s;
        }
        button:hover { background-color: #3182ce; }
        .error { color: #f56565; font-weight: 500; margin-bottom: 15px; }
    </style>
</head>
<body>
    <div class="login-box">
        <img src="../assets/img/logo.png" alt="MinangMaknyus Logo" class="logo" />
        <h1>Admin Login</h1>
        <div class="subtitle">Masuk ke Panel Admin</div>

        <?php if (!empty($error)) : ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="post" autocomplete="off">
            <div class="form-group">
                <input type="text" name="username" placeholder="Username" value="<?= $username_input ?>" required />
            </div>
            <div class="form-group">
                <input type="password" name="password" placeholder="Password" required />
            </div>
            <button type="submit">Login</button>
        </form>
    </div>
</body>
</html>
