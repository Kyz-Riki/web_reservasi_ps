<?php
session_start();
include('../config/db.php');
if ($_SESSION['role'] != 'customer') {
    header('Location: ../auth/login.php');
    exit;
}
$user_id = $_SESSION['user_id'];
// Proses sewa
if (isset($_POST['pesan'])) {
    $varian_id = $_POST['varian_id'];
    $tanggal_sewa = $_POST['tanggal_sewa'];
    $jam_datang = $_POST['jam_datang'];
    $durasi_sewa = $_POST['durasi_sewa'];
    $catatan = htmlspecialchars($_POST['catatan']);
    // Cek ketersediaan PS untuk sewa (tidak sedang disewa di periode tsb)
    $check_sewa = mysqli_query($conn, "SELECT * FROM pesanan WHERE varian_id = '$varian_id' AND jenis_pesanan = 'bawa_pulang' AND status = 'Disetujui' AND (DATE_ADD(tanggal_bermain, INTERVAL hari DAY) > '$tanggal_sewa' OR tanggal_bermain = '$tanggal_sewa')");
    if (mysqli_num_rows($check_sewa) > 0) {
        $_SESSION['error'] = "Maaf, PlayStation ini sedang disewa di periode tersebut. Silakan pilih varian atau tanggal lain.";
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
    $query = "INSERT INTO pesanan (user_id, varian_id, jenis_pesanan, catatan, tanggal_bermain, jam_datang, hari) VALUES ('$user_id', '$varian_id', 'bawa_pulang', '$catatan', '$tanggal_sewa', '$jam_datang', '$durasi_sewa')";
    mysqli_query($conn, $query);
    $_SESSION['success'] = "Sewa berhasil dikirim!";
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}
// Ambil varian PS dengan perhitungan stok yang tersedia
$varian = mysqli_query($conn, "SELECT v.*, 
    (v.stok_sewa - COALESCE(
        (SELECT COUNT(*) FROM pesanan p 
         WHERE p.varian_id = v.id 
         AND p.jenis_pesanan = 'bawa_pulang'
         AND p.status = 'Disetujui'), 0
    )) as sisa_stok_sewa
    FROM varian_ps v 
    WHERE v.tersedia = 1 
    AND v.is_deleted = 0");
// Riwayat sewa user
$pesanan = mysqli_query($conn, "SELECT p.*, v.nama_varian FROM pesanan p JOIN varian_ps v ON p.varian_id = v.id WHERE p.user_id = $user_id AND p.jenis_pesanan = 'bawa_pulang' ORDER BY p.waktu_pesanan DESC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sewa Bawa Pulang</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="sewa_bawa_pulang.css">
</head>
<body>
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-7 col-md-9">
            <a href="booking.php" class="btn btn-outline-secondary mb-3"><i class="fas fa-arrow-left me-1"></i> Kembali</a>
            <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                <i class="fas fa-check-circle me-2"></i> <?= $_SESSION['success']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['success']); endif; ?>
            <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i> <?= $_SESSION['error']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['error']); endif; ?>
            <div class="card">
                <div class="card-header py-3">
                    <h5 class="mb-0"><i class="fas fa-boxes ps-icon"></i> Sewa Bawa Pulang</h5>
                </div>
                <div class="card-body p-4">
                    <form method="POST" id="sewaForm">
                        <div class="mb-3">
                            <label for="tanggal_sewa" class="form-label">Tanggal Sewa (Mulai)</label>
                            <input type="date" class="form-control" id="tanggal_sewa" name="tanggal_sewa" min="<?= date('Y-m-d'); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="jam_datang" class="form-label">Jam Datang Ke PS</label>
                            <select class="form-select" id="jam_datang" name="jam_datang" required>
                                <?php for ($i = 8; $i <= 22; $i++) { $jam = sprintf("%02d:00", $i); echo "<option value='$jam'>$jam</option>"; } ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="durasi_sewa" class="form-label">Durasi Sewa (Hari)</label>
                            <select class="form-select" id="durasi_sewa" name="durasi_sewa" required>
                                <?php for ($i = 1; $i <= 7; $i++) { echo "<option value='$i'>$i Hari</option>"; } ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Varian PlayStation</label>
                            <div class="varian-grid">
                                <?php 
                                mysqli_data_seek($varian, 0);
                                while($v = mysqli_fetch_assoc($varian)) { 
                                    // Tentukan class berdasarkan jumlah stok sewa
                                    $stockClass = '';
                                    $stokSewa = (int)$v['sisa_stok_sewa'];
                                    if ($stokSewa > 2) {
                                        $stockClass = 'stock-high';
                                    } elseif ($stokSewa > 0) {
                                        $stockClass = 'stock-medium';
                                    } else {
                                        $stockClass = 'stock-low';
                                    }
                                ?>
                                    <div class="varian-box">
                                        <input type="radio" name="varian_id" id="varian_<?= $v['id'] ?>" value="<?= $v['id'] ?>" class="d-none" required <?= $stokSewa <= 0 ? 'disabled' : '' ?>>
                                        <label for="varian_<?= $v['id'] ?>" class="varian-select-box" style="cursor: pointer;">
                                            <div class="varian-title"><?= htmlspecialchars($v['nama_varian']) ?></div>
                                            <div class="stok-label">Stok Tersedia</div>
                                            <div class="<?= $stockClass ?>">
                                                Bawa Pulang : <?= $stokSewa ?> unit
                                            </div>
                                        </label>
                                    </div>
                                <?php } ?>
                            </div>
                        </div>
                        <div class="mb-4">
                            <label for="catatan" class="form-label">Informasi Kontak & Catatan</label>
                            <textarea class="form-control" id="catatan" name="catatan" rows="3" placeholder="Catatan untuk admin" required></textarea>
                        </div>
                        <div class="d-grid">
                            <button type="submit" name="pesan" class="btn btn-success">
                                <i class="fas fa-paper-plane me-2"></i>Kirim Sewa
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            <!-- Riwayat Sewa -->
            <div class="card mt-4">
                <div class="card-header py-3">
                    <h5 class="mb-0"><i class="fas fa-history ps-icon"></i> Riwayat Sewa Saya</h5>
                </div>
                <div class="card-body p-4">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Varian PS</th>
                                    <th>Tanggal Mulai</th>
                                    <th>Durasi</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php if (mysqli_num_rows($pesanan) > 0) { while($row = mysqli_fetch_assoc($pesanan)) { ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['nama_varian']); ?></td>
                                    <td><?= date('d M Y', strtotime($row['tanggal_bermain'])); ?></td>
                                    <td><?= $row['hari']; ?> Hari</td>
                                    <td><?= isset($row['status']) ? $row['status'] : 'Pending'; ?></td>
                                </tr>
                            <?php }} else { ?>
                                <tr><td colspan="4" class="text-center">Belum ada sewa</td></tr>
                            <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Tambahkan event listener untuk tanggal dan durasi
document.getElementById('tanggal_sewa').addEventListener('change', cekStokSewa);
document.getElementById('durasi_sewa').addEventListener('change', cekStokSewa);

function cekStokSewa() {
    const varianInputs = document.querySelectorAll('input[name="varian_id"]');
    const tanggal = document.getElementById('tanggal_sewa').value;
    const durasi = document.getElementById('durasi_sewa').value;
    const btnSubmit = document.querySelector('button[name="pesan"]');

    // Reset semua radio button saat ada perubahan waktu
    varianInputs.forEach(input => {
        input.checked = false;
    });
    btnSubmit.disabled = true;

    if (tanggal && durasi) {
        varianInputs.forEach(input => {
            const varian = input.value;
            const varianBox = input.closest('.varian-box');
            const stockInfo = varianBox.querySelector('.stock-high, .stock-medium, .stock-low');
            
            fetch(`cek_stok_sewa.php?varian_id=${varian}&tanggal=${tanggal}&durasi=${durasi}`)
                .then(res => res.json())
                .then(data => {
                    stockInfo.className = ''; // Reset class
                    if (data.stok > 2) {
                        stockInfo.className = 'stock-high';
                        stockInfo.innerHTML = `Bawa Pulang : ${data.stok} unit`;
                        input.disabled = false;
                    } else if (data.stok > 0) {
                        stockInfo.className = 'stock-medium';
                        stockInfo.innerHTML = `Bawa Pulang : ${data.stok} unit`;
                        input.disabled = false;
                    } else {
                        stockInfo.className = 'stock-low';
                        stockInfo.innerHTML = `Bawa Pulang : ${data.stok} unit`;
                        input.disabled = true;
                        if (input.checked) {
                            input.checked = false;
                            btnSubmit.disabled = true;
                        }
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    stockInfo.className = 'stock-low';
                    stockInfo.innerHTML = 'Error checking stock';
                    input.disabled = true;
                });
        });
    }
}

// Tambahkan event listener untuk radio button
document.querySelectorAll('input[name="varian_id"]').forEach(radio => {
    radio.addEventListener('change', function() {
        const btnSubmit = document.querySelector('button[name="pesan"]');
        btnSubmit.disabled = !this.checked;
    });
});

// Style untuk kotak yang dipilih
document.querySelectorAll('.varian-select-box').forEach(box => {
    box.addEventListener('click', function() {
        if (!this.querySelector('input[type="radio"]').disabled) {
            document.querySelectorAll('.varian-select-box').forEach(b => b.classList.remove('selected'));
            this.classList.add('selected');
        }
    });
});

// Disable tombol submit saat load
window.addEventListener('DOMContentLoaded', function() {
    document.querySelector('button[name="pesan"]').disabled = true;
    // Jalankan cek stok pertama kali jika semua field sudah terisi
    if (document.getElementById('tanggal_sewa').value &&
        document.getElementById('durasi_sewa').value) {
        cekStokSewa();
    }
});

// Validasi form sebelum submit
document.getElementById('sewaForm').addEventListener('submit', function(e) {
    const tanggal = document.getElementById('tanggal_sewa').value;
    const jamDatang = document.getElementById('jam_datang').value;
    const durasi = document.getElementById('durasi_sewa').value;
    const varian = document.querySelector('input[name="varian_id"]:checked');
    const catatan = document.getElementById('catatan').value.trim();
    
    if (!tanggal || !jamDatang || !durasi || !varian || !catatan) {
        e.preventDefault();
        alert('Mohon lengkapi form sewa.');
    }
});
</script>
</body>
</html>


