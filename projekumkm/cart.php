<?php
$cart = mysqli_query($conn, "
  SELECT c.id, p.nama, p.harga, c.qty, (p.harga * c.qty) as total
  FROM tb_cart c
  JOIN tb_produk p ON c.produk_id = p.id
  WHERE c.user_id='$user_id'
");
?>
