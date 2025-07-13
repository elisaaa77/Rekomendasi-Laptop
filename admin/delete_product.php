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

$collection->deleteOne(['_id' => $id]);

header("Location: index.php"); // arahkan kembali ke daftar
exit;
