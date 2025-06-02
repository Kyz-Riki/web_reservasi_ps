<?php
include('../config/db.php');

$varian_id = $_GET['varian_id'];
$tanggal = $_GET['tanggal'];
$durasi = $_GET['durasi'];

// Ambil stok total varian
$q = mysqli_query($conn, "SELECT stok_sewa FROM varian_ps WHERE id = '$varian_id'");
$data = mysqli_fetch_assoc($q);
$stok_total = $data ? (int)$data['stok_sewa'] : 0;

// Hitung pesanan overlap (status Disetujui)
$q2 = mysqli_query($conn, "SELECT COUNT(*) as jml FROM pesanan 
    WHERE varian_id = '$varian_id'
    AND jenis_pesanan = 'bawa_pulang'
    AND status = 'Disetujui'
    AND (
        (tanggal_bermain <= '$tanggal' AND DATE_ADD(tanggal_bermain, INTERVAL hari DAY) > '$tanggal')
        OR
        (tanggal_bermain > '$tanggal' AND tanggal_bermain < DATE_ADD('$tanggal', INTERVAL $durasi DAY))
    )
");
$row2 = mysqli_fetch_assoc($q2);
$terpakai = $row2 ? (int)$row2['jml'] : 0;

$stok_tersedia = $stok_total - $terpakai;
echo json_encode(['stok' => $stok_tersedia]);
