<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

$servername = "localhost";
$username = "bgcdarep";
$password = "i3h7Gj";
$dbname = "bgcdarep_m1";

$link = mysqli_connect($servername, $username, $password, $dbname);

if (!$link) {
    die("Ошибка подключения: " . mysqli_connect_error());
}

// Если уже авторизован
if(isset($_SESSION["logged"]) && $_SESSION["logged"] == "1"){
    if(isset($_SESSION["userid"]) && $_SESSION["userid"] == 1){
        header("Location: deda.php");
    } else {
        header("Location: profile.php");
    }
    exit();
}

$error = false;

if(isset($_POST["auth"])){
    $login = trim($_POST["login"]);
    $password = trim($_POST["password"]);
    
    $stmt = mysqli_prepare($link, "SELECT * FROM users WHERE login = ?");
    mysqli_stmt_bind_param($stmt, "s", $login);
    mysqli_stmt_execute($stmt);
    $q = mysqli_stmt_get_result($stmt);
    
    if(mysqli_num_rows($q) == 1){
        $user = mysqli_fetch_assoc($q);
        
        if($user["hash"] && password_verify($password, $user["hash"])){
            $_SESSION["logged"] = "1";
            $_SESSION["userid"] = $user["id_user"];
            
            if($user["id_user"] == 1){
                header("Location: deda.php");
            } else {
                header("Location: profile.php");
            }
            exit();
        } else {
            $error = true;
        }
    } else {
        $error = true;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Вход на сайт</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 400px; margin: 50px auto; padding: 20px; }
        form { background: #f5f5f5; padding: 20px; border-radius: 8px; }
        input { width: 100%; padding: 8px; margin: 5px 0 15px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        button { background: #4CAF50; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; width: 100%; }
        .error { color: red; padding: 10px; background: #ffeeee; border-radius: 4px; margin-bottom: 15px; }
        a { display: inline-block; margin-top: 10px; }
    </style>
</head>
<body>
    <h2>Вход в систему</h2>
    
    <?php if($error): ?>
        <div class="error">Неверный логин или пароль</div>
    <?php endif; ?>
    
    <form method="POST">
        <input type="text" name="login" placeholder="Логин" required/>
        <input type="password" name="password" placeholder="Пароль" required/>
        <button type="submit" name="auth">Войти</button>
    </form>
    
    <a href="regist.php">Регистрация</a><br>
    <a href="index.php">На главную</a>
</body>
</html>