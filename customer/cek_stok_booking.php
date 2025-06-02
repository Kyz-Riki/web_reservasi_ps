<?php
header('Content-Type: application/json');
include('../config/db.php');

$varian_id = $_GET['varian_id'];
$tanggal = $_GET['tanggal'];
$jam_mulai = $_GET['jam_mulai'];
$jam_selesai = $_GET['jam_selesai'];

// Ambil total stok booking untuk varian ini
$stok_query = mysqli_query($conn, "SELECT stok_booking FROM varian_ps WHERE id = '$varian_id'");
$stok_data = mysqli_fetch_assoc($stok_query);
$total_stok = $stok_data['stok_booking'];

// Hitung jumlah unit yang sedang digunakan pada waktu yang overlap
$used_query = mysqli_query($conn, "SELECT COUNT(*) as total_used FROM pesanan 
    WHERE varian_id = '$varian_id' 
    AND jenis_pesanan = 'booking'
    AND tanggal_bermain = '$tanggal'
    AND ((jam_mulai <= '$jam_mulai' AND jam_selesai > '$jam_mulai') 
         OR (jam_mulai < '$jam_selesai' AND jam_selesai >= '$jam_selesai')
         OR (jam_mulai >= '$jam_mulai' AND jam_selesai <= '$jam_selesai'))
    AND status = 'Disetujui'");

$used_data = mysqli_fetch_assoc($used_query);
$units_used = $used_data['total_used'];

// Hitung sisa stok yang tersedia
$stok_tersedia = $total_stok - $units_used;

echo json_encode([
    'stok' => $stok_tersedia,
    'total_stok' => $total_stok,
    'units_used' => $units_used
]);
