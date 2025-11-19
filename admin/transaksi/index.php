<?php

/**
 * Data Transaksi - Admin Panel
 * Sistem Inventaris Sekolah
 */

// Handle AJAX request untuk detail FIRST (before any output)
$action = $_GET['action'] ?? '';
if ($action == 'get_detail' && isset($_GET['id'])) {
    $transaksi_id = (int)$_GET['id'];
    try {
        $pdo = new PDO("mysql:host=localhost;dbname=inventaris_sekolah", "root", "");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmt = $pdo->prepare("
            SELECT 
                t.*,
                b.nama_barang,
                b.foto,
                k.nama_kategori
            FROM transaksi t
            LEFT JOIN barang b ON t.barang_id = b.id
            LEFT JOIN kategori k ON b.kategori_id = k.id
            WHERE t.id = ?
        ");
        $stmt->execute([$transaksi_id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($data) {
            header('Content-Type: application/json');
            echo json_encode($data);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Transaksi tidak ditemukan']);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Terjadi kesalahan sistem: ' . $e->getMessage()]);
    }
    exit();
}

$page_title = 'Data Transaksi';
require_once '../includes/header.php';
?>

<style>
    /* Responsive tweaks kept from barang index */
    @media (max-width: 768px) {
        .table-responsive {
            font-size: 0.85rem;
        }

        .table td,
        .table th {
            padding: 0.45rem 0.4rem;
        }

        .btn-group .btn {
            padding: 0.25rem 0.45rem;
            font-size: 0.75rem;
        }
    }
</style>

<?php

// If add/edit -> include form
if ($action == 'add' || $action == 'edit') {
    include 'form.php';
    exit();
}

// Delete transaksi
if ($action == 'delete' && isset($_GET['id'])) {
    $tid = (int)$_GET['id'];
    try {
        $pdo = new PDO("mysql:host=localhost;dbname=inventaris_sekolah", "root", "");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmt = $pdo->prepare("SELECT id FROM transaksi WHERE id = ?");
        $stmt->execute([$tid]);
        if ($stmt->fetch()) {
            $stmt = $pdo->prepare("DELETE FROM transaksi WHERE id = ?");
            $stmt->execute([$tid]);
            echo "<script>alert('Transaksi berhasil dihapus!'); window.location.href='index.php';</script>";
            exit();
        } else {
            echo "<script>alert('Transaksi tidak ditemukan'); window.location.href='index.php';</script>";
            exit();
        }
    } catch (Exception $e) {
        echo "<script>alert('Gagal menghapus transaksi'); window.location.href='index.php';</script>";
        exit();
    }
}

// Filters / pagination
$search = isset($_GET['search']) ? validateInput($_GET['search']) : '';
$barang_filter = isset($_GET['barang']) ? (int)$_GET['barang'] : 0;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

try {
    $pdo = new PDO("mysql:host=localhost;dbname=inventaris_sekolah", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // build where (gunakan named params)
    $where = [];
    $params = [];

    if ($search !== '') {
        $where[] = "(b.nama_barang LIKE :search OR t.deskripsi LIKE :search)";
        $params[':search'] = "%{$search}%";
    }

    if ($barang_filter > 0) {
        $where[] = "t.barang_id = :barang";
        $params[':barang'] = $barang_filter;
    }

    $where_clause = $where ? "WHERE " . implode(' AND ', $where) : "";

    // total count
    $countSql = "
        SELECT COUNT(*) as total
        FROM transaksi t
        LEFT JOIN barang b ON t.barang_id = b.id
        $where_clause
    ";
    $stmt = $pdo->prepare($countSql);
    $stmt->execute($params);
    $total_records = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];
    $total_pages = $total_records ? ceil($total_records / $limit) : 0;

    // fetch list (pakai same named params, lalu bind limit/offset)
    $query = "
        SELECT
            t.*,
            b.nama_barang,
            b.foto,
            k.nama_kategori
        FROM transaksi t
        LEFT JOIN barang b ON t.barang_id = b.id
        LEFT JOIN kategori k ON b.kategori_id = k.id
        $where_clause
        ORDER BY t.created_at DESC
        LIMIT :limit OFFSET :offset
    ";
    $stmt = $pdo->prepare($query);
    // bind named params untuk search/filter
    foreach ($params as $key => $val) {
        $stmt->bindValue($key, $val);
    }
    $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
    $stmt->execute();
    $transaksi_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // barang list for filter/select
    $barang_list = $pdo->query("SELECT id, nama_barang FROM barang ORDER BY nama_barang")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $transaksi_list = [];
    $total_pages = 0;
    $barang_list = [];
}

$kondisi_labels = [
    'baik' => 'Baik',
    'rusak_ringan' => 'Rusak Ringan',
    'rusak_berat' => 'Rusak Berat'
];

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
                        <p class="text-muted mb-0">Kelola semua data transaksi inventaris sekolah</p>
                    </div>
                    <div class="col-md-4 text-md-end">
                        <a href="?action=add" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Tambah Transaksi
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filter Section -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-filter me-2"></i>Filter Data</h5>
            </div>
            <div class="card-body">
                <form method="GET" class="row g-2">
                    <input type="hidden" name="action" value="">
                    <div class="col-md-4">
                        <input type="text" name="search" id="search" class="form-control" placeholder="Cari nama barang atau deskripsi..." value="<?= htmlspecialchars($search) ?>">
                    </div>
                    <div class="col-md-3">
                        <select name="barang" id="barang" class="form-select">
                            <option value="0">Semua Barang</option>
                            <?php foreach ($barang_list as $b): ?>
                                <option value="<?= $b['id'] ?>" <?= $barang_filter == $b['id'] ? 'selected' : '' ?>><?= htmlspecialchars($b['nama_barang']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-primary w-100"><i class="fas fa-search me-1"></i>Cari</button>
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
            <div class="card-body">
                <?php if (empty($transaksi_list)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-exchange-alt fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">Tidak ada data transaksi</h5>
                        <p class="text-muted">Tambahkan transaksi baru</p>
                        <a href="?action=add" class="btn btn-primary"><i class="fas fa-plus me-1"></i>Tambah Transaksi</a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle text-center">
                            <thead class="table-light">
                                <tr>
                                    <th style="width:60px">No</th>
                                    <th>Barang</th>
                                    <th>Kondisi</th>
                                    <th>Jumlah</th>
                                    <th>Harga Satuan</th>
                                    <th>Total</th>
                                    <th class="d-none d-md-table-cell">Tahun</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $no = ($offset ?? 0) + 1; ?>
                                <?php foreach ($transaksi_list as $t): ?>
                                    <tr>
                                        <td><?= $no++ ?></td>
                                        <td class="text-start">
                                            <div class="d-flex align-items-center">
                                                <?php if (!empty($t['foto'])): ?>
                                                    <img src="../../uploads/<?= htmlspecialchars($t['foto']) ?>" alt="<?= htmlspecialchars($t['nama_barang']) ?>" class="rounded me-2" style="width:48px;height:48px;object-fit:cover;">
                                                <?php else: ?>
                                                    <div class="bg-light rounded me-2 d-flex align-items-center justify-content-center" style="width:48px;height:48px;"><i class="fas fa-box text-muted"></i></div>
                                                <?php endif; ?>
                                                <div class="text-start">
                                                    <div class="fw-semibold"><?= htmlspecialchars($t['nama_barang'] ?? '-') ?></div>
                                                    <small class="text-muted d-none d-md-inline"><?= htmlspecialchars(mb_strimwidth($t['deskripsi'] ?? '-', 0, 80, '...')) ?></small>
                                                    <div class="d-md-none"><small class="text-muted"><?= htmlspecialchars($t['nama_kategori'] ?? '-') ?></small></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?= htmlspecialchars($kondisi_labels[$t['kondisi']] ?? '-') ?></td>
                                        <td><?= htmlspecialchars((int)$t['jumlah']) ?></td>
                                        <td>Rp <?= number_format((float)$t['harga_satuan'], 2, ',', '.') ?></td>
                                        <td>Rp <?= number_format((float)$t['total'], 2, ',', '.') ?></td>
                                        <td class="d-none d-md-table-cell"><?= htmlspecialchars($t['tahun_pengadaan'] ?? '-') ?></td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <button type="button" class="btn btn-sm btn-outline-primary" onclick="showDetail(<?= $t['id'] ?>)">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <a href="?action=edit&id=<?= $t['id'] ?>" class="btn btn-sm btn-outline-warning">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <button type="button" class="btn btn-sm btn-outline-danger" onclick="showDeleteModal(<?= $t['id'] ?>, '<?= htmlspecialchars(addslashes($t['nama_barang'] ?? 'Transaksi')) ?>')">
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
                        <nav aria-label="Page navigation" class="mt-3">
                            <ul class="pagination justify-content-center">
                                <?php if ($page > 1): ?>
                                    <li class="page-item"><a class="page-link" href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&barang=<?= $barang_filter ?>"><i class="fas fa-chevron-left"></i></a></li>
                                <?php endif; ?>

                                <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                    <li class="page-item <?= $i == $page ? 'active' : '' ?>"><a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&barang=<?= $barang_filter ?>"><?= $i ?></a></li>
                                <?php endfor; ?>

                                <?php if ($page < $total_pages): ?>
                                    <li class="page-item"><a class="page-link" href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&barang=<?= $barang_filter ?>"><i class="fas fa-chevron-right"></i></a></li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Detail Modal -->
<div class="modal fade" id="detailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-info-circle me-2"></i>Detail Transaksi</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="detailModalBody">
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status"></div>
                    <p class="mt-2">Memuat...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                <button type="button" class="btn btn-primary" id="editTransaksiBtn" style="display:none;">Edit</button>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirm Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="fas fa-exclamation-triangle me-2"></i>Konfirmasi Hapus</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <p id="deleteItemInfo"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Ya, Hapus</button>
            </div>
        </div>
    </div>
</div>

<script>
    const kondisiMap = {
        'baik': 'Baik',
        'rusak_ringan': 'Rusak Ringan',
        'rusak_berat': 'Rusak Berat'
    };

    function showDetail(id) {
        const modalEl = document.getElementById('detailModal');
        const modal = new bootstrap.Modal(modalEl);
        document.getElementById('detailModalBody').innerHTML = `<div class="text-center py-4"><div class="spinner-border text-primary" role="status"></div><p class="mt-2">Memuat...</p></div>`;
        modal.show();

        fetch(`?action=get_detail&id=${id}`)
            .then(r => r.json())
            .then(data => {
                if (data.error) {
                    document.getElementById('detailModalBody').innerHTML = `<div class="text-center py-4"><i class="fas fa-exclamation-triangle fa-3x text-warning"></i><p class="mt-2">${data.error}</p></div>`;
                    return;
                }
                const foto = data.foto ? `<img src="../../uploads/${data.foto}" class="img-fluid rounded" style="max-height:220px;object-fit:cover;">` :
                    `<div class="text-center py-4"><i class="fas fa-box-open fa-4x text-secondary"></i><div class="text-muted">Tidak ada foto</div></div>`;
                document.getElementById('detailModalBody').innerHTML = `
                <div class="row">
                    <div class="col-md-4 text-center">${foto}</div>
                    <div class="col-md-8">
                        <h5 class="fw-bold">${data.nama_barang || '-'}</h5>
                        <p class="mb-1"><strong>Kategori:</strong> ${data.nama_kategori || '-'}</p>
                        <p class="mb-1"><strong>Kondisi:</strong> ${kondisiMap[data.kondisi] || '-'}</p>
                        <p class="mb-1"><strong>Jumlah:</strong> ${data.jumlah || 0}</p>
                        <p class="mb-1"><strong>Harga Satuan:</strong> Rp ${Number(data.harga_satuan || 0).toLocaleString('id-ID', {minimumFractionDigits:2})}</p>
                        <p class="mb-1"><strong>Total:</strong> Rp ${Number(data.total || 0).toLocaleString('id-ID', {minimumFractionDigits:2})}</p>
                        <p class="mb-1"><strong>Tahun Pengadaan:</strong> ${data.tahun_pengadaan || '-'}</p>
                        <hr>
                        <h6 class="text-muted">Deskripsi</h6>
                        <p>${data.deskripsi || '-'}</p>
                    </div>
                </div>
            `;
                const editBtn = document.getElementById('editTransaksiBtn');
                editBtn.style.display = 'inline-block';
                editBtn.onclick = () => {
                    window.location.href = `?action=edit&id=${data.id}`;
                };
            })
            .catch(() => {
                document.getElementById('detailModalBody').innerHTML = `<div class="text-center py-4"><i class="fas fa-exclamation-triangle fa-3x text-danger"></i><p class="mt-2">Terjadi kesalahan saat memuat data</p></div>`;
            });
    }

    function showDeleteModal(id, name) {
        document.getElementById('deleteItemInfo').innerHTML = `<p>Hapus transaksi untuk <strong>${name}</strong> (ID: ${id})?</p>`;
        document.getElementById('confirmDeleteBtn').onclick = function() {
            window.location.href = `?action=delete&id=${id}`;
        };
        const delModal = new bootstrap.Modal(document.getElementById('deleteModal'));
        delModal.show();
    }
</script>

<?php require_once '../includes/footer.php'; ?>
?>