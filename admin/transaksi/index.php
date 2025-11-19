<?php

/**
 * Data Transaksi - Admin Panel (disesuaikan dengan struktur transaksi baru)
 */

$action = $_GET['action'] ?? '';
if ($action == 'get_detail' && isset($_GET['id'])) {
    $transaksi_id = (int)$_GET['id'];
    try {
        $pdo = new PDO("mysql:host=localhost;dbname=inventaris_sekolah", "root", "");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmt = $pdo->prepare("
            SELECT t.*, k.nama_kategori
            FROM transaksi t
            LEFT JOIN kategori k ON t.kategori_id = k.id
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
// include form for add/edit
if ($action == 'add' || $action == 'edit') {
    include 'form.php';
    exit();
}

// delete transaksi
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

// filters / pagination
$search = isset($_GET['search']) ? validateInput($_GET['search']) : '';
$kategori_filter = isset($_GET['kategori']) ? (int)$_GET['kategori'] : 0;
$tahun_filter = isset($_GET['tahun']) ? (int)$_GET['tahun'] : 0;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

try {
    $pdo = new PDO("mysql:host=localhost;dbname=inventaris_sekolah", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // build where with named params
    $where = [];
    $params = [];

    if ($search !== '') {
        $where[] = "(t.nama_barang LIKE :search OR t.deskripsi LIKE :search OR t.bahan LIKE :search OR t.asal LIKE :search OR k.nama_kategori LIKE :search)";
        $params[':search'] = "%{$search}%";
    }

    if ($kategori_filter > 0) {
        $where[] = "t.kategori_id = :kategori";
        $params[':kategori'] = $kategori_filter;
    }

    if ($tahun_filter > 0) {
        $where[] = "t.tahun_pengadaan = :tahun";
        $params[':tahun'] = $tahun_filter;
    }

    $where_clause = $where ? "WHERE " . implode(" AND ", $where) : "";

    // total count
    $countSql = "
        SELECT COUNT(*) AS total
        FROM transaksi t
        LEFT JOIN kategori k ON t.kategori_id = k.id
        $where_clause
    ";
    $stmt = $pdo->prepare($countSql);
    $stmt->execute($params);
    $total_records = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];
    $total_pages = $total_records ? ceil($total_records / $limit) : 0;

    // fetch list
    $query = "
        SELECT t.*, k.nama_kategori
        FROM transaksi t
        LEFT JOIN kategori k ON t.kategori_id = k.id
        $where_clause
        ORDER BY t.created_at DESC
        LIMIT :limit OFFSET :offset
    ";
    $stmt = $pdo->prepare($query);
    // bind named params
    foreach ($params as $key => $val) {
        $stmt->bindValue($key, $val);
    }
    $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
    $stmt->execute();
    $transaksi_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // kategori for filter
    $kategori_list = $pdo->query("SELECT id, nama_kategori FROM kategori ORDER BY nama_kategori")->fetchAll(PDO::FETCH_ASSOC);

    // tahun pengadaan for filter
    $tahun_list = $pdo->query("SELECT DISTINCT tahun_pengadaan FROM transaksi WHERE tahun_pengadaan IS NOT NULL AND tahun_pengadaan != '' ORDER BY tahun_pengadaan DESC")->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) {
    error_log("[admin/transaksi/index.php] " . $e->getMessage());
    $transaksi_list = [];
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
                            <i class="fas fa-exchange-alt me-2"></i>Data Transaksi
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
                    <div class="col-lg-3 col-md-6 col-12">
                        <label for="tahun" class="form-label">Tahun Pengadaan</label>
                        <select class="form-select" id="tahun" name="tahun">
                            <option value="">Semua Tahun</option>
                            <?php foreach ($tahun_list as $th): ?>
                                <option value="<?= $th ?>" <?= $tahun_filter == $th ? 'selected' : '' ?>>
                                    <?= $th ?>
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

<!-- Table -->
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
                        <table class="table table-hover align-middle">
                            <thead class="table-light text-center">
                                <tr>
                                    <th style="width:60px">No</th>
                                    <th class="text-start">Nama Barang</th>
                                    <th>Bahan</th>
                                    <th>Asal</th>
                                    <th>Jumlah</th>
                                    <th>Harga Satuan</th>
                                    <th>Total</th>
                                    <th class="d-none d-md-table-cell">Tahun</th>
                                    <th class="d-none d-md-table-cell">Kategori</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $no = ($offset ?? 0) + 1; ?>
                                <?php foreach ($transaksi_list as $t): ?>
                                    <tr>
                                        <td class="text-center"><?= $no++ ?></td>
                                        <td><?= htmlspecialchars($t['nama_barang'] ?? '-') ?></td>
                                        <td class="text-center"><?= htmlspecialchars($t['bahan'] ?? '-') ?></td>
                                        <td class="text-center"><?= htmlspecialchars($t['asal'] ?? '-') ?></td>
                                        <td class="text-center"><?= (int)($t['jumlah'] ?? 0) ?></td>
                                        <td class="text-end">Rp <?= number_format((float)($t['harga_satuan'] ?? 0), 2, ',', '.') ?></td>
                                        <td class="text-end">Rp <?= number_format((float)($t['total'] ?? 0), 2, ',', '.') ?></td>
                                        <td class="text-center d-none d-md-table-cell"><?= htmlspecialchars($t['tahun_pengadaan'] ?? '-') ?></td>
                                        <td class="text-center d-none d-md-table-cell"><?= htmlspecialchars($t['nama_kategori'] ?? '-') ?></td>
                                        <td class="text-center">
                                            <div class="btn-group" role="group">
                                                <button type="button" class="btn btn-sm btn-outline-primary" onclick="showDetail(<?= $t['id'] ?>)"><i class="fas fa-eye"></i></button>
                                                <a href="?action=edit&id=<?= $t['id'] ?>" class="btn btn-sm btn-outline-warning"><i class="fas fa-edit"></i></a>
                                                <button type="button" class="btn btn-sm btn-outline-danger" onclick="showDeleteModal(<?= $t['id'] ?>, '<?= htmlspecialchars(addslashes($t['nama_barang'] ?? 'Transaksi')) ?>')"><i class="fas fa-trash"></i></button>
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
                                    <li class="page-item"><a class="page-link" href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&kategori=<?= $kategori_filter ?>"><i class="fas fa-chevron-left"></i></a></li>
                                <?php endif; ?>

                                <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                    <li class="page-item <?= $i == $page ? 'active' : '' ?>"><a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&kategori=<?= $kategori_filter ?>"><?= $i ?></a></li>
                                <?php endfor; ?>

                                <?php if ($page < $total_pages): ?>
                                    <li class="page-item"><a class="page-link" href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&kategori=<?= $kategori_filter ?>"><i class="fas fa-chevron-right"></i></a></li>
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

<!-- Delete Modal -->
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

                document.getElementById('detailModalBody').innerHTML = `
                <div class="row">
                    <div class="col-12">
                        <h5 class="fw-bold">${data.nama_barang || '-'}</h5>
                        <p class="mb-1"><strong>Kategori:</strong> ${data.nama_kategori || '-'}</p>
                        <p class="mb-1"><strong>Bahan:</strong> ${data.bahan || '-'}</p>
                        <p class="mb-1"><strong>Asal:</strong> ${data.asal || '-'}</p>
                        <p class="mb-1"><strong>Jumlah:</strong> ${Number(data.jumlah || 0)}</p>
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