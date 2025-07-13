<?php
require 'vendor/autoload.php';
use MongoDB\Client;
use MongoDB\BSON\ObjectId;

session_start();

// Inisialisasi keranjang jika belum ada
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Cek apakah user login
if (!isset($_SESSION['user_id'])) {
    echo "Silakan login terlebih dahulu.";
    exit;
}

$client = new Client("mongodb://localhost:27017");
$collection = $client->ecommerce->products;
$users = $client->ecommerce->users;
$interactions = $client->ecommerce->interactions;

// Ambil data produk berdasarkan ID
if (!isset($_GET['id'])) {
    echo "Produk tidak ditemukan.";
    exit;
}

$product_id = $_GET['id'];

// Mengatasi ID produk yang mungkin bukan ObjectId
$product = null;
if (strlen($product_id) == 24 && ctype_xdigit($product_id)) {
    // If the product_id is 24 chars long and hex, treat as ObjectId
    try {
        $productObjectId = new ObjectId($product_id);
        $product = $collection->findOne(['_id' => $productObjectId]);
    } catch (Exception $e) {
        echo "Produk tidak ditemukan.";
        exit;
    }
} else {
    // If it's not a valid ObjectId, treat it as a string
    $product = $collection->findOne(['_id' => $product_id]);
}

if (!$product) {
    echo "Produk tidak ditemukan.";
    exit;
}

// Fungsi untuk mencatat interaksi pengguna di koleksi interactions
function catatInteraction($client, $user_id, $product_id, $action) {
    $interactions = $client->ecommerce->interactions;

    // Jika product_id bukan ObjectId, gunakan sebagai string
    if (strlen($product_id) == 24 && ctype_xdigit($product_id)) {
        $product_id = new ObjectId($product_id);
    }

    // Mencatat interaksi pengguna
    $interactions->insertOne([
        'user_id' => new ObjectId($user_id), // Menggunakan ObjectId untuk user_id
        'product_id' => $product_id, // Menggunakan ObjectId atau string untuk product_id
        'action' => $action,
        'timestamp' => new MongoDB\BSON\UTCDateTime() // Waktu interaksi
    ]);
}

// Fungsi untuk menambahkan produk ke dalam cart pengguna di database
function addToCart($client, $user_id, $product) {
    $users = $client->ecommerce->users;

    // Pastikan user_id dalam bentuk ObjectId
    try {
        $userObjectId = new ObjectId($user_id);
    } catch (Exception $e) {
        $userObjectId = $user_id;
    }
// Cek apakah produk sudah ada di cart user
$user = $users->findOne(['_id' => $userObjectId]);

if ($user && isset($user['cart'])) {
    // Cek apakah produk sudah ada di cart
    foreach ($user['cart'] as $cartItem) {
        if ($cartItem['product_id'] == $product['_id']) {
            return false; // Produk sudah ada di cart
        }
    }
}


    // Tambahkan produk ke cart di database
    $result = $users->updateOne(
        ['_id' => $userObjectId],
        ['$push' => ['cart' => [
            'product_id' => $product['_id'],
            'name' => $product['name'],
            'category' => $product['category'],
            'price' => $product['price'],
            'image' => $product['image'],
            'added_at' => new MongoDB\BSON\UTCDateTime()
        ]]]
    );

    return $result->getModifiedCount() > 0;
}

// Handle view
$user_id = $_SESSION['user_id'];
catatInteraction($client, $user_id, $product_id, 'view');

// Handle like
if (isset($_POST['like'])) {
    try {
        $productObjectId = new ObjectId($product_id);
        $collection->updateOne(
            ['_id' => $productObjectId],
            ['$inc' => ['likes' => 1]]
        );
    } catch (Exception $e) {
        $collection->updateOne(
            ['_id' => $product_id],
            ['$inc' => ['likes' => 1]]
        );
    }

    catatInteraction($client, $user_id, $product_id, 'like');
    // Refresh product data
    try {
        $productObjectId = new ObjectId($product_id);
        $product = $collection->findOne(['_id' => $productObjectId]);
    } catch (Exception $e) {
        $product = $collection->findOne(['_id' => $product_id]);
    }
}

// Handle add to cart
if (isset($_POST['add_to_cart'])) {
    // Coba tambahkan ke database terlebih dahulu
    $addResult = addToCart($client, $user_id, $product);

    if ($addResult) {
        // Jika berhasil ditambahkan ke database, sync ke session
        // Catat interaksi di database user
        catatInteraction($client, $user_id, $product_id, 'cart');

        echo "<script>alert('Produk berhasil ditambahkan ke keranjang!');</script>";
    } else {
        echo "<script>alert('Produk sudah ada di dalam keranjang!');</script>";
    }
}

// Handle pembelian
if (isset($_POST['buy'])) {
    // Catat pembelian langsung
    catatInteraction($client, $user_id, $product_id, 'purchase');

    // Redirect ke halaman checkout dengan ID produk
    header('Location: bayar.php?id=' . $product_id);
    exit;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Produk</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500&display=swap">
    <style>
        body { font-family: 'Roboto', sans-serif; background: #f8f9fa; padding: 0; margin: 0; }
        .container { max-width: 900px; margin: 40px auto; background: #fff; padding: 30px; border-radius: 10px; box-shadow: 0 5px 20px rgba(0,0,0,0.1); }
        img { max-width: 100%; border-radius: 8px; }
        h2 { font-size: 28px; color: #333; margin-bottom: 10px; }
        .price { color: #28a745; font-size: 22px; font-weight: bold; margin-top: 15px; }
        .specs, .description { margin: 15px 0; color: #555; }
        ul.specs li { margin: 5px 0; }
        .button-container { display: flex; gap: 10px; margin-top: 20px; flex-wrap: wrap; }
        .button { flex: 1; padding: 12px; background: #007bff; color: #fff; border: none; border-radius: 6px; font-size: 16px; cursor: pointer; }
        .button:hover { background: #0056b3; }
        .back { display: inline-block; margin-top: 20px; background: #6c757d; color: #fff; padding: 10px 20px; border-radius: 5px; text-decoration: none; }
    </style>
</head>
<body>
<div class="container">
    <h2><?= htmlspecialchars($product['name']) ?></h2>
    <?php if (!empty($product['image'])): ?>
        <img src="img/<?= htmlspecialchars($product['image']) ?>" alt="<?= htmlspecialchars($product['name']) ?>">
    <?php endif; ?>
    <p class="description"><strong>Kategori:</strong> <?= htmlspecialchars($product['category']) ?><br><?= htmlspecialchars($product['description']) ?></p>
    <ul class="specs">
        <li><strong>CPU:</strong> <?= isset($product['specs']['CPU']) ? $product['specs']['CPU'] : 'N/A' ?></li>
        <li><strong>RAM:</strong> <?= isset($product['specs']['RAM']) ? $product['specs']['RAM'] : 'N/A' ?></li>
        <li><strong>VGA:</strong> <?= isset($product['specs']['VGA']) ? $product['specs']['VGA'] : 'N/A' ?></li>
        <li><strong>Storage:</strong> <?= isset($product['specs']['Storage']) ? $product['specs']['Storage'] : 'N/A' ?></li>
    </ul>
    <p class="price">Rp <?= number_format($product['price'], 0, ',', '.') ?></p>
    <p> <strong>❤️Likes:</strong> <?= $product['likes'] ?? 0 ?></p>

    <form method="POST" style="margin-top: 15px;">
        <button type="submit" name="like" class="button">Like ❤️</button>
    </form>

    <div class="button-container">
        <form method="POST"><button type="submit" name="add_to_cart" class="button">Add to Cart 🛒</button></form>
        <form method="POST"><button type="submit" name="buy" class="button">Buy Now 💳</button></form>
    </div>

    <a href="index.php" class="back">⬅ Kembali</a>
</div>
</body>
</html>
        