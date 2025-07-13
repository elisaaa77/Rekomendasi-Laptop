<?php
require '../vendor/autoload.php';
use MongoDB\Client;

$client = new Client("mongodb://localhost:27017");
$collection = $client->ecommerce->products;

$id = $_GET['id'] ?? null;
if (!$id) {
    echo "ID produk tidak ditemukan.";
    exit;
}

$product = $collection->findOne(['_id' => $id]);
if (!$product) {
    echo "Produk tidak ditemukan.";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle upload gambar
    $imageName = $product['image']; // default: gambar lama
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $tmpName = $_FILES['image']['tmp_name'];
        $imageName = basename($_FILES['image']['name']);
        move_uploaded_file($tmpName, "../img/" . $imageName);
    }

    // Update data
    $updateData = [
        'name' => $_POST['name'],
        'category' => $_POST['category'],
        'description' => $_POST['description'],
        'specs' => [
            'CPU' => $_POST['cpu'],
            'RAM' => $_POST['ram'],
            'VGA' => $_POST['vga'],
            'Storage' => $_POST['storage'],
        ],
        'price' => (int) $_POST['price'],
        'rating' => (float) $_POST['rating'],
        'image' => $imageName
    ];
    $collection->updateOne(['_id' => $id], ['$set' => $updateData]);

    header("Location: index.php"); // kembali ke halaman daftar produk
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Edit Produk</title>
    <style>
        body { font-family: Arial; background: #f4f4f4; padding: 40px; }
        form { background: white; padding: 30px; max-width: 600px; margin: auto; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        input, textarea { width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ccc; border-radius: 4px; }
        button { background: #007bff; color: white; border: none; padding: 10px 20px; border-radius: 4px; }
        img { max-width: 100%; margin-bottom: 15px; border-radius: 8px; }
    </style>
</head>
<body>
    <form method="POST" enctype="multipart/form-data">
        <h2>Edit Produk</h2>


        <input type="text" name="name" value="<?= $product['name'] ?>" required>
        <input type="text" name="category" value="<?= $product['category'] ?>" required>
        <textarea name="description" required><?= $product['description'] ?></textarea>
        <input type="text" name="cpu" value="<?= $product['specs']['CPU'] ?>" required>
        <input type="text" name="ram" value="<?= $product['specs']['RAM'] ?>" required>
        <input type="text" name="vga" value="<?= $product['specs']['VGA'] ?>" required>
        <input type="text" name="storage" value="<?= $product['specs']['Storage'] ?>" required>
        <input type="number" name="price" value="<?= $product['price'] ?>" required>
        <!-- Menampilkan gambar produk -->
        <?php if (!empty($product['image'])): ?>
            <img src="../img/<?= htmlspecialchars($product['image']) ?>" alt="Gambar Produk">
        <?php endif; ?>
        <!-- Upload gambar baru -->
        <input type="file" name="image" accept="image/*">

        <input type="number" step="0.1" name="rating" value="<?= $product['rating'] ?>" required>
        <button type="submit">Simpan Perubahan</button>
    </form>
</body>
</html>
