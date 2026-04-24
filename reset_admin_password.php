<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$servername = "localhost";
$username = "bgcdarep";
$password = "i3h7Gj";
$dbname = "bgcdarep_m1";

$link = mysqli_connect($servername, $username, $password, $dbname);

if (!$link) {
    die("Ошибка подключения: " . mysqli_connect_error());
}

mysqli_set_charset($link, "utf8");

// Новый пароль
$new_password = "admin123";
$hash = password_hash($new_password, PASSWORD_BCRYPT);

// Обновляем пароль для администратора (id_user = 1)
$stmt = mysqli_prepare($link, "UPDATE users SET hash = ? WHERE id_user = 1");
mysqli_stmt_bind_param($stmt, "s", $hash);
$result = mysqli_stmt_execute($stmt);

if ($result) {
    echo "<h2 style='color: green;'>✅ Пароль для администратора успешно обновлен!</h2>";
    echo "<p>Логин: <strong>admin</strong></p>";
    echo "<p>Новый пароль: <strong>admin123</strong></p>";
    echo "<a href='login.php'>Перейти на страницу входа</a>";
} else {
    echo "<h2 style='color: red;'>❌ Ошибка обновления: " . mysqli_error($link) . "</h2>";
}

mysqli_close($link);
?>