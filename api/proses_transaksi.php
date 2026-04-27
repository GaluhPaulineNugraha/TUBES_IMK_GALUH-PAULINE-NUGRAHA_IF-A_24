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
    // 🔴 VALIDASI STOK PERTAMA: Cek semua item sebelum proses
    $stmtCek = $pdo->prepare("SELECT id, stok, nama_barang FROM menu WHERE id = ?");
    $stokError = [];
    
    foreach ($items as $item) {
        $stmtCek->execute([$item['id']]);
        $menu = $stmtCek->fetch();
        if (!$menu) {
            throw new Exception("Menu dengan ID {$item['id']} tidak ditemukan");
        }
        if ($menu['stok'] < $item['qty']) {
            $stokError[] = "{$menu['nama_barang']} (stok: {$menu['stok']}, diminta: {$item['qty']})";
        }
    }
    
    if (!empty($stokError)) {
        throw new Exception("Stok tidak mencukupi untuk: " . implode(", ", $stokError));
    }
    
    // Jika lolos validasi, mulai transaksi
    $pdo->beginTransaction();
    
    $stmt = $pdo->prepare("INSERT INTO transaksi (kode_transaksi, id_kasir, tipe_order, subtotal, pajak, total, metode_pembayaran) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$kode_transaksi, $id_kasir, $tipe_order, $subtotal, $pajak, $total, $metode]);
    $id_transaksi = $pdo->lastInsertId();
    
    foreach ($items as $item) {
        $stmt = $pdo->prepare("INSERT INTO detail_transaksi (id_transaksi, id_menu, qty, harga_satuan, subtotal) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$id_transaksi, $item['id'], $item['qty'], $item['price'], $item['price'] * $item['qty']]);
        
        $stmt = $pdo->prepare("UPDATE menu SET stok = stok - ? WHERE id = ? AND stok >= ?");
        $stmt->execute([$item['qty'], $item['id'], $item['qty']]);
        
        if ($stmt->rowCount() === 0) {
            throw new Exception("Gagal update stok untuk item ID {$item['id']}. Stok mungkin tidak cukup.");
        }
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
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>