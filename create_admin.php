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

// Пароль для админа
$admin_password = "admin123";
$hash = password_hash($admin_password, PASSWORD_BCRYPT);

// Удаляем старого админа если есть
mysqli_query($link, "DELETE FROM users WHERE login = 'admin'");

// Создаем админа (без email)
$sql = "INSERT INTO users (login, hash, name) VALUES ('admin', '$hash', 'Администратор')";

if (mysqli_query($link, $sql)) {
    echo "✅ Администратор успешно создан!<br>";
    echo "Логин: <strong>admin</strong><br>";
    echo "Пароль: <strong>$admin_password</strong><br>";
    echo "ID пользователя: " . mysqli_insert_id($link) . "<br>";
} else {
    echo "❌ Ошибка: " . mysqli_error($link) . "<br>";
}

// Показываем всех пользователей
echo "<h3>Список пользователей:</h3>";
$result = mysqli_query($link, "SELECT id_user, login, name FROM users");
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>ID</th><th>Логин</th><th>Имя</th></tr>";
while($row = mysqli_fetch_assoc($result)) {
    echo "<tr>";
    echo "<td>" . $row['id_user'] . "</td>";
    echo "<td>" . $row['login'] . "</td>";
    echo "<td>" . $row['name'] . "</td>";
    echo "</tr>";
}
echo "<table>";

mysqli_close($link);
?>