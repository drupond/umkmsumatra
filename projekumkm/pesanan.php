<?php
session_start();
include 'db.php';

// Pastikan user login
if (!isset($_SESSION['username'])) {
    header("Location: auth/login.php");
    exit;
}

$username = $_SESSION['username'];
$id_user = $_SESSION['id'];

// Ambil data pesanan user
$sql = "
    SELECT t.id_transaksi, t.tanggal, t.total, t.status_pengiriman, t.alamat, t.metode_pembayaran, t.metode_pengiriman,
            d.jumlah, p.nama, p.foto, d.harga
    FROM tb_transaksi t
    JOIN tb_detail d ON t.id_transaksi = d.id_transaksi
    JOIN tb_produk p ON d.id_produk = p.id
    WHERE t.id_pelanggan = ?
    ORDER BY t.tanggal DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_user);
$stmt->execute();
$res = $stmt->get_result();

$pesanan = [];
while ($row = mysqli_fetch_assoc($res)) {
    $id_transaksi = $row['id_transaksi'];
    
    // Inisialisasi entri pesanan jika belum ada
    if (!isset($pesanan[$id_transaksi])) {
        $pesanan[$id_transaksi] = [
            'tanggal' => $row['tanggal'],
            'total' => $row['total'],
            'status' => $row['status_pengiriman'],
            'alamat' => $row['alamat'],
            'metode_pembayaran' => $row['metode_pembayaran'],
            'metode_pengiriman' => $row['metode_pengiriman'],
            'items' => []
        ];
    }
    
    // Tambahkan item produk ke pesanan
    $pesanan[$id_transaksi]['items'][] = $row;
}
$stmt->close();

// Hitung jumlah keranjang untuk navbar
$cart_count = 0;
if (isset($_SESSION['keranjang'])) {
    foreach ($_SESSION['keranjang'] as $item) {
        $cart_count += $item['qty'];
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pesanan Saya - MinangMaknyus</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <style>
        :root {
            --primary-color-red: #dc3545;
            --primary-color-yellow: #ffc107;
            --light-color: #f8f9fa;
            --text-dark: #212529;
            --text-muted: #6c757d;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        body { background-color: #fffdf5; color: var(--text-dark); line-height: 1.6; padding-top: 72px; }
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

        /* --- Konten Halaman Pesanan --- */
        .order-card { border-radius: 10px; overflow: hidden; }
        .order-header { background: #ffc107; color: #000; padding: 10px 15px; font-weight: 600; }
        .order-item { border-bottom: 1px solid #eee; padding: 15px; display:flex; align-items:center; }
        .order-item img { width:70px; height:70px; object-fit:cover; border-radius:6px; margin-right:15px; }
        .order-footer { padding: 15px; background: #fff3cd; }

        /* --- Footer --- */
        footer { background-color: #fff; padding: 40px 0 20px; text-align: center; border-top: 1px solid rgba(0, 0, 0, 0.1); color: var(--text-dark); }
        .footer-social-links a { color: var(--text-dark); font-size: 1.5rem; margin: 0 10px; transition: color 0.3s; }
        .footer-social-links a:hover { color: var(--primary-color-red); }

        /* --- Responsif --- */
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

    <div class="container py-4">
        <h3 class="fw-bold mb-4 text-danger">Pesanan Saya</h3>

        <?php if (!empty($pesanan)): ?>
            <?php foreach ($pesanan as $id => $data): ?>
                <div class="card shadow-sm mb-4 order-card">
                    <div class="order-header d-flex justify-content-between">
                        <span>ID Pesanan: #<?= htmlspecialchars($id) ?></span>
                        <span>Status: <?= htmlspecialchars($data['status']) ?></span>
                    </div>
                    <div class="order-body bg-white">
                        <?php foreach ($data['items'] as $item): ?>
                            <div class="order-item">
                                <img src="admin/uploads/<?= htmlspecialchars($item['foto']) ?>" alt="<?= htmlspecialchars($item['nama']) ?>"
                                     onerror="this.src='https://placehold.co/70x70'">
                                <div>
                                    <h6 class="mb-1"><?= htmlspecialchars($item['nama']) ?></h6>
                                    <small class="text-muted">Harga: Rp <?= number_format($item['harga'],0,',','.') ?></small><br>
                                    <small class="text-muted">Jumlah: <?= $item['jumlah'] ?></small>
                                </div>
                                <div class="ms-auto fw-bold text-danger">
                                    Rp <?= number_format($item['harga'] * $item['jumlah'], 0, ',', '.') ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="order-footer d-flex justify-content-between align-items-center">
                        <small>Tanggal: <?= $data['tanggal'] ?></small>
                        <div>
                           <span class="fw-bold me-2">Total: Rp <?= number_format($data['total'],0,',','.') ?></span>
                           <a href="invoice.php?id=<?= $id ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-file-earmark-text"></i> Lihat Invoice</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="text-center py-5">
                <p class="text-muted">Belum ada pesanan.</p>
                <a href="index.php" class="btn btn-warning">Belanja Sekarang</a>
            </div>
        <?php endif; ?>
    </div>
    
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
</body>
</html>