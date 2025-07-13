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
$usersCollection = $client->ecommerce->users;  // Koleksi Users
$productsCollection = $client->ecommerce->products; // Koleksi Products
$interactionsCollection = $client->ecommerce->interactions; // Koleksi Interactions

// Ambil data user berdasarkan ID
$user_id = $_SESSION['user_id'];
$user = $usersCollection->findOne(['_id' => new ObjectId($user_id)]);

// Ambil daftar kategori dan tag dari produk MongoDB
$categories = $productsCollection->distinct('category'); // Mengambil kategori unik dari produk
$tags = $productsCollection->distinct('tags'); // Mengambil tag unik dari produk

// Ambil semua produk
$products = $productsCollection->find()->toArray(); // Ambil produk dalam bentuk array

// Proses preferensi yang dikirimkan
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $preferred_categories = isset($_POST['categories']) ? $_POST['categories'] : [];
    $preferred_tags = isset($_POST['tags']) ? $_POST['tags'] : [];

    // Pastikan bahwa kategori dan tag adalah array
    if (!is_array($preferred_categories)) {
        $preferred_categories = [];
    }
    if (!is_array($preferred_tags)) {
        $preferred_tags = [];
    }

    // Update preferensi di database user
    $usersCollection->updateOne(
        ['_id' => new ObjectId($user_id)],
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

// Ambil produk yang sering dibeli bersamaan (Berdasarkan tindakan 'purchase' di koleksi Interactions)
$recommendedProductsData = $interactionsCollection->aggregate([
    [
        '$match' => ['user_id' => new ObjectId($user_id), 'action' => 'purchase']
    ],
    [
        '$group' => [
            '_id' => '$product_id',
            'count' => ['$sum' => 1]
        ]
    ],
    [
        '$sort' => ['count' => -1],
    ],
    ['$limit' => 5] // Ambil 5 produk yang paling sering dibeli bersama
])->toArray();

// Konversi hasil agregasi menjadi array product_ids
$recommendedProductIds = array_map(function($item) {
    return $item['_id'];
}, $recommendedProductsData);

// Ambil detail produk berdasarkan ID yang direkomendasikan
$recommendedProducts = [];
if (!empty($recommendedProductIds)) {
    $recommendedProducts = $productsCollection->find([
        '_id' => ['$in' => $recommendedProductIds]
    ])->toArray();
}

// Ambil preferensi user untuk rekomendasi
$preferred_categories = isset($user['preferences']) ? $user['preferences'] : [];
$preferred_tags = isset($user['preferences']) ? $user['preferences'] : [];

// Ambil produk serupa berdasarkan kategori (Preferensi kategori yang dipilih oleh user)
$recommendedByCategory = [];
if (!empty($preferred_categories)) {
    $recommendedByCategory = $productsCollection->find([
        'category' => ['$in' => $preferred_categories],
        '_id' => ['$ne' => new ObjectId($user_id)] // Jangan rekomendasikan produk yang sama
    ])->toArray();
}

// Ambil produk serupa berdasarkan tag (Preferensi tag yang dipilih oleh user)
$recommendedByTag = [];
if (!empty($preferred_tags)) {
    $recommendedByTag = $productsCollection->find([
        'tags' => ['$in' => $preferred_tags],
        '_id' => ['$ne' => new ObjectId($user_id)] // Jangan rekomendasikan produk yang sama
    ])->toArray();
}

// Ambil produk populer berdasarkan preferensi serupa pengguna
$recommendedByUserPreferencesData = [];
if (!empty($preferred_categories) || !empty($preferred_tags)) {
    // Dapatkan user lain dengan preferensi yang serupa
    $similarUsers = $usersCollection->find([
        'preferences' => ['$in' => $preferred_categories] // Menggunakan kategori untuk menemukan pengguna serupa
    ])->toArray();

    // Ambil produk yang banyak dibeli oleh pengguna-pengguna serupa
    if (!empty($similarUsers)) {
        $similarUserIds = array_map(function($user) { return $user['_id']; }, $similarUsers);
        
        $recommendedByUserPreferencesData = $interactionsCollection->aggregate([
            [
                '$match' => ['user_id' => ['$in' => $similarUserIds], 'action' => 'purchase']
            ],
            [
                '$group' => [
                    '_id' => '$product_id',
                    'popularity' => ['$sum' => 1]
                ]
            ],
            [
                '$sort' => ['popularity' => -1]
            ],
            ['$limit' => 5] // Ambil produk paling populer
        ])->toArray();
    }
}

// Konversi hasil agregasi menjadi produk detail
$recommendedByUserPreferences = [];
if (!empty($recommendedByUserPreferencesData)) {
    $popularProductIds = array_map(function($item) {
        return $item['_id'];
    }, $recommendedByUserPreferencesData);
    
    $recommendedByUserPreferences = $productsCollection->find([
        '_id' => ['$in' => $popularProductIds]
    ])->toArray();
}

// Gabungkan semua rekomendasi dan hilangkan duplikasi
$allRecommendations = array_merge($recommendedProducts, $recommendedByCategory, $recommendedByTag, $recommendedByUserPreferences);
$uniqueRecommendations = [];
$seenIds = [];

foreach ($allRecommendations as $product) {
    $productId = (string)$product['_id'];
    if (!in_array($productId, $seenIds)) {
        $seenIds[] = $productId;
        $uniqueRecommendations[] = $product;
    }
}

// Batasi rekomendasi maksimal 10 produk
$finalRecommendations = array_slice($uniqueRecommendations, 0, 10);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Katalog Produk</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500&display=swap">
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f7f7f7;
        }

        /* Navbar Style */
        .navbar {
            background-color: #007bff;
            color: white;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .navbar a {
            color: white;
            text-decoration: none;
            padding: 10px 15px;
            font-size: 16px;
            border-radius: 4px;
            transition: background-color 0.3s ease;
        }

        .navbar a:hover {
            background-color: #0056b3;
        }

        .container {
            width: 100%;
            max-width: 1200px;
            margin: 50px auto;
            padding: 20px;
            background-color: #fff;
            box-sizing: border-box;
        }

        h1 {
            text-align: center;
            font-size: 36px;
            margin-bottom: 30px;
            color: #333;
        }

        .tab-btn {
            background-color: #f0f0f0;
            border: 1px solid #ccc;
            padding: 10px 25px;
            cursor: pointer;
            font-size: 16px;
            margin: 0 10px;
            border-radius: 5px;
            transition: background-color 0.3s ease;
        }

        .tab-btn:hover {
            background-color: #ddd;
        }

        .tab-btn.active {
            background-color: #007bff;
            color: white;
            border-color: #007bff;
        }

        .catalog, .recommended {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            justify-content: center;
        }

        .product-card {
            background-color: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            text-align: center;
            overflow: hidden;
            transition: transform 0.3s ease;
            padding: 15px;
        }

        .product-card:hover {
            transform: translateY(-10px);
        }

        .product-card img {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-bottom: 1px solid #ddd;
            margin-bottom: 10px;
        }

        .product-card h3 {
            font-size: 18px;
            margin: 10px 0;
            color: #333;
        }

        .product-card p {
            font-size: 14px;
            color: #777;
        }

        .product-card .price {
            font-size: 20px;
            color: #28a745;
            font-weight: bold;
            margin: 15px 0;
        }

        .product-card .btn {
            background-color: #007bff;
            color: white;
            padding: 8px 16px;
            text-decoration: none;
            border-radius: 4px;
            display: inline-block;
            margin-top: 10px;
            transition: background-color 0.3s ease;
        }

        .product-card .btn:hover {
            background-color: #0056b3;
        }

        .no-recommendations {
            text-align: center;
            color: #666;
            font-size: 16px;
            margin: 50px 0;
        }

        /* Responsive Design for Navbar */
        @media (max-width: 768px) {
            .navbar {
                flex-direction: column;
                align-items: flex-start;
            }

            .tab-btn {
                margin-bottom: 10px;
            }
        }
    </style>
</head>
<body>

<!-- Navbar -->
<div class="navbar">
    <div>
        <a href="index.php">Home</a>
    </div>
    <div>
        <a href="cart.php">Cart 🛒</a>
        <a href="preferensi.php">Preferensi</a>
        <a href="logout.php" class="logout-btn">Logout</a>
    </div>
</div>

<div class="container">
    <h1>🛒 Katalog Produk</h1>

    <!-- Tab Navigation -->
    <div style="text-align:center; margin-bottom: 30px;">
        <button class="tab-btn active" onclick="showSection('catalog')">🛍️ Semua Produk</button>
        <button class="tab-btn" onclick="showSection('recommended')">🎯 Rekomendasi (<?= count($finalRecommendations) ?>)</button>
    </div>

    <!-- Katalog Semua Produk -->
    <div class="catalog" id="catalog">
        <?php foreach ($products as $product): ?>
            <div class="product-card">
                <!-- Check if the 'image' field exists -->
                <?php if (!empty($product['image'])): ?>
                    <img src="img/<?= htmlspecialchars($product['image']) ?>" alt="<?= htmlspecialchars($product['name'] ?? 'No Name') ?>">
                <?php else: ?>
                    <img src="img/default-image.jpg" alt="No Image Available">  <!-- Default image if no image is available -->
                <?php endif; ?>

                <!-- Check if the 'name' field exists -->
                <h3>
                    <?= isset($product['name']) ? htmlspecialchars($product['name']) : 'No Name' ?>
                </h3>

                <!-- Check if the 'category' field exists -->
                <p>
                    <?= isset($product['category']) ? htmlspecialchars($product['category']) : 'No Category' ?>
                </p>

                <!-- Check if the 'price' field exists -->
                <div class="price">
                    Rp <?= isset($product['price']) ? number_format($product['price'], 0, ',', '.') : '0' ?>
                </div>
                
                <a href="beli.php?id=<?= $product['_id'] ?>" class="btn">Lihat Detail</a>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Rekomendasi Produk -->
    <div class="recommended" id="recommended" style="display:none;">
        <?php if (!empty($finalRecommendations)): ?>
            <?php foreach ($finalRecommendations as $product): ?>
                <div class="product-card">
                    <!-- Check if the 'image' field exists -->
                    <?php if (!empty($product['image'])): ?>
                        <img src="img/<?= htmlspecialchars($product['image']) ?>" alt="<?= htmlspecialchars($product['name'] ?? 'No Name') ?>">
                    <?php else: ?>
                        <img src="img/default-image.jpg" alt="No Image Available">
                    <?php endif; ?>

                    <!-- Check if the 'name' field exists -->
                    <h3>
                        <?= isset($product['name']) ? htmlspecialchars($product['name']) : 'No Name' ?>
                    </h3>

                    <!-- Check if the 'category' field exists -->
                    <p>
                        <?= isset($product['category']) ? htmlspecialchars($product['category']) : 'No Category' ?>
                    </p>

                    <!-- Check if the 'price' field exists -->
                    <div class="price">
                        Rp <?= isset($product['price']) ? number_format($product['price'], 0, ',', '.') : '0' ?>
                    </div>
                    
                    <a href="beli.php?id=<?= $product['_id'] ?>" class="btn">Lihat Detail</a>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="no-recommendations">
                <h3>Belum Ada Rekomendasi</h3>
                <p>Mulai berbelanja atau atur preferensi Anda untuk mendapatkan rekomendasi produk yang sesuai.</p>
                <a href="preferensi.php" class="btn">Atur Preferensi</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Tab Switching Script -->
<script>
    function showSection(section) {
        document.getElementById('catalog').style.display = section === 'catalog' ? 'grid' : 'none';
        document.getElementById('recommended').style.display = section === 'recommended' ? 'grid' : 'none';

        const buttons = document.querySelectorAll('.tab-btn');
        buttons.forEach(btn => btn.classList.remove('active'));

        if (section === 'catalog') {
            buttons[0].classList.add('active');
        } else {
            buttons[1].classList.add('active');
        }
    }
</script>

</body>
</html>