<?php
session_start();
include 'db.php';

// --- Bagian Logika PHP ---

// Pastikan user login
if (!isset($_SESSION['username'])) {
    header("Location: auth/login.php");
    exit;
}

// Ambil data user
$username = $_SESSION['username'];
$stmt = $conn->prepare("SELECT * FROM tb_user WHERE username = ? LIMIT 1");
$stmt->bind_param("s", $username);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    header("Location: auth/login.php");
    exit;
}

// Ambil data alamat dari database
$alamatList = [];
$stmt = $conn->prepare("SELECT a.*, p.name AS provinsi_name, r.name AS regency_name, d.name AS district_name
                         FROM tb_alamat a
                         LEFT JOIN tb_provinces p ON a.provinsi_id = p.id
                         LEFT JOIN tb_regencies r ON a.regency_id = r.id
                         LEFT JOIN tb_districts d ON a.district_id = d.id
                         WHERE a.id_user = ? ORDER BY utama DESC, a.id DESC");
$stmt->bind_param("i", $user['id']);
$stmt->execute();
$res = $stmt->get_result();
while($row = $res->fetch_assoc()) {
    $alamatList[] = $row;
}
$stmt->close();

$alamat_utama = $alamatList[0] ?? null;

// Ambil keranjang dari session
$keranjang = $_SESSION['keranjang'] ?? [];

// Jika ada produk yang dibeli langsung dari index.php
if (isset($_GET['produk_id'])) {
    $id_produk = intval($_GET['produk_id']);
    $stmt = $conn->prepare("SELECT * FROM tb_produk WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $id_produk);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($row) {
        $_SESSION['keranjang'] = [
            $row['id'] => [
                'id' => $row['id'], 
                'nama' => $row['nama'], 
                'harga'=> $row['harga'], 
                'qty' => 1, 
                'foto' => $row['foto']
            ]
        ];
        header("Location: checkout.php");
        exit;
    } else {
        header("Location: index.php?msg=Produk tidak ditemukan.");
        exit;
    }
}

if (empty($keranjang)) {
    header("Location: index.php?msg=Keranjang kosong.");
    exit;
}

$subtotal_produk = 0;
foreach ($keranjang as $item) {
    $subtotal_produk += ($item['harga'] ?? 0) * ($item['qty'] ?? 0);
}

// Atur opsi pengiriman
$opsi_pengiriman = [
    'Hemat Kargo' => ['ongkir' => 3500, 'estimasi' => '14 - 15 Sep'],
    'Reguler'     => ['ongkir' => 6500, 'estimasi' => '13 - 14 Sep']
];

// Set default pengiriman jika tidak ada di session
if (!isset($_SESSION['pengiriman'])) {
    $_SESSION['pengiriman'] = 'Hemat Kargo';
    $_SESSION['ongkir'] = $opsi_pengiriman['Hemat Kargo']['ongkir'];
}

// Perbarui data pengiriman jika dikirim dari form modal
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_shipping') {
    $pengiriman_baru = htmlspecialchars(trim($_POST['pengiriman_final']));
    if (isset($opsi_pengiriman[$pengiriman_baru])) {
        $_SESSION['pengiriman'] = $pengiriman_baru;
        $_SESSION['ongkir'] = $opsi_pengiriman[$pengiriman_baru]['ongkir'];
    }
    header("Location: checkout.php");
    exit;
}

$pengiriman_final = $_SESSION['pengiriman'];
$ongkir_final = $_SESSION['ongkir'];
$estimasi_final = $opsi_pengiriman[$pengiriman_final]['estimasi'];
$biaya_layanan = 2000;
$total_pembayaran = $subtotal_produk + $ongkir_final + $biaya_layanan;

// --- PROSES SIMPAN PESANAN KE DATABASE ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'buat_pesanan') {
    if (!$alamat_utama) {
        die("Alamat pengiriman belum diatur.");
    }

    $catatan = htmlspecialchars(trim($_POST['pesan'] ?? ''));
    $metode_bayar = htmlspecialchars(trim($_POST['metode'] ?? 'COD - Cek Dulu'));

    $nama_penerima = $alamat_utama['nama_penerima'];
    $nohp = $alamat_utama['telepon'];
    $alamat_detail = $alamat_utama['alamat_lengkap'] . ', ' . $alamat_utama['district_name'] . ', ' . $alamat_utama['regency_name'] . ', ' . $alamat_utama['provinsi_name'] . ', ID ' . $alamat_utama['kode_pos'];

    // Perbaikan: Hapus kolom 'ongkir' dan 'estimasi' dari query INSERT
    $stmt = $conn->prepare("INSERT INTO tb_transaksi (id_user, id_pelanggan, nama_penerima, nohp, alamat, tanggal, metode_pembayaran, metode_pengiriman, catatan, total) VALUES (?, ?, ?, ?, ?, NOW(), ?, ?, ?, ?)");
    $stmt->bind_param("iissssssi", $user['id'], $user['id'], $nama_penerima, $nohp, $alamat_detail, $metode_bayar, $pengiriman_final, $catatan, $total_pembayaran);

    if ($stmt->execute()) {
        $id_transaksi = $stmt->insert_id;
        $stmt->close();

        $stmt_detail = $conn->prepare("INSERT INTO tb_detail (id_transaksi, id_produk, jumlah, harga) VALUES (?, ?, ?, ?)");
        
        foreach ($keranjang as $item) {
            $id_produk = $item['id'];
            $qty = $item['qty'];
            $harga = $item['harga'];
            $stmt_detail->bind_param("iiii", $id_transaksi, $id_produk, $qty, $harga);
            $stmt_detail->execute();
        }
        $stmt_detail->close();

        unset($_SESSION['keranjang']);

        header("Location: invoice.php?id=" . $id_transaksi);
        exit;
    } else {
        die("Gagal menyimpan transaksi. Error: " . $conn->error);
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>MinangMaknyus Checkout</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <style>
        body {
            margin: 0;
            background-color: #f5f5f5;
            font-family: -apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,"Helvetica Neue",Arial,sans-serif,"Apple Color Emoji","Segoe UI Emoji","Segoe UI Symbol";
        }
        .main-wrapper {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        .header-bar {
            background-color: #fff;
            padding: 1rem 0;
            border-bottom: 1px solid #ddd;
        }
        .header-content {
            max-width: 900px;
            margin: auto;
            display: flex;
            align-items: center;
        }
        .header-logo {
            height: 30px;
            margin-right: 1rem;
        }
        .header-checkout-text {
            font-size: 1.5rem;
            color: #dc3545;
        }
        .content-wrapper {
            flex: 1;
            padding-top: 20px;
            padding-bottom: 20px;
        }
        .container-checkout {
            max-width: 900px;
            margin: auto;
            background-color: #fff;
            border-radius: 4px;
            box-shadow: 0 1px 2px rgba(0,0,0,.08);
        }
        .divider-bar {
            height: 5px;
            background: repeating-linear-gradient(-45deg,#f36253,#f36253 5px,#96b7da 5px,#96b7da 10px);
        }
        .section-address,
        .section-products,
        .shipping-details,
        .payment-methods-section,
        .summary-section {
            padding: 24px;
            border-bottom: 1px solid rgba(0,0,0,.09);
        }
        .section-address .address-header,
        .section-products .product-header,
        .shipping-details .shipping-header,
        .payment-methods-section .payment-header {
            display: flex;
            align-items: center;
            font-size: 1rem;
            font-weight: 500;
            color: #dc3545;
        }
        .address-info {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-top: 12px;
        }
        .address-name {
            font-weight: 600;
        }
        .address-tag {
            background-color: #dc3545;
            color: white;
            padding: 2px 6px;
            border-radius: 2px;
            font-size: 0.8rem;
        }
        .address-ubah, .shipping-ubah {
            color: #007bff;
            text-decoration: none;
        }
        .product-item-header {
            display: flex;
            justify-content: space-between;
            font-weight: 500;
            color: #757575;
            font-size: 0.9rem;
            border-bottom: 1px solid #f0f0f0;
            padding-bottom: 10px;
        }
        .product-item-header .price-details,
        .product-item-row .price-details {
            display: flex;
            width: 50%;
            text-align: center;
        }
        .product-item-header .price-details > div,
        .product-item-row .price-details > div {
            flex: 1;
        }
        .product-item-row {
            display: flex;
            align-items: center;
            padding-top: 15px;
            padding-bottom: 15px;
        }
        .product-item-row img {
            width: 40px;
            height: 40px;
            object-fit: cover;
            margin-right: 15px;
        }
        .product-name {
            font-weight: 500;
            flex: 1;
        }
        .product-note {
            padding-top: 15px;
            font-size: 0.9rem;
            color: #757575;
            border-top: 1px dashed #e0e0e0;
        }
        .product-note input {
            border: 1px solid #e0e0e0;
            border-radius: 4px;
            padding: 8px;
            width: 100%;
        }
        .shipping-info-wrapper {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 12px;
        }
        .shipping-cost {
            font-weight: bold;
        }
        .payment-options {
            display: flex;
            flex-wrap: wrap;
            margin-top: 15px;
        }
        .payment-btn {
            border: 1px solid #e0e0e0;
            background-color: white;
            color: #333;
            padding: 8px 16px;
            border-radius: 4px;
            font-size: 0.9rem;
            margin-right: 8px;
            margin-bottom: 8px;
            cursor: pointer;
        }
        .payment-btn.active {
            border-color: #dc3545;
            color: #dc3545;
            font-weight: bold;
        }
        .summary-section {
            background-color: #fcfcfc;
        }
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            font-size: 0.9rem;
        }
        .summary-row .label {
            color: #757575;
        }
        .summary-row .value {
            font-weight: 500;
        }
        .summary-total {
            border-top: 1px dashed #e0e0e0;
            padding-top: 15px;
            margin-top: 15px;
            font-size: 1.1rem;
        }
        .summary-total .label {
            font-weight: 500;
        }
        .summary-total .value {
            font-weight: bold;
            color: #dc3545;
            font-size: 1.5rem;
        }
        .submit-button-container {
            padding: 24px;
            text-align: right;
            border-top: 1px solid #f0f0f0;
        }
        .submit-btn {
            background-color: #dc3545;
            color: white;
            padding: 12px 48px;
            border: none;
            border-radius: 4px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
        }
        /* Modal Styles */
        .modal-content { border-radius: 8px; }
        .modal-header h5 { font-weight: bold; }
        .shipping-option { border: 1px solid #e0e0e0; border-radius: 8px; padding: 15px; margin-bottom: 10px; cursor: pointer; transition: border-color 0.2s; position: relative;}
        .shipping-option:hover { border-color: #dc3545; }
        .shipping-option.selected { border-color: #dc3545; background-color: #fffaf0; }
        .shipping-option .check-icon { display: none; color: #dc3545; font-size: 1.5rem; position: absolute; right: 15px; top: 50%; transform: translateY(-50%); }
        .shipping-option.selected .check-icon { display: block; }
    </style>
</head>
<body>
    <div class="main-wrapper">
        <header class="header-bar">
            <div class="header-content">
                <img src="assets/img/logo.png" alt="MinangMaknyus Logo" class="header-logo">
                <span style="border-left: 1px solid #e0e0e0; padding-left: 1rem;"></span>
                <span class="header-checkout-text">Checkout</span>
            </div>
        </header>

        <div class="content-wrapper">
            <div class="container-checkout">
                <div class="divider-bar"></div>
                <div class="section-address">
                    <div class="address-header">
                        <svg height="16" viewBox="0 0 12 16" width="12" style="margin-right: 8px;"><path d="M6 3.2c1.506 0 2.727 1.195 2.727 2.667 0 1.473-1.22 2.666-2.727 2.666S3.273 7.34 3.273 5.867C3.273 4.395 4.493 3.2 6 3.2zM0 6c0-3.315 2.686-6 6-6s6 2.685 6 6c0 2.498-1.964 5.742-6 9.933C1.613 11.743 0 8.498 0 6z" fill-rule="evenodd" fill="#dc3545"></path></svg>
                        Alamat Pengiriman
                    </div>
                    <?php if ($alamat_utama): ?>
                    <div class="address-info">
                        <div class="address-details">
                            <div>
                                <span class="address-name"><?= htmlspecialchars($alamat_utama['nama_penerima'] ?? '') ?></span> (+62 <?= htmlspecialchars($alamat_utama['telepon'] ?? '') ?>)
                            </div>
                            <div>
                                <?= htmlspecialchars($alamat_utama['alamat_lengkap'] ?? '') ?>, <?= htmlspecialchars($alamat_utama['district_name'] ?? '') ?>, <?= htmlspecialchars($alamat_utama['regency_name'] ?? '') ?>, <?= htmlspecialchars($alamat_utama['provinsi_name'] ?? '') ?>, ID <?= htmlspecialchars($alamat_utama['kode_pos'] ?? '') ?>
                                <span class="address-tag">Utama</span>
                            </div>
                        </div>
                        <div>
                            <a href="alamat.php" class="address-ubah">Ubah</a>
                        </div>
                    </div>
                    <?php else: ?>
                    <p>Belum ada alamat utama yang terdaftar.</p>
                    <a href="alamat.php" class="address-ubah">Tambah Alamat</a>
                    <?php endif; ?>
                </div>
                
                <div class="section-products">
                    <div class="product-item-header">
                        <div class="product-title">Produk Dipesan</div>
                        <div class="price-details">
                            <div>Harga Satuan</div>
                            <div>Jumlah</div>
                            <div>Subtotal Produk</div>
                        </div>
                    </div>
                    <?php 
                    $produk_checkout = [];
                    foreach ($keranjang as $item_key => $item) {
                        $produk_id = intval($item_key); 
                        $stmt = $conn->prepare("SELECT * FROM tb_produk WHERE id = ?");
                        $stmt->bind_param("i", $produk_id);
                        $stmt->execute();
                        $row = $stmt->get_result()->fetch_assoc();
                        $stmt->close();
                        if ($row) {
                            $row['qty'] = $item['qty'];
                            $produk_checkout[] = $row;
                        }
                    }
                    ?>
                    <?php foreach ($produk_checkout as $item): ?>
                    <div class="product-item-row">
                        <img src="admin/uploads/<?= htmlspecialchars($item['foto'] ?? '') ?>" onerror="this.src='https://placehold.co/40x40/f0f0f0/gray'" />
                        <span class="product-name"><?= htmlspecialchars($item['nama'] ?? '') ?></span>
                        <div class="price-details">
                            <div>Rp<?= number_format($item['harga'] ?? 0, 0, ',', '.') ?></div>
                            <div><?= htmlspecialchars($item['qty'] ?? 0) ?></div>
                            <div>Rp<?= number_format(($item['harga'] ?? 0) * ($item['qty'] ?? 0), 0, ',', '.') ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <div class="product-note">
                        Pesanan: <input type="text" name="pesan" form="checkout-form" placeholder="(Opsional) Tinggalkan pesan">
                    </div>
                </div>

                <div class="shipping-details">
                    <div class="d-flex align-items-center justify-content-between w-100">
                        <div class="shipping-header">Opsi Pengiriman:</div>
                        <div class="d-flex align-items-center">
                            <a href="#" class="shipping-ubah" data-bs-toggle="modal" data-bs-target="#shippingModal" style="margin-right: 15px;">Ubah</a>
                            <span class="shipping-price">Rp<span id="display-ongkir"><?= number_format($ongkir_final, 0, ',', '.') ?></span></span>
                        </div>
                    </div>
                    <div style="margin-top: 10px;">
                        <span style="font-weight: 600;" id="pengiriman-text"><?= htmlspecialchars($pengiriman_final) ?></span>
                        <br>
                        <span style="color: #26aa99;">Garansi tiba: <span id="estimasi-text"><?= htmlspecialchars($estimasi_final) ?></span></span>
                    </div>
                </div>

                <div class="payment-methods-section">
                    <div style="font-weight: 500; color: #dc3545; margin-bottom: 15px;">Metode Pembayaran</div>
                    <div class="payment-options">
                        <button type="button" class="payment-btn active" data-metode="COD - Cek Dulu">COD - Cek Dulu</button>
                        <button type="button" class="payment-btn" data-metode="Transfer Bank">Transfer Bank</button>
                    </div>
                    <input type="hidden" name="metode" id="input-metode" form="checkout-form" value="COD - Cek Dulu">
                </div>
                
                <form action="checkout.php" method="post" id="checkout-form">
                    <input type="hidden" name="action" value="buat_pesanan">
                    <input type="hidden" name="pengiriman_final" id="pengiriman-final" value="<?= htmlspecialchars($pengiriman_final) ?>">
                    <input type="hidden" name="ongkir" id="input-ongkir" value="<?= htmlspecialchars($ongkir_final) ?>">
                    <input type="hidden" name="estimasi_final" id="estimasi-final" value="<?= htmlspecialchars($estimasi_final) ?>">
                    
                    <div class="summary-section">
                        <div class="summary-row">
                            <span class="label">Subtotal Pesanan</span>
                            <span class="value">Rp<?= number_format($subtotal_produk, 0, ',', '.') ?></span>
                        </div>
                        <div class="summary-row">
                            <span class="label">Subtotal Pengiriman</span>
                            <span class="value" id="display-ongkir-summary">Rp<?= number_format($ongkir_final, 0, ',', '.') ?></span>
                        </div>
                        <div class="summary-row">
                            <span class="label">Biaya Layanan</span>
                            <span class="value">Rp<?= number_format($biaya_layanan, 0, ',', '.') ?></span>
                        </div>
                        <div class="summary-total">
                            <div class="summary-row">
                                <span class="label">Total Pembayaran</span>
                                <span class="value" id="total-price">Rp<?= number_format($total_pembayaran, 0, ',', '.') ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="submit-button-container">
                        <button type="submit" class="submit-btn">Buat Pesanan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="shippingModal" tabindex="-1">
        <div class="modal-dialog modal-sm modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Pilih Opsi Pengiriman</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <?php foreach ($opsi_pengiriman as $nama_opsi => $data_opsi): ?>
                    <div class="shipping-option <?= ($pengiriman_final === $nama_opsi) ? 'selected' : ''; ?>" 
                         data-ongkir="<?= htmlspecialchars($data_opsi['ongkir']) ?>" 
                         data-pengiriman="<?= htmlspecialchars($nama_opsi) ?>" 
                         data-estimasi="<?= htmlspecialchars($data_opsi['estimasi']) ?>">
                        <div class="d-flex justify-content-between align-items-center w-100 position-relative">
                            <div>
                                <div class="fw-bold"><?= htmlspecialchars($nama_opsi) ?></div>
                                <small class="text-muted">Garansi tiba: <?= htmlspecialchars($data_opsi['estimasi']) ?></small>
                            </div>
                            <div class="fw-bold">Rp<?= number_format($data_opsi['ongkir'], 0, ',', '.') ?></div>
                            <i class="fa-solid fa-check-circle check-icon"></i>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div class="modal-footer d-flex justify-content-between">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Nanti Saja</button>
                    <button type="button" class="btn btn-danger" id="confirmShippingBtn">Konfirmasi</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const shippingModal = new bootstrap.Modal(document.getElementById('shippingModal'));
        const ongkirSummary = document.getElementById('display-ongkir-summary');
        const totalPrice = document.getElementById('total-price');

        document.querySelectorAll('.shipping-option').forEach(option => {
            option.addEventListener('click', () => {
                document.querySelectorAll('.shipping-option').forEach(o => o.classList.remove('selected'));
                option.classList.add('selected');
            });
        });

        document.getElementById('confirmShippingBtn').addEventListener('click', () => {
            const selectedOption = document.querySelector('.shipping-option.selected');
            const ongkir = selectedOption.getAttribute('data-ongkir');
            const pengiriman = selectedOption.getAttribute('data-pengiriman');
            const estimasi = selectedOption.getAttribute('data-estimasi');

            // Menggunakan AJAX untuk memperbarui sesi tanpa memuat ulang halaman
            fetch('checkout.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=update_shipping&pengiriman_final=${pengiriman}`
            }).then(() => {
                // Setelah sesi diperbarui, update tampilan di halaman
                document.getElementById('pengiriman-text').textContent = pengiriman;
                document.getElementById('estimasi-text').textContent = estimasi;
                document.getElementById('display-ongkir').textContent = formatRupiah(ongkir);
                document.getElementById('display-ongkir-summary').textContent = formatRupiah(ongkir);
                
                const subtotalProduk = <?= $subtotal_produk ?>;
                const biayaLayanan = <?= $biaya_layanan ?>;
                const newTotal = subtotalProduk + parseInt(ongkir) + biayaLayanan;
                totalPrice.textContent = formatRupiah(newTotal);

                // Update hidden input untuk form
                document.getElementById('pengiriman-final').value = pengiriman;
                document.getElementById('input-ongkir').value = ongkir;
                
                shippingModal.hide();
            });
        });

        document.querySelectorAll('.payment-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                document.querySelectorAll('.payment-btn').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                document.getElementById('input-metode').value = btn.getAttribute('data-metode');
            });
        });

        function formatRupiah(angka) {
            var number_string = angka.toString(),
                sisa = number_string.length % 3,
                rupiah = number_string.substr(0, sisa),
                ribuan = number_string.substr(sisa).match(/\d{3}/g);
            
            if (ribuan) {
                separator = sisa ? '.' : '';
                rupiah += separator + ribuan.join('.');
            }
            return 'Rp' + rupiah;
        }
    });
</script>
</body>
</html>