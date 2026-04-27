<?php
require_once 'config/db.php';
if (!isset($_SESSION['kasir_id'])) { header('Location: index.php'); exit(); }

$kategori = $pdo->query("SELECT * FROM kategori ORDER BY id")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'add') {
        $stmt = $pdo->prepare("INSERT INTO menu (nama_barang, id_kategori, harga, stok) VALUES (?, ?, ?, ?)");
        $stmt->execute([$_POST['nama'], $_POST['kategori'], $_POST['harga'], $_POST['stok']]);
        header('Location: stok.php');
        exit();
    } elseif ($action === 'edit') {
        $stmt = $pdo->prepare("UPDATE menu SET nama_barang=?, id_kategori=?, harga=?, stok=? WHERE id=?");
        $stmt->execute([$_POST['nama'], $_POST['kategori'], $_POST['harga'], $_POST['stok'], $_POST['id']]);
        header('Location: stok.php');
        exit();
    } elseif ($action === 'delete') {
        $stmt = $pdo->prepare("DELETE FROM menu WHERE id=?");
        $stmt->execute([$_POST['id']]);
        header('Location: stok.php');
        exit();
    }
}

$menu = $pdo->query("SELECT m.*, k.nama_kategori FROM menu m JOIN kategori k ON m.id_kategori = k.id ORDER BY k.id, m.id")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resto Serba Serbi - Manajemen Stok</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #f5f0e8; }

        /* SIDEBAR */
        .app-container { display: flex; height: 100vh; overflow: hidden; }
        .sidebar { width: 280px; background: #2A4B2F; display: flex; flex-direction: column; }
        .sidebar-header { padding: 25px 20px; border-bottom: 1px solid rgba(255,255,255,0.1); display: flex; align-items: center; gap: 12px; }
        .sidebar-header i { font-size: 28px; color: #E8B84B; }
        .sidebar-header h3 { font-size: 18px; color: #E8B84B; }
        .sidebar-nav { flex: 1; padding: 20px 0; }
        .nav-item { display: flex; align-items: center; gap: 15px; padding: 14px 20px; color: rgba(255,255,255,0.8); text-decoration: none; margin: 5px 10px; border-radius: 12px; transition: all 0.3s; }
        .nav-item i { width: 24px; }
        .nav-item:hover { background: rgba(232,184,75,0.2); color: #E8B84B; }
        .nav-item.active { background: #E8B84B; color: #2A4B2F; }
        .sidebar-footer { padding: 20px; border-top: 1px solid rgba(255,255,255,0.1); }
        .btn-logout { width: 100%; padding: 12px; background: rgba(255,255,255,0.1); color: white; border: none; border-radius: 10px; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 10px; text-decoration: none; transition: all 0.3s; }
        .btn-logout:hover { background: #dc2626; }

        /* MAIN CONTENT */
        .main-content { flex: 1; overflow-y: auto; padding: 20px 30px; }
        .top-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; padding-bottom: 15px; border-bottom: 2px solid #e0d5c5; }
        .page-title h1 { font-size: 24px; color: #2A4B2F; }
        .page-title h1 i { color: #E8B84B; margin-right: 10px; }
        .date-time { background: white; padding: 10px 20px; border-radius: 10px; }

        /* ACTION BAR */
        .action-bar { margin-bottom: 25px; text-align: right; }
        .btn-add { background: #2A4B2F; color: white; padding: 12px 24px; border: none; border-radius: 10px; cursor: pointer; display: inline-flex; align-items: center; gap: 10px; font-weight: 600; transition: all 0.3s; }
        .btn-add:hover { background: #E8B84B; color: #2A4B2F; transform: translateY(-2px); }

        /* STOK TABLE */
        .stok-table-container { background: white; border-radius: 15px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .stok-table { width: 100%; border-collapse: collapse; }
        .stok-table th { background: #2A4B2F; color: white; padding: 15px; text-align: left; font-weight: 600; }
        .stok-table td { padding: 12px 15px; border-bottom: 1px solid #e0d5c5; }
        .stok-table tr:hover { background: #faf7f2; }

        .status-stok { padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; display: inline-block; }
        .status-stok.aman { background: #dcfce7; color: #166534; }
        .status-stok.menipis { background: #fef9c3; color: #854d0e; }
        .status-stok.habis { background: #fee2e2; color: #dc2626; }

        .btn-edit { background: #E8B84B; color: #2A4B2F; padding: 6px 12px; border: none; border-radius: 6px; cursor: pointer; margin-right: 5px; transition: all 0.3s; }
        .btn-edit:hover { background: #d4a42e; }
        .btn-delete-stok { background: #dc2626; color: white; padding: 6px 12px; border: none; border-radius: 6px; cursor: pointer; transition: all 0.3s; }
        .btn-delete-stok:hover { background: #b91c1c; }

        /* MODAL */
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); justify-content: center; align-items: center; z-index: 1000; }
        .modal-content { background: white; border-radius: 20px; width: 90%; max-width: 500px; overflow: hidden; }
        .modal-content.small { max-width: 400px; }
        .modal-header { padding: 20px; background: #2A4B2F; color: white; display: flex; justify-content: space-between; align-items: center; }
        .modal-header h3 { color: #E8B84B; margin: 0; }
        .modal-header .close { font-size: 28px; cursor: pointer; color: white; }
        .modal-body { padding: 25px; }
        .modal-footer { padding: 20px; border-top: 1px solid #e0d5c5; display: flex; gap: 10px; }

        .input-group { margin-bottom: 20px; }
        .input-group label { display: block; margin-bottom: 8px; font-weight: 600; color: #2A4B2F; }
        .input-group input, .input-group select { width: 100%; padding: 12px; border: 2px solid #e0d5c5; border-radius: 10px; font-size: 14px; transition: all 0.3s; }
        .input-group input:focus, .input-group select:focus { outline: none; border-color: #E8B84B; }

        .btn-confirm { background: #2A4B2F; color: white; padding: 12px; border: none; border-radius: 10px; font-weight: 600; cursor: pointer; flex: 1; transition: all 0.3s; }
        .btn-confirm:hover { background: #E8B84B; color: #2A4B2F; }
        .btn-cancel { background: #f5f0e8; color: #2A4B2F; padding: 12px; border: none; border-radius: 10px; font-weight: 600; cursor: pointer; flex: 1; transition: all 0.3s; }
        .btn-cancel:hover { background: #e0d5c5; }

        @media (max-width: 900px) { .sidebar { width: 80px; } .sidebar-header h3, .nav-item span { display: none; } .nav-item { justify-content: center; } .nav-item i { margin: 0; } }
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
                <div class="page-title">
                    <h1><i class="fas fa-boxes"></i> MANAJEMEN STOK</h1>
                </div>
                <div class="date-time">
                    <i class="far fa-calendar-alt"></i>
                    <span id="currentDate"></span>
                </div>
            </div>

            <div class="action-bar">
                <button id="tambahBtn" class="btn-add">
                    <i class="fas fa-plus"></i> TAMBAH MENU BARU
                </button>
            </div>

            <div class="stok-table-container">
                <table class="stok-table">
                    <thead>
                        <tr>
                            <th>NO</th>
                            <th>NAMA BARANG</th>
                            <th>KATEGORI</th>
                            <th>HARGA</th>
                            <th>STOK</th>
                            <th>STATUS</th>
                            <th>AKSI</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $no = 1; foreach ($menu as $item): 
                            $status = $item['stok'] <= 0 ? 'habis' : ($item['stok'] <= 10 ? 'menipis' : 'aman');
                            $statusText = $item['stok'] <= 0 ? 'Habis' : ($item['stok'] <= 10 ? 'Menipis' : 'Aman');
                        ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td><strong><?= htmlspecialchars($item['nama_barang']) ?></strong></td>
                            <td><?= htmlspecialchars($item['nama_kategori']) ?></td>
                            <td>Rp <?= number_format($item['harga'], 0, ',', '.') ?></td>
                            <td><?= $item['stok'] ?></td>
                            <td><span class="status-stok <?= $status ?>"><?= $statusText ?></span></td>
                            <td>
                                <button class="btn-edit" 
                                    data-id="<?= $item['id'] ?>" 
                                    data-name="<?= htmlspecialchars($item['nama_barang']) ?>" 
                                    data-cat="<?= $item['id_kategori'] ?>" 
                                    data-price="<?= $item['harga'] ?>" 
                                    data-stock="<?= $item['stok'] ?>">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                <button class="btn-delete-stok" 
                                    data-id="<?= $item['id'] ?>" 
                                    data-name="<?= htmlspecialchars($item['nama_barang']) ?>">
                                    <i class="fas fa-trash"></i> Hapus
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($menu)): ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 40px;">
                                <i class="fas fa-box-open" style="font-size: 40px; color: #ccc; margin-bottom: 10px; display: block;"></i>
                                Belum ada data menu. Silakan tambah menu baru.
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <!-- MODAL TAMBAH/EDIT MENU -->
    <div id="menuModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle">TAMBAH MENU</h3>
                <span class="close">&times;</span>
            </div>
            <form method="POST" id="menuForm">
                <input type="hidden" name="action" id="formAction" value="add">
                <input type="hidden" name="id" id="editId">
                <div class="modal-body">
                    <div class="input-group">
                        <label><i class="fas fa-tag"></i> Nama Barang</label>
                        <input type="text" name="nama" id="menuName" placeholder="Contoh: Nasi Goreng" required>
                    </div>
                    <div class="input-group">
                        <label><i class="fas fa-list"></i> Kategori</label>
                        <select name="kategori" id="menuCategory" required>
                            <?php foreach ($kategori as $k): ?>
                                <option value="<?= $k['id'] ?>"><?= htmlspecialchars($k['nama_kategori']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="input-group">
                        <label><i class="fas fa-money-bill-wave"></i> Harga (Rp)</label>
                        <input type="number" name="harga" id="menuPrice" placeholder="Contoh: 15000" required>
                    </div>
                    <div class="input-group">
                        <label><i class="fas fa-boxes"></i> Stok</label>
                        <input type="number" name="stok" id="menuStock" placeholder="Contoh: 50" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn-confirm"><i class="fas fa-save"></i> SIMPAN</button>
                    <button type="button" id="cancelMenuBtn" class="btn-cancel"><i class="fas fa-times"></i> BATAL</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL KONFIRMASI HAPUS -->
    <div id="deleteMenuModal" class="modal">
        <div class="modal-content small">
            <div class="modal-header">
                <h3><i class="fas fa-trash-alt"></i> Konfirmasi Hapus</h3>
            </div>
            <div class="modal-body">
                <p>Apakah Anda yakin ingin menghapus menu ini?</p>
                <p id="deleteMenuName" style="font-weight: bold; color: #E8B84B; text-align: center; margin-top: 15px;"></p>
            </div>
            <div class="modal-footer">
                <button id="confirmDeleteBtn" class="btn-confirm"><i class="fas fa-check"></i> YA, HAPUS</button>
                <button id="cancelDeleteBtn" class="btn-cancel"><i class="fas fa-times"></i> BATAL</button>
            </div>
        </div>
    </div>

    <script>
        // TAMBAH MENU
        document.getElementById('tambahBtn').onclick = function() {
            document.getElementById('modalTitle').innerHTML = '<i class="fas fa-plus"></i> TAMBAH MENU';
            document.getElementById('formAction').value = 'add';
            document.getElementById('editId').value = '';
            document.getElementById('menuName').value = '';
            document.getElementById('menuCategory').value = '1';
            document.getElementById('menuPrice').value = '';
            document.getElementById('menuStock').value = '';
            document.getElementById('menuModal').style.display = 'flex';
        };

        // EDIT MENU
        document.querySelectorAll('.btn-edit').forEach(function(btn) {
            btn.onclick = function() {
                document.getElementById('modalTitle').innerHTML = '<i class="fas fa-edit"></i> EDIT MENU';
                document.getElementById('formAction').value = 'edit';
                document.getElementById('editId').value = this.dataset.id;
                document.getElementById('menuName').value = this.dataset.name;
                document.getElementById('menuCategory').value = this.dataset.cat;
                document.getElementById('menuPrice').value = this.dataset.price;
                document.getElementById('menuStock').value = this.dataset.stock;
                document.getElementById('menuModal').style.display = 'flex';
            };
        });

        // HAPUS MENU
        let deleteId = null;
        document.querySelectorAll('.btn-delete-stok').forEach(function(btn) {
            btn.onclick = function() {
                deleteId = this.dataset.id;
                document.getElementById('deleteMenuName').innerHTML = this.dataset.name;
                document.getElementById('deleteMenuModal').style.display = 'flex';
            };
        });

        // Konfirmasi Hapus
        document.getElementById('confirmDeleteBtn').onclick = function() {
            if (deleteId) {
                let form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = '<input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="' + deleteId + '">';
                document.body.appendChild(form);
                form.submit();
            }
        };

        // TUTUP MODAL
        function closeModals() {
            document.getElementById('menuModal').style.display = 'none';
            document.getElementById('deleteMenuModal').style.display = 'none';
            deleteId = null;
        }

        document.querySelectorAll('.modal .close, #cancelMenuBtn, #cancelDeleteBtn').forEach(function(el) {
            el.onclick = closeModals;
        });

        // Tutup modal jika klik di luar
        window.onclick = function(event) {
            if (event.target === document.getElementById('menuModal')) closeModals();
            if (event.target === document.getElementById('deleteMenuModal')) closeModals();
        };

        // SET DATE
        function updateDateTime() {
            let options = { weekday: 'long', year: 'numeric', month: 'numeric', day: 'numeric' };
            document.getElementById('currentDate').innerText = new Date().toLocaleDateString('id-ID', options);
        }
        updateDateTime();
    </script>
</body>
</html>