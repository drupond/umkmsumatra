<?php
include 'db.php';
header('Content-Type: application/json');

$province_id = isset($_GET['province_id']) ? intval($_GET['province_id']) : 0;
$cities = [];

if ($province_id > 0) {
    $stmt = $conn->prepare("SELECT id, name FROM tb_regencies WHERE province_id = ? ORDER BY name ASC");
    $stmt->bind_param("i", $province_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $cities[] = $row;
    }
    $stmt->close();
}

echo json_encode($cities);
?>