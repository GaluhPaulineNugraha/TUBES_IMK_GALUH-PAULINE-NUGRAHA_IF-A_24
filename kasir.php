<?php
date_default_timezone_set('Asia/Jakarta');
require_once 'config/db.php';

if (!isset($_SESSION['kasir_id'])) {
    header('Location: index.php');
    exit();
}

$stmt = $pdo->query("SELECT m.*, k.nama_kategori FROM menu m JOIN kategori k ON m.id_kategori = k.id ORDER BY k.id, m.id");
$menu = $stmt->fetchAll();

// Generate kode transaksi unik berurutan
function generateUniqueCode($pdo) {
    $today = date('dmy');
    $stmt = $pdo->prepare("SELECT kode_transaksi FROM transaksi WHERE kode_transaksi LIKE CONCAT('TRX', ?, '%') ORDER BY id DESC LIMIT 1");
    $stmt->execute([$today]);
    $lastCode = $stmt->fetchColumn();
    
    if ($lastCode && substr($lastCode, 3, 6) == $today) {
        $lastNumber = (int)substr($lastCode, -2);
        $newNumber = str_pad($lastNumber + 1, 2, '0', STR_PAD_LEFT);
    } else {
        $newNumber = '01';
    }
    
    return 'TRX' . $today . $newNumber;
}

do {
    $kode_transaksi = generateUniqueCode($pdo);
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM transaksi WHERE kode_transaksi = ?");
    $stmt->execute([$kode_transaksi]);
    $exists = $stmt->fetchColumn();
} while ($exists > 0);
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
        .sidebar-header { padding: 25px 20px; border-bottom: 1px solid rgba(255,255,255,0.1); text-align: center; }
        .sidebar-header h3 { font-size: 18px; color: #E8B84B; }
        .sidebar-nav { flex: 1; padding: 20px 0; }
        .nav-item { display: flex; align-items: center; gap: 15px; padding: 14px 20px; color: rgba(255,255,255,0.8); text-decoration: none; margin: 5px 10px; border-radius: 12px; }
        .nav-item i { width: 24px; }
        .nav-item:hover { background: rgba(232,184,75,0.2); color: #E8B84B; }
        .nav-item.active { background: #E8B84B; color: #2A4B2F; }
        .sidebar-footer { padding: 20px; }
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

        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        @keyframes slideOut {
            from { transform: translateX(0); opacity: 1; }
            to { transform: translateX(100%); opacity: 0; }
        }

        .custom-notification {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 10001;
            animation: slideIn 0.3s ease;
        }
        .custom-notification-content {
            background: white;
            border-radius: 12px;
            padding: 12px 20px;
            min-width: 280px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            display: flex;
            align-items: center;
            gap: 12px;
            border-left: 4px solid;
        }
        .custom-notification.success .custom-notification-content { border-left-color: #2A4B2F; background: #f0fdf4; }
        .custom-notification.error .custom-notification-content { border-left-color: #dc2626; background: #fef2f2; }
        .custom-notification.warning .custom-notification-content { border-left-color: #f59e0b; background: #fffbeb; }
        .notif-text { flex: 1; }
        .notif-title { font-weight: 700; margin-bottom: 2px; font-size: 13px; }
        .notif-message { font-size: 11px; color: #6b7280; }
        .notif-close { background: none; border: none; cursor: pointer; font-size: 12px; }

        @media (max-width: 900px) { 
            .kasir-layout { grid-template-columns: 1fr; } 
            .sidebar { width: 80px; } 
            .sidebar-header h3, .nav-item span { display: none; } 
            .nav-item { justify-content: center; } 
        }
    </style>
</head>
<body>
    <div class="app-container">
        <aside class="sidebar">
            <div class="sidebar-header">
                <h3>Resto Serba Serbi</h3>
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
                            <button class="btn-category" data-cat="nasi">NASI</button>
                            <button class="btn-category" data-cat="lauk">LAUK</button>
                            <button class="btn-category" data-cat="pepes">PEPES</button>
                            <button class="btn-category" data-cat="minuman">MINUMAN</button>
                            <button class="btn-category" data-cat="sayuran">SAYURAN</button>
                            <button class="btn-category" data-cat="camilan">CAMILAN</button>
                            <div class="search-box"><i class="fas fa-search"></i><input type="text" id="searchMenu" placeholder="Cari menu..."></div>
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
                                <p>Rp <?= number_format($item['harga'], 0, ',', '.') ?></p>
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
                    <button class="pay-method active" data-method="cash"><i class="fas fa-money-bill-wave"></i> CASH</button>
                    <button class="pay-method" data-method="qris"><i class="fas fa-qrcode"></i> QRIS</button>
                </div>
                <div id="cashSection">
                    <div class="cash-input"><label>Jumlah Uang</label><input type="number" id="jumlahUang" placeholder="Masukkan nominal uang"></div>
                    <div id="kembalianBox" class="kembalian-box"><div class="label">Kembalian</div><div class="amount" id="kembalian">Rp 0</div></div>
                </div>
                <div id="qrisSection" style="display:none;">
                    <div class="qris-box"><i class="fas fa-qrcode"></i><p>Scan QR Code untuk pembayaran</p></div>
                </div>
            </div>
            <div class="payment-footer">
                <button id="confirmPayment" class="btn-pay">BAYAR</button>
                <button id="cancelPayment" class="btn-cancel-pay">BATAL</button>
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

        function formatRupiah(angka) {
            return 'Rp ' + angka.toLocaleString('id-ID');
        }

        function showNotif(title, message, type) {
            let old = document.querySelector('.custom-notification');
            if(old) old.remove();
            let notif = document.createElement('div');
            notif.className = 'custom-notification ' + type;
            notif.innerHTML = '<div class="custom-notification-content"><div class="notif-text"><div class="notif-title">' + title + '</div><div class="notif-message">' + message + '</div></div><button class="notif-close" onclick="this.closest(\'.custom-notification\').remove()"><i class="fas fa-times"></i></button></div>';
            document.body.appendChild(notif);
            setTimeout(() => { if(notif) notif.remove(); }, 1000);
        }

        function showStruk(data, metode, uang) {
            let items = '';
            for(let item of data.items) {
                items += '<tr><td style="padding:4px 0;">' + item.name + '</td><td style="text-align:center;">' + item.qty + '</td><td style="text-align:right;">' + formatRupiah(item.price) + '</td><td style="text-align:right;">' + formatRupiah(item.price * item.qty) + '</td></tr>';
            }
            let kembalian = uang ? uang - data.total : null;
            let html = '<div id="strukPrint" style="position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.6);z-index:10000;display:flex;align-items:center;justify-content:center;"><div style="background:white;width:320px;padding:16px;font-family:monospace;font-size:12px;"><div style="text-align:center;"><b>RESTO SERBA SERBI</b><br>Jl. Hos Cokroaminoto Gg Nangka, Cianjur<br>Telp: (0263) 123456<hr>' + data.tanggal + '<br>Kasir: <?= $_SESSION['kasir_nama'] ?><br>Metode: ' + metode.toUpperCase() + '<br><b>Kode: ' + data.kode_transaksi + '</b><hr></div><table style="width:100%;"><thead><tr><th>Item</th><th>Qty</th><th>Harga</th><th>Total</th></tr></thead><tbody>' + items + '</tbody></table><hr><div>Subtotal: ' + formatRupiah(data.subtotal) + (data.pajak > 0 ? '<br>Pajak (5%): ' + formatRupiah(data.pajak) : '') + '<hr><b>Total: ' + formatRupiah(data.total) + '</b>' + (kembalian !== null ? '<hr>Tunai: ' + formatRupiah(uang) + '<br>Kembalian: ' + formatRupiah(kembalian) : '') + '</div><hr><div style="text-align:center;">Terima Kasih<br>Silahkan Datang Kembali</div><div style="display:flex;gap:10px;margin-top:16px;justify-content:center;"><button onclick="printStruk()" style="padding:6px 12px;background:#2A4B2F;color:white;border:none;">CETAK</button><button onclick="closeStruk()" style="padding:6px 12px;background:#E8B84B;color:#2A4B2F;border:none;">TUTUP</button></div></div></div>';
            document.body.insertAdjacentHTML('beforeend', html);
        }

        function printStruk() {
            let content = document.querySelector('#strukPrint > div');
            let win = window.open('', '_blank');
            win.document.write('<html><head><title>Struk</title><style>body{font-family:monospace;padding:20px;}button{display:none;}</style></head><body>' + content.outerHTML + '</body></html>');
            win.document.close();
            win.print();
            win.close();
        }

        function closeStruk() { let el = document.getElementById('strukPrint'); if(el) el.remove(); }

        function tambahKeKeranjang(btn) {
            if(btn.disabled) { showNotif('Stok Habis', 'Menu ini habis!', 'warning'); return; }
            let item = btn.closest('.menu-item');
            let id = parseInt(item.dataset.id), nama = item.dataset.name, harga = parseInt(item.dataset.price), stok = parseInt(item.dataset.stock);
            if(stok <= 0) { showNotif('Stok Habis', 'Maaf, ' + nama + ' habis!', 'warning'); btn.disabled = true; btn.innerHTML = 'HABIS'; return; }
            let existing = keranjang.find(i => i.id === id);
            if(existing) {
                if(existing.qty + 1 > stok) { showNotif('Stok Tidak Cukup', 'Stok ' + nama + ' tersisa ' + stok, 'warning'); return; }
                existing.qty++;
                showNotif('Berhasil', nama + ' +1', 'success');
            } else {
                keranjang.push({id, nama, harga, qty: 1});
                showNotif('Ditambahkan', nama + ' ditambahkan', 'success');
            }
            renderCart();
        }

        function hitungSubtotal() { return keranjang.reduce((s,i) => s + (i.harga * i.qty), 0); }
        function hitungPajak() { return tipeOrder === 'dinein' ? Math.round(hitungSubtotal() * 0.05) : 0; }
        function hitungTotal() { return hitungSubtotal() + hitungPajak(); }

        function renderCart() {
            let tbody = document.getElementById('cartItems');
            if(keranjang.length === 0) {
                tbody.innerHTML = '<tr class="empty-row"><td colspan="5">Belum ada pesanan</td></tr>';
                document.getElementById('subtotal').innerHTML = 'Rp 0';
                document.getElementById('taxAmount').innerHTML = 'Rp 0';
                document.getElementById('cartTotal').innerHTML = 'Rp 0';
                document.getElementById('bayarBtn').disabled = true;
                document.getElementById('taxRow').style.display = tipeOrder === 'dinein' ? 'flex' : 'none';
                return;
            }
            let html = '';
            for(let item of keranjang) {
                html += '<tr><td>' + item.nama + '</td><td style="text-align:center;"><button onclick="ubahQty(' + item.id + ',-1)" style="background:#dc2626;color:white;border:none;width:28px;border-radius:6px;">-</button> ' + item.qty + ' <button onclick="ubahQty(' + item.id + ',1)" style="background:#2A4B2F;color:white;border:none;width:28px;border-radius:6px;">+</button></td><td>' + formatRupiah(item.harga) + '</td><td>' + formatRupiah(item.harga * item.qty) + '</td><td><button onclick="hapusItem(' + item.id + ')" style="background:none;border:none;color:#dc2626;"><i class="fas fa-trash"></i></button></td></tr>';
            }
            tbody.innerHTML = html;
            let subtotal = hitungSubtotal(), pajak = hitungPajak(), total = hitungTotal();
            document.getElementById('subtotal').innerHTML = formatRupiah(subtotal);
            document.getElementById('taxAmount').innerHTML = formatRupiah(pajak);
            document.getElementById('cartTotal').innerHTML = formatRupiah(total);
            document.getElementById('bayarBtn').disabled = false;
            document.getElementById('taxRow').style.display = tipeOrder === 'dinein' ? 'flex' : 'none';
        }

        function ubahQty(id, change) {
            let index = keranjang.findIndex(i => i.id === id);
            if(index === -1) return;
            let item = keranjang[index];
            let menuItem = document.querySelector('.menu-item[data-id="' + id + '"]');
            let maxStock = menuItem ? parseInt(menuItem.dataset.stock) : 999;
            let newQty = item.qty + change;
            if(newQty <= 0) {
                keranjang.splice(index,1);
                showNotif('Dihapus', item.nama + ' dihapus', 'warning');
            } else if(newQty > maxStock) {
                showNotif('Stok Tidak Cukup', 'Stok ' + item.nama + ' tersisa ' + maxStock, 'warning');
                return;
            } else {
                item.qty = newQty;
            }
            renderCart();
        }

        function hapusItem(id) {
            let item = keranjang.find(i => i.id === id);
            if(item) {
                keranjang = keranjang.filter(i => i.id !== id);
                renderCart();
                showNotif('Dihapus', item.nama + ' dihapus', 'warning');
            }
        }

        function batalkanSemua() {
            if(keranjang.length === 0) return;
            if(confirm('Batalkan semua pesanan?')) {
                keranjang = [];
                renderCart();
                showNotif('Dibatalkan', 'Semua pesanan dibatalkan', 'warning');
            }
        }

        function filterMenu() {
            let items = document.querySelectorAll('.menu-item');
            items.forEach(item => {
                let nama = item.dataset.name.toLowerCase();
                let kategoriHeader = item.previousElementSibling;
                while(kategoriHeader && !kategoriHeader.classList.contains('menu-kategori')) kategoriHeader = kategoriHeader.previousElementSibling;
                let kategoriNama = kategoriHeader ? kategoriHeader.innerText : '';
                let kategoriValue = mapKategori[kategoriNama] || 'all';
                let matchKategori = kategoriAktif === 'all' || kategoriValue === kategoriAktif;
                let matchCari = nama.includes(kataCari);
                item.style.display = matchKategori && matchCari ? 'flex' : 'none';
            });
            document.querySelectorAll('.menu-kategori').forEach(div => {
                let next = div.nextElementSibling, ada = false;
                while(next && next.classList.contains('menu-item')) {
                    if(next.style.display !== 'none') { ada = true; break; }
                    next = next.nextElementSibling;
                }
                div.style.display = ada ? 'block' : 'none';
            });
        }

        function showPaymentModal() {
            if(keranjang.length === 0) { showNotif('Keranjang Kosong', 'Belum ada pesanan!', 'warning'); return; }
            document.getElementById('paymentModal').style.display = 'flex';
            document.getElementById('modalTotal').innerHTML = formatRupiah(hitungTotal());
            document.getElementById('jumlahUang').value = '';
            document.getElementById('kembalian').innerHTML = 'Rp 0';
            document.getElementById('kembalianBox').classList.remove('error');
        }

        async function prosesPembayaran(metode, uang) {
            if(isProcessing) return;
            let cekStokGagal = [];
            for(let item of keranjang) {
                let menuItem = document.querySelector('.menu-item[data-id="' + item.id + '"]');
                let stokTersedia = menuItem ? parseInt(menuItem.dataset.stock) : 0;
                if(item.qty > stokTersedia) cekStokGagal.push(item.nama);
            }
            if(cekStokGagal.length > 0) {
                showNotif('Stok Tidak Cukup', cekStokGagal.join(', ') + ' stok tidak mencukupi!', 'error');
                return;
            }
            isProcessing = true;
            let total = hitungTotal();
            if(metode === 'cash' && uang < total) {
                showNotif('Pembayaran Gagal', 'Uang tidak cukup!', 'error');
                isProcessing = false;
                return;
            }
            let data = {
                kode_transaksi: document.getElementById('kodeTransaksi').value,
                id_kasir: parseInt(document.getElementById('kasirId').value),
                tipe_order: tipeOrder,
                subtotal: hitungSubtotal(),
                pajak: hitungPajak(),
                total: total,
                metode: metode,
                items: keranjang.map(i => ({id: i.id, qty: i.qty, price: i.harga, name: i.nama}))
            };
            try {
                let res = await fetch('api/proses_transaksi.php',  {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify(data)
                });
                let result = await res.json();
                if(result.success) {
                    for(let item of keranjang) {
                        let menuItem = document.querySelector('.menu-item[data-id="' + item.id + '"]');
                        if(menuItem) {
                            let stokBaru = parseInt(menuItem.dataset.stock) - item.qty;
                            menuItem.dataset.stock = stokBaru;
                            let small = menuItem.querySelector('small');
                            if(small) small.innerHTML = 'Stok: ' + stokBaru;
                            let btn = menuItem.querySelector('.btn-select');
                            if(stokBaru <= 0) { btn.disabled = true; btn.innerHTML = 'HABIS'; }
                        }
                    }
                    showNotif('Berhasil', metode === 'cash' ? 'Kembalian: ' + formatRupiah(uang - total) : 'Pembayaran QRIS berhasil', 'success');
                    showStruk(result.data, metode, uang);
                    keranjang = [];
                    renderCart();
                    document.getElementById('paymentModal').style.display = 'none';
                } else {
                    showNotif('Gagal', result.message, 'error');
                }
            } catch(e) {
                showNotif('Error', e.message, 'error');
            }
            isProcessing = false;
        }

        // Event Listeners
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
        document.getElementById('cancelPayment').onclick = () => document.getElementById('paymentModal').style.display = 'none';
        window.onclick = function(e) { if(e.target === document.getElementById('paymentModal')) document.getElementById('paymentModal').style.display = 'none'; };

        document.querySelectorAll('.pay-method').forEach(btn => {
            btn.onclick = function() {
                document.querySelectorAll('.pay-method').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                let method = this.dataset.method;
                document.getElementById('cashSection').style.display = method === 'cash' ? 'block' : 'none';
                document.getElementById('qrisSection').style.display = method === 'qris' ? 'block' : 'none';
            };
        });

        document.getElementById('jumlahUang').oninput = function() {
            let total = hitungTotal();
            let uang = parseInt(this.value) || 0;
            let kembalian = uang - total;
            let span = document.getElementById('kembalian');
            let box = document.getElementById('kembalianBox');
            if(kembalian < 0) {
                span.innerHTML = 'Uang Tidak Cukup';
                box.classList.add('error');
            } else {
                span.innerHTML = formatRupiah(kembalian);
                box.classList.remove('error');
            }
        };

        document.getElementById('confirmPayment').onclick = function() {
            let activeMethod = document.querySelector('.pay-method.active').dataset.method;
            if(activeMethod === 'cash') {
                let total = hitungTotal();
                let uang = parseInt(document.getElementById('jumlahUang').value) || 0;
                if(uang >= total) prosesPembayaran('cash', uang);
                else showNotif('Uang Kurang', 'Uang tidak cukup!', 'error');
            } else {
                prosesPembayaran('qris', null);
            }
        };

        filterMenu();
        let now = new Date();
        document.getElementById('currentDate').innerHTML = now.toLocaleDateString('id-ID', {weekday:'long', year:'numeric', month:'numeric', day:'numeric'}) + ' ' + now.toLocaleTimeString('id-ID');
    </script>
</body>
</html>