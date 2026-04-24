<?php
session_start();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Главная</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 600px;
            margin: 50px auto;
            padding: 20px;
            text-align: center;
        }
        .buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 30px;
            flex-wrap: wrap;
        }
        .btn {
            display: inline-block;
            padding: 12px 30px;
            text-decoration: none;
            border-radius: 5px;
            font-size: 16px;
            transition: all 0.3s ease;
        }
        .btn-login {
            background-color: #4CAF50;
            color: white;
        }
        .btn-login:hover {
            background-color: #45a049;
            transform: scale(1.05);
        }
        .btn-register {
            background-color: #2196F3;
            color: white;
        }
        .btn-register:hover {
            background-color: #1976D2;
            transform: scale(1.05);
        }
        .btn-profile {
            background-color: #ff9800;
            color: white;
        }
        .btn-profile:hover {
            background-color: #e68900;
        }
        .btn-logout {
            background-color: #f44336;
            color: white;
        }
        .btn-logout:hover {
            background-color: #d32f2f;
        }
        .welcome {
            background-color: #f5f5f5;
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <h1>🏪 Добро пожаловать!</h1>
    
    <?php if(isset($_SESSION["logged"]) && $_SESSION["logged"] == "1"): ?>
        <div class="welcome">
            <p>Вы авторизованы как: 
                <strong><?php echo isset($_SESSION["userid"]) && $_SESSION["userid"] == 1 ? "Администратор" : "Пользователь"; ?></strong>
            </p>
        </div>
        <div class="buttons">
            <a href="profile.php" class="btn btn-profile">👤 Личный кабинет</a>
            <?php if(isset($_SESSION["userid"]) && $_SESSION["userid"] == 1): ?>
                <a href="deda.php" class="btn btn-profile" style="background-color: #9C27B0;">📦 Управление товарами</a>
            <?php endif; ?>
            <a href="logout.php" class="btn btn-logout">🚪 Выйти</a>
        </div>
    <?php else: ?>
        <div class="buttons">
            <a href="login.php" class="btn btn-login">🔑 Вход</a>
            <a href="regist.php" class="btn btn-register">📝 Регистрация</a>
            <a href="shop.php" style="display: inline-block; padding: 12px 25px; background: #4CAF50; color: white; text-decoration: none; border-radius: 5px;">🛍️ Перейти в магазин</a>
        </div>
        <p style="margin-top: 30px; color: #666;">Войдите или зарегистрируйтесь для доступа к системе</p>
    <?php endif; ?>
</body>
</html>