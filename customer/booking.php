<?php
session_start();
include('../config/db.php');

if ($_SESSION['role'] != 'customer') {
    header('Location: ../auth/login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$varian = mysqli_query($conn, "SELECT v.*, 
    (v.stok_booking - COALESCE(
        (SELECT COUNT(*) FROM pesanan p 
         WHERE p.varian_id = v.id 
         AND p.jenis_pesanan = 'booking'
         AND p.status = 'Disetujui'), 0
    )) as sisa_stok_booking,
    (v.stok_sewa - COALESCE(
        (SELECT COUNT(*) FROM pesanan p 
         WHERE p.varian_id = v.id 
         AND p.jenis_pesanan = 'bawa_pulang'
         AND p.status = 'Disetujui'), 0
    )) as sisa_stok_sewa
    FROM varian_ps v 
    WHERE v.tersedia = 1 
    AND v.is_deleted = 0");

// Fetch user's orders
$pesanan = mysqli_query($conn, "SELECT p.*, v.nama_varian, p.alasan_penolakan, 
                               p.tanggal_bermain, p.jam_mulai, p.jam_selesai, p.jam_datang, p.hari
                               FROM pesanan p 
                               JOIN varian_ps v ON p.varian_id = v.id
                               WHERE p.user_id = $user_id
                               AND v.is_deleted = 0
                               ORDER BY p.waktu_pesanan DESC");

if (isset($_POST['pesan'])) {
    $user_id = $_SESSION['user_id'];
    $varian_id = $_POST['varian_id'];
    $jenis_pesanan = $_POST['jenis_pesanan'];
    $catatan = htmlspecialchars($_POST['catatan']);
    
    // Default values
    $tanggal_bermain = null;
    $jam_mulai = null;
    $jam_selesai = null;
    $jam_datang = null;
    $hari = null;
    
    if ($jenis_pesanan == 'booking') {
        $tanggal_bermain = $_POST['tanggal_bermain'];
        $jam_mulai = $_POST['jam_mulai'];
        $jam_selesai = $_POST['jam_selesai'];
        
        // Cek ketersediaan waktu booking
        $check_booking = mysqli_query($conn, "SELECT * FROM pesanan 
                                           WHERE varian_id = '$varian_id' 
                                           AND jenis_pesanan = 'booking'
                                           AND tanggal_bermain = '$tanggal_bermain'
                                           AND ((jam_mulai <= '$jam_mulai' AND jam_selesai > '$jam_mulai') 
                                                OR (jam_mulai < '$jam_selesai' AND jam_selesai >= '$jam_selesai')
                                                OR (jam_mulai >= '$jam_mulai' AND jam_selesai <= '$jam_selesai'))
                                           AND status = 'Disetujui'");
                                           
        if (mysqli_num_rows($check_booking) > 0) {
            $_SESSION['error'] = "Maaf, waktu tersebut sudah dibooking. Silakan pilih waktu lain.";
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        }
    } elseif ($jenis_pesanan == 'bawa_pulang') {
        $tanggal_bermain = $_POST['tanggal_sewa']; // Tanggal pinjam
        $jam_datang = $_POST['jam_datang'];       // Jam datang ke PS
        $hari = $_POST['durasi_sewa'];            // Durasi sewa (dalam hari)
        
        // Cek ketersediaan PS untuk sewa
        $check_sewa = mysqli_query($conn, "SELECT * FROM pesanan 
                                        WHERE varian_id = '$varian_id' 
                                        AND jenis_pesanan = 'bawa_pulang'
                                        AND status = 'Disetujui'");
                                        
        if (mysqli_num_rows($check_sewa) > 0) {
            $_SESSION['error'] = "Maaf, PlayStation ini sedang disewa. Silakan pilih varian lain.";
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        }
    }

    $query = "INSERT INTO pesanan (user_id, varian_id, jenis_pesanan, catatan, tanggal_bermain, jam_mulai, jam_selesai, jam_datang, hari) 
              VALUES ('$user_id', '$varian_id', '$jenis_pesanan', '$catatan'";
    
    if ($jenis_pesanan == 'booking') {
        $query .= ", '$tanggal_bermain', '$jam_mulai', '$jam_selesai', NULL, NULL";
    } elseif ($jenis_pesanan == 'bawa_pulang') {
        $query .= ", '$tanggal_bermain', NULL, NULL, '$jam_datang', '$hari'";
    } else {
        $query .= ", NULL, NULL, NULL, NULL, NULL";
    }
    
    $query .= ")";
    
    mysqli_query($conn, $query);

    $_SESSION['success'] = "Pesanan berhasil dikirim!";
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pilih Jenis Pemesanan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-6 col-md-8">
                <div class="card text-center">
                    <div class="card-header py-3">
                        <h5 class="mb-0"><i class="fas fa-gamepad ps-icon"></i> Pilih Jenis Pemesanan</h5>
                    </div>
                    <div class="card-body p-4">
                        <a href="booking_jam.php" class="btn btn-primary btn-lg mb-3 w-100">
                            <i class="fas fa-calendar-check me-2"></i> Booking Jam Main
                        </a>
                        <a href="sewa_bawa_pulang.php" class="btn btn-success btn-lg w-100">
                            <i class="fas fa-boxes me-2"></i> Sewa Bawa Pulang
                        </a>
                    </div>
                    <div class="card-footer bg-white text-center py-3" style="border-radius: 0 0 15px 15px;">
                        <a href="../auth/logout.php" class="logout-link">
                            <i class="fas fa-sign-out-alt me-1"></i> Logout
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>