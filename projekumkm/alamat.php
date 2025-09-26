<?php
/**
 * alamat.php
 * Halaman untuk mengelola alamat pengguna.
 * Diperbaiki dengan prepared statements di semua operasi.
 */

session_start();

// Redirect jika belum login
if (!isset($_SESSION['username'])) {
    header("Location: auth/login.php");
    exit;
}

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

// Flash messages
function set_flash($type, $title, $text = '') {
    $_SESSION['flash'] = ['type' => $type, 'title' => $title, 'text' => $text];
}

function get_flash() {
    if (!empty($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $f;
    }
    return null;
}

// Direktori foto profil
$uploadBaseDir = __DIR__ . '/uploads/profile/';
$publicUploadBase = 'uploads/profile/'; 

// Ambil data user
$username = $_SESSION['username'];
$stmt = $conn->prepare("SELECT id, nama, username, foto FROM tb_user WHERE username = ? LIMIT 1");
$stmt->bind_param("s", $username);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    session_destroy();
    header("Location: auth/login.php?msg=user_not_found");
    exit;
}
$id_user = $user['id'];

// Tangani aksi (tambah, ubah, hapus, atur utama)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'tambah' || $action === 'ubah') {
        $nama_penerima = safe_str($conn, $_POST['nama_penerima']);
        $telepon = safe_str($conn, $_POST['telepon']);
        $alamat_lengkap = safe_str($conn, $_POST['alamat_lengkap']);
        $provinsi_id = intval($_POST['provinsi']);
        $regency_id = intval($_POST['regency']);
        $district_id = intval($_POST['district']);
        $kode_pos = safe_str($conn, $_POST['kode_pos']);
        $detail_lainnya = safe_str($conn, $_POST['detail_lainnya']);
        $jenis_alamat = safe_str($conn, $_POST['jenis_alamat']);
        $is_utama = isset($_POST['utama']) ? 1 : 0;

        // Jika alamat baru diatur sebagai utama, set alamat lain menjadi tidak utama
        if ($is_utama) {
            $stmt = $conn->prepare("UPDATE tb_alamat SET utama = 0 WHERE id_user = ?");
            $stmt->bind_param("i", $id_user);
            $stmt->execute();
            $stmt->close();
        }

        if ($action === 'tambah') {
            $stmt = $conn->prepare("INSERT INTO tb_alamat (id_user, nama_penerima, telepon, alamat_lengkap, provinsi_id, regency_id, district_id, kode_pos, detail_lainnya, jenis_alamat, utama) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("isssiiisssi", $id_user, $nama_penerima, $telepon, $alamat_lengkap, $provinsi_id, $regency_id, $district_id, $kode_pos, $detail_lainnya, $jenis_alamat, $is_utama);
            if ($stmt->execute()) {
                set_flash('success', 'Berhasil', 'Alamat berhasil ditambahkan.');
            } else {
                set_flash('error', 'Gagal', 'Terjadi kesalahan saat menambahkan alamat.');
            }
            $stmt->close();
        } else { // ubah
            $id_alamat = intval($_POST['id_alamat']);
            $stmt = $conn->prepare("UPDATE tb_alamat SET nama_penerima = ?, telepon = ?, alamat_lengkap = ?, provinsi_id = ?, regency_id = ?, district_id = ?, kode_pos = ?, detail_lainnya = ?, jenis_alamat = ?, utama = ? WHERE id = ? AND id_user = ?");
            $stmt->bind_param("sssiissssiis", $nama_penerima, $telepon, $alamat_lengkap, $provinsi_id, $regency_id, $district_id, $kode_pos, $detail_lainnya, $jenis_alamat, $is_utama, $id_alamat, $id_user);
            if ($stmt->execute()) {
                set_flash('success', 'Berhasil', 'Alamat berhasil diubah.');
            } else {
                set_flash('error', 'Gagal', 'Terjadi kesalahan saat mengubah alamat.');
            }
            $stmt->close();
        }

    } elseif ($action === 'hapus') {
        $id_alamat = intval($_POST['id_alamat']);
        $stmt = $conn->prepare("DELETE FROM tb_alamat WHERE id = ? AND id_user = ?");
        $stmt->bind_param("ii", $id_alamat, $id_user);
        if ($stmt->execute()) {
            set_flash('success', 'Berhasil', 'Alamat berhasil dihapus.');
        } else {
            set_flash('error', 'Gagal', 'Terjadi kesalahan saat menghapus alamat.');
        }
        $stmt->close();
    } elseif ($action === 'set_utama') {
        $id_alamat = intval($_POST['id_alamat']);
        // Set semua alamat lain menjadi tidak utama terlebih dahulu
        $stmt_reset = $conn->prepare("UPDATE tb_alamat SET utama = 0 WHERE id_user = ?");
        $stmt_reset->bind_param("i", $id_user);
        $stmt_reset->execute();
        $stmt_reset->close();

        // Atur alamat yang dipilih sebagai utama
        $stmt = $conn->prepare("UPDATE tb_alamat SET utama = 1 WHERE id = ? AND id_user = ?");
        $stmt->bind_param("ii", $id_alamat, $id_user);
        if ($stmt->execute()) {
            set_flash('success', 'Berhasil', 'Alamat berhasil diatur sebagai utama.');
        } else {
            set_flash('error', 'Gagal', 'Terjadi kesalahan.');
        }
        $stmt->close();
    }
    header("Location: alamat.php");
    exit;
}

// Ambil semua alamat pengguna
$alamatList = [];
$stmt = $conn->prepare("SELECT a.*, p.name AS provinsi_name, r.name AS regency_name, d.name AS district_name FROM tb_alamat a LEFT JOIN tb_provinces p ON a.provinsi_id = p.id LEFT JOIN tb_regencies r ON a.regency_id = r.id LEFT JOIN tb_districts d ON a.district_id = d.id WHERE a.id_user = ? ORDER BY a.utama DESC, a.id DESC");
$stmt->bind_param("i", $id_user);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $alamatList[] = $row;
}
$stmt->close();

// Ambil data wilayah untuk form
$provinces = [];
$res_prov = mysqli_query($conn, "SELECT id, name FROM tb_provinces ORDER BY name ASC");
while ($row = mysqli_fetch_assoc($res_prov)) {
    $provinces[] = $row;
}

$flash = get_flash();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1"/>
    <title>Alamat Saya - MinangMaknyus</title>

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

        /* --- Alamat Konten --- */
        .address-card {
            border-left: 4px solid var(--text-muted);
        }
        .address-card.utama {
            border-color: var(--primary-color-red);
        }
        .address-label {
            font-size: 0.75rem;
            font-weight: 600;
            padding: 2px 8px;
            border-radius: 4px;
            text-transform: uppercase;
        }
        .address-actions {
            opacity: 0;
            transition: opacity 0.2s ease;
        }
        .address-card:hover .address-actions {
            opacity: 1;
        }
        .btn-add-address {
            padding: 0.75rem 2rem;
            border-radius: 50px;
            font-weight: 600;
        }

        /* --- Modal --- */
        .modal-body select, .modal-body input {
            border-color: #dee2e6;
        }
        .modal-body .form-check-label {
            padding: 8px 16px;
            border: 1px solid #ccc;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s;
        }
        .modal-body .form-check-input[type="radio"]:checked + .form-check-label {
            background-color: var(--primary-color-red);
            color: white;
            border-color: var(--primary-color-red);
        }
        .modal-footer .btn {
            border-radius: 50px;
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
                        <a href="alamat.php" class="nav-link active">
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
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h4 class="fw-bold m-0">Alamat Saya</h4>
                        <button id="add-address-btn" class="btn btn-danger btn-add-address">
                            <i class="fa-solid fa-plus me-2"></i>Tambah Alamat Baru
                        </button>
                    </div>

                    <?php if (empty($alamatList)): ?>
                        <div class="text-center py-5 text-muted">
                            <i class="fa-regular fa-address-book fs-1 mb-3"></i>
                            <p>Anda belum memiliki alamat yang tersimpan.</p>
                        </div>
                    <?php else: ?>
                        <div class="row g-4">
                            <?php foreach ($alamatList as $alamat): ?>
                                <div class="col-12">
                                    <div class="card p-4 h-100 address-card <?= $alamat['utama'] ? 'utama' : '' ?>">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <div>
                                                <div class="d-flex align-items-center mb-1">
                                                    <h6 class="fw-bold m-0 me-2"><?= htmlspecialchars($alamat['nama_penerima'] ?? '') ?></h6>
                                                    <?php if ($alamat['utama']): ?>
                                                        <span class="badge text-bg-danger address-label">Utama</span>
                                                    <?php else: ?>
                                                        <span class="badge text-bg-secondary address-label"><?= htmlspecialchars($alamat['jenis_alamat'] ?? '') ?></span>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="text-muted small"><?= htmlspecialchars($alamat['telepon'] ?? '') ?></div>
                                            </div>
                                            <div class="dropdown address-actions">
                                                <button class="btn btn-link text-dark dropdown-toggle p-0" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="fas fa-ellipsis-h"></i>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                                    <li><button class="dropdown-item edit-btn" type="button" 
                                                                data-id="<?= $alamat['id'] ?>" 
                                                                data-nama="<?= htmlspecialchars($alamat['nama_penerima'] ?? '') ?>" 
                                                                data-telp="<?= htmlspecialchars($alamat['telepon'] ?? '') ?>" 
                                                                data-alamat="<?= htmlspecialchars($alamat['alamat_lengkap'] ?? '') ?>" 
                                                                data-provinsi="<?= htmlspecialchars($alamat['provinsi_id'] ?? '') ?>"
                                                                data-kota="<?= htmlspecialchars($alamat['regency_id'] ?? '') ?>"
                                                                data-kecamatan="<?= htmlspecialchars($alamat['district_id'] ?? '') ?>"
                                                                data-kodepos="<?= htmlspecialchars($alamat['kode_pos'] ?? '') ?>"
                                                                data-detaillain="<?= htmlspecialchars($alamat['detail_lainnya'] ?? '') ?>"
                                                                data-jenisalamat="<?= htmlspecialchars($alamat['jenis_alamat'] ?? '') ?>"
                                                                data-utama="<?= $alamat['utama'] ?>">Ubah Alamat</button></li>
                                                    <?php if (!$alamat['utama']): ?>
                                                        <li>
                                                            <form method="post" class="d-block w-100" onsubmit="return confirm('Apakah Anda yakin ingin menghapus alamat ini?');">
                                                                <input type="hidden" name="action" value="hapus">
                                                                <input type="hidden" name="id_alamat" value="<?= $alamat['id'] ?>">
                                                                <button type="submit" class="dropdown-item text-danger">Hapus</button>
                                                            </form>
                                                        </li>
                                                        <li>
                                                            <form method="post" class="d-block w-100">
                                                                <input type="hidden" name="action" value="set_utama">
                                                                <input type="hidden" name="id_alamat" value="<?= $alamat['id'] ?>">
                                                                <button type="submit" class="dropdown-item">Atur sebagai Utama</button>
                                                            </form>
                                                        </li>
                                                    <?php endif; ?>
                                                </ul>
                                            </div>
                                        </div>
                                        <p class="mb-1">
                                            <?= htmlspecialchars($alamat['alamat_lengkap'] ?? '') ?>, 
                                            <?= htmlspecialchars($alamat['district_name'] ?? '') ?>, 
                                            <?= htmlspecialchars($alamat['regency_name'] ?? '') ?>, 
                                            <?= htmlspecialchars($alamat['provinsi_name'] ?? '') ?>, 
                                            <?= htmlspecialchars($alamat['kode_pos'] ?? '') ?>
                                        </p>
                                        <p class="text-muted small m-0"><?= htmlspecialchars($alamat['detail_lainnya'] ?? '') ?></p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
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

    <div class="modal fade" id="alamatModal" tabindex="-1" aria-labelledby="alamatModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="alamatModalLabel">Tambah Alamat Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="alamatForm" method="post" action="alamat.php">
                    <div class="modal-body">
                        <input type="hidden" name="action" id="alamatAction" value="tambah">
                        <input type="hidden" name="id_alamat" id="alamatId">
                        
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="nama_penerima" class="form-label">Nama Lengkap</label>
                                <input type="text" name="nama_penerima" id="nama_penerima" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label for="telepon" class="form-label">Nomor Telepon</label>
                                <input type="tel" name="telepon" id="telepon" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label for="provinsi" class="form-label">Provinsi</label>
                                <select id="provinsi" name="provinsi" class="form-select" required>
                                    <option value="">Pilih Provinsi</option>
                                    <?php foreach($provinces as $prov): ?>
                                        <option value="<?= $prov['id'] ?>"><?= htmlspecialchars($prov['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="kota" class="form-label">Kota/Kabupaten</label>
                                <select id="kota" name="regency" class="form-select" required>
                                    <option value="">Pilih Kota/Kabupaten</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="kecamatan" class="form-label">Kecamatan</label>
                                <select id="kecamatan" name="district" class="form-select" required>
                                    <option value="">Pilih Kecamatan</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="kode_pos" class="form-label">Kode Pos</label>
                                <input type="text" name="kode_pos" id="kode_pos" class="form-control" placeholder="Kode Pos" required>
                            </div>
                            <div class="col-12">
                                <label for="alamat_lengkap" class="form-label">Nama Jalan, Gedung, No. Rumah</label>
                                <input type="text" name="alamat_lengkap" id="alamat_lengkap" class="form-control" required>
                            </div>
                            <div class="col-12">
                                <label for="detail_lainnya" class="form-label">Detail Lainnya (Cth: Blok / Unit No., Patokan)</label>
                                <input type="text" name="detail_lainnya" id="detail_lainnya" class="form-control">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Tandai Sebagai:</label>
                                <div class="d-flex gap-2">
                                    <input type="radio" class="btn-check" name="jenis_alamat" id="jenis_rumah" value="Rumah" autocomplete="off" checked>
                                    <label class="btn btn-outline-secondary" for="jenis_rumah">Rumah</label>
                                    <input type="radio" class="btn-check" name="jenis_alamat" id="jenis_kantor" value="Kantor" autocomplete="off">
                                    <label class="btn btn-outline-secondary" for="jenis_kantor">Kantor</label>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="utama" id="utama">
                                    <label class="form-check-label" for="utama">Atur sebagai alamat utama</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-danger">Simpan Alamat</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const alamatModal = new bootstrap.Modal(document.getElementById('alamatModal'));
        
        document.addEventListener('DOMContentLoaded', function() {
            const modalTitle = document.getElementById('alamatModalLabel');
            const form = document.getElementById('alamatForm');
            const actionInput = document.getElementById('alamatAction');
            const idInput = document.getElementById('alamatId');
            const namaInput = document.getElementById('nama_penerima');
            const telpInput = document.getElementById('telepon');
            const alamatInput = document.getElementById('alamat_lengkap');
            const detailLainnyaInput = document.getElementById('detail_lainnya');
            const utamaCheckbox = document.getElementById('utama');
            const provinsiSelect = document.getElementById('provinsi');
            const kotaSelect = document.getElementById('kota');
            const kecamatanSelect = document.getElementById('kecamatan');
            const kodePosInput = document.getElementById('kode_pos');

            async function fetchCities(provinceId, selectedCity = null) {
                if (!provinceId) {
                    kotaSelect.innerHTML = '<option value="">Pilih Kota/Kabupaten</option>';
                    kecamatanSelect.innerHTML = '<option value="">Pilih Kecamatan</option>';
                    return;
                }
                try {
                    const response = await fetch(`api_daerah.php?type=regencies&provinsi_id=${provinceId}`);
                    const data = await response.json();
                    kotaSelect.innerHTML = '<option value="">Pilih Kota/Kabupaten</option>';
                    data.forEach(item => {
                        const option = document.createElement('option');
                        option.value = item.id;
                        option.textContent = item.name;
                        if (selectedCity && selectedCity == item.id) {
                            option.selected = true;
                        }
                        kotaSelect.appendChild(option);
                    });
                } catch (e) {
                    console.error("Gagal mengambil data kota:", e);
                    Swal.fire({icon: 'error', title: 'Error', text: 'Gagal memuat daftar kota.'});
                }
            }

            async function fetchDistricts(regencyId, selectedDistrict = null) {
                if (!regencyId) {
                    kecamatanSelect.innerHTML = '<option value="">Pilih Kecamatan</option>';
                    return;
                }
                try {
                    const response = await fetch(`api_daerah.php?type=districts&regency_id=${regencyId}`);
                    const data = await response.json();
                    kecamatanSelect.innerHTML = '<option value="">Pilih Kecamatan</option>';
                    data.forEach(item => {
                        const option = document.createElement('option');
                        option.value = item.id;
                        option.textContent = item.name;
                        if (selectedDistrict && selectedDistrict == item.id) {
                            option.selected = true;
                        }
                        kecamatanSelect.appendChild(option);
                    });
                } catch (e) {
                    console.error("Gagal mengambil data kecamatan:", e);
                    Swal.fire({icon: 'error', title: 'Error', text: 'Gagal memuat daftar kecamatan.'});
                }
            }
            
            provinsiSelect.addEventListener('change', () => fetchCities(provinsiSelect.value));
            kotaSelect.addEventListener('change', () => fetchDistricts(kotaSelect.value));
            
            document.getElementById('add-address-btn').addEventListener('click', () => {
                modalTitle.textContent = 'Tambah Alamat Baru';
                actionInput.value = 'tambah';
                form.reset();
                provinsiSelect.value = '';
                kotaSelect.innerHTML = '<option value="">Pilih Kota/Kabupaten</option>';
                kecamatanSelect.innerHTML = '<option value="">Pilih Kecamatan</option>';
                alamatModal.show();
            });

            document.querySelectorAll('.edit-btn').forEach(btn => {
                btn.addEventListener('click', async () => {
                    const data = btn.dataset;
                    modalTitle.textContent = 'Ubah Alamat';
                    actionInput.value = 'ubah';
                    idInput.value = data.id;
                    namaInput.value = data.nama;
                    telpInput.value = data.telp;
                    alamatInput.value = data.alamat;
                    detailLainnyaInput.value = data.detaillain;
                    utamaCheckbox.checked = data.utama === '1';
                    kodePosInput.value = data.kodepos;
                    document.querySelector(`input[name="jenis_alamat"][value="${data.jenisalamat}"]`).checked = true;

                    provinsiSelect.value = data.provinsi;
                    await fetchCities(data.provinsi, data.kota);
                    await fetchDistricts(data.kota, data.kecamatan);

                    alamatModal.show();
                });
            });
            
            const flash = <?= json_encode($flash) ?>;
            if (flash && flash.type) {
                Swal.fire({
                    icon: flash.type,
                    title: flash.title,
                    text: flash.text,
                    toast: true,
                    position: 'top-end',
                    timer: 3000,
                    showConfirmButton: false
                });
            }
        });
    </script>
</body>
</html>