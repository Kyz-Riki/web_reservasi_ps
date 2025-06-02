<?php
session_start();
include('../config/db.php');

if ($_SESSION['role'] != 'admin') {
    header('Location: ../auth/login.php');
    exit();
}

// Initialize variables
$username = '';
$password = '';
$role = 'customer';
$status = 'active';
$error = '';

// Process form submission
if (isset($_POST['add_user'])) {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);
    $role = mysqli_real_escape_string($conn, $_POST['role']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    
    // Validate inputs
    if (empty($username) || empty($password)) {
        $error = "Username dan password harus diisi!";
    } else {
        // Check if username already exists
        $check_query = mysqli_query($conn, "SELECT * FROM users WHERE username = '$username'");
        if (mysqli_num_rows($check_query) > 0) {
            $error = "Username sudah digunakan!";
        } else {
            // Hash password menggunakan MD5 (diubah dari password_hash agar konsisten dengan register.php)
            $hashed_password = md5($password);
            
            // Insert new user
            $query = "INSERT INTO users (username, password, role, status) VALUES ('$username', '$hashed_password', '$role', '$status')";
            $result = mysqli_query($conn, $query);
            
            if ($result) {
                $_SESSION['message'] = "User baru berhasil ditambahkan!";
                $_SESSION['msg_type'] = "success";
                header("Location: users.php");
                exit();
            } else {
                $error = "Gagal menambahkan user: " . mysqli_error($conn);
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah User - Playstation Store</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="user.css">
</head>
<body>
    <div class="container">
        <!-- Header Section -->
        <div class="header">
            <h2><i class="fas fa-user-plus"></i> Tambah User Baru</h2>
            <div class="user-info">
                <span class="admin-badge"><i class="fas fa-user-shield"></i> Admin</span>
                <span><?= $_SESSION['username'] ?? 'Admin' ?></span>
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
            <a href="../auth/logout.php" class="nav-link logout">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
        
        <!-- Form Section -->
        <div class="form-section">
            <div class="form-header">
                <h3>Form Tambah User</h3>
                <a href="users.php" class="back-link">
                    <i class="fas fa-arrow-left"></i> Kembali ke Daftar User
                </a>
            </div>
            
            <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i>
                <?= $error ?>
            </div>
            <?php endif; ?>
            
            <form action="" method="POST">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" class="form-control" value="<?= htmlspecialchars($username) ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="password-field">
                        <input type="password" id="password" name="password" class="form-control" required>
                        <i class="fas fa-eye password-toggle" id="togglePassword"></i>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="role">Role</label>
                    <select id="role" name="role" class="select-control">
                        <option value="customer" <?= ($role == 'customer') ? 'selected' : '' ?>>Customer</option>
                        <option value="admin" <?= ($role == 'admin') ? 'selected' : '' ?>>Admin</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="status">Status</label>
                    <select id="status" name="status" class="select-control">
                        <option value="active" <?= ($status == 'active') ? 'selected' : '' ?>>Active</option>
                        <option value="inactive" <?= ($status == 'inactive') ? 'selected' : '' ?>>Inactive</option>
                    </select>
                </div>
                
                <div class="form-actions">
                    <a href="users.php" class="btn btn-secondary">Batal</a>
                    <button type="submit" name="add_user" class="btn btn-primary">
                        <i class="fas fa-save"></i> Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
    // Toggle password visibility
    document.addEventListener('DOMContentLoaded', function() {
        const togglePassword = document.getElementById('togglePassword');
        const password = document.getElementById('password');
        
        togglePassword.addEventListener('click', function() {
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            
            // Toggle icon
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
        });
    });
    </script>
</body>
</html>