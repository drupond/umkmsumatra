<?php
session_start();
include '../db.php';

// Pastikan admin login (sesi di-uncomment jika sudah diimplementasikan)
// if (!isset($_SESSION['admin'])) {
//     header('Location: ../auth/login.php');
//     exit;
// }

// Ambil ID transaksi dari URL
$id_transaksi = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id_transaksi === 0) {
    echo "ID Transaksi tidak valid.";
    exit;
}

// Tangani update status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $new_status = $_POST['status'];
    $stmt = $conn->prepare("UPDATE tb_transaksi SET status_pengiriman = ? WHERE id_transaksi = ?");
    $stmt->bind_param("si", $new_status, $id_transaksi);
    $stmt->execute();
    $stmt->close();
    header("Location: detail_transaksi.php?id=$id_transaksi");
    exit;
}

// Ambil data transaksi, detail produk, dan info pelanggan
$sql = "
    SELECT 
        t.*, 
        u.nama AS nama_pelanggan, 
        u.telepon, 
        u.email,
        a.nama_penerima,
        a.telepon AS telepon_penerima,
        a.alamat_lengkap AS alamat_penerima,
        p.name AS provinsi,
        r.name AS regency,
        d.name AS district,
        a.kode_pos
    FROM tb_transaksi t
    LEFT JOIN tb_user u ON t.id_pelanggan = u.id
    LEFT JOIN tb_alamat a ON t.id_alamat = a.id
    LEFT JOIN tb_provinces p ON a.provinsi_id = p.id
    LEFT JOIN tb_regencies r ON a.regency_id = r.id
    LEFT JOIN tb_districts d ON a.district_id = d.id
    WHERE t.id_transaksi = ?
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_transaksi);
$stmt->execute();
$res = $stmt->get_result();
$transaksi = $res->fetch_assoc();
$stmt->close();

if (!$transaksi) {
    echo "Transaksi tidak ditemukan.";
    exit;
}

// Ambil item produk dalam transaksi
$sql_items = "
    SELECT 
        dt.*, 
        pr.nama AS nama_produk, 
        pr.foto
    FROM tb_detail dt
    JOIN tb_produk pr ON dt.id_produk = pr.id
    WHERE dt.id_transaksi = ?
";
$stmt_items = $conn->prepare($sql_items);
$stmt_items->bind_param("i", $id_transaksi);
$stmt_items->execute();
$res_items = $stmt_items->get_result();
$items = [];
while ($row = $res_items->fetch_assoc()) {
    $items[] = $row;
}
$stmt_items->close();

// Definisikan status yang mungkin
$status_list = ['dibuat', 'diproses', 'dikirim', 'diterima'];
$status_map = [
    'dibuat' => ['icon' => 'bi-box', 'color' => 'secondary', 'text' => 'Pesanan Dibuat'],
    'diproses' => ['icon' => 'bi-gear', 'color' => 'warning', 'text' => 'Sedang Diproses'],
    'dikirim' => ['icon' => 'bi-truck', 'color' => 'info', 'text' => 'Dikirim'],
    'diterima' => ['icon' => 'bi-check-circle', 'color' => 'success', 'text' => 'Diterima']
];

$current_status = $transaksi['status_pengiriman'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Detail Transaksi - Admin MinangMaknyus</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body { font-family: 'Poppins', sans-serif; background: #f1f3f5; }
        .sidebar-custom {
            position: fixed; top: 0; left: 0; height: 100vh; width: 220px;
            background: #212529; padding-top: 18px; color: #adb5bd; overflow-y: auto;
        }
        .sidebar-custom .brand { display:flex; align-items:center; gap:10px; padding: 12px 16px; border-bottom: 1px solid rgba(255,255,255,0.03); }
        .sidebar-custom .brand img { height:40px; }
        .sidebar-custom .nav-link {
            color: #adb5bd; padding: 10px 16px; display:flex; align-items:center; gap:10px;
            border-radius:6px; margin:6px 8px; transition: background .15s, color .15s;
        }
        .sidebar-custom .nav-link:hover { background: rgba(255,255,255,0.03); color:#fff; }
        .sidebar-custom .nav-link.active { background: linear-gradient(90deg,#0d6efd,#6610f2); color:#fff; font-weight:600; }
        .main-content { margin-left: 240px; padding: 32px; }
        .order-item img { width:80px; height:80px; object-fit:cover; border-radius:8px; }
        .btn-status-update { width: 100%; }
    </style>
</head>
<body>

<!-- Sidebar -->
<nav class="sidebar-custom">
    <div class="brand px-2">
        <img src="../assets/img/logo.png" alt="logo" onerror="this.src='https://placehold.co/80x40'">
        <div><div style="color:#fff;font-weight:700">MinangMaknyus</div><small style="color:#8b949e">Admin Panel</small></div>
    </div>
    <div class="mt-3">
        <a href="dashboard.php" class="nav-link"><i class="bi bi-house-fill"></i> Dashboard</a>
        <a href="transaksi.php" class="nav-link active"><i class="bi bi-receipt"></i> Transaksi</a>
        <a href="pelanggan.php" class="nav-link"><i class="bi bi-people-fill"></i> Pelanggan</a>
    </div>
    <div class="mt-4 px-2">
        <a href="../auth/logout.php" class="nav-link" style="margin-top:10px"><i class="bi bi-box-arrow-right"></i> Sign out</a>
    </div>
</nav>

<!-- Main content -->
<div class="main-content">
    <div class="d-flex justify-content-between align-items-start mb-3">
        <div>
            <h3 class="mb-0">Detail Transaksi #<?= htmlspecialchars($id_transaksi) ?></h3>
            <small class="text-muted">Rincian pesanan dari pelanggan</small>
        </div>
        <div>
            <a href="transaksi.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Kembali</a>
        </div>
    </div>

    <!-- Informasi Utama & Status -->
    <div class="row g-4 mb-4">
        <div class="col-md-8">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h5 class="card-title mb-3">Informasi Pelanggan</h5>
                    <div class="row">
                        <div class="col-md-6">
                            <p class="mb-1"><b>Nama Pelanggan:</b> <?= htmlspecialchars($transaksi['nama_pelanggan']) ?></p>
                            <p class="mb-1"><b>Email:</b> <?= htmlspecialchars($transaksi['email']) ?></p>
                            <p class="mb-1"><b>Telepon:</b> <?= htmlspecialchars($transaksi['telepon']) ?></p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-1"><b>Nama Penerima:</b> <?= htmlspecialchars($transaksi['nama_penerima']) ?></p>
                            <p class="mb-1"><b>Telepon Penerima:</b> <?= htmlspecialchars($transaksi['telepon_penerima']) ?></p>
                            <p class="mb-1"><b>Metode Pembayaran:</b> <?= htmlspecialchars($transaksi['metode_pembayaran']) ?></p>
                            <p class="mb-1"><b>Metode Pengiriman:</b> <?= htmlspecialchars($transaksi['metode_pengiriman']) ?></p>
                        </div>
                    </div>
                    <h5 class="card-title mt-4 mb-2">Alamat Pengiriman</h5>
                    <p class="mb-1">
                        <?= htmlspecialchars($transaksi['alamat_penerima']) ?>, 
                        <?= htmlspecialchars($transaksi['district']) ?>, 
                        <?= htmlspecialchars($transaksi['regency']) ?>, 
                        <?= htmlspecialchars($transaksi['provinsi']) ?> <?= htmlspecialchars($transaksi['kode_pos']) ?>
                    </p>
                    <p class="text-muted">Catatan: <?= htmlspecialchars($transaksi['catatan'] ?? 'Tidak ada catatan') ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h5 class="card-title mb-3">Update Status Transaksi</h5>
                    <div class="d-flex align-items-center mb-3">
                        <i class="bi <?= $status_map[$current_status]['icon'] ?> fs-2 me-2 text-<?= $status_map[$current_status]['color'] ?>"></i>
                        <h4 class="m-0 text-<?= $status_map[$current_status]['color'] ?>"><?= $status_map[$current_status]['text'] ?></h4>
                    </div>
                    <form action="detail_transaksi.php?id=<?= $id_transaksi ?>" method="POST">
                        <div class="mb-3">
                            <label for="status" class="form-label">Ubah Status</label>
                            <select name="status" id="status" class="form-select">
                                <?php foreach ($status_list as $status): ?>
                                    <option value="<?= $status ?>" <?= ($current_status === $status) ? 'selected' : '' ?>>
                                        <?= $status_map[$status]['text'] ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" name="update_status" class="btn btn-primary btn-status-update">Update Status</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabel Produk -->
    <div class="card shadow-sm">
        <div class="card-body">
            <h5 class="card-title mb-3">Produk yang Dipesan</h5>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Produk</th>
                            <th>Jumlah</th>
                            <th>Harga Satuan</th>
                            <th>Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $no = 1;
                        $total_produk = 0;
                        foreach ($items as $item): 
                            $subtotal = $item['jumlah'] * $item['harga'];
                            $total_produk += $subtotal;
                        ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <img src="uploads/<?= htmlspecialchars($item['foto']) ?>" alt="<?= htmlspecialchars($item['nama_produk']) ?>" class="order-item-img me-2" onerror="this.src='https://placehold.co/80x80'">
                                        <div class="fw-bold"><?= htmlspecialchars($item['nama_produk']) ?></div>
                                    </div>
                                </td>
                                <td><?= htmlspecialchars($item['jumlah']) ?></td>
                                <td>Rp <?= number_format($item['harga'], 0, ',', '.') ?></td>
                                <td>Rp <?= number_format($subtotal, 0, ',', '.') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="table-light fw-bold">
                            <td colspan="4" class="text-end">Total Harga Produk</td>
                            <td>Rp <?= number_format($total_produk, 0, ',', '.') ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

</div> <!-- /main-content -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
