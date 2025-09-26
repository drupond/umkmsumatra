<?php
session_start();
if (!isset($_SESSION['login'])) {
    header("Location: auth/login.php");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
  <title>Dashboard</title>
</head>
<body>
  <h2>Halo, <?= $_SESSION['username'] ?>! Anda login sebagai <?= $_SESSION['role'] ?>.</h2>
  <a href="auth/logout.php">Logout</a>
</body>
</html>
