<?php
// Mencegah session_start() dipanggil berulang kali
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Fungsi untuk menghitung total item di keranjang
function getCartCount($keranjang) {
    $count = 0;
    if (is_array($keranjang)) {
        foreach ($keranjang as $item) {
            $count += $item['qty'];
        }
    }
    return $count;
}

// Fungsi untuk menyorot kata kunci dalam teks
function highlightKeyword($text, $keyword) {
    if (!$keyword) {
        return htmlspecialchars($text);
    }
    return preg_replace(
        "/(" . preg_quote($keyword, "/") . ")/i",
        "<mark style='background-color:#ffc107; color:#212529'>$1</mark>",
        htmlspecialchars($text)
    );
}

// Fungsi untuk mengambil semua kategori
function getKategoriList($conn) {
    $kategoriList = [];
    $resKat = mysqli_query($conn, "SELECT * FROM tb_kategori ORDER BY nama_kategori ASC");
    while ($row = mysqli_fetch_assoc($resKat)) {
        $kategoriList[] = $row;
    }
    return $kategoriList;
}