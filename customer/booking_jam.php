<?php
session_start();
include('../config/db.php');
if ($_SESSION['role'] != 'customer') {
    header('Location: ../auth/login.php');
    exit;
}
$user_id = $_SESSION['user_id'];
// Proses booking
if (isset($_POST['pesan'])) {
    $varian_id = $_POST['varian_id'];
    $tanggal_bermain = $_POST['tanggal_bermain'];
    $jam_mulai = $_POST['jam_mulai'];
    $jam_selesai = $_POST['jam_selesai'];
    $catatan = htmlspecialchars($_POST['catatan']);

    // Cek total unit yang tersedia untuk varian ini
    $check_stok = mysqli_query($conn, "SELECT stok_booking FROM varian_ps WHERE id = '$varian_id'");
    $stok_data = mysqli_fetch_assoc($check_stok);
    $total_stok = $stok_data['stok_booking'];

    // Hitung jumlah unit yang sedang digunakan pada waktu yang overlap
    $check_booking = mysqli_query($conn, "SELECT COUNT(*) as total_used FROM pesanan 
                                       WHERE varian_id = '$varian_id' 
                                       AND jenis_pesanan = 'booking'
                                       AND tanggal_bermain = '$tanggal_bermain'
                                       AND ((jam_mulai <= '$jam_mulai' AND jam_selesai > '$jam_mulai') 
                                            OR (jam_mulai < '$jam_selesai' AND jam_selesai >= '$jam_selesai')
                                            OR (jam_mulai >= '$jam_mulai' AND jam_selesai <= '$jam_selesai'))
                                       AND status = 'Disetujui'");
    
    $booking_data = mysqli_fetch_assoc($check_booking);
    $units_used = $booking_data['total_used'];
    
    // Jika semua unit sudah digunakan pada waktu tersebut
    if ($units_used >= $total_stok) {
        $_SESSION['error'] = "Maaf, semua unit PlayStation sudah dibooking pada waktu tersebut. Silakan pilih waktu lain.";
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }

    $query = "INSERT INTO pesanan (user_id, varian_id, jenis_pesanan, catatan, tanggal_bermain, jam_mulai, jam_selesai) 
              VALUES ('$user_id', '$varian_id', 'booking', '$catatan', '$tanggal_bermain', '$jam_mulai', '$jam_selesai')";
    
    mysqli_query($conn, $query);
    $_SESSION['success'] = "Booking berhasil dikirim!";
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}
// Ambil varian PS dengan perhitungan stok yang tersedia
$varian = mysqli_query($conn, "SELECT v.*, 
    (v.stok_booking - COALESCE(
        (SELECT COUNT(*) FROM pesanan p 
         WHERE p.varian_id = v.id 
         AND p.jenis_pesanan = 'booking'
         AND p.status = 'Disetujui'), 0
    )) as sisa_stok_booking
    FROM varian_ps v 
    WHERE v.tersedia = 1 
    AND v.is_deleted = 0");
// Riwayat booking user
$pesanan = mysqli_query($conn, "SELECT p.*, v.nama_varian FROM pesanan p JOIN varian_ps v ON p.varian_id = v.id WHERE p.user_id = $user_id AND p.jenis_pesanan = 'booking' ORDER BY p.waktu_pesanan DESC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Jam Main</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="booking_jam.css">
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
                    <h5 class="mb-0"><i class="fas fa-calendar-check ps-icon"></i> Booking Jam Main</h5>
                </div>
                <div class="card-body p-4">
                    <form method="POST" id="bookingForm">
                        <div class="mb-3">
                            <label for="tanggal_bermain" class="form-label">Tanggal Bermain</label>
                            <input type="date" class="form-control" id="tanggal_bermain" name="tanggal_bermain" min="<?= date('Y-m-d'); ?>" required>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="jam_mulai" class="form-label">Jam Mulai</label>
                                <select class="form-select" id="jam_mulai" name="jam_mulai" required>
                                    <?php for ($i = 8; $i <= 22; $i++) { $jam = sprintf("%02d:00", $i); echo "<option value='$jam'>$jam</option>"; } ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="jam_selesai" class="form-label">Jam Selesai</label>
                                <select class="form-select" id="jam_selesai" name="jam_selesai" required>
                                    <?php for ($i = 9; $i <= 23; $i++) { $jam = sprintf("%02d:00", $i); echo "<option value='$jam'>$jam</option>"; } ?>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Varian PlayStation</label>
                            <div class="varian-grid">
                                <?php 
                                mysqli_data_seek($varian, 0);
                                while($v = mysqli_fetch_assoc($varian)) { 
                                    // Tentukan class berdasarkan jumlah stok booking
                                    $stockClass = '';
                                    $stokBooking = (int)$v['sisa_stok_booking'];
                                    if ($stokBooking > 2) {
                                        $stockClass = 'stock-high';
                                    } elseif ($stokBooking > 0) {
                                        $stockClass = 'stock-medium';
                                    } else {
                                        $stockClass = 'stock-low';
                                    }
                                ?>
                                    <div class="varian-box">
                                        <input type="radio" name="varian_id" id="varian_<?= $v['id'] ?>" value="<?= $v['id'] ?>" class="d-none" required <?= $stokBooking <= 0 ? 'disabled' : '' ?>>
                                        <label for="varian_<?= $v['id'] ?>" class="varian-select-box" style="cursor: pointer;">
                                            <div class="varian-title"><?= htmlspecialchars($v['nama_varian']) ?></div>
                                            <div class="stok-label">Stok Tersedia</div>
                                            <div class="<?= $stockClass ?>">
                                                Booking : <?= $stokBooking ?> unit
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
                            <button type="submit" name="pesan" class="btn btn-primary">
                                <i class="fas fa-paper-plane me-2"></i>Kirim Booking
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            <!-- Riwayat Booking -->
            <div class="card mt-4">
                <div class="card-header py-3">
                    <h5 class="mb-0"><i class="fas fa-history ps-icon"></i> Riwayat Booking Saya</h5>
                </div>
                <div class="card-body p-4">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Varian PS</th>
                                    <th>Tanggal</th>
                                    <th>Jam</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php if (mysqli_num_rows($pesanan) > 0) { while($row = mysqli_fetch_assoc($pesanan)) { ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['nama_varian']); ?></td>
                                    <td><?= date('d M Y', strtotime($row['tanggal_bermain'])); ?></td>
                                    <td><?= $row['jam_mulai']; ?> - <?= $row['jam_selesai']; ?></td>
                                    <td><?= isset($row['status']) ? $row['status'] : 'Pending'; ?></td>
                                </tr>
                            <?php }} else { ?>
                                <tr><td colspan="4" class="text-center">Belum ada booking</td></tr>
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
// Validasi jam selesai harus setelah jam mulai
document.getElementById('jam_mulai').addEventListener('change', function() {
    const jamMulai = this.value;
    const jamSelesai = document.getElementById('jam_selesai');
    const jamMulaiValue = parseInt(jamMulai.split(':')[0]);
    jamSelesai.innerHTML = '';
    for (let i = jamMulaiValue + 1; i <= 23; i++) {
        const jam = i < 10 ? `0${i}:00` : `${i}:00`;
        const option = document.createElement('option');
        option.value = jam;
        option.textContent = jam;
        jamSelesai.appendChild(option);
    }
    cekStokBooking();
});

// Tambahkan event listener untuk tanggal dan jam selesai
document.getElementById('tanggal_bermain').addEventListener('change', cekStokBooking);
document.getElementById('jam_selesai').addEventListener('change', cekStokBooking);

// AJAX cek stok booking
function cekStokBooking() {
    const varianInputs = document.querySelectorAll('input[name="varian_id"]');
    const tanggal = document.getElementById('tanggal_bermain').value;
    const jamMulai = document.getElementById('jam_mulai').value;
    const jamSelesai = document.getElementById('jam_selesai').value;
    const btnSubmit = document.querySelector('button[name="pesan"]');

    // Reset semua radio button saat ada perubahan waktu
    varianInputs.forEach(input => {
        input.checked = false;
    });
    btnSubmit.disabled = true;

    if (tanggal && jamMulai && jamSelesai) {
        varianInputs.forEach(input => {
            const varian = input.value;
            const varianBox = input.closest('.varian-box');
            const stockInfo = varianBox.querySelector('.stock-high, .stock-medium, .stock-low');
            
            fetch(`cek_stok_booking.php?varian_id=${varian}&tanggal=${tanggal}&jam_mulai=${jamMulai}&jam_selesai=${jamSelesai}`)
                .then(res => res.json())
                .then(data => {
                    stockInfo.className = ''; // Reset class
                    if (data.stok > 2) {
                        stockInfo.className = 'stock-high';
                        stockInfo.innerHTML = `Booking : ${data.stok} unit`;
                        input.disabled = false;
                    } else if (data.stok > 0) {
                        stockInfo.className = 'stock-medium';
                        stockInfo.innerHTML = `Booking : ${data.stok} unit`;
                        input.disabled = false;
                    } else {
                        stockInfo.className = 'stock-low';
                        stockInfo.innerHTML = `Booking : ${data.stok} unit`;
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
    if (document.getElementById('tanggal_bermain').value &&
        document.getElementById('jam_mulai').value &&
        document.getElementById('jam_selesai').value) {
        cekStokBooking();
    }
});

// Validasi form sebelum submit
document.getElementById('bookingForm').addEventListener('submit', function(e) {
    const tanggal = document.getElementById('tanggal_bermain').value;
    const jamMulai = document.getElementById('jam_mulai').value;
    const jamSelesai = document.getElementById('jam_selesai').value;
    const varian = document.querySelector('input[name="varian_id"]:checked');
    const catatan = document.getElementById('catatan').value.trim();
    
    if (!tanggal || !jamMulai || !jamSelesai || !varian || !catatan) {
        e.preventDefault();
        alert('Mohon lengkapi form booking.');
    }
});
</script>
</body>
</html>
