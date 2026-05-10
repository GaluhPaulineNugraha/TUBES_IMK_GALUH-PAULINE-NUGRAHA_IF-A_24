<?php
require_once 'config/db.php';

if (!isset($_SESSION['kasir_id'])) {
    header('Location: index.php');
    exit();
}

$filter = $_GET['filter'] ?? 'today';
switch($filter){
    case 'yesterday': 
        $cond = "DATE(tanggal_transaksi) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)"; 
        break;
    case 'threeDays': 
        $cond = "tanggal_transaksi >= DATE_SUB(NOW(), INTERVAL 3 DAY)"; 
        break;
    case 'sevenDays': 
        $cond = "tanggal_transaksi >= DATE_SUB(NOW(), INTERVAL 7 DAY)"; 
        break;
    default: 
        $cond = "DATE(tanggal_transaksi) = CURDATE()"; 
        $filter = 'today';
}

// Ambil total penjualan dan jumlah transaksi
$stmt = $pdo->prepare("SELECT COALESCE(SUM(total),0) as total, COUNT(*) as count FROM transaksi WHERE $cond");
$stmt->execute(); 
$stats = $stmt->fetch();

// Ambil total pajak
$stmt = $pdo->prepare("SELECT COALESCE(SUM(pajak),0) as pajak FROM transaksi WHERE $cond");
$stmt->execute(); 
$totalPajak = $stmt->fetchColumn();

// Ambil total dine in
$stmt = $pdo->prepare("SELECT COALESCE(SUM(total),0) as dinein FROM transaksi WHERE $cond AND tipe_order='dinein'");
$stmt->execute(); 
$dinein = $stmt->fetchColumn();

// Ambil total dine out
$stmt = $pdo->prepare("SELECT COALESCE(SUM(total),0) as dineout FROM transaksi WHERE $cond AND tipe_order='dineout'");
$stmt->execute(); 
$dineout = $stmt->fetchColumn();

// Ambil semua transaksi dengan detail kasir
$stmt = $pdo->prepare("
    SELECT t.*, k.nama as kasir_nama 
    FROM transaksi t 
    JOIN kasir k ON t.id_kasir = k.id 
    WHERE $cond 
    ORDER BY t.tanggal_transaksi DESC
");
$stmt->execute(); 
$transactions = $stmt->fetchAll();

// Ambil rata-rata transaksi per hari (untuk periode multi-hari)
$avgPerDay = 0;
if (in_array($filter, ['threeDays', 'sevenDays'])) {
    $days = $filter == 'threeDays' ? 3 : 7;
    $avgPerDay = $days > 0 ? $stats['total'] / $days : 0;
}
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
    <style>
        /* Tambahan style untuk analisis */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            display: flex;
            align-items: center;
            gap: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .stat-card .stat-icon {
            width: 60px;
            height: 60px;
            background: #f5f0e8;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .stat-card .stat-icon i {
            font-size: 30px;
            color: #E8B84B;
        }
        .stat-card .stat-info h3 {
            font-size: 12px;
            color: #666;
            margin-bottom: 5px;
        }
        .stat-card .stat-info .stat-value {
            font-size: 24px;
            font-weight: 700;
            color: #2A4B2F;
        }
        .stat-card .stat-info .stat-sub {
            font-size: 11px;
            color: #999;
            margin-top: 3px;
        }
        .detail-report {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 25px;
        }
        .detail-report .report-header {
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f5f0e8;
        }
        .report-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        .stat-detail {
            background: #faf7f2;
            padding: 15px;
            border-radius: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .stat-detail span:first-child {
            color: #666;
        }
        .stat-detail strong {
            color: #2A4B2F;
            font-size: 16px;
        }
        .detail-transaksi-section {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-top: 20px;
        }
        .detail-transaksi-section h3 {
            margin-bottom: 15px;
            color: #2A4B2F;
        }
        .table-wrapper {
            overflow-x: auto;
        }
        .transaksi-table {
            width: 100%;
            border-collapse: collapse;
        }
        .transaksi-table th,
        .transaksi-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e0d5c5;
        }
        .transaksi-table th {
            background: #faf7f2;
            font-weight: 600;
            color: #2A4B2F;
        }
        .transaksi-table tr:hover {
            background: #faf7f2;
        }
        .empty-row td {
            text-align: center;
            color: #999;
            padding: 40px;
        }
        .badge-method {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
        }
        .badge-cash {
            background: #dcfce7;
            color: #166534;
        }
        .badge-qris {
            background: #fef3c7;
            color: #92400e;
        }
        .badge-dinein {
            background: #e0e7ff;
            color: #3730a3;
        }
        .badge-dineout {
            background: #fce7f3;
            color: #9d174d;
        }
    </style>
</head>
<body>
    <div class="app-container">
        <aside class="sidebar">
            <div class="sidebar-header" style="justify-content: center;">
                <h3 style="text-align: center; width: 100%;">Resto Serba Serbi</h3>
            </div>
            <nav class="sidebar-nav">
                <a href="stok.php" class="nav-item"><i class="fas fa-boxes"></i><span>Lihat Stok</span></a>
                <a href="kasir.php" class="nav-item"><i class="fas fa-cash-register"></i><span>Kasir</span></a>
                 <a href="analisis.php" class="nav-item active"><i class="fas fa-chart-bar"></i><span>Analisis Laporan</span></a>
                <a href="profil.php" class="nav-item"><i class="fas fa-user-circle"></i><span>Profil Kasir</span></a>
            </nav>
    <div class="sidebar-footer">
    <a href="logout.php" class="btn-logout">
        <i class="fas fa-sign-out-alt" style="transform: rotate(180deg);"></i>
        <span>Keluar</span>
    </a>
</div>
        </aside>

        <main class="main-content">
            <div class="top-bar">
                <div class="page-title">
                    <h1><i class="fas fa-chart-bar"></i> ANALISIS LAPORAN</h1>
                </div>
                <div class="date-time">
                    <i class="far fa-calendar-alt"></i>
                    <span id="currentDate"></span>
                </div>
            </div>

            <!-- Filter Periode -->
            <div class="analisis-filters" style="display: flex; gap: 10px; margin-bottom: 25px; flex-wrap: wrap;">
                <a href="?filter=today" class="filter-btn <?= $filter == 'today' ? 'active' : '' ?>" style="padding: 10px 20px; border: 1px solid #E8B84B; background: <?= $filter == 'today' ? '#2A4B2F' : 'white'; ?>; border-radius: 10px; cursor: pointer; text-decoration: none; color: <?= $filter == 'today' ? 'white' : '#2A4B2F'; ?>;">
                    <i class="fas fa-calendar-day"></i> HARI INI
                </a>
                <a href="?filter=yesterday" class="filter-btn <?= $filter == 'yesterday' ? 'active' : '' ?>" style="padding: 10px 20px; border: 1px solid #E8B84B; background: <?= $filter == 'yesterday' ? '#2A4B2F' : 'white'; ?>; border-radius: 10px; cursor: pointer; text-decoration: none; color: <?= $filter == 'yesterday' ? 'white' : '#2A4B2F'; ?>;">
                    <i class="fas fa-calendar-minus"></i> KEMARIN
                </a>
                <a href="?filter=threeDays" class="filter-btn <?= $filter == 'threeDays' ? 'active' : '' ?>" style="padding: 10px 20px; border: 1px solid #E8B84B; background: <?= $filter == 'threeDays' ? '#2A4B2F' : 'white'; ?>; border-radius: 10px; cursor: pointer; text-decoration: none; color: <?= $filter == 'threeDays' ? 'white' : '#2A4B2F'; ?>;">
                    <i class="fas fa-chart-line"></i> 3 HARI TERAKHIR
                </a>
                <a href="?filter=sevenDays" class="filter-btn <?= $filter == 'sevenDays' ? 'active' : '' ?>" style="padding: 10px 20px; border: 1px solid #E8B84B; background: <?= $filter == 'sevenDays' ? '#2A4B2F' : 'white'; ?>; border-radius: 10px; cursor: pointer; text-decoration: none; color: <?= $filter == 'sevenDays' ? 'white' : '#2A4B2F'; ?>;">
                    <i class="fas fa-chart-line"></i> 7 HARI TERAKHIR
                </a>
            </div>

            <!-- Statistik Utama -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-chart-simple"></i></div>
                    <div class="stat-info">
                        <h3>TOTAL PENJUALAN</h3>
                        <p class="stat-value">Rp <?= number_format($stats['total'], 0, ',', '.') ?></p>
                        <?php if ($avgPerDay > 0): ?>
                        <p class="stat-sub">Rata-rata/hari: Rp <?= number_format($avgPerDay, 0, ',', '.') ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-receipt"></i></div>
                    <div class="stat-info">
                        <h3>TOTAL TRANSAKSI</h3>
                        <p class="stat-value"><?= $stats['count'] ?></p>
                        <p class="stat-sub">Transaksi</p>
                    </div>
                </div>
            </div>

            <!-- Detail Laporan -->
            <div class="detail-report">
                <div class="report-header">
                    <h3><i class="fas fa-chart-pie"></i> DETAIL PENJUALAN</h3>
                </div>
                <div class="report-stats">
                    <div class="stat-detail">
                        <span><i class="fas fa-money-bill-wave"></i> Total Pendapatan:</span>
                        <strong>Rp <?= number_format($stats['total'], 0, ',', '.') ?></strong>
                    </div>
                    <div class="stat-detail">
                        <span><i class="fas fa-percent"></i> Total Pajak (5% Dine In):</span>
                        <strong>Rp <?= number_format($totalPajak, 0, ',', '.') ?></strong>
                    </div>
                    <div class="stat-detail">
                        <span><i class="fas fa-chair"></i> Dine In:</span>
                        <strong>Rp <?= number_format($dinein, 0, ',', '.') ?></strong>
                    </div>
                    <div class="stat-detail">
                        <span><i class="fas fa-shopping-bag"></i> Dine Out:</span>
                        <strong>Rp <?= number_format($dineout, 0, ',', '.') ?></strong>
                    </div>
                </div>
            </div>

            <!-- Daftar Transaksi -->
            <div class="detail-transaksi-section">
                <h3><i class="fas fa-list-ul"></i> DAFTAR TRANSAKSI</h3>
                <div class="table-wrapper">
                    <table class="transaksi-table">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Waktu</th>
                                <th>Kasir</th>
                                <th>Tipe</th>
                                <th>Subtotal</th>
                                <th>Pajak</th>
                                <th>Grand Total</th>
                                <th>Metode</th>
                                <th>Kode Transaksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($transactions)): ?>
                                <tr class="empty-row">
                                    <td colspan="9">
                                        <i class="fas fa-inbox"></i> Belum ada transaksi pada periode ini
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($transactions as $t): ?>
                                    <tr>
                                        <td><?= date('d/m/Y', strtotime($t['tanggal_transaksi'])) ?></td>
                                        <td><?= date('H:i:s', strtotime($t['tanggal_transaksi'])) ?></td>
                                        <td><?= htmlspecialchars($t['kasir_nama']) ?></td>
                                        <td>
                                            <span class="badge-method <?= $t['tipe_order'] == 'dinein' ? 'badge-dinein' : 'badge-dineout' ?>">
                                                <?= $t['tipe_order'] == 'dinein' ? '🍽️ Dine In' : '🥡 Dine Out' ?>
                                            </span>
                                        </td>
                                        <td>Rp <?= number_format($t['subtotal'], 0, ',', '.') ?></td>
                                        <td>Rp <?= number_format($t['pajak'], 0, ',', '.') ?></td>
                                        <td><strong>Rp <?= number_format($t['total'], 0, ',', '.') ?></strong></td>
                                        <td>
                                            <span class="badge-method <?= $t['metode_pembayaran'] == 'cash' ? 'badge-cash' : 'badge-qris' ?>">
                                                <?= $t['metode_pembayaran'] == 'cash' ? '💵 CASH' : '📱 QRIS' ?>
                                            </span>
                                        </td>
                                        <td><small><?= $t['kode_transaksi'] ?></small></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <script src="js/main.js"></script>
    <script>
        // Update date time display
        function updateDateTime() {
            const options = { weekday: 'long', year: 'numeric', month: 'numeric', day: 'numeric' };
            const dateElement = document.getElementById('currentDate');
            if (dateElement) {
               dateElement.innerHTML = new Date().toLocaleDateString('id-ID', options);
            }
        }
        updateDateTime();
   
    </script>
</body>
</html>