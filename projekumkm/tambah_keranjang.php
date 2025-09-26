<?php
session_start();
include 'db.php';

header('Content-Type: application/json');

$response = [
    'status' => 'error',
    'message' => 'Gagal menambahkan produk ke keranjang.',
    'cart_count' => 0
];

if (isset($_POST['id_produk']) && is_numeric($_POST['id_produk'])) {
    $id_produk = intval($_POST['id_produk']);
    $qty = isset($_POST['qty']) ? intval($_POST['qty']) : 1;
    
    // Asumsi Anda memiliki tabel tb_produk dan id_produk valid
    $query = "SELECT * FROM tb_produk WHERE id = $id_produk";
    $result = mysqli_query($conn, $query);
    
    if (mysqli_num_rows($result) > 0) {
        $product = mysqli_fetch_assoc($result);

        if (!isset($_SESSION['keranjang'])) {
            $_SESSION['keranjang'] = [];
        }

        if (isset($_SESSION['keranjang'][$id_produk])) {
            // Produk sudah ada, tambahkan kuantitas
            $_SESSION['keranjang'][$id_produk]['qty'] += $qty;
        } else {
            // Produk belum ada, tambahkan produk baru
            $_SESSION['keranjang'][$id_produk] = [
                'id_produk' => $id_produk,
                'nama' => $product['nama'],
                'harga' => $product['harga'],
                'qty' => $qty,
                'foto' => $product['foto']
            ];
        }

        $cart_count = 0;
        foreach ($_SESSION['keranjang'] as $item) {
            $cart_count += $item['qty'];
        }

        $response['status'] = 'success';
        $response['message'] = 'Produk berhasil ditambahkan ke keranjang.';
        $response['cart_count'] = $cart_count;

    } else {
        $response['message'] = 'Produk tidak ditemukan.';
    }

} else {
    $response['message'] = 'ID produk tidak valid.';
}

echo json_encode($response);