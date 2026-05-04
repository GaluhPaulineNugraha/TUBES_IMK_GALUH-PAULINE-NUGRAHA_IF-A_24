<?php
require_once 'config/db.php';
if (!isset($_SESSION['kasir_id'])) { header('Location: index.php'); exit(); }

$stmt = $pdo->prepare("SELECT * FROM kasir WHERE id=?");
$stmt->execute([$_SESSION['kasir_id']]);
$kasir = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resto Serba Serbi - Profil Kasir</title>
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
                <a href="analisis.php" class="nav-item"><i class="fas fa-chart-bar"></i><span>Analisis Laporan</span></a>
                <a href="kasir.php" class="nav-item"><i class="fas fa-cash-register"></i><span>Kasir</span></a>
                <a href="stok.php" class="nav-item"><i class="fas fa-boxes"></i><span>Manajemen Stok</span></a>
                <a href="profil.php" class="nav-item active"><i class="fas fa-user-circle"></i><span>Profil Kasir</span></a>

    </nav>
    <div class="sidebar-footer">
        <a href="logout.php" class="btn-logout"><i class="fas fa-sign-out-alt"></i><span>Keluar</span></a>
    </div>
</aside>
        <main class="main-content">
            <div class="top-bar"><div class="page-title"><h1><i class="fas fa-user-circle"></i> PROFIL KASIR</h1></div><div class="date-time"><i class="far fa-calendar-alt"></i><span id="currentDate"></span></div></div>
            <div class="profile-card">
                <div class="profile-avatar"><i class="fas fa-user-circle"></i></div>
                <div class="profile-info">
                    <div class="info-row"><label>NAMA</label><p><?= htmlspecialchars($kasir['nama']) ?></p></div>
                    <div class="info-row"><label>USERNAME</label><p><?= htmlspecialchars($kasir['username']) ?></p></div>
                    <div class="info-row"><label>NO TELEPHONE</label><p><?= htmlspecialchars($kasir['no_telephone']) ?></p></div>
                    <div class="info-row"><label>JADWAL</label><p><?= htmlspecialchars($kasir['jadwal']) ?></p></div>
                </div>
                <button onclick="history.back()" class="btn-back"><i class="fas fa-arrow-left"></i> KEMBALI</button>
            </div>
        </main>
    </div>
    <script src="js/main.js"></script>
</body>
</html>