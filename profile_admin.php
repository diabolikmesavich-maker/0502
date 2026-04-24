<?php
session_start();
require_once "bdconnect.php";

// Проверка, что пользователь админ (id_user = 1)
if(!isset($_SESSION["logged"]) || $_SESSION["logged"] != "1" || !isset($_SESSION["userid"]) || $_SESSION["userid"] != 1){
    header("Location: login.php");
    exit();
}

// Перенаправляем на страницу управления товарами
header("Location: profile.php");
exit();
?>