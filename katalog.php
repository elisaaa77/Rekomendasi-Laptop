<?php
require 'vendor/autoload.php';
use MongoDB\Client;

session_start();

$client = new Client("mongodb://localhost:27017");
$collection = $client->ecommerce->products;
$products = $collection->find();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Katalog Produk</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f7f7f7; padding: 30px; }
        .container { max-width: 1200px; margin: auto; }
        h1 { text-align: center; margin-bottom: 30px; }
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
        }
        .card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            overflow: hidden;
            padding: 15px;
            text-align: center;
        }
        .card img {
            max-width: 100%;
            height: 180px;
            object-fit: cover;
            border-radius: 6px;
        }
        .card h3 {
            margin: 10px 0;
            color: #007bff;
        }
        .card p.price {
            color: #28a745;
            font-weight: bold;
        }
        .btn {
            display: inline-block;
            padding: 10px 15px;
            background: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 10px;
        }
        .btn:hover { background: #0056b3; }
    </style>
</head>
<body>
<div class="container">
    <h1>🛒 Katalog Laptop</h1>
    <div class="grid">
        <?php foreach ($products as $product): ?>
            <div class="card">
                <?php if (!empty($product['image'])): ?>
                    <img src="img/<?= htmlspecialchars($product['image']) ?>" alt="<?= htmlspecialchars($product['name']) ?>">
                <?php endif; ?>
                <h3><?= htmlspecialchars($product['name']) ?></h3>
                <p><?= htmlspecialchars($product['category']) ?></p>
                <p class="price">Rp <?= number_format($product['price'], 0, ',', '.') ?></p>
                <a href="beli.php?id=<?= $product['_id'] ?>" class="btn">Beli Sekarang</a>
            </div>
        <?php endforeach; ?>
    </div>
</div>
</body>
</html>