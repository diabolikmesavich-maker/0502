<?php
session_start();
require_once "bdconnect.php";

// Проверка авторизации
if(!isset($_SESSION["logged"]) || $_SESSION["logged"] != "1") {
    header("Location: login.php");
    exit();
}

$userid = (int)$_SESSION["userid"];
$message = "";

if(isset($_POST['upload'])) {
    if(isset($_FILES['avatar']) && $_FILES['avatar']['error'] == 0) {
        $upload_dir = "avatars/";
        
        // Создаем папку если нет
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/jpg'];
        $max_size = 2 * 1024 * 1024; // 2MB
        
        if (in_array($_FILES['avatar']['type'], $allowed_types) && $_FILES['avatar']['size'] <= $max_size) {
            $extension = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
            $filename = "user_" . $userid . "_" . time() . "." . $extension;
            $target_path = $upload_dir . $filename;
            
            if (move_uploaded_file($_FILES['avatar']['tmp_name'], $target_path)) {
                // Удаляем старую аватарку
                $sql = "SELECT avatar FROM users WHERE id_user = $userid";
                $result = mysqli_query($link, $sql);
                $old = mysqli_fetch_assoc($result);
                if(!empty($old['avatar']) && file_exists($old['avatar'])) {
                    unlink($old['avatar']);
                }
                
                // Сохраняем новую
                $update = "UPDATE users SET avatar = '$target_path' WHERE id_user = $userid";
                if(mysqli_query($link, $update)) {
                    $message = "✅ Аватарка успешно загружена!";
                } else {
                    $message = "❌ Ошибка при сохранении: " . mysqli_error($link);
                }
            } else {
                $message = "❌ Ошибка при загрузке файла";
            }
        } else {
            $message = "❌ Неверный формат или файл больше 2MB";
        }
    } else {
        $message = "❌ Выберите файл";
    }
}

// Получаем текущую аватарку
$sql = "SELECT avatar FROM users WHERE id_user = $userid";
$result = mysqli_query($link, $sql);
$user = mysqli_fetch_assoc($result);
$current_avatar = !empty($user['avatar']) && file_exists($user['avatar']) ? $user['avatar'] : "";
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Загрузка аватарки</title>
    <style>
        body { font-family: Arial; max-width: 500px; margin: 50px auto; text-align: center; }
        .avatar { width: 150px; height: 150px; border-radius: 50%; object-fit: cover; border: 3px solid #4CAF50; margin: 20px; }
        .message { padding: 10px; margin: 20px; border-radius: 5px; }
        .success { background: #d4edda; color: #155724; }
        .error { background: #f8d7da; color: #721c24; }
        input, button { margin: 10px; padding: 10px; }
        button { background: #4CAF50; color: white; border: none; border-radius: 5px; cursor: pointer; }
    </style>
</head>
<body>
    <h1>Загрузка аватарки</h1>
    
    <?php if($message): ?>
        <div class="message <?php echo strpos($message, '✅') !== false ? 'success' : 'error'; ?>">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>
    
    <?php if($current_avatar): ?>
        <img src="<?php echo $current_avatar; ?>" class="avatar" alt="Текущая аватарка">
    <?php else: ?>
        <div class="avatar" style="background: #ccc; display: flex; align-items: center; justify-content: center; font-size: 50px;">
            👤
        </div>
    <?php endif; ?>
    
    <form method="post" enctype="multipart/form-data">
        <input type="file" name="avatar" accept="image/jpeg,image/png,image/gif,image/webp" required>
        <br>
        <button type="submit" name="upload">📤 Загрузить аватарку</button>
    </form>
    
    <br>
    <a href="profile.php">← Вернуться в профиль</a>
</body>
</html>