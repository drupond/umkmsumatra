<?php
session_start();
include 'db.php';

// --- Ambil kategori ---
$kategoriList = [];
$resKat = mysqli_query($conn, "SELECT * FROM tb_kategori ORDER BY nama_kategori ASC");
while ($row = mysqli_fetch_assoc($resKat)) {
    $kategoriList[] = $row;
}

// --- Filter produk ---
$where = "WHERE 1=1";
$keyword = $_GET['q'] ?? '';
$kat = $_GET['kat'] ?? '';

if (!empty($keyword)) {
    $where .= " AND nama_produk LIKE '%" . mysqli_real_escape_string($conn, $keyword) . "%'";
}
if (!empty($kat)) {
    $where .= " AND id_kategori = '" . mysqli_real_escape_string($conn, $kat) . "'";
}

// --- Pagination ---
$limit = 6; // produk per halaman
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

// Hitung total produk
$resCount = mysqli_query($conn, "SELECT COUNT(*) as total FROM tb_produk $where");
$totalData = mysqli_fetch_assoc($resCount)['total'];
$totalPage = ceil($totalData / $limit);

// Ambil produk
$sql = "SELECT * FROM tb_produk $where ORDER BY id_produk DESC LIMIT $limit OFFSET $offset";
$resProd = mysqli_query($conn, $sql);

$produk = [];
while ($row = mysqli_fetch_assoc($resProd)) {
    $produk[] = $row;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Produk - MinangMaknyus</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container py-4">
    <h2>Produk Terbaru</h2>

    <!-- Produk -->
    <div class="row row-cols-1 row-cols-md-3 g-4">
        <?php foreach ($produk as $p): ?>
            <div class="col">
                <div class="card h-100">
                    <img src="uploads/<?php echo $p['foto']; ?>" class="card-img-top">
                    <div class="card-body">
                        <h5><?php echo $p['nama_produk']; ?></h5>
                        <p>Rp <?php echo number_format($p['harga']); ?></p>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Pagination -->
    <nav class="mt-4">
        <ul class="pagination justify-content-center">
            <?php if ($page > 1): ?>
                <li class="page-item"><a class="page-link" href="?page=<?php echo $page-1; ?>">Sebelumnya</a></li>
            <?php endif; ?>

            <?php for ($i = 1; $i <= $totalPage; $i++): ?>
                <li class="page-item <?php if ($i == $page) echo 'active'; ?>">
                    <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                </li>
            <?php endfor; ?>

            <?php if ($page < $totalPage): ?>
                <li class="page-item"><a class="page-link" href="?page=<?php echo $page+1; ?>">Selanjutnya</a></li>
            <?php endif; ?>
        </ul>
    </nav>
</div>
</body>
</html>
