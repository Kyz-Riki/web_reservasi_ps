<?php
session_start();
include('../config/db.php');

if ($_SESSION['role'] != 'admin') {
    header('Location: ../auth/login.php');
    exit;
}

// Validasi ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: varian.php');
    exit;
}

$id = $_GET['id'];
$query = mysqli_query($conn, "SELECT * FROM varian_ps WHERE id = $id");

if (mysqli_num_rows($query) === 0) {
    header('Location: varian.php');
    exit;
}

$varian = mysqli_fetch_assoc($query);

if (isset($_POST['update'])) {
    $nama_varian = htmlspecialchars($_POST['nama_varian']);
    $tersedia = (int)$_POST['tersedia'];
    $stok_booking = (int)$_POST['stok_booking'];
    $stok_sewa = (int)$_POST['stok_sewa'];
    
    // Validasi input
    if (empty($nama_varian)) {
        $error = "Nama varian tidak boleh kosong!";
    } else {
        $update_query = mysqli_query($conn, "UPDATE varian_ps SET 
            nama_varian='$nama_varian', 
            tersedia='$tersedia',
            stok_booking='$stok_booking',
            stok_sewa='$stok_sewa' 
            WHERE id=$id");
        
        if ($update_query) {
            header('Location: varian.php?status=updated');
            exit;
        } else {
            $error = "Gagal mengupdate data: " . mysqli_error($conn);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Varian PS - Admin Panel</title>
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
            background-color: #f5f7fb;
            color: #333;
        }
        
        .container {
            max-width: 900px;
            margin: 40px auto;
            padding: 0 20px;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        
        .header h2 {
            color: #333;
            font-size: 24px;
            font-weight: 600;
            display: flex;
            align-items: center;
        }
        
        .header h2 i {
            margin-right: 10px;
            color: #6c5ce7;
        }
        
        .card {
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            padding: 30px;
            margin-bottom: 30px;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #555;
            font-size: 15px;
        }
        
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 15px;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: #6c5ce7;
            box-shadow: 0 0 0 3px rgba(108, 92, 231, 0.1);
            outline: none;
        }
        
        .select-wrapper {
            position: relative;
        }
        
        .select-wrapper:after {
            content: '\f107';
            font-family: 'Font Awesome 5 Free';
            font-weight: 900;
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            pointer-events: none;
            color: #666;
        }
        
        select.form-control {
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            padding-right: 30px;
            cursor: pointer;
        }
        
        .status-indicator {
            display: inline-block;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            margin-right: 8px;
        }
        
        .status-available {
            background-color: #2ecc71;
        }
        
        .status-unavailable {
            background-color: #e74c3c;
        }
        
        .alert {
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        
        .alert-danger {
            background-color: #fee2e2;
            border-left: 4px solid #ef4444;
            color: #b91c1c;
        }
        
        .btn {
            display: inline-block;
            padding: 12px 20px;
            font-size: 15px;
            font-weight: 500;
            text-align: center;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s ease;
            border: none;
        }
        
        .btn-primary {
            background-color: #6c5ce7;
            color: #fff;
        }
        
        .btn-primary:hover {
            background-color: #5d4ed6;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(108, 92, 231, 0.2);
        }
        
        .btn-secondary {
            background-color: #f7f7f7;
            color: #555;
        }
        
        .btn-secondary:hover {
            background-color: #eee;
        }
        
        .btn i {
            margin-right: 8px;
        }
        
        .buttons {
            display: flex;
            justify-content: flex-end;
            gap: 15px;
            margin-top: 20px;
        }
        
        .back-link {
            display: inline-flex;
            align-items: center;
            color: #555;
            text-decoration: none;
            font-weight: 500;
            font-size: 15px;
            transition: all 0.3s ease;
        }
        
        .back-link:hover {
            color: #6c5ce7;
        }
        
        .back-link i {
            margin-right: 8px;
        }
        
        .footer {
            margin-top: 30px;
            text-align: center;
            color: #888;
            font-size: 14px;
        }
        
        /* Responsive Styles */
        @media (max-width: 768px) {
            .container {
                margin: 20px auto;
            }
            
            .header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            
            .buttons {
                flex-direction: column;
                width: 100%;
            }
            
            .btn {
                width: 100%;
                margin-bottom: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2><i class="fas fa-gamepad"></i> Edit Varian PlayStation</h2>
            <a href="varian.php" class="back-link"><i class="fas fa-arrow-left"></i> Kembali ke Daftar Varian</a>
        </div>
        
        <div class="card">
            <?php if(isset($error)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> <?= $error ?>
            </div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label for="nama_varian">Nama Varian PlayStation</label>
                    <input type="text" class="form-control" id="nama_varian" name="nama_varian" value="<?= htmlspecialchars($varian['nama_varian']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="tersedia">Status Ketersediaan</label>
                    <div class="select-wrapper">
                        <select class="form-control" id="tersedia" name="tersedia">
                            <option value="1" <?= $varian['tersedia'] ? 'selected' : ''; ?>>
                                <span class="status-indicator status-available"></span> Tersedia
                            </option>
                            <option value="0" <?= !$varian['tersedia'] ? 'selected' : ''; ?>>
                                <span class="status-indicator status-unavailable"></span> Tidak Tersedia
                            </option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="stok_booking">Stok untuk Booking</label>
                    <input type="number" class="form-control" id="stok_booking" name="stok_booking" 
                           value="<?= htmlspecialchars($varian['stok_booking']); ?>" min="0" required>
                </div>
                
                <div class="form-group">
                    <label for="stok_sewa">Stok untuk Sewa</label>
                    <input type="number" class="form-control" id="stok_sewa" name="stok_sewa" 
                           value="<?= htmlspecialchars($varian['stok_sewa']); ?>" min="0" required>
                </div>
                
                <div class="buttons">
                    <a href="varian.php" class="btn btn-secondary"><i class="fas fa-times"></i> Batal</a>
                    <button type="submit" name="update" class="btn btn-primary"><i class="fas fa-save"></i> Update Varian</button>
                </div>
            </form>
        </div>
        
        <div class="footer">
            &copy; <?= date('Y') ?> PlayStation Store Admin Panel
        </div>
    </div>
</body>
</html>