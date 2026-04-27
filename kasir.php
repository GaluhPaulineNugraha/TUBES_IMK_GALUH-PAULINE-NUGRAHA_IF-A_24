<?php
require_once 'config/db.php';

if (!isset($_SESSION['kasir_id'])) {
    header('Location: index.php');
    exit();
}

$stmt = $pdo->query("SELECT m.*, k.nama_kategori FROM menu m JOIN kategori k ON m.id_kategori = k.id ORDER BY k.id, m.id");
$menu = $stmt->fetchAll();

// Generate kode transaksi UNIK
$kode_transaksi = 'TRX' . date('Ymd') . substr(time(), -6) . rand(10, 99);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resto Serba Serbi - Kasir</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #f5f0e8; }

        .app-container { display: flex; height: 100vh; overflow: hidden; }
        .sidebar { width: 280px; background: #2A4B2F; display: flex; flex-direction: column; }
        .sidebar-header { padding: 25px 20px; border-bottom: 1px solid rgba(255,255,255,0.1); display: flex; align-items: center; gap: 12px; }
        .sidebar-header i { font-size: 28px; color: #E8B84B; }
        .sidebar-header h3 { font-size: 18px; color: #E8B84B; }
        .sidebar-nav { flex: 1; padding: 20px 0; }
        .nav-item { display: flex; align-items: center; gap: 15px; padding: 14px 20px; color: rgba(255,255,255,0.8); text-decoration: none; margin: 5px 10px; border-radius: 12px; }
        .nav-item i { width: 24px; }
        .nav-item:hover { background: rgba(232,184,75,0.2); color: #E8B84B; }
        .nav-item.active { background: #E8B84B; color: #2A4B2F; }
        .sidebar-footer { padding: 20px; border-top: 1px solid rgba(255,255,255,0.1); }
        .btn-logout { width: 100%; padding: 12px; background: rgba(255,255,255,0.1); color: white; border: none; border-radius: 10px; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 10px; text-decoration: none; }
        .btn-logout:hover { background: #dc2626; }

        .main-content { flex: 1; overflow-y: auto; padding: 20px 30px; }
        .top-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; padding-bottom: 15px; border-bottom: 2px solid #e0d5c5; }
        .page-title h1 { font-size: 24px; color: #2A4B2F; }
        .page-title h1 i { color: #E8B84B; margin-right: 10px; }
        .date-time { background: white; padding: 10px 20px; border-radius: 10px; }

        .kasir-layout { display: grid; grid-template-columns: 1fr 1fr; gap: 25px; }
        .struk-panel, .menu-panel { background: white; border-radius: 15px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .panel-header { padding: 15px 20px; background: #f5f0e8; border-bottom: 1px solid #e0d5c5; }
        .panel-header h3 { margin-bottom: 12px; color: #2A4B2F; }
        .order-type { display: flex; gap: 10px; }
        .btn-type { padding: 6px 16px; border: 1px solid #E8B84B; background: white; border-radius: 6px; cursor: pointer; }
        .btn-type.active { background: #E8B84B; color: #2A4B2F; }
        .table-wrapper { overflow-x: auto; }
        .cart-table { width: 100%; border-collapse: collapse; }
        .cart-table th, .cart-table td { padding: 12px 8px; text-align: left; border-bottom: 1px solid #e0d5c5; }
        .cart-table th { background: #faf7f2; }
        .cart-summary { padding: 15px 20px; background: #faf7f2; border-top: 1px solid #e0d5c5; }
        .summary-row { display: flex; justify-content: space-between; margin-bottom: 10px; }
        .summary-row.total { font-size: 18px; font-weight: 700; margin-top: 10px; padding-top: 10px; border-top: 2px solid #e0d5c5; }
        .cart-actions { padding: 15px 20px; display: flex; gap: 15px; }
        .btn-payment, .btn-cancel-order { flex: 1; padding: 12px; border: none; border-radius: 10px; font-weight: 600; cursor: pointer; }
        .btn-payment { background: #2A4B2F; color: white; }
        .btn-payment:disabled { background: #ccc; cursor: not-allowed; }
        .btn-cancel-order { background: #dc2626; color: white; }

        .menu-search { display: flex; gap: 10px; flex-wrap: wrap; align-items: center; margin-bottom: 15px; }
        .btn-category { padding: 6px 14px; border: 1px solid #E8B84B; background: white; border-radius: 6px; cursor: pointer; }
        .btn-category.active { background: #E8B84B; color: #2A4B2F; }
        .search-box { flex: 1; position: relative; }
        .search-box i { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #999; }
        .search-box input { width: 100%; padding: 8px 12px 8px 35px; border: 1px solid #e0d5c5; border-radius: 8px; }
        .menu-list { padding: 15px; max-height: 550px; overflow-y: auto; }
        .menu-item { display: flex; justify-content: space-between; align-items: center; padding: 12px 15px; background: #faf7f2; border-radius: 10px; margin-bottom: 8px; }
        .menu-item:hover { background: #f5f0e8; }
        .menu-info h4 { margin-bottom: 4px; }
        .menu-info p { color: #E8B84B; font-weight: 600; }
        .menu-info small { color: #999; font-size: 11px; }
        .btn-select { padding: 8px 20px; background: #2A4B2F; color: white; border: none; border-radius: 8px; cursor: pointer; }
        .btn-select:hover { background: #E8B84B; }
        .btn-select:disabled { background: #ccc; cursor: not-allowed; }
        .menu-kategori { font-size: 16px; font-weight: 700; color: #2A4B2F; background: #f5f0e8; padding: 10px 15px; margin: 15px 0 8px 0; border-radius: 8px; border-left: 4px solid #E8B84B; }
        .empty-row td { text-align: center; color: #999; padding: 40px; }

        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); justify-content: center; align-items: center; z-index: 1000; }
        .payment-modal { background: white; border-radius: 24px; width: 90%; max-width: 420px; overflow: hidden; }
        .payment-header { background: #2A4B2F; padding: 20px; text-align: center; }
        .payment-header h3 { color: #E8B84B; margin: 0; }
        .payment-body { padding: 25px; }
        .payment-total { background: #f5f0e8; border-radius: 16px; padding: 20px; text-align: center; margin-bottom: 20px; }
        .payment-total .label { font-size: 14px; color: #666; }
        .payment-total .amount { font-size: 28px; font-weight: 800; color: #2A4B2F; }
        .payment-methods { display: flex; gap: 15px; margin-bottom: 25px; }
        .pay-method { flex: 1; padding: 12px; border: 2px solid #e0d5c5; background: white; border-radius: 12px; cursor: pointer; text-align: center; font-weight: 600; display: flex; align-items: center; justify-content: center; gap: 8px; }
        .pay-method.active { background: #2A4B2F; border-color: #2A4B2F; color: white; }
        .pay-method.active i { color: #E8B84B; }
        .cash-input { margin-bottom: 20px; }
        .cash-input label { display: block; margin-bottom: 8px; font-weight: 600; color: #2A4B2F; }
        .cash-input input { width: 100%; padding: 14px; border: 2px solid #e0d5c5; border-radius: 12px; font-size: 16px; }
        .kembalian-box { background: #f0fdf4; border-radius: 12px; padding: 15px; text-align: center; margin-bottom: 20px; }
        .kembalian-box .label { font-size: 14px; color: #166534; }
        .kembalian-box .amount { font-size: 22px; font-weight: 700; color: #166534; }
        .kembalian-box.error { background: #fef2f2; }
        .kembalian-box.error .label { color: #991b1b; }
        .kembalian-box.error .amount { color: #dc2626; }
        .qris-box { text-align: center; padding: 20px; }
        .qris-box i { font-size: 80px; color: #2A4B2F; }
        .payment-footer { padding: 20px; border-top: 1px solid #e0d5c5; display: flex; gap: 12px; }
        .btn-pay { flex: 1; padding: 14px; background: #2A4B2F; color: white; border: none; border-radius: 12px; font-weight: 600; cursor: pointer; }
        .btn-pay:hover { background: #E8B84B; color: #2A4B2F; }
        .btn-cancel-pay { flex: 1; padding: 14px; background: #f5f0e8; color: #2A4B2F; border: none; border-radius: 12px; font-weight: 600; cursor: pointer; }

        .confirm-modal { background: white; border-radius: 20px; width: 90%; max-width: 360px; overflow: hidden; }
        .confirm-header { background: #2A4B2F; padding: 20px; text-align: center; }
        .confirm-header h3 { color: #E8B84B; }
        .confirm-body { padding: 30px; text-align: center; }
        .confirm-footer { padding: 20px; border-top: 1px solid #e0d5c5; display: flex; gap: 12px; }
        .btn-confirm-yes { flex: 1; padding: 12px; background: #2A4B2F; color: white; border: none; border-radius: 10px; cursor: pointer; }
        .btn-confirm-no { flex: 1; padding: 12px; background: #f5f0e8; color: #2A4B2F; border: none; border-radius: 10px; cursor: pointer; }

        @media (max-width: 900px) { .kasir-layout { grid-template-columns: 1fr; } .sidebar { width: 80px; } .sidebar-header h3, .nav-item span { display: none; } .nav-item { justify-content: center; } }
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
                <a href="kasir.php" class="nav-item active"><i class="fas fa-cash-register"></i><span>Kasir</span></a>
                <a href="stok.php" class="nav-item"><i class="fas fa-boxes"></i><span>Lihat Stok</span></a>
                <a href="profil.php" class="nav-item"><i class="fas fa-user-circle"></i><span>Profil Kasir</span></a>
            </nav>
            <div class="sidebar-footer">
                <a href="logout.php" class="btn-logout"><i class="fas fa-sign-out-alt"></i><span>Keluar</span></a>
            </div>
        </aside>

        <main class="main-content">
            <div class="top-bar">
                <div class="page-title"><h1><i class="fas fa-cash-register"></i> SISTEM KASIR</h1></div>
                <div class="date-time"><i class="far fa-calendar-alt"></i><span id="currentDate"></span></div>
            </div>

            <div class="kasir-layout">
                <div class="struk-panel">
                    <div class="panel-header">
                        <h3>STRUK PELANGGAN</h3>
                        <div class="order-type">
                            <button class="btn-type active" data-type="dinein">DINE IN</button>
                            <button class="btn-type" data-type="dineout">DINE OUT</button>
                        </div>
                    </div>
                    <div class="table-wrapper">
                        <table class="cart-table">
                            <thead><tr><th>NAMA BARANG</th><th>QTY</th><th>HARGA</th><th>SUBTOTAL</th><th>OPSI</th></tr></thead>
                            <tbody id="cartItems"><tr class="empty-row"><td colspan="5">Belum ada pesanan</td></tr></tbody>
                        </table>
                    </div>
                    <div class="cart-summary">
                        <div class="summary-row"><span>SUB TOTAL</span><span id="subtotal">Rp 0</span></div>
                        <div class="summary-row" id="taxRow"><span>PAJAK (5%)</span><span id="taxAmount">Rp 0</span></div>
                        <div class="summary-row total"><span>TOTAL</span><span id="cartTotal">Rp 0</span></div>
                    </div>
                    <div class="cart-actions">
                        <button id="bayarBtn" class="btn-payment" disabled>PEMBAYARAN</button>
                        <button id="batalkanPesananBtn" class="btn-cancel-order">BATALKAN PESANAN</button>
                    </div>
                </div>

                <div class="menu-panel">
                    <div class="panel-header">
                        <h3>LIST MENU</h3>
                        <div class="menu-search">
                            <button class="btn-category active" data-cat="all">SEMUA</button>
                            <button class="btn-category" data-cat="nasi">ANEKA NASI</button>
                            <button class="btn-category" data-cat="lauk">ANEKA LAUK PAUK</button>
                            <button class="btn-category" data-cat="pepes">ANEKA PEPES</button>
                            <button class="btn-category" data-cat="minuman">ANEKA MINUMAN</button>
                            <button class="btn-category" data-cat="sayuran">ANEKA SAYURAN</button>
                            <button class="btn-category" data-cat="camilan">ANEKA CAMILAN</button>
                            <div class="search-box"><i class="fas fa-search"></i><input type="text" id="searchMenu" placeholder="Cari Disini"></div>
                        </div>
                    </div>
                    <div class="menu-list" id="menuList">
                        <?php 
                        $current = '';
                        foreach ($menu as $item): 
                            if ($current != $item['nama_kategori']):
                                $current = $item['nama_kategori'];
                                echo '<div class="menu-kategori">' . htmlspecialchars($current) . '</div>';
                            endif;
                        ?>
                        <div class="menu-item" data-id="<?= $item['id'] ?>" data-name="<?= htmlspecialchars($item['nama_barang']) ?>" data-price="<?= $item['harga'] ?>" data-stock="<?= $item['stok'] ?>">
                            <div class="menu-info">
                                <h4><?= htmlspecialchars($item['nama_barang']) ?></h4>
                                <p>Rp <?= number_format($item['harga'], 0, ',', '.') ?>,00</p>
                                <small>Stok: <?= $item['stok'] ?></small>
                            </div>
                            <button class="btn-select" onclick="tambahKeKeranjang(this)" <?= $item['stok'] <= 0 ? 'disabled' : '' ?>>
                                <?= $item['stok'] <= 0 ? 'HABIS' : 'PILIH' ?>
                            </button>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <div id="paymentModal" class="modal">
        <div class="payment-modal">
            <div class="payment-header"><h3><i class="fas fa-credit-card"></i> PEMBAYARAN</h3></div>
            <div class="payment-body">
                <div class="payment-total">
                    <div class="label">Total Pembayaran</div>
                    <div class="amount" id="modalTotal">Rp 0</div>
                </div>
                <div class="payment-methods">
                    <button class="pay-method active" data-method="cash" id="cashMethod"><i class="fas fa-money-bill-wave"></i> CASH</button>
                    <button class="pay-method" data-method="qris" id="qrisMethod"><i class="fas fa-qrcode"></i> QRIS</button>
                </div>
                <div id="cashSection">
                    <div class="cash-input"><label><i class="fas fa-coins"></i> Jumlah Uang</label><input type="number" id="jumlahUang" placeholder="Masukkan nominal uang"></div>
                    <div id="kembalianBox" class="kembalian-box"><div class="label">Kembalian</div><div class="amount" id="kembalian">Rp 0</div></div>
                </div>
                <div id="qrisSection" style="display:none;">
                    <div class="qris-box"><i class="fas fa-qrcode"></i><p>Scan QR Code untuk pembayaran</p><img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=RestoSerbaSerbi" alt="QR Code" style="width:150px; margin:10px auto;"></div>
                </div>
            </div>
            <div class="payment-footer">
                <button id="confirmPayment" class="btn-pay"><i class="fas fa-check-circle"></i> BAYAR</button>
                <button id="cancelPayment" class="btn-cancel-pay"><i class="fas fa-times-circle"></i> BATAL</button>
            </div>
        </div>
    </div>

    <input type="hidden" id="kodeTransaksi" value="<?= $kode_transaksi ?>">
    <input type="hidden" id="kasirId" value="<?= $_SESSION['kasir_id'] ?>">

    <script>
        let keranjang = [];
        let tipeOrder = 'dinein';
        let kategoriAktif = 'all';
        let kataCari = '';
        let isProcessing = false;

        const mapKategori = {
            'Aneka Nasi': 'nasi',
            'Aneka Lauk Pauk': 'lauk',
            'Aneka Pepes': 'pepes',
            'Aneka Minuman': 'minuman',
            'Aneka Sayuran': 'sayuran',
            'Aneka Camilan': 'camilan'
        };

        function tambahKeKeranjang(btn) {
            if (btn.disabled) {
                alert('Menu ini sedang habis!');
                return;
            }
            
            let item = btn.closest('.menu-item');
            let id = parseInt(item.dataset.id);
            let nama = item.dataset.name;
            let harga = parseInt(item.dataset.price);
            let stok = parseInt(item.dataset.stock);
            
            // CEK STOK SAAT INI
            if (stok <= 0) {
                alert(`Maaf, ${nama} sedang HABIS!`);
                btn.disabled = true;
                btn.innerHTML = 'HABIS';
                return;
            }
            
            let existing = keranjang.find(i => i.id === id);
            if (existing) {
                if (existing.qty + 1 > stok) { 
                    alert(`Stok ${nama} tidak mencukupi! Tersisa ${stok}`);
                    return; 
                }
                existing.qty++;
            } else {
                keranjang.push({ id, nama, harga, qty: 1 });
            }
            renderCart();
        }

        function hitungSubtotal() { return keranjang.reduce((s, i) => s + (i.harga * i.qty), 0); }
        function hitungPajak() { return tipeOrder === 'dinein' ? Math.round(hitungSubtotal() * 0.05) : 0; }
        function hitungTotal() { return hitungSubtotal() + hitungPajak(); }

        function renderCart() {
            let tbody = document.getElementById('cartItems');
            if (keranjang.length === 0) {
                tbody.innerHTML = '<tr class="empty-row"><td colspan="5">Belum ada pesanan</td></tr>';
                document.getElementById('subtotal').innerHTML = 'Rp 0';
                document.getElementById('taxAmount').innerHTML = 'Rp 0';
                document.getElementById('cartTotal').innerHTML = 'Rp 0';
                document.getElementById('bayarBtn').disabled = true;
                document.getElementById('taxRow').style.display = tipeOrder === 'dinein' ? 'flex' : 'none';
                return;
            }
            
            let html = '';
            for (let i = 0; i < keranjang.length; i++) {
                let item = keranjang[i];
                html += `<tr>
                     <td>${item.nama}</td>
                    <td style="text-align: center;">
                        <div style="display: flex; align-items: center; justify-content: center; gap: 8px;">
                            <button onclick="ubahQty(${item.id}, -1)" style="width: 30px; height: 30px; background: #dc2626; color: white; border: none; border-radius: 6px; cursor: pointer;">-</button>
                            <span style="min-width: 30px;">${item.qty}</span>
                            <button onclick="ubahQty(${item.id}, 1)" style="width: 30px; height: 30px; background: #2A4B2F; color: white; border: none; border-radius: 6px; cursor: pointer;">+</button>
                        </div>
                    </td>
                    <td>Rp ${item.harga.toLocaleString('id-ID')},00</td>
                    <td>Rp ${(item.harga * item.qty).toLocaleString('id-ID')},00</td>
                    <td><button onclick="hapusItem(${item.id})" style="background: none; border: none; color: #dc2626; cursor: pointer; font-size: 16px;"><i class="fas fa-trash"></i></button></td>
                </tr>`;
            }
            tbody.innerHTML = html;
            
            let subtotal = hitungSubtotal();
            let pajak = hitungPajak();
            let total = hitungTotal();
            document.getElementById('subtotal').innerHTML = `Rp ${subtotal.toLocaleString('id-ID')},00`;
            document.getElementById('taxAmount').innerHTML = `Rp ${pajak.toLocaleString('id-ID')},00`;
            document.getElementById('cartTotal').innerHTML = `Rp ${total.toLocaleString('id-ID')},00`;
            document.getElementById('bayarBtn').disabled = false;
            document.getElementById('taxRow').style.display = tipeOrder === 'dinein' ? 'flex' : 'none';
        }

        function ubahQty(id, change) {
            let index = keranjang.findIndex(i => i.id === id);
            if (index === -1) return;
            let item = keranjang[index];
            let menuItem = document.querySelector(`.menu-item[data-id="${id}"]`);
            let maxStock = menuItem ? parseInt(menuItem.dataset.stock) : 999;
            
            let newQty = item.qty + change;
            if (newQty <= 0) {
                keranjang.splice(index, 1);
            } else if (newQty > maxStock) {
                alert(`Stok tidak cukup! Tersisa ${maxStock}`);
                return;
            } else {
                item.qty = newQty;
            }
            renderCart();
        }

        function hapusItem(id) {
            if (confirm('Hapus menu ini?')) {
                keranjang = keranjang.filter(i => i.id !== id);
                renderCart();
            }
        }

        function batalkanSemua() {
            if (keranjang.length === 0) return;
            if (confirm('Batalkan semua pesanan?')) {
                keranjang = [];
                renderCart();
            }
        }

        function filterMenu() {
            let items = document.querySelectorAll('.menu-item');
            items.forEach(item => {
                let nama = item.dataset.name.toLowerCase();
                let kategoriHeader = item.previousElementSibling;
                while (kategoriHeader && !kategoriHeader.classList.contains('menu-kategori')) {
                    kategoriHeader = kategoriHeader.previousElementSibling;
                }
                let kategoriNama = kategoriHeader ? kategoriHeader.innerText : '';
                let kategoriValue = mapKategori[kategoriNama] || 'all';
                let matchKategori = kategoriAktif === 'all' || kategoriValue === kategoriAktif;
                let matchCari = nama.includes(kataCari);
                item.style.display = matchKategori && matchCari ? 'flex' : 'none';
            });
            document.querySelectorAll('.menu-kategori').forEach(div => {
                let next = div.nextElementSibling;
                let ada = false;
                while (next && next.classList.contains('menu-item')) {
                    if (next.style.display !== 'none') { ada = true; break; }
                    next = next.nextElementSibling;
                }
                div.style.display = ada ? 'block' : 'none';
            });
        }

        function showPaymentModal() {
            if (keranjang.length === 0) { alert('Belum ada pesanan!'); return; }
            document.getElementById('paymentModal').style.display = 'flex';
            document.getElementById('modalTotal').innerHTML = `Rp ${hitungTotal().toLocaleString('id-ID')},00`;
            document.getElementById('jumlahUang').value = '';
            document.getElementById('kembalian').innerHTML = 'Rp 0';
            document.getElementById('kembalianBox').classList.remove('error');
        }

        // ✅ FUNGSI CEK STOK SEBELUM BAYAR (FIX BUG)
        async function prosesPembayaran(metode, uang = null) {
            if (isProcessing) { alert('Proses sedang berjalan...'); return false; }
            
            // 🔴 CEK STOK ULANG SEBELUM PROSES PEMBAYARAN
            let cekStokGagal = [];
            for (let item of keranjang) {
                let menuItem = document.querySelector(`.menu-item[data-id="${item.id}"]`);
                let stokTersedia = menuItem ? parseInt(menuItem.dataset.stock) : 0;
                
                if (item.qty > stokTersedia) {
                    cekStokGagal.push({
                        nama: item.nama,
                        qtyDiminta: item.qty,
                        stokTersedia: stokTersedia
                    });
                }
            }
            
            if (cekStokGagal.length > 0) {
                let pesanError = "❌ Stok tidak mencukupi untuk item berikut:\n";
                for (let item of cekStokGagal) {
                    pesanError += `\n• ${item.nama}: butuh ${item.qty}, tersisa ${item.stokTersedia}`;
                }
                pesanError += "\n\nSilakan update keranjang Anda.";
                alert(pesanError);
                
                for (let item of cekStokGagal) {
                    keranjang = keranjang.filter(i => i.id !== item.id);
                }
                renderCart();
                return false;
            }
            
            isProcessing = true;
            
            let total = hitungTotal();
            if (metode === 'cash' && (!uang || uang < total)) {
                alert('Uang tidak cukup!');
                isProcessing = false;
                return false;
            }
            
            let data = {
                kode_transaksi: document.getElementById('kodeTransaksi').value,
                id_kasir: parseInt(document.getElementById('kasirId').value),
                tipe_order: tipeOrder,
                subtotal: hitungSubtotal(),
                pajak: hitungPajak(),
                total: total,
                metode: metode,
                items: keranjang.map(i => ({ id: i.id, qty: i.qty, price: i.harga, name: i.nama }))
            };
            
            try {
                let res = await fetch('api/proses_transaksi.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                let result = await res.json();
                if (result.success) {
                    for (let item of keranjang) {
                        let menuItem = document.querySelector(`.menu-item[data-id="${item.id}"]`);
                        if (menuItem) {
                            let stokLama = parseInt(menuItem.dataset.stock);
                            let stokBaru = stokLama - item.qty;
                            menuItem.dataset.stock = stokBaru;
                            let smallTag = menuItem.querySelector('small');
                            if (smallTag) smallTag.innerHTML = `Stok: ${stokBaru}`;
                            
                            let btnSelect = menuItem.querySelector('.btn-select');
                            if (stokBaru <= 0) {
                                btnSelect.disabled = true;
                                btnSelect.innerHTML = 'HABIS';
                            }
                        }
                    }
                    
                    alert(metode === 'cash' ? `✅ Pembayaran berhasil! Kembalian: Rp ${(uang - total).toLocaleString('id-ID')}` : '✅ Pembayaran QRIS berhasil!');
                    showStruk(result.data, metode, uang);
                    keranjang = [];
                    renderCart();
                    document.getElementById('paymentModal').style.display = 'none';
                    setTimeout(() => location.reload(), 1500);
                } else {
                    alert('❌ Error: ' + result.message);
                }
            } catch(e) {
                alert('❌ Error: ' + e.message);
            }
            isProcessing = false;
        }

        function showStruk(data, metode, uang = null) {
            let itemsHtml = '';
            for (let i = 0; i < data.items.length; i++) {
                let item = data.items[i];
                itemsHtml += `<tr><td style="padding:5px;">${item.name}</td><td style="padding:5px;text-align:center;">${item.qty}</td><td style="padding:5px;text-align:right;">Rp ${item.price.toLocaleString('id-ID')}</td><td style="padding:5px;text-align:right;">Rp ${(item.price * item.qty).toLocaleString('id-ID')}</td></tr>`;
            }
            let kembalian = uang ? uang - data.total : null;
            let html = `
                <div id="strukPrint" style="position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:10000; display:flex; align-items:center; justify-content:center;">
                    <div style="background:white; width:380px; border-radius:10px; padding:20px; font-family:monospace;">
                        <div style="text-align:center;">
                            <h3 style="color:#2A4B2F;">Resto Serba Serbi</h3>
                            <p>Jl. Hos Cokroaminoto Gg Nangka, Cianjur</p>
                            <hr>
                            <p>${data.tanggal}</p>
                            <p>Kasir: <?= $_SESSION['kasir_nama'] ?></p>
                            <p>Metode: ${metode.toUpperCase()}</p>
                            <p>Kode: ${data.kode_transaksi}</p>
                            <hr>
                        </div>
                        <table style="width:100%;">
                            <thead><tr><th>Nama</th><th>Qty</th><th>Harga</th><th>Subtotal</th></tr></thead>
                            <tbody>${itemsHtml}</tbody>
                            <tfoot>
                                <tr><td colspan="3"><strong>Subtotal:</strong></td><td>Rp ${data.subtotal.toLocaleString('id-ID')}</td></tr>
                                <tr><td colspan="3"><strong>Pajak 5%:</strong></td><td>Rp ${data.pajak.toLocaleString('id-ID')}</td></tr>
                                <tr><td colspan="3"><strong>TOTAL:</strong></td><td><strong>Rp ${data.total.toLocaleString('id-ID')}</strong></td></tr>
                                ${kembalian !== null ? `<tr><td colspan="3">Tunai:</td><td>Rp ${uang.toLocaleString('id-ID')}</td></tr>
                                <tr><td colspan="3">Kembalian:</td><td>Rp ${kembalian.toLocaleString('id-ID')}</td></tr>` : ''}
                            </tfoot>
                        </table>
                        <hr>
                        <div style="text-align:center;">
                            <p>Terima Kasih</p>
                            <p>Silahkan Datang Kembali</p>
                            <button onclick="window.print()" style="margin:10px; padding:8px 20px; background:#2A4B2F; color:white; border:none; border-radius:5px; cursor:pointer;">CETAK STRUK</button>
                            <button onclick="this.closest('#strukPrint').remove()" style="margin:10px; padding:8px 20px; background:#E8B84B; color:#2A4B2F; border:none; border-radius:5px; cursor:pointer;">TUTUP</button>
                        </div>
                    </div>
                </div>`;
            document.body.insertAdjacentHTML('beforeend', html);
        }

        document.querySelectorAll('.btn-type').forEach(btn => {
            btn.onclick = function() {
                document.querySelectorAll('.btn-type').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                tipeOrder = this.dataset.type;
                renderCart();
            };
        });

        document.querySelectorAll('.btn-category').forEach(btn => {
            btn.onclick = function() {
                document.querySelectorAll('.btn-category').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                kategoriAktif = this.dataset.cat;
                filterMenu();
            };
        });

        document.getElementById('searchMenu').oninput = function(e) { kataCari = e.target.value.toLowerCase(); filterMenu(); };
        document.getElementById('bayarBtn').onclick = showPaymentModal;
        document.getElementById('batalkanPesananBtn').onclick = batalkanSemua;

        let paymentModal = document.getElementById('paymentModal');
        document.getElementById('cancelPayment').onclick = () => paymentModal.style.display = 'none';
        window.onclick = function(event) { if (event.target === paymentModal) paymentModal.style.display = 'none'; };

        document.getElementById('cashMethod').onclick = function() {
            this.classList.add('active');
            document.getElementById('qrisMethod').classList.remove('active');
            document.getElementById('cashSection').style.display = 'block';
            document.getElementById('qrisSection').style.display = 'none';
        };
        document.getElementById('qrisMethod').onclick = function() {
            this.classList.add('active');
            document.getElementById('cashMethod').classList.remove('active');
            document.getElementById('cashSection').style.display = 'none';
            document.getElementById('qrisSection').style.display = 'block';
        };

        document.getElementById('jumlahUang').oninput = function() {
            let total = hitungTotal();
            let uang = parseInt(this.value) || 0;
            let kembalian = uang - total;
            let span = document.getElementById('kembalian');
            let box = document.getElementById('kembalianBox');
            if (kembalian < 0) {
                span.innerHTML = 'Uang Tidak Cukup';
                box.classList.add('error');
            } else {
                span.innerHTML = `Rp ${kembalian.toLocaleString('id-ID')},00`;
                box.classList.remove('error');
            }
        };

        document.getElementById('confirmPayment').onclick = function() {
            let activeMethod = document.querySelector('.pay-method.active').dataset.method;
            if (activeMethod === 'cash') {
                let total = hitungTotal();
                let uang = parseInt(document.getElementById('jumlahUang').value) || 0;
                if (uang >= total) prosesPembayaran('cash', uang);
                else alert('Uang tidak cukup!');
            } else {
                prosesPembayaran('qris');
            }
        };

        filterMenu();
        document.getElementById('currentDate').innerText = new Date().toLocaleDateString('id-ID', { weekday: 'long', year: 'numeric', month: 'numeric', day: 'numeric' });
    </script>
</body>
</html>