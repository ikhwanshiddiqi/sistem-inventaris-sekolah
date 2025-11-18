<?php

/**
 * Dashboard Admin - Sistem Inventaris Sekolah
 */

$page_title = 'Dashboard Admin';
require_once 'includes/header.php';

// Ambil statistik dashboard
try {
    $pdo = new PDO("mysql:host=localhost;dbname=inventaris_sekolah", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Get dashboard stats
    $stmt = $pdo->query("SELECT COUNT(*) as total_barang FROM barang");
    $total_barang = $stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COUNT(*) as total_kategori FROM kategori");
    $total_kategori = $stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COUNT(*) as barang_rusak FROM barang WHERE kondisi IN ('rusak_ringan', 'rusak_berat')");
    $barang_rusak = $stmt->fetchColumn();

    $stats = [
        'total_barang' => $total_barang,
        'total_kategori' => $total_kategori,
        'barang_rusak' => $barang_rusak
    ];

    // Data barang per kategori
    $stmt = $pdo->query("
        SELECT 
            k.nama_kategori,
            COUNT(b.id) as total_barang
        FROM kategori k
        LEFT JOIN barang b ON k.id = b.kategori_id
        GROUP BY k.id, k.nama_kategori
        ORDER BY total_barang DESC
    ");
    $kategori_chart = $stmt->fetchAll();
} catch (Exception $e) {
    $kategori_chart = [];
}
?>

<!-- Welcome Section -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h2 class="mb-2">Selamat Datang, <?= $user_name ?>! 👋</h2>
                        <p class="text-muted mb-0">Kelola inventaris sekolah dengan mudah dan efisien</p>
                    </div>
                    <div class="col-md-4 text-md-end">
                        <div class="d-flex gap-2 justify-content-md-end">
                            <a href="barang/?action=add" class="btn btn-primary">
                                <i class="fas fa-plus me-2"></i>Tambah Barang
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Statistik Cards -->
<div class="row mb-4">
    <div class="col-xl-3 col-lg-6 col-md-6 mb-4">
        <div class="stat-card">
            <div class="stat-icon primary">
                <i class="fas fa-boxes"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number"><?= number_format($stats['total_barang']) ?></div>
                <div class="stat-label">Total Barang</div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-lg-6 col-md-6 mb-4">
        <div class="stat-card">
            <div class="stat-icon warning">
                <i class="fas fa-tags"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number"><?= number_format($stats['total_kategori']) ?></div>
                <div class="stat-label">Kategori Barang</div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-lg-6 col-md-6 mb-4">
        <div class="stat-card">
            <div class="stat-icon danger">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number"><?= number_format($stats['barang_rusak']) ?></div>
                <div class="stat-label">Barang Rusak</div>
            </div>
        </div>
    </div>
</div>

<!-- Charts Row -->
<div class="row mb-4">
    <div class="col-xl-4 col-lg-12 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-chart-pie me-2"></i>Barang per Kategori</h5>
            </div>
            <div class="card-body">
                <div style="position: relative; height: 300px;">
                    <canvas id="kategoriChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-bolt me-2"></i>Aksi Cepat</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <a href="barang/?action=add" class="btn btn-primary w-100">
                            <i class="fas fa-plus me-2"></i>Tambah Barang
                        </a>
                    </div>
                    <div class="col-md-3 mb-3">
                        <a href="peminjaman/" class="btn btn-success w-100">
                            <i class="fas fa-handshake me-2"></i>Peminjaman
                        </a>
                    </div>
                    <div class="col-md-3 mb-3">
                        <a href="laporan/" class="btn btn-info w-100">
                            <i class="fas fa-chart-bar me-2"></i>Laporan
                        </a>
                    </div>
                    <div class="col-md-3 mb-3">
                        <a href="user/" class="btn btn-warning w-100">
                            <i class="fas fa-user-plus me-2"></i>Kelola User
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Chart untuk barang per kategori
    const kategoriCtx = document.getElementById('kategoriChart').getContext('2d');
    const kategoriChart = new Chart(kategoriCtx, {
        type: 'doughnut',
        data: {
            labels: <?= json_encode(array_column($kategori_chart, 'nama_kategori')) ?>,
            datasets: [{
                data: <?= json_encode(array_column($kategori_chart, 'total_barang')) ?>,
                backgroundColor: [
                    '#4f46e5',
                    '#7c3aed',
                    '#10b981',
                    '#f59e0b',
                    '#ef4444',
                    '#8b5cf6'
                ],
                borderWidth: 0,
                hoverOffset: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 20,
                        usePointStyle: true
                    }
                }
            }
        }
    });
</script>

<?php require_once 'includes/footer.php'; ?>