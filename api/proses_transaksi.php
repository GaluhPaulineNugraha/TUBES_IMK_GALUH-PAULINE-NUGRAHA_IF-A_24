<?php
date_default_timezone_set('Asia/Jakarta');
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

// Log untuk debugging
error_log("=== PROSES TRANSAKSI ===");
error_log("Kode: " . $kode_transaksi);
error_log("Kasir: " . $id_kasir);
error_log("Total: " . $total);
error_log("Items: " . json_encode($items));

try {
    // VALIDASI STOK
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
    
    // Mulai transaksi database
    $pdo->beginTransaction();
    
    // Ambil waktu SERVER saat ini
    $currentDateTime = date('Y-m-d H:i:s');
    $tanggalStruk = date('d/m/Y H:i:s');
    
    // INSERT ke tabel transaksi
    $stmt = $pdo->prepare("INSERT INTO transaksi (kode_transaksi, id_kasir, tipe_order, subtotal, pajak, total, metode_pembayaran, tanggal_transaksi) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $result = $stmt->execute([$kode_transaksi, $id_kasir, $tipe_order, $subtotal, $pajak, $total, $metode, $currentDateTime]);
    
    if (!$result) {
        throw new Exception("Gagal insert transaksi: " . json_encode($stmt->errorInfo()));
    }
    
    $id_transaksi = $pdo->lastInsertId();
    error_log("ID Transaksi: " . $id_transaksi);
    
    // INSERT detail transaksi dan update stok
    foreach ($items as $item) {
        // Insert detail
        $stmt = $pdo->prepare("INSERT INTO detail_transaksi (id_transaksi, id_menu, qty, harga_satuan, subtotal) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$id_transaksi, $item['id'], $item['qty'], $item['price'], $item['price'] * $item['qty']]);
        
        // Update stok
        $stmt = $pdo->prepare("UPDATE menu SET stok = stok - ? WHERE id = ? AND stok >= ?");
        $stmt->execute([$item['qty'], $item['id'], $item['qty']]);
        
        if ($stmt->rowCount() === 0) {
            throw new Exception("Gagal update stok untuk item ID {$item['id']}");
        }
    }
    
    $pdo->commit();
    error_log("TRANSAKSI BERHASIL: " . $kode_transaksi);
    
    echo json_encode([
        'success' => true,
        'data' => [
            'kode_transaksi' => $kode_transaksi,
            'tanggal' => $tanggalStruk,
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
    error_log("ERROR: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>