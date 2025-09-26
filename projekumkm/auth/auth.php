<?php
include '../db.php';
session_start();

// Jika sudah login, arahkan sesuai role
if (isset($_SESSION['login'])) {
    if ($_SESSION['role'] == 'admin') {
        header("Location: ../admin/dashboard.php");
    } elseif ($_SESSION['role'] == 'pelanggan') {
        header("Location: ../index.php");
    }
    exit;
}

$error = '';
$success = '';
$is_register_form = false; // Flag untuk menentukan form mana yang ditampilkan saat ada pesan error/success

// Proses form login
if (isset($_POST['login_submit'])) {
    $is_register_form = false;
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = mysqli_prepare($conn, "SELECT id, username, password, role FROM tb_user WHERE username = ? LIMIT 1");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "s", $username);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $data = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        if ($data && password_verify($password, $data['password'])) {
            $_SESSION['login'] = true;
            $_SESSION['id'] = $data['id'];
            $_SESSION['username'] = $data['username'];
            $_SESSION['role'] = $data['role'];

            if ($data['role'] === 'admin') {
                header("Location: ../admin/dashboard.php");
            } else {
                header("Location: ../index.php");
            }
            exit;
        } else {
            $error = '⚠️ Username atau password salah.';
        }
    } else {
        $error = '⚠️ Terjadi kesalahan pada server. Silakan coba lagi.';
    }
}

// Proses form registrasi
if (isset($_POST['register_submit'])) {
    $is_register_form = true;
    $nama = htmlspecialchars(trim($_POST['nama'] ?? ''));
    $username = htmlspecialchars(trim($_POST['username'] ?? ''));
    $password = $_POST['password'] ?? '';
    $email = htmlspecialchars(trim($_POST['email'] ?? ''));

    if (empty($nama) || empty($username) || empty($email) || empty($password)) {
        $error = '⚠️ Nama, Username, Email, dan Password tidak boleh kosong.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = '⚠️ Format email tidak valid.';
    } elseif (strlen($password) < 6) {
        $error = '⚠️ Password minimal 6 karakter.';
    } else {
        $stmt_check = mysqli_prepare($conn, "SELECT id FROM tb_user WHERE username = ? OR email = ?");
        mysqli_stmt_bind_param($stmt_check, "ss", $username, $email);
        mysqli_stmt_execute($stmt_check);
        mysqli_stmt_store_result($stmt_check);

        if (mysqli_stmt_num_rows($stmt_check) > 0) {
            $error = '⚠️ Username atau Email sudah digunakan. Silakan pilih yang lain.';
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $role = 'pelanggan';
            $stmt_insert = mysqli_prepare($conn, "INSERT INTO tb_user (nama, username, email, password, role) VALUES (?, ?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt_insert, "sssss", $nama, $username, $email, $hashed_password, $role);

            if ($stmt_insert->execute()) {
                $success = "✅ Registrasi berhasil! Silakan masuk.";
            } else {
                $error = '⚠️ Registrasi gagal. Silakan coba lagi.';
            }
        }
        mysqli_stmt_close($stmt_check);
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet" />
    <title>MinangMaknyus | Masuk & Daftar</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&display=swap');

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Montserrat', sans-serif; }
        body {
            background: linear-gradient(to right, #e2e2e2, #c9d6ff);
            display: flex; align-items: center; justify-content: center;
            flex-direction: column; height: 100vh;
        }
        .container {
            background: #fff; border-radius: 30px; box-shadow: 0 5px 15px rgba(0, 0, 0, 0.35);
            position: relative; overflow: hidden; width: 768px; max-width: 100%; min-height: 480px;
        }
        .container p { font-size: 14px; line-height: 20px; margin: 20px 0; }
        .container a { color: #333; font-size: 13px; text-decoration: none; margin: 15px 0 10px; }
        .container button {
            background: #512da8; color: #fff; font-size: 12px;
            padding: 10px 45px; border: 1px solid transparent; border-radius: 8px;
            font-weight: 600; text-transform: uppercase; margin-top: 10px; cursor: pointer;
        }
        .container button.hidden { background: transparent; border-color: #fff; }
        .container form {
            background: #fff; display: flex; align-items: center; justify-content: center;
            flex-direction: column; padding: 0 40px; height: 100%;
        }
        .container input {
            background: #eee; border: none; margin: 8px 0; padding: 10px 15px;
            font-size: 13px; border-radius: 8px; width: 100%; outline: none;
        }
        .form-container { position: absolute; top: 0; height: 100%; transition: all 0.6s ease-in-out; }
        .sign-in { left: 0; width: 50%; z-index: 2; }
        .container.active .sign-in { transform: translateX(100%); }
        .sign-up { left: 0; width: 50%; opacity: 0; z-index: 1; }
        .container.active .sign-up {
            transform: translateX(100%); opacity: 1; z-index: 5; animation: move 0.6s;
        }
        @keyframes move {
            0%, 49.99% { opacity: 0; z-index: 1; }
            50%, 100% { opacity: 1; z-index: 5; }
        }
        .toggle-container {
            position: absolute; top: 0; left: 50%; width: 50%; height: 100%;
            overflow: hidden; transition: all 0.6s ease-in-out;
            border-radius: 150px 0 0 100px; z-index: 1000;
        }
        .container.active .toggle-container {
            transform: translateX(-100%); border-radius: 0 150px 100px 0;
        }
        .toggle {
            color: #fff; position: relative; left: -100%; width: 200%;
            height: 100%; transform: translateX(0); transition: all 0.6s ease-in-out;
        }
        .container.active .toggle { transform: translateX(50%); }
        .toggle-panel {
            position: absolute; width: 50%; height: 100%;
            display: flex; align-items: center; justify-content: center;
            flex-direction: column; padding: 0 30px; text-align: center;
            top: 0; transition: all 0.6s ease-in-out;
        }
        .toggle-left { transform: translateX(-200%); }
        .container.active .toggle-left { transform: translateX(0); }
        .toggle-right { right: 0; transform: translateX(0); }
        .container.active .toggle-right { transform: translateX(200%); }

        .error { color: #d32f2f; font-weight: bold; margin-bottom: 16px; text-align: center; }
        .success { color: #198754; font-weight: bold; margin-bottom: 16px; text-align: center; }

        /* Logo kecil seukuran teks Masuk/Daftar */
        .logo { margin-bottom: 20px; text-align: center; }
        .logo h1 {
            font-family: 'Poppins', sans-serif; font-weight: 700;
            font-size: 2rem; margin: 0; line-height: 1.2;
            text-shadow: 2px 2px 6px rgba(0,0,0,0.15);
        }
        .logo .minang { color: #e63946; }
        .logo .maknyus { color: #f6ad02; font-style: italic; }

        @media (max-width: 768px) { .logo h1 { font-size: 1.8rem; } }

        .form-container.sign-in button { background: #FFC107; }
        .form-container.sign-up button { background: #dc3545; }
        .toggle-panel.toggle-right { background: linear-gradient(to right, #FFC107, #FF9800); }
        .toggle-panel.toggle-left { background: linear-gradient(to right, #dc3545, #a52d3a); }
        .toggle-panel button { background: transparent; border-color: #fff; color: #fff; }
        .toggle {
            background: linear-gradient(to right, #dc3545, #a52d3a);
            transition: transform 0.6s ease-in-out;
        }
        .container.active .toggle {
            background: linear-gradient(to right, #FFC107, #FF9800);
            transform: translateX(50%);
        }
    </style>
</head>
<body class="<?= $is_register_form ? 'active' : '' ?>">
    <div class="container" id="container">
        <div class="form-container sign-up">
            <form action="" method="POST">
                <div class="logo">
                    <h1><span class="minang">Minang</span><span class="maknyus">Maknyus</span></h1>
                </div>
                <h1>Daftar Akun</h1>
                <?php if (isset($_POST['register_submit'])): ?>
                    <?php if (!empty($error)): ?>
                        <div class="error"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>
                    <?php if (!empty($success)): ?>
                        <div class="success"><?= htmlspecialchars($success) ?></div>
                    <?php endif; ?>
                <?php endif; ?>
                <input type="text" placeholder="Nama Lengkap" name="nama" required>
                <input type="text" placeholder="Username" name="username" required>
                <input type="email" placeholder="Email" name="email" required>
                <input type="password" placeholder="Password" name="password" required>
                <button type="submit" name="register_submit">Daftar</button>
            </form>
        </div>

        <div class="form-container sign-in">
            <form action="" method="POST">
                <div class="logo">
                    <h1><span class="minang">Minang</span><span class="maknyus">Maknyus</span></h1>
                </div>
                <h1>Masuk</h1>
                <?php if (isset($_POST['login_submit'])): ?>
                    <?php if (!empty($error)): ?>
                        <div class="error"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>
                    <?php if (!empty($success)): ?>
                        <div class="success"><?= htmlspecialchars($success) ?></div>
                    <?php endif; ?>
                <?php endif; ?>
                <input type="text" placeholder="Username" name="username" required>
                <input type="password" placeholder="Password" name="password" required>
                <a href="#">Lupa Password?</a>
                <button type="submit" name="login_submit">Masuk</button>
            </form>
        </div>

        <div class="toggle-container">
            <div class="toggle">
                <div class="toggle-panel toggle-left">
                    <h1>Selamat Datang Kembali!</h1>
                    <p>Silahkan masuk untuk menikmati kelezatan otentik dari MinangMaknyus</p>
                    <button class="hidden" id="login">Masuk</button>
                </div>
                <div class="toggle-panel toggle-right">
                    <h1>Halo, Pelanggan!</h1>
                    <p>Daftar sekarang untuk mendapatkan diskon dan promo menarik dari MinangMaknyus</p>
                    <button class="hidden" id="register">Daftar</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        const container = document.getElementById('container');
        const registerBtn = document.getElementById('register');
        const loginBtn = document.getElementById('login');

        registerBtn.addEventListener('click', () => container.classList.add("active"));
        loginBtn.addEventListener('click', () => container.classList.remove("active"));
        
        // Menambahkan kelas 'active' pada body jika registrasi gagal atau berhasil
        <?php if ($is_register_form): ?>
            document.getElementById('container').classList.add('active');
        <?php endif; ?>
    </script>
</body>
</html>