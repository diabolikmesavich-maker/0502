<?php
$servername = "localhost";
$username = "bgcdarep";
$password = "i3h7Gj";
$dbname = "bgcdarep_m1";

// Создаем подключение в объектном стиле для совместимости
$conn = new mysqli($servername, $username, $password, $dbname);

// Проверка подключения
if ($conn->connect_error) {
    die("Ошибка подключения: " . $conn->connect_error);
}

// Устанавливаем кодировку
$conn->set_charset("utf8");

// Также создаем переменную $link для процедурного стиля (для совместимости со старыми файлами)
$link = $conn;
?>