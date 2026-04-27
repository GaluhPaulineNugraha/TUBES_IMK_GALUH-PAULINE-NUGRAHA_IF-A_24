<?php
require_once 'config/db.php';

if (!isset($_SESSION['kasir_id'])) {
    header('Location: index.php');
    exit();
}

$today = date('Y-m-d');
$stmt = $pdo->prepare("SELECT COALESCE(SUM(total),0) as total, COUNT(*) as count FROM transaksi WHERE DATE(tanggal_transaksi)=?");
$stmt->execute([$today]);
$stats = $stmt->fetch();

$trends = [];
foreach (['7 HARI'=>7, '3 HARI'=>3, 'KEMARIN'=>1, 'HARI INI'=>0] as $label=>$days) {
    if ($days==0) $stmt = $pdo->prepare("SELECT COALESCE(SUM(total),0) as total, COUNT(*) as count FROM transaksi WHERE DATE(tanggal_transaksi)=?");
    else $stmt = $pdo->prepare("SELECT COALESCE(SUM(total),0) as total, COUNT(*) as count FROM transaksi WHERE tanggal_transaksi >= DATE_SUB(NOW(), INTERVAL $days DAY)");
    $days==0 ? $stmt->execute([$today]) : $stmt->execute();
    $trends[$label] = $stmt->fetch();
}

$stmt = $pdo->prepare("SELECT t.*, k.nama as kasir_nama FROM transaksi t JOIN kasir k ON t.id_kasir=k.id WHERE DATE(t.tanggal_transaksi)=? ORDER BY t.tanggal_transaksi DESC");
$stmt->execute([$today]);
$transactions = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resto Serba Serbi - Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Inter',sans-serif; background:#f5f0e8; }
        .app-container { display:flex; height:100vh; overflow:hidden; }
        .sidebar { width:280px; background:#2A4B2F; display:flex; flex-direction:column; }
        .sidebar-header { padding:25px 20px; border-bottom:1px solid rgba(255,255,255,0.1); display:flex; align-items:center; gap:12px; }
        .sidebar-header i { font-size:28px; color:#B28538; }
        .sidebar-header h3 { font-size:18px; color:#B28538; }
        .sidebar-nav { flex:1; padding:20px 0; }
        .nav-item { display:flex; align-items:center; gap:15px; padding:14px 20px; color:rgba(255,255,255,0.8); text-decoration:none; margin:5px 10px; border-radius:12px; }
        .nav-item i { width:24px; }
        .nav-item:hover { background:rgba(178,133,56,0.2); color:#B28538; }
        .nav-item.active { background:#B28538; color:#2A4B2F; }
        .sidebar-footer { padding:20px; border-top:1px solid rgba(255,255,255,0.1); }
        .btn-logout { width:100%; padding:12px; background:rgba(255,255,255,0.1); color:white; border:none; border-radius:10px; cursor:pointer; display:flex; align-items:center; justify-content:center; gap:10px; text-decoration:none; }
        .btn-logout:hover { background:#dc2626; }
        .main-content { flex:1; overflow-y:auto; padding:20px 30px; }
        .top-bar { display:flex; justify-content:space-between; align-items:center; margin-bottom:30px; padding-bottom:15px; border-bottom:2px solid #e0d5c5; }
        .page-title h1 { font-size:24px; color:#2A4B2F; }
        .page-title h1 i { color:#B28538; margin-right:10px; }
        .page-title p { color:#666; margin-top:5px; }
        .date-time { background:white; padding:10px 20px; border-radius:10px; }
        .stats-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(250px,1fr)); gap:20px; margin-bottom:30px; }
        .stat-card { background:white; border-radius:15px; padding:20px; display:flex; align-items:center; gap:20px; }
        .stat-icon { width:60px; height:60px; background:#f5f0e8; border-radius:12px; display:flex; align-items:center; justify-content:center; }
        .stat-icon i { font-size:30px; color:#B28538; }
        .stat-info h3 { font-size:12px; color:#666; margin-bottom:5px; }
        .stat-value { font-size:24px; font-weight:700; color:#2A4B2F; }
        .analisis-section { background:white; border-radius:15px; padding:20px; margin-bottom:25px; }
        .analisis-section h2 { margin-bottom:20px; color:#2A4B2F; }
        .trend-list { display:flex; flex-direction:column; gap:10px; }
        .trend-item { display:flex; justify-content:space-between; padding:12px; background:#faf7f2; border-radius:8px; }
        .trend-label { font-weight:600; color:#2A4B2F; }
        .trend-value { color:#B28538; font-weight:500; }
        .detail-transaksi-section { background:white; border-radius:15px; padding:20px; margin-top:20px; }
        .detail-transaksi-section h3 { margin-bottom:15px; color:#2A4B2F; }
        .table-wrapper { overflow-x:auto; }
        .transaksi-table { width:100%; border-collapse:collapse; }
        .transaksi-table th, .transaksi-table td { padding:12px; text-align:left; border-bottom:1px solid #e0d5c5; }
        .transaksi-table th { background:#faf7f2; }
        .empty-row td { text-align:center; color:#999; padding:30px; }
        @media (max-width:900px) { .sidebar { width:80px; } .sidebar-header h3, .nav-item span { display:none; } .nav-item { justify-content:center; } .nav-item i { margin:0; } }
    </style>
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
            <div class="top-bar">
                <div class="page-title"><h1><i class="fas fa-chart-line"></i> Dashboard</h1><p>Selamat datang, <?= htmlspecialchars($_SESSION['kasir_nama']) ?></p></div>
                <div class="date-time"><i class="far fa-calendar-alt"></i><span id="currentDate"></span></div>
            </div>
            <div class="stats-grid">
                <div class="stat-card"><div class="stat-icon"><i class="fas fa-chart-simple"></i></div><div class="stat-info"><h3>TOTAL PENJUALAN (RP)</h3><p class="stat-value">Rp <?= number_format($stats['total'],0,',','.') ?></p></div></div>
                <div class="stat-card"><div class="stat-icon"><i class="fas fa-receipt"></i></div><div class="stat-info"><h3>TOTAL TRANSAKSI</h3><p class="stat-value"><?= $stats['count'] ?></p></div></div>
            </div>
            <div class="analisis-section">
                <h2><i class="fas fa-chart-line"></i> ANALISIS PENDAPATAN</h2>
                <div class="trend-list">
                    <div class="trend-item"><span class="trend-label">7 HARI:</span><span class="trend-value">Rp <?= number_format($trends['7 HARI']['total'],0,',','.') ?> (<?= $trends['7 HARI']['count'] ?> orang)</span></div>
                    <div class="trend-item"><span class="trend-label">3 HARI:</span><span class="trend-value">Rp <?= number_format($trends['3 HARI']['total'],0,',','.') ?> (<?= $trends['3 HARI']['count'] ?> orang)</span></div>
                    <div class="trend-item"><span class="trend-label">KEMARIN:</span><span class="trend-value">Rp <?= number_format($trends['KEMARIN']['total'],0,',','.') ?> (<?= $trends['KEMARIN']['count'] ?> orang)</span></div>
                    <div class="trend-item"><span class="trend-label">HARI INI:</span><span class="trend-value">Rp <?= number_format($trends['HARI INI']['total'],0,',','.') ?> (<?= $trends['HARI INI']['count'] ?> orang)</span></div>
                </div>
            </div>
            <div class="detail-transaksi-section">
                <h3><i class="fas fa-list-ul"></i> DETAIL TRANSAKSI HARI INI</h3>
                <div class="table-wrapper"><table class="transaksi-table"><thead><tr><th>Waktu</th><th>Kasir</th><th>Tipe</th><th>Subtotal</th><th>Pajak</th><th>Grand Total</th><th>Metode</th></tr></thead><tbody>
                <?php if(empty($transactions)): ?><tr class="empty-row"><td colspan="7">Belum ada transaksi hari ini</td></tr>
                <?php else: foreach($transactions as $t): ?>
                <tr>
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
    <script>document.getElementById('currentDate').innerText=new Date().toLocaleDateString('id-ID',{weekday:'long',year:'numeric',month:'numeric',day:'numeric'});</script>
</body>
</html>