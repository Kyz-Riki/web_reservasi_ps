<?php
session_start();
include('../config/db.php');

if ($_SESSION['role'] != 'admin') {
    header('Location: ../auth/login.php');
    exit;
}

// Check for success/error messages
$statusMsg = '';
$statusClass = '';

if (isset($_GET['status'])) {
    switch ($_GET['status']) {
        case 'added':
            $statusMsg = 'Varian PlayStation baru berhasil ditambahkan!';
            $statusClass = 'alert-success';
            break;
        case 'updated':
            $statusMsg = 'Varian PlayStation berhasil diperbarui!';
            $statusClass = 'alert-success';
            break;
        case 'deleted':
            $statusMsg = 'Varian PlayStation berhasil dihapus!';
            $statusClass = 'alert-success';
            break;
        case 'error':
            $statusMsg = 'Terjadi kesalahan! Silakan coba lagi.';
            $statusClass = 'alert-danger';
            break;
    }
}

// Pagination configuration
$records_per_page = 5;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($page - 1) * $records_per_page;

// Remove show_deleted functionality - always show active items
$show_deleted = false;

// Count total records for pagination based on is_deleted status
$total_records_query = mysqli_query($conn, "SELECT COUNT(*) as total FROM varian_ps WHERE is_deleted = 0");
$total_records = mysqli_fetch_assoc($total_records_query)['total'];
$total_pages = ceil($total_records / $records_per_page);

// Get varian data with pagination, always filter by is_deleted = 0
$varian = mysqli_query($conn, "SELECT v.* 
                             FROM varian_ps v
                             WHERE v.is_deleted = 0
                             ORDER BY v.tersedia DESC, v.nama_varian ASC
                             LIMIT $offset, $records_per_page");

// Count stats
$varian_tersedia = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM varian_ps WHERE tersedia = 1 AND is_deleted = 0"))['total'];
$varian_deleted = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM varian_ps WHERE is_deleted = 1"))['total'];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Varian PlayStation - Admin Panel</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
<link rel="stylesheet" href="varian.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <h2><i class="fas fa-tags"></i> Manajemen Varian PlayStation</h2>
            <?php if(!$show_deleted): ?>
            <a href="tambah_varian.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Tambah Varian Baru
            </a>
            <?php endif; ?>
        </div>
        
        <!-- Stats Section -->
        <div class="stats-container">
            <div class="stat-card">
                <i class="fas fa-gamepad stat-icon"></i>
                <div class="stat-value"><?= $total_records + ($show_deleted ? 0 : $varian_deleted) ?></div>
                <div class="stat-label">Total Varian</div>
            </div>
            <div class="stat-card">
                <i class="fas fa-check-circle stat-icon"></i>
                <div class="stat-value"><?= $varian_tersedia ?></div>
                <div class="stat-label">Varian Tersedia</div>
            </div>
            <div class="stat-card">
                <i class="fas fa-times-circle stat-icon"></i>
                <div class="stat-value"><?= $total_records - $varian_tersedia - ($show_deleted ? $total_records : 0) ?></div>
                <div class="stat-label">Varian Tidak Tersedia</div>
            </div>
        </div>
        
        <!-- Notification Section -->
        <?php if($statusMsg != ''): ?>
        <div class="alert <?= $statusClass ?>">
            <i class="fas <?= $statusClass == 'alert-success' ? 'fa-check-circle' : 'fa-exclamation-circle' ?>"></i>
            <?= $statusMsg ?>
        </div>
        <?php endif; ?>
        
        <!-- Navigation -->
        <div class="navigation">
            <a href="dashboard.php" class="nav-link">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a>
            <a href="varian.php" class="nav-link active">
                <i class="fas fa-tags"></i> Manajemen Varian PS
            </a>
            <a href="users.php" class="nav-link">
                <i class="fas fa-users"></i> Manajemen User
            </a>
            <a href="laporan.php" class="nav-link">
                <i class="fas fa-clipboard-list"></i> Laporan
            </a>
            <a href="../auth/logout.php" class="nav-link logout">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
        
        <!-- Varian Table -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Daftar Varian PlayStation</h3>
                <div class="card-tools">
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" id="searchInput" placeholder="Cari varian..." onkeyup="searchTable()">
                    </div>
                    <div class="view-toggle">
                        <a href="varian.php" class="active">
                            <i class="fas fa-list"></i> Aktif
                        </a>
                    </div>
                </div>
            </div>
            
            <?php if(mysqli_num_rows($varian) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Nama Varian</th>
                        <th>Status</th>
                        <th>Stok Booking</th>
                        <th>Stok Sewa</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($v = mysqli_fetch_assoc($varian)): ?>
                    <tr>
                        <td>
                            <strong><?= htmlspecialchars($v['nama_varian']); ?></strong>
                        </td>
                        <td>
                            <span class="status-badge <?= $v['tersedia'] ? 'status-available' : 'status-unavailable' ?>">
                                <i class="fas <?= $v['tersedia'] ? 'fa-check-circle' : 'fa-times-circle' ?>"></i>
                                <?= $v['tersedia'] ? 'Tersedia' : 'Tidak Tersedia'; ?>
                            </span>
                        </td>
                        <td>
                            <?php 
                            $stok_booking_class = 'badge-primary';
                            if ($v['stok_booking'] <= 2) {
                                $stok_booking_class = 'stok-warning';
                            }
                            if ($v['stok_booking'] == 0) {
                                $stok_booking_class = 'stok-danger';
                            }
                            ?>
                            <span class="badge <?= $stok_booking_class ?>">
                                <i class="fas fa-gamepad"></i>
                                <?= $v['stok_booking']; ?> Unit
                            </span>
                        </td>
                        <td>
                            <?php 
                            $stok_sewa_class = 'badge-primary';
                            if ($v['stok_sewa'] <= 2) {
                                $stok_sewa_class = 'stok-warning';
                            }
                            if ($v['stok_sewa'] == 0) {
                                $stok_sewa_class = 'stok-danger';
                            }
                            ?>
                            <span class="badge <?= $stok_sewa_class ?>">
                                <i class="fas fa-box"></i>
                                <?= $v['stok_sewa']; ?> Unit
                            </span>
                        </td>
                        <td>
                            <div class="actions">
                                <a href="edit_varian.php?id=<?= $v['id']; ?>" class="btn btn-warning btn-sm">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                                <a href="hapus_varian.php?id=<?= $v['id']; ?>" class="btn btn-danger btn-sm" 
                                   onclick="return confirm('Yakin ingin menghapus varian <?= addslashes(htmlspecialchars($v['nama_varian'])); ?>?');">
                                    <i class="fas fa-trash"></i> Hapus
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            
            <!-- Pagination -->
            <?php if($total_pages > 1): ?>
            <ul class="pagination">
                <?php if($page > 1): ?>
                <li><a href="?page=1"><i class="fas fa-angle-double-left"></i></a></li>
                <li><a href="?page=<?= $page - 1 ?>"><i class="fas fa-angle-left"></i></a></li>
                <?php else: ?>
                <li class="disabled"><span><i class="fas fa-angle-double-left"></i></span></li>
                <li class="disabled"><span><i class="fas fa-angle-left"></i></span></li>
                <?php endif; ?>
                
                <?php
                // Determine the range of page numbers to display
                $start_page = max(1, $page - 2);
                $end_page = min($total_pages, $page + 2);
                
                // Always show at least 5 pages if available
                if ($end_page - $start_page + 1 < 5) {
                    if ($start_page == 1) {
                        $end_page = min($total_pages, $start_page + 4);
                    } elseif ($end_page == $total_pages) {
                        $start_page = max(1, $end_page - 4);
                    }
                }
                
                // Generate page links
                for ($i = $start_page; $i <= $end_page; $i++): ?>
                    <?php if($i == $page): ?>
                    <li class="active"><span><?= $i ?></span></li>
                    <?php else: ?>
                    <li><a href="?page=<?= $i ?>"><?= $i ?></a></li>
                    <?php endif; ?>
                <?php endfor; ?>
                
                <?php if($page < $total_pages): ?>
                <li><a href="?page=<?= $page + 1 ?>"><i class="fas fa-angle-right"></i></a></li>
                <li><a href="?page=<?= $total_pages ?>"><i class="fas fa-angle-double-right"></i></a></li>
                <?php else: ?>
                <li class="disabled"><span><i class="fas fa-angle-right"></i></span></li>
                <li class="disabled"><span><i class="fas fa-angle-double-right"></i></span></li>
                <?php endif; ?>
            </ul>
            <?php endif; ?>
            
            <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-folder-open"></i>
                <h3>Belum ada varian PlayStation</h3>
                <p>Mulai tambahkan varian PlayStation untuk ditampilkan di sini</p>
                <a href="tambah_varian.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Tambah Varian Baru
                </a>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="footer">
            &copy; <?= date('Y') ?> PlayStation Store Admin Panel | Semua hak cipta dilindungi
        </div>
    </div>
    
    <script>
    function searchTable() {
        var input, filter, table, tr, td, i, txtValue;
        input = document.getElementById("searchInput");
        filter = input.value.toUpperCase();
        table = document.querySelector("table");
        tr = table.getElementsByTagName("tr");
        
        for (i = 1; i < tr.length; i++) {
            td = tr[i].getElementsByTagName("td")[0];
            if (td) {
                txtValue = td.textContent || td.innerText;
                if (txtValue.toUpperCase().indexOf(filter) > -1) {
                    tr[i].style.display = "";
                } else {
                    tr[i].style.display = "none";
                }
            }
        }
    }
    </script>
</body>
</html>