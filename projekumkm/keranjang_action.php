<?php
session_start();
header('Content-Type: application/json');
include 'db.php';

$response = ['ok' => false, 'msg' => '', 'subtotal' => 0, 'total' => 0, 'cart_count' => 0];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_qty'])) {
    $id_produk = intval($_POST['id']);
    $qty_baru = intval($_POST['qty']);
    $size = htmlspecialchars($_POST['size']);
    $item_key = $id_produk . '_' . $size;

    if (!isset($_SESSION['keranjang'][$item_key])) {
        $response['msg'] = "Produk tidak ditemukan di keranjang.";
        echo json_encode($response);
        exit;
    }

    if ($qty_baru <= 0) {
        // Hapus item jika kuantitasnya 0 atau kurang
        unset($_SESSION['keranjang'][$item_key]);
    } else {
        // Periksa stok dari database
        $res = mysqli_query($conn, "SELECT stok FROM tb_produk WHERE id = $id_produk");
        $produk_db = mysqli_fetch_assoc($res);

        if ($qty_baru > $produk_db['stok']) {
            $response['msg'] = "Stok produk tidak mencukupi.";
            echo json_encode($response);
            exit;
        }

        $_SESSION['keranjang'][$item_key]['qty'] = $qty_baru;
    }

    // Hitung ulang total dan subtotal
    $total = 0;
    $subtotal_item = 0;
    $cart_count = 0;
    
    foreach ($_SESSION['keranjang'] as $key => $item) {
        $harga = $item['harga'] ?? 0;
        $qty = $item['qty'] ?? 0;
        $total += $harga * $qty;
        $cart_count += $qty;

        if ($key === $item_key) {
            $subtotal_item = $harga * $qty;
        }
    }

    $response['ok'] = true;
    $response['msg'] = 'Kuantitas berhasil diperbarui.';
    $response['subtotal'] = $subtotal_item;
    $response['total'] = $total;
    $response['cart_count'] = $cart_count;
}

echo json_encode($response);
?>