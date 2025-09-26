<?php
// Pastikan path ke db.php sudah benar.
// Jika file db.php ada di satu level di atas folder 'api_daerah', gunakan '../db.php'.
// Jika file db.php ada di satu level yang sama, gunakan 'db.php'.
include 'db.php'; 

header('Content-Type: application/json');

$type = $_GET['type'] ?? '';
$id = intval($_GET['provinsi_id'] ?? $_GET['regency_id'] ?? 0);

$result = [];

// Tambahkan blok try-catch untuk menangkap error database
try {
    if ($type === 'regencies' && $id > 0) {
        $stmt = mysqli_prepare($conn, "SELECT id, name FROM tb_regencies WHERE province_id = ? ORDER BY name ASC");
        if ($stmt === false) {
            throw new Exception("Gagal menyiapkan statement: " . mysqli_error($conn));
        }
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_assoc($res)) {
            $result[] = $row;
        }
        mysqli_stmt_close($stmt);
    } elseif ($type === 'districts' && $id > 0) {
        $stmt = mysqli_prepare($conn, "SELECT id, name FROM tb_districts WHERE regency_id = ? ORDER BY name ASC");
        if ($stmt === false) {
            throw new Exception("Gagal menyiapkan statement: " . mysqli_error($conn));
        }
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_assoc($res)) {
            $result[] = $row;
        }
        mysqli_stmt_close($stmt);
    }
    
    echo json_encode($result);

} catch (Exception $e) {
    // Tangani error dan kirimkan respons JSON yang berisi pesan error
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

exit;