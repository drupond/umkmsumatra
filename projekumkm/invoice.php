<?php
session_start();
include 'db.php';

// --- Ambil transaksi ---
$id_transaksi = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id_transaksi <= 0) die("Transaksi tidak valid.");

// Ambil data transaksi
$sql = "SELECT t.*, u.nama AS nama_pelanggan, u.telepon, u.email 
        FROM tb_transaksi t 
        JOIN tb_user u ON t.id_user = u.id 
        WHERE t.id_transaksi = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_transaksi);
$stmt->execute();
$res = $stmt->get_result();
$transaksi = $res->fetch_assoc();
$stmt->close();

if (!$transaksi) die("Data transaksi tidak ditemukan.");

// --- Ambil detail produk ---
$sqlDetail = "SELECT d.*, p.nama, p.harga as harga_produk, p.foto 
              FROM tb_detail d 
              JOIN tb_produk p ON d.id_produk = p.id 
              WHERE d.id_transaksi = ?";
$stmt = $conn->prepare($sqlDetail);
$stmt->bind_param("i", $id_transaksi);
$stmt->execute();
$resDetail = $stmt->get_result();
$items = [];
while ($row = $resDetail->fetch_assoc()) {
    $row['subtotal'] = $row['harga'] * $row['jumlah'];
    $items[] = $row;
}
$stmt->close();

// Hitung subtotal produk dari detail transaksi
$total_produk = 0;
foreach($items as $it) {
    $total_produk += $it['subtotal'];
}

// Perbaikan: Menghitung ongkir dan biaya layanan dari total yang tersimpan
// Asumsi: biaya layanan tetap 2000
$biaya_layanan = 2000;

// Logika untuk menentukan biaya ongkir berdasarkan metode pengiriman
$opsi_pengiriman_harga = [
    'Hemat Kargo' => 3500,
    'Reguler'     => 6500
];
$metode_pengiriman = $transaksi['metode_pengiriman'] ?? 'Reguler';
$ongkir = $opsi_pengiriman_harga[$metode_pengiriman] ?? 0;

$total_keseluruhan = $transaksi['total'] ?? $total_produk + $ongkir + $biaya_layanan;
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Invoice #<?= $id_transaksi ?> - MinangMaknyus</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet"/>
<style>
    body { font-family: Arial, sans-serif; font-size: 13px; background:#f8f8f8; padding:20px; }
    .invoice-box { position: relative; max-width: 900px; margin:auto; background:#fff; padding:20px; border:1px solid #ddd; box-shadow:0 0 10px rgba(0,0,0,0.15); border-radius:8px; }
    .header { display:flex; justify-content:space-between; align-items:center; border-bottom:2px solid #dc3545; padding-bottom:10px; margin-bottom:20px; }
    .header .logo-text { font-size: 2rem; font-weight: 700; color: #dc3545; }
    .header .maknyus { color: #ffc107; font-style: italic; }
    h2 { margin:0; color:#dc3545; }
    .info p { margin:2px 0; }
    .table { width:100%; border-collapse: collapse; margin-top:15px; }
    .table th, .table td { border:1px solid #000; padding:6px; text-align:center; }
    .table th { background:#f5f5f5; }
    .total { font-weight:bold; color:#d00; }
    .footer { margin-top:30px; font-size:12px; text-align:center; color:#777; }
    .btn-cetak { display:inline-block; padding:8px 12px; background:#dc3545; color:#fff; border-radius:4px; text-decoration:none; margin-top:15px; border:none; cursor:pointer; }
    .btn-cetak:hover { background: #d33c46; }
    .lunas-stamp {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%) rotate(-30deg);
        color: rgba(220, 53, 69, 0.2);
        font-size: 8em;
        font-weight: bold;
        text-transform: uppercase;
        z-index: 1000;
        pointer-events: none;
    }
    .product-image {
        width: 40px;
        height: 40px;
        object-fit: cover;
        border-radius: 4px;
        margin-right: 10px;
    }
    @media print { 
        .btn-cetak { display:none; }
        .lunas-stamp {
            color: rgba(220, 53, 69, 0.4);
        }
    }
</style>
</head>
<body>
<div class="invoice-box">
    <?php if (($transaksi['status_pengiriman'] ?? '') === 'diterima'): ?>
        <div class="lunas-stamp">LUNAS</div>
    <?php endif; ?>
    <div class="header">
        <div>
            <div class="logo-text">Minang<span class="maknyus">Maknyus</span></div>
            <small>No. Invoice: INV-<?= htmlspecialchars($transaksi['id_transaksi']) ?></small><br>
            <small>Tanggal: <?= date('d F Y H:i:s', strtotime($transaksi['tanggal'])) ?></small>
        </div>
        <div>
            <h2>Invoice Pembelian</h2>
        </div>
    </div>

    <div class="info">
        <p><b>Nama Pelanggan:</b> <?= htmlspecialchars($transaksi['nama_penerima'] ?? '') ?></p>
        <p><b>Telepon:</b> <?= htmlspecialchars($transaksi['nohp'] ?? '') ?></p>
        <p><b>Email:</b> <?= htmlspecialchars($transaksi['email'] ?? '') ?></p>
        <p><b>Alamat Pengiriman:</b> <?= htmlspecialchars($transaksi['alamat'] ?? '-') ?></p>
        <p><b>Metode Pembayaran:</b> <?= htmlspecialchars($transaksi['metode_pembayaran'] ?? '-') ?></p>
        <p><b>Metode Pengiriman:</b> <?= htmlspecialchars($transaksi['metode_pengiriman'] ?? '-') ?></p>
    </div>

    <table class="table">
        <thead>
            <tr>
                <th>Produk</th>
                <th>Qty</th>
                <th>Harga Satuan</th>
                <th>Subtotal</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach($items as $it): ?>
            <tr>
                <td align="left">
                    <div class="d-flex align-items-center">
                        <img src="admin/uploads/<?= htmlspecialchars($it['foto'] ?? '') ?>" alt="<?= htmlspecialchars($it['nama'] ?? '') ?>" class="product-image">
                        <span><?= htmlspecialchars($it['nama'] ?? '') ?></span>
                    </div>
                </td>
                <td><?= htmlspecialchars($it['jumlah']) ?></td>
                <td>Rp <?= number_format($it['harga_produk'],0,',','.') ?></td>
                <td>Rp <?= number_format($it['subtotal'],0,',','.') ?></td>
            </tr>
        <?php endforeach; ?>
            <tr>
                <td colspan="3" align="right"><b>Subtotal Produk</b></td>
                <td>Rp <?= number_format($total_produk,0,',','.') ?></td>
            </tr>
            <tr>
                <td colspan="3" align="right"><b>Ongkos Kirim</b></td>
                <td>Rp <?= number_format($ongkir,0,',','.') ?></td>
            </tr>
            <tr>
                <td colspan="3" align="right"><b>Biaya Layanan</b></td>
                <td>Rp <?= number_format($biaya_layanan,0,',','.') ?></td>
            </tr>
            <tr>
                <td colspan="3" align="right"><b>Total Pembayaran</b></td>
                <td class="total">Rp <?= number_format($total_keseluruhan,0,',','.') ?></td>
            </tr>
        </tbody>
    </table>

    <div style="text-align:right; margin-top:20px;">
        <button class="btn-cetak" onclick="window.print()"><i class="fa-solid fa-print"></i> Cetak</button>
        <a href="generate_pdf.php?id=<?= $id_transaksi ?>" class="btn-cetak" target="_blank">
            <i class="fa-solid fa-download"></i> Unduh PDF
        </a>
    </div>

    <div class="footer">
        Terima kasih telah berbelanja di <b>MinangMaknyus</b> ❤️<br>
        Support: support@minangmaknyus.com | WhatsApp: 0811 2173 1734
    </div>
</div>
</body>
</html>