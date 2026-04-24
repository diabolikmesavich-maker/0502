<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require_once "bdconnect.php";

// Проверка авторизации
if(!isset($_SESSION["logged"]) || $_SESSION["logged"] != "1") {
    header("Location: login.php");
    exit();
}

// Получаем данные пользователя
$userid = (int)$_SESSION["userid"];
$query = "SELECT id_user, login, name, avatar FROM users WHERE id_user = $userid";
$result = mysqli_query($link, $query);
$user = mysqli_fetch_assoc($result);

if(!$user) {
    session_destroy();
    header("Location: login.php");
    exit();
}

// Если админ - перенаправляем на deda.php
if($userid == 1) {
    header("Location: deda.php");
    exit();
}

// Обработка загрузки аватарки
$avatar_message = "";
if(isset($_POST['upload_avatar']) && isset($_FILES['avatar'])) {
    $upload_dir = "avatars/";
    $absolute_upload_dir = __DIR__ . "/" . $upload_dir;
    
    if (!file_exists($absolute_upload_dir)) {
        mkdir($absolute_upload_dir, 0777, true);
    }
    
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/jpg'];
    $max_size = 2 * 1024 * 1024; // 2 MB
    
    if ($_FILES['avatar']['error'] == 0) {
        if (in_array($_FILES['avatar']['type'], $allowed_types) && $_FILES['avatar']['size'] <= $max_size) {
            $extension = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
            $filename = "user_" . $userid . "_" . time() . "." . $extension;
            $target_path = $upload_dir . $filename;
            $absolute_path = $absolute_upload_dir . $filename;
            
            if (move_uploaded_file($_FILES['avatar']['tmp_name'], $absolute_path)) {
                // Удаляем старую аватарку, если она существует
                if (!empty($user['avatar']) && file_exists(__DIR__ . "/" . $user['avatar'])) {
                    @unlink(__DIR__ . "/" . $user['avatar']);
                }
                
                // Обновляем запись в БД
                $update_sql = "UPDATE users SET avatar = '$target_path' WHERE id_user = $userid";
                if (mysqli_query($link, $update_sql)) {
                    $avatar_message = "Аватарка успешно обновлена!";
                    $user['avatar'] = $target_path;
                } else {
                    $avatar_message = "Ошибка при сохранении в БД: " . mysqli_error($link);
                }
            } else {
                $avatar_message = "Ошибка при загрузке файла";
            }
        } else {
            $avatar_message = "Неверный формат или размер файла (макс. 2MB, форматы: JPG, PNG, GIF, WEBP)";
        }
    } else {
        $avatar_message = "Ошибка при загрузке файла";
    }
}

// Обработка удаления аватарки
if(isset($_POST['delete_avatar'])) {
    if (!empty($user['avatar']) && file_exists(__DIR__ . "/" . $user['avatar'])) {
        @unlink(__DIR__ . "/" . $user['avatar']);
    }
    $update_sql = "UPDATE users SET avatar = NULL WHERE id_user = $userid";
    if (mysqli_query($link, $update_sql)) {
        $avatar_message = "Аватарка удалена";
        $user['avatar'] = null;
    }
}

// Получаем заказы пользователя
$orders_query = "SELECT o.*, t.name as product_name, t.image_path 
                 FROM orders o 
                 LEFT JOIN tovars t ON o.id_tovar = t.id 
                 WHERE o.id_user = $userid 
                 ORDER BY o.datatime DESC";
$orders_result = mysqli_query($link, $orders_query);
$orders = [];
if($orders_result) {
    while($order = mysqli_fetch_assoc($orders_result)) {
        $orders[] = $order;
    }
}

// Группируем заказы по номеру заказа (id_order)
$grouped_orders = [];
foreach($orders as $order) {
    $order_num = $order['id_order'];
    if(!isset($grouped_orders[$order_num])) {
        $grouped_orders[$order_num] = [
            'id_order' => $order['id_order'],
            'datatime' => $order['datatime'],
            'status' => $order['status'],
            'total_cost' => 0,
            'items' => []
        ];
    }
    $grouped_orders[$order_num]['items'][] = $order;
    $grouped_orders[$order_num]['total_cost'] += isset($order['cost']) ? (float)$order['cost'] : 0;
}

// Путь к аватарке (по умолчанию - gravatar или заглушка)
$avatar_path = '';
if(!empty($user['avatar']) && file_exists(__DIR__ . "/" . $user['avatar'])) {
    $avatar_path = $user['avatar'];
} else {
    $avatar_path = "https://www.gravatar.com/avatar/" . md5(strtolower(trim($user['login']))) . "?d=mp&s=200";
}

// Статусы заказов на русском
$statuses = [
    'new' => '🟡 Новый',
    'processing' => '🟠 В обработке',
    'completed' => '✅ Выполнен',
    'cancelled' => '❌ Отменен'
];
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Личный кабинет - Мои заказы</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 1200px; margin: 0 auto; padding: 20px; background: #f0f0f0; }
        .card { background: white; padding: 25px; border-radius: 10px; margin-bottom: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h2, h3 { color: #333; margin-top: 0; }
        .profile-container { display: flex; gap: 30px; flex-wrap: wrap; }
        .avatar-section { text-align: center; flex: 0 0 200px; }
        .avatar-section img { width: 150px; height: 150px; border-radius: 50%; object-fit: cover; border: 3px solid #4CAF50; }
        .info-section { flex: 1; }
        .info { margin: 15px 0; padding: 15px; background: #f8f9fa; border-radius: 8px; }
        .label { font-weight: bold; color: #555; width: 100px; display: inline-block; }
        .avatar-form { margin-top: 15px; text-align: center; }
        .avatar-form input[type="file"] { margin: 10px 0; }
        .btn { display: inline-block; padding: 10px 20px; margin: 5px; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; font-size: 14px; transition: 0.3s; }
        .btn-upload { background-color: #4CAF50; color: white; }
        .btn-upload:hover { background-color: #45a049; }
        .btn-delete { background-color: #f44336; color: white; }
        .btn-delete:hover { background-color: #da190b; }
        .btn-back { background-color: #2196F3; color: white; }
        .btn-back:hover { background-color: #0b7dda; }
        .btn-logout { background-color: #ff9800; color: white; }
        .btn-logout:hover { background-color: #e68a00; }
        .btn-orders { background-color: #9c27b0; color: white; }
        .btn-orders:hover { background-color: #7b1fa2; }
        .message { padding: 12px; margin-bottom: 15px; border-radius: 5px; background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error-message { background-color: #f8d7da; color: #721c24; border-color: #f5c6cb; }
        .buttons { margin-top: 20px; display: flex; gap: 10px; flex-wrap: wrap; }
        
        /* Стили для заказов */
        .orders-section { margin-top: 20px; }
        .order-card { background: #f8f9fa; border-radius: 8px; margin-bottom: 20px; overflow: hidden; border: 1px solid #e0e0e0; }
        .order-header { background: #4CAF50; color: white; padding: 12px 20px; display: flex; justify-content: space-between; flex-wrap: wrap; gap: 10px; }
        .order-header strong { font-size: 16px; }
        .order-body { padding: 15px 20px; }
        .order-items { margin-top: 10px; }
        .order-item { display: flex; align-items: center; padding: 10px 0; border-bottom: 1px solid #e0e0e0; gap: 15px; flex-wrap: wrap; }
        .order-item:last-child { border-bottom: none; }
        .order-item-img { width: 60px; height: 60px; object-fit: cover; border-radius: 5px; background: #e0e0e0; }
        .order-item-info { flex: 2; min-width: 150px; }
        .order-item-name { font-weight: bold; }
        .order-item-price { color: #666; font-size: 12px; }
        .order-item-quantity { min-width: 80px; }
        .order-item-cost { font-weight: bold; color: #4CAF50; min-width: 80px; text-align: right; }
        .order-total { text-align: right; margin-top: 15px; padding-top: 10px; border-top: 2px solid #ddd; font-weight: bold; font-size: 18px; }
        .order-status { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: bold; }
        .status-new { background-color: #ffc107; color: #856404; }
        .status-processing { background-color: #17a2b8; color: white; }
        .status-completed { background-color: #28a745; color: white; }
        .status-cancelled { background-color: #dc3545; color: white; }
        .empty-orders { text-align: center; padding: 40px; color: #999; }
        .shop-link { margin-top: 20px; text-align: center; }
        .shop-link a { display: inline-block; padding: 12px 25px; background-color: #4CAF50; color: white; text-decoration: none; border-radius: 5px; font-size: 16px; }
        .shop-link a:hover { background-color: #45a049; }
        
        .default-avatar { background-color: #e0e0e0; display: flex; align-items: center; justify-content: center; font-size: 60px; color: #999; }
    </style>
</head>
<body>
    <!-- Личная информация -->
    <div class="card">
        <h2>👤 Личный кабинет</h2>
        
        <?php if($avatar_message): ?>
            <div class="message <?php echo strpos($avatar_message, 'Ошибка') !== false ? 'error-message' : ''; ?>">
                <?php echo htmlspecialchars($avatar_message); ?>
            </div>
        <?php endif; ?>
        
        <div class="profile-container">
            <!-- Секция аватарки -->
            <div class="avatar-section">
                <?php if(strpos($avatar_path, 'gravatar') !== false || strpos($avatar_path, 'http') === 0): ?>
                    <img src="<?php echo htmlspecialchars($avatar_path); ?>" alt="Аватар" class="default-avatar" style="object-fit: cover;">
                <?php else: ?>
                    <img src="<?php echo htmlspecialchars($avatar_path); ?>" alt="Аватар" onerror="this.src='https://www.gravatar.com/avatar/<?php echo md5($user['login']); ?>?d=mp&s=200'">
                <?php endif; ?>
                
                <form method="post" enctype="multipart/form-data" class="avatar-form">
                    <input type="file" name="avatar" accept="image/jpeg,image/png,image/gif,image/webp" required>
                    <div>
                        <button type="submit" name="upload_avatar" class="btn btn-upload">📤 Загрузить</button>
                        <?php if(!empty($user['avatar'])): ?>
                            <button type="submit" name="delete_avatar" class="btn btn-delete" onclick="return confirm('Удалить аватарку?')">🗑 Удалить</button>
                        <?php endif; ?>
                    </div>
                </form>
                <p style="font-size: 12px; color: #666; margin-top: 10px;">Поддерживаются: JPG, PNG, GIF, WEBP (макс. 2MB)</p>
            </div>
            
            <!-- Секция информации -->
            <div class="info-section">
                <div class="info">
                    <div><span class="label">👤 Логин:</span> <?php echo htmlspecialchars($user["login"] ?? ''); ?></div>
                    <div><span class="label">📛 Имя:</span> <?php echo htmlspecialchars($user["name"] ?? ''); ?></div>
                    <div><span class="label">🆔 ID:</span> <?php echo $user["id_user"] ?? ''; ?></div>
                </div>
                
                <div class="buttons">
                    <a href="index.php" class="btn btn-back">🏠 На главную</a>
                    <a href="logout.php" class="btn btn-logout">🚪 Выйти</a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Секция заказов -->
    <div class="card">
        <h3>📦 Мои заказы</h3>
        
        <?php if(empty($grouped_orders)): ?>
            <div class="empty-orders">
                <p>😕 У вас пока нет заказов</p>
                <p style="font-size: 14px;">Перейдите в магазин, чтобы сделать первый заказ!</p>
                <div class="shop-link">
                <a href="shop.php" class="btn" style="background-color: #4CAF50;">🛍️ Магазин</a>
                </div>
            </div>
        <?php else: ?>
            <?php foreach($grouped_orders as $order): ?>
                <div class="order-card">
                    <div class="order-header">
                        <strong>📋 Заказ #<?php echo htmlspecialchars($order['id_order']); ?></strong>
                        <span>📅 <?php date_default_timezone_set('Europe/Moscow'); ?></span>
                        <span class="order-status status-<?php echo htmlspecialchars($order['status'] ?? 'new'); ?>">
                            <?php echo $statuses[$order['status'] ?? 'new'] ?? $order['status']; ?>
                        </span>
                    </div>
                    <div class="order-body">
                        <div class="order-items">
                            <?php foreach($order['items'] as $item): ?>
                                <div class="order-item">
                                    <?php if(!empty($item['image_path']) && file_exists($item['image_path'])): ?>
                                        <img src="<?php echo htmlspecialchars($item['image_path']); ?>" class="order-item-img" alt="<?php echo htmlspecialchars($item['product_name'] ?? 'Товар'); ?>">
                                    <?php else: ?>
                                        <div class="order-item-img" style="display: flex; align-items: center; justify-content: center; background: #e0e0e0;">📦</div>
                                    <?php endif; ?>
                                    <div class="order-item-info">
                                        <div class="order-item-name"><?php echo htmlspecialchars($item['product_name'] ?? 'Товар #' . ($item['id_tovar'] ?? '?')); ?></div>
                                        <div class="order-item-price"><?php echo number_format(($item['cost'] ?? 0) / max(1, ($item['quantity'] ?? 1)), 2); ?> руб/шт</div>
                                    </div>
                                    <div class="order-item-quantity">✖ <?php echo (int)($item['quantity'] ?? 0); ?> шт</div>
                                    <div class="order-item-cost"><?php echo number_format($item['cost'] ?? 0, 2); ?> руб</div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="order-total">
                            Итого: <?php echo number_format($order['total_cost'] ?? 0, 2); ?> руб
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <div class="shop-link" style="margin-top: 20px;">
                <a href="shop.php">🛒 Сделать новый заказ</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>