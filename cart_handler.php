<?php
session_start();

// Подключение к БД
$servername = "localhost";
$username = "bgcdarep";
$password = "i3h7Gj";
$dbname = "bgcdarep_m1";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Ошибка подключения: " . $conn->connect_error);
}

// ========== ОБРАБОТКА КОРЗИНЫ ==========

// 1. Добавление товара
if (isset($_GET['add_to_cart']) && is_numeric($_GET['add_to_cart'])) {
    $product_id = intval($_GET['add_to_cart']);
    $quantity = isset($_GET['quantity']) ? intval($_GET['quantity']) : 1;
    if ($quantity < 1) $quantity = 1;
    
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = array();
    }
    
    if (isset($_SESSION['cart'][$product_id])) {
        $_SESSION['cart'][$product_id] += $quantity;
    } else {
        $_SESSION['cart'][$product_id] = $quantity;
    }
    
    $_SESSION['cart_message'] = "✅ Товар добавлен в корзину!";
    header("Location: " . ($_GET['from'] ?? 'index.php'));
    exit();
}

// 2. Удаление товара из корзины
if (isset($_GET['remove_from_cart']) && is_numeric($_GET['remove_from_cart'])) {
    $product_id = intval($_GET['remove_from_cart']);
    if (isset($_SESSION['cart'][$product_id])) {
        unset($_SESSION['cart'][$product_id]);
        $_SESSION['cart_message'] = "🗑 Товар удален из корзины";
    }
    header("Location: " . ($_GET['from'] ?? 'index.php'));
    exit();
}

// 3. Очистка корзины
if (isset($_GET['clear_cart'])) {
    $_SESSION['cart'] = array();
    $_SESSION['cart_message'] = "🧹 Корзина очищена";
    header("Location: " . ($_GET['from'] ?? 'index.php'));
    exit();
}

// 4. Обновление количества
if (isset($_POST['update_cart']) && isset($_POST['quantity'])) {
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = array();
    }
    
    foreach ($_POST['quantity'] as $product_id => $quantity) {
        $product_id = intval($product_id);
        $quantity = intval($quantity);
        if ($quantity > 0) {
            $_SESSION['cart'][$product_id] = $quantity;
        } else {
            unset($_SESSION['cart'][$product_id]);
        }
    }
    $_SESSION['cart_message'] = "🔄 Корзина обновлена";
    header("Location: " . ($_POST['from'] ?? 'index.php'));
    exit();
}

// 5. ОФОРМЛЕНИЕ ЗАКАЗА (для любого авторизованного пользователя)
if (isset($_POST['checkout'])) {
    // Проверяем авторизацию
    if (!isset($_SESSION["logged"]) || $_SESSION["logged"] != "1" || !isset($_SESSION["userid"])) {
        $_SESSION['order_message'] = "⚠️ Для оформления заказа необходимо авторизоваться!";
        header("Location: login.php");
        exit();
    }
    
    // Проверяем, что корзина не пуста
    if (empty($_SESSION['cart'])) {
        $_SESSION['order_message'] = "❌ Корзина пуста! Добавьте товары перед оформлением заказа.";
        header("Location: " . ($_POST['from'] ?? 'index.php'));
        exit();
    }
    
    $id_user = intval($_SESSION["userid"]);
    $data = date("Y-m-d H:i:s");
    $id_order = time() . rand(100, 999); // Уникальный номер заказа
    
    $order_success = true;
    $order_message = "";
    $order_items = [];
    
    foreach ($_SESSION['cart'] as $product_id => $quantity) {
        $product_id = intval($product_id);
        $quantity = intval($quantity);
        
        // Получаем информацию о товаре
        $product_sql = "SELECT cena, kol, name FROM tovars WHERE id = $product_id";
        $product_result = $conn->query($product_sql);
        
        if ($product_result && $product_result->num_rows > 0) {
            $product = $product_result->fetch_assoc();
            $price = floatval($product['cena']);
            $available = intval($product['kol']);
            $product_name = $product['name'];
            
            // Проверяем наличие
            if ($quantity > $available) {
                $order_success = false;
                $order_message = "❌ Товара \"$product_name\" недостаточно на складе. Доступно: $available шт.";
                break;
            }
            
            $total_cost = $price * $quantity;
            
            // Сохраняем заказ
            $sql = "INSERT INTO orders (id_order, id_user, id_tovar, quantity, cost, datatime, status) 
                    VALUES ('$id_order', '$id_user', '$product_id', '$quantity', '$total_cost', '$data', 'new')";
            
            if (!$conn->query($sql)) {
                $order_success = false;
                $order_message = "❌ Ошибка БД: " . $conn->error;
                break;
            }
            
            // Уменьшаем остаток
            $new_quantity = $available - $quantity;
            $conn->query("UPDATE tovars SET kol = $new_quantity WHERE id = $product_id");
            
            $order_items[] = $product_name . " x" . $quantity;
        } else {
            $order_success = false;
            $order_message = "❌ Товар с ID $product_id не найден";
            break;
        }
    }
    
    if ($order_success) {
        $_SESSION['cart'] = array(); // Очищаем корзину
        $_SESSION['order_message'] = "✅ ЗАКАЗ ОФОРМЛЕН! Номер: $id_order\nТовары: " . implode(", ", $order_items);
    } else {
        $_SESSION['order_message'] = $order_message;
    }
    
    $return_url = isset($_POST['from']) ? $_POST['from'] : 'index.php';
    header("Location: $return_url");
    exit();
}

// Если ничего не обработано - перенаправляем на главную
header("Location: index.php");
exit();