<?php
require '../vendor/autoload.php';
use MongoDB\Client;

$client = new Client("mongodb://localhost:27017");
$collection = $client->ecommerce->products;
$products = $collection->find();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Daftar Laptop</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #eef1f5;
            padding: 30px;
        }
        table {
            margin-bottom: 20px;
            width: 100%;
            border-collapse: collapse;
            background: #fff;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        th, td {
            padding: 10px;
            border: 1px solid #ccc;
            text-align: left;
            vertical-align: top;
        }
        th {
            background: #007bff;
            color: white;
        }
 
        .actions a {
            text-decoration: none;
            justify-content: space-between;
            gap: 10px;
            padding: 10px 10px;
            border-radius: 4px;
            margin-top: 10px;
        }
        .edit-btn {
             display: flex;
    justify-content: space-between; /* Distribute space between the buttons */
    gap: 10px; /* Add space between buttons */
    margin-top: 10px;
            background-color: #ffc107;
            color: black;
        }
        .delete-btn {
             display: flex;
    justify-content: space-between; /* Distribute space between the buttons */
    gap: 10px; /* Add space between buttons */
    margin-top: 10px;
            background-color: #dc3545;
            color: white;
        }
        .tambah-btn {
            margin-top: 10px;
            background-color:rgb(15, 255, 47);
            color: black;
        }
        h1 {
            text-align: center;
            margin-bottom: 30px;
            color: #333;
        }
        a.logout { float: right; text-decoration: none; color: #c00; }
    </style>
</head>
<body>
        <a class="logout" href="../logout.php">Logout</a>
    <h1>📋 Tabel Daftar Laptop</h1>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Nama</th>
                <th>Kategori</th>
                <th>Deskripsi</th>
                <th>Spesifikasi</th>
                <th>Harga</th>
                <th>Gambar Produk</th>
                <th>Tags</th>  <!-- Kolom Tags -->
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($products as $product): ?>
                <tr>
                    <td><?= $product['_id'] ?></td>
                    <td><?= htmlspecialchars($product['name']) ?></td>
                    <td><?= htmlspecialchars($product['category']) ?></td>
                    <td><?= htmlspecialchars($product['description']) ?></td>
                    <td>
                        CPU: <?= $product['specs']['CPU'] ?><br>
                        RAM: <?= $product['specs']['RAM'] ?><br>
                        VGA: <?= $product['specs']['VGA'] ?><br>
                        Storage: <?= $product['specs']['Storage'] ?>
                    </td>
                    <td>Rp <?= number_format($product['price'], 0, ',', '.') ?></td>
                    <td>
                        <?php if (!empty($product['image'])): ?>
                            <img src="../img/<?= htmlspecialchars($product['image']) ?>" alt="Gambar" style="max-width: 100px; border-radius: 4px;">
                        <?php else: ?>
                            <em>Tidak ada gambar</em>
                        <?php endif; ?>
                    </td>

            
                    <td>
                        <?php 
                        if (!empty($product['tags'])):
                            // Convert BSONArray to PHP array and then implode it
                            $tags = iterator_to_array($product['tags']);
                            echo implode(", ", $tags);  // Join tags with commas
                        else:
                            echo "<em>Tidak ada tags</em>";
                        endif;
                        ?>
                    </td>
                    <td class="actions">
                        <a href="edit_product.php?id=<?= $product['_id'] ?>" class="edit-btn">Edit</a>
                        <a href="delete_product.php?id=<?= $product['_id'] ?>" class="delete-btn" onclick="return confirm('Yakin ingin menghapus produk ini?')">Hapus</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="actions">
        <a href="add_product.php" class="tambah-btn">Tambah Product</a>
    </div>
</body>
</html>
