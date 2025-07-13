<?php
session_start();

// Pastikan user login
if (!isset($_SESSION['user_id'])) {
    echo "Silakan login terlebih dahulu.";
    exit;
}

// Inisialisasi keranjang jika belum ada
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// MongoDB setup
require 'vendor/autoload.php';
use MongoDB\Client;

$client = new Client("mongodb://localhost:27017");
$usersCollection = $client->ecommerce->users;  // Users collection
$productsCollection = $client->ecommerce->products; // Products collection

// Sync cart from the database to the session
function syncCartFromDatabase($client, $user_id) {
    $users = $client->ecommerce->users;

    try {
        $userObjectId = new MongoDB\BSON\ObjectId($user_id);
    } catch (Exception $e) {
        $userObjectId = $user_id;
    }

    $user = $users->findOne(['_id' => $userObjectId]);

    if ($user && isset($user['cart'])) {
        $_SESSION['cart'] = [];
        foreach ($user['cart'] as $cartItem) {
            $_SESSION['cart'][] = [
               'product_id' => (string)$cartItem['product_id'], // ✅ Sesuai data MongoDB
                'name' => $cartItem['name'],
                'category' => $cartItem['category'],
                'price' => $cartItem['price'],
                'quantity' => $cartItem['quantity'], // Store quantity
                'image' => $cartItem['image']
            ];
        }
    }
}

$user_id = $_SESSION['user_id'];
syncCartFromDatabase($client, $user_id);

// Menghitung total harga
$totalPrice = 0;
foreach ($_SESSION['cart'] as $cartItem) {
    $cartItem = json_decode(json_encode($cartItem), true);
    if (isset($cartItem['price']) && isset($cartItem['quantity'])) {
        $totalPrice += $cartItem['price'] * $cartItem['quantity'];
    }
}

// Handle untuk menghapus item dari keranjang
if (isset($_GET['remove_id'])) {
    $removeId = $_GET['remove_id'];
    $_SESSION['cart'] = array_filter($_SESSION['cart'], function($item) use ($removeId) {
        return $item['_id'] != $removeId;
    });
    $_SESSION['cart'] = array_values($_SESSION['cart']); // Reindex array

    // Sync the updated cart to the database
    $usersCollection->updateOne(
        ['_id' => new MongoDB\BSON\ObjectId($user_id)],
        ['$set' => ['cart' => $_SESSION['cart']]]
    );

    header('Location: cart.php');
    exit;
}

// Handle untuk update jumlah item
if (isset($_POST['update_quantity'])) {
    $updatedCart = [];
    foreach ($_SESSION['cart'] as $item) {
        if ($item['product_id'] === $_POST['product_id']) {
            $item['quantity'] = $_POST['quantity']; // Update quantity
        }
        $updatedCart[] = $item;
    }
    $_SESSION['cart'] = $updatedCart;

    // Sync the updated cart to the database
    $usersCollection->updateOne(
        ['_id' => new MongoDB\BSON\ObjectId($user_id)],
        ['$set' => ['cart' => $_SESSION['cart']]]
    );

    header('Location: cart.php');
    exit;
}

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Keranjang Belanja</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500&display=swap">
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f7f7f7;
        }
        .container {
            max-width: 800px;
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
        .cart-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #ddd;
        }
        .cart-item h3 {
            margin: 0;
        }
        .cart-item .price {
            color: #28a745;
            font-weight: bold;
        }
        .cart-item .quantity {
            width: 50px;
        }
        .total-price {
            font-size: 18px;
            font-weight: bold;
            margin-top: 20px;
            text-align: right;
        }
        .btn {
            padding: 12px 25px;
            font-size: 16px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            width: 100%;
            margin-top: 30px;
        }
        .btn:hover {
            background-color: #0056b3;
        }
        .back-btn {
            padding: 12px 25px;
            font-size: 16px;
            background-color: #6c757d;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            width: 100%;
            margin-top: 10px;
            text-align: center;
            text-decoration: none;
        }
        .back-btn:hover {
            background-color: #5a6268;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Keranjang Belanja</h1>

        <?php if (empty($_SESSION['cart'])): ?>
            <p>Keranjang belanja Anda kosong.</p>
        <?php else: ?>
            <?php foreach ($_SESSION['cart'] as $cartItem): ?>
                <div class="cart-item">
                    <div>
                        <h3><?= htmlspecialchars($cartItem['name']) ?></h3>
                        <p>Kategori: <?= htmlspecialchars($cartItem['category']) ?></p>
                        <form method="POST">
                           <input type="hidden" name="product_id" value="<?= htmlspecialchars($cartItem['product_id']) ?>">

                            <input type="number" name="quantity" class="quantity" value="<?= $cartItem['quantity'] ?? 1 ?>" min="1">
                            <button type="submit" name="update_quantity" class="btn">Update Quantity</button>
                        </form>
                    </div>
                    <div class="price">Rp <?= number_format($cartItem['price'], 0, ',', '.') ?> x <?= $cartItem['quantity'] ?? 1 ?></div>
                    <a href="cart.php?remove_id=<?= $cartItem['product_id'] ?>" class="btn" style="background-color: #dc3545;">Hapus</a>
                </div>
            <?php endforeach; ?>

            <div class="total-price">
                Total: Rp <?= number_format($totalPrice, 0, ',', '.') ?>
            </div>

            <form method="POST" action="checkout.php">
                <button type="submit" class="btn">Lanjutkan ke Pembayaran</button>
            </form>
        <?php endif; ?>

        <a href="index.php" class="back-btn">⬅️ Kembali ke Dashboard</a>
    </div>
</body>
</html>
