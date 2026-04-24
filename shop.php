<?php
session_start();

// Проверяем авторизацию (но не блокируем просмотр товаров)
$is_logged = isset($_SESSION["logged"]) && $_SESSION["logged"] == "1" && isset($_SESSION["userid"]);

$servername = "localhost";
$username = "bgcdarep";
$password = "i3h7Gj";
$dbname = "bgcdarep_m1";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Ошибка подключения: " . $conn->connect_error);
}

// Получаем сообщения из сессии
$cart_message = $_SESSION['cart_message'] ?? '';
$order_message = $_SESSION['order_message'] ?? '';
unset($_SESSION['cart_message'], $_SESSION['order_message']);

// Получаем все товары
$result = $conn->query("SELECT * FROM tovars ORDER BY id DESC");

// Получаем корзину
$cart_items = [];
$cart_total = 0;
if (!empty($_SESSION['cart'])) {
    $cart_ids = array_keys($_SESSION['cart']);
    $cart_ids_str = implode(',', $cart_ids);
    $cart_result = $conn->query("SELECT * FROM tovars WHERE id IN ($cart_ids_str)");
    if ($cart_result) {
        while ($item = $cart_result->fetch_assoc()) {
            $item['cart_quantity'] = $_SESSION['cart'][$item['id']];
            $item['item_total'] = $item['cena'] * $item['cart_quantity'];
            $cart_total += $item['item_total'];
            $cart_items[] = $item;
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Магазин - Покупка товаров</title>
    <meta charset="UTF-8">
    <style>
        * { box-sizing: border-box; }
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background: #f0f2f5; }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            border-radius: 10px;
            margin-bottom: 30px;
        }
        .header h1 { margin: 0; }
        .header a {
            color: white;
            text-decoration: none;
            padding: 8px 16px;
            background: rgba(255,255,255,0.2);
            border-radius: 5px;
            margin-left: 10px;
            transition: 0.3s;
        }
        .header a:hover { background: rgba(255,255,255,0.3); }
        
        .container { display: flex; gap: 30px; flex-wrap: wrap; }
        
        .products {
            flex: 2;
            min-width: 300px;
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
        }
        
        .product-card {
            background: white;
            border-radius: 12px;
            padding: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: transform 0.2s;
        }
        .product-card:hover { transform: translateY(-3px); }
        
        .product-img {
            width: 100%;
            height: 180px;
            object-fit: cover;
            border-radius: 8px;
            background: #e0e0e0;
        }
        .product-name {
            font-size: 18px;
            font-weight: bold;
            margin: 12px 0 5px;
        }
        .product-price {
            color: #e74c3c;
            font-size: 22px;
            font-weight: bold;
        }
        .product-stock {
            color: #666;
            font-size: 12px;
            margin: 5px 0;
        }
        .add-btn {
            background: #4CAF50;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
            width: 100%;
            font-size: 16px;
            margin-top: 10px;
            transition: 0.3s;
        }
        .add-btn:hover { background: #45a049; }
        
        .cart {
            flex: 1;
            min-width: 320px;
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: sticky;
            top: 20px;
            max-height: 80vh;
            overflow-y: auto;
        }
        .cart h2 {
            margin-top: 0;
            border-bottom: 2px solid #4CAF50;
            padding-bottom: 10px;
        }
        .cart-badge {
            background: #e74c3c;
            color: white;
            border-radius: 50%;
            padding: 2px 8px;
            font-size: 12px;
            margin-left: 8px;
        }
        .cart-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #eee;
            flex-wrap: wrap;
            gap: 10px;
        }
        .cart-item-name { flex: 2; font-weight: bold; }
        .cart-item-price { font-size: 12px; color: #666; }
        .cart-item-quantity input {
            width: 60px;
            padding: 5px;
            text-align: center;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .cart-item-total { font-weight: bold; color: #4CAF50; min-width: 80px; text-align: right; }
        .cart-item-remove a {
            color: #e74c3c;
            text-decoration: none;
            font-size: 20px;
            font-weight: bold;
        }
        .cart-total {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 2px solid #ddd;
            text-align: right;
            font-size: 20px;
            font-weight: bold;
        }
        .cart-buttons {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
        .cart-buttons button, .cart-buttons a {
            flex: 1;
            padding: 12px;
            text-align: center;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
            font-weight: bold;
        }
        .checkout-btn { background: #4CAF50; color: white; }
        .clear-btn { background: #95a5a6; color: white; }
        .update-btn { background: #3498db; color: white; }
        .empty-cart { text-align: center; color: #999; padding: 40px 20px; }
        
        .message {
            padding: 12px 20px;
            margin-bottom: 20px;
            border-radius: 8px;
        }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        
        @media (max-width: 768px) {
            .container { flex-direction: column; }
            .cart { position: static; max-height: none; }
        }
    </style>
</head>
<body>

<div class="header">
    <h1>🛍️ Магазин товаров</h1>
    <div>
        <?php if($is_logged): ?>
            <span>👤 <?php echo htmlspecialchars($_SESSION["login"] ?? 'Пользователь'); ?></span>
            <a href="profile.php">📁 Мои заказы</a>
            <a href="logout.php">🚪 Выйти</a>
        <?php else: ?>
            <a href="login.php">🔑 Войти</a>
            <a href="register.php">📝 Регистрация</a>
        <?php endif; ?>
    </div>
</div>

<?php if($cart_message): ?>
    <div class="message success"><?php echo htmlspecialchars($cart_message); ?></div>
<?php endif; ?>

<?php if($order_message): ?>
    <div class="message <?php echo strpos($order_message, '✅') !== false ? 'success' : 'error'; ?>">
        <?php echo nl2br(htmlspecialchars($order_message)); ?>
    </div>
<?php endif; ?>

<div class="container">
    <!-- Список товаров -->
    <div class="products">
        <?php if($result && $result->num_rows > 0): ?>
            <?php while($product = $result->fetch_assoc()): ?>
                <div class="product-card">
                    <?php if(!empty($product['image_path']) && file_exists($product['image_path'])): ?>
                        <img src="<?php echo $product['image_path']; ?>" class="product-img" alt="<?php echo htmlspecialchars($product['name']); ?>">
                    <?php else: ?>
                        <div class="product-img" style="display: flex; align-items: center; justify-content: center; font-size: 48px;">📦</div>
                    <?php endif; ?>
                    <div class="product-name"><?php echo htmlspecialchars($product['name']); ?></div>
                    <div class="product-price"><?php echo number_format($product['cena'], 2); ?> ₽</div>
                    <div class="product-stock">✅ В наличии: <?php echo $product['kol']; ?> шт.</div>
                    <a href="cart_handler.php?add_to_cart=<?php echo $product['id']; ?>&from=shop.php" class="add-btn">🛒 В корзину</a>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p style="text-align: center; color: #999;">😕 Товаров пока нет</p>
        <?php endif; ?>
    </div>
    
    <!-- Корзина -->
    <div class="cart">
        <h2>🛒 Корзина <span class="cart-badge"><?php echo count($_SESSION['cart'] ?? []); ?></span></h2>
        
        <?php if(empty($cart_items)): ?>
            <div class="empty-cart">
                <p>🛍️ Корзина пуста</p>
                <p style="font-size: 13px;">Добавьте товары, нажав "В корзину"</p>
            </div>
        <?php else: ?>
            <form method="post" action="cart_handler.php">
                <input type="hidden" name="from" value="shop.php">
                <?php foreach($cart_items as $item): ?>
                    <div class="cart-item">
                        <div class="cart-item-name">
                            <?php echo htmlspecialchars($item['name']); ?>
                            <div class="cart-item-price"><?php echo $item['cena']; ?> ₽/шт</div>
                        </div>
                        <div class="cart-item-quantity">
                            <input type="number" name="quantity[<?php echo $item['id']; ?>]" 
                                   value="<?php echo $item['cart_quantity']; ?>" 
                                   min="1" max="<?php echo $item['kol']; ?>">
                        </div>
                        <div class="cart-item-total">
                            <?php echo number_format($item['item_total'], 2); ?> ₽
                        </div>
                        <div class="cart-item-remove">
                            <a href="cart_handler.php?remove_from_cart=<?php echo $item['id']; ?>&from=shop.php" onclick="return confirm('Удалить товар из корзины?')">✖</a>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <div class="cart-total">
                    Итого: <?php echo number_format($cart_total, 2); ?> ₽
                </div>
                
                <div class="cart-buttons">
                    <button type="submit" name="update_cart" class="update-btn">🔄 Обновить</button>
                    <a href="cart_handler.php?clear_cart=1&from=shop.php" class="clear-btn" onclick="return confirm('Очистить корзину?')">🗑 Очистить</a>
                </div>
                
                <div class="cart-buttons" style="margin-top: 10px;">
                    <button type="submit" name="checkout" class="checkout-btn" onclick="return confirm('Подтверждаете оформление заказа?')">✅ Оформить заказ</button>
                </div>
            </form>
        <?php endif; ?>
        
        <?php if(!$is_logged && !empty($cart_items)): ?>
            <p style="text-align: center; margin-top: 15px; font-size: 12px; color: #666;">
                ⚠️ Для оформления заказа <a href="login.php">войдите</a> в аккаунт
            </p>
        <?php endif; ?>
    </div>
</div>

</body>
</html>

<?php $conn->close(); ?>