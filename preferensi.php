<?php
session_start();
require 'vendor/autoload.php';
use MongoDB\Client;

// Pastikan user login
if (!isset($_SESSION['user_id'])) {
    echo "Silakan login terlebih dahulu.";
    exit;
}

// Inisialisasi koneksi MongoDB
$client = new Client("mongodb://localhost:27017");
$usersCollection = $client->ecommerce->users;  // Koleksi Users
$productsCollection = $client->ecommerce->products; // Koleksi Products

// Ambil data user berdasarkan ID
$user_id = $_SESSION['user_id'];
$user = $usersCollection->findOne(['_id' => new MongoDB\BSON\ObjectId($user_id)]);

// Ambil daftar kategori dan tag dari produk MongoDB
$categories = $productsCollection->distinct('category'); // Mengambil kategori unik dari produk
$tags = $productsCollection->distinct('tags'); // Mengambil tag unik dari produk

// Proses preferensi yang dikirimkan
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $preferred_categories = isset($_POST['categories']) ? $_POST['categories'] : [];
    $preferred_tags = isset($_POST['tags']) ? $_POST['tags'] : [];

    // Update preferensi di database user
    $usersCollection->updateOne(
        ['_id' => new MongoDB\BSON\ObjectId($user_id)],
        [
            '$set' => [
                'preferences' => array_merge($preferred_categories, $preferred_tags) // Menyimpan preferensi
            ]
        ]
    );

    echo "<script>alert('Preferensi berhasil disimpan!'); window.location='index.php';</script>";
}

// Cek apakah user sudah memiliki preferensi atau belum
$hasPreferences = !empty($user['preferences']);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Form Input Preferensi</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f7f7f7;
            padding: 50px;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }
        h2 {
            text-align: center;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
        }
        select, input[type="checkbox"] {
            width: 100%;
            padding: 10px;
            margin-bottom: 10px;
        }
        button {
            width: 100%;
            padding: 12px;
            font-size: 16px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        button:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>

<div class="container">
    <h2>Form Input Preferensi</h2>
    
    <?php if (!$hasPreferences): ?>
        <!-- If user doesn't have preferences, show form to input preferences -->
        <form method="POST">

            <!-- Preferensi Kategori -->
            <div class="form-group">
                <label for="categories">Pilih Kategori Favorit Anda:</label>
                <div>
                    <?php foreach ($categories as $category): ?>
                        <label>
                            <input type="checkbox" name="categories[]" value="<?= htmlspecialchars($category) ?>"> <?= htmlspecialchars($category) ?>
                        </label><br>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Preferensi Tag -->
            <div class="form-group">
                <label for="tags">Pilih Tag Favorit Anda:</label>
                <div>
                    <?php foreach ($tags as $tag): ?>
                        <label>
                            <input type="checkbox" name="tags[]" value="<?= htmlspecialchars($tag) ?>"> <?= htmlspecialchars($tag) ?>
                        </label><br>
                    <?php endforeach; ?>
                </div>
            </div>

            <button type="submit">Simpan Preferensi</button>
        </form>
    <?php else: ?>
        <!-- If user has preferences, show them -->
        <p>Preferensi Anda sudah diset:</p>
        <ul>
            <li><strong>Preferensi Kategori:</strong> <?= implode(', ', $user['preferences']) ?></li>
        </ul>
        <p>Jika ingin memperbarui preferensi, silakan lakukan perubahan pada form di atas.</p>
    <?php endif; ?>
</div>

</body>
</html>
