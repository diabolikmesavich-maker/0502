<?php
// Подключаемся к БД, если еще не подключены
if (!isset($link) && !isset($conn)) {
    require_once "bdconnect.php";
} elseif (!isset($link) && isset($conn)) {
    $link = $conn;
}

$user = null;

if (isset($_SESSION["logged"]) && $_SESSION["logged"] == "1" && isset($_SESSION["userid"])) {
    $id_user = (int)$_SESSION["userid"];
    
    $stmt = mysqli_prepare($link, "SELECT id_user, login, name, email, age, salary, photo, created_at FROM users WHERE id_user = ?");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $id_user);
        mysqli_stmt_execute($stmt);
        $user_query = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($user_query);
        mysqli_stmt_close($stmt);
    }
}
?>