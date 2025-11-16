<?php
// sesuaikan path koneksi dan header
include '../../config/koneksi.php';
include '../layout/header.php';

// pastikan koneksi aktif
if (!isset($conn)) {
    die("Koneksi gagal: variabel \$conn tidak ditemukan.");
}

// ambil kata pencarian
$cari = isset($_GET['cari']) ? trim($_GET['cari']) : '';

?>

<div class="container mt-4">
    <h2>Data Kategori Barang</h2>

    <form method="GET" action="" class="form-inline mb-3">
        <input type="text" name="cari" class="form-control mr-2" placeholder="Cari kategori..." 
               value="<?php echo htmlspecialchars($cari); ?>">
        <button type="submit" class="btn btn-primary">Cari</button>
        <a href="index.php" class="btn btn-secondary ml-2">Reset</a>
    </form>

    <table class="table table-bordered table-striped">
        <thead class="thead-dark">
            <tr>
                <th>No</th>
                <th>Nama Kategori</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $no = 1;

            // perbaiki query pencarian
            if ($cari != '') {
                $safe = mysqli_real_escape_string($conn, $cari);
                $query = "SELECT * FROM kategori WHERE nama_kategori LIKE '%$safe%' ORDER BY nama_kategori ASC";
            } else {
                $query = "SELECT * FROM kategori ORDER BY nama_kategori ASC";
            }

            $result = mysqli_query($conn, $query);

            if (!$result) {
                echo "<tr><td colspan='3'>Query Error: " . mysqli_error($conn) . "</td></tr>";
            } else {
                if (mysqli_num_rows($result) > 0) {
                    while ($data = mysqli_fetch_assoc($result)) {
                        echo "<tr>
                                <td>{$no}</td>
                                <td>{$data['nama_kategori']}</td>
                                <td>
                                    <a href='edit.php?id={$data['id_kategori']}' class='btn btn-warning btn-sm'>Edit</a>
                                    <a href='hapus.php?id={$data['id_kategori']}' 
                                       onclick='return confirm(\"Yakin mau hapus?\");' 
                                       class='btn btn-danger btn-sm'>Hapus</a>
                                </td>
                              </tr>";
                        $no++;
                    }
                } else {
                    echo "<tr><td colspan='3' class='text-center text-muted'>Data tidak ditemukan.</td></tr>";
                }
            }
            ?>
        </tbody>
    </table>
</div>

<?php include '../layout/footer.php'; ?>
