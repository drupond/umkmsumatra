<?php
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama   = mysqli_real_escape_string($conn, $_POST['nama']);
    $email  = mysqli_real_escape_string($conn, $_POST['email']);
    $pesan  = mysqli_real_escape_string($conn, $_POST['pesan']);

    $query = "INSERT INTO tb_pesan (nama, email, pesan) VALUES ('$nama', '$email', '$pesan')";
    if (mysqli_query($conn, $query)) {
        echo "<script>alert('Pesan berhasil dikirim!');window.location='index.php#kontak';</script>";
    } else {
        echo "<script>alert('Gagal mengirim pesan.');window.location='index.php#kontak';</script>";
    }
}
