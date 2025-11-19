<?php

/**
 * Form Transaksi - Admin Panel
 */

$page_title = isset($_GET['action']) && $_GET['action'] == 'edit' ? 'Edit Transaksi' : 'Tambah Transaksi';
require_once '../includes/header.php';

$action = $_GET['action'] ?? 'add';
$transaksi_id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$error = '';
$success = '';

try {
    $pdo = new PDO("mysql:host=localhost;dbname=inventaris_sekolah", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // kategori list untuk dropdown (boleh kosong)
    $kategori_list = $pdo->query("SELECT id, nama_kategori FROM kategori ORDER BY nama_kategori")->fetchAll(PDO::FETCH_ASSOC);

    // ambil data transaksi jika edit
    $transaksi = null;
    if ($action === 'edit' && $transaksi_id) {
        $stmt = $pdo->prepare("SELECT * FROM transaksi WHERE id = ?");
        $stmt->execute([$transaksi_id]);
        $transaksi = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$transaksi) {
            echo "<script>alert('Transaksi tidak ditemukan'); location.href='index.php';</script>";
            exit();
        }
    }
} catch (Exception $e) {
    error_log("[admin/transaksi/form.php] DB error: " . $e->getMessage());
    $kategori_list = [];
    $transaksi = null;
    $error = 'Gagal koneksi database';
}

// proses submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kategori_id = isset($_POST['kategori_id']) && $_POST['kategori_id'] !== '' ? (int)$_POST['kategori_id'] : null;
    $nama_barang = trim($_POST['nama_barang'] ?? '');
    $deskripsi = trim($_POST['deskripsi'] ?? '');
    $bahan = trim($_POST['bahan'] ?? '');
    $asal = trim($_POST['asal'] ?? '');
    $jumlah = isset($_POST['jumlah']) ? (int)$_POST['jumlah'] : 0;
    $harga_satuan = isset($_POST['harga_satuan']) ? (float)$_POST['harga_satuan'] : 0.0;
    $tahun_pengadaan = trim($_POST['tahun_pengadaan'] ?? '');
    $total = $jumlah * $harga_satuan;

    // validasi sederhana
    if ($nama_barang === '') {
        $error = 'Nama barang harus diisi.';
    } elseif ($jumlah <= 0) {
        $error = 'Jumlah minimal 1.';
    } elseif ($harga_satuan < 0) {
        $error = 'Harga satuan tidak valid.';
    }

    if (empty($error)) {
        try {
            if ($action === 'add') {
                $stmt = $pdo->prepare("
                    INSERT INTO transaksi
                        (kategori_id, nama_barang, deskripsi, bahan, asal, jumlah, harga_satuan, total, tahun_pengadaan)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $kategori_id,
                    $nama_barang,
                    $deskripsi ?: null,
                    $bahan ?: null,
                    $asal ?: null,
                    $jumlah,
                    number_format($harga_satuan, 2, '.', ''),
                    number_format($total, 2, '.', ''),
                    $tahun_pengadaan ?: null
                ]);
                echo "<script>localStorage.removeItem('transaksiFormData'); alert('Transaksi berhasil ditambahkan'); location.href='index.php';</script>";
                exit();
            } else {
                $stmt = $pdo->prepare("
                    UPDATE transaksi SET
                        kategori_id = ?, nama_barang = ?, deskripsi = ?, bahan = ?, asal = ?, jumlah = ?, harga_satuan = ?, total = ?, tahun_pengadaan = ?, updated_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([
                    $kategori_id,
                    $nama_barang,
                    $deskripsi ?: null,
                    $bahan ?: null,
                    $asal ?: null,
                    $jumlah,
                    number_format($harga_satuan, 2, '.', ''),
                    number_format($total, 2, '.', ''),
                    $tahun_pengadaan ?: null,
                    $transaksi_id
                ]);
                echo "<script>localStorage.removeItem('transaksiFormData'); alert('Transaksi berhasil diupdate'); location.href='index.php';</script>";
                exit();
            }
        } catch (Exception $e) {
            error_log("[admin/transaksi/form.php] DB error: " . $e->getMessage());
            $error = 'Terjadi kesalahan saat menyimpan data.';
            if (isset($_GET['debug'])) {
                $error = 'Error: ' . htmlspecialchars($e->getMessage());
            }
        }
    }
}

// helper untuk prefill form
$old = function ($key, $default = '') use ($transaksi) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        return $_POST[$key] ?? $default;
    }
    if ($transaksi) {
        return $transaksi[$key] ?? $default;
    }
    return $default;
};

// inisialisasi tampilan awal harga & total (format IDR tanpa .00 jika nol desimal)
$init_harga = (float)($old('harga_satuan', $transaksi['harga_satuan'] ?? 0));
function format_idr_display($n)
{
    if ($n == 0) return '';
    if ($n == floor($n)) return number_format($n, 0, ',', '.');
    return number_format($n, 2, ',', '.');
}
$init_harga_display = format_idr_display($init_harga);
$init_total = (float)($old('jumlah', $transaksi['jumlah'] ?? 0)) * $init_harga;
$init_total_display = format_idr_display($init_total);

?>

<!-- Header Section -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h2 class="mb-2">
                            <i class="fas fa-<?= $action == 'edit' ? 'edit' : 'plus' ?> me-2"></i>
                            <?= $page_title ?>
                        </h2>
                        <p class="text-muted mb-0">
                            <?= $action == 'edit' ? 'Edit data barang yang sudah ada' : 'Tambahkan barang baru ke inventaris' ?>
                        </p>
                    </div>
                    <div class="col-md-4 text-md-end">
                        <a href="index.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Kembali
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Form -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-info-circle me-2"></i>Informasi Barang
                </h5>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i><?= $error ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <form method="POST" id="transaksiForm">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">
                                <i class="fas fa-tags me-1"></i>Kategori <span class="text-danger">*</span>
                            </label>
                            <select name="kategori_id" class="form-select">
                                <option value="">Pilih Kategori</option>
                                <?php foreach ($kategori_list as $k): ?>
                                    <option value="<?= $k['id'] ?>" <?= ($old('kategori_id') !== '' && (int)$old('kategori_id') === (int)$k['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($k['nama_kategori']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-8">
                            <label class="form-label"><i class="fas fa-box me-1"></i>Nama Barang <span class="text-danger">*</span></label>
                            <input type="text" name="nama_barang" class="form-control" required value="<?= htmlspecialchars($old('nama_barang')) ?>">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">
                                <i class="fas fa-calendar-alt me-1"></i>Tahun Pengadaan
                            </label>
                            <input type="number" name="tahun_pengadaan" class="form-control" min="1900" max="<?= date('Y') ?>" value="<?= htmlspecialchars($old('tahun_pengadaan')) ?>">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">
                                <i class="fas fa-cubes me-1"></i>Bahan
                            </label>
                            <input type="text" name="bahan" class="form-control" value="<?= htmlspecialchars($old('bahan')) ?>">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">
                                <i class="fas fa-book me-1"></i>Asal Usul
                            </label>
                            <input type="text" name="asal" class="form-control" value="<?= htmlspecialchars($old('asal')) ?>">
                        </div>

                        <div class="col-12">
                            <label class="form-label">
                                <i class="fas fa-align-left me-1"></i>Deskripsi
                            </label>
                            <textarea name="deskripsi" rows="3" class="form-control"><?= htmlspecialchars($old('deskripsi')) ?></textarea>
                        </div>

                        <div class="col-md-2">
                            <label class="form-label"><b>Jumlah</b> <span class="text-danger">*</span></label>
                            <input type="number" name="jumlah" id="jumlah" class="form-control" min="1" required value="<?= htmlspecialchars($old('jumlah', 1)) ?>">
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">
                                <b>Harga Satuan</b> <span class="text-danger">*</span>
                            </label>
                            <input type="hidden" name="harga_satuan" id="harga_satuan" value="<?= htmlspecialchars($old('harga_satuan', $transaksi['harga_satuan'] ?? 0)) ?>">

                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="text" id="harga_satuan_display" class="form-control" inputmode="numeric" pattern="[0-9.,]*"
                                    value="<?= $init_harga_display ?>"
                                    placeholder="0">
                            </div>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">
                                <b>Total</b>
                            </label>
                            <input type="text" id="total" class="form-control" readonly value="<?= $init_total_display ? 'Rp ' . $init_total_display : '' ?>">
                        </div>

                        <div class="col-12 d-flex justify-content-end gap-2 mt-2">
                            <a href="index.php" class="btn btn-secondary">Batal</a>
                            <button type="submit" class="btn btn-primary"><?= $action == 'edit' ? 'Update' : 'Simpan' ?></button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    const form = document.getElementById('transaksiForm');
    const autoSaveKey = 'transaksiFormData';
    const autoSaveIndicator = document.createElement('div');
    autoSaveIndicator.className = 'alert alert-info alert-sm';
    autoSaveIndicator.style.display = 'none';
    autoSaveIndicator.innerHTML = '<i class="fas fa-save me-1"></i>Auto-save aktif';
    form.prepend(autoSaveIndicator);

    let autoSaveTimer;
    form.addEventListener('input', () => {
        clearTimeout(autoSaveTimer);
        autoSaveTimer = setTimeout(() => {
            const fd = new FormData(form);
            localStorage.setItem(autoSaveKey, JSON.stringify(Object.fromEntries(fd)));
            autoSaveIndicator.style.display = 'block';
            setTimeout(() => autoSaveIndicator.style.display = 'none', 1500);
        }, 1500);
    });

    const jumlahEl = document.getElementById('jumlah');
    const hargaHiddenEl = document.getElementById('harga_satuan');
    const hargaDisplayEl = document.getElementById('harga_satuan_display');
    const totalEl = document.getElementById('total');

    // Format ribuan IDR
    function formatIDR(n) {
        if (!isFinite(n)) return "0";
        return n.toLocaleString('id-ID', {
            minimumFractionDigits: 0,
            maximumFractionDigits: 0
        });
    }

    // Hanya angka → return number
    function getNumeric(str) {
        return parseInt(String(str).replace(/\D/g, '')) || 0;
    }

    function calcTotal() {
        const jumlah = parseInt(jumlahEl.value) || 0;
        const harga = parseInt(hargaHiddenEl.value) || 0;
        const total = jumlah * harga;
        totalEl.value = total ? "Rp " + formatIDR(total) : "";
    }

    // REAL-TIME formatting dengan titik ribuan
    function handleHargaInput() {
        let raw = getNumeric(hargaDisplayEl.value); // hanya angka
        hargaHiddenEl.value = raw; // simpan angka murni
        hargaDisplayEl.value = formatIDR(raw); // tampilkan format IDR
        calcTotal();
    }

    if (hargaDisplayEl) {
        // hanya ANGKA, tidak boleh koma atau titik manual
        hargaDisplayEl.addEventListener("input", function() {
            handleHargaInput();
        });

        // Enter → tetap format ulang
        hargaDisplayEl.addEventListener("keydown", function(e) {
            if (e.key === "Enter") {
                e.preventDefault();
                handleHargaInput();
                this.blur();
            }
        });
    }

    jumlahEl && jumlahEl.addEventListener("input", calcTotal);

    window.addEventListener("load", () => {
        const saved = localStorage.getItem(autoSaveKey);
        const isEdit = <?= $action === 'edit' ? 'true' : 'false' ?>;

        if (saved && !isEdit) {
            try {
                const data = JSON.parse(saved);
                Object.keys(data).forEach(k => {
                    const el = form.elements[k];
                    if (el && el.type !== 'file') el.value = data[k];
                });
            } catch (e) {}
        }

        // Set dari hidden ke display pada load
        if (hargaHiddenEl && hargaDisplayEl) {
            let n = parseInt(hargaHiddenEl.value) || 0;
            hargaDisplayEl.value = n ? formatIDR(n) : "";
            hargaHiddenEl.value = n;
        }

        calcTotal();
    });

    form.addEventListener("submit", () => {
        handleHargaInput();
        calcTotal();
        localStorage.removeItem(autoSaveKey);
    });
</script>


<?php require_once '../includes/footer.php'; ?>