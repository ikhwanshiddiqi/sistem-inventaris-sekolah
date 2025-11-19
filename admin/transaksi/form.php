<?php

/**
 * Form Transaksi - Admin Panel
 * Sistem Inventaris Sekolah
 */

$page_title = isset($_GET['action']) && $_GET['action'] == 'edit' ? 'Edit Transaksi' : 'Tambah Transaksi';
require_once '../includes/header.php';

$action = $_GET['action'] ?? 'add';
$transaksi_id = $_GET['id'] ?? null;
$error = '';
$success = '';

try {
    $pdo = new PDO("mysql:host=localhost;dbname=inventaris_sekolah", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // ambil daftar barang untuk select
    $barang_list = $pdo->query("SELECT id, nama_barang FROM barang ORDER BY nama_barang")->fetchAll(PDO::FETCH_ASSOC);

    // jika edit, ambil data transaksi
    $transaksi = null;
    if ($action == 'edit' && $transaksi_id) {
        $stmt = $pdo->prepare("SELECT * FROM transaksi WHERE id = ?");
        $stmt->execute([$transaksi_id]);
        $transaksi = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$transaksi) {
            $error = 'Transaksi tidak ditemukan';
        }
    }
} catch (Exception $e) {
    error_log("[admin/transaksi/form.php] DB error: " . $e->getMessage());
    $barang_list = [];
    $transaksi = null;
    $error = 'Gagal koneksi database';
}

// proses submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $barang_id = (int)($_POST['barang_id'] ?? 0);
    $deskripsi = trim($_POST['deskripsi'] ?? '');
    $kondisi = trim($_POST['kondisi'] ?? 'baik');
    $jumlah = (int)($_POST['jumlah'] ?? 0);
    $harga_satuan = (float)($_POST['harga_satuan'] ?? 0);
    $tahun_pengadaan = trim($_POST['tahun_pengadaan'] ?? null);

    // validasi
    if ($barang_id <= 0) {
        $error = 'Silakan pilih barang.';
    } elseif ($jumlah <= 0) {
        $error = 'Jumlah harus diisi dan minimal 1.';
    } elseif ($harga_satuan < 0) {
        $error = 'Harga satuan tidak valid.';
    } else {
        // pastikan barang ada (menghindari FK error)
        try {
            $chk = $pdo->prepare("SELECT 1 FROM barang WHERE id = ?");
            $chk->execute([$barang_id]);
            if (!$chk->fetch()) {
                $error = 'Barang yang dipilih tidak ditemukan.';
            }
        } catch (Exception $e) {
            error_log("[admin/transaksi/form.php] check barang error: " . $e->getMessage());
            $error = 'Terjadi kesalahan saat validasi barang.';
        }
    }

    if (empty($error)) {
        $total = (int)($jumlah * $harga_satuan);

        try {
            if ($action == 'add') {
                $stmt = $pdo->prepare("
                    INSERT INTO transaksi (barang_id, deskripsi, kondisi, jumlah, harga_satuan, total, tahun_pengadaan)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $barang_id,
                    $deskripsi,
                    $kondisi,
                    $jumlah,
                    $harga_satuan,
                    $total,
                    $tahun_pengadaan ?: null
                ]);
                $success = 'Transaksi berhasil ditambahkan!';
                echo "<script>localStorage.removeItem('transaksiFormData'); alert('Transaksi berhasil ditambahkan!'); location.href='index.php';</script>";
                exit();
            } else {
                $stmt = $pdo->prepare("
                    UPDATE transaksi SET
                        barang_id = ?, deskripsi = ?, kondisi = ?, jumlah = ?, harga_satuan = ?, total = ?, tahun_pengadaan = ?, updated_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([
                    $barang_id,
                    $deskripsi,
                    $kondisi,
                    $jumlah,
                    $harga_satuan,
                    $total,
                    $tahun_pengadaan ?: null,
                    $transaksi_id
                ]);
                $success = 'Transaksi berhasil diupdate!';
                echo "<script>localStorage.removeItem('transaksiFormData'); alert('Transaksi berhasil diupdate!'); location.href='index.php';</script>";
                exit();
            }
        } catch (Exception $e) {
            error_log("[admin/transaksi/form.php] DB error: " . $e->getMessage());
            if (isset($_GET['debug'])) {
                $error = 'Terjadi kesalahan sistem: ' . htmlspecialchars($e->getMessage());
            } else {
                $error = 'Terjadi kesalahan sistem!';
            }
        }
    }
}

// helper values untuk form (prefill saat edit atau setelah gagal validasi)
$old = function ($key, $default = '') use ($transaksi) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        return $_POST[$key] ?? $default;
    }
    if ($transaksi) {
        return $transaksi[$key] ?? $default;
    }
    return $default;
};

?>

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
                            <?= $action == 'edit' ? 'Edit data transaksi yang sudah ada' : 'Tambahkan transaksi baru ke inventaris' ?>
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

<!-- Form Section -->
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
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="barang_id" class="form-label">Barang <span class="text-danger">*</span></label>
                            <select name="barang_id" id="barang_id" class="form-select" required>
                                <option value="">Pilih Barang</option>
                                <?php foreach ($barang_list as $b): ?>
                                    <option value="<?= $b['id'] ?>" <?= (string)$old('barang_id') === (string)$b['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($b['nama_barang']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-8 mb-3">
                            <label for="deskripsi" class="form-label">Deskripsi</label>
                            <textarea name="deskripsi" id="deskripsi" class="form-control" rows="3"><?= htmlspecialchars($old('deskripsi')) ?></textarea>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-2 mb-3">
                            <label for="tahun_pengadaan" class="form-label">Tahun Pengadaan</label>
                            <input type="number" name="tahun_pengadaan" id="tahun_pengadaan" class="form-control" min="1900" max="<?= date('Y') ?>" value="<?= htmlspecialchars($old('tahun_pengadaan')) ?>">
                        </div>
                        <div class="col-md-2 mb-3">
                            <label for="kondisi" class="form-label">Kondisi</label>
                            <select name="kondisi" id="kondisi" class="form-select">
                                <?php $kondisi_opts = ['baik' => 'Baik', 'rusak_ringan' => 'Rusak Ringan', 'rusak_berat' => 'Rusak Berat']; ?>
                                <?php foreach ($kondisi_opts as $k => $label): ?>
                                    <option value="<?= $k ?>" <?= $old('kondisi', 'baik') === $k ? 'selected' : '' ?>><?= $label ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-2 mb-3">
                            <label for="jumlah" class="form-label">Jumlah <span class="text-danger">*</span></label>
                            <input type="number" name="jumlah" id="jumlah" class="form-control" min="1" value="<?= htmlspecialchars($old('jumlah', 1)) ?>" required>
                        </div>

                        <div class="col-md-3 mb-3">
                            <label for="harga_satuan" class="form-label">Harga Satuan</label>
                            <input type="number" step="0.01" name="harga_satuan" id="harga_satuan" class="form-control" value="<?= htmlspecialchars($old('harga_satuan', '0.00')) ?>">
                        </div>

                        <div class="col-md-3 mb-3">
                            <label for="total" class="form-label">Total</label>
                            <input type="number" step="0.01" id="total" class="form-control" value="<?= htmlspecialchars($old('total', '0')) ?>" readonly>
                        </div>
                    </div>

                    <!-- Auto-save indicator -->
                    <div class="alert alert-info alert-sm" id="autoSaveIndicator" style="display: none;">
                        <i class="fas fa-save me-1"></i>Auto-save aktif
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <a href="index.php" class="btn btn-secondary">Batal</a>
                        <button type="submit" class="btn btn-primary"><?= $action == 'edit' ? 'Update' : 'Simpan' ?></button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    // Auto-save functionality
    let autoSaveTimer;
    const form = document.getElementById('transaksiForm');
    const autoSaveIndicator = document.getElementById('autoSaveIndicator');

    form.addEventListener('input', function() {
        clearTimeout(autoSaveTimer);
        autoSaveTimer = setTimeout(() => {
            saveFormData();
            showAutoSaveIndicator();
        }, 2000);
    });

    function saveFormData() {
        const formData = new FormData(form);
        localStorage.setItem('transaksiFormData', JSON.stringify(Object.fromEntries(formData)));
    }

    function showAutoSaveIndicator() {
        autoSaveIndicator.style.display = 'block';
        setTimeout(() => {
            autoSaveIndicator.style.display = 'none';
        }, 2000);
    }

    // Restore form data on page load
    window.addEventListener('load', function() {
        const savedData = localStorage.getItem('transaksiFormData');
        if (savedData && !<?= $action == 'edit' ? 'true' : 'false' ?>) {
            const data = JSON.parse(savedData);
            Object.keys(data).forEach(key => {
                const element = form.elements[key];
                if (element && element.type !== 'file') {
                    element.value = data[key];
                }
            });
        }
    });

    const jumlahEl = document.getElementById('jumlah');
    const hargaEl = document.getElementById('harga_satuan');
    const totalEl = document.getElementById('total');

    function calcTotal() {
        const jumlah = parseFloat(jumlahEl.value) || 0;
        const harga = parseFloat(hargaEl.value) || 0;
        totalEl.value = (jumlah * harga).toFixed(2);
    }

    jumlahEl.addEventListener('input', calcTotal);
    hargaEl.addEventListener('input', calcTotal);
    window.addEventListener('load', calcTotal);
</script>

<?php require_once '../includes/footer.php'; ?>
?>