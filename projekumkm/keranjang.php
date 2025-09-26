<?php
session_start();
include 'db.php';

// --- Ambil data untuk navbar ---
$cart_count = 0;
if (isset($_SESSION['keranjang'])) {
    foreach ($_SESSION['keranjang'] as $item) {
        $cart_count += $item['qty'] ?? 0;
    }
}

// Pastikan user login
if (!isset($_SESSION['username'])) {
    header("Location: auth/auth.php");
    exit;
}

if (!isset($_SESSION['keranjang'])) {
    $_SESSION['keranjang'] = [];
}
$keranjang = $_SESSION['keranjang'];

$total = 0;
foreach ($keranjang as $item) {
    // Memastikan kunci array ada sebelum digunakan
    $harga = $item['harga'] ?? 0;
    $qty = $item['qty'] ?? 0;
    $total += $harga * $qty;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Keranjang Belanja - MinangMaknyus</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root {
            --primary-color-red: #dc3545;
            --primary-color-yellow: #ffc107;
            --light-color: #f8f9fa;
            --text-dark: #212529;
            --text-muted: #6c757d;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            background-color: #fffdf5;
            color: var(--text-dark);
            line-height: 1.6;
            padding-top: 100px; /* Space for the fixed header */
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        /* --- Header & Navigasi --- */
        header {
            background-color: #fff;
            padding: 20px 0;
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
        }

        .navbar-custom {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo h1 {
            color: var(--primary-color-red);
            font-size: 1.8rem;
            font-weight: 700;
            cursor: pointer;
        }
        
        .logo .maknyus {
            color: var(--primary-color-yellow);
            font-style: italic;
        }

        .nav-links {
            list-style: none;
            display: flex;
            gap: 25px;
            margin-bottom: 0;
        }

        .nav-links a {
            color: var(--text-dark);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s;
        }

        .nav-links a:hover {
            color: var(--primary-color-red);
        }

        .nav-icons a {
            color: var(--text-dark);
            margin-left: 20px;
            font-size: 1.2rem;
        }

        .hamburger {
            display: none;
            cursor: pointer;
        }

        .hamburger .bar {
            display: block;
            width: 25px;
            height: 3px;
            margin: 5px auto;
            transition: all 0.3s ease-in-out;
            background-color: var(--text-dark);
        }

        #cart-count { 
            font-size: 0.8rem; 
            position: relative; 
            top: -8px; 
            left: -8px; 
        }

        /* Keranjang-specific styles */
        body { background:#f8f9fa; }
        .cart-container { max-width: 900px; margin: auto; background:#fff; border-radius:8px; box-shadow:0 0 10px rgba(0,0,0,0.1); }
        .cart-header { font-weight:600; border-bottom:2px solid #f2f2f2; padding:12px 0; }
        .cart-item { border-bottom:1px solid #eee; padding:15px 0; }
        .cart-item img { width:80px; height:80px; object-fit:cover; border-radius:6px; }
        .cart-footer { border-top:2px solid #f2f2f2; padding:15px; }
        .quantity-input { width: 50px; text-align: center; }

        /* --- Responsif --- */
        @media (max-width: 768px) {
            .nav-links {
                display: none;
                flex-direction: column;
                position: absolute;
                top: 80px;
                left: 0;
                width: 100%;
                background-color: #fff;
                border-top: 1px solid rgba(0, 0, 0, 0.1);
                padding: 20px;
            }

            .nav-links.active {
                display: flex;
            }
            
            .nav-links li {
                text-align: center;
                margin-bottom: 10px;
            }

            .hamburger {
                display: block;
            }
            .navbar-custom {
                flex-wrap: wrap; 
            }
            .navbar-custom .logo {
                flex-grow: 1;
            }
            .navbar-custom .d-flex {
                order: 1; 
            }
            .navbar-custom .hamburger {
                order: 2; 
            }
            
            .navbar-custom .d-flex,
            .navbar-custom .dropdown {
                margin-left: 0 !important;
                margin-right: 0 !important;
            }
            .navbar-custom .d-flex a,
            .navbar-custom .dropdown a {
                margin: 0 5px !important;
            }
        }
    </style>
</head>
<body>

<header>
    <div class="container navbar-custom">
        <div class="logo">
            <h1>Minang<span class="maknyus">Maknyus</span></h1>
        </div>
        <ul class="nav-links">
            <li><a href="index.php#hero-carousel">Beranda</a></li>
            <li><a href="index.php#produk">Produk</a></li>
            <li><a href="index.php#testimoni">Testimoni</a></li>
            <li><a href="index.php#faq">FAQ</a></li>
            <li><a href="index.php#about-us">Tentang Kami</a></li>
            <li><a href="index.php#kontak">Hubungi Kami</a></li>
        </ul>
        <div class="d-flex align-items-center ms-lg-3">
            <a href="#" class="btn btn-outline-danger position-relative me-3">
                <i class="fas fa-heart"></i>
            </a>
            <a href="keranjang.php" class="btn btn-outline-dark position-relative me-3">
                <i class="fas fa-shopping-cart"></i>
                <span id="cart-count" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                    <?= $cart_count ?>
                </span>
            </a>
            <?php if (isset($_SESSION['username'])): ?>
                <div class="dropdown">
                    <a href="#" class="d-flex align-items-center text-dark text-decoration-none dropdown-toggle"
                       id="dropdownUser" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-user-circle fs-4 me-2"></i>
                        <span class="fw-semibold"><?= htmlspecialchars($_SESSION['username']) ?></span>
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
        <div class="hamburger">
            <span class="bar"></span>
            <span class="bar"></span>
            <span class="bar"></span>
        </div>
    </div>
</header>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold text-danger">Keranjang Belanja</h4>
        </div>

    <div class="cart-container p-3">
        <div class="row cart-header text-muted">
            <div class="col-5">Produk</div>
            <div class="col-2 text-center">Harga Satuan</div>
            <div class="col-3 text-center">Kuantitas</div>
            <div class="col-2 text-end">Total Harga</div>
        </div>

        <div id="cart-items-container">
            <?php if (!empty($keranjang)): ?>
                <?php foreach ($keranjang as $item): ?>
                    <div class="row cart-item align-items-center" data-id="<?= htmlspecialchars($item['id'] ?? '') ?>" data-size="<?= htmlspecialchars($item['size'] ?? '') ?>" data-stok="<?= htmlspecialchars($item['stok'] ?? 0) ?>">
                        <div class="col-5 d-flex align-items-center">
                            <img src="admin/uploads/<?= htmlspecialchars($item['foto'] ?? '') ?>" alt="<?= htmlspecialchars($item['nama'] ?? '') ?>" 
                                 onerror="this.src='https://placehold.co/80x80'">
                            <div class="ms-3">
                                <h6 class="mb-1"><?= htmlspecialchars($item['nama'] ?? '') ?></h6>
                                <small class="text-muted">Size: <?= htmlspecialchars($item['size'] ?? '-') ?></small>
                            </div>
                        </div>
                        <div class="col-2 text-center text-danger fw-semibold">
                            Rp <?= number_format($item['harga'] ?? 0,0,',','.') ?>
                        </div>
                        <div class="col-3 text-center">
                            <div class="d-inline-flex align-items-center border rounded">
                                <button class="btn btn-sm btn-light p-1 quantity-btn" data-action="minus">-</button>
                                <input type="number" class="form-control form-control-sm quantity-input border-0 p-1" value="<?= htmlspecialchars($item['qty'] ?? 0) ?>" min="1">
                                <button class="btn btn-sm btn-light p-1 quantity-btn" data-action="plus">+</button>
                            </div>
                            <br>
                            <a href="#" class="text-danger remove-item">Hapus</a>
                        </div>
                        <div class="col-2 text-end fw-bold text-danger" data-subtotal="<?= ($item['harga'] ?? 0) * ($item['qty'] ?? 0) ?>">
                            Rp <?= number_format(($item['harga'] ?? 0) * ($item['qty'] ?? 0),0,',','.') ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="text-center py-5">
                    <p class="text-muted">Keranjang masih kosong.</p>
                    <a href="index.php" class="btn btn-danger">Belanja Sekarang</a>
                </div>
            <?php endif; ?>
        </div>

        <?php if (!empty($keranjang)): ?>
            <div class="cart-footer d-flex justify-content-end align-items-center">
                <div class="text-end">
                    <small class="d-block text-muted">Total:</small>
                    <span class="fs-5 fw-bold text-danger" id="cart-total">Rp <?= number_format($total,0,',','.') ?></span>
                    <a href="checkout.php" class="btn btn-danger ms-3 px-4">Checkout</a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const hamburger = document.querySelector('.hamburger');
        const navLinks = document.querySelector('.nav-links');
        if (hamburger && navLinks) {
            hamburger.addEventListener('click', () => {
                navLinks.classList.toggle('active');
            });
        }
        
        const container = document.getElementById('cart-items-container');

        // Handle +/- buttons
        container.addEventListener('click', function(event) {
            if (event.target.classList.contains('quantity-btn')) {
                const btn = event.target;
                const itemElement = btn.closest('.cart-item');
                const id = itemElement.dataset.id;
                const size = itemElement.dataset.size;
                const stok = parseInt(itemElement.dataset.stok);
                const input = itemElement.querySelector('.quantity-input');
                let newQty = parseInt(input.value);

                if (btn.dataset.action === 'plus') {
                    newQty++;
                    if (newQty > stok) {
                        Swal.fire('Stok Habis!', `Maaf, stok hanya tersisa ${stok} item.`, 'warning');
                        return;
                    }
                } else if (btn.dataset.action === 'minus' && newQty > 1) {
                    newQty--;
                }

                updateQuantity(id, newQty, size);
            }
        });

        // Handle input field change
        container.addEventListener('change', function(event) {
            if (event.target.classList.contains('quantity-input')) {
                const input = event.target;
                const itemElement = input.closest('.cart-item');
                const id = itemElement.dataset.id;
                const size = itemElement.dataset.size;
                const stok = parseInt(itemElement.dataset.stok);
                let newQty = parseInt(input.value);

                if (newQty <= 0 || isNaN(newQty)) {
                    Swal.fire({
                        title: 'Hapus produk?',
                        text: "Apakah Anda yakin ingin menghapus produk ini dari keranjang?",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        cancelButtonColor: '#6c757d',
                        confirmButtonText: 'Ya, hapus!',
                        cancelButtonText: 'Batal'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            updateQuantity(id, 0, size);
                        } else {
                            input.value = 1;
                        }
                    });
                } else if (newQty > stok) {
                    Swal.fire('Stok Habis!', `Maaf, stok hanya tersisa ${stok} item.`, 'warning');
                    input.value = stok;
                } else {
                    updateQuantity(id, newQty, size);
                }
            }
        });

        // Handle 'Hapus' link
        container.addEventListener('click', function(event) {
            if (event.target.classList.contains('remove-item')) {
                event.preventDefault();
                const itemElement = event.target.closest('.cart-item');
                const id = itemElement.dataset.id;
                const size = itemElement.dataset.size;
                Swal.fire({
                    title: 'Hapus produk?',
                    text: "Apakah Anda yakin ingin menghapus produk ini dari keranjang?",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Ya, hapus!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        updateQuantity(id, 0, size);
                    }
                });
            }
        });

        function updateQuantity(id, qty, size) {
            fetch('keranjang_action.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `update_qty=1&id=${id}&qty=${qty}&size=${size}`
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Jaringan tidak responsif.');
                }
                return response.json();
            })
            .then(data => {
                if (data.ok) {
                    // Cek apakah item dihapus
                    if(qty === 0) {
                        location.reload();
                    } else {
                        // Perbarui tampilan item secara dinamis
                        const itemElement = document.querySelector(`.cart-item[data-id="${id}"][data-size="${size}"]`);
                        itemElement.querySelector('.quantity-input').value = qty;
                        itemElement.querySelector('[data-subtotal]').textContent = 'Rp ' + data.subtotal.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
                        document.getElementById('cart-total').textContent = 'Rp ' + data.total.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
                        document.getElementById('cart-count').textContent = data.cart_count;
                    }
                } else {
                    Swal.fire('Gagal!', data.msg, 'error');
                }
            })
            .catch(error => {
                Swal.fire('Error!', 'Gagal terhubung ke server.', 'error');
                console.error('AJAX Error:', error);
            });
        }
    });
</script>
</body>
</html>