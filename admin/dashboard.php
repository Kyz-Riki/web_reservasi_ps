<?php
session_start();
include('../config/db.php');

if ($_SESSION['role'] != 'admin') {
    header('Location: ../auth/login.php');
}

// First, check if status column exists, if not, add it
$checkColumn = mysqli_query($conn, "SHOW COLUMNS FROM pesanan LIKE 'status'");
if(mysqli_num_rows($checkColumn) == 0) {
    // Add status column if it doesn't exist
    $addColumn = mysqli_query($conn, "ALTER TABLE pesanan ADD COLUMN status VARCHAR(20) DEFAULT 'Pending'");
    if(!$addColumn) {
        $_SESSION['message'] = "Gagal menambahkan kolom status: " . mysqli_error($conn);
        $_SESSION['msg_type'] = "danger";
    }
}

// Tambah kolom 'alasan_penolakan' jika belum ada
$checkColumn2 = mysqli_query($conn, "SHOW COLUMNS FROM pesanan LIKE 'alasan_penolakan'");
if(mysqli_num_rows($checkColumn2) == 0) {
    mysqli_query($conn, "ALTER TABLE pesanan ADD COLUMN alasan_penolakan TEXT");
}

// Handle status update
if (isset($_POST['update_status'])) {
    $pesanan_id = $_POST['pesanan_id'];
    $new_status = $_POST['status'];
    $alasan_penolakan = isset($_POST['alasan_penolakan']) ? mysqli_real_escape_string($conn, $_POST['alasan_penolakan']) : NULL;

    if ($new_status == 'Ditolak' && !empty($alasan_penolakan)) {
        $update_query = mysqli_query($conn, "UPDATE pesanan SET status = '$new_status', alasan_penolakan = '$alasan_penolakan' WHERE id = $pesanan_id");
    } else {
        $update_query = mysqli_query($conn, "UPDATE pesanan SET status = '$new_status' WHERE id = $pesanan_id");
    }

    if ($update_query) {
        $_SESSION['message'] = "Status pesanan berhasil diperbarui!";
        $_SESSION['msg_type'] = "success";
    } else {
        $_SESSION['message'] = "Gagal memperbarui status: " . mysqli_error($conn);
        $_SESSION['msg_type'] = "danger";
    }

    header('Location: dashboard.php');
    exit();
}

// Pagination settings
$records_per_page = 5;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $records_per_page;

// Filter for completed orders if requested
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$where_clause = '';

if ($filter == 'completed') {
    $where_clause = " WHERE p.status = 'Selesai'";
} elseif ($filter == 'pending') {
    $where_clause = " WHERE p.status = 'Pending'";
} elseif ($filter == 'process') {
    $where_clause = " WHERE p.status = 'Disetujui'";
} elseif ($filter == 'rejected') {
    $where_clause = " WHERE p.status = 'Ditolak'";
} elseif ($filter == 'booking') {
    $where_clause = " WHERE p.jenis_pesanan = 'booking'";
} elseif ($filter == 'rental') {
    $where_clause = " WHERE p.jenis_pesanan = 'bawa_pulang'";
}

// Get total records for pagination
$total_records_query = mysqli_query($conn, "SELECT COUNT(*) as total FROM pesanan p 
                                            JOIN users u ON p.user_id = u.id 
                                            JOIN varian_ps v ON p.varian_id = v.id
                                            $where_clause");
$total_records_data = mysqli_fetch_assoc($total_records_query);
$total_records = $total_records_data['total'];
$total_pages = ceil($total_records / $records_per_page);

// Get records with pagination
$pesanan = mysqli_query($conn, "SELECT p.*, u.username, v.nama_varian,
                                DATE_FORMAT(p.tanggal_bermain, '%Y-%m-%d') as tanggal_bermain,
                                DATE_FORMAT(p.jam_mulai, '%H:%i') as jam_mulai,
                                DATE_FORMAT(p.jam_selesai, '%H:%i') as jam_selesai,
                                DATE_FORMAT(p.jam_datang, '%H:%i') as jam_datang,
                                p.hari
                                FROM pesanan p 
                                JOIN users u ON p.user_id = u.id 
                                JOIN varian_ps v ON p.varian_id = v.id
                                $where_clause
                                ORDER BY p.waktu_pesanan DESC
                                LIMIT $offset, $records_per_page");

// Hitung statistik
$total_pesanan_query = mysqli_query($conn, "SELECT COUNT(*) as total FROM pesanan");
$total_pesanan_data = mysqli_fetch_assoc($total_pesanan_query);
$total_pesanan = $total_pesanan_data['total'];

$completed_pesanan_query = mysqli_query($conn, "SELECT COUNT(*) as total FROM pesanan WHERE status = 'Selesai'");
$completed_pesanan_data = mysqli_fetch_assoc($completed_pesanan_query);
$completed_pesanan = $completed_pesanan_data['total'];

$jenis_query = mysqli_query($conn, "SELECT COUNT(DISTINCT jenis_pesanan) as total FROM pesanan");
$jenis_data = mysqli_fetch_assoc($jenis_query);
$total_jenis = $jenis_data['total'];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - Playstation Store</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="dashboard.css">
    <style>
        /* Pagination styles */
        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 20px;
            gap: 5px;
        }
        
        .pagination a, .pagination span {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 35px;
            height: 35px;
            padding: 0 10px;
            border-radius: 5px;
            background-color: #f5f5f5;
            color: #333;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .pagination a:hover {
            background-color: #e0e0e0;
        }
        
        .pagination .active {
            background-color: #4361ee;
            color: white;
        }
        
        .pagination .disabled {
            color: #aaa;
            cursor: not-allowed;
        }
        
        .pagination-info {
            text-align: center;
            margin-top: 10px;
            color: #666;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header Section -->
        <div class="header">
            <h2><i class="fas fa-gamepad"></i> Dashboard Admin</h2>
            <div class="user-info">
                <span class="admin-badge"><i class="fas fa-user-shield"></i> Admin</span>
                
            </div>
        </div>
        
        <!-- Navigation Section -->
        <div class="navigation">
            <a href="dashboard.php" class="nav-link active">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a>
            <a href="varian.php" class="nav-link">
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
        
        <!-- Alert Message -->
        <?php if (isset($_SESSION['message'])): ?>
        <div class="alert alert-<?= $_SESSION['msg_type'] ?>">
            <i class="fas fa-<?= $_SESSION['msg_type'] == 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
            <?= $_SESSION['message'] ?>
        </div>
        <?php 
        unset($_SESSION['message']);
        unset($_SESSION['msg_type']);
        endif; 
        ?>
        
        <!-- Stats Section -->
        <div class="stats-container">
            <div class="stat-card">
                <i class="fas fa-shopping-cart stat-icon"></i>
                <div class="stat-value"><?= $total_pesanan ?></div>
                <div class="stat-label">Total Pesanan</div>
            </div>
            <div class="stat-card">
                <i class="fas fa-check-circle stat-icon"></i>
                <div class="stat-value"><?= $completed_pesanan ?></div>
                <div class="stat-label">Pesanan Selesai</div>
            </div>
            <div class="stat-card">
                <i class="fas fa-list-alt stat-icon"></i>
                <div class="stat-value"><?= $total_jenis ?></div>
                <div class="stat-label">Jenis Layanan</div>
            </div>
        </div>
        
        <!-- Table Section -->
        <div class="table-section">
            <div class="filter-tabs">
                <a href="dashboard.php?filter=all" class="filter-tab <?= ($filter == 'all') ? 'active' : '' ?>">
                    <i class="fas fa-th-list"></i> Semua Pesanan
                </a>
                <a href="dashboard.php?filter=pending" class="filter-tab <?= ($filter == 'pending') ? 'active' : '' ?>">
                    <i class="fas fa-clock"></i> Pending
                </a>
                <a href="dashboard.php?filter=process" class="filter-tab <?= ($filter == 'process') ? 'active' : '' ?>">
                    <i class="fas fa-spinner"></i> Disetujui
                </a>
                <a href="dashboard.php?filter=completed" class="filter-tab <?= ($filter == 'completed') ? 'active' : '' ?>">
                    <i class="fas fa-check-circle"></i> Selesai
                </a>
                <a href="dashboard.php?filter=rejected" class="filter-tab <?= ($filter == 'rejected') ? 'active' : '' ?>">
                    <i class="fas fa-times-circle"></i> Ditolak
                </a>
                <div class="filter-divider"></div>
                <a href="dashboard.php?filter=booking" class="filter-tab <?= ($filter == 'booking') ? 'active' : '' ?>">
                    <i class="fas fa-calendar-check"></i> Booking
                </a>
                <a href="dashboard.php?filter=rental" class="filter-tab <?= ($filter == 'rental') ? 'active' : '' ?>">
                    <i class="fas fa-truck"></i> Sewa Bawa Pulang
                </a>
            </div>
            
            <div class="table-header">
                <h3>Daftar Pesanan</h3>
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" id="searchInput" placeholder="Cari pesanan..." onkeyup="searchTable()">
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="data-table" id="pesananTable">
                    <thead>
                        <tr>
                            <th>Customer</th>
                            <th>Varian PS</th>
                            <th>Jenis Pesanan</th>
                            <th>Catatan</th>
                            <th>Tanggal Main</th>
                            <th>Jam Mulai</th>
                            <th>Jam Selesai</th>
                            <th>Jam Datang</th>
                            <th>Hari</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php 
                    if (mysqli_num_rows($pesanan) > 0) {
                        while($row = mysqli_fetch_assoc($pesanan)) { 
                    ?>
                        <tr>
                            <td data-label="Customer"><?= htmlspecialchars($row['username']); ?></td>
                            <td data-label="Varian PS"><?= htmlspecialchars($row['nama_varian']); ?></td>
                            <td data-label="Jenis Pesanan">
                                <?php
                                $jenis = strtolower($row['jenis_pesanan']);
                                $statusClass = '';
                                $icon = '';
                                
                                if ($jenis == 'booking') {
                                    $statusClass = 'status sewa';
                                    $icon = '<i class="fas fa-calendar-check"></i> ';
                                } else {
                                    $statusClass = 'status service';
                                    $icon = '<i class="fas fa-tools"></i> ';
                                }
                                ?>
                                <span class="<?= $statusClass ?>"><?= $icon . htmlspecialchars($row['jenis_pesanan']); ?></span>
                            </td>
                            <td data-label="Catatan"><?= htmlspecialchars($row['catatan']); ?></td>
                            <td data-label="Tanggal Main"><?= $row['tanggal_bermain'] ? date('d M Y', strtotime($row['tanggal_bermain'])) : '-'; ?></td>
                            <td data-label="Jam Mulai"><?= $row['jenis_pesanan'] == 'booking' ? ($row['jam_mulai'] ? $row['jam_mulai'] : '-') : '-'; ?></td>
                            <td data-label="Jam Selesai"><?= $row['jenis_pesanan'] == 'booking' ? ($row['jam_selesai'] ? $row['jam_selesai'] : '-') : '-'; ?></td>
                            <td data-label="Jam Datang"><?= $row['jenis_pesanan'] == 'bawa_pulang' ? ($row['jam_datang'] ? $row['jam_datang'] : '-') : '-'; ?></td>
                            <td data-label="Hari"><?= $row['hari'] ? htmlspecialchars($row['hari']) : '-'; ?></td>
                            <td data-label="Status">
                                <?php
                                // Get actual status from database (or default to "Pending" if null)
                                $status = isset($row['status']) ? $row['status'] : 'Pending';
                                
                                // Set appropriate badge classes and icons based on status
                                switch($status) {
                                    case 'Disetujui':
                                        $badgeClass = 'status-badge diproses';
                                        $icon = '<i class="fas fa-spinner fa-spin"></i>';
                                        break;
                                    case 'Selesai':
                                        $badgeClass = 'status-badge selesai';
                                        $icon = '<i class="fas fa-check-circle"></i>';
                                        break;
                                    case 'Ditolak':
                                        $badgeClass = 'status-badge ditolak';
                                        $icon = '<i class="fas fa-times-circle"></i>';
                                        break;
                                    default: // Pending
                                        $badgeClass = 'status-badge pending';
                                        $icon = '<i class="fas fa-clock"></i>';
                                }
                                ?>
                                <span class="<?= $badgeClass ?>"><?= $icon ?> <?= $status ?></span>
                            </td>
                            <td data-label="Aksi" class="action-cell">
                                <form method="POST" action="dashboard.php" style="display: flex; flex-direction: column; gap: 5px;">
                                    <input type="hidden" name="pesanan_id" value="<?= $row['id'] ?>">
                                    <select name="status" class="status-select" onchange="toggleTextarea(this, <?= $row['id'] ?>)">
                                        <option value="Pending" <?= ($status == 'Pending') ? 'selected' : '' ?>>Pending</option>
                                        <option value="Disetujui" <?= ($status == 'Disetujui') ? 'selected' : '' ?>>Disetujui</option>
                                        <option value="Selesai" <?= ($status == 'Selesai') ? 'selected' : '' ?>>Selesai</option>
                                        <option value="Ditolak" <?= ($status == 'Ditolak') ? 'selected' : '' ?>>Ditolak</option>
                                    </select>
                                    <textarea name="alasan_penolakan" id="alasan_<?= $row['id'] ?>" class="textarea-alasan" placeholder="Tulis alasan penolakan..." style="display: <?= ($status == 'Ditolak') ? 'block' : 'none' ?>;"><?= htmlspecialchars($row['alasan_penolakan'] ?? '') ?></textarea>
                                    <button type="submit" name="update_status" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Update
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php 
                        }
                    } else {
                    ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 20px;">Tidak ada pesanan ditemukan</td>
                        </tr>
                    <?php } ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination Section -->
            <?php if ($total_records > 0): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=1&filter=<?= $filter ?>"><i class="fas fa-angle-double-left"></i></a>
                    <a href="?page=<?= $page-1 ?>&filter=<?= $filter ?>"><i class="fas fa-angle-left"></i></a>
                <?php else: ?>
                    <span class="disabled"><i class="fas fa-angle-double-left"></i></span>
                    <span class="disabled"><i class="fas fa-angle-left"></i></span>
                <?php endif; ?>
                
                <?php
                // Display page numbers with limits
                $start_page = max(1, $page - 2);
                $end_page = min($total_pages, $page + 2);
                
                // Always show first page
                if ($start_page > 1) {
                    echo '<a href="?page=1&filter=' . $filter . '">1</a>';
                    if ($start_page > 2) {
                        echo '<span class="disabled">...</span>';
                    }
                }
                
                // Display page numbers
                for ($i = $start_page; $i <= $end_page; $i++) {
                    if ($i == $page) {
                        echo '<span class="active">' . $i . '</span>';
                    } else {
                        echo '<a href="?page=' . $i . '&filter=' . $filter . '">' . $i . '</a>';
                    }
                }
                
                // Always show last page
                if ($end_page < $total_pages) {
                    if ($end_page < $total_pages - 1) {
                        echo '<span class="disabled">...</span>';
                    }
                    echo '<a href="?page=' . $total_pages . '&filter=' . $filter . '">' . $total_pages . '</a>';
                }
                ?>
                
                <?php if ($page < $total_pages): ?>
                    <a href="?page=<?= $page+1 ?>&filter=<?= $filter ?>"><i class="fas fa-angle-right"></i></a>
                    <a href="?page=<?= $total_pages ?>&filter=<?= $filter ?>"><i class="fas fa-angle-double-right"></i></a>
                <?php else: ?>
                    <span class="disabled"><i class="fas fa-angle-right"></i></span>
                    <span class="disabled"><i class="fas fa-angle-double-right"></i></span>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
    function searchTable() {
        var input, filter, table, tr, td, i, txtValue;
        input = document.getElementById("searchInput");
        filter = input.value.toUpperCase();
        table = document.getElementById("pesananTable");
        tr = table.getElementsByTagName("tr");
        
        for (i = 0; i < tr.length; i++) {
            // Skip header row
            if (i === 0) continue;
            
            let matched = false;
            // Loop through all table columns (excluding the action column)
            for (let j = 0; j < 6; j++) {
                td = tr[i].getElementsByTagName("td")[j];
                if (td) {
                    txtValue = td.textContent || td.innerText;
                    if (txtValue.toUpperCase().indexOf(filter) > -1) {
                        matched = true;
                        break;
                    }
                }
            }
            
            if (matched) {
                tr[i].style.display = "";
            } else {
                tr[i].style.display = "none";
            }
        }
    }
    
    // Auto-hide alert messages after 5 seconds
    document.addEventListener("DOMContentLoaded", function() {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            setTimeout(() => {
                alert.style.opacity = '0';
                alert.style.transition = 'opacity 0.5s';
                setTimeout(() => {
                    alert.style.display = 'none';
                }, 500);
            }, 5000);
        });
    });
    
    function toggleTextarea(selectElement, id) {
        const textarea = document.getElementById('alasan_' + id);
        if (selectElement.value === 'Ditolak') {
            textarea.style.display = 'block';
        } else {
            textarea.style.display = 'none';
            textarea.value = ''; // clear value if not Ditolak
        }
    }
    </script>
</body>
</html>
