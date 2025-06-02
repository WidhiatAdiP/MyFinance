<?php
include 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = $_POST['nama'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $stmt = $conn->prepare("INSERT INTO users (nama, email, password) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $nama, $email, $password);
    $stmt->execute();
    
    header("Location: login.php");
}
?>

<!DOCTYPE html>
<html>
    <head>
        <title>Register</title>
        <link rel="stylesheet" href="assets/style.css">
    </head>
    <body>
        <div class="form-container">
            <h2>Register</h2>
            <form method="post">
                <input type="text" name="nama" placeholder="Nama Lengkap" required />
                <input type="email" name="email" placeholder="Email" required />
                <input type="password" name="password" placeholder="Password" required />
                <button type="submit">Register Now</button>
                <p>Already have account? <a href="login.php">Login Instead</a></p>
            </form>
        </div>
    </body>
</html>