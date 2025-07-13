
<?php
require 'vendor/autoload.php';
use MongoDB\Client;

session_start();
$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST["email"] ?? "";
    $password = $_POST["password"] ?? "";

    if (empty($email) || empty($password)) {
        $message = "Email dan password wajib diisi.";
    } else {
        $client = new Client("mongodb://localhost:27017");
        $collection = $client->ecommerce->users;

        $user = $collection->findOne(['email' => $email]);

        if ($user) {
            if ($user['password'] === $password) {
                $_SESSION['user_id'] = (string)$user['_id'];
                $_SESSION['user_name'] = $user['name'];
                header("Location: index.php");
                exit;
            } else {
                $message = "❌ Password salah.";
            }
        } else {
            $message = "❌ Email belum terdaftar.";
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Login Pengguna</title>
    <style>
        body { font-family: Arial; background: #f4f4f4; padding: 50px; }
        form { background: white; padding: 30px; border-radius: 8px; max-width: 400px; margin: auto; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        input { width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #aaa; border-radius: 4px; }
        button { padding: 10px; width: 100%; background: #007bff; color: white; border: none; border-radius: 4px; }
        .message { text-align: center; margin-top: 15px; color: green; }
    </style>
</head>
<body>
    <form method="POST">
        <h2>Login Pengguna</h2>
        <input type="email" name="email" placeholder="Email" required>
        <input type="password" name="password" placeholder="Password" required>
        <button type="submit">Login</button>
        <?php if (!empty($message)): ?>
            <div class="message"><?= $message ?></div>
        <?php endif; ?>
        <p style="text-align:center; margin-top:10px;">Belum punya akun? <a href="register.php">Daftar sekarang</a></p>
    </form>
</body>
</html>
