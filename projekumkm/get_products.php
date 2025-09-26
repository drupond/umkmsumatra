<?php
include 'db.php';
include 'functions.php';

// Ambil parameter dari request AJAX
$kategoriId = $_GET['kategori_id'] ?? 'semua';
$keyword = $_GET['q'] ?? '';

// Bangun query SQL
$where = "WHERE 1=1";
if ($kategoriId !== 'semua') {
    $where .= " AND id_kategori = " . intval($kategoriId);
}
if ($keyword !== '') {
    $safeKeyword = mysqli_real_escape_string($conn, $keyword);
    $where .= " AND nama LIKE '%$safeKeyword%'";
}

$query = "SELECT * FROM tb_produk " . $where . " ORDER BY id DESC";
$res = mysqli_query($conn, $query);

// Tampilkan produk jika ada
if (mysqli_num_rows($res) > 0) {
    while ($p = mysqli_fetch_assoc($res)) {
?>
<div class="col">
    <div class="card shadow-sm h-100">
        <img src="admin/uploads/<?= htmlspecialchars($p['foto']) ?>" alt="<?= htmlspecialchars($p['nama']) ?>" class="card-img-top" onerror="this.src='https://placehold.co/300x225'">
        <div class="card-body d-flex flex-column">
            <h5 class="card-title"><?= highlightKeyword($p['nama'], $keyword) ?></h5>
            <div class="d-flex align-items-center mb-2 mt-auto">
                <div class="text-warning me-1">
                    <i class="fas fa-star"></i>
                    <i class="fas fa-star"></i>
                    <i class="fas fa-star"></i>
                    <i class="fas fa-star"></i>
                    <i class="fas fa-star-half-alt"></i>
                </div>
                <small class="text-muted">(123 ulasan)</small>
            </div>
            <p class="card-text"><strong class="text-danger fs-5">Rp <?= number_format($p['harga'], 0, ',', '.') ?></strong></p>
            <div class="d-flex align-items-center gap-2 mt-2">
                <a href="detail.php?id=<?= $p['id'] ?>" class="btn btn-outline-secondary btn-sm flex-grow-1">Detail</a>
                <button class="btn btn-sm btn-danger add-to-cart flex-grow-1" data-id="<?= $p['id'] ?>">
                    <i class="bi bi-cart3"></i> Tambah
                </button>
            </div>
        </div>
    </div>
</div>
<?php
    }
} else {
?>
<div class="col-12 text-center text-muted py-5">
    <h4>Produk tidak ditemukan.</h4>
    <p>Coba kata kunci lain atau reset filter.</p>
</div>
<?php
}