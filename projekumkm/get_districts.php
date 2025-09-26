<?php
include 'db.php';
header('Content-Type: application/json');

$regency_id = isset($_GET['regency_id']) ? intval($_GET['regency_id']) : 0;
$districts = [];

if ($regency_id > 0) {
    $stmt = $conn->prepare("SELECT id, name FROM tb_districts WHERE regency_id = ? ORDER BY name ASC");
    $stmt->bind_param("i", $regency_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $districts[] = $row;
    }
    $stmt->close();
}

echo json_encode($districts);
?>