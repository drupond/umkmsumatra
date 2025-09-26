<?php
session_start();
include 'db.php';

// Pastikan ada produk yang dikirim
if (!isset($_GET['produk'])) {
    header("Location: index.php");
    exit;
}

$id_produk = intval($_GET['produk']);

// Ambil data produk dari database
$stmt = $conn->prepare("SELECT * FROM tb_produk WHERE id = ?");
$stmt->bind_param("i", $id_produk);
$stmt->execute();
$result = $stmt->get_result();
$produk = $result->fetch_assoc();
$stmt->close();

if (!$produk) {
    // Jika produk tidak ditemukan, kembali ke halaman utama
    header("Location: index.php");
    exit;
}

// Tambahkan produk ke keranjang
if (!isset($_SESSION['keranjang'])) {
    $_SESSION['keranjang'] = [];
}

$found = false;
foreach ($_SESSION['keranjang'] as $key => $item) {
    if ($item['id'] == $id_produk) {
        $_SESSION['keranjang'][$key]['qty']++;
        $found = true;
        break;
    }
}

// Jika produk belum ada di keranjang, tambahkan sebagai item baru
if (!$found) {
    $_SESSION['keranjang'][] = [
        'id' => $produk['id'],
        'nama' => $produk['nama'],
        'harga' => $produk['harga'],
        'qty' => 1
    ];
}

// Redirect langsung ke halaman keranjang
header("Location: keranjang.php");
exit;
?>
