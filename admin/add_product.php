<?php
require '../vendor/autoload.php';
use MongoDB\Client;

session_start();
$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $client = new Client("mongodb://localhost:27017");
    $collection = $client->ecommerce->products;

    // Upload gambar
    $imageName = "";
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $imageName = basename($_FILES['image']['name']);
        $tmpName = $_FILES['image']['tmp_name'];
        move_uploaded_file($tmpName, "../img/" . $imageName);
    }

    $insert = $collection->insertOne([
        '_id' => $_POST['product_id'],
        'name' => $_POST['name'],
        'category' => $_POST['category'],
        'description' => $_POST['description'],
        'specs' => [
            'CPU' => $_POST['cpu'],
            'RAM' => $_POST['ram'],
            'VGA' => $_POST['vga'],
            'Storage' => $_POST['storage']
        ],
        'price' => (int) $_POST['price'],
        'rating' => (float) $_POST['rating'],
        'tags' => explode(",", $_POST['tags']),
        'image' => $imageName
    ]);

    $message = "✅ Produk berhasil ditambahkan.";
     header("Location: index.php"); // kembali ke halaman daftar produk
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin - Tambah Produk</title>
    <style>
        body { font-family: Arial; background: #f4f4f4; padding: 40px; }
        form { background: white; padding: 30px; max-width: 600px; margin: auto; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        input, textarea { width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ccc; border-radius: 4px; }
        button { background: #28a745; color: white; border: none; padding: 10px 20px; border-radius: 4px; }
        h2 { margin-bottom: 20px; }
        .message { color: green; text-align: center; }
    </style>
</head>
<body>
    <form method="POST" enctype="multipart/form-data">
        <h2>Tambah Produk Baru</h2>
        <input type="text" name="product_id" placeholder="ID Produk" required>
        <input type="text" name="name" placeholder="Nama Produk" required>
        <input type="text" name="category" placeholder="Kategori" required>
        <textarea name="description" placeholder="Deskripsi" required></textarea>
        <input type="text" name="cpu" placeholder="CPU" required>
        <input type="text" name="ram" placeholder="RAM" required>
        <input type="text" name="vga" placeholder="VGA" required>
        <input type="text" name="storage" placeholder="Storage" required>
        <input type="number" name="price" placeholder="Harga" required>
        <input type="text" name="tags" placeholder="Tag (pisahkan dengan koma)" required>
    

        <!-- Tambah input upload gambar -->
        <label>Upload Gambar Produk</label>
        <input type="file" name="image" accept="image/*" required>

        <button type="submit">Tambah Produk</button>
        <?php if (!empty($message)): ?>
            <p class="message"><?= $message ?></p>
        <?php endif; ?>
    </form>
</body>
</html>
