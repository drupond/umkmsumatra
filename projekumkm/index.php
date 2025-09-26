<?php
include 'db.php';
include 'functions.php';

// Ambil data yang diperlukan untuk tampilan awal
$kategoriList = getKategoriList($conn);
$cart_count = getCartCount($_SESSION['keranjang'] ?? []);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MinangMaknyus - Kelezatan Otentik Ranah Minang</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    
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
            padding-top: 72px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .section-title {
            text-align: center;
            font-size: 2.5rem;
            margin-bottom: 50px;
            font-weight: 600;
            position: relative;
            color: var(--primary-color-red);
        }
        .section-title::after {
            content: '';
            position: absolute;
            left: 50%;
            bottom: -15px;
            transform: translateX(-50%);
            width: 80px;
            height: 4px;
            background-color: var(--primary-color-yellow);
            border-radius: 2px;
        }

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

        .nav-icons a {
            color: var(--text-dark);
            margin-left: 20px;
            font-size: 1.2rem;
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

        /* --- Hero Carousel --- */
        #hero-carousel {
            margin-top: 0;
        }
        .carousel-item img {
            height: 80vh;
            object-fit: cover;
            filter: brightness(60%);
        }
        .carousel-caption {
            top: 50%;
            transform: translateY(-50%);
            bottom: auto;
        }
        .carousel-caption h5 {
            font-size: 3.5rem;
            font-weight: 700;
        }
        .carousel-caption p {
            font-size: 1.2rem;
        }
        .btn-primary-red {
            background-color: var(--primary-color-red);
            color: white;
            padding: 12px 30px;
            text-decoration: none;
            border-radius: 50px;
            font-weight: 600;
            transition: background-color 0.3s;
            border: none;
        }

        .btn-primary-red:hover {
            background-color: #a52d3a;
            color: white;
        }
        
        /* --- Produk Section --- */
        #produk {
            background-color: var(--light-color);
            color: var(--text-dark);
            padding: 80px 0;
        }
        
        .card-img-top {
            height: 250px;
            object-fit: cover;
            border-bottom: 1px solid #eee;
        }
        .card-body h5 { color: #212529; }
        .card-body strong { color: #dc3545; }
        .card-body .btn-outline-secondary { color: #6c757d; border-color: #6c757d; }
        .card-body .btn-outline-secondary:hover { color: #fff; background-color: #6c757d; }
        .card-body .btn-danger { background-color: var(--primary-color-red); border-color: var(--primary-color-red); }
        .card-body .btn-danger:hover { background-color: #a52d3a; border-color: #a52d3a; }
        
        .card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .card:hover {
            transform: translateY(-8px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
        }

        .card-img-top {
            transition: transform 0.4s ease;
            overflow: hidden;
        }

        .card:hover .card-img-top {
            transform: scale(1.05);
        }
        
        /* --- Testimoni Section --- */
        #testimoni {
            background-color: #f8f9fa;
            padding: 80px 0;
        }

        #testimoni .card-text {
            font-style: italic;
            color: var(--text-muted);
        }

        /* --- FAQ Section --- */
        #faq {
            background-color: #fffdf5;
            padding: 80px 0;
        }

        .accordion-button:not(.collapsed) {
            color: #fff;
            background-color: var(--primary-color-red);
            box-shadow: none;
        }
        .accordion-button:not(.collapsed)::after {
            filter: brightness(0) invert(1);
        }

        .accordion-body {
            background-color: #fff;
            color: var(--text-dark);
        }

        /* --- About Us --- */
        #about-us {
            padding: 80px 0;
        }
        #about-us .content {
            display: flex;
            align-items: center;
            gap: 50px;
        }

        #about-us .image-container {
            flex: 1;
        }

        #about-us img {
            width: 100%;
            border-radius: 8px;
        }

        #about-us .text-container {
            flex: 1;
        }

        #about-us h2 {
            font-size: 2rem;
            color: var(--primary-color-red);
        }

        #about-us p {
            color: var(--text-muted);
        }

        /* --- Contact Section --- */
        #kontak {
            background-color: var(--light-color);
            color: var(--text-dark);
            padding: 80px 0;
        }
        .contact-content {
            display: flex;
            gap: 50px;
            flex-wrap: wrap;
        }
        
        .contact-map {
            flex: 2;
            min-height: 400px;
            border-radius: 8px;
            overflow: hidden;
            border: 1px solid rgba(0, 0, 0, 0.1);
        }

        .contact-form {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .contact-form input,
        .contact-form textarea {
            width: 100%;
            padding: 12px;
            background-color: #fff;
            border: 1px solid rgba(0, 0, 0, 0.2);
            border-radius: 4px;
            color: var(--text-dark);
        }

        .contact-form input::placeholder,
        .contact-form textarea::placeholder {
            color: var(--text-muted);
        }

        .contact-form button {
            background-color: var(--primary-color-red);
            color: white;
            border: none;
            cursor: pointer;
            padding: 15px;
            border-radius: 4px;
            font-weight: 600;
            transition: background-color 0.3s;
        }
        
        .contact-form button:hover {
            background-color: #a52d3a;
        }

        /* --- Footer --- */
        footer {
            background-color: #fff;
            padding: 40px 0 20px;
            text-align: center;
            border-top: 1px solid rgba(0, 0, 0, 0.1);
            color: var(--text-dark);
        }

        .footer-social-links {
            margin-bottom: 20px;
        }

        .footer-social-links a {
            color: var(--text-dark);
            font-size: 1.5rem;
            margin: 0 10px;
            transition: color 0.3s;
        }

        .footer-social-links a:hover {
            color: var(--primary-color-red);
        }

        footer .copyright {
            color: var(--text-muted);
            font-size: 0.9rem;
        }
        
        .toast { 
            background-color: var(--primary-color-red); 
            color: white; 
        }

        /* --- [CSS BARU] Navigasi Kategori Produk --- */
        .category-nav {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 40px;
        }
        .category-nav .nav-link {
            color: var(--text-dark);
            background-color: #fff;
            border: 1px solid #dee2e6;
            border-radius: 50px;
            padding: 8px 20px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        .category-nav .nav-link:hover {
            color: var(--text-dark);
            background-color: var(--primary-color-yellow);
            border-color: var(--primary-color-yellow);
        }
        .category-nav .nav-link.active {
            color: #fff;
            background-color: var(--primary-color-red);
            border-color: var(--primary-color-red);
            font-weight: 600;
            box-shadow: 0 4px 10px rgba(220, 53, 69, 0.3);
        }

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
            .nav-link {
                padding: 10px 0;
            }
            .navbar-nav .nav-item {
                border-bottom: 1px solid #eee;
            }
            .navbar-nav .nav-item:last-child {
                border-bottom: none;
            }
            
            .navbar-custom .d-flex.align-items-center {
                flex-direction: column;
                align-items: flex-start !important;
                gap: 15px;
                margin-top: 20px;
            }
            .navbar-custom .d-flex.align-items-center > a,
            .navbar-custom .dropdown {
                width: 100%;
            }
            .navbar-custom .dropdown-menu {
                position: relative !important;
                transform: none !important;
                border: none;
                box-shadow: none;
            }
            .navbar-custom .dropdown-item {
                padding-left: 0;
            }

            .carousel-caption h5 {
                font-size: 2rem;
            }
            .carousel-caption p {
                font-size: 1rem;
            }

            .section-title {
                font-size: 2rem;
            }

            #about-us .content,
            .contact-content {
                flex-direction: column;
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
                            <a class="nav-link active" aria-current="page" href="#hero-carousel">Beranda</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#produk">Produk</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#testimoni">Testimoni</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#faq">FAQ</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#about-us">Tentang Kami</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#kontak">Hubungi Kami</a>
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

    <main>
        <div id="hero-carousel" class="carousel slide" data-bs-ride="carousel">
            <div class="carousel-indicators">
                <button type="button" data-bs-target="#hero-carousel" data-bs-slide-to="0" class="active" aria-current="true" aria-label="Slide 1"></button>
                <button type="button" data-bs-target="#hero-carousel" data-bs-slide-to="1" aria-label="Slide 2"></button>
                <button type="button" data-bs-target="#hero-carousel" data-bs-slide-to="2" aria-label="Slide 3"></button>
            </div>
            <div class="carousel-inner">
                <div class="carousel-item active">
                    <img src="assets/img/rendang.png" class="d-block w-100" alt="Rendang">
                    <div class="carousel-caption d-none d-md-block">
                        <h5>MinangMaknyus</h5>
                        <p>Kelezatan otentik dari Ranah Minang langsung ke meja Anda. Setiap suapan adalah cerita.</p>
                        <a href="#produk" class="btn-primary-red">Lihat Menu</a>
                    </div>
                </div>
                <div class="carousel-item">
                    <img src="assets/img/satepadang.png" class="d-block w-100" alt="Sate Padang">
                    <div class="carousel-caption d-none d-md-block">
                        <h5>MinangMaknyus</h5>
                        <p>Kelezatan otentik dari Ranah Minang langsung ke meja Anda. Setiap suapan adalah cerita.</p>
                        <a href="#produk" class="btn-primary-red">Lihat Menu</a>
                    </div>
                </div>
                <div class="carousel-item">
                    <img src="assets/img/Keripik.png" class="d-block w-100" alt="Keripik Sanjai">
                    <div class="carousel-caption d-none d-md-block">
                        <h5>MinangMaknyus</h5>
                        <p>Kelezatan otentik dari Ranah Minang langsung ke meja Anda. Setiap suapan adalah cerita.</p>
                        <a href="#produk" class="btn-primary-red">Lihat Menu</a>
                    </div>
                </div>
            </div>
            <button class="carousel-control-prev" type="button" data-bs-target="#hero-carousel" data-bs-slide="prev">
                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Previous</span>
            </button>
            <button class="carousel-control-next" type="button" data-bs-target="#hero-carousel" data-bs-slide="next">
                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Next</span>
            </button>
        </div>
        
        <div id="produk" class="album py-5 bg-light">
            <div class="container">
                <h2 class="section-title">Produk Kami</h2>

                <div class="d-flex justify-content-center mb-4">
                    <div class="kategori-filter mb-4 d-flex flex-wrap justify-content-center gap-2">
                        <button class="btn btn-outline-danger kategori-btn active" data-kategori="semua">Semua</button>
                        <?php foreach ($kategoriList as $kategori): ?>
                            <button class="btn btn-outline-danger kategori-btn" data-kategori="<?= $kategori['id_kategori'] ?>">
                                <?= htmlspecialchars($kategori['nama_kategori']) ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="d-flex justify-content-center mb-5">
                    <div class="d-flex gap-2" style="max-width:500px; width: 100%;">
                        <input type="text" id="searchInput" class="form-control" placeholder="Cari masakan favoritmu...">
                        <button type="button" id="searchButton" class="btn btn-danger">Cari</button>
                        <button type="button" id="resetButton" class="btn btn-outline-secondary">Reset</button>
                    </div>
                </div>
            
                <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 g-4" id="product-container">
                    </div>
            </div>
        </div>

        <section id="testimoni" class="py-5 bg-white">
            <div class="container">
                <h2 class="section-title">Apa Kata Mereka?</h2>
                <div class="row row-cols-1 row-cols-md-3 g-4">
                    <div class="col">
                        <div class="card h-100 shadow-sm p-3">
                            <div class="d-flex align-items-center mb-3">
                                <img src="https://i.pravatar.cc/50?img=1" class="rounded-circle me-3" alt="Pelanggan 1">
                                <div>
                                    <h6 class="mb-0">Rani P.</h6>
                                    <small class="text-muted">Jakarta</small>
                                </div>
                            </div>
                            <div class="text-warning mb-2">
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                            </div>
                            <p class="card-text fst-italic">"Rendangnya luar biasa! Bumbunya meresap sempurna, serasa makan di Padang. Pengiriman cepat dan packing aman."</p>
                        </div>
                    </div>
                    <div class="col">
                        <div class="card h-100 shadow-sm p-3">
                            <div class="d-flex align-items-center mb-3">
                                <img src="https://i.pravatar.cc/50?img=2" class="rounded-circle me-3" alt="Pelanggan 2">
                                <div>
                                    <h6 class="mb-0">Andi S.</h6>
                                    <small class="text-muted">Bandung</small>
                                </div>
                            </div>
                            <div class="text-warning mb-2">
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star-half-alt"></i>
                            </div>
                            <p class="card-text fst-italic">"Sate Padangnya maknyus! Kuahnya kental dan pedasnya pas. Kualitas makanan sangat terjamin."</p>
                        </div>
                    </div>
                    <div class="col">
                        <div class="card h-100 shadow-sm p-3">
                            <div class="d-flex align-items-center mb-3">
                                <img src="https://i.pravatar.cc/50?img=3" class="rounded-circle me-3" alt="Pelanggan 3">
                                <div>
                                    <h6 class="mb-0">Dina L.</h6>
                                    <small class="text-muted">Surabaya</small>
                                </div>
                            </div>
                            <div class="text-warning mb-2">
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                            </div>
                            <p class="card-text fst-italic">"Keripik sanjainya renyah dan bikin nagih. Layanan pelanggan juga sangat responsif. Sangat direkomendasikan!"</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section id="faq" class="py-5">
            <div class="container">
                <h2 class="section-title">FAQ</h2>
                <div class="accordion" id="faqAccordion">
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingOne">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="false" aria-controls="collapseOne">
                                Bagaimana metode pengiriman produk?
                            </button>
                        </h2>
                        <div id="collapseOne" class="accordion-collapse collapse" aria-labelledby="headingOne" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                Kami menggunakan jasa pengiriman ekspedisi terpercaya. Produk dikemas dengan metode khusus untuk menjaga kualitas dan kesegaran selama perjalanan.
                            </div>
                        </div>
                    </div>
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingTwo">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                                Apa saja pilihan metode pembayaran?
                            </button>
                        </h2>
                        <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                Anda dapat melakukan pembayaran melalui transfer bank (BCA, Mandiri), dompet digital (OVO, GoPay), dan kartu kredit. Semua transaksi aman dan terjamin.
                            </div>
                        </div>
                    </div>
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingThree">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                                Apakah ada kebijakan pengembalian dana (refund)?
                            </button>
                        </h2>
                        <div id="collapseThree" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                Refund dapat diajukan jika produk yang Anda terima dalam kondisi rusak atau tidak sesuai pesanan. Silakan hubungi layanan pelanggan kami dalam waktu 1x24 jam setelah produk diterima.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section id="about-us">
            <div class="container">
                <h2 class="section-title">Tentang MinangMaknyus</h2>
                <div class="content">
                    <div class="image-container">
                        <img src="https://responradio.com/wp-content/uploads/2023/12/Mengungkap-Keunikan-dan-Asal-Usul-Rumah-Gadang.jpg" alt="Rumah Gadang" loading="lazy">
                    </div>
                    <div class="text-container">
                        <p>Kami adalah tim yang berdedikasi untuk melestarikan dan menyajikan kuliner otentik dari Minangkabau. Setiap masakan kami dibuat dengan resep turun temurun dan bahan-bahan segar pilihan untuk memastikan cita rasa yang tak terlupakan.</p>
                        <p>MinangMaknyus bukan hanya tentang makanan, tapi juga tentang tradisi, kehangatan, dan kenangan. Kami mengundang Anda untuk merasakan kekayaan budaya Minang melalui setiap hidangan kami.</p>
                    </div>
                </div>
            </div>
        </section>

        <section id="kontak">
            <div class="container">
                <h2 class="section-title">Hubungi Kami</h2>
                <div class="contact-content">
                    <div class="contact-map">
                        <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3960.6083161427513!2d107.00057087595503!3d-6.938883267923761!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2e68449c251d1463%3A0xc39f8f2679163b7!2sJl.%20Raya%20Kedawung%2C%20Kalikoa%2C%20Kec.%20Kedawung%2C%20Kabupaten%20Cirebon%2C%20Jawa%20Barat!5e0!3m2!1sid!2sid!4v1699920150912!5m2!1sid!2sid" width="100%" height="100%" style="border:0;" allowfullscreen="" loading="lazy"></iframe>
                    </div>
                    <form class="contact-form" action="simpan_pesan.php" method="POST">
                        <input type="text" name="nama" placeholder="Nama Anda" required>
                        <input type="email" name="email" placeholder="Email Anda" required>
                        <textarea name="pesan" rows="5" placeholder="Pesan Anda" required></textarea>
                        <button type="submit">Kirim Pesan</button>
                    </form>
                </div>
            </div>
        </section>
    </main>

    <footer>
        <div class="container">
            <div class="footer-social-links">
                <a href="#"><i class="fab fa-facebook"></i></a>
                <a href="#"><i class="fab fa-instagram"></i></a>
                <a href="#"><i class="fab fa-twitter"></i></a>
            </div>
            <p class="copyright">© <?= date('Y') ?> MinangMaknyus. Semua Hak Dilindungi.</p>
        </div>
    </footer>
    
    <div class="position-fixed bottom-0 end-0 p-3" style="z-index: 1100">
        <div id="cart-toast" class="toast align-items-center text-bg-success border-0" role="alert">
            <div class="d-flex">
                <div class="toast-body">✅ Berhasil ditambahkan ke keranjang</div>
            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        $(document).ready(function() {
            // Fungsi untuk memuat produk
            function loadProducts(kategoriId, keyword) {
                $.ajax({
                    url: 'get_products.php',
                    method: 'GET',
                    data: { kategori_id: kategoriId, q: keyword },
                    beforeSend: function() {
                        $('#product-container').html('<div class="col-12 text-center py-5"><div class="spinner-border text-danger" role="status"><span class="visually-hidden">Loading...</span></div><p class="mt-2">Memuat produk...</p></div>');
                    },
                    success: function(response) {
                        $('#product-container').html(response);
                    },
                    error: function(xhr) {
                        console.log('Error loading products');
                        $('#product-container').html('<div class="col-12 text-center text-danger py-5">Terjadi kesalahan saat memuat produk.</div>');
                    }
                });
            }

            // Muat semua produk saat halaman pertama kali dimuat
            loadProducts('semua', '');

            // Tangani klik tombol kategori
            $('.kategori-btn').click(function(e) {
                e.preventDefault();
                $('.kategori-btn').removeClass('active');
                $(this).addClass('active');
                
                const kategoriId = $(this).data('kategori');
                const keyword = $('#searchInput').val(); // Ambil kata kunci pencarian yang sedang aktif
                loadProducts(kategoriId, keyword);
            });
            
            // Tangani pencarian
            $('#searchButton').click(function(e) {
                e.preventDefault();
                const keyword = $('#searchInput').val();
                const kategoriId = $('.kategori-btn.active').data('kategori');
                loadProducts(kategoriId, keyword);
            });
            
            // Tangani tombol reset
            $('#resetButton').click(function(e) {
                e.preventDefault();
                $('#searchInput').val('');
                $('.kategori-btn').removeClass('active');
                $('[data-kategori="semua"]').addClass('active');
                loadProducts('semua', '');
            });

            $(document).on("click", ".add-to-cart", function(e) {
                e.preventDefault();
                let id = $(this).data("id");
                
                $.post("tambah_keranjang.php", { id_produk: id, qty: 1, size: 'M' }, function(response) {
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

            // Auto-scroll saat halaman dimuat jika ada hash
            if (window.location.hash) {
                $('html, body').animate({
                    scrollTop: $(window.location.hash).offset().top - 72 
                }, 800);
            }
        });
    </script>
</body>
</html>