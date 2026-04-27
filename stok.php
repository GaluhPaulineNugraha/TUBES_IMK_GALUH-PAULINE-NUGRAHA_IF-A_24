<?php
require_once 'config/db.php';
if (!isset($_SESSION['kasir_id'])) { 
    header('Location: index.php'); 
    exit(); 
}

// HANYA SELECT - TIDAK ADA PROSES POST UNTUK ADD/EDIT/DELETE
$menu = $pdo->query("SELECT m.*, k.nama_kategori FROM menu m JOIN kategori k ON m.id_kategori = k.id ORDER BY k.id, m.id")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resto Serba Serbi - Lihat Stok</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #f5f0e8; }

        .app-container { display: flex; height: 100vh; overflow: hidden; }
        .sidebar { width: 280px; background: #2A4B2F; display: flex; flex-direction: column; }
        .sidebar-header { padding: 25px 20px; border-bottom: 1px solid rgba(255,255,255,0.1); display: flex; align-items: center; gap: 12px; }
        .sidebar-header i { font-size: 28px; color: #E8B84B; }
        .sidebar-header h3 { font-size: 18px; color: #E8B84B; text-align: center; width: 100%; }
        .sidebar-nav { flex: 1; padding: 20px 0; }
        .nav-item { display: flex; align-items: center; gap: 15px; padding: 14px 20px; color: rgba(255,255,255,0.8); text-decoration: none; margin: 5px 10px; border-radius: 12px; transition: all 0.3s; }
        .nav-item i { width: 24px; }
        .nav-item:hover { background: rgba(232,184,75,0.2); color: #E8B84B; }
        .nav-item.active { background: #E8B84B; color: #2A4B2F; }
        .sidebar-footer { padding: 20px; border-top: 1px solid rgba(255,255,255,0.1); }
        .btn-logout { width: 100%; padding: 12px; background: rgba(255,255,255,0.1); color: white; border: none; border-radius: 10px; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 10px; text-decoration: none; transition: all 0.3s; }
        .btn-logout:hover { background: #dc2626; }

        .main-content { flex: 1; overflow-y: auto; padding: 20px 30px; }
        .top-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; padding-bottom: 15px; border-bottom: 2px solid #e0d5c5; }
        .page-title h1 { font-size: 24px; color: #2A4B2F; }
        .page-title h1 i { color: #E8B84B; margin-right: 10px; }
        .date-time { background: white; padding: 10px 20px; border-radius: 10px; }

        /* SEARCH BAR STYLES */
        .search-container {
            margin-bottom: 25px;
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
        }
        .search-box {
            flex: 1;
            position: relative;
            max-width: 400px;
        }
        .search-box i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
            font-size: 16px;
        }
        .search-box input {
            width: 100%;
            padding: 12px 15px 12px 45px;
            border: 2px solid #e0d5c5;
            border-radius: 12px;
            font-size: 14px;
            transition: all 0.3s;
            background: white;
        }
        .search-box input:focus {
            outline: none;
            border-color: #E8B84B;
            box-shadow: 0 0 0 3px rgba(232,184,75,0.2);
        }
        .search-box input::placeholder {
            color: #bbb;
        }
        .filter-category {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            align-items: center;
        }
        .btn-filter {
            padding: 8px 20px;
            border: 1px solid #E8B84B;
            background: white;
            border-radius: 20px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 500;
            transition: all 0.3s;
            color: #2A4B2F;
        }
        .btn-filter:hover {
            background: #E8B84B20;
        }
        .btn-filter.active {
            background: #E8B84B;
            color: #2A4B2F;
            border-color: #E8B84B;
        }
        .clear-search {
            background: none;
            border: none;
            color: #dc2626;
            cursor: pointer;
            font-size: 14px;
            padding: 8px 15px;
            border-radius: 20px;
            transition: all 0.3s;
        }
        .clear-search:hover {
            background: #fee2e2;
        }
        .result-count {
            background: #f5f0e8;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 13px;
            color: #666;
        }

        .stok-table-container { 
            background: white; 
            border-radius: 15px; 
            overflow: hidden; 
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            min-height: 400px;
        }
        .stok-table { 
            width: 100%; 
            border-collapse: collapse; 
        }
        .stok-table th { 
            background: #2A4B2F; 
            color: white; 
            padding: 15px; 
            text-align: left; 
            font-weight: 600; 
        }
        .stok-table td { 
            padding: 12px 15px; 
            border-bottom: 1px solid #e0d5c5; 
        }
        .stok-table tr:hover { 
            background: #faf7f2; 
        }

        .status-stok { 
            padding: 5px 12px; 
            border-radius: 20px; 
            font-size: 12px; 
            font-weight: 600; 
            display: inline-block; 
        }
        .status-stok.aman { background: #dcfce7; color: #166534; }
        .status-stok.habis { background: #fee2e2; color: #dc2626; }

        .no-result {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }
        .no-result i {
            font-size: 50px;
            margin-bottom: 15px;
            color: #ddd;
        }

        @media (max-width: 900px) { 
            .sidebar { width: 80px; } 
            .sidebar-header h3, .nav-item span { display: none; } 
            .nav-item { justify-content: center; } 
            .nav-item i { margin: 0; } 
            .search-container { flex-direction: column; align-items: stretch; }
            .search-box { max-width: 100%; }
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
                <a href="analisis.php" class="nav-item"><i class="fas fa-chart-bar"></i><span>Analisis Laporan</span></a>
                <a href="kasir.php" class="nav-item"><i class="fas fa-cash-register"></i><span>Kasir</span></a>
                <a href="stok.php" class="nav-item active"><i class="fas fa-boxes"></i><span>Lihat Stok</span></a>
                <a href="profil.php" class="nav-item"><i class="fas fa-user-circle"></i><span>Profil Kasir</span></a>
            </nav>
            <div class="sidebar-footer">
                <a href="logout.php" class="btn-logout"><i class="fas fa-sign-out-alt"></i><span>Keluar</span></a>
            </div>
        </aside>

        <main class="main-content">
            <div class="top-bar">
                <div class="page-title">
                    <h1><i class="fas fa-boxes"></i> LIHAT STOK MENU</h1>
                </div>
                <div class="date-time">
                    <i class="far fa-calendar-alt"></i>
                    <span id="currentDate"></span>
                </div>
            </div>

            <!-- SEARCH BAR SECTION -->
            <div class="search-container">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" id="searchInput" placeholder="Cari menu berdasarkan nama... (contoh: nasi, ayam, es)">
                </div>
                <div class="filter-category">
                    <button class="btn-filter active" data-filter="all">Semua Menu</button>
                    <button class="btn-filter" data-filter="habis">Menu Habis</button>
                </div>
                <button class="clear-search" id="clearSearchBtn">
                    <i class="fas fa-times-circle"></i> Reset
                </button>
                <div class="result-count" id="resultCount">
                    <i class="fas fa-utensils"></i> <span id="menuCount"><?= count($menu) ?></span> menu
                </div>
            </div>

            <div class="stok-table-container">
                <table class="stok-table" id="menuTable">
                    <thead>
                        <tr>
                            <th>NO</th>
                            <th>NAMA BARANG</th>
                            <th>KATEGORI</th>
                            <th>HARGA</th>
                            <th>STOK</th>
                            <th>STATUS</th>
                        </tr>
                    </thead>
                    <tbody id="tableBody">
                        <?php $no = 1; foreach ($menu as $item): 
                            $status = $item['stok'] <= 0 ? 'habis' : 'aman';
                            $statusText = $item['stok'] <= 0 ? 'Habis' : 'Tersedia';
                        ?>
                        <tr data-nama="<?= strtolower(htmlspecialchars($item['nama_barang'])) ?>" data-status="<?= $status ?>">
                            <td><?= $no++ ?></td>
                            <td><strong><?= htmlspecialchars($item['nama_barang']) ?></strong></td>
                            <td><?= htmlspecialchars($item['nama_kategori']) ?></td>
                            <td>Rp <?= number_format($item['harga'], 0, ',', '.') ?></td>
                            <td><?= $item['stok'] ?> <span style="font-size: 11px; color: #999;">porsi</span></td>
                            <td><span class="status-stok <?= $status ?>"><?= $statusText ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($menu)): ?>
                        <tr id="emptyRow">
                            <td colspan="6" style="text-align: center; padding: 40px;">
                                <i class="fas fa-box-open" style="font-size: 40px; color: #ccc; margin-bottom: 10px; display: block;"></i>
                                Belum ada data menu.
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                <div id="noResultRow" style="display: none;">
                    <div class="no-result">
                        <i class="fas fa-search"></i>
                        <h3>Menu tidak ditemukan</h3>
                        <p>Coba dengan kata kunci lain atau reset pencarian</p>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Ambil semua baris data (kecuali baris kosong)
        let allRows = Array.from(document.querySelectorAll('#tableBody tr')).filter(row => row.id !== 'emptyRow');
        let emptyRowExists = document.getElementById('emptyRow') !== null;
        let noResultDiv = document.getElementById('noResultRow');
        let tableBody = document.getElementById('tableBody');
        
        function filterTable() {
            let searchTerm = document.getElementById('searchInput').value.toLowerCase().trim();
            let selectedFilter = document.querySelector('.btn-filter.active').dataset.filter;
            
            let visibleCount = 0;
            
            allRows.forEach(row => {
                let nama = row.dataset.nama || '';
                let status = row.dataset.status || '';
                
                // Cek filter status (hanya 'all' atau 'habis')
                let matchStatus = (selectedFilter === 'all') || (status === 'habis');
                
                // Cek search term
                let matchSearch = searchTerm === '' || nama.includes(searchTerm);
                
                if (matchStatus && matchSearch) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });
            
            // Update jumlah menu yang terlihat
            document.getElementById('menuCount').innerText = visibleCount;
            
            // Tampilkan pesan "tidak ditemukan" jika perlu
            if (visibleCount === 0 && !emptyRowExists) {
                if (!noResultDiv || !noResultDiv.parentNode) {
                    // Cek apakah sudah ada
                    let existing = document.getElementById('noResultRow');
                    if (!existing) {
                        tableBody.insertAdjacentHTML('afterend', '<div id="noResultRow"><div class="no-result"><i class="fas fa-search"></i><h3>Menu tidak ditemukan</h3><p>Coba dengan kata kunci lain atau reset pencarian</p></div></div>');
                        noResultDiv = document.getElementById('noResultRow');
                    } else {
                        noResultDiv = existing;
                    }
                }
                if (noResultDiv) noResultDiv.style.display = 'block';
            } else {
                if (noResultDiv) noResultDiv.style.display = 'none';
            }
        }
        
        // Event listener untuk search input
        document.getElementById('searchInput').addEventListener('keyup', filterTable);
        document.getElementById('searchInput').addEventListener('input', filterTable);
        
        // Event listener untuk filter kategori stok
        document.querySelectorAll('.btn-filter').forEach(btn => {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.btn-filter').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                filterTable();
            });
        });
        
        // Reset button
        document.getElementById('clearSearchBtn').addEventListener('click', function() {
            document.getElementById('searchInput').value = '';
            document.querySelector('.btn-filter[data-filter="all"]').click();
            filterTable();
        });
        
        function updateDateTime() {
            let options = { weekday: 'long', year: 'numeric', month: 'numeric', day: 'numeric' };
            document.getElementById('currentDate').innerText = new Date().toLocaleDateString('id-ID', options);
        }
        updateDateTime();
    </script>
</body>
</html>