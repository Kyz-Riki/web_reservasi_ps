<?php
session_start();
include('../config/db.php');

// Penanganan Login
if (isset($_POST['login_submit'])) {
    $username = $_POST['login_username'];
    $password = md5($_POST['login_password']); // Tetap menggunakan MD5

    $query = mysqli_query($conn, "SELECT * FROM users WHERE username='$username' AND password='$password'");
    $data = mysqli_fetch_assoc($query);

    if ($data) {
        // Check if user account is inactive
        if ($data['status'] == 'inactive') {
            $error_message = "Akun Anda telah dinonaktifkan. Silakan hubungi admin untuk informasi lebih lanjut.";
        } else {
            $_SESSION['user_id'] = $data['id'];
            $_SESSION['username'] = $data['username']; // Tambahkan username ke session
            $_SESSION['role'] = $data['role'];

            if ($data['role'] == 'admin') {
                header('Location: ../admin/dashboard.php');
            } else {
                header('Location: ../customer/booking.php');
            }
        }
    } else {
        $error_message = "Username atau password salah. Silakan coba lagi.";
    }
}

// Penanganan Register
if (isset($_POST['register_submit'])) {
    $username = $_POST['register_username'];
    $password = $_POST['register_password'];
    $confirm_password = $_POST['register_confirm_password'];
    
    // Validasi input
    if (empty($username) || empty($password) || empty($confirm_password)) {
        $reg_error_message = "Semua field harus diisi.";
    } elseif ($password != $confirm_password) {
        $reg_error_message = "Password dan konfirmasi password tidak cocok.";
    } else {
        // Cek apakah username sudah digunakan
        $check_query = mysqli_query($conn, "SELECT * FROM users WHERE username='$username'");
        if (mysqli_num_rows($check_query) > 0) {
            $reg_error_message = "Username sudah digunakan. Silakan pilih username lain.";
        } else {
            // Hash password menggunakan MD5
            $hashed_password = md5($password);
            
            // Simpan data ke database dengan role 'customer'
            $insert_query = mysqli_query($conn, "INSERT INTO users (username, password, role) VALUES ('$username', '$hashed_password', 'customer')");
            
            if ($insert_query) {
                $success_message = "Pendaftaran berhasil! Silakan login.";
                // Tidak perlu redirect karena kita sudah dalam satu halaman
            } else {
                $reg_error_message = "Terjadi kesalahan. Silakan coba lagi.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login/Signup Form</title>
    <link rel="stylesheet" href="style.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
</head>
<body>
    <div class="container" id="container">
        <div class="form-box login">
            <form method="POST">
                <?php if(isset($error_message)): ?>
                <div class="error-message">
                    <i class='bx bx-error'></i> <?php echo $error_message; ?>
                </div>
                <?php endif; ?>
                <h1>Login</h1>
                <div class="input-box">
                    <input type="text" name="login_username" placeholder="Username" required>
                    <i class='bx bxs-user'></i>
                </div>
                <div class="input-box">
                    <input type="password" name="login_password" id="login_password" placeholder="Password" required>
                    <i class='bx bxs-lock-alt'></i>
                    <i class='bx bx-hide' id="login_toggle_password"></i>
                </div>
                <button type="submit" class="btn" name="login_submit">Login</button>
                <div class="login-link">
                    <a href="../index.php">Kembali ke halaman utama</a>
                </div>
            </form>
        </div>

        <div class="form-box register">
            <form method="POST">
                <?php if(isset($reg_error_message)): ?>
                <div class="error-message">
                    <i class='bx bx-error'></i> <?php echo $reg_error_message; ?>
                </div>
                <?php endif; ?>
                
                <?php if(isset($success_message)): ?>
                <div class="success-message">
                    <i class='bx bx-check'></i> <?php echo $success_message; ?>
                </div>
                <?php endif; ?>
                <h1>Registration</h1>
                <div class="input-box">
                    <input type="text" name="register_username" placeholder="Username" required>
                    <i class='bx bxs-user'></i>
                </div>
                <div class="input-box">
                    <input type="password" name="register_password" id="register_password" placeholder="Password" required>
                    <i class='bx bxs-lock-alt'></i>
                    <i class='bx bx-hide' id="register_toggle_password"></i>
                </div>
                <div class="input-box">
                    <input type="password" name="register_confirm_password" id="register_confirm_password" placeholder="Confirm Password" required>
                    <i class='bx bxs-lock-alt'></i>
                    <i class='bx bx-hide' id="register_toggle_confirm"></i>
                </div>
                <button type="submit" class="btn" name="register_submit">Register</button>
                <div class="login-link">
                    <a href="../index.php">Kembali ke halaman utama</a>
                </div>
            </form>
        </div>

        <div class="toggle-box">
            <div class="toggle-panel toggle-left">
                <h1>Hello, Welcome!</h1>
                <p>Belum punya akun?</p>
                <button class="btn register-btn">Daftar</button>
            </div>

            <div class="toggle-panel toggle-right">
                <h1>Welcome Back!</h1>
                <p>Sudah punya akun?</p>
                <button class="btn login-btn">Login</button>
            </div>
        </div>
    </div>

    <script>
        const container = document.querySelector(".container");
        const registerBtn = document.querySelector(".register-btn");
        const loginBtn = document.querySelector(".login-btn");

        registerBtn.addEventListener("click", () => {
          container.classList.add("active");
        });

        loginBtn.addEventListener("click", () => {
          container.classList.remove("active");
        });

        // Jika ada pesan kesalahan register, tampilkan form register
        <?php if(isset($reg_error_message) || isset($success_message)): ?>
        document.addEventListener('DOMContentLoaded', function() {
            container.classList.add("active");
        });
        <?php endif; ?>

        // Password visibility toggle functionality
        const togglePassword = (passwordField, toggleBtn) => {
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                toggleBtn.classList.remove('bx-hide');
                toggleBtn.classList.add('bx-show');
            } else {
                passwordField.type = 'password';
                toggleBtn.classList.remove('bx-show');
                toggleBtn.classList.add('bx-hide');
            }
        };

        // Login password toggle
        document.getElementById('login_toggle_password').addEventListener('click', function() {
            togglePassword(document.getElementById('login_password'), this);
        });

        // Register password toggle
        document.getElementById('register_toggle_password').addEventListener('click', function() {
            togglePassword(document.getElementById('register_password'), this);
        });

        // Register confirm password toggle
        document.getElementById('register_toggle_confirm').addEventListener('click', function() {
            togglePassword(document.getElementById('register_confirm_password'), this);
        });
    </script>
    
    <style>
        /* Password toggle icon styles */
        .bx-hide, .bx-show {
            position: absolute;
            right: 20px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #777;
            z-index: 10;
        }
        
        .bx-hide:hover, .bx-show:hover {
            color: #444;
        }
        
        /* Override the default positioning for the lock icon */
        .input-box .bxs-lock-alt {
            right: 50px !important;
            left: auto !important;
        }
    </style>
</body>
</html>