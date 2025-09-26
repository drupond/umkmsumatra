<?php
session_start();
session_destroy();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logout</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body {
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background-color: #f8f9fa;
        }
    </style>
</head>
<body>
    <script>
        // Tampilkan SweetAlert2 saat halaman dimuat
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                icon: 'success',
                title: 'Berhasil keluar!',
                text: 'Sampai jumpa kembali.',
                showConfirmButton: false,
                timer: 1500, // Pop-up akan hilang setelah 1.5 detik
                timerProgressBar: true,
                allowOutsideClick: false,
                allowEscapeKey: false,
                didClose: () => {
                    // Redirect ke halaman login setelah pop-up hilang
                    window.location.href = 'auth.php';
                }
            });
        });
    </script>
</body>
</html>