<?php
session_start();
include 'db.php';

$cart_count = 0;
if (isset($_SESSION['keranjang'])) {
    foreach ($_SESSION['keranjang'] as $item) {
        $cart_count += $item['qty'];
    }
}

$id_produk = intval($_GET['id'] ?? 0);
$produk = null;

if ($id_produk > 0) {
    $res = mysqli_query($conn, "SELECT * FROM tb_produk WHERE id = " . $id_produk);
    if ($res) {
        $produk = mysqli_fetch_assoc($res);
    }
}

if (!$produk) {
    echo "<div class='container mt-5 text-center'><h4>Produk tidak ditemukan!</h4><p><a href='index.php'>Kembali ke beranda</a></p></div>";
    exit;
}

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($produk['nama']) ?> - MinangMaknyus</title>

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

        /* --- Detail Produk --- */
        .product-img { 
            max-height: 500px; 
            object-fit: contain; 
            border-radius: 8px; 
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .size-option input { display: none; }
        .size-option label {
            border: 1px solid #ccc; 
            padding: 6px 14px; 
            margin-right: 6px; 
            border-radius: 6px; 
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .size-option input:checked + label { background: #ffc107; color: #000; border-color: #ffc107; }
        .btn-add-to-cart { background-color: var(--primary-color-yellow); color: var(--text-dark); border-color: var(--primary-color-yellow); }
        .btn-add-to-cart:hover { background-color: #e5ac00; color: var(--text-dark); border-color: #e5ac00; }
        .toast { background-color: var(--primary-color-red); color: white; }

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
    
    <div class="container py-5">
        <div class="row">
            <div class="col-md-6 mb-4 mb-md-0">
                <img src="admin/uploads/<?= htmlspecialchars($produk['foto']) ?>" class="w-100 product-img" alt="<?= htmlspecialchars($produk['nama']) ?>" onerror="this.src='https://placehold.co/500x400'">
            </div>
            <div class="col-md-6">
                <h3 class="fw-bold"><?= htmlspecialchars($produk['nama']) ?></h3>
                <?php 
                    $res_kat = mysqli_query($conn, "SELECT nama_kategori FROM tb_kategori WHERE id_kategori = " . $produk['id_kategori']);
                    $kategori = mysqli_fetch_assoc($res_kat);
                ?>
                <p class="text-muted">Kategori: <?= htmlspecialchars($kategori['nama_kategori'] ?? 'Tidak Diketahui') ?></p>
                <h4 class="text-danger fw-bold mb-2">Rp <?= number_format($produk['harga'], 0, ',', '.') ?></h4>
                <p class="text-muted">Stok Tersedia: <?= $produk['stok'] ?></p>

                <form id="add-to-cart-form">
                    <input type="hidden" name="id_produk" value="<?= $produk['id'] ?>">
                    <div class="mb-3">
                        <label class="fw-bold d-block">Pilih Size:</label>
                        <div class="size-option d-flex">
                            <input type="radio" id="sizeS" name="size" value="S" checked>
                            <label for="sizeS">S</label>
                            <input type="radio" id="sizeM" name="size" value="M">
                            <label for="sizeM">M</label>
                            <input type="radio" id="sizeL" name="size" value="L">
                            <label for="sizeL">L</label>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="fw-bold">Jumlah:</label>
                        <div class="input-group" style="width:150px;">
                            <button class="btn btn-outline-secondary" type="button" id="minus-qty">-</button>
                            <input type="number" name="qty" id="qty-input" class="form-control text-center" value="1" min="1" max="<?= $produk['stok'] ?>">
                            <button class="btn btn-outline-secondary" type="button" id="plus-qty">+</button>
                        </div>
                    </div>

                    <div class="d-flex gap-2 mb-4">
                        <button type="submit" class="btn btn-add-to-cart"><i class="bi bi-cart-plus"></i> Tambah ke Keranjang</button>
                        <a href="<?= isset($_SESSION['login']) ? "proses_checkout.php?produk=" . $produk['id'] : "auth/auth.php" ?>" 
                            class="btn btn-danger">Beli Sekarang</a>
                    </div>
                </form>

                <h6 class="fw-bold">Deskripsi Produk</h6>
                <p class="text-muted"><?= nl2br(htmlspecialchars($produk['deskripsi'])) ?></p>
                </a>
            </div>
        </div>
    </div>

    <div class="position-fixed bottom-0 end-0 p-3" style="z-index: 1100">
        <div id="cart-toast" class="toast align-items-center text-bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body">✅ Berhasil ditambahkan ke keranjang</div>
            </div>
        </div>
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
    <script>
        // Quantity Buttons
        const qtyInput = document.getElementById('qty-input');
        const minusBtn = document.getElementById('minus-qty');
        const plusBtn = document.getElementById('plus-qty');

        if (qtyInput && minusBtn && plusBtn) {
            minusBtn.addEventListener('click', () => {
                let val = parseInt(qtyInput.value);
                if (val > qtyInput.min) {
                    qtyInput.value = val - 1;
                }
            });

            plusBtn.addEventListener('click', () => {
                let val = parseInt(qtyInput.value);
                if (val < qtyInput.max) {
                    qtyInput.value = val + 1;
                }
            });
        }
        
        // Add to cart form submission
        $('#add-to-cart-form').on('submit', function(e) {
            e.preventDefault();
            
            $.post("tambah_keranjang.php", $(this).serialize(), function(response) {
                if (response.status === 'success') {
                    $("#cart-count").text(response.cart_count);
                    let toastEl = document.getElementById("cart-toast");
                    let toast = new bootstrap.Toast(toastEl);
                    toast.show();
                } else {
                    alert(response.message);
                }
            }, 'json').fail(function() {
                alert("Terjadi kesalahan saat memproses data.");
            });
        });
    </script>
</body>
</html>