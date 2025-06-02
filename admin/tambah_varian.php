<?php
session_start();
include('../config/db.php');

if ($_SESSION['role'] != 'admin') {
    header('Location: ../auth/login.php');
    exit;
}

if (isset($_POST['simpan'])) {
    $nama_varian = htmlspecialchars($_POST['nama_varian']);
    $tersedia = $_POST['tersedia'];
    $stok_booking = (int)$_POST['stok_booking'];
    $stok_sewa = (int)$_POST['stok_sewa'];

    mysqli_query($conn, "INSERT INTO varian_ps (nama_varian, tersedia, stok_booking, stok_sewa) 
                        VALUES ('$nama_varian', '$tersedia', '$stok_booking', '$stok_sewa')");

    $_SESSION['success'] = "Varian PS berhasil ditambahkan!";
    header('Location: varian.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Varian PS</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Poppins Font -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fc;
        }
        .card {
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            border: none;
        }
        .btn-primary {
            background-color: #6c5ce7;
            border-color: #6c5ce7;
            font-weight: 500;
            border-radius: 8px;
            padding: 10px 20px;
            transition: all 0.3s;
        }
        .btn-primary:hover {
            background-color: #5649c0;
            border-color: #5649c0;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(108, 92, 231, 0.3);
        }
        .card-header {
            background: linear-gradient(to right, #6c5ce7, #5649c0);
            color: white;
            border-radius: 15px 15px 0 0 !important;
            font-weight: 600;
            letter-spacing: 0.5px;
        }
        .back-link {
            color: #6c5ce7;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s;
        }
        .back-link:hover {
            color: #5649c0;
            text-decoration: underline;
        }
        .form-label {
            font-weight: 500;
            color: #555;
        }
        .form-control, .form-select {
            border-radius: 8px;
            padding: 10px 15px;
            border: 1px solid #e0e0e0;
            font-size: 14px;
        }
        .form-control:focus, .form-select:focus {
            box-shadow: 0 0 0 3px rgba(108, 92, 231, 0.25);
            border-color: #6c5ce7;
        }
        .page-title {
            font-weight: 700;
            color: #333;
            margin-bottom: 25px;
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-6 col-md-8">
                <div class="card">
                    <div class="card-header py-3">
                        <h5 class="mb-0"><i class="fas fa-plus-circle me-2"></i> Tambah Varian PS</h5>
                    </div>
                    <div class="card-body p-4">
                        <form method="POST">
                            <div class="mb-3">
                                <label for="nama_varian" class="form-label">Nama Varian PS</label>
                                <input type="text" class="form-control" id="nama_varian" name="nama_varian" placeholder="Masukkan nama varian PS" required>
                            </div>
                            <div class="mb-3">
                                <label for="tersedia" class="form-label">Status Ketersediaan</label>
                                <select class="form-select" id="tersedia" name="tersedia">
                                    <option value="1">Tersedia</option>
                                    <option value="0">Tidak Tersedia</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="stok_booking" class="form-label">Stok untuk Booking</label>
                                <input type="number" class="form-control" id="stok_booking" name="stok_booking" 
                                       value="0" min="0" required>
                            </div>
                            <div class="mb-3">
                                <label for="stok_sewa" class="form-label">Stok untuk Sewa</label>
                                <input type="number" class="form-control" id="stok_sewa" name="stok_sewa" 
                                       value="0" min="0" required>
                            </div>
                            <div class="d-grid">
                                <button type="submit" name="simpan" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Simpan
                                </button>
                            </div>
                        </form>
                    </div>
                    <div class="card-footer bg-white text-center py-3" style="border-radius: 0 0 15px 15px;">
                        <a href="varian.php" class="back-link">
                            <i class="fas fa-arrow-left me-1"></i> Kembali ke Daftar Varian
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>