<?php
session_start();
include '../db.php'; // sesuaikan path jika perlu

// Optional: cek apakah admin login
// if (!isset($_SESSION['admin'])) { header('Location: ../auth/login.php'); exit; }

// Ambil data pesan dari database, diurutkan dari yang terbaru
$pesanRes = mysqli_query($conn, "SELECT * FROM tb_pesan ORDER BY tanggal DESC");

?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pesan Masuk ┃ MinangMaknyus</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body { font-family: 'Poppins', sans-serif; background: #f1f3f5; }
        .sidebar-custom {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: 220px;
            background: #212529;
            padding-top: 18px;
            color: #adb5bd;
            overflow-y: auto;
        }
        .sidebar-custom .brand {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 16px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.03);
        }
        .sidebar-custom .brand img { height: 40px; }
        .sidebar-custom .nav-link {
            color: #adb5bd;
            padding: 10px 16px;
            display: flex;
            align-items: center;
            gap: 10px;
            border-radius: 6px;
            margin: 6px 8px;
            transition: background .15s, color .15s;
        }
        .sidebar-custom .nav-link:hover { background: rgba(255, 255, 255, 0.03); color: #fff; }
        .sidebar-custom .nav-link.active {
            background: linear-gradient(90deg, #0d6efd, #6610f2);
            color: #fff;
            font-weight: 600;
        }
        .main-content { margin-left: 240px; padding: 32px; }
        .signout { position: fixed; top: 12px; right: 22px; z-index: 50; }
        @media (max-width: 767px) {
            .sidebar-custom { position: relative; width: 100%; height: auto; }
            .main-content { margin-left: 0; padding: 12px; }
            .signout { position: static; margin-top: 10px; }
        }
    </style>
</head>
<body>

<nav class="sidebar-custom">
    <div class="brand px-2">
        <img src="../assets/img/logo.png" alt="logo" onerror="this.src='https://placehold.co/80x40'">
        <div>
            <div style="color: #fff; font-weight: 700;">MinangMaknyus</div>
            <small style="color: #8b949e;">Admin Panel</small>
        </div>
    </div>
    <div class="mt-3">
        <a href="dashboard.php" class="nav-link"><i class="bi bi-house-fill"></i> Dashboard</a>
        <a href="transaksi.php" class="nav-link"><i class="bi bi-receipt"></i> Transaksi</a>
        <a href="pelanggan.php" class="nav-link"><i class="bi bi-people-fill"></i> Pelanggan</a>
        <a href="pesan.php" class="nav-link active"><i class="bi bi-envelope-fill"></i> Pesan</a>
    </div>
    <div class="mt-4 px-2">
        <a href="../auth/logout.php" class="nav-link" style="margin-top: 10px"><i class="bi bi-box-arrow-right"></i> Sign out</a>
    </div>
</nav>

<div class="signout d-none d-md-block">
    <a href="../auth/logout.php" class="btn btn-sm btn-outline-light">Sign out</a>
</div>

<div class="main-content">
    <div class="d-flex justify-content-between align-items-start mb-3">
        <div>
            <h3 class="mb-0">📩 Pesan Masuk</h3>
            <small class="text-muted">Daftar semua pesan dari formulir kontak</small>
        </div>
    </div>

    <div class="container-fluid p-0">
        <div class="card mb-4 shadow-sm">
            <div class="card-body bg-white">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th style="width:50px">No</th>
                                <th>Nama</th>
                                <th>Email</th>
                                <th>Pesan</th>
                                <th>Tanggal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (mysqli_num_rows($pesanRes) > 0): ?>
                                <?php $no = 1; while ($p = mysqli_fetch_assoc($pesanRes)): ?>
                                <tr>
                                    <td><?= $no++ ?></td>
                                    <td><?= htmlspecialchars($p['nama'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($p['email'] ?? '-') ?></td>
                                    <td style="white-space: pre-wrap;"><?= htmlspecialchars($p['pesan'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($p['tanggal'] ?? '-') ?></td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">Belum ada pesan masuk.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div> 
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>