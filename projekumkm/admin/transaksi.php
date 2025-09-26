<?php
session_start();
include '../db.php'; // <-- sesuaikan path jika perlu

// Optional: cek apakah admin login
// if (!isset($_SESSION['admin'])) { header('Location: ../auth/login.php'); exit; }

// ---------- Helper ----------
function safe($conn, $v) {
    return mysqli_real_escape_string($conn, trim($v));
}

function json_response($data) {
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

// flash untuk notifikasi sisi server -> JS SweetAlert nanti akan pake ini
$flash = ['type'=>'','title'=>'','text'=>''];

// ---------- Upload dir (untuk preview produk jika diperlukan) ----------
$uploadDir = __DIR__ . '/uploads/';
$publicUploadDir = 'uploads/'; // relative URL
if (!is_dir($uploadDir)) {
    @mkdir($uploadDir, 0777, true);
}

/* =========================
   AJAX / ACTION HANDLER
   =========================
   - action=detail       -> return JSON detail transaksi
   - action=update_status -> update status transaksi (POST)
   - action=delete       -> delete transaksi (GET/POST)
   - action=export_csv   -> export CSV (handled later)
*/
if (isset($_GET['action'])) {
    $action = $_GET['action'];

    // --- DETAIL: kirim JSON detail transaksi ---
    if ($action === 'detail' && isset($_GET['id'])) {
        $id = intval($_GET['id']);
        // ambil transaksi + user info
        $sql = "SELECT t.*, u.nama AS nama_pelanggan, u.telepon, u.email, u.alamat 
                FROM tb_transaksi t
                LEFT JOIN tb_user u ON t.id_pelanggan = u.id
                WHERE t.id_transaksi = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $res = $stmt->get_result();
        $trans = $res->fetch_assoc();
        $stmt->close();
        if (!$trans) {
            json_response(['ok'=>false,'msg'=>'Transaksi tidak ditemukan']);
        }

        // ambil detail produk
        $sqlD = "SELECT d.*, p.nama, p.foto
                 FROM tb_detail d
                 LEFT JOIN tb_produk p ON d.id_produk = p.id
                 WHERE d.id_transaksi = ?";
        $stmt = $conn->prepare($sqlD);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $resD = $stmt->get_result();
        $items = [];
        $subtotal = 0;
        while ($row = $resD->fetch_assoc()) {
            $row['subtotal'] = $row['harga'] * $row['jumlah'];
            $subtotal += $row['subtotal'];
            $items[] = $row;
        }
        $stmt->close();

        // return
        json_response([
            'ok' => true,
            'transaksi' => $trans,
            'items' => $items,
            'subtotal' => $subtotal
        ]);
    }

    // --- UPDATE STATUS: via POST (AJAX) ---
    if ($action === 'update_status' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $id = intval($_POST['id'] ?? 0);
        $status = safe($conn, $_POST['status'] ?? '');

        // UBAH: Sesuaikan daftar status yang valid
        $allowed = ['dibuat','diproses','dikirim','diterima','dibatalkan'];
        if (!in_array($status, $allowed)) {
            json_response(['ok'=>false,'msg'=>'Status tidak valid']);
        }

        // UBAH: Gunakan kolom 'status_pengiriman' agar konsisten
        $sql = "UPDATE tb_transaksi SET status_pengiriman = ? WHERE id_transaksi = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $status, $id);
        if ($stmt->execute()) {
            json_response(['ok'=>true,'msg'=>'Status berhasil diperbarui']);
        } else {
            json_response(['ok'=>false,'msg'=>'Gagal memperbarui: '. $stmt->error]);
        }
    }

    // --- DELETE transaksi (safely) ---
    if ($action === 'delete' && isset($_GET['id'])) {
        $id = intval($_GET['id']);
        // hapus detail dulu
        $stmt = $conn->prepare("DELETE FROM tb_detail WHERE id_transaksi = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();

        // hapus transaksi
        $stmt = $conn->prepare("DELETE FROM tb_transaksi WHERE id_transaksi = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            // jika request AJAX => return JSON
            if (isset($_GET['ajax'])) {
                json_response(['ok'=>true,'msg'=>'Transaksi dihapus']);
            } else {
                header("Location: transaksi.php?deleted=1");
                exit;
            }
        } else {
            if (isset($_GET['ajax'])) {
                json_response(['ok'=>false,'msg'=>'Gagal menghapus: ' . $stmt->error]);
            } else {
                $flash = ['type'=>'error','title'=>'Gagal hapus','text'=>$stmt->error];
            }
        }
        $stmt->close();
    }

    // --- EXPORT CSV (simple) ---
    if ($action === 'export_csv') {
        // header CSV
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=transaksi_export_' . date('Ymd_His') . '.csv');

        $out = fopen('php://output', 'w');
        fputcsv($out, ['ID Transaksi','Nama Pelanggan','Total','Tanggal','Status','Metode Bayar','Metode Pengiriman','Alamat']);

        $q = "SELECT t.*, u.nama AS nama_pelanggan FROM tb_transaksi t LEFT JOIN tb_user u ON t.id_pelanggan = u.id ORDER BY t.id_transaksi DESC";
        $res = mysqli_query($conn, $q);
        while ($r = mysqli_fetch_assoc($res)) {
            fputcsv($out, [
                $r['id_transaksi'],
                $r['nama_pelanggan'] ?? '-',
                $r['total'] ?? 0,
                $r['tanggal'] ?? '',
                $r['status_pengiriman'] ?? '',
                $r['metode_pembayaran'] ?? '',
                $r['metode_pengiriman'] ?? '',
                str_replace(["\r","\n"], [' ',' '], $r['alamat'] ?? '')
            ]);
        }
        fclose($out);
        exit;
    }

    // unsupported action => return 400
    http_response_code(400);
    echo "Action tidak dikenal";
    exit;
}

/* =========================
   PAGE: Render HTML
   ========================= */

// ambil daftar transaksi (limit/pagination sederhana)
$perPage = 25;
$page = max(1, intval($_GET['page'] ?? 1));
$offset = ($page - 1) * $perPage;

// filter pencarian sederhana
$search = safe($conn, $_GET['q'] ?? '');
$statusFilter = safe($conn, $_GET['status_filter'] ?? '');

// Membangun klausa WHERE dan parameter secara dinamis
$where = "WHERE 1";
$params = [];
$paramTypes = "";

if ($search !== '') {
    $where .= " AND (t.id_transaksi LIKE ? OR u.nama LIKE ? OR t.status_pengiriman LIKE ?)";
    $like = "%$search%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $paramTypes .= 'sss';
}
if ($statusFilter !== '') {
    $where .= " AND t.status_pengiriman = ?";
    $params[] = $statusFilter;
    $paramTypes .= 's';
}

// Hitung total untuk pagination
$countSql = "SELECT COUNT(*) AS cnt FROM tb_transaksi t
             LEFT JOIN tb_user u ON t.id_pelanggan = u.id " . $where;
$stmtCount = $conn->prepare($countSql);
if (!empty($params)) {
    $bindParams = [];
    $bindParams[] = & $paramTypes;
    for ($i = 0; $i < count($params); $i++) {
        $bindParams[] = & $params[$i];
    }
    call_user_func_array([$stmtCount, 'bind_param'], $bindParams);
}
$stmtCount->execute();
$resCnt = $stmtCount->get_result();
$cnt = (int) $resCnt->fetch_assoc()['cnt'];
$stmtCount->close();

$totalPages = max(1, ceil($cnt / $perPage));

// Ambil data transaksi (JOIN user)
$listSql = "SELECT t.*, u.nama AS nama_pelanggan, u.telepon
             FROM tb_transaksi t
             LEFT JOIN tb_user u ON t.id_pelanggan = u.id " . $where . "
             ORDER BY t.id_transaksi DESC LIMIT ? OFFSET ?";

$params[] = $perPage;
$params[] = $offset;
$paramTypes .= 'ii';

$stmt = $conn->prepare($listSql);
$bindParams = [];
$bindParams[] = & $paramTypes;
for ($i = 0; $i < count($params); $i++) {
    $bindParams[] = & $params[$i];
}
call_user_func_array([$stmt, 'bind_param'], $bindParams);

$stmt->execute();
$res = $stmt->get_result();
$transaksiList = [];
while ($row = $res->fetch_assoc()) {
    $transaksiList[] = $row;
}
$stmt->close();
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Transaksi ┃ MinangMaknyus - Admin</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        body { font-family: 'Poppins', sans-serif; background: #f1f3f5; }
        /* Sidebar (cocokkan dengan dashboard yang Anda punya) */
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
        .sidebar-custom .brand { display:flex; align-items:center; gap:10px; padding: 12px 16px; border-bottom: 1px solid rgba(255,255,255,0.03); }
        .sidebar-custom .brand img { height:40px; }
        .sidebar-custom .nav-link { color: #adb5bd; padding: 10px 16px; display:flex; align-items:center; gap:10px; border-radius:6px; margin:6px 8px; transition: background .15s, color .15s; }
        .sidebar-custom .nav-link:hover { background: rgba(255,255,255,0.03); color:#fff; }
        .sidebar-custom .nav-link.active { background: linear-gradient(90deg,#0d6efd,#6610f2); color:#fff; font-weight:600; }

        .main-content { margin-left: 240px; padding: 32px; }
        .table-img { width:60px; height:60px; object-fit:cover; border-radius:6px; }
        .form-required::after { content: " *"; color: #d63384; }
        @media (max-width: 767px) {
            .sidebar-custom { position: relative; width:100%; height:auto; }
            .main-content { margin-left: 0; padding: 12px; }
        }

        /* badge warna status */
        .st-dibuat { background:#ffc107; color:#000; }
        .st-diproses { background:#0d6efd; color:#fff; }
        .st-dikirim { background:#0dcaf0; color:#000; }
        .st-diterima { background:#198754; color:#fff; }
        .st-dibatalkan { background:#dc3545; color:#fff; }
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
        <a href="dashboard.php" class="nav-link"><i class="bi bi-house-fill"></i> Dashboard</a>
        <a href="transaksi.php" class="nav-link active"><i class="bi bi-receipt"></i> Transaksi</a>
        <a href="pelanggan.php" class="nav-link"><i class="bi bi-people-fill"></i> Pelanggan</a>
        <a href="pesan.php" class="nav-link"><i class="bi bi-envelope-fill"></i> Pesan</a>
    </div>

    <div class="mt-4 px-2">
        <a href="../auth/logout.php" class="nav-link" style="margin-top:10px"><i class="bi bi-box-arrow-right"></i> Sign out</a>
    </div>
</nav>

<div class="main-content">
    <div class="d-flex justify-content-between align-items-start mb-3">
        <div>
            <h3 class="mb-0">Transaksi</h3>
            <small class="text-muted">Kelola daftar transaksi & detail</small>
        </div>
        <div class="d-flex gap-2">
            <a href="?action=export_csv" class="btn btn-outline-secondary btn-sm" id="btnExport"><i class="bi bi-download"></i> Export CSV</a>
            <button class="btn btn-outline-secondary btn-sm" id="btnRefresh"><i class="bi bi-arrow-clockwise"></i> Refresh</button>
        </div>
    </div>

    <div class="card mb-3 shadow-sm">
        <div class="card-body bg-white">
            <form method="GET" class="row g-2 align-items-center">
                <div class="col-md-4">
                    <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" class="form-control" placeholder="Cari ID transaksi, nama pelanggan, status...">
                </div>
                <div class="col-md-2">
                    <select name="status_filter" id="status_filter" class="form-select">
                        <option value="">Semua Status</option>
                        <option value="dibuat" <?= $statusFilter === 'dibuat' ? 'selected' : '' ?>>Dibuat</option>
                        <option value="diproses" <?= $statusFilter === 'diproses' ? 'selected' : '' ?>>Diproses</option>
                        <option value="dikirim" <?= $statusFilter === 'dikirim' ? 'selected' : '' ?>>Dikirim</option>
                        <option value="diterima" <?= $statusFilter === 'diterima' ? 'selected' : '' ?>>Diterima</option>
                        <option value="dibatalkan" <?= $statusFilter === 'dibatalkan' ? 'selected' : '' ?>>Dibatalkan</option>
                    </select>
                </div>
                <div class="col-auto">
                    <button class="btn btn-primary"><i class="bi bi-search"></i> Cari</button>
                    <a href="transaksi.php" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card mb-4 shadow-sm">
        <div class="card-body bg-white">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th style="width:60px">No</th>
                            <th>ID</th>
                            <th>Nama Pelanggan</th>
                            <th>Total</th>
                            <th>Tanggal</th>
                            <th>Status</th>
                            <th>Metode Bayar</th>
                            <th style="width:220px">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($transaksiList)): ?>
                            <tr><td colspan="8" class="text-center text-muted">Tidak ada transaksi</td></tr>
                        <?php else: ?>
                            <?php $no = $offset + 1; foreach ($transaksiList as $t): 
                                $st = $t['status_pengiriman'] ?? 'dibuat';
                                $badgeClass = 'st-dibuat';
                                if ($st === 'diproses') $badgeClass = 'st-diproses';
                                if ($st === 'dikirim') $badgeClass = 'st-dikirim';
                                if ($st === 'diterima') $badgeClass = 'st-diterima';
                            ?>
                                <tr>
                                    <td><?= $no++ ?></td>
                                    <td>#<?= $t['id_transaksi'] ?></td>
                                    <td><?= htmlspecialchars($t['nama_pelanggan'] ?? '-') ?> <br><small class="text-muted"><?= htmlspecialchars($t['telepon'] ?? '-') ?></small></td>
                                    <td><strong>Rp <?= number_format($t['total'] ?? 0,0,',','.') ?></strong></td>
                                    <td><?= htmlspecialchars($t['tanggal'] ?? '') ?></td>
                                    <td><span class="badge <?= $badgeClass ?>"><?= htmlspecialchars(ucfirst($st)) ?></span></td>
                                    <td><?= htmlspecialchars($t['metode_pembayaran'] ?? '-') ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-primary btn-detail" data-id="<?= $t['id_transaksi'] ?>"><i class="bi bi-eye"></i> Lihat Detail</button>
                                        <div class="btn-group">
                                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">Aksi</button>
                                            <ul class="dropdown-menu">
                                                <li><a class="dropdown-item update-status" href="#" data-id="<?= $t['id_transaksi'] ?>" data-status="diproses">Tandai Diproses</a></li>
                                                <li><a class="dropdown-item update-status" href="#" data-id="<?= $t['id_transaksi'] ?>" data-status="dikirim">Tandai Dikirim</a></li>
                                                <li><a class="dropdown-item update-status" href="#" data-id="<?= $t['id_transaksi'] ?>" data-status="diterima">Tandai Diterima</a></li>
                                                <li><hr class="dropdown-divider"></li>
                                                <li><a class="dropdown-item text-danger delete-trans" href="#" data-id="<?= $t['id_transaksi'] ?>">Hapus Transaksi</a></li>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <nav class="mt-3">
                <ul class="pagination">
                    <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                        <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                            <a class="page-link" href="?page=<?= $p ?><?= $search ? '&q=' . urlencode($search) : '' ?>"><?= $p ?></a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>

        </div>
    </div>

</div> 
<div class="modal fade" id="modalDetailTransaksi" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detail Transaksi</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="detailContainer">
                    <div class="text-center text-muted py-4">Memuat...</div>
                </div>
            </div>
            <div class="modal-footer">
                <button id="btnPrintDetail" type="button" class="btn btn-outline-secondary">Cetak</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
// helper: format rupiah
function rupiah(x) {
    return 'Rp ' + (Number(x) || 0).toLocaleString('id-ID');
}

// tampilkan detail transaksi via AJAX
document.querySelectorAll('.btn-detail').forEach(btn=>{
    btn.addEventListener('click', function(){
        const id = this.dataset.id;
        const modal = new bootstrap.Modal(document.getElementById('modalDetailTransaksi'));
        const container = document.getElementById('detailContainer');
        container.innerHTML = '<div class="text-center text-muted py-4">Memuat...</div>';
        fetch('?action=detail&id=' + encodeURIComponent(id))
            .then(r => r.json())
            .then(d => {
                if (!d.ok) {
                    container.innerHTML = '<div class="text-danger p-3">Error: ' + (d.msg || 'Gagal memuat data') + '</div>';
                    return;
                }
                const t = d.transaksi;
                const items = d.items || [];
                const subtotal = d.subtotal || 0;
                // build html
                let html = '';
                html += '<div class="row">';
                html += '<div class="col-md-6"><h6>Detail Pelanggan</h6>';
                html += '<p><strong>' + (t.nama_pelanggan || '-') + '</strong><br>' + (t.telepon || '-') + '<br>' + (t.email || '-') + '</p></div>';
                html += '<div class="col-md-6"><h6>Info Transaksi</h6>';
                html += '<p>ID: <strong>#' + t.id_transaksi + '</strong><br>Tanggal: ' + (t.tanggal || '-') + '<br>Status: <span class="badge">'+ (t.status || '-') + '</span></p></div>';
                html += '</div>';
                html += '<hr>';
                html += '<div class="table-responsive"><table class="table table-sm">';
                html += '<thead class="table-light"><tr><th>Produk</th><th class="text-center">Harga</th><th class="text-center">Qty</th><th class="text-end">Subtotal</th></tr></thead>';
                html += '<tbody>';
                items.forEach(it => {
                    const nama = it.nama || ('Produk #' + it.id_produk);
                    const foto = it.foto ? ('<img src="<?= $publicUploadDir ?>' + it.foto + '" style="height:40px;width:40px;object-fit:cover;border-radius:6px;margin-right:8px">') : '';
                    html += '<tr>';
                    html += '<td>' + foto + '<strong>' + nama + '</strong></td>';
                    html += '<td class="text-center">' + rupiah(it.harga) + '</td>';
                    html += '<td class="text-center">' + it.jumlah + '</td>';
                    html += '<td class="text-end">' + rupiah(it.subtotal) + '</td>';
                    html += '</tr>';
                });
                html += '</tbody></table></div>';
                html += '<div class="d-flex justify-content-end">';
                html += '<div style="min-width:260px">';
                html += '<div class="d-flex justify-content-between"><span>Subtotal</span><strong>' + rupiah(subtotal) + '</strong></div>';
                // shipping / biaya layanan / voucher jika ada di transaksi
                if (t.ongkir || t.biaya_layanan || t.voucher) {
                    html += '<div class="d-flex justify-content-between"><span>Ongkir</span><span>' + rupiah(t.ongkir || 0) + '</span></div>';
                    html += '<div class="d-flex justify-content-between"><span>Biaya Layanan</span><span>' + rupiah(t.biaya_layanan || 0) + '</span></div>';
                    if (t.voucher) html += '<div class="d-flex justify-content-between"><span>Voucher</span><span>- ' + rupiah(t.voucher_nominal || 0) + '</span></div>';
                }
                html += '<hr>';
                html += '<div class="d-flex justify-content-between"><span>Total</span><strong>' + rupiah(t.total || 0) + '</strong></div>';
                html += '</div></div>';

                container.innerHTML = html;

                // show modal
                modal.show();

                // print button
                document.getElementById('btnPrintDetail').onclick = function(){
                    const printWindow = window.open('', '_blank', 'width=800,height=600');
                    printWindow.document.write('<html><head><title>Detail Transaksi #' + t.id_transaksi + '</title>');
                    printWindow.document.write('<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">');
                    printWindow.document.write('</head><body>');
                    printWindow.document.write('<div class="container">' + container.innerHTML + '</div>');
                    printWindow.document.close();
                    setTimeout(()=>printWindow.print(), 600);
                };
            })
            .catch(err=>{
                container.innerHTML = '<div class="text-danger p-3">Error memuat: ' + err.message + '</div>';
            });
    });
});

// update status via AJAX
document.querySelectorAll('.update-status').forEach(a=>{
    a.addEventListener('click', function(e){
        e.preventDefault();
        const id = this.dataset.id;
        const status = this.dataset.status;
        Swal.fire({
            title: 'Ubah status?',
            text: 'Set status transaksi #' + id + ' menjadi "' + status + '"?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Ya, ubah',
        }).then(res=>{
            if (!res.isConfirmed) return;
            fetch('?action=update_status', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'id=' + encodeURIComponent(id) + '&status=' + encodeURIComponent(status)
            }).then(r=>r.json()).then(d=>{
                if (d.ok) {
                    Swal.fire({ icon: 'success', title: d.msg, timer: 1100, showConfirmButton:false }).then(()=> location.reload());
                } else {
                    Swal.fire({ icon: 'error', title: 'Gagal', text: d.msg || 'Error' });
                }
            }).catch(err=>{
                Swal.fire({ icon: 'error', title: 'Error', text: err.message });
            });
        });
    });
});

// delete transaksi
document.querySelectorAll('.delete-trans').forEach(a=>{
    a.addEventListener('click', function(e){
        e.preventDefault();
        const id = this.dataset.id;
        Swal.fire({
            title: 'Hapus transaksi?',
            html: 'Transaksi <strong>#' + id + '</strong> akan dihapus permanen.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ya, hapus'
        }).then(res=>{
            if (!res.isConfirmed) return;
            fetch('?action=delete&id=' + encodeURIComponent(id) + '&ajax=1')
                .then(r=>r.json())
                .then(d=>{
                    if (d.ok) {
                        Swal.fire({ icon:'success', title: d.msg, timer:1100, showConfirmButton:false }).then(()=> location.reload());
                    } else {
                        Swal.fire({ icon:'error', title: 'Gagal', text: d.msg || 'Error' });
                    }
                }).catch(err=>{
                    Swal.fire({ icon:'error', title:'Error', text: err.message });
                });
        });
    });
});

// refresh button
document.getElementById('btnRefresh')?.addEventListener('click', ()=> location.reload());

// preselect status filter if present in GET
(function(){
    const params = new URLSearchParams(window.location.search);
    const sf = params.get('status_filter') || '';
    if (sf) {
        const sel = document.getElementById('status_filter');
        if (sel) sel.value = sf;
    }
})();
</script>

</body>
</html>