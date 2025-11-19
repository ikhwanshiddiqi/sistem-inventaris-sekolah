<?php

/**
 * Form Barang - Admin Panel
 * Sistem Inventaris Sekolah
 */

// Simple upload function
function uploadFileSimple($file, $destination)
{
    if (!isset($file['error']) || is_array($file['error'])) {
        return false;
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return false;
    }

    $fileInfo = pathinfo($file['name']);
    $extension = strtolower($fileInfo['extension']);
    $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];

    if (!in_array($extension, $allowedTypes)) {
        return false;
    }

    $filename = uniqid() . '.' . $extension;
    $filepath = $destination . '/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        return false;
    }

    return $filename;
}

$page_title = isset($_GET['action']) && $_GET['action'] == 'edit' ? 'Edit Transaksi' : 'Tambah Transaksi';
require_once '../includes/header.php';

$action = $_GET['action'] ?? 'add';
$barang_id = $_GET['id'] ?? null;
$barang = null;
$error = '';
$success = '';

// Ambil data untuk dropdown
try {
    $pdo = new PDO("mysql:host=localhost;dbname=inventaris_sekolah", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $kategori_list = $pdo->query("SELECT * FROM kategori ORDER BY nama_kategori")->fetchAll();

    // Jika edit, ambil data barang
    if ($action == 'edit' && $barang_id) {
        $stmt = $pdo->prepare("SELECT * FROM barang WHERE id = ?");
        $stmt->execute([$barang_id]);
        $barang = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$barang) {
            $error = 'Barang tidak ditemukan!';
            // Use JavaScript redirect instead of PHP header
            echo "<script>alert('Barang tidak ditemukan!'); window.location.href='index.php';</script>";
            exit();
        }
    }
} catch (Exception $e) {
    $kategori_list = [];
}

// Proses form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_barang = trim($_POST['nama_barang']);
    $deskripsi = trim($_POST['deskripsi']);
    $kategori_id = (int)$_POST['kategori_id'];

    // Validasi
    if (empty($nama_barang)) {
        $error = 'Nama barang harus diisi!';
    } else {
        try {
            if ($action == 'add') {
                // Upload foto jika ada
                $foto = null;
                $upload_dir = '../../uploads/';
                if (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
                    if (!file_exists($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }

                    $foto = uploadFileSimple($_FILES['foto'], $upload_dir);
                    if (!$foto) {
                        $error = 'Gagal upload foto!';
                    }
                }

                if (!$error) {
                    $stmt = $pdo->prepare("
                        INSERT INTO barang (nama_barang, deskripsi, kategori_id, 
                                          foto)
                        VALUES (?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        $nama_barang,
                        $deskripsi,
                        $kategori_id,
                        $foto,
                    ]);

                    $barang_id = $pdo->lastInsertId();
                    $success = 'Barang berhasil ditambahkan!';
                    echo "<script>alert('Barang berhasil ditambahkan!'); window.location.href='index.php';</script>";
                    exit();
                }
            } else {
                // Update barang
                $foto = $barang['foto']; // Keep existing foto
                $upload_dir = '../../uploads/';

                if (isset($_POST['hapus_foto']) && $_POST['hapus_foto'] == '1') {
                    if ($foto && file_exists($upload_dir . $foto)) {
                        unlink($upload_dir . $foto);
                    }
                    $foto = null;
                } elseif (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
                    $new_foto = uploadFileSimple($_FILES['foto'], $upload_dir);
                    if ($new_foto) {
                        if ($foto && file_exists($upload_dir . $foto)) {
                            unlink($upload_dir . $foto);
                        }
                        $foto = $new_foto;
                    }
                }

                $stmt = $pdo->prepare("
                    UPDATE barang SET 
                        nama_barang = ?, deskripsi = ?, kategori_id = ?,
                        foto = ?
                    WHERE id = ?
                ");
                $stmt->execute([
                    $nama_barang,
                    $deskripsi,
                    $kategori_id,
                    $foto,
                    $barang_id
                ]);

                $success = 'Barang berhasil diupdate!';
                echo "<script>alert('Barang berhasil diupdate!'); window.location.href='index.php';</script>";
                exit();
            }
        } catch (Exception $e) {
            $error = 'Terjadi kesalahan sistem!';
            // Log detail error untuk debugging
            error_log("[admin/barang/form.php] ERROR: " . $e->getMessage());
            if (isset($_GET['debug'])) {
                $error = 'Terjadi kesalahan sistem: ' . htmlspecialchars($e->getMessage());
            } else {
                $error = 'Terjadi kesalahan sistem!';
            }
        }
    }
}
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

                <form method="POST" action="" enctype="multipart/form-data" id="barangForm">


                    <div class="row">
                        <div class="col-md-8">
                            <!-- Basic Information -->
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="nama_barang" class="form-label">
                                        <i class="fas fa-box me-1"></i>Nama Barang <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" class="form-control" id="nama_barang" name="nama_barang"
                                        value="<?= htmlspecialchars($barang['nama_barang'] ?? '') ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="kategori_id" class="form-label">
                                        <i class="fas fa-tags me-1"></i>Kategori <span class="text-danger">*</span>
                                    </label>
                                    <select class="form-select" id="kategori_id" name="kategori_id" required>
                                        <option value="">Pilih Kategori</option>
                                        <?php foreach ($kategori_list as $kat): ?>
                                            <option value="<?= $kat['id'] ?>"
                                                <?= ($barang['kategori_id'] ?? '') == $kat['id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($kat['nama_kategori']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="deskripsi" class="form-label">
                                    <i class="fas fa-align-left me-1"></i>Deskripsi
                                </label>
                                <textarea class="form-control" id="deskripsi" name="deskripsi" rows="3"
                                    placeholder="Deskripsi detail barang..."><?= htmlspecialchars($barang['deskripsi'] ?? '') ?></textarea>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <!-- Photo Upload -->
                            <div class="mb-3">
                                <label for="foto" class="form-label">
                                    <i class="fas fa-camera me-1"></i>Foto Barang
                                </label>
                                <div class="upload-area" id="uploadArea">
                                    <?php if (isset($barang['foto']) && $barang['foto']): ?>
                                        <img src="../../uploads/<?= $barang['foto'] ?>" id="previewImage"
                                            class="img-fluid rounded" style="max-height: 200px;">
                                    <?php else: ?>
                                        <div class="upload-placeholder" id="uploadPlaceholder">
                                            <i class="fas fa-cloud-upload-alt fa-3x text-muted mb-3"></i>
                                            <p class="text-muted mb-0">Klik untuk upload foto</p>
                                        </div>
                                    <?php endif; ?>
                                    <input type="file" class="form-control" id="foto" name="foto"
                                        accept="image/*" style="display: none;">
                                </div>

                                <?php if ($action == 'edit' && isset($barang['foto']) && $barang['foto']): ?>
                                    <div class="form-check mt-2">
                                        <input class="form-check-input" type="checkbox" id="hapus_foto" name="hapus_foto" value="1">
                                        <label class="form-check-label text-danger" for="hapus_foto">
                                            <i class="fas fa-trash me-1"></i>Hapus foto saat ini
                                        </label>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Auto-save indicator -->
                            <div class="alert alert-info alert-sm" id="autoSaveIndicator" style="display: none;">
                                <i class="fas fa-save me-1"></i>Auto-save aktif
                            </div>
                        </div>
                    </div>

                    <hr>

                    <!-- Action Buttons -->
                    <div class="row">
                        <div class="col-12">
                            <div class="d-flex gap-2 justify-content-end">
                                <a href="index.php" class="btn btn-secondary">
                                    <i class="fas fa-times me-2"></i>Batal
                                </a>
                                <button type="submit" class="btn btn-primary" id="submitBtn">
                                    <i class="fas fa-save me-2"></i>
                                    <?= $action == 'edit' ? 'Update Barang' : 'Simpan Barang' ?>
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
    .upload-area {
        border: 2px dashed #dee2e6;
        border-radius: 10px;
        padding: 20px;
        text-align: center;
        cursor: pointer;
        transition: all 0.3s ease;
        background: #f8f9fa;
    }

    .upload-area:hover {
        border-color: var(--primary-color);
        background: rgba(79, 70, 229, 0.05);
    }

    .upload-placeholder {
        color: #6c757d;
    }

    .alert-sm {
        padding: 0.5rem 0.75rem;
        font-size: 0.875rem;
    }
</style>

<script>
    // Auto-save functionality
    let autoSaveTimer;
    const form = document.getElementById('barangForm');
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
        localStorage.setItem('barangFormData', JSON.stringify(Object.fromEntries(formData)));
    }

    function showAutoSaveIndicator() {
        autoSaveIndicator.style.display = 'block';
        setTimeout(() => {
            autoSaveIndicator.style.display = 'none';
        }, 2000);
    }

    // Restore form data on page load
    window.addEventListener('load', function() {
        const savedData = localStorage.getItem('barangFormData');
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

    // Clear saved data on successful submit
    form.addEventListener('submit', function() {
        localStorage.removeItem('barangFormData');
    });

    // Photo upload preview
    const uploadArea = document.getElementById('uploadArea');
    const fotoInput = document.getElementById('foto');
    const previewImage = document.getElementById('previewImage');
    const uploadPlaceholder = document.getElementById('uploadPlaceholder');

    uploadArea.addEventListener('click', function() {
        fotoInput.click();
    });

    fotoInput.addEventListener('change', function() {
        const file = this.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                if (previewImage) {
                    previewImage.src = e.target.result;
                } else {
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.className = 'img-fluid rounded';
                    img.style.maxHeight = '200px';
                    img.id = 'previewImage';

                    uploadPlaceholder.style.display = 'none';
                    uploadArea.appendChild(img);
                }
            };
            reader.readAsDataURL(file);
        }
    });

    // Handle hapus foto checkbox
    const hapusFotoCheckbox = document.getElementById('hapus_foto');
    if (hapusFotoCheckbox) {
        hapusFotoCheckbox.addEventListener('change', function() {
            const fotoInput = document.getElementById('foto');
            const uploadArea = document.getElementById('uploadArea');

            if (this.checked) {
                // Disable file input and show message
                fotoInput.disabled = true;
                uploadArea.innerHTML = `
                <div class="upload-placeholder text-danger">
                    <i class="fas fa-trash fa-3x mb-3"></i>
                    <p class="mb-0">Foto akan dihapus saat disimpan</p>
                </div>
            `;
            } else {
                // Re-enable file input and restore original
                fotoInput.disabled = false;
                location.reload(); // Simple way to restore original state
            }
        });
    }

    // Loading state
    const submitBtn = document.getElementById('submitBtn');
    form.addEventListener('submit', function() {
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Menyimpan...';
        submitBtn.disabled = true;
    });
</script>

<?php require_once '../includes/footer.php'; ?>