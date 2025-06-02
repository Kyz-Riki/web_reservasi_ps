<?php
session_start();
include('../config/db.php');

if ($_SESSION['role'] != 'admin') {
    header('Location: ../auth/login.php');
    exit;
}

if (isset($_GET['id'])) {
    $id = mysqli_real_escape_string($conn, $_GET['id']);
    
    // Instead of deleting the record, update is_deleted to 1
    $query = "UPDATE varian_ps SET is_deleted = 1 WHERE id = '$id'";
    
    if (mysqli_query($conn, $query)) {
        header('Location: varian.php?status=deleted');
    } else {
        header('Location: varian.php?status=error');
    }
} else {
    header('Location: varian.php');
}
?>