<?php
session_start();
include('../config/db.php');

if ($_SESSION['role'] != 'admin') {
    header('Location: ../auth/login.php');
}

// Initialize filter variables
$filter_status = isset($_GET['status']) ? $_GET['status'] : '';
$filter_jenis = isset($_GET['jenis_pesanan']) ? $_GET['jenis_pesanan'] : '';
$filter_tanggal_mulai = isset($_GET['tanggal_mulai']) ? $_GET['tanggal_mulai'] : '';
$filter_tanggal_selesai = isset($_GET['tanggal_selesai']) ? $_GET['tanggal_selesai'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Build where clause based on filters
$where_clauses = [];

if (!empty($filter_status)) {
    $where_clauses[] = "p.status = '$filter_status'";
}

if (!empty($filter_jenis)) {
    $where_clauses[] = "p.jenis_pesanan = '$filter_jenis'";
}

if (!empty($filter_tanggal_mulai)) {
    $where_clauses[] = "DATE(p.waktu_pesanan) >= '$filter_tanggal_mulai'";
}

if (!empty($filter_tanggal_selesai)) {
    $where_clauses[] = "DATE(p.waktu_pesanan) <= '$filter_tanggal_selesai'";
}

if (!empty($search)) {
    $search = mysqli_real_escape_string($conn, $search);
    $where_clauses[] = "(u.username LIKE '%$search%' OR v.nama_varian LIKE '%$search%' OR p.catatan LIKE '%$search%')";
}

$where_clause = !empty($where_clauses) ? " WHERE " . implode(' AND ', $where_clauses) : "";

// Pagination
$records_per_page = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $records_per_page;

// Count total records for pagination
$total_records_query = mysqli_query($conn, "SELECT COUNT(*) as total FROM pesanan p 
                                           JOIN users u ON p.user_id = u.id 
                                           JOIN varian_ps v ON p.varian_id = v.id
                                           $where_clause");
$total_records_data = mysqli_fetch_assoc($total_records_query);
$total_records = $total_records_data['total'];
$total_pages = ceil($total_records / $records_per_page);

// Get records with pagination
$laporan_query = mysqli_query($conn, "SELECT p.*, u.username, v.nama_varian,
                                    DATE_FORMAT(p.waktu_pesanan, '%d-%m-%Y') as formatted_waktu_pesanan,
                                    DATE_FORMAT(p.tanggal_bermain, '%d-%m-%Y') as formatted_tanggal_bermain
                                    FROM pesanan p
                                    JOIN users u ON p.user_id = u.id
                                    JOIN varian_ps v ON p.varian_id = v.id
                                    $where_clause
                                    ORDER BY p.waktu_pesanan DESC
                                    LIMIT $offset, $records_per_page");

// Statistics
$total_pesanan_query = mysqli_query($conn, "SELECT COUNT(*) as total FROM pesanan");
$total_pesanan_data = mysqli_fetch_assoc($total_pesanan_query);
$total_pesanan = $total_pesanan_data['total'];

$pending_pesanan_query = mysqli_query($conn, "SELECT COUNT(*) as total FROM pesanan WHERE status = 'Pending'");
$pending_pesanan_data = mysqli_fetch_assoc($pending_pesanan_query);
$pending_pesanan = $pending_pesanan_data['total'];

$disetujui_pesanan_query = mysqli_query($conn, "SELECT COUNT(*) as total FROM pesanan WHERE status = 'Disetujui'");
$disetujui_pesanan_data = mysqli_fetch_assoc($disetujui_pesanan_query);
$disetujui_pesanan = $disetujui_pesanan_data['total'];

$selesai_pesanan_query = mysqli_query($conn, "SELECT COUNT(*) as total FROM pesanan WHERE status = 'Selesai'");
$selesai_pesanan_data = mysqli_fetch_assoc($selesai_pesanan_query);
$selesai_pesanan = $selesai_pesanan_data['total'];

$ditolak_pesanan_query = mysqli_query($conn, "SELECT COUNT(*) as total FROM pesanan WHERE status = 'Ditolak'");
$ditolak_pesanan_data = mysqli_fetch_assoc($ditolak_pesanan_query);
$ditolak_pesanan = $ditolak_pesanan_data['total'];

// Handle export to Excel
if (isset($_GET['export']) && $_GET['export'] == 'excel') {
    // Set headers for Excel download
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment;filename="laporan_pesanan.xls"');
    header('Cache-Control: max-age=0');
    
    // Get all records for export (no pagination)
    $export_query = mysqli_query($conn, "SELECT p.*, u.username, v.nama_varian,
                                        DATE_FORMAT(p.waktu_pesanan, '%d-%m-%Y') as formatted_waktu_pesanan
                                        FROM pesanan p
                                        JOIN users u ON p.user_id = u.id
                                        JOIN varian_ps v ON p.varian_id = v.id
                                        $where_clause
                                        ORDER BY p.waktu_pesanan DESC");
    
    // Output Excel content
    echo '<!DOCTYPE html>';
    echo '<html>';
    echo '<head>';
    echo '<meta charset="UTF-8">';
    echo '<title>Laporan Pesanan</title>';
    echo '</head>';
    echo '<body>';
    echo '<table border="1">';
    echo '<thead><tr><th>No</th><th>Nama Customer</th><th>Jenis Pesanan</th><th>Varian PS</th><th>Catatan</th><th>Tanggal Pesanan</th><th>Status</th></tr></thead>';
    echo '<tbody>';
    
    $no = 1;
    while ($row = mysqli_fetch_assoc($export_query)) {
        echo '<tr>';
        echo '<td>' . $no . '</td>';
        echo '<td>' . $row['username'] . '</td>';
        echo '<td>' . ($row['jenis_pesanan'] == 'booking' ? 'Booking' : 'Bawa Pulang') . '</td>';
        echo '<td>' . $row['nama_varian'] . '</td>';
        echo '<td>' . $row['catatan'] . '</td>';
        echo '<td>' . $row['formatted_waktu_pesanan'] . '</td>';
        echo '<td>' . $row['status'] . '</td>';
        echo '</tr>';
        $no++;
    }
    
    echo '</tbody>';
    echo '</table>';
    echo '</body>';
    echo '</html>';
    exit();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Pesanan - Playstation Store</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="laporan.css">
</head>
<body>
    <div class="container">
        <!-- Header Section -->
        <div class="header">
            <h2><i class="fas fa-clipboard-list"></i> Laporan Pesanan</h2>
            <div class="user-info">
                <span class="admin-badge"><i class="fas fa-user-shield"></i> Admin</span>
            </div>
        </div>
        
        <!-- Navigation Section -->
        <div class="navigation">
            <a href="dashboard.php" class="nav-link">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a>
            <a href="varian.php" class="nav-link">
                <i class="fas fa-tags"></i> Manajemen Varian PS
            </a>
            <a href="users.php" class="nav-link">
                <i class="fas fa-users"></i> Manajemen User
            </a>
            <a href="laporan.php" class="nav-link active">
                <i class="fas fa-clipboard-list"></i> Laporan
            </a>
            <a href="../auth/logout.php" class="nav-link logout">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
        
        <!-- Stats Section -->
        <div class="stats-container">
            <div class="stat-card">
                <i class="fas fa-shopping-cart stat-icon"></i>
                <div class="stat-value"><?= $total_pesanan ?></div>
                <div class="stat-label">Total Pesanan</div>
            </div>
            <div class="stat-card">
                <i class="fas fa-clock stat-icon"></i>
                <div class="stat-value"><?= $pending_pesanan ?></div>
                <div class="stat-label">Pending</div>
            </div>
            <div class="stat-card">
                <i class="fas fa-spinner stat-icon"></i>
                <div class="stat-value"><?= $disetujui_pesanan ?></div>
                <div class="stat-label">Disetujui</div>
            </div>
            <div class="stat-card">
                <i class="fas fa-check-circle stat-icon"></i>
                <div class="stat-value"><?= $selesai_pesanan ?></div>
                <div class="stat-label">Selesai</div>
            </div>
            <div class="stat-card">
                <i class="fas fa-times-circle stat-icon"></i>
                <div class="stat-value"><?= $ditolak_pesanan ?></div>
                <div class="stat-label">Ditolak</div>
            </div>
        </div>
        
        <!-- Table Section -->
        <div class="table-section">
            <!-- Filter Form -->
            <form method="GET" action="laporan.php" class="filter-form">
                <div class="filter-group">
                    <label for="status">Status Pesanan</label>
                    <select name="status" id="status" class="filter-select">
                        <option value="">Semua Status</option>
                        <option value="Pending" <?= $filter_status == 'Pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="Disetujui" <?= $filter_status == 'Disetujui' ? 'selected' : '' ?>>Disetujui</option>
                        <option value="Selesai" <?= $filter_status == 'Selesai' ? 'selected' : '' ?>>Selesai</option>
                        <option value="Ditolak" <?= $filter_status == 'Ditolak' ? 'selected' : '' ?>>Ditolak</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="jenis_pesanan">Jenis Pesanan</label>
                    <select name="jenis_pesanan" id="jenis_pesanan" class="filter-select">
                        <option value="">Semua Jenis</option>
                        <option value="booking" <?= $filter_jenis == 'booking' ? 'selected' : '' ?>>Booking</option>
                        <option value="bawa_pulang" <?= $filter_jenis == 'bawa_pulang' ? 'selected' : '' ?>>Bawa Pulang</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="tanggal_mulai">Tanggal Mulai</label>
                    <input type="date" name="tanggal_mulai" id="tanggal_mulai" class="filter-input" value="<?= $filter_tanggal_mulai ?>">
                </div>
                
                <div class="filter-group">
                    <label for="tanggal_selesai">Tanggal Selesai</label>
                    <input type="date" name="tanggal_selesai" id="tanggal_selesai" class="filter-input" value="<?= $filter_tanggal_selesai ?>">
                </div>
                
                <div class="filter-group">
                    <label for="search">Pencarian</label>
                    <input type="text" name="search" id="search" placeholder="Cari customer, varian, dll..." class="filter-input" value="<?= $search ?>">
                </div>
                
                <button type="submit" class="filter-button">
                    <i class="fas fa-filter"></i> Filter
                </button>
                
                <a href="laporan.php" class="filter-button reset">
                    <i class="fas fa-sync-alt"></i> Reset
                </a>
                
                <!-- Export to Excel -->
                <a href="laporan.php?<?= http_build_query(array_filter($_GET)) ?>&export=excel" class="export-button">
                    <i class="fas fa-file-excel"></i> Export Excel
                </a>
            </form>
            
            <div class="table-header">
                <h3>Daftar Pesanan</h3>
            </div>
            
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama Customer</th>
                            <th>Jenis Pesanan</th>
                            <th>Varian PS</th>
                            <th>Catatan</th>
                            <th>Tanggal Pesanan</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php 
                    if (mysqli_num_rows($laporan_query) > 0) {
                        $no = $offset + 1;
                        while($row = mysqli_fetch_assoc($laporan_query)) { 
                    ?>
                        <tr>
                            <td data-label="No"><?= $no ?></td>
                            <td data-label="Nama Customer"><?= htmlspecialchars($row['username']) ?></td>
                            <td data-label="Jenis Pesanan">
                                <?php if($row['jenis_pesanan'] == 'booking'): ?>
                                    <span class="status-badge diproses">
                                        <i class="fas fa-calendar-check"></i> Booking
                                    </span>
                                <?php else: ?>
                                    <span class="status-badge pending">
                                        <i class="fas fa-truck"></i> Bawa Pulang
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td data-label="Varian PS"><?= htmlspecialchars($row['nama_varian']) ?></td>
                            <td data-label="Catatan"><?= htmlspecialchars($row['catatan']) ?></td>
                            <td data-label="Tanggal Pesanan"><?= $row['formatted_waktu_pesanan'] ?></td>
                            <td data-label="Status">
                                <?php if($row['status'] == 'Pending'): ?>
                                    <span class="status-badge pending">
                                        <i class="fas fa-clock"></i> Pending
                                    </span>
                                <?php elseif($row['status'] == 'Disetujui'): ?>
                                    <span class="status-badge diproses">
                                        <i class="fas fa-spinner"></i> Disetujui
                                    </span>
                                <?php elseif($row['status'] == 'Selesai'): ?>
                                    <span class="status-badge selesai">
                                        <i class="fas fa-check-circle"></i> Selesai
                                    </span>
                                <?php elseif($row['status'] == 'Ditolak'): ?>
                                    <span class="status-badge ditolak">
                                        <i class="fas fa-times-circle"></i> Ditolak
                                    </span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php 
                            $no++;
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
            
            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <!-- Previous page link -->
                <a href="<?= $page > 1 ? 'laporan.php?' . http_build_query(array_filter(array_merge($_GET, ['page' => $page-1]))) : '#' ?>" 
                   class="<?= $page <= 1 ? 'disabled' : '' ?>">
                    <i class="fas fa-chevron-left"></i>
                </a>
                
                <!-- Page number links -->
                <?php
                $start_page = max(1, $page - 2);
                $end_page = min($total_pages, $start_page + 4);
                
                if ($end_page - $start_page < 4 && $start_page > 1) {
                    $start_page = max(1, $end_page - 4);
                }
                
                for ($i = $start_page; $i <= $end_page; $i++): 
                    // Remove 'page' from $_GET and add the new page number
                    $params = $_GET;
                    $params['page'] = $i;
                ?>
                    <a href="laporan.php?<?= http_build_query(array_filter($params)) ?>" 
                       class="<?= $i == $page ? 'active' : '' ?>">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>
                
                <!-- Next page link -->
                <a href="<?= $page < $total_pages ? 'laporan.php?' . http_build_query(array_filter(array_merge($_GET, ['page' => $page+1]))) : '#' ?>" 
                   class="<?= $page >= $total_pages ? 'disabled' : '' ?>">
                    <i class="fas fa-chevron-right"></i>
                </a>
            </div>
            
            <div class="pagination-info">
                Menampilkan <?= $offset + 1 ?>-<?= min($offset + $records_per_page, $total_records) ?> dari <?= $total_records ?> pesanan
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Auto-format date inputs to match MySQL date format
        const dateInputs = document.querySelectorAll('input[type="date"]');
        dateInputs.forEach(input => {
            if (input.value) {
                const date = new Date(input.value);
                if (!isNaN(date.getTime())) {
                    const year = date.getFullYear();
                    const month = String(date.getMonth() + 1).padStart(2, '0');
                    const day = String(date.getDate()).padStart(2, '0');
                    input.value = `${year}-${month}-${day}`;
                }
            }
        });
        
        // Ensure end date is not before start date
        const startDateInput = document.getElementById('tanggal_mulai');
        const endDateInput = document.getElementById('tanggal_selesai');
        
        if (startDateInput && endDateInput) {
            startDateInput.addEventListener('change', function() {
                if (endDateInput.value && startDateInput.value > endDateInput.value) {
                    endDateInput.value = startDateInput.value;
                }
            });
            
            endDateInput.addEventListener('change', function() {
                if (startDateInput.value && startDateInput.value > endDateInput.value) {
                    alert('Tanggal selesai tidak boleh sebelum tanggal mulai');
                    endDateInput.value = startDateInput.value;
                }
            });
        }
    });
    </script>
</body>
</html>
