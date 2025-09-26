<?php
session_start();
include 'db.php';

if (!isset($_SESSION['username'])) {
  header("Location: auth/login.php");
  exit;
}

$username      = $_SESSION['username'];
$nama          = $_POST['nama'];
$email         = $_POST['email'];
$telepon       = $_POST['telepon'];
$jenis_kelamin = $_POST['jenis_kelamin'];

// Kalau kosong, jadikan NULL
$tgl_lahir = !empty($_POST['tgl_lahir']) ? $_POST['tgl_lahir'] : null;

$foto = null;
if (!empty($_FILES['foto']['name'])) {
    $targetDir = "uploads/";
    if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
    $fileName = time() . "_" . basename($_FILES["foto"]["name"]);
    $targetFilePath = $targetDir . $fileName;
    move_uploaded_file($_FILES["foto"]["tmp_name"], $targetFilePath);
    $foto = $fileName;
}

// Siapkan query
$sql = "UPDATE tb_user SET 
        nama='$nama',
        email='$email',
        telepon='$telepon',
        jenis_kelamin='$jenis_kelamin'";

// Tambahkan tgl_lahir (gunakan NULL tanpa kutip kalau kosong)
if ($tgl_lahir) {
    $sql .= ", tgl_lahir='$tgl_lahir'";
} else {
    $sql .= ", tgl_lahir=NULL";
}

// Tambahkan foto jika ada
if ($foto) {
    $sql .= ", foto='$foto'";
}

$sql .= " WHERE username='$username'";

if (mysqli_query($conn, $sql)) {
    header("Location: profil.php?success=1");
    exit;
} else {
    echo "Error: " . mysqli_error($conn);
}
