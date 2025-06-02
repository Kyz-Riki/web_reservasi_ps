<?php
session_start();
include('../config/db.php');

if ($_SESSION['role'] != 'admin') {
    header('Location: ../auth/login.php');
}

// Handle user deletion
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $delete_query = mysqli_query($conn, "UPDATE users SET is_deleted = 1 WHERE id = $id");

    
    if ($delete_query) {
        $_SESSION['message'] = "User berhasil dihapus!";
        $_SESSION['msg_type'] = "success";
    } else {
        $_SESSION['message'] = "Gagal menghapus user: " . mysqli_error($conn);
        $_SESSION['msg_type'] = "danger";
    }
    
    header('Location: users.php');
    exit();
}

// Handle user status update (active/inactive)
if (isset($_POST['update_status'])) {
    $user_id = $_POST['user_id'];
    $new_status = $_POST['status'];
    
    $update_query = mysqli_query($conn, "UPDATE users SET status = '$new_status' WHERE id = $user_id");
    
    if ($update_query) {
        $_SESSION['message'] = "Status user berhasil diperbarui!";
        $_SESSION['msg_type'] = "success";
    } else {
        $_SESSION['message'] = "Gagal memperbarui status: " . mysqli_error($conn);
        $_SESSION['msg_type'] = "danger";
    }
    
    header('Location: users.php');
    exit();
}

// Filter users if requested
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$where_clause = '';

if ($filter == 'admin') {
    $where_clause = " WHERE role = 'admin'";
} elseif ($filter == 'customer') {
    $where_clause = " WHERE role = 'customer'";
} elseif ($filter == 'active') {
    $where_clause = " WHERE status = 'active'";
} elseif ($filter == 'inactive') {
    $where_clause = " WHERE status = 'inactive'";
}

$where_clause .= ($where_clause ? " AND " : " WHERE ") . "is_deleted = 0";

// Pagination setup
$results_per_page = 5; // Tampilkan 5 data per halaman
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$start_from = ($page - 1) * $results_per_page;

// Count total records for pagination
$total_records_query = mysqli_query($conn, "SELECT COUNT(*) as total FROM users $where_clause");
$total_records_data = mysqli_fetch_assoc($total_records_query);
$total_records = $total_records_data['total'];
$total_pages = ceil($total_records / $results_per_page);

// Query users with pagination
$users = mysqli_query($conn, "SELECT * FROM users $where_clause ORDER BY id ASC LIMIT $start_from, $results_per_page");

// Count statistics
$total_users_query = mysqli_query($conn, "SELECT COUNT(*) as total FROM users WHERE is_deleted = 0");
$total_users_data = mysqli_fetch_assoc($total_users_query);
$total_users = $total_users_data['total'];

$admin_users_query = mysqli_query($conn, "SELECT COUNT(*) as total FROM users WHERE role = 'admin' AND is_deleted = 0");
$admin_users_data = mysqli_fetch_assoc($admin_users_query);
$admin_users = $admin_users_data['total'];

$customer_users_query = mysqli_query($conn, "SELECT COUNT(*) as total FROM users WHERE role = 'customer' AND is_deleted = 0");
$customer_users_data = mysqli_fetch_assoc($customer_users_query);
$customer_users = $customer_users_data['total'];

$active_users_query = mysqli_query($conn, "SELECT COUNT(*) as total FROM users WHERE status = 'active' AND is_deleted = 0");
$active_users_data = mysqli_fetch_assoc($active_users_query);
$active_users = $active_users_data['total'];

$inactive_users_query = mysqli_query($conn, "SELECT COUNT(*) as total FROM users WHERE status = 'inactive' AND is_deleted = 0");
$inactive_users_data = mysqli_fetch_assoc($inactive_users_query);
$inactive_users = $inactive_users_data['total'];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen User - Playstation Store</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="manajemenUser.css">
    <style>
        /* Pagination styles */
        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 20px;
            gap: 5px;
        }
        
        .pagination-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 40px;
            height: 40px;
            padding: 5px 10px;
            background: #f8f9fa;
            border: 1px solid #ddd;
            border-radius: 4px;
            color: #333;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .pagination-btn:hover {
            background: #e9ecef;
        }
        
        .pagination-btn.active {
            background: #4E73DF;
            color: white;
            border-color: #4E73DF;
        }
        
        .pagination-btn.disabled {
            opacity: 0.5;
            cursor: not-allowed;
            pointer-events: none;
        }
        
        .pagination-info {
            text-align: center;
            color: #6c757d;
            margin-top: 10px;
            font-size: 14px;
        }

        /* Status Badge Styles */
        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 4px 8px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .status-active {
            background-color: #e3fcef;
            color: #00a36a;
            border: 1px solid #00a36a;
        }
        
        .status-inactive {
            background-color: #f9e3e3;
            color: #dc3545;
            border: 1px solid #dc3545;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header Section -->
        <div class="header">
            <h2><i class="fas fa-users-cog"></i> Manajemen User</h2>
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
            <a href="users.php" class="nav-link active">
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
                <i class="fas fa-users stat-icon"></i>
                <div class="stat-value"><?= $total_users ?></div>
                <div class="stat-label">Total User</div>
            </div>
            <div class="stat-card">
                <i class="fas fa-user-shield stat-icon"></i>
                <div class="stat-value"><?= $admin_users ?></div>
                <div class="stat-label">Admin</div>
            </div>
            <div class="stat-card">
                <i class="fas fa-user stat-icon"></i>
                <div class="stat-value"><?= $customer_users ?></div>
                <div class="stat-label">Customer</div>
            </div>
            <div class="stat-card">
                <i class="fas fa-check-circle stat-icon"></i>
                <div class="stat-value"><?= $active_users ?></div>
                <div class="stat-label">User Aktif</div>
            </div>
            <div class="stat-card">
                <i class="fas fa-times-circle stat-icon"></i>
                <div class="stat-value"><?= $inactive_users ?></div>
                <div class="stat-label">User Tidak Aktif</div>
            </div>
        </div>
        
        <!-- Table Section -->
        <div class="table-section">
            <div class="filter-tabs">
                <a href="users.php?filter=all" class="filter-tab <?= ($filter == 'all') ? 'active' : '' ?>">
                    <i class="fas fa-th-list"></i> Semua User
                </a>
                <a href="users.php?filter=admin" class="filter-tab <?= ($filter == 'admin') ? 'active' : '' ?>">
                    <i class="fas fa-user-shield"></i> Admin
                </a>
                <a href="users.php?filter=customer" class="filter-tab <?= ($filter == 'customer') ? 'active' : '' ?>">
                    <i class="fas fa-user"></i> Customer
                </a>
                <a href="users.php?filter=active" class="filter-tab <?= ($filter == 'active') ? 'active' : '' ?>">
                    <i class="fas fa-check-circle"></i> Aktif
                </a>
                <a href="users.php?filter=inactive" class="filter-tab <?= ($filter == 'inactive') ? 'active' : '' ?>">
                    <i class="fas fa-times-circle"></i> Tidak Aktif
                </a>
            </div>
            
            <div class="table-header">
                <h3>Daftar User</h3>
                <div style="display: flex; gap: 10px; align-items: center;">
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" id="searchInput" placeholder="Cari user..." onkeyup="searchTable()">
                    </div>
                    <a href="add_user.php" class="add-user-btn">
                        <i class="fas fa-user-plus"></i> Tambah User
                    </a>
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="data-table" id="usersTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Password</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php 
                    if (mysqli_num_rows($users) > 0) {
                        while($user = mysqli_fetch_assoc($users)) { 
                            // Default status to 'active' if not set
                            $status = isset($user['status']) ? $user['status'] : 'active';
                    ?>
                        <tr>
                            <td data-label="ID"><?= $user['id']; ?></td>
                            <td data-label="Username"><?= htmlspecialchars($user['username']); ?></td>
                            <td data-label="Password" class="password-field">
                                <?= substr($user['password'], 0, 10) . '...'; ?>
                            </td>
                            <td data-label="Role">
                                <?php if($user['role'] == 'admin'): ?>
                                    <span class="role-badge admin">
                                        <i class="fas fa-user-shield"></i> Admin
                                    </span>
                                <?php else: ?>
                                    <span class="role-badge customer">
                                        <i class="fas fa-user"></i> Customer
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td data-label="Status">
                                <span class="status-badge <?= $status == 'active' ? 'status-active' : 'status-inactive' ?>">
                                    <i class="fas <?= $status == 'active' ? 'fa-check-circle' : 'fa-times-circle' ?>"></i>
                                    <?= $status == 'active' ? 'Aktif' : 'Tidak Aktif' ?>
                                </span>
                            </td>
                            <td data-label="Aksi" class="action-cell">
                                <a href="edit_user.php?id=<?= $user['id']; ?>" class="action-btn btn-edit">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                                <?php if($user['id'] != $_SESSION['user_id']): // Prevent deleting self ?>
                                <a href="users.php?delete=<?= $user['id']; ?>" class="action-btn btn-delete" 
                                   onclick="return confirm('Apakah Anda yakin ingin menghapus user ini?')">
                                    <i class="fas fa-trash"></i> Hapus
                                </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php 
                        }
                    } else {
                    ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 20px;">Tidak ada user ditemukan</td>
                        </tr>
                    <?php } ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <!-- Previous page link -->
                <a href="<?= $page > 1 ? 'users.php?filter='.$filter.'&page='.($page-1) : '#' ?>" 
                   class="pagination-btn <?= $page <= 1 ? 'disabled' : '' ?>">
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
                ?>
                    <a href="users.php?filter=<?= $filter ?>&page=<?= $i ?>" 
                       class="pagination-btn <?= $i == $page ? 'active' : '' ?>">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>
                
                <!-- Next page link -->
                <a href="<?= $page < $total_pages ? 'users.php?filter='.$filter.'&page='.($page+1) : '#' ?>" 
                   class="pagination-btn <?= $page >= $total_pages ? 'disabled' : '' ?>">
                    <i class="fas fa-chevron-right"></i>
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
    function searchTable() {
        var input, filter, table, tr, td, i, txtValue;
        input = document.getElementById("searchInput");
        filter = input.value.toUpperCase();
        table = document.getElementById("usersTable");
        tr = table.getElementsByTagName("tr");
        
        for (i = 0; i < tr.length; i++) {
            // Skip header row
            if (i === 0) continue;
            
            let matched = false;
            // Loop through columns (id, username, role, status)
            for (let j = 0; j < 5; j++) {
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
    </script>
</body>
</html>