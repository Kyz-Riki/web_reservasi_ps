<?php
session_start();
include('../config/db.php');

if (isset($_POST['register'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validasi input
    if (empty($username) || empty($password) || empty($confirm_password)) {
        $error_message = "Semua field harus diisi.";
    } elseif ($password != $confirm_password) {
        $error_message = "Password dan konfirmasi password tidak cocok.";
    } else {
        // Cek apakah username sudah digunakan
        $check_query = mysqli_query($conn, "SELECT * FROM users WHERE username='$username'");
        if (mysqli_num_rows($check_query) > 0) {
            $error_message = "Username sudah digunakan. Silakan pilih username lain.";
        } else {
            // Hash password menggunakan MD5
            $hashed_password = md5($password);
            
            // Simpan data ke database dengan role 'customer'
            $insert_query = mysqli_query($conn, "INSERT INTO users (username, password, role) VALUES ('$username', '$hashed_password', 'customer')");
            
            if ($insert_query) {
                $success_message = "Pendaftaran berhasil! Silakan login.";
                header("refresh:2;url=login.php"); // Redirect ke halaman login setelah 2 detik
            } else {
                $error_message = "Terjadi kesalahan. Silakan coba lagi.";
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
    <title>Registrasi - Reservasi PlayStation</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }
        
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background: linear-gradient(135deg, #3498db, #8e44ad);
            overflow: hidden;
        }
        
        .register-container {
            width: 100%;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        
        .register-box {
            width: 450px;
            background: rgba(255, 255, 255, 0.9);
            border-radius: 15px;
            padding: 40px;
            box-shadow: 0 15px 25px rgba(0, 0, 0, 0.2);
            backdrop-filter: blur(10px);
            transform: translateY(0);
            transition: transform 0.5s, box-shadow 0.5s;
        }
        
        .register-box:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 35px rgba(0, 0, 0, 0.3);
        }
        
        .register-box h2 {
            margin-bottom: 30px;
            color: #333;
            text-align: center;
            font-size: 32px;
            font-weight: 600;
            letter-spacing: 1px;
        }
        
        .input-box {
            position: relative;
            margin-bottom: 30px;
        }
        
        .input-box input {
            width: 100%;
            padding: 15px 10px;
            font-size: 16px;
            color: #333;
            border: none;
            border-bottom: 2px solid #999;
            outline: none;
            background: transparent;
            transition: border-color 0.3s;
        }
        
        .input-box label {
            position: absolute;
            top: 15px;
            left: 10px;
            color: #999;
            font-size: 16px;
            pointer-events: none;
            transition: 0.3s;
        }
        
        .input-box input:focus ~ label,
        .input-box input:valid ~ label {
            top: -10px;
            left: 0;
            color: #6c5ce7;
            font-size: 14px;
        }
        
        .input-box input:focus,
        .input-box input:valid {
            border-bottom: 2px solid #6c5ce7;
        }
        
        .error-message {
            color: #e74c3c;
            margin-bottom: 20px;
            text-align: center;
            font-size: 14px;
            padding: 10px;
            background-color: rgba(231, 76, 60, 0.1);
            border-radius: 5px;
            border-left: 3px solid #e74c3c;
        }
        
        .success-message {
            color: #27ae60;
            margin-bottom: 20px;
            text-align: center;
            font-size: 14px;
            padding: 10px;
            background-color: rgba(39, 174, 96, 0.1);
            border-radius: 5px;
            border-left: 3px solid #27ae60;
        }
        
        button {
            width: 100%;
            padding: 15px 0;
            background: #6c5ce7;
            color: #fff;
            border: none;
            border-radius: 30px;
            font-size: 18px;
            cursor: pointer;
            transition: background 0.3s, transform 0.2s;
            margin-top: 10px;
            font-weight: 500;
            letter-spacing: 1px;
            box-shadow: 0 5px 15px rgba(142, 68, 173, 0.4);
        }
        
        button:hover {
            background: #6c5ce7;
            transform: scale(1.02);
        }
        
        button:active {
            transform: scale(0.98);
        }
        
        .login-link {
            margin-top: 20px;
            text-align: center;
            font-size: 14px;
            color: #555;
        }
        
        .login-link a {
            color: #6c5ce7;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s;
        }
        
        .login-link a:hover {
            color: #6c5ce7;
            text-decoration: underline;
        }
        
        /* Background animation */
        @keyframes gradientAnimation {
            0% {
                background-position: 0% 50%;
            }
            50% {
                background-position: 100% 50%;
            }
            100% {
                background-position: 0% 50%;
            }
        }
        
        body:before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(-45deg, #3498db, #6c5ce7, #2980b9, #6c5ce7);
            background-size: 400% 400%;
            animation: gradientAnimation 15s ease infinite;
            z-index: -1;
        }
        
        /* Responsive styling */
        @media (max-width: 500px) {
            .register-box {
                width: 90%;
                padding: 30px 20px;
            }
            
            .register-box h2 {
                font-size: 24px;
            }
            
            .input-box input {
                padding: 12px 8px;
            }
            
            button {
                padding: 12px 0;
                font-size: 16px;
            }
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-box">
            <h2>Daftar Akun Baru</h2>
            <form method="POST">
                <?php if(isset($error_message)): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
                </div>
                <?php endif; ?>
                
                <?php if(isset($success_message)): ?>
                <div class="success-message">
                    <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
                </div>
                <?php endif; ?>
                
                <div class="input-box">
                    <input type="text" name="username" required>
                    <label><i class="fas fa-user"></i> Username</label>
                </div>
                
                <div class="input-box">
                    <input type="password" name="password" required>
                    <label><i class="fas fa-lock"></i> Password</label>
                </div>
                
                <div class="input-box">
                    <input type="password" name="confirm_password" required>
                    <label><i class="fas fa-check-circle"></i> Konfirmasi Password</label>
                </div>
                
                <button type="submit" name="register">
                    <i class="fas fa-user-plus"></i> Daftar
                </button>
                
                <div class="login-link">
                    Sudah punya akun? <a href="login.php">Masuk sekarang</a>
                </div>
                <div class="login-link">
                    <a href="../index.php">Kembali ke halaman utama</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
