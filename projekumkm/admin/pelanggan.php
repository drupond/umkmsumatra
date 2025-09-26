<?php
session_start();
include '../db.php'; // sesuaikan path jika perlu

// Optional: cek apakah admin login
// if (!isset($_SESSION['admin'])) { header('Location: ../auth/login.php'); exit; }

// helper: flash message (JS SweetAlert)
$flash = [
    'type' => '', // success|error|info|warning
    'title' => '',
    'text' => ''
];

/* ---------------------------
    HANDLE: HAPUS pelanggan
    --------------------------- */
if (isset($_GET['hapus'])) {
    $id = intval($_GET['hapus']);
    
    $check_transaksi_stmt = mysqli_prepare($conn, "SELECT id_transaksi FROM tb_transaksi WHERE id_user = ?");
    mysqli_stmt_bind_param($check_transaksi_stmt, "i", $id);
    mysqli_stmt_execute($check_transaksi_stmt);
    $check_transaksi_res = mysqli_stmt_get_result($check_transaksi_stmt);

    if (mysqli_num_rows($check_transaksi_res) > 0) {
        $flash = [
            'type' => 'error',
            'title' => 'Gagal Hapus Pelanggan',
            'text' => 'Pelanggan ini memiliki transaksi. Harap hapus transaksi terkait terlebih dahulu.'
        ];
    } else {
        $stmt = mysqli_prepare($conn, "DELETE FROM tb_user WHERE id = ?");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "i", $id);
            if (mysqli_stmt_execute($stmt)) {
                header("Location: pelanggan.php?status=deleted");
                exit;
            } else {
                $flash = ['type' => 'error', 'title' => 'Gagal hapus', 'text' => mysqli_error($conn)];
            }
            mysqli_stmt_close($stmt);
        } else {
            $flash = ['type' => 'error', 'title' => 'Prepare error', 'text' => mysqli_error($conn)];
        }
    }
}

/* ---------------------------
    HANDLE: RESET PASSWORD
    --------------------------- */
if (isset($_GET['reset_password'])) {
    $id = intval($_GET['reset_password']);
    $new_password = password_hash('123456', PASSWORD_DEFAULT); // Password default
    
    $stmt = mysqli_prepare($conn, "UPDATE tb_user SET password = ? WHERE id = ?");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "si", $new_password, $id);
        if (mysqli_stmt_execute($stmt)) {
            header("Location: pelanggan.php?status=password_reset");
            exit;
        } else {
            $flash = ['type' => 'error', 'title' => 'Gagal Reset Password', 'text' => mysqli_error($conn)];
        }
        mysqli_stmt_close($stmt);
    } else {
        $flash = ['type' => 'error', 'title' => 'Prepare error', 'text' => mysqli_error($conn)];
    }
}


/* ---------------------------
    Ambil data pelanggan
    --------------------------- */
$pelangganRes = mysqli_query($conn, "
    SELECT id, nama, email, telepon, jenis_kelamin, tgl_lahir
    FROM tb_user
    WHERE role = 'pelanggan'
    ORDER BY id DESC
");
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Kelola Pelanggan ┃ MinangMaknyus</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body { font-family: 'Poppins', sans-serif; background: #f1f3f5; }
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
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 16px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.03);
        }
        .sidebar-custom .brand img { height: 40px; }
        .sidebar-custom .nav-link {
            color: #adb5bd;
            padding: 10px 16px;
            display: flex;
            align-items: center;
            gap: 10px;
            border-radius: 6px;
            margin: 6px 8px;
            transition: background .15s, color .15s;
        }
        .sidebar-custom .nav-link:hover { background: rgba(255, 255, 255, 0.03); color: #fff; }
        .sidebar-custom .nav-link.active {
            background: linear-gradient(90deg, #0d6efd, #6610f2);
            color: #fff;
            font-weight: 600;
        }
        .main-content { margin-left: 240px; padding: 32px; }
        .signout { position: fixed; top: 12px; right: 22px; z-index: 50; }
        @media (max-width: 767px) {
            .sidebar-custom { position: relative; width: 100%; height: auto; }
            .main-content { margin-left: 0; padding: 12px; }
            .signout { position: static; margin-top: 10px; }
        }
    </style>
</head>
<body>

<nav class="sidebar-custom">
    <div class="brand px-2">
        <img src="../assets/img/logo.png" alt="logo" onerror="this.src='https://placehold.co/80x40'">
        <div>
            <div style="color: #fff; font-weight: 700;">MinangMaknyus</div>
            <small style="color: #8b949e;">Admin Panel</small>
        </div>
    </div>
    <div class="mt-3">
        <a href="dashboard.php" class="nav-link"><i class="bi bi-house-fill"></i> Dashboard</a>
        <a href="transaksi.php" class="nav-link"><i class="bi bi-receipt"></i> Transaksi</a>
        <a href="pelanggan.php" class="nav-link active"><i class="bi bi-people-fill"></i> Pelanggan</a>
        <a href="pesan.php" class="nav-link"><i class="bi bi-envelope-fill"></i> Pesan</a>   
    </div>
    <div class="mt-4 px-2">
        <a href="../auth/logout.php" class="nav-link" style="margin-top: 10px"><i class="bi bi-box-arrow-right"></i> Sign out</a>
    </div>
</nav>

<div class="signout d-none d-md-block">
    <a href="../auth/logout.php" class="btn btn-sm btn-outline-light">Sign out</a>
</div>

<div class="main-content">
    <div class="d-flex justify-content-between align-items-start mb-3">
        <div>
            <h3 class="mb-0">Kelola Pelanggan</h3>
            <small class="text-muted">Daftar semua pelanggan yang terdaftar</small>
        </div>
    </div>

    <div class="container-fluid p-0">
        <div class="card mb-4 shadow-sm">
            <div class="card-body bg-white">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th style="width:50px">No</th>
                                <th>Nama</th>
                                <th>Email</th>
                                <th>Telepon</th>
                                <th>Jenis Kelamin</th>
                                <th>Tanggal Lahir</th>
                                <th style="width:220px">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no = 1; while ($p = mysqli_fetch_assoc($pelangganRes)): ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td><?= htmlspecialchars($p['nama'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($p['email'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($p['telepon'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($p['jenis_kelamin'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($p['tgl_lahir'] ?? '-') ?></td>
                                <td>
                                    <button class="btn btn-sm btn-info text-white me-2 reset-password-btn" data-id="<?= $p['id'] ?>" data-nama="<?= htmlspecialchars($p['nama'] ?? '') ?>">
                                        <i class="bi bi-key-fill"></i> Reset Password
                                    </button>
                                    <button class="btn btn-sm btn-danger btn-delete" data-id="<?= $p['id'] ?>" data-nama="<?= htmlspecialchars($p['nama'] ?? '') ?>">
                                        <i class="bi bi-trash"></i> Hapus
                                    </button>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div> <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
document.querySelectorAll('.btn-delete').forEach(btn => {
    btn.addEventListener('click', function() {
        const id = this.dataset.id;
        const nama = this.dataset.nama;
        Swal.fire({
            title: 'Yakin hapus?',
            html: `Pelanggan <strong>${nama}</strong> akan dihapus permanen.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Ya, hapus',
            cancelButtonText: 'Batal'
        }).then(res => {
            if (res.isConfirmed) {
                window.location.href = '?hapus=' + encodeURIComponent(id);
            }
        });
    });
});

document.querySelectorAll('.reset-password-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const id = this.dataset.id;
        const nama = this.dataset.nama;
        Swal.fire({
            title: 'Reset Password?',
            html: `Password pelanggan <strong>${nama}</strong> akan direset menjadi "123456".`,
            icon: 'info',
            showCancelButton: true,
            confirmButtonColor: '#0dcaf0',
            confirmButtonText: 'Ya, Reset',
            cancelButtonText: 'Batal'
        }).then(res => {
            if (res.isConfirmed) {
                window.location.href = '?reset_password=' + encodeURIComponent(id);
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
    timer: 2000,
    showConfirmButton: false
});
<?php endif; ?>

// show deleted notification via GET param
<?php if (isset($_GET['status']) && $_GET['status'] === 'deleted'): ?>
Swal.fire({
    icon: 'success',
    title: 'Pelanggan berhasil dihapus',
    timer: 1400,
    showConfirmButton: false
});
<?php endif; ?>

<?php if (isset($_GET['status']) && $_GET['status'] === 'password_reset'): ?>
Swal.fire({
    icon: 'success',
    title: 'Password berhasil direset',
    text: 'Password baru pelanggan adalah "123456".',
    timer: 2500,
    showConfirmButton: false
});
<?php endif; ?>

</script>

</body>
</html>