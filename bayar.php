<?php
require 'vendor/autoload.php';
use MongoDB\Client;

session_start();

// Pastikan user login
if (!isset($_SESSION['user_id'])) {
    echo "Silakan login terlebih dahulu.";
    exit;
}

if (!isset($_GET['id'])) {
    echo "Produk tidak ditemukan.";
    exit;
}

$client = new Client("mongodb://localhost:27017");
$productId = $_GET['id'];
$product = $client->ecommerce->products->findOne(['_id' => $productId]);

if (!$product) {
    echo "Produk tidak ditemukan.";
    exit;
}

if (isset($_POST['place_order'])) {
    $client->ecommerce->users->updateOne(
        ['_id' => $_SESSION['user_id']],
        ['$push' => ['history' => [
            'product_id' => $productId,
            'action' => 'confirm_purchase',
            'timestamp' => date('Y-m-d H:i:s')
        ]]]
    );

    echo "<script>alert('✅ Pembelian berhasil!'); window.location='index.php';</script>";
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500&display=swap">
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f7f7f7;
        }
        .container {
            max-width: 600px;
            margin: 50px auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            box-sizing: border-box;
        }
        h1 {
            text-align: center;
            color: #333;
        }
        .product-info {
            margin-bottom: 20px;
        }
        .price {
            font-size: 20px;
            color: #28a745;
            font-weight: bold;
        }
        .btn {
            display: block;
            width: 100%;
            padding: 12px;
            background: #007bff;
            color: white;
            text-align: center;
            border: none;
            border-radius: 5px;
            margin-top: 20px;
            cursor: pointer;
            font-size: 16px;
        }
        .btn:hover {
            background: #0056b3;
        }
        .back-btn {
            display: inline-block;
            margin-top: 20px;
            background: #6c757d;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            font-size: 16px;
            text-align: center;
        }
        .back-btn:hover {
            background: #5a6268;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Konfirmasi Pembelian</h1>
        
        <div class="product-info">
            <h2><?= htmlspecialchars($product['name']) ?></h2>
            <p><strong>Kategori:</strong> <?= htmlspecialchars($product['category']) ?></p>
            <p class="price">Rp <?= number_format($product['price'], 0, ',', '.') ?></p>
        </div>
        
        <form method="POST">
            <button type="submit" name="place_order" class="btn">Konfirmasi Beli</button>
        </form>

        <a href="index.php" class="back-btn">⬅️ Kembali ke Dashboard</a>
    </div>
</body>
</html>
