<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require_once "bdconnect.php";

$error = '';
$success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $login = trim(mysqli_real_escape_string($link, $_POST['login']));
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $name = trim(mysqli_real_escape_string($link, $_POST['name']));
    
    // Валидация
    if (empty($login) || empty($password) || empty($name)) {
        $error = "Пожалуйста, заполните все поля";
    } elseif ($password !== $confirm_password) {
        $error = "Пароли не совпадают";
    } elseif (strlen($password) < 4) {
        $error = "Пароль должен содержать минимум 4 символа";
    } else {
        // Проверка существования логина
        $check_sql = "SELECT id_user FROM users WHERE login = '$login'";
        $check_result = mysqli_query($link, $check_sql);
        
        if (mysqli_num_rows($check_result) > 0) {
            $error = "Пользователь с таким логином уже существует";
        } else {
            // Хэширование пароля
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Обработка аватарки
            $avatar_path = null;
            if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] == 0) {
                $upload_dir = "avatars/";
                $absolute_upload_dir = __DIR__ . "/" . $upload_dir;
                
                if (!file_exists($absolute_upload_dir)) {
                    mkdir($absolute_upload_dir, 0777, true);
                }
                
                $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/jpg'];
                $max_size = 2 * 1024 * 1024;
                
                if (in_array($_FILES['avatar']['type'], $allowed_types) && $_FILES['avatar']['size'] <= $max_size) {
                    $extension = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
                    $filename = "user_" . time() . "_" . rand(1000, 9999) . "." . $extension;
                    $target_path = $upload_dir . $filename;
                    $absolute_path = $absolute_upload_dir . $filename;
                    
                    if (move_uploaded_file($_FILES['avatar']['tmp_name'], $absolute_path)) {
                        $avatar_path = $target_path;
                    }
                }
            }
            
            $avatar_value = $avatar_path ? "'$avatar_path'" : "NULL";
            $sql = "INSERT INTO users (login, password, name, avatar) VALUES ('$login', '$hashed_password', '$name', $avatar_value)";
            
            if (mysqli_query($link, $sql)) {
                $success = "Регистрация успешна! Теперь вы можете войти.";
                // Очищаем форму
                $_POST = array();
            } else {
                $error = "Ошибка регистрации: " . mysqli_error($link);
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Регистрация</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 500px; margin: 50px auto; padding: 20px; background: #f5f5f5; }
        .container { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h2 { text-align: center; color: #333; margin-bottom: 25px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; color: #555; }
        input[type="text"], input[type="password"], input[type="file"] { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        input[type="file"] { padding: 5px; }
        button { width: 100%; padding: 12px; background-color: #4CAF50; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; }
        button:hover { background-color: #45a049; }
        .error { background-color: #f8d7da; color: #721c24; padding: 10px; border-radius: 4px; margin-bottom: 20px; border: 1px solid #f5c6cb; }
        .success { background-color: #d4edda; color: #155724; padding: 10px; border-radius: 4px; margin-bottom: 20px; border: 1px solid #c3e6cb; }
        .login-link { text-align: center; margin-top: 20px; }
        .login-link a { color: #2196F3; text-decoration: none; }
        .avatar-preview { text-align: center; margin-top: 10px; }
        .avatar-preview img { max-width: 100px; max-height: 100px; border-radius: 50%; border: 2px solid #4CAF50; }
        .avatar-info { font-size: 12px; color: #666; margin-top: 5px; }
    </style>
</head>
<body>
    <div class="container">
        <h2>📝 Регистрация</h2>
        
        <?php if($error): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if($success): ?>
            <div class="success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <form method="post" enctype="multipart/form-data">
            <div class="form-group">
                <label>Логин *</label>
                <input type="text" name="login" value="<?php echo isset($_POST['login']) ? htmlspecialchars($_POST['login']) : ''; ?>" required>
            </div>
            
            <div class="form-group">
                <label>Пароль * (минимум 4 символа)</label>
                <input type="password" name="password" required>
            </div>
            
            <div class="form-group">
                <label>Подтверждение пароля *</label>
                <input type="password" name="confirm_password" required>
            </div>
            
            <div class="form-group">
                <label>Ваше имя *</label>
                <input type="text" name="name" value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>" required>
            </div>
            
            <div class="form-group">
                <label>Аватарка (необязательно)</label>
                <input type="file" name="avatar" accept="image/jpeg,image/png,image/gif,image/webp" id="avatarInput">
                <div class="avatar-info">Поддерживаются: JPG, PNG, GIF, WEBP (макс. 2MB)</div>
                <div class="avatar-preview" id="avatarPreview"></div>
            </div>
            
            <button type="submit">Зарегистрироваться</button>
        </form>
        
        <div class="login-link">
            Уже есть аккаунт? <a href="login.php">Войти</a>
        </div>
    </div>
    
    <script>
        // Предпросмотр аватарки перед загрузкой
        document.getElementById('avatarInput').addEventListener('change', function(e) {
            const preview = document.getElementById('avatarPreview');
            preview.innerHTML = '';
            
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(event) {
                    const img = document.createElement('img');
                    img.src = event.target.result;
                    preview.appendChild(img);
                };
                reader.readAsDataURL(file);
            }
        });
    </script>
</body>
</html>