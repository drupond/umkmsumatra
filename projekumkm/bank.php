<?php
/**
 * bank.php
 * Halaman untuk mengelola bank dan kartu pengguna.
 */

session_start();

// Redirect jika belum login
if (!isset($_SESSION['username'])) {
    header("Location: auth/login.php");
    exit;
}

// Sertakan koneksi database
include 'db.php';

// Ambil data user dari database
$username = $_SESSION['username'];
$user = null;
$stmt = mysqli_prepare($conn, "SELECT id, username, nama, foto FROM tb_user WHERE username = ? LIMIT 1");
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "s", $username);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($res);
    mysqli_stmt_close($stmt);
}

if (!$user) {
    session_destroy();
    header("Location: auth/login.php?msg=user_not_found");
    exit;
}

$uploadBaseDir = __DIR__ . '/uploads/profile/';
$publicUploadBase = 'uploads/profile/'; 

// Data untuk bank populer di Indonesia dengan sumber logo HD
$popular_banks = [
    ['name' => 'Bank Central Asia (BCA)', 'logo' => 'https://upload.wikimedia.org/wikipedia/commons/5/5c/Bank_Central_Asia.svg'],
    ['name' => 'Bank Mandiri', 'logo' => 'https://upload.wikimedia.org/wikipedia/commons/a/ad/Bank_Mandiri_logo_2016.svg'],
    ['name' => 'Bank Rakyat Indonesia (BRI)', 'logo' => 'https://upload.wikimedia.org/wikipedia/commons/f/f6/Logo_BRI_2020.svg'],
    ['name' => 'Bank Negara Indonesia (BNI)', 'logo' => 'https://upload.wikimedia.org/wikipedia/commons/b/b3/BNI_logo.svg'],
    ['name' => 'Bank Syariah Indonesia (BSI)', 'logo' => 'https://upload.wikimedia.org/wikipedia/commons/5/52/Bank_Syariah_Indonesia_%28BSI%29_logo_2022.svg'],
    ['name' => 'CIMB Niaga', 'logo' => 'https://upload.wikimedia.org/wikipedia/commons/4/4b/CIMB_Niaga.svg'],
    ['name' => 'Bank Danamon', 'logo' => 'https://upload.wikimedia.org/wikipedia/commons/e/ec/Bank_Danamon_logo.svg'],
    ['name' => 'Bank OCBC NISP', 'logo' => 'https://upload.wikimedia.org/wikipedia/commons/e/e0/OCBC_NISP_Logo.svg']
];

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1"/>
    <title>Bank & Kartu - MinangMaknyus</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet"/>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        body { font-family: 'Helvetica Neue', Arial, sans-serif; }
        .main-accent { background-color: #ff5722; color: white; }
        .btn-accent { background-color: #ff5722; color: white; padding: 0.5rem 1rem; border-radius: 4px; transition: background-color 0.2s; }
        .btn-accent:hover { background-color: #e64a19; }
        .card { background: white; border-radius: 4px; box-shadow: 0 1px 2px rgba(0,0,0,0.08); }
        input, select { border-color: #e2e8f0; }
        input:focus, select:focus { outline: 2px solid #ff5722; outline-offset: -2px; }
        .nav-link { display: flex; align-items: center; gap: 12px; padding: 8px 16px; color: #4a4a4a; font-weight: 500; transition: color 0.2s, background-color 0.2s; }
        .nav-link:hover { color: #ff5722; }
        .nav-link.active { color: #ff5722; background-color: rgba(255, 87, 34, 0.08); }
        .nav-link.active::before { content: ''; position: absolute; left: 0; top: 0; bottom: 0; width: 4px; background-color: #ff5722; }
        .nav-link i { width: 20px; text-align: center; }
        .bank-card { transition: transform 0.2s; }
        .bank-card:hover { transform: translateY(-4px); }
    </style>
</head>
<body class="bg-[#f5f5f5] text-slate-800">

<header class="main-accent">
    <div class="max-w-6xl mx-auto flex items-center justify-between px-4 py-3">
        <div class="flex items-center gap-3">
            <img src="assets/img/logo.png" alt="logo" class="w-10 h-10 object-contain" onerror="this.src='https://placehold.co/40x40'"/>
            <div class="text-white text-lg font-bold">MinangMaknyus</div>
        </div>
        <div class="flex-1 max-w-lg mx-4 relative hidden sm:block"></div>
        <div class="flex items-center gap-6 text-sm">
            <div class="relative group">
                <a href="#" class="flex items-center gap-2 text-white hover:opacity-90">
                    <div class="w-6 h-6 rounded-full overflow-hidden bg-white border-2 border-white">
                        <img src="<?= (!empty($user['foto']) && file_exists($uploadBaseDir . $user['foto'])) ? htmlspecialchars($publicUploadBase . $user['foto']) : 'assets/img/profil.png' ?>" alt="avatar" class="w-full h-full object-cover">
                    </div>
                    <div class="text-sm"><?= htmlspecialchars($user['username']) ?></div>
                    <i class="fa-solid fa-angle-down text-xs"></i>
                </a>
                <div class="absolute right-0 mt-2 py-2 w-48 bg-white rounded-md shadow-xl opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-300 z-10">
                    <a href="index.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"><i class="fa-solid fa-house-chimney mr-2"></i> Kembali ke Beranda</a>
                    <a href="auth/logout.php" class="block px-4 py-2 text-sm text-red-600 hover:bg-gray-100"><i class="fa-solid fa-arrow-right-from-bracket mr-2"></i> Keluar</a>
                </div>
            </div>
        </div>
    </div>
</header>

<main class="max-w-6xl mx-auto px-4 pt-8 pb-12">
    <div class="flex flex-col md:flex-row gap-4">
        <aside class="w-full md:w-64">
            <div class="bg-white p-4">
                <div class="flex items-center gap-2 mb-4">
                    <div class="w-10 h-10 rounded-full overflow-hidden bg-gray-100 border">
                        <img src="<?= (!empty($user['foto']) && file_exists($uploadBaseDir . $user['foto'])) ? htmlspecialchars($publicUploadBase . $user['foto']) : 'assets/img/profil.png' ?>" alt="avatar" class="w-full h-full object-cover">
                    </div>
                    <div>
                        <div class="font-medium text-sm"><?= htmlspecialchars($user['nama'] ?? $user['username']) ?></div>
                        <a href="profil.php" class="text-xs text-gray-500 hover:text-gray-700">
                            <i class="fa-regular fa-pen-to-square mr-1"></i>Ubah Profil
                        </a>
                    </div>
                </div>
                <nav class="text-sm space-y-2">
                    <h3 class="font-bold text-gray-500 text-xs tracking-wide uppercase mt-6 mb-2">Akun Saya</h3>
                    <a href="profil.php" class="nav-link relative">
                        <i class="fa-regular fa-user"></i>
                        <span>Profil</span>
                    </a>
                    <a href="bank.php" class="nav-link relative active">
                        <i class="fa-regular fa-credit-card"></i>
                        <span>Bank & Kartu</span>
                    </a>
                    <a href="alamat.php" class="nav-link relative">
                        <i class="fa-regular fa-map"></i>
                        <span>Alamat</span>
                    </a>
                    <a href="ubah_password.php" class="nav-link relative">
                        <i class="fa-solid fa-lock"></i>
                        <span>Ubah Password</span>
                    </a>
                    <a href="notifikasi.php" class="nav-link relative">
                        <i class="fa-regular fa-bell"></i>
                        <span>Pengaturan Notifikasi</span>
                    </a>
                    <a href="privasi.php" class="nav-link relative">
                        <i class="fa-solid fa-shield-halved"></i>
                        <span>Pengaturan Privasi</span>
                    </a>
                </nav>
            </div>
        </aside>

        <section class="flex-1">
            <div class="card p-6">
                <h2 class="text-lg font-semibold mb-1">Bank & Kartu Saya</h2>
                <p class="text-gray-500 text-sm mb-6">Tambahkan rekening bank atau kartu Anda untuk memudahkan transaksi.</p>
                
                <div class="flex flex-wrap gap-4">
                    <!-- Cards for popular banks -->
                    <?php foreach ($popular_banks as $bank): ?>
                        <div class="bank-card w-40 p-4 border rounded-md cursor-pointer flex flex-col items-center justify-center text-center shadow-sm hover:shadow-md">
                            <img src="<?= htmlspecialchars($bank['logo']) ?>" alt="<?= htmlspecialchars($bank['name']) ?>" class="h-12 w-auto mb-2" onerror="this.src='https://placehold.co/48x48?text=Bank'">
                            <span class="text-xs font-medium text-gray-700"><?= htmlspecialchars($bank['name']) ?></span>
                        </div>
                    <?php endforeach; ?>

                    <!-- Add new bank card -->
                    <div class="bank-card w-40 p-4 border border-dashed rounded-md cursor-pointer flex flex-col items-center justify-center text-center text-gray-500 hover:bg-gray-50">
                        <i class="fa-solid fa-plus text-xl mb-2"></i>
                        <span class="text-sm">Tambah Baru</span>
                    </div>
                </div>

                <div class="mt-8">
                    <h3 class="text-md font-semibold mb-3">Informasi Rekening Bank</h3>
                    <!-- Dummy form for adding bank account -->
                    <form action="#" method="post" class="space-y-4 max-w-lg">
                        <div>
                            <label for="bank_name" class="block text-sm font-medium text-gray-700">Nama Bank</label>
                            <input type="text" id="bank_name" name="bank_name" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[#ff5722] focus:ring-1 focus:ring-[#ff5722] sm:text-sm">
                        </div>
                        <div>
                            <label for="account_number" class="block text-sm font-medium text-gray-700">Nomor Rekening</label>
                            <input type="text" id="account_number" name="account_number" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[#ff5722] focus:ring-1 focus:ring-[#ff5722] sm:text-sm">
                        </div>
                        <div>
                            <label for="account_holder" class="block text-sm font-medium text-gray-700">Nama Pemilik Rekening</label>
                            <input type="text" id="account_holder" name="account_holder" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[#ff5722] focus:ring-1 focus:ring-[#ff5722] sm:text-sm">
                        </div>
                        <div class="pt-2">
                            <button type="submit" class="btn-accent px-6 py-2">Simpan Rekening</button>
                        </div>
                    </form>
                </div>
            </div>
        </section>
    </div>
</main>

<footer class="max-w-6xl mx-auto px-4 pb-12 pt-6 text-sm text-gray-500">
    <div class="flex items-center justify-between">
        <div>© <?= date('Y') ?> MinangMaknyus</div>
        <div>Butuh bantuan? <a href="mailto:support@minangmaknyus.id" class="text-[#ff5722]">support@minangmaknyus.id</a></div>
    </div>
</footer>

</body>
</html>
