<?php
require_once 'config/db.php';

if (!isset($_SESSION['kasir_id'])) {
    header('Location: index.php');
    exit();
}

$filter = $_GET['filter'] ?? 'today';
switch($filter){
    case 'yesterday': $cond = "DATE(tanggal_transaksi)=DATE_SUB(CURDATE(),INTERVAL 1 DAY)"; break;
    case 'threeDays': $cond = "tanggal_transaksi>=DATE_SUB(NOW(),INTERVAL 3 DAY)"; break;
    case 'sevenDays': $cond = "tanggal_transaksi>=DATE_SUB(NOW(),INTERVAL 7 DAY)"; break;
    default: $cond = "DATE(tanggal_transaksi)=CURDATE()"; $filter='today';
}

$stmt = $pdo->prepare("SELECT COALESCE(SUM(total),0) as total, COUNT(*) as count FROM transaksi WHERE $cond");
$stmt->execute(); $stats = $stmt->fetch();
$stmt = $pdo->prepare("SELECT COALESCE(SUM(pajak),0) as pajak FROM transaksi WHERE $cond");
$stmt->execute(); $totalPajak = $stmt->fetchColumn();
$stmt = $pdo->prepare("SELECT COALESCE(SUM(total),0) as dinein FROM transaksi WHERE $cond AND tipe_order='dinein'");
$stmt->execute(); $dinein = $stmt->fetchColumn();
$stmt = $pdo->prepare("SELECT COALESCE(SUM(total),0) as dineout FROM transaksi WHERE $cond AND tipe_order='dineout'");
$stmt->execute(); $dineout = $stmt->fetchColumn();
$stmt = $pdo->prepare("SELECT t.*, k.nama as kasir_nama FROM transaksi t JOIN kasir k ON t.id_kasir=k.id WHERE $cond ORDER BY t.tanggal_transaksi DESC");
$stmt->execute(); $transactions = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resto Serba Serbi - Analisis Laporan</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="app-container">
       <aside class="sidebar">
    <div class="sidebar-header" style="justify-content: center;">
        <h3 style="text-align: center; width: 100%;">Resto Serba Serbi</h3>
    </div>
    <nav class="sidebar-nav">
                <a href="analisis.php" class="nav-item active"><i class="fas fa-chart-bar"></i><span>Analisis Laporan</span></a>
                <a href="kasir.php" class="nav-item"><i class="fas fa-cash-register"></i><span>Kasir</span></a>
                <a href="stok.php" class="nav-item"><i class="fas fa-boxes"></i><span>Manajemen Stok</span></a>
                <a href="profil.php" class="nav-item"><i class="fas fa-user-circle"></i><span>Profil Kasir</span></a>

    </nav>
    <div class="sidebar-footer">
        <a href="logout.php" class="btn-logout"><i class="fas fa-sign-out-alt"></i><span>Keluar</span></a>
    </div>
</aside>

        <main class="main-content">
            <div class="top-bar"><div class="page-title"><h1><i class="fas fa-chart-bar"></i> ANALISIS LAPORAN</h1></div><div class="date-time"><i class="far fa-calendar-alt"></i><span id="currentDate"></span></div></div>
            <div class="analisis-filters">
                <a href="?filter=today" class="filter-btn <?= $filter=='today'?'active':'' ?>">HARI INI</a>
                <a href="?filter=yesterday" class="filter-btn <?= $filter=='yesterday'?'active':'' ?>">KEMARIN</a>
                <a href="?filter=threeDays" class="filter-btn <?= $filter=='threeDays'?'active':'' ?>">3 HARI YANG LALU</a>
                <a href="?filter=sevenDays" class="filter-btn <?= $filter=='sevenDays'?'active':'' ?>">7 HARI YANG LALU</a>
            </div>
            <div class="stats-grid">
                <div class="stat-card"><div class="stat-icon"><i class="fas fa-chart-simple"></i></div><div class="stat-info"><h3>TOTAL PENJUALAN (RP)</h3><p class="stat-value">Rp <?= number_format($stats['total'],0,',','.') ?></p></div></div>
                <div class="stat-card"><div class="stat-icon"><i class="fas fa-receipt"></i></div><div class="stat-info"><h3>TOTAL TRANSAKSI</h3><p class="stat-value"><?= $stats['count'] ?></p></div></div>
            </div>
            <div class="detail-report"><div class="report-header"><h3>DETAIL PENJUALAN</h3></div>
                <div class="report-stats">
                    <div class="stat-detail"><span>Total Pendapatan:</span><strong>Rp <?= number_format($stats['total'],0,',','.') ?></strong></div>
                    <div class="stat-detail"><span>Total Pajak:</span><strong>Rp <?= number_format($totalPajak,0,',','.') ?></strong></div>
                    <div class="stat-detail"><span>Total Dine In:</span><strong>Rp <?= number_format($dinein,0,',','.') ?></strong></div>
                    <div class="stat-detail"><span>Total Dine Out:</span><strong>Rp <?= number_format($dineout,0,',','.') ?></strong></div>
                </div>
            </div>
            <div class="detail-transaksi-section"><h3><i class="fas fa-list-ul"></i> DAFTAR TRANSAKSI</h3>
                <div class="table-wrapper"><table class="transaksi-table"><thead><tr><th>Tanggal</th><th>Waktu</th><th>Kasir</th><th>Tipe</th><th>Subtotal</th><th>Pajak</th><th>Grand Total</th><th>Metode</th></tr></thead><tbody>
                <?php if(empty($transactions)): ?><tr class="empty-row"><td colspan="8">Belum ada transaksi</td></tr>
                <?php else: foreach($transactions as $t): ?>
                <tr>
                    <td><?= date('d/m/Y',strtotime($t['tanggal_transaksi'])) ?></td>
                    <td><?= date('H:i',strtotime($t['tanggal_transaksi'])) ?></td>
                    <td><?= htmlspecialchars($t['kasir_nama']) ?></td>
                    <td><?= $t['tipe_order']=='dinein'?'Dine In':'Dine Out' ?></td>
                    <td>Rp <?= number_format($t['subtotal'],0,',','.') ?></td>
                    <td>Rp <?= number_format($t['pajak'],0,',','.') ?></td>
                    <td>Rp <?= number_format($t['total'],0,',','.') ?></td>
                    <td><?= strtoupper($t['metode_pembayaran']) ?></td>
                </tr>
                <?php endforeach; endif; ?>
                </tbody></table></div>
            </div>
        </main>
    </div>
    <script src="js/main.js"></script>
</body>
</html>