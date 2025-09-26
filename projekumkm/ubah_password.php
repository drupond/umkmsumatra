<?php
/**
 * ubah_password.php
 * Halaman untuk mengubah password pengguna.
 * Menggunakan prepared statements dan password_hash untuk keamanan.
 */

session_start();

// Redirect jika belum login
if (!isset($_SESSION['username'])) {
    header("Location: auth/login.php");
    exit;
}

// Sertakan koneksi database
include 'db.php';

// Hitung jumlah keranjang untuk navbar
$cart_count = 0;
if (isset($_SESSION['keranjang'])) {
    foreach ($_SESSION['keranjang'] as $item) {
        $cart_count += $item['qty'];
    }
}

// Helper: sanitize input
function safe_str($conn, $v) {
    return mysqli_real_escape_string($conn, trim($v));
}

// Helper: Flash message untuk notifikasi
function set_flash($type, $title, $text = '') {
    $_SESSION['flash'] = [
        'type' => $type, // success|error|info|warning
        'title' => $title,
        'text' => $text
    ];
}

function get_flash() {
    if (!empty($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $f;
    }
    return null;
}

// Ambil data user dari database
$username = $_SESSION['username'];
$user = null;
$stmt = mysqli_prepare($conn, "SELECT id, username, nama, email, telepon, jenis_kelamin, tgl_lahir, foto FROM tb_user WHERE username = ? LIMIT 1");
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "s", $username);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($res);
    mysqli_stmt_close($stmt);
}

if (!$user) {
    session_destroy();
    header("Location: auth/login.php?msg=user_not_found");
    exit;
}

/* ------------------------------------------------
    HANDLE: Ubah password
    ------------------------------------------------ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_password') {
    $current = $_POST['current_password'] ?? '';
    $new = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    $errors = [];
    if (empty($new) || strlen($new) < 6) {
        $errors[] = "Password baru minimal 6 karakter.";
    }
    if ($new !== $confirm) {
        $errors[] = "Konfirmasi password tidak cocok.";
    }

    $stmt = mysqli_prepare($conn, "SELECT password FROM tb_user WHERE id = ? LIMIT 1");
    if ($stmt) {
        $id_user = intval($user['id']);
        mysqli_stmt_bind_param($stmt, "i", $id_user);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($res);
        mysqli_stmt_close($stmt);
        if ($row && !password_verify($current, $row['password'])) {
            $errors[] = "Password lama salah.";
        }
    } else {
        $errors[] = "Query verifikasi password gagal.";
    }

    if (empty($errors)) {
        $newHash = password_hash($new, PASSWORD_DEFAULT);
        $stmt = mysqli_prepare($conn, "UPDATE tb_user SET password = ? WHERE id = ?");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "si", $newHash, $id_user);
            if (mysqli_stmt_execute($stmt)) {
                set_flash('success', 'Password diubah', 'Password berhasil diperbarui.');
            } else {
                set_flash('error', 'Gagal', 'Gagal menyimpan password baru.');
            }
            mysqli_stmt_close($stmt);
        } else {
            set_flash('error', 'Gagal', 'Prepare query gagal.');
        }
    } else {
        set_flash('error', 'Gagal', implode("<br/>", $errors));
    }
    header("Location: ubah_password.php");
    exit;
}

$flash = get_flash();
$uploadBaseDir = __DIR__ . '/uploads/profile/'; // Definisikan di sini untuk menghindari error
$publicUploadBase = 'uploads/profile/'; // Definisikan di sini
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1"/>
    <title>Ubah Password - MinangMaknyus</title>

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet"/>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        :root {
            --primary-color-red: #dc3545;
            --primary-color-yellow: #ffc107;
            --light-color: #f8f9fa;
            --text-dark: #212529;
            --text-muted: #6c757d;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        body { background-color: var(--light-color); color: var(--text-dark); line-height: 1.6; padding-top: 72px; }
        .container { max-width: 1200px; margin: 0 auto; padding: 0 20px; }
        
        /* --- Header & Navigasi --- */
        .navbar-custom {
            background-color: #fff;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
        }
        .navbar-brand h1 {
            color: var(--primary-color-red);
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 0;
        }
        .navbar-brand .maknyus {
            color: var(--primary-color-yellow);
            font-style: italic;
        }
        .nav-link.active {
            color: var(--primary-color-red) !important;
            font-weight: 600;
        }
        .nav-link:hover {
            color: var(--primary-color-red) !important;
        }
        #cart-count { 
            font-size: 0.75rem; 
            top: -5px; 
            left: 90%; 
            padding: 3px 6px;
        }
        .navbar .dropdown-menu {
            border: 1px solid rgba(0, 0, 0, 0.15);
            border-radius: 8px;
        }
        .navbar .dropdown-item:hover {
            background-color: var(--light-color);
            color: var(--primary-color-red);
        }

        /* --- Konten Profil (Desain Elegan) --- */
        .profile-container {
            background-color: #fff;
            padding: 3rem;
            border-radius: 1rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
        }
        .profile-sidebar {
            border-right: 1px solid #eee;
        }
        @media (max-width: 768px) {
            .profile-sidebar {
                border-right: none;
                border-bottom: 1px solid #eee;
            }
        }

        .profile-photo-preview {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            border: 3px solid #eee;
            overflow: hidden;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }
        .profile-photo-preview:hover {
            border-color: var(--primary-color-red);
        }
        .btn-accent { 
            background-color: var(--primary-color-red);
            color: white;
            padding: 0.75rem 2.5rem;
            border-radius: 50px;
            transition: background-color 0.2s;
            border: none;
            font-weight: 600;
        }
        .btn-accent:hover { 
            background-color: #a52d3a; 
        }

        /* --- Sidebar & Navigasi Samping --- */
        .profile-sidebar .nav-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 15px;
            color: var(--text-dark);
            font-weight: 500;
            text-decoration: none;
            transition: color 0.2s, background-color 0.2s;
            border-radius: 8px;
        }
        .profile-sidebar .nav-link:hover {
            color: var(--primary-color-red);
            background-color: var(--light-color);
        }
        .profile-sidebar .nav-link.active {
            color: var(--primary-color-red);
            background-color: rgba(220, 53, 69, 0.1);
            font-weight: 600;
        }
        .profile-sidebar .nav-link.active i {
            color: var(--primary-color-red);
        }
        .profile-sidebar .nav-link i {
            width: 20px;
            text-align: center;
            font-size: 1.1rem;
            color: var(--text-muted);
        }

        /* --- Footer --- */
        footer { background-color: #fff; padding: 40px 0 20px; text-align: center; border-top: 1px solid rgba(0, 0, 0, 0.1); color: var(--text-dark); }
        .footer-social-links a { color: var(--text-dark); font-size: 1.5rem; margin: 0 10px; transition: color 0.3s; }
        .footer-social-links a:hover { color: var(--primary-color-red); }

        /* --- Responsif Navbar --- */
        @media (max-width: 991.98px) {
            .navbar-collapse {
                position: fixed;
                top: 0;
                bottom: 0;
                left: 0;
                z-index: 1050;
                width: 75%;
                background-color: #fff;
                padding: 20px;
                box-shadow: 2px 0 10px rgba(0,0,0,0.1);
                transform: translateX(-100%);
                transition: transform 0.3s ease-in-out;
            }
            .offcanvas.show {
                transform: translateX(0);
            }
            .navbar-nav {
                flex-direction: column;
            }
            .nav-item {
                border-bottom: 1px solid #eee;
            }
            .nav-item:last-child {
                border-bottom: none;
            }
            .d-flex.align-items-center {
                flex-direction: column;
                align-items: flex-start !important;
                gap: 15px;
                margin-top: 20px;
            }
            .d-flex.align-items-center > a, .dropdown {
                width: 100%;
            }
            .dropdown-menu {
                position: relative !important;
                transform: none !important;
                border: none;
                box-shadow: none;
            }
            .dropdown-item {
                padding-left: 0;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light fixed-top navbar-custom">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <h1>Minang<span class="maknyus">Maknyus</span></h1>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasNavbar" aria-controls="offcanvasNavbar">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="offcanvas offcanvas-start" tabindex="-1" id="offcanvasNavbar" aria-labelledby="offcanvasNavbarLabel">
                <div class="offcanvas-header">
                    <h5 class="offcanvas-title" id="offcanvasNavbarLabel">Minang<span class="text-warning">Maknyus</span></h5>
                    <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
                </div>
                <div class="offcanvas-body">
                    <ul class="navbar-nav justify-content-center flex-grow-1 pe-3">
                        <li class="nav-item">
                            <a class="nav-link" href="index.php">Beranda</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="index.php#produk">Produk</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="index.php#testimoni">Testimoni</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="index.php#faq">FAQ</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="index.php#about-us">Tentang Kami</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="index.php#kontak">Hubungi Kami</a>
                        </li>
                    </ul>
                    <div class="d-flex align-items-center">
                        <a href="#" class="btn btn-sm btn-light position-relative me-2 d-none d-lg-block">
                            <i class="fas fa-heart text-danger"></i>
                        </a>
                        <a href="<?= isset($_SESSION['login']) ? 'keranjang.php' : 'auth/auth.php' ?>" class="btn btn-sm btn-light position-relative me-3 d-none d-lg-block">
                            <i class="fas fa-shopping-cart text-dark"></i>
                            <span id="cart-count" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                <?= $cart_count ?>
                            </span>
                        </a>
                        <?php if (isset($_SESSION['username'])): ?>
                            <div class="dropdown">
                                <a href="#" class="d-flex align-items-center text-dark text-decoration-none dropdown-toggle" 
                                   id="dropdownUser" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fas fa-user-circle fs-4 me-2"></i>
                                    <span class="fw-semibold d-none d-lg-inline"><?= htmlspecialchars($_SESSION['username']) ?></span>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="dropdownUser">
                                    <li><a class="dropdown-item" href="profil.php"><i class="fas fa-user me-2"></i> Akun Saya</a></li>
                                    <li><a class="dropdown-item" href="pesanan.php"><i class="fas fa-box-open me-2"></i> Pesanan Saya</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item text-danger" href="auth/logout.php"><i class="fas fa-sign-out-alt me-2"></i> Log Out</a></li>
                                </ul>
                            </div>
                        <?php else: ?>
                            <a href="auth/auth.php" class="btn btn-outline-danger me-2">Masuk</a>
                            <a href="auth/auth.php" class="btn btn-danger">Daftar</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <main class="container py-5">
        <div class="row g-4 profile-container">
            <aside class="col-md-3 profile-sidebar">
                <div class="p-3">
                    <div class="d-flex align-items-center flex-column text-center mb-4">
                        <div class="profile-photo-preview mb-3" style="width: 100px; height: 100px;">
                            <img src="<?= (!empty($user['foto']) && file_exists($uploadBaseDir . $user['foto'])) ? htmlspecialchars($publicUploadBase . $user['foto']) : 'assets/img/profil.png' ?>" alt="avatar" class="w-100 h-100 object-fit-cover">
                        </div>
                        <div class="fw-bold mb-1"><?= htmlspecialchars($user['nama'] ?? $user['username']) ?></div>
                        <a href="profil.php" class="text-sm text-decoration-none text-muted">
                            <i class="fa-regular fa-pen-to-square me-1"></i>Ubah Profil
                        </a>
                    </div>
                    <nav class="nav flex-column">
                        <h6 class="text-uppercase text-muted fw-bold mb-2">Akun Saya</h6>
                        <a href="profil.php" class="nav-link">
                            <i class="fa-regular fa-user"></i>
                            <span>Profil</span>
                        </a>
                        <a href="pesanan.php" class="nav-link">
                            <i class="fa-solid fa-box-open"></i>
                            <span>Pesanan Saya</span>
                        </a>
                        <a href="alamat.php" class="nav-link">
                            <i class="fa-regular fa-map"></i>
                            <span>Alamat</span>
                        </a>
                        <a href="ubah_password.php" class="nav-link active">
                            <i class="fa-solid fa-lock"></i>
                            <span>Ubah Password</span>
                        </a>
                        <a href="auth/logout.php" class="nav-link text-danger">
                            <i class="fa-solid fa-arrow-right-from-bracket"></i>
                            <span>Keluar</span>
                        </a>
                    </nav>
                </div>
            </aside>

            <section class="col-md-9">
                <div class="p-4">
                    <h4 class="fw-bold mb-1">Ubah Password</h4>
                    <p class="text-muted mb-4">Untuk keamanan akun Anda, mohon jangan bagikan password Anda kepada siapapun.</p>
                    
                    <form method="post" class="needs-validation" novalidate>
                        <input type="hidden" name="action" value="change_password" />
                        
                        <div class="row g-4 mb-4">
                            <div class="col-12">
                                <div class="mb-3 row align-items-center">
                                    <label for="current_password" class="col-sm-4 col-form-label text-md-end">Password Lama</label>
                                    <div class="col-sm-8">
                                        <input type="password" name="current_password" id="current_password" class="form-control" required>
                                        <div class="invalid-feedback">
                                            Mohon masukkan password lama Anda.
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-3 row align-items-center">
                                    <label for="new_password" class="col-sm-4 col-form-label text-md-end">Password Baru</label>
                                    <div class="col-sm-8">
                                        <input type="password" name="new_password" id="new_password" class="form-control" required minlength="6">
                                        <div class="invalid-feedback">
                                            Password baru minimal 6 karakter.
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-4 row align-items-center">
                                    <label for="confirm_password" class="col-sm-4 col-form-label text-md-end">Konfirmasi Password Baru</label>
                                    <div class="col-sm-8">
                                        <input type="password" name="confirm_password" id="confirm_password" class="form-control" required minlength="6">
                                        <div class="invalid-feedback">
                                            Konfirmasi password harus cocok dengan password baru.
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="text-center text-md-start">
                            <button type="submit" class="btn btn-accent">Simpan</button>
                        </div>
                    </form>
                </div>
            </section>
        </div>
    </main>

    <footer>
        <div class="container">
            <div class="footer-social-links mb-2">
                <a href="#"><i class="fab fa-facebook me-3"></i></a>
                <a href="#"><i class="fab fa-instagram me-3"></i></a>
                <a href="#"><i class="fab fa-twitter"></i></a>
            </div>
            <p class="mb-0">© <?= date('Y') ?> MinangMaknyus. Semua Hak Dilindungi.</p>
        </div>
    </footer>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        <?php if ($flash): ?>
        Swal.fire({
            icon: '<?= $flash['type'] === 'error' ? 'error' : ($flash['type'] === 'success' ? 'success' : 'info') ?>',
            title: '<?= addslashes($flash['title']) ?>',
            html: '<?= addslashes($flash['text']) ?>',
            toast: true,
            position: 'top-end',
            timer: 2500,
            showConfirmButton: false
        });
        <?php endif; ?>

        (function () {
            'use strict'
            const form = document.querySelector('form')
            if (form) {
                form.addEventListener('submit', function (event) {
                    if (!form.checkValidity()) {
                        event.preventDefault()
                        event.stopPropagation()
                    }
                    form.classList.add('was-validated')
                }, false)
            }
        })()

        const newPasswordInput = document.getElementById('new_password');
        const confirmPasswordInput = document.getElementById('confirm_password');

        if (newPasswordInput && confirmPasswordInput) {
            function validatePasswords(){
                if (newPasswordInput.value !== confirmPasswordInput.value) {
                    confirmPasswordInput.setCustomValidity('Konfirmasi password tidak cocok');
                } else {
                    confirmPasswordInput.setCustomValidity('');
                }
            }

            newPasswordInput.addEventListener('change', validatePasswords);
            confirmPasswordInput.addEventListener('keyup', validatePasswords);
        }
    </script>
</body>
</html>