<?php
session_start();
include '../db.php'; // sesuaikan path jika perlu

// Optional: cek apakah admin login
// if (!isset($_SESSION['admin'])) { header('Location: ../auth/login.php'); exit; }

$uploadDir = __DIR__ . '/uploads/';
$publicUploadDir = 'uploads/'; // relative URL

// helper: sanitize
function safe($conn, $v) {
    return mysqli_real_escape_string($conn, trim($v));
}

// helper: flash message (JS SweetAlert)
$flash = [
    'type' => '', // success|error|info|warning
    'title' => '',
    'text' => ''
];

// ensure upload dir exists
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

/* ---------------------------
   Ambil list kategori (dipakai di form)
   --------------------------- */
$kategoriListRes = mysqli_query($conn, "SELECT * FROM tb_kategori ORDER BY id_kategori ASC");

/* ---------------------------
   HANDLE: TAMBAH produk
   --------------------------- */
if (isset($_POST['tambah'])) {
    $nama = safe($conn, $_POST['nama'] ?? '');
    $harga = intval($_POST['harga'] ?? 0);
    $stok = intval($_POST['stok'] ?? 0);
    // form mengirim id_kategori
    $id_kategori = (isset($_POST['id_kategori']) && $_POST['id_kategori'] !== '') ? intval($_POST['id_kategori']) : null;
    $deskripsi = safe($conn, $_POST['deskripsi'] ?? '');

    // file upload handling
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] !== UPLOAD_ERR_NO_FILE) {
        $file = $_FILES['foto'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png','gif','webp'];
        if (!in_array($ext, $allowed)) {
            $flash = ['type'=>'error','title'=>'Format tidak valid','text'=>'Gunakan file gambar (jpg, jpeg, png, gif, webp).'];
        } elseif ($file['size'] > 3 * 1024 * 1024) {
            $flash = ['type'=>'error','title'=>'File terlalu besar','text'=>'Ukuran maksimal 3MB.'];
        } else {
            $newName = time() . '_' . bin2hex(random_bytes(5)) . '.' . $ext;
            $target = $uploadDir . $newName;
            if (move_uploaded_file($file['tmp_name'], $target)) {
                // insert
                $sql = "INSERT INTO tb_produk (nama, harga, stok, id_kategori, deskripsi, foto)
                        VALUES (?, ?, ?, ?, ?, ?)";
                $stmt = mysqli_prepare($conn, $sql);
                if ($stmt) {
                    // bind param - treat id_kategori as integer or null
                    mysqli_stmt_bind_param($stmt, "siiiss", $nama, $harga, $stok, $id_kategori, $deskripsi, $newName);
                    if (mysqli_stmt_execute($stmt)) {
                        $flash = ['type'=>'success','title'=>'Berhasil','text'=>'Produk berhasil ditambahkan.'];
                    } else {
                        @unlink($target);
                        $flash = ['type'=>'error','title'=>'Database error','text'=>mysqli_error($conn)];
                    }
                    mysqli_stmt_close($stmt);
                } else {
                    @unlink($target);
                    $flash = ['type'=>'error','title'=>'Prepare error','text'=>mysqli_error($conn)];
                }
            } else {
                $flash = ['type'=>'error','title'=>'Upload gagal','text'=>'Tidak bisa menyimpan file.'];
            }
        }
    } else {
        $flash = ['type'=>'error','title'=>'Foto kosong','text'=>'Mohon pilih file foto produk.'];
    }
}

/* ---------------------------
   HANDLE: EDIT produk
   --------------------------- */
if (isset($_POST['edit'])) {
    $id = intval($_POST['id'] ?? 0);
    $nama = safe($conn, $_POST['nama'] ?? '');
    $harga = intval($_POST['harga'] ?? 0);
    $stok = intval($_POST['stok'] ?? 0);
    $id_kategori = (isset($_POST['id_kategori']) && $_POST['id_kategori'] !== '') ? intval($_POST['id_kategori']) : null;
    $deskripsi = safe($conn, $_POST['deskripsi'] ?? '');

    // ambil nama file lama
    $oldFoto = '';
    $r = mysqli_query($conn, "SELECT foto FROM tb_produk WHERE id = $id");
    if ($r && mysqli_num_rows($r)) {
        $oldFoto = mysqli_fetch_assoc($r)['foto'];
    }

    $newFotoName = $oldFoto;
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] !== UPLOAD_ERR_NO_FILE) {
        $file = $_FILES['foto'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png','gif','webp'];
        if (!in_array($ext, $allowed)) {
            $flash = ['type'=>'error','title'=>'Format tidak valid','text'=>'Gunakan file gambar (jpg, jpeg, png, gif, webp).'];
        } elseif ($file['size'] > 3 * 1024 * 1024) {
            $flash = ['type'=>'error','title'=>'File terlalu besar','text'=>'Ukuran maksimal 3MB.'];
        } else {
            $newName = time() . '_' . bin2hex(random_bytes(5)) . '.' . $ext;
            $target = $uploadDir . $newName;
            if (move_uploaded_file($file['tmp_name'], $target)) {
                $newFotoName = $newName;
                // hapus foto lama jika ada
                if ($oldFoto && file_exists($uploadDir . $oldFoto)) {
                    @unlink($uploadDir . $oldFoto);
                }
            } else {
                $flash = ['type'=>'error','title'=>'Upload gagal','text'=>'Tidak bisa menyimpan file.'];
            }
        }
    }

    // update DB
    $sql = "UPDATE tb_produk SET nama=?, harga=?, stok=?, id_kategori=?, deskripsi=?, foto=? WHERE id=?";
    $stmt = mysqli_prepare($conn, $sql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "siiissi", $nama, $harga, $stok, $id_kategori, $deskripsi, $newFotoName, $id);
        if (mysqli_stmt_execute($stmt)) {
            $flash = ['type'=>'success','title'=>'Berhasil','text'=>'Produk berhasil diperbarui.'];
        } else {
            $flash = ['type'=>'error','title'=>'Database error','text'=>mysqli_error($conn)];
        }
        mysqli_stmt_close($stmt);
    } else {
        $flash = ['type'=>'error','title'=>'Prepare error','text'=>mysqli_error($conn)];
    }
}

/* ---------------------------
   HANDLE: HAPUS produk
   --------------------------- */
if (isset($_GET['hapus'])) {
    $id = intval($_GET['hapus']);
    // ambil foto
    $r = mysqli_query($conn, "SELECT foto FROM tb_produk WHERE id = $id");
    if ($r && mysqli_num_rows($r)) {
        $row = mysqli_fetch_assoc($r);
        if (!empty($row['foto']) && file_exists($uploadDir . $row['foto'])) {
            @unlink($uploadDir . $row['foto']);
        }
    }
    $stmt = mysqli_prepare($conn, "DELETE FROM tb_produk WHERE id = ?");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $id);
        if (mysqli_stmt_execute($stmt)) {
            // redirect untuk mencegah double delete saat refresh
            header("Location: dashboard.php?status=deleted");
            exit;
        } else {
            $flash = ['type'=>'error','title'=>'Gagal hapus','text'=>mysqli_error($conn)];
        }
        mysqli_stmt_close($stmt);
    } else {
        $flash = ['type'=>'error','title'=>'Prepare error','text'=>mysqli_error($conn)];
    }
}

/* ---------------------------
   FILTER & PENCARIAN PRODUK + PAGINATION
   --------------------------- */
$where = "WHERE 1=1";
$keyword = $_GET['q'] ?? '';
$filterKat = $_GET['kat'] ?? '';

if ($keyword !== '') {
    $safeKey = mysqli_real_escape_string($conn, $keyword);
    $where .= " AND (p.nama LIKE '%$safeKey%' OR p.deskripsi LIKE '%$safeKey%')";
}

if ($filterKat !== '') {
    $safeKat = intval($filterKat);
    $where .= " AND p.id_kategori = $safeKat";
}

/* Pagination */
$limit = 10;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $limit;

/* Hitung total produk (filtered) */
$countRes = mysqli_query($conn, "
    SELECT COUNT(*) AS total 
    FROM tb_produk p 
    LEFT JOIN tb_kategori k ON p.id_kategori = k.id_kategori
    $where
");
$totalProdukFiltered = mysqli_fetch_assoc($countRes)['total'] ?? 0;
$totalPages = max(1, ceil($totalProdukFiltered / $limit));

/* Ambil produk sesuai filter & pagination */
$produkRes = mysqli_query($conn, "
    SELECT p.*, k.nama_kategori 
    FROM tb_produk p
    LEFT JOIN tb_kategori k ON p.id_kategori = k.id_kategori
    $where
    ORDER BY p.id DESC
    LIMIT $limit OFFSET $offset
");

/* ---------------------------
   Ambil kategori ulang untuk modal edit selects (reset pointer)
   --------------------------- */
$kategoriResForEdit = mysqli_query($conn, "SELECT * FROM tb_kategori ORDER BY id_kategori ASC");

/* ---------------------------
   STATISTIK: produk, kategori, stok, transaksi
   --------------------------- */
$qProduk = mysqli_query($conn, "SELECT COUNT(*) AS jml FROM tb_produk");
$totalProduk = mysqli_fetch_assoc($qProduk)['jml'] ?? 0;

$qKategori = mysqli_query($conn, "SELECT COUNT(*) AS jml FROM tb_kategori");
$totalKategori = mysqli_fetch_assoc($qKategori)['jml'] ?? 0;

$qStok = mysqli_query($conn, "SELECT SUM(stok) AS total FROM tb_produk");
$totalStok = mysqli_fetch_assoc($qStok)['total'] ?? 0;

$qTransaksi = mysqli_query($conn, "SELECT COUNT(*) AS jml FROM tb_transaksi");
$totalTransaksi = mysqli_fetch_assoc($qTransaksi)['jml'] ?? 0;

/* ---------------------------
   DATA UNTUK GRAFIK PENJUALAN (transaksi per bulan)
   Pastikan tb_transaksi memiliki kolom 'tanggal' (DATE/DATETIME) dan 'total'
   --------------------------- */
$salesRes = mysqli_query($conn, "
    SELECT DATE_FORMAT(tanggal, '%Y-%m') AS bulan, SUM(total) AS omzet
    FROM tb_transaksi
    WHERE status = 'selesai'
    GROUP BY DATE_FORMAT(tanggal, '%Y-%m')
    ORDER BY bulan ASC
");
$labels = [];
$values = [];
while ($row = mysqli_fetch_assoc($salesRes)) {
    $labels[] = $row['bulan'];
    $values[] = (float)$row['omzet'];
}

/* helper highlight */
function highlight($text, $keyword) {
    if ($keyword === '') return htmlspecialchars($text);
    return preg_replace('/(' . preg_quote($keyword, '/') . ')/i', '<mark>$1</mark>', htmlspecialchars($text));
}
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Admin Dashboard ┃ MinangMaknyus</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

  <style>
    body { font-family: 'Poppins', sans-serif; background: #f1f3f5; }
    /* Sidebar */
    .sidebar-custom {
      position: fixed;
      top: 0;
      left: 0;
      height: 100vh;
      width: 220px;
      background: #212529;
      padding-top: 18px;
      color: #adb5bd;
      overflow-y: auto;
    }
    .sidebar-custom .brand {
      display:flex;
      align-items:center;
      gap:10px;
      padding: 12px 16px;
      border-bottom: 1px solid rgba(255,255,255,0.03);
    }
    .sidebar-custom .brand img { height:40px; }
    .sidebar-custom .nav-link {
      color: #adb5bd;
      padding: 10px 16px;
      display:flex;
      align-items:center;
      gap:10px;
      border-radius:6px;
      margin:6px 8px;
      transition: background .15s, color .15s;
    }
    .sidebar-custom .nav-link:hover { background: rgba(255,255,255,0.03); color:#fff; }
    .sidebar-custom .nav-link.active {
      background: linear-gradient(90deg,#0d6efd,#6610f2);
      color:#fff;
      font-weight:600;
    }
    .main-content { margin-left: 240px; padding: 32px; }
    .signout { position: fixed; top: 12px; right: 22px; z-index: 50; }
    .table-img { width:60px; height:60px; object-fit:cover; border-radius:6px; }
    .form-required::after { content: " *"; color: #d63384; }
    @media (max-width: 767px) {
      .sidebar-custom { position: relative; width:100%; height:auto; }
      .main-content { margin-left: 0; padding: 12px; }
      .signout { position: static; margin-top:10px; }
    }
    /* small card tweaks */
    .stat-card h6 { font-size:13px; color:#6c757d; margin-bottom:6px; }
    .stat-card h3 { font-weight:700; margin:0; }
  </style>
</head>
<body>

<nav class="sidebar-custom">
  <div class="brand px-2">
    <img src="../assets/img/logo.png" alt="logo" onerror="this.src='https://placehold.co/80x40'">
    <div>
      <div style="color:#fff;font-weight:700">MinangMaknyus</div>
      <small style="color:#8b949e">Admin Panel</small>
    </div>
  </div>

  <div class="mt-3">
    <a href="dashboard.php" class="nav-link active"><i class="bi bi-house-fill"></i> Dashboard</a>
    <a href="transaksi.php" class="nav-link"><i class="bi bi-receipt"></i> Transaksi</a>
    <a href="pelanggan.php" class="nav-link"><i class="bi bi-people-fill"></i> Pelanggan</a>
    <a href="pesan.php" class="nav-link"><i class="bi bi-envelope-fill"></i> Pesan</a>
  </div>

  <div class="mt-4 px-2">
    <a href="../auth/logout.php" class="nav-link" style="margin-top:10px"><i class="bi bi-box-arrow-right"></i> Sign out</a>
  </div>
</nav>

<div class="signout d-none d-md-block">
  <a href="../auth/logout.php" class="btn btn-sm btn-outline-light">Sign out</a>
</div>

<div class="main-content">
  <div class="d-flex justify-content-between align-items-start mb-3">
    <div>
      <h3 class="mb-0">Dashboard</h3>
      <small class="text-muted">Kelola data produk</small>
    </div>
    <div class="d-flex gap-2">
      <button class="btn btn-outline-secondary btn-sm" id="btnExport"><i class="bi bi-share"></i> Share</button>
      <button class="btn btn-outline-secondary btn-sm" id="btnExport2"><i class="bi bi-download"></i> Export</button>
    </div>
  </div>

  <div class="container-fluid p-0">

    <!-- STAT CARDS -->
    <div class="row mb-4">
      <div class="col-md-3">
        <div class="card shadow-sm p-3 stat-card bg-white">
          <h6>Total Produk</h6>
          <h3><?= number_format($totalProduk) ?></h3>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card shadow-sm p-3 stat-card bg-white">
          <h6>Total Kategori</h6>
          <h3><?= number_format($totalKategori) ?></h3>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card shadow-sm p-3 stat-card bg-white">
          <h6>Stok Keseluruhan</h6>
          <h3><?= number_format($totalStok) ?></h3>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card shadow-sm p-3 stat-card bg-white">
          <h6>Transaksi</h6>
          <h3><?= number_format($totalTransaksi) ?></h3>
        </div>
      </div>
    </div>

    <!-- SALES CHART -->
    <div class="card mb-4 shadow-sm">
      <div class="card-body bg-white">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h5 class="mb-0">Grafik Penjualan per Bulan</h5>
          <small class="text-muted">(Omzet transaksi dengan status 'selesai')</small>
        </div>
        <canvas id="salesChart" height="100"></canvas>
      </div>
    </div>

    <div class="card mb-4 shadow-sm">
      <div class="card-body bg-white">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h5 class="mb-0">Tambah Produk</h5>
          <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#tambahModal"><i class="bi bi-plus-lg"></i> Tambah</button>
        </div>

        <!-- SEARCH & FILTER -->
        <form method="GET" class="row g-2 mb-3">
          <div class="col-md-4">
            <input type="text" name="q" value="<?= htmlspecialchars($keyword) ?>"
                   class="form-control" placeholder="Cari produk...">
          </div>
          <div class="col-md-3">
            <select name="kat" class="form-select">
              <option value="">Semua Kategori</option>
              <?php
                mysqli_data_seek($kategoriListRes, 0);
                while ($k = mysqli_fetch_assoc($kategoriListRes)): ?>
                  <option value="<?= $k['id_kategori'] ?>" <?= ($filterKat == $k['id_kategori'] ? 'selected' : '') ?>>
                    <?= htmlspecialchars($k['nama_kategori']) ?>
                  </option>
              <?php endwhile; ?>
            </select>
          </div>
          <div class="col-md-2">
            <button class="btn btn-primary w-100"><i class="bi bi-search"></i> Cari</button>
          </div>
          <div class="col-md-2">
            <a href="dashboard.php" class="btn btn-outline-secondary w-100">Reset</a>
          </div>
        </form>

        <div class="table-responsive">
          <table class="table table-hover mb-0 align-middle">
            <thead class="table-light">
              <tr>
                <th style="width:60px">No</th>
                <th>Nama</th>
                <th>Harga</th>
                <th>Stok</th>
                <th>Kategori</th>
                <th>Deskripsi</th>
                <th>Foto</th>
                <th style="width:150px">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php $no = $offset + 1; while ($p = mysqli_fetch_assoc($produkRes)): ?>
                <tr>
                  <td><?= $no++ ?></td>
                  <td><?= highlight($p['nama'] ?? '-', $keyword) ?></td>
                  <td>Rp<?= number_format(intval($p['harga'] ?? 0),0,',','.') ?></td>
                  <td><?= htmlspecialchars($p['stok'] ?? '0') ?></td>
                  <td><?= htmlspecialchars($p['nama_kategori'] ?? '-') ?></td>
                  <td style="max-width:200px;white-space:normal;"><?= highlight($p['deskripsi'] ?? '-', $keyword) ?></td>
                  <td>
                    <?php if (!empty($p['foto']) && file_exists($uploadDir . $p['foto'])): ?>
                      <img src="<?= $publicUploadDir . $p['foto'] ?>" class="table-img" alt="foto">
                    <?php else: ?>
                      <img src="https://placehold.co/60x60" class="table-img" alt="no image">
                    <?php endif; ?>
                  </td>
                  <td>
                    <button class="btn btn-sm btn-warning editBtn" 
                      data-id="<?= $p['id'] ?>"
                      data-nama="<?= htmlspecialchars($p['nama'] ?? '') ?>"
                      data-harga="<?= intval($p['harga'] ?? 0) ?>"
                      data-stok="<?= intval($p['stok'] ?? 0) ?>"
                      data-id_kategori="<?= $p['id_kategori'] ?>"
                      data-deskripsi="<?= htmlspecialchars($p['deskripsi'] ?? '') ?>"
                      data-bs-toggle="modal" data-bs-target="#editModal">
                      <i class="bi bi-pencil-square"></i> Edit
                    </button>
                    <button class="btn btn-sm btn-danger btn-delete" data-id="<?= $p['id'] ?>" data-nama="<?= htmlspecialchars($p['nama'] ?? '') ?>"><i class="bi bi-trash"></i> Hapus</button>
                  </td>
                </tr>
              <?php endwhile; ?>

              <?php if ($totalProdukFiltered == 0): ?>
                <tr><td colspan="8" class="text-center text-muted">Tidak ada produk.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>

        <!-- PAGINATION -->
        <div class="mt-3 d-flex justify-content-between align-items-center">
          <div class="text-muted">Menampilkan <?= ($totalProdukFiltered>0?($offset+1):0) ?> - <?= min($offset + $limit, $totalProdukFiltered) ?> dari <?= $totalProdukFiltered ?> produk</div>
          <nav>
            <ul class="pagination mb-0">
              <?php
                // helper to preserve q & kat in links
                $qs = [];
                if ($keyword !== '') $qs['q'] = $keyword;
                if ($filterKat !== '') $qs['kat'] = $filterKat;

                $baseQ = http_build_query($qs);
                $link = function($pnum) use ($baseQ) {
                    return 'dashboard.php' . ($baseQ ? '?'.$baseQ.'&page='.$pnum : '?page='.$pnum);
                };
              ?>

              <?php if ($page > 1): ?>
                <li class="page-item">
                  <a class="page-link" href="<?= $link($page-1) ?>">« Prev</a>
                </li>
              <?php endif; ?>

              <?php
                // show max 7 page links with current in center when possible
                $start = max(1, $page - 3);
                $end = min($totalPages, $start + 6);
                if ($end - $start < 6) $start = max(1, $end - 6);
                for ($i = $start; $i <= $end; $i++): ?>
                  <li class="page-item <?= ($i == $page ? 'active' : '') ?>">
                    <a class="page-link" href="<?= $link($i) ?>"><?= $i ?></a>
                  </li>
              <?php endfor; ?>

              <?php if ($page < $totalPages): ?>
                <li class="page-item">
                  <a class="page-link" href="<?= $link($page+1) ?>">Next »</a>
                </li>
              <?php endif; ?>
            </ul>
          </nav>
        </div>

      </div>
    </div>
  </div>
</div>

<!-- MODAL TAMBAH -->
<div class="modal fade" id="tambahModal" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content bg-white">
      <div class="modal-header">
        <h5 class="modal-title">Tambah Produk</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" enctype="multipart/form-data">
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label form-required">Nama Produk</label>
              <input type="text" name="nama" class="form-control" required>
            </div>
            <div class="col-md-3">
              <label class="form-label form-required">Harga</label>
              <input type="number" name="harga" class="form-control" required>
            </div>
            <div class="col-md-3">
              <label class="form-label form-required">Stok</label>
              <input type="number" name="stok" class="form-control" required>
            </div>
            <div class="col-md-4">
              <label class="form-label">Kategori</label>
              <select name="id_kategori" class="form-select">
                <option value="">Pilih Kategori</option>
                <?php
                mysqli_data_seek($kategoriListRes, 0);
                while ($k = mysqli_fetch_assoc($kategoriListRes)): ?>
                  <option value="<?= $k['id_kategori'] ?>"><?= htmlspecialchars($k['nama_kategori']) ?></option>
                <?php endwhile; ?>
              </select>
            </div>
            <div class="col-md-8">
              <label class="form-label form-required">Foto Produk</label>
              <input type="file" name="foto" id="fotoTambah" accept="image/*" class="form-control" required>
              <img id="previewTambah" src="#" alt="Preview" style="display:none; max-height:100px; margin-top:10px; border-radius:6px;">
            </div>
            <div class="col-12">
              <label class="form-label">Deskripsi</label>
              <textarea name="deskripsi" class="form-control" rows="3"></textarea>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button name="tambah" class="btn btn-primary">Tambah</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- MODAL EDIT -->
<div class="modal fade" id="editModal" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content bg-white">
      <div class="modal-header">
        <h5 class="modal-title">Edit Produk</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" enctype="multipart/form-data" id="formEdit">
        <div class="modal-body">
          <input type="hidden" name="id" id="edit_id">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Nama Produk</label>
              <input type="text" name="nama" id="edit_nama" class="form-control" required>
            </div>
            <div class="col-md-3">
              <label class="form-label">Harga</label>
              <input type="number" name="harga" id="edit_harga" class="form-control" required>
            </div>
            <div class="col-md-3">
              <label class="form-label">Stok</label>
              <input type="number" name="stok" id="edit_stok" class="form-control" required>
            </div>
            <div class="col-md-4">
              <label class="form-label">Kategori</label>
              <select name="id_kategori" id="edit_id_kategori" class="form-select">
                <option value="">Pilih Kategori</option>
                <?php
                // gunakan kategoriResForEdit
                mysqli_data_seek($kategoriResForEdit, 0);
                while ($k2 = mysqli_fetch_assoc($kategoriResForEdit)): ?>
                  <option value="<?= $k2['id_kategori'] ?>"><?= htmlspecialchars($k2['nama_kategori']) ?></option>
                <?php endwhile; ?>
              </select>
            </div>
            <div class="col-md-8">
              <label class="form-label">Ganti Foto (opsional)</label>
              <input type="file" name="foto" class="form-control" accept="image/*">
              <small class="text-muted">Kosongkan jika tidak ingin mengganti foto.</small>
            </div>
            <div class="col-12">
              <label class="form-label">Deskripsi</label>
              <textarea name="deskripsi" id="edit_deskripsi" class="form-control" rows="3"></textarea>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" name="edit" class="btn btn-success">Simpan</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
document.getElementById('fotoTambah')?.addEventListener('change', function(e){
  const f = e.target.files[0];
  const preview = document.getElementById('previewTambah');
  if (!f) { preview.style.display='none'; return; }
  const reader = new FileReader();
  reader.onload = function(ev){ preview.src = ev.target.result; preview.style.display = 'block'; }
  reader.readAsDataURL(f);
});
</script>

<script>
document.querySelectorAll('.editBtn').forEach(btn=>{
  btn.addEventListener('click', function(){
    const id = this.dataset.id || '';
    const nama = this.dataset.nama || '';
    const harga = this.dataset.harga || 0;
    const stok = this.dataset.stok || 0;
    const id_kategori = this.dataset.id_kategori || '';
    const deskripsi = this.dataset.deskripsi || '';

    document.getElementById('edit_id').value = id;
    document.getElementById('edit_nama').value = nama;
    document.getElementById('edit_harga').value = harga;
    document.getElementById('edit_stok').value = stok;
    document.getElementById('edit_id_kategori').value = id_kategori;
    document.getElementById('edit_deskripsi').value = deskripsi;
  });
});

document.querySelectorAll('.btn-delete').forEach(btn=>{
  btn.addEventListener('click', function(){
    const id = this.dataset.id;
    const nama = this.dataset.nama;
    Swal.fire({
      title: 'Yakin hapus?',
      html: `Produk <strong>${nama}</strong> akan dihapus. Foto juga akan dihapus dari server.`,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#d33',
      confirmButtonText: 'Ya, hapus',
      cancelButtonText: 'Batal'
    }).then(res=>{
      if (res.isConfirmed) {
        window.location.href = '?hapus=' + encodeURIComponent(id);
      }
    });
  });
});

// show flash from PHP (if any)
<?php if (!empty($flash['type'])): ?>
Swal.fire({
  icon: '<?= $flash['type'] ?>',
  title: '<?= addslashes($flash['title']) ?>',
  html: '<?= addslashes($flash['text']) ?>',
  timer: 1800,
  showConfirmButton: false
});
<?php endif; ?>

// show deleted notification via GET param
<?php if (isset($_GET['status']) && $_GET['status'] === 'deleted'): ?>
Swal.fire({
  icon: 'success',
  title: 'Produk berhasil dihapus',
  timer: 1400,
  showConfirmButton: false
});
<?php endif; ?>
</script>

<script>
// Chart.js salesChart
const ctx = document.getElementById('salesChart')?.getContext('2d');
if (ctx) {
  const salesChart = new Chart(ctx, {
      type: 'line',
      data: {
          labels: <?= json_encode($labels) ?>,
          datasets: [{
              label: 'Omzet Penjualan',
              data: <?= json_encode($values) ?>,
              borderColor: '#0d6efd',
              backgroundColor: 'rgba(13, 110, 253, 0.12)',
              borderWidth: 2,
              fill: true,
              tension: 0.3,
              pointRadius: 4,
              pointBackgroundColor: '#0d6efd'
          }]
      },
      options: {
          responsive: true,
          plugins: { legend: { display: false } },
          scales: {
              y: {
                  ticks: {
                      callback: function(value) {
                          try {
                              return 'Rp' + new Intl.NumberFormat('id-ID').format(value);
                          } catch(e) {
                              return 'Rp' + value;
                          }
                      }
                  }
              }
          }
      }
  });
}
</script>

</body>
</html>
