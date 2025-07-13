
<?php
require 'vendor/autoload.php';
use MongoDB\Client;

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST["name"] ?? "";
    $email = $_POST["email"] ?? "";
    $password = $_POST["password"] ?? "";

    if (empty($name) || empty($email) || empty($password)) {
        $message = "Semua field wajib diisi.";
    } else {
        $client = new Client("mongodb://localhost:27017");
        $collection = $client->ecommerce->users;

        $user = $collection->findOne(['email' => $email]);

        if ($user) {
            $message = "❌ Email sudah terdaftar.";
        } else {
            $insert = $collection->insertOne([
                'name' => $name,
                'email' => $email,
                'password' => $password
            ]);
            $message = "✅ Registrasi berhasil. Silakan login.";
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Registrasi Pengguna</title>
    <style>
        body { font-family: Arial; background: #f4f4f4; padding: 50px; }
        form { background: white; padding: 30px; border-radius: 8px; max-width: 400px; margin: auto; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        input { width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #aaa; border-radius: 4px; }
        button { padding: 10px; width: 100%; background: #28a745; color: white; border: none; border-radius: 4px; }
        .message { text-align: center; margin-top: 15px; color: green; }
    </style>
</head>
<body>
    <form method="POST">
        <h2>Registrasi Pengguna</h2>
        <input type="text" name="name" placeholder="Nama Lengkap" required>
        <input type="email" name="email" placeholder="Email" required>
        <input type="password" name="password" placeholder="Password" required>
        <button type="submit">Daftar</button>
        <?php if (!empty($message)): ?>
            <div class="message"><?= $message ?></div>
        <?php endif; ?>
        <p style="text-align:center; margin-top:10px;">Sudah punya akun? <a href="login.php">Login di sini</a></p>
    </form>
</body>
</html>
