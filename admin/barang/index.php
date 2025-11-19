<?php

/**
 * Data Barang - Admin Panel
 * Sistem Inventaris Sekolah
 */

// Handle AJAX request untuk detail FIRST (before any output)
$action = $_GET['action'] ?? '';
if ($action == 'get_detail' && isset($_GET['id'])) {
    session_start();
    $barang_id = (int)$_GET['id'];
    try {
        $pdo = new PDO("mysql:host=localhost;dbname=inventaris_sekolah", "root", "");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmt = $pdo->prepare("
            SELECT 
                b.*,
                k.nama_kategori
            FROM barang b
            LEFT JOIN kategori k ON b.kategori_id = k.id
            WHERE b.id = ?
        ");
        $stmt->execute([$barang_id]);
        $barang = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($barang) {
            header('Content-Type: application/json');
            echo json_encode($barang);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Barang tidak ditemukan']);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Terjadi kesalahan sistem: ' . $e->getMessage()]);
    }
    exit();
}

$page_title = 'Data Barang';
require_once '../includes/header.php';
?>

<style>
    /* Mobile responsive for barang table */
    @media (max-width: 768px) {
        .table-responsive {
            font-size: 0.8rem;
        }

        .table td,
        .table th {
            padding: 0.5rem 0.25rem;
        }

        .btn-group .btn {
            padding: 0.2rem 0.4rem;
            font-size: 0.7rem;
        }

        .badge {
            font-size: 0.7rem;
            padding: 0.25rem 0.5rem;
        }

        .fw-semibold {
            font-size: 0.9rem;
        }

        .text-muted {
            font-size: 0.75rem;
        }
    }
</style>

<?php

// Handle actions
if ($action == 'add' || $action == 'edit') {
    include 'form.php';
    exit();
}

if ($action == 'delete' && isset($_GET['id'])) {
    $barang_id = (int)$_GET['id'];
    try {
        $pdo = new PDO("mysql:host=localhost;dbname=inventaris_sekolah", "root", "");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Ambil info barang untuk log
        $stmt = $pdo->prepare("SELECT nama_barang, foto FROM barang WHERE id = ?");
        $stmt->execute([$barang_id]);
        $barang = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($barang) {
            if ($barang['foto'] && file_exists('../../uploads/' . $barang['foto'])) {
                unlink('../../uploads/' . $barang['foto']);
            }

            $stmt = $pdo->prepare("DELETE FROM barang WHERE id = ?");
            $stmt->execute([$barang_id]);

            echo "<script>alert('Barang berhasil dihapus!'); window.location.href='index.php';</script>";
            exit();
        } else {
            echo "<script>alert('Barang tidak ditemukan!'); window.location.href='index.php';</script>";
            exit();
        }
    } catch (Exception $e) {
        echo "<script>alert('Gagal menghapus barang!'); window.location.href='index.php';</script>";
        exit();
    }
}

// Ambil data barang dengan pagination dan search
$search = isset($_GET['search']) ? validateInput($_GET['search']) : '';
$kategori_filter = isset($_GET['kategori']) ? (int)$_GET['kategori'] : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

try {
    $pdo = new PDO("mysql:host=localhost;dbname=inventaris_sekolah", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // build where (gunakan named params supaya binding lebih aman)
    $where_conditions = [];
    $params = [];

    if (!empty($search)) {
        $where_conditions[] = "(b.nama_barang LIKE :search OR b.deskripsi LIKE :search)";
        $params[':search'] = "%{$search}%";
    }

    if (!empty($kategori_filter)) {
        $where_conditions[] = "b.kategori_id = :kategori";
        $params[':kategori'] = $kategori_filter;
    }

    $where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

    // total count
    $count_query = "
        SELECT COUNT(*) as total
        FROM barang b
        LEFT JOIN kategori k ON b.kategori_id = k.id
        $where_clause
    ";
    $stmt = $pdo->prepare($count_query);
    $stmt->execute($params);
    $total_records = (int)$stmt->fetchColumn();
    $total_pages = $total_records ? ceil($total_records / $limit) : 0;

    // fetch list
    $query = "
        SELECT 
            b.*,
            k.nama_kategori
        FROM barang b
        LEFT JOIN kategori k ON b.kategori_id = k.id
        $where_clause
        ORDER BY b.created_at DESC
        LIMIT :limit OFFSET :offset
    ";
    $stmt = $pdo->prepare($query);

    // bind named params (search/kategori)
    foreach ($params as $key => $val) {
        $stmt->bindValue($key, $val);
    }

    // bind limit/offset sebagai integer
    $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);

    $stmt->execute();
    $barang_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Ambil kategori untuk filter
    $kategori_list = $pdo->query("SELECT * FROM kategori ORDER BY nama_kategori")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("[admin/barang/index.php] " . $e->getMessage());
    $barang_list = [];
    $total_pages = 0;
    $kategori_list = [];
    $total_records = 0;
}

$flash = getFlashMessage();
?>

<!-- Header Section -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h2 class="mb-2">
                            <i class="fas fa-boxes me-2"></i>Data Barang
                        </h2>
                        <p class="text-muted mb-0">Kelola semua data barang inventaris sekolah</p>
                    </div>
                    <div class="col-md-4 text-md-end">
                        <a href="?action=add" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Tambah Barang
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Flash Message -->
<?php if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show" role="alert">
        <i class="fas fa-<?= $flash['type'] == 'success' ? 'check-circle' : 'exclamation-triangle' ?> me-2"></i>
        <?= $flash['message'] ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Filter Section -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-filter me-2"></i>Filter Data</h5>
            </div>
            <div class="card-body">
                <form method="GET" action="" class="row g-3">
                    <div class="col-lg-4 col-md-6 col-12">
                        <label for="search" class="form-label">Cari Barang</label>
                        <input type="text" class="form-control" id="search" name="search"
                            value="<?= htmlspecialchars($search) ?>" placeholder="Nama atau deskripsi...">
                    </div>
                    <div class="col-lg-3 col-md-6 col-12">
                        <label for="kategori" class="form-label">Kategori</label>
                        <select class="form-select" id="kategori" name="kategori">
                            <option value="">Semua Kategori</option>
                            <?php foreach ($kategori_list as $kat): ?>
                                <option value="<?= $kat['id'] ?>" <?= $kategori_filter == $kat['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($kat['nama_kategori']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-lg-2 col-md-6 col-12">
                        <label class="form-label">&nbsp;</label>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search me-2"></i><span class="d-none d-md-inline">Cari</span>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Data Table -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <div class="row align-items-center">
                    <div class="col-md-6 col-12 mb-2 mb-md-0">
                        <h5 class="mb-0">
                            <i class="fas fa-list me-2"></i>Daftar Barang
                            <span class="badge bg-primary ms-2"><?= number_format($total_records ?? 0) ?> item</span>
                        </h5>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <?php if (empty($barang_list)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-box-open fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">Tidak ada data barang</h5>
                        <p class="text-muted">Mulai dengan menambahkan barang baru</p>
                        <a href="?action=add" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Tambah Barang Pertama
                        </a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th style="width:60px">No</th>
                                    <th>Nama Barang</th>
                                    <th class="d-none d-md-table-cell">Kategori</th>
                                    <th class="text-center">Baik</th>
                                    <th class="text-center">Sedang</th>
                                    <th class="text-center">Rusak</th>
                                    <th class="text-center d-none d-md-table-cell">Total</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $no = ($offset ?? 0) + 1; ?>
                                <?php foreach ($barang_list as $barang): ?>
                                    <?php
                                    $baik = (int)($barang['jumlah_baik'] ?? 0);
                                    $sedang = (int)($barang['jumlah_sedang'] ?? 0);
                                    $rusak = (int)($barang['jumlah_rusak'] ?? 0);
                                    $total_jml = $baik + $sedang + $rusak;
                                    ?>
                                    <tr>
                                        <td><?php echo $no++; ?></td>
                                        <td>
                                            <div class="d-flex align-items-center ">
                                                <?php if (!empty($barang['foto'])): ?>
                                                    <div class="me-2">
                                                        <img src="../../uploads/<?= htmlspecialchars($barang['foto']) ?>"
                                                            alt="<?= htmlspecialchars($barang['nama_barang']) ?>"
                                                            class="rounded" style="width: 40px; height: 40px; object-fit: cover;">
                                                    </div>
                                                <?php else: ?>
                                                    <div class="me-2 bg-light rounded d-flex align-items-center justify-content-center"
                                                        style="width: 40px; height: 40px;">
                                                        <i class="fas fa-box text-muted"></i>
                                                    </div>
                                                <?php endif; ?>
                                                <div>
                                                    <div class="fw-semibold"><?= htmlspecialchars($barang['nama_barang']) ?></div>
                                                    <small class="text-muted d-none d-md-inline"><?= htmlspecialchars($barang['deskripsi']) ?></small>
                                                    <div class="d-md-none">
                                                        <small class="text-muted"><?= htmlspecialchars($barang['nama_kategori']) ?></small>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="d-none d-md-table-cell">
                                            <span class="badge bg-info"><?= htmlspecialchars($barang['nama_kategori']) ?></span>
                                        </td>
                                        <td class="text-center"><?= $baik ?></td>
                                        <td class="text-center"><?= $sedang ?></td>
                                        <td class="text-center"><?= $rusak ?></td>
                                        <td class="text-center d-none d-md-table-cell"><?= $total_jml ?></td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <button type="button" onclick="showDetail(<?= $barang['id'] ?>)"
                                                    class="btn btn-sm btn-outline-primary" title="Lihat Detail">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <a href="?action=edit&id=<?= $barang['id'] ?>"
                                                    class="btn btn-sm btn-outline-warning" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <button type="button" class="btn btn-sm btn-outline-danger"
                                                    onclick="showDeleteModal(<?= $barang['id'] ?>, '<?= htmlspecialchars($barang['nama_barang']) ?>')"
                                                    title="Hapus">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <nav aria-label="Page navigation" class="mt-4">
                            <ul class="pagination justify-content-center">
                                <?php if ($page > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&kategori=<?= $kategori_filter ?>">
                                            <i class="fas fa-chevron-left"></i>
                                        </a>
                                    </li>
                                <?php endif; ?>

                                <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                    <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                        <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&kategori=<?= $kategori_filter ?>">
                                            <?= $i ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>

                                <?php if ($page < $total_pages): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&kategori=<?= $kategori_filter ?>">
                                            <i class="fas fa-chevron-right"></i>
                                        </a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Modal Detail Barang -->
<div class="modal fade" id="detailModal" tabindex="-1" aria-labelledby="detailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="detailModalLabel">
                    <i class="fas fa-box me-2"></i>Detail Barang
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="detailModalBody">
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Memuat detail barang...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>Tutup
                </button>
                <button type="button" class="btn btn-primary" id="editBarangBtn" style="display: none;">
                    <i class="fas fa-edit me-2"></i>Edit Barang
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Konfirmasi Hapus -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteModalLabel">
                    <i class="fas fa-exclamation-triangle me-2"></i>Konfirmasi Hapus
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-4">
                    <i class="fas fa-trash-alt fa-4x text-danger mb-3"></i>
                    <h5>Hapus Barang</h5>
                    <p class="text-muted">Apakah Anda yakin ingin menghapus barang ini?</p>
                </div>
                <div class="alert alert-warning" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Peringatan:</strong> Tindakan ini tidak dapat dibatalkan dan foto barang juga akan dihapus secara permanen.
                </div>
                <div id="deleteItemInfo" class="text-center"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>Batal
                </button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">
                    <i class="fas fa-trash me-2"></i>Ya, Hapus Barang
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    // Auto-submit form on filter change
    document.getElementById('kategori').addEventListener('change', function() {
        this.form.submit();
    });

    // Detail popup function (menampilkan jumlah per kondisi)
    function showDetail(id) {
        const modal = new bootstrap.Modal(document.getElementById('detailModal'));
        modal.show();
        document.getElementById('detailModalBody').innerHTML = `
            <div class="text-center py-4">
                <div class="spinner-border text-primary" role="status"></div>
                <p class="mt-2">Memuat detail barang...</p>
            </div>`;

        fetch(`?action=get_detail&id=${id}`)
            .then(res => res.json())
            .then(data => {
                if (data.error) {
                    document.getElementById('detailModalBody').innerHTML = `<div class="text-center py-4"><i class="fas fa-exclamation-triangle fa-3x text-warning"></i><p class="mt-2">${data.error}</p></div>`;
                    return;
                }
                const foto = data.foto ? `<img src="../../uploads/${data.foto}" class="img-fluid rounded" style="max-height:300px;object-fit:cover;">` :
                    `<div class='d-flex flex-column align-items-center justify-content-center' style='height:200px;'><i class='fas fa-box-open fa-5x text-secondary mb-2'></i><div class='text-muted'>Tidak ada foto</div></div>`;

                const baik = Number(data.jumlah_baik || 0);
                const sedang = Number(data.jumlah_sedang || 0);
                const rusak = Number(data.jumlah_rusak || 0);
                const total = baik + sedang + rusak;

                document.getElementById('detailModalBody').innerHTML = `
                    <div class="row">
                        <div class="col-md-4 text-center">${foto}</div>
                        <div class="col-md-8">
                            <h5 class="fw-bold">${data.nama_barang || '-'}</h5>
                            <p class="mb-1"><strong>Kategori:</strong> ${data.nama_kategori || '-'}</p>
                            <div class="row mb-2">
                                <div class="col-4"><strong>Baik</strong><div>${baik}</div></div>
                                <div class="col-4"><strong>Sedang</strong><div>${sedang}</div></div>
                                <div class="col-4"><strong>Rusak</strong><div>${rusak}</div></div>
                            </div>
                            <p class="mb-1"><strong>Total:</strong> ${total}</p>
                            <hr>
                            <h6 class="text-muted">Deskripsi</h6>
                            <p>${data.deskripsi || '-'}</p>
                        </div>
                    </div>
                `;
                const editBtn = document.getElementById('editBarangBtn');
                editBtn.style.display = 'inline-block';
                editBtn.onclick = () => {
                    window.location.href = `?action=edit&id=${data.id}`;
                };
            })
            .catch(() => {
                document.getElementById('detailModalBody').innerHTML = `<div class="text-center py-4"><i class="fas fa-exclamation-triangle fa-3x text-danger"></i><p class="mt-2">Terjadi kesalahan saat memuat data</p></div>`;
            });
    }

    function showDeleteModal(id, namaBarang) {
        document.getElementById('deleteItemInfo').innerHTML = `
        <div class="card border-danger">
            <div class="card-body">
                <h6 class="card-title text-danger"><i class="fas fa-box me-2"></i>${namaBarang}</h6>
                <p class="card-text mb-0"><strong>ID:</strong> <span class="text-muted">#${id}</span></p>
            </div>
        </div>`;
        document.getElementById('confirmDeleteBtn').onclick = function() {
            window.location.href = `?action=delete&id=${id}`;
        };
        const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
        deleteModal.show();
    }
</script>

<?php require_once '../includes/footer.php'; ?>