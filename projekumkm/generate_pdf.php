<?php
require_once 'vendor/autoload.php';

use Dompdf\Dompdf;

session_start();
include 'db.php';

// --- Ambil transaksi ---
$id_transaksi = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id_transaksi <= 0) die("Transaksi tidak valid.");

// Ambil data transaksi + user
$sql = "SELECT t.*, u.nama AS nama_pelanggan, u.telepon, u.email, u.alamat 
        FROM tb_transaksi t 
        JOIN tb_user u ON t.id_pelanggan = u.id 
        WHERE t.id_transaksi = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_transaksi);
$stmt->execute();
$res = $stmt->get_result();
$transaksi = $res->fetch_assoc();
$stmt->close();

if (!$transaksi) die("Data transaksi tidak ditemukan.");

// --- Ambil detail produk ---
$sqlDetail = "SELECT d.*, p.nama, p.harga as harga_produk 
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

// Hitung total dari detail produk
$total_produk = 0;
foreach($items as $it) {
    $total_produk += $it['subtotal'];
}

$ongkir = 3500; 
$biaya_layanan = 2000;
$total_keseluruhan = $total_produk + $ongkir + $biaya_layanan;

// --- DOMPDF: Buat HTML untuk PDF ---
ob_start();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Invoice #<?= $id_transaksi ?> - MinangMaknyus</title>
<style>
    body { font-family: Arial, sans-serif; font-size: 11px; margin: 0; padding: 20px; }
    .invoice-box { max-width: 800px; margin: auto; padding: 20px; border: 1px solid #ddd; box-shadow: 0 0 10px rgba(0,0,0,0.15); border-radius: 8px; }
    .header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #dc3545; padding-bottom: 10px; margin-bottom: 20px; }
    .header img { height: 40px; }
    h2 { margin: 0; color: #dc3545; }
    .info p { margin: 2px 0; }
    .table { width: 100%; border-collapse: collapse; margin-top: 15px; }
    .table th, .table td { border: 1px solid #000; padding: 6px; text-align: center; }
    .table th { background: #f5f5f5; }
    .total { font-weight: bold; color: #d00; }
    .footer { margin-top: 30px; font-size: 10px; text-align: center; color: #777; }
    .lunas-stamp {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%) rotate(-30deg);
        color: rgba(220, 53, 69, 0.4);
        font-size: 8em;
        font-weight: bold;
        text-transform: uppercase;
        z-index: 1000;
        pointer-events: none;
    }
</style>
</head>
<body>
    <?php if ($transaksi['status_pengiriman'] === 'diterima'): ?>
        <div class="lunas-stamp">LUNAS</div>
    <?php endif; ?>
    <div class="invoice-box">
        <div class="header">
            <div>
                <h2>Invoice Pembelian</h2>
                <small>No. Invoice: INV-<?= htmlspecialchars($id_transaksi) ?></small><br>
                <small>Tanggal: <?= date('d F Y H:i:s', strtotime($transaksi['tanggal'])) ?></small>
            </div>
            <div>
                <img src="assets/img/logo.png" alt="Logo MinangMaknyus">
            </div>
        </div>

        <div class="info">
            <p><b>Nama Pelanggan:</b> <?= htmlspecialchars($transaksi['nama_pelanggan']) ?></p>
            <p><b>Telepon:</b> <?= htmlspecialchars($transaksi['telepon']) ?></p>
            <p><b>Email:</b> <?= htmlspecialchars($transaksi['email']) ?></p>
            <p><b>Alamat Pengiriman:</b> <?= htmlspecialchars($transaksi['alamat'] ?? '-') ?></p>
            <p><b>Metode Pembayaran:</b> <?= htmlspecialchars($transaksi['metode_pembayaran']) ?></p>
            <p><b>Metode Pengiriman:</b> <?= htmlspecialchars($transaksi['metode_pengiriman']) ?></p>
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
                    <td align="left"><?= htmlspecialchars($it['nama']) ?></td>
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

        <div class="footer">
            Terima kasih telah berbelanja di <b>MinangMaknyus</b> ❤️<br>
            Support: support@minangmaknyus.com | WhatsApp: 0811 2173 1734
        </div>
    </div>
</body>
</html>
<?php
$html = ob_get_clean();

$dompdf = new Dompdf();

// Set opsi untuk mengaktifkan akses file
$options = $dompdf->getOptions();
$options->set('isRemoteEnabled', true);
$options->set('chroot', $_SERVER['DOCUMENT_ROOT']);
$dompdf->setOptions($options);

$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

$dompdf->stream("Invoice-INV-{$id_transaksi}.pdf", ["Attachment" => true]);
?>