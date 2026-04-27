<?php
require_once '../config/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method tidak diizinkan']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Data tidak valid']);
    exit();
}

$kode_transaksi = $data['kode_transaksi'] ?? '';
$id_kasir = $data['id_kasir'] ?? 0;
$tipe_order = $data['tipe_order'] ?? 'dinein';
$subtotal = $data['subtotal'] ?? 0;
$pajak = $data['pajak'] ?? 0;
$total = $data['total'] ?? 0;
$metode = $data['metode'] ?? 'cash';
$items = $data['items'] ?? [];

try {
    $pdo->beginTransaction();
    
    $stmt = $pdo->prepare("INSERT INTO transaksi (kode_transaksi, id_kasir, tipe_order, subtotal, pajak, total, metode_pembayaran) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$kode_transaksi, $id_kasir, $tipe_order, $subtotal, $pajak, $total, $metode]);
    $id_transaksi = $pdo->lastInsertId();
    
    foreach ($items as $item) {
        $stmt = $pdo->prepare("INSERT INTO detail_transaksi (id_transaksi, id_menu, qty, harga_satuan, subtotal) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$id_transaksi, $item['id'], $item['qty'], $item['price'], $item['price'] * $item['qty']]);
        
        $stmt = $pdo->prepare("UPDATE menu SET stok = stok - ? WHERE id = ?");
        $stmt->execute([$item['qty'], $item['id']]);
    }
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'data' => [
            'kode_transaksi' => $kode_transaksi,
            'tanggal' => date('d/m/Y H:i:s'),
            'items' => $items,
            'subtotal' => $subtotal,
            'pajak' => $pajak,
            'total' => $total
        ]
    ]);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>