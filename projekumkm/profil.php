<?php
/**
 * profil.php
 * Halaman profil pengguna lengkap dengan update data, upload foto, dan ubah password.
 * Menggunakan prepared statements untuk keamanan.
 */

session_start();

// Redirect jika belum login
if (!isset($_SESSION['username'])) {
    header("Location: auth/login.php");
    exit;
}

// Sertakan koneksi database
include 'db.php';

// Hitung jumlah keranjang
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

// Direktori upload
$uploadBaseDir = __DIR__ . '/uploads/profile/';
$publicUploadBase = 'uploads/profile/'; 

if (!is_dir($uploadBaseDir)) {
    @mkdir($uploadBaseDir, 0755, true);
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
    HANDLE: Update profil
    ------------------------------------------------ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profil') {
    $nama            = safe_str($conn, $_POST['nama'] ?? '');
    $email           = safe_str($conn, $_POST['email'] ?? '');
    $telepon         = safe_str($conn, $_POST['telepon'] ?? '');
    $jenis_kelamin   = safe_str($conn, $_POST['jenis_kelamin'] ?? '');
    $tgl_lahir       = safe_str($conn, $_POST['tgl_lahir'] ?? '');

    $errors = [];
    if ($nama === '') $errors[] = "Nama tidak boleh kosong.";
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Email tidak valid.";
    if ($telepon === '') $errors[] = "Nomor telepon tidak boleh kosong.";

    $newFotoName = $user['foto'];
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] !== UPLOAD_ERR_NO_FILE) {
        $file = $_FILES['foto'];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = "Upload foto gagal (kode: {$file['error']}).";
        } else {
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg','jpeg','png','webp'];
            if (!in_array($ext, $allowed)) {
                $errors[] = "Format foto tidak didukung. Gunakan JPG, PNG atau WEBP.";
            } elseif ($file['size'] > 1024 * 1024 * 2) {
                $errors[] = "Ukuran foto maksimal 2MB.";
            } else {
                $newFotoName = time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
                $dest = $uploadBaseDir . $newFotoName;
                if (!move_uploaded_file($file['tmp_name'], $dest)) {
                    $errors[] = "Gagal menyimpan file foto.";
                } else {
                    if (!empty($user['foto']) && file_exists($uploadBaseDir . $user['foto'])) {
                        @unlink($uploadBaseDir . $user['foto']);
                    }
                }
            }
        }
    }

    if (empty($errors)) {
        $sql = "UPDATE tb_user SET nama = ?, email = ?, telepon = ?, jenis_kelamin = ?, tgl_lahir = ?, foto = ? WHERE id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        if ($stmt) {
            $id_user = intval($user['id']);
            mysqli_stmt_bind_param($stmt, "ssssssi", $nama, $email, $telepon, $jenis_kelamin, $tgl_lahir, $newFotoName, $id_user);
            if (mysqli_stmt_execute($stmt)) {
                set_flash('success', 'Profil disimpan', 'Perubahan profil berhasil disimpan.');
                mysqli_stmt_close($stmt);
                header("Location: profil.php?updated=1");
                exit;
            } else {
                $dbErr = mysqli_stmt_error($stmt);
                mysqli_stmt_close($stmt);
                if ($newFotoName !== $user['foto'] && file_exists($uploadBaseDir . $newFotoName)) {
                    @unlink($uploadBaseDir . $newFotoName);
                }
                set_flash('error', 'Gagal menyimpan', 'Error database: ' . $dbErr);
            }
        } else {
            set_flash('error', 'Gagal menyimpan', 'Kesalahan pada query update.');
        }
    } else {
        set_flash('error', 'Validasi gagal', implode("<br/>", $errors));
    }

    header("Location: profil.php");
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
    header("Location: profil.php");
    exit;
}

$flash = get_flash();

// Refresh data user setelah update
$stmt = mysqli_prepare($conn, "SELECT id, username, nama, email, telepon, jenis_kelamin, tgl_lahir, foto FROM tb_user WHERE username = ? LIMIT 1");
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "s", $username);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($res);
    mysqli_stmt_close($stmt);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1"/>
    <title>Profil Saya - MinangMaknyus</title>

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
            --shopee-orange: #ee4d2d;
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
        .profile-photo-button {
            background-color: #fff;
            border: 1px solid #ddd;
            color: #555;
            padding: 8px 16px;
            border-radius: 50px;
            cursor: pointer;
            font-size: 14px;
            margin-top: 12px;
            transition: all 0.2s ease;
        }
        .profile-photo-button:hover {
            background-color: var(--primary-color-red);
            color: white;
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
                        <div class="profile-photo-preview mb-3">
                            <img src="<?= (!empty($user['foto']) && file_exists($uploadBaseDir . $user['foto'])) ? htmlspecialchars($publicUploadBase . $user['foto']) : 'assets/img/profil.png' ?>" alt="avatar" class="w-100 h-100 object-fit-cover">
                        </div>
                        <div class="fw-bold mb-1"><?= htmlspecialchars($user['nama'] ?? $user['username']) ?></div>
                        <a href="profil.php" class="text-sm text-decoration-none text-muted">
                            <i class="fa-regular fa-pen-to-square me-1"></i>Ubah Profil
                        </a>
                    </div>
                    <nav class="nav flex-column">
                        <h6 class="text-uppercase text-muted fw-bold mb-2">Akun Saya</h6>
                        <a href="profil.php" class="nav-link active">
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
                        <a href="ubah_password.php" class="nav-link">
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
                    <h4 class="fw-bold mb-1">Profil Saya</h4>
                    <p class="text-muted mb-4">Kelola informasi Anda untuk mengontrol, melindungi, dan mengamankan akun.</p>
                    
                    <form method="post" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="update_profil" />
                        
                        <div class="row g-4 mb-4">
                            <div class="col-md-8">
                                <div class="mb-3 row align-items-center">
                                    <label class="col-sm-4 col-form-label text-md-end text-muted">Username</label>
                                    <div class="col-sm-8">
                                        <input type="text" class="form-control-plaintext" value="<?= htmlspecialchars($user['username']) ?>" readonly>
                                    </div>
                                </div>
                                <div class="mb-3 row align-items-center">
                                    <label for="nama" class="col-sm-4 col-form-label text-md-end">Nama</label>
                                    <div class="col-sm-8">
                                        <input type="text" name="nama" id="nama" class="form-control" value="<?= htmlspecialchars($user['nama'] ?? '') ?>" required>
                                    </div>
                                </div>
                                <div class="mb-3 row align-items-center">
                                    <label for="email" class="col-sm-4 col-form-label text-md-end">Email</label>
                                    <div class="col-sm-8">
                                        <div class="input-group">
                                            <input type="email" name="email" id="email" class="form-control" value="<?= htmlspecialchars($user['email'] ?? '') ?>" required>
                                            <a href="#" class="btn btn-outline-danger">Ubah</a>
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-3 row align-items-center">
                                    <label for="telepon" class="col-sm-4 col-form-label text-md-end">Nomor Telepon</label>
                                    <div class="col-sm-8">
                                        <div class="input-group">
                                            <input type="text" name="telepon" id="telepon" class="form-control" value="<?= htmlspecialchars($user['telepon'] ?? '') ?>" required>
                                            <a href="#" class="btn btn-outline-danger">Ubah</a>
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-3 row align-items-center">
                                    <label class="col-sm-4 col-form-label text-md-end">Jenis Kelamin</label>
                                    <div class="col-sm-8">
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="jenis_kelamin" id="laki" value="Laki-laki" <?= (isset($user['jenis_kelamin']) && $user['jenis_kelamin']==='Laki-laki') ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="laki">Laki-laki</label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="jenis_kelamin" id="perempuan" value="Perempuan" <?= (isset($user['jenis_kelamin']) && $user['jenis_kelamin']==='Perempuan') ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="perempuan">Perempuan</label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="jenis_kelamin" id="lainnya" value="Lainnya" <?= (isset($user['jenis_kelamin']) && $user['jenis_kelamin']==='Lainnya') ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="lainnya">Lainnya</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-3 row align-items-center">
                                    <label for="tgl_lahir" class="col-sm-4 col-form-label text-md-end">Tanggal Lahir</label>
                                    <div class="col-sm-8">
                                        <input type="date" name="tgl_lahir" id="tgl_lahir" class="form-control" value="<?= htmlspecialchars($user['tgl_lahir'] ?? '') ?>">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-4 d-flex flex-column align-items-center border-start py-5">
                                <div class="profile-photo-container">
                                    <div class="profile-photo-preview">
                                        <img id="previewImg" src="<?= (!empty($user['foto']) && file_exists($uploadBaseDir . $user['foto'])) ? htmlspecialchars($publicUploadBase . $user['foto']) : 'assets/img/profil.png' ?>" alt="preview" class="w-100 h-100 object-fit-cover">
                                    </div>
                                    <button type="button" onclick="document.getElementById('fotoInput').click()" class="profile-photo-button">Pilih Gambar</button>
                                    <input type="file" name="foto" id="fotoInput" accept="image/*" class="d-none">
                                    <div class="text-sm text-muted mt-2">
                                        <p>Ukuran gambar maks. 2MB</p>
                                        <p>Format: JPG, PNG, WEBP</p>
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
        const fotoInput = document.getElementById('fotoInput');
        const previewImg = document.getElementById('previewImg');
        
        fotoInput?.addEventListener('change', function(e){
            const f = e.target.files[0];
            if (!f) return;
            if (!f.type.startsWith('image/')) {
                Swal.fire({icon:'error', title:'File tidak valid', text:'Pilih file gambar.'});
                fotoInput.value = '';
                return;
            }
            const reader = new FileReader();
            reader.onload = function(ev){
                previewImg.src = ev.target.result;
            };
            reader.readAsDataURL(f);
        });

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

    </script>
</body>
</html>