<?php
session_start();
require 'vendor/autoload.php';
use MongoDB\Client;
use MongoDB\BSON\ObjectId;

$client = new Client("mongodb://localhost:27017");
$db = $client->ecommerce;
$products = $db->products;
$users = $db->users;
$orders = $db->orders;

// Cek login
if (!isset($_SESSION['user_id'])) {
    echo "Silakan login terlebih dahulu.";
    exit;
}

$userId = $_SESSION['user_id'];

// Fungsi untuk mengambil user data
function getUserData($client, $userId) {
    $users = $client->ecommerce->users;
    
    try {
        $userObjectId = new ObjectId($userId);
    } catch (Exception $e) {
        $userObjectId = $userId;
    }
    
    return $users->findOne(['_id' => $userObjectId]);
}

// Fungsi mencatat ke history
function catatHistory($client, $userId, $productId, $action) {
    $users = $client->ecommerce->users;
    
    try {
        $userObjectId = new ObjectId($userId);
    } catch (Exception $e) {
        $userObjectId = $userId;
    }
    
    $users->updateOne(
        ['_id' => $userObjectId],
        ['$push' => ['history' => [
            'product_id' => $productId,
            'action' => $action,
            'timestamp' => new MongoDB\BSON\UTCDateTime()
        ]]]
    );
}

// Fungsi untuk membuat order
function createOrder($client, $userId, $items, $totalPrice, $customerData) {
    $orders = $client->ecommerce->orders;
    
    $orderId = 'ORDER-' . time() . '-' . rand(1000, 9999);
    
    $orderData = [
        'order_id' => $orderId,
        'user_id' => $userId,
        'items' => $items,
        'total_price' => $totalPrice,
        'customer_info' => $customerData,
        'status' => 'pending',
        'created_at' => new MongoDB\BSON\UTCDateTime(),
        'updated_at' => new MongoDB\BSON\UTCDateTime()
    ];
    
    $result = $orders->insertOne($orderData);
    
    return $result->getInsertedId() ? $orderId : false;
}

// Fungsi untuk menghapus semua item dari cart
function clearCart($client, $userId) {
    $users = $client->ecommerce->users;
    
    try {
        $userObjectId = new ObjectId($userId);
    } catch (Exception $e) {
        $userObjectId = $userId;
    }
    
    $users->updateOne(
        ['_id' => $userObjectId],
        ['$set' => ['cart' => []]]
    );
}

// Ambil data user
$userData = getUserData($client, $userId);
$cartItems = [];
$totalPrice = 0;

// Cek apakah checkout dari single product atau cart
if (isset($_GET['id'])) {
    // Checkout single product
    $productId = $_GET['id'];
    
    try {
        $productObjectId = new ObjectId($productId);
        $product = $products->findOne(['_id' => $productObjectId]);
    } catch (Exception $e) {
        $product = $products->findOne(['_id' => $productId]);
    }
    
    if (!$product) {
        echo "Produk tidak ditemukan.";
        exit;
    }
    
    $cartItems = [
        [
            'product_id' => $product['_id'],
            'name' => $product['name'],
            'category' => $product['category'],
            'price' => $product['price'],
            'image' => $product['image'],
            'quantity' => $product['quantity']
        ]
    ];
    
    $totalPrice = $product['price'];
    
    // Catat view untuk single product
    catatHistory($client, $userId, $productId, 'view');
    
} else {
    // Checkout dari cart
    if ($userData && isset($userData['cart']) && !empty($userData['cart'])) {
        $cartItems = $userData['cart'];
        foreach ($cartItems as $item) {
            $totalPrice += $item['price'];
        }
    } else {
        echo "<script>alert('Keranjang Anda kosong!'); window.location='index.php';</script>";
        exit;
    }
}

// Handle form submission
if (isset($_POST['place_order'])) {
    // Validasi input
    $errors = [];
    
    if (empty($_POST['name'])) $errors[] = "Nama wajib diisi";
    if (empty($_POST['email'])) $errors[] = "Email wajib diisi";
    if (empty($_POST['phone'])) $errors[] = "Nomor telepon wajib diisi";
    if (empty($_POST['address'])) $errors[] = "Alamat wajib diisi";
    if (empty($_POST['payment_method'])) $errors[] = "Metode pembayaran wajib dipilih";
    
    if (empty($errors)) {
        // Data customer
        $customerData = [
            'name' => $_POST['name'],
            'email' => $_POST['email'],
            'phone' => $_POST['phone'],
            'address' => $_POST['address'],
            'payment_method' => $_POST['payment_method']
        ];
        
        // Buat order
        $orderId = createOrder($client, $userId, $cartItems, $totalPrice, $customerData);
        
        if ($orderId) {
            // Catat history untuk semua item
            foreach ($cartItems as $item) {
                catatHistory($client, $userId, $item['product_id'], 'purchase');
            }
            
            // Hapus cart jika checkout dari cart (bukan single product)
            if (!isset($_GET['id'])) {
                clearCart($client, $userId);
            }
            
            echo "<script>
                alert('✅ Pesanan berhasil dibuat! Order ID: $orderId');
                window.location='index.php';
            </script>";
            exit;
        } else {
            $errors[] = "Terjadi kesalahan saat membuat pesanan";
        }
    }
}

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Konfirmasi Pembelian</title>
    <style>
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
            margin: 0; 
            padding: 20px;
            min-height: 100vh;
        }
        .container { 
            max-width: 800px; 
            margin: 0 auto; 
            background: #fff; 
            border-radius: 15px; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.2); 
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: 300;
        }
        .content {
            padding: 30px;
        }
        .section {
            margin-bottom: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 10px;
            border-left: 4px solid #667eea;
        }
        .section h2 {
            margin-top: 0;
            color: #333;
            font-size: 20px;
        }
        .item {
            display: flex;
            align-items: center;
            padding: 15px;
            background: white;
            margin-bottom: 10px;
            border-radius: 8px;
            border: 1px solid #e0e0e0;
        }
        .item img {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 5px;
            margin-right: 15px;
        }
        .item-details {
            flex: 1;
        }
        .item-name {
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
        }
        .item-category {
            color: #666;
            font-size: 14px;
        }
        .item-price {
            color: #28a745;
            font-weight: bold;
            font-size: 16px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #333;
        }
        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s;
            box-sizing: border-box;
        }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
        }
        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }
        .total-section {
            background: #e8f5e8;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .total-price {
            font-size: 24px;
            font-weight: bold;
            color: #28a745;
            text-align: right;
        }
        .btn {
            display: block;
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            text-align: center;
            border: none;
            border-radius: 8px;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s;
        }
        .btn:hover {
            transform: translateY(-2px);
        }
        .btn:active {
            transform: translateY(0);
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .back-link {
            display: inline-block;
            margin-top: 20px;
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }
        .back-link:hover {
            text-decoration: underline;
        }
        .payment-methods {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }
        .payment-option {
            flex: 1;
            min-width: 120px;
        }
        .payment-option input[type="radio"] {
            display: none;
        }
        .payment-option label {
            display: block;
            padding: 15px;
            background: white;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
        }
        .payment-option input[type="radio"]:checked + label {
            border-color: #667eea;
            background: #f0f2ff;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🛒 Konfirmasi Pembelian</h1>
        </div>
        
        <div class="content">
            <?php if (!empty($errors)): ?>
                <div class="error">
                    <strong>Terjadi kesalahan:</strong>
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?= htmlspecialchars($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <div class="section">
                <h2>📦 Ringkasan Pesanan</h2>
                <?php foreach ($cartItems as $item): ?>
                    <div class="item">
                        <?php if (!empty($item['image'])): ?>
                            <img src="img/<?= htmlspecialchars($item['image']) ?>" alt="<?= htmlspecialchars($item['name']) ?>">
                        <?php endif; ?>
                        <div class="item-details">
                            <div class="item-name"><?= htmlspecialchars($item['name']) ?></div>
                            <div class="item-category"><?= htmlspecialchars($item['category']) ?></div>
                        </div>
                        <div class="item-price">Rp <?= number_format($item['price'], 0, ',', '.') ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="total-section">
                <div class="total-price">Total: Rp <?= number_format($totalPrice, 0, ',', '.') ?></div>
            </div>
            
            <form method="POST">
                <div class="section">
                    <h2>📋 Informasi Pelanggan</h2>
                    
                    <div class="form-group">
                        <label for="name">Nama Lengkap *</label>
                        <input type="text" id="name" name="name" value="<?= isset($userData['name']) ? htmlspecialchars($userData['name']) : '' ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email *</label>
                        <input type="email" id="email" name="email" value="<?= isset($userData['email']) ? htmlspecialchars($userData['email']) : '' ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="phone">Nomor Telepon *</label>
                        <input type="tel" id="phone" name="phone" value="<?= isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : '' ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="address">Alamat Lengkap *</label>
                        <textarea id="address" name="address" placeholder="Masukkan alamat lengkap termasuk kode pos" required><?= isset($_POST['address']) ? htmlspecialchars($_POST['address']) : '' ?></textarea>
                    </div>
                </div>
                
                <div class="section">
                    <h2>💳 Metode Pembayaran</h2>
                    <div class="payment-methods">
                        <div class="payment-option">
                            <input type="radio" id="bank_transfer" name="payment_method" value="bank_transfer" required>
                            <label for="bank_transfer">
                                🏦<br>Transfer Bank
                            </label>
                        </div>
                        <div class="payment-option">
                            <input type="radio" id="credit_card" name="payment_method" value="credit_card">
                            <label for="credit_card">
                                💳<br>Kartu Kredit
                            </label>
                        </div>
                        <div class="payment-option">
                            <input type="radio" id="e_wallet" name="payment_method" value="e_wallet">
                            <label for="e_wallet">
                                📱<br>E-Wallet
                            </label>
                        </div>
                        <div class="payment-option">
                            <input type="radio" id="cod" name="payment_method" value="cod">
                            <label for="cod">
                                💰<br>COD
                            </label>
                        </div>
                    </div>
                </div>
                
                <button type="submit" name="place_order" class="btn">
                    🛍️ Buat Pesanan
                </button>
            </form>
            
            <a href="<?= isset($_GET['id']) ? 'product.php?id=' . $_GET['id'] : 'cart.php' ?>" class="back-link">
                ← Kembali
            </a>
        </div>
    </div>
</body>
</html>