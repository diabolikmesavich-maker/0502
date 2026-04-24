<?php
// ЭТОТ КОД ДОБАВЬТЕ В САМОЕ НАЧАЛО deda.php
session_start();

// Проверка - только админ с id=1 может зайти на страницу управления товарами
// НО для оформления заказа авторизация не требуется
if(!isset($_SESSION["logged"]) || $_SESSION["logged"] != "1"){
    header("Location: login.php");
    exit();
}

$servername = "localhost";
$username = "bgcdarep";
$password = "i3h7Gj";
$dbname = "bgcdarep_m1";

$conn = new mysqli($servername, $username, $password, $dbname);

// Проверка подключения
if ($conn->connect_error) {
    die("Ошибка подключения: " . $conn->connect_error);
}

// ========== ИНИЦИАЛИЗАЦИЯ КОРЗИНЫ В СЕССИИ ==========
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = array();
}

// ========== ОБРАБОТКА ДЕЙСТВИЙ С КОРЗИНОЙ ==========
// Добавление товара в корзину
if (isset($_GET['add_to_cart']) && is_numeric($_GET['add_to_cart'])) {
    $product_id = intval($_GET['add_to_cart']);
    $quantity = isset($_GET['quantity']) ? intval($_GET['quantity']) : 1;
    
    if ($quantity < 1) $quantity = 1;
    
    if (isset($_SESSION['cart'][$product_id])) {
        $_SESSION['cart'][$product_id] += $quantity;
    } else {
        $_SESSION['cart'][$product_id] = $quantity;
    }
    
    $_SESSION['cart_message'] = "Товар добавлен в корзину!";
    header("Location: deda.php");
    exit();
}

// Удаление товара из корзины
if (isset($_GET['remove_from_cart']) && is_numeric($_GET['remove_from_cart'])) {
    $product_id = intval($_GET['remove_from_cart']);
    if (isset($_SESSION['cart'][$product_id])) {
        unset($_SESSION['cart'][$product_id]);
        $_SESSION['cart_message'] = "Товар удален из корзины";
    }
    header("Location: deda.php");
    exit();
}

// Очистка всей корзины
if (isset($_GET['clear_cart'])) {
    $_SESSION['cart'] = array();
    $_SESSION['cart_message'] = "Корзина очищена";
    header("Location: deda.php");
    exit();
}

// Обновление количества товаров в корзине
if (isset($_POST['update_cart'])) {
    if (isset($_POST['quantity']) && is_array($_POST['quantity'])) {
        foreach ($_POST['quantity'] as $product_id => $quantity) {
            $product_id = intval($product_id);
            $quantity = intval($quantity);
            if ($quantity > 0) {
                $_SESSION['cart'][$product_id] = $quantity;
            } else {
                unset($_SESSION['cart'][$product_id]);
            }
        }
    }
    $_SESSION['cart_message'] = "Корзина обновлена";
    header("Location: deda.php");
    exit();
}

// ========== ОФОРМЛЕНИЕ ЗАКАЗА (для любого авторизованного пользователя) ==========
if (isset($_POST['checkout'])) {
    // Проверяем, авторизован ли пользователь
    if (!isset($_SESSION["userid"]) || empty($_SESSION["userid"])) {
        $_SESSION['order_message'] = "Для оформления заказа необходимо авторизоваться!";
        header("Location: login.php");
        exit();
    }
    
    if (!empty($_SESSION['cart'])) {
        $id_user = intval($_SESSION["userid"]); // ID текущего пользователя
        $data = date("Y-m-d H:i:s");
        $id_order = strtotime($data); // Уникальный номер заказа
        
        $order_success = true;
        $order_message = "";
        
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
                
                // Проверяем наличие товара
                if ($quantity > $available) {
                    $order_success = false;
                    $order_message = "Ошибка: для товара \"$product_name\" недостаточно товара на складе. Доступно: $available шт.";
                    break;
                }
                
                $total_cost = $price * $quantity;
                
                $sql = "INSERT INTO orders (id_order, id_user, id_tovar, quantity, cost, datatime, status) 
                        VALUES ('$id_order', '$id_user', '$product_id', '$quantity', '$total_cost', '$data', 'new')";
                
                if (!$conn->query($sql)) {
                    $order_success = false;
                    $order_message = "Ошибка при оформлении заказа: " . $conn->error;
                    break;
                }
                
                // Уменьшаем количество товара на складе
                $new_quantity = $available - $quantity;
                $update_stock = "UPDATE tovars SET kol = $new_quantity WHERE id = $product_id";
                $conn->query($update_stock);
            } else {
                $order_success = false;
                $order_message = "Ошибка: товар с ID $product_id не найден";
                break;
            }
        }
        
        if ($order_success) {
            $_SESSION['cart'] = array(); // Очищаем корзину
            $_SESSION['order_message'] = "✅ Заказ успешно оформлен! Номер заказа: " . $id_order;
        } else {
            $_SESSION['order_message'] = $order_message;
        }
    } else {
        $_SESSION['order_message'] = "Корзина пуста";
    }
    header("Location: deda.php");
    exit();
}

// ========== ВЫВОД СООБЩЕНИЙ ИЗ СЕССИИ ==========
$cart_message = isset($_SESSION['cart_message']) ? $_SESSION['cart_message'] : '';
$order_message = isset($_SESSION['order_message']) ? $_SESSION['order_message'] : '';
unset($_SESSION['cart_message']);
unset($_SESSION['order_message']);

// ========== ВСЕ ОСТАЛЬНЫЕ ОБРАБОТЧИКИ (категории, товары и т.д.) ==========
// ... (оставляем весь остальной код без изменений, кроме удаления старой обработки checkout)

// Проверяем и создаем таблицу orders (заказы) если её нет
$check_orders_table = "SHOW TABLES LIKE 'orders'";
$orders_table_exists = $conn->query($check_orders_table);

if ($orders_table_exists && $orders_table_exists->num_rows == 0) {
    $create_orders = "CREATE TABLE orders (
        id INT AUTO_INCREMENT PRIMARY KEY,
        id_order VARCHAR(50) NOT NULL,
        id_user INT DEFAULT 0,
        id_tovar INT NOT NULL,
        quantity INT NOT NULL DEFAULT 1,
        cost DECIMAL(10,2) NOT NULL,
        datatime DATETIME NOT NULL,
        status ENUM('new', 'processing', 'completed', 'cancelled') DEFAULT 'new'
    )";
    
    if ($conn->query($create_orders) === TRUE) {
        $conn->query("ALTER TABLE orders ADD INDEX idx_order (id_order)");
        $conn->query("ALTER TABLE orders ADD INDEX idx_user (id_user)");
        $conn->query("ALTER TABLE orders ADD INDEX idx_tovar (id_tovar)");
    }
}

// ========== ДАЛЬШЕ ВЕСЬ ОСТАЛЬНОЙ КОД (категории, товары, HTML) ==========
// ... (весь остальной код от добавления категорий до закрытия соединения)

// Проверяем и создаем таблицу categories если её нет
$check_categories_table = "SHOW TABLES LIKE 'categories'";
$categories_table_exists = $conn->query($check_categories_table);

if ($categories_table_exists && $categories_table_exists->num_rows == 0) {
    $create_categories = "CREATE TABLE categories (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    if ($conn->query($create_categories) === TRUE) {
        // Добавляем тестовые категории
        $insert_categories = "INSERT INTO categories (name, description) VALUES
            ('Электроника', 'Товары электронной категории'),
            ('Одежда', 'Одежда и аксессуары'),
            ('Продукты питания', 'Продовольственные товары'),
            ('Книги', 'Книги и учебные материалы')";
        $conn->query($insert_categories);
    }
}

// Проверяем и добавляем поле category_id в таблицу tovars если его нет
$check_column = "SHOW COLUMNS FROM tovars LIKE 'category_id'";
$column_exists = $conn->query($check_column);

if ($column_exists && $column_exists->num_rows == 0) {
    $add_column = "ALTER TABLE tovars ADD COLUMN category_id INT DEFAULT NULL";
    $conn->query($add_column);
    
    // Добавляем внешний ключ
    $add_foreign = "ALTER TABLE tovars ADD FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL";
    $conn->query($add_foreign);
}

$check_image_column = "SHOW COLUMNS FROM tovars LIKE 'image_path'";
$image_column_exists = $conn->query($check_image_column);

if ($image_column_exists && $image_column_exists->num_rows == 0) {
    $add_image_column = "ALTER TABLE tovars ADD COLUMN image_path VARCHAR(255) DEFAULT NULL";
    $conn->query($add_image_column);
}

// Обработка добавления новой категории
if (isset($_POST['add_category'])) {
    $cat_name = $conn->real_escape_string($_POST['cat_name']);
    $cat_description = $conn->real_escape_string($_POST['cat_description']);
    
    $insert_sql = "INSERT INTO categories (name, description) VALUES ('$cat_name', '$cat_description')";
    
    if ($conn->query($insert_sql) === TRUE) {
        $cat_message = "Категория успешно добавлена";
    } else {
        $cat_message = "Ошибка при добавлении категории: " . $conn->error;
    }
}

// Обработка удаления категории
if (isset($_GET['delete_category'])) {
    $cat_id = intval($_GET['delete_category']);
    
    // Сначала обнуляем category_id в товарах
    $update_tovars = "UPDATE tovars SET category_id = NULL WHERE category_id = $cat_id";
    $conn->query($update_tovars);
    
    $delete_sql = "DELETE FROM categories WHERE id = $cat_id";
    
    if ($conn->query($delete_sql) === TRUE) {
        $cat_message = "Категория успешно удалена";
    } else {
        $cat_message = "Ошибка при удалении категории: " . $conn->error;
    }
}

// Обработка обновления категории товара
if (isset($_POST['update_category'])) {
    $product_id = intval($_POST['product_id']);
    $category_id = !empty($_POST['category_id']) ? intval($_POST['category_id']) : 'NULL';
    
    $update_sql = "UPDATE tovars SET category_id = " . ($category_id === 'NULL' ? 'NULL' : $category_id) . " WHERE id = $product_id";
    
    if ($conn->query($update_sql) === TRUE) {
        $message = "Категория товара обновлена";
    }
}

// Обработка добавления нового товара с изображением
if (isset($_POST['add_product'])) {
    $name = $conn->real_escape_string($_POST['name']);
    $cena = floatval($_POST['cena']);
    $kol = intval($_POST['kol']);
    $srok = !empty($_POST['srok']) ? "'" . $conn->real_escape_string($_POST['srok']) . "'" : "NULL";
    $category_id = !empty($_POST['category_id']) ? intval($_POST['category_id']) : "NULL";
    
    // Обработка загруженного изображения
    $image_path = "NULL";
    
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $upload_dir = "uploads/";
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $max_size = 5 * 1024 * 1024;
        
        if (in_array($_FILES['image']['type'], $allowed_types) && $_FILES['image']['size'] <= $max_size) {
            $extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $filename = uniqid() . '_' . time() . '.' . $extension;
            $target_path = $upload_dir . $filename;
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $target_path)) {
                $image_path = "'" . $target_path . "'";
            }
        }
    }
    
    $sql = "INSERT INTO tovars (name, cena, kol, srok, category_id, image_path) 
            VALUES ('$name', $cena, $kol, $srok, " . ($category_id === "NULL" ? "NULL" : $category_id) . ", $image_path)";
    
    if ($conn->query($sql) === TRUE) {
        $message = "Товар успешно добавлен";
    } else {
        $message = "Ошибка при добавлении товара: " . $conn->error;
    }
}

// Обработка удаления выбранных товаров
if (isset($_POST['delete_selected']) && isset($_POST['selected_ids'])) {
    $selected_ids = $_POST['selected_ids'];
    $deleted_count = 0;
    
    foreach ($selected_ids as $id) {
        $id = intval($id); 
        $delete_sql = "DELETE FROM tovars WHERE id = $id";
        
        if ($conn->query($delete_sql) === TRUE) {
            $deleted_count++;
        }
    }
    
    if ($deleted_count > 0) {
        $message = "Успешно удалено записей: " . $deleted_count;
    } else {
        $message = "Ошибка при удалении записей";
    }
}

// Обработка удаления одного товара
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    $delete_sql = "DELETE FROM tovars WHERE id = $delete_id";
    
    if ($conn->query($delete_sql) === TRUE) {
        $message = "Запись успешно удалена";
    } else {
        $message = "Ошибка при удалении: " . $conn->error;
    }
}

// Получаем список категорий для выпадающего списка
$categories_sql = "SELECT * FROM categories ORDER BY name";
$categories_result = $conn->query($categories_sql);

// Определяем условие фильтрации для SQL запроса
$filter_condition = "";
$filter_category_id = "";
$search_condition = "";
$search_query = "";
$sort_condition = "";
$selected_sort = "";

// Обработка поиска товара
if (isset($_GET['search']) && !empty(trim($_GET['search']))) {
    $search_query = $conn->real_escape_string(trim($_GET['search']));
    $search_condition = " AND t.name LIKE '%$search_query%'";
}

// Обработка фильтрации по категории
if (isset($_GET['apply_filter'])) {
    if (isset($_GET['category_filter'])) {
        $filter_category_id = intval($_GET['category_filter']);
        if ($filter_category_id > 0) {
            $filter_condition = " AND t.category_id = $filter_category_id";
        }
    }
}

// Обработка сортировки по цене
if(isset($_POST["sort"]) && isset($_POST["cena"])) {
    $cena_sort = $_POST["cena"];
    
    if($cena_sort == "min") {
        $sort_condition = " ORDER BY t.cena ASC";
        $selected_sort = "min";
    } elseif($cena_sort == "max") {
        $sort_condition = " ORDER BY t.cena DESC";
        $selected_sort = "max";
    } else {
        $sort_condition = "";
        $selected_sort = "0";
    }
}

// Получаем товары с информацией о категориях
$sql = "SELECT t.*, c.name as category_name 
        FROM tovars t 
        LEFT JOIN categories c ON t.category_id = c.id 
        WHERE 1=1 $filter_condition $search_condition";

// Добавляем сортировку
if (!empty($sort_condition)) {
    $sql .= $sort_condition;
} else {
    $sql .= " ORDER BY t.id DESC";
}

$result = $conn->query($sql);

// Получаем товары в корзине для отображения
$cart_items = array();
$cart_total = 0;

if (!empty($_SESSION['cart'])) {
    $cart_ids = array_keys($_SESSION['cart']);
    $cart_ids_str = implode(',', $cart_ids);
    $cart_sql = "SELECT * FROM tovars WHERE id IN ($cart_ids_str)";
    $cart_result = $conn->query($cart_sql);
    
    if ($cart_result) {
        while ($item = $cart_result->fetch_assoc()) {
            $item['cart_quantity'] = $_SESSION['cart'][$item['id']];
            $item['item_total'] = $item['cena'] * $item['cart_quantity'];
            $cart_total += $item['item_total'];
            $cart_items[] = $item;
        }
    }
}

// Получаем аватарку админа
$admin_avatar = "";
$avatar_sql = "SELECT avatar FROM users WHERE id_user = 1";
$avatar_result = $conn->query($avatar_sql);
if($avatar_result && $avatar_result->num_rows > 0) {
    $avatar_row = $avatar_result->fetch_assoc();
    $admin_avatar = $avatar_row['avatar'] ?? "";
}

// Обработка аватарки админа (оставляем без изменений)
$avatar_message = "";
if(isset($_POST['upload_admin_avatar']) && isset($_FILES['admin_avatar'])) {
    $admin_id = 1;
    $upload_dir = "avatars/";
    $absolute_upload_dir = __DIR__ . "/" . $upload_dir;
    
    if (!file_exists($absolute_upload_dir)) {
        mkdir($absolute_upload_dir, 0777, true);
    }
    
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/jpg'];
    $max_size = 2 * 1024 * 1024;
    
    if ($_FILES['admin_avatar']['error'] == 0) {
        if (in_array($_FILES['admin_avatar']['type'], $allowed_types) && $_FILES['admin_avatar']['size'] <= $max_size) {
            $extension = pathinfo($_FILES['admin_avatar']['name'], PATHINFO_EXTENSION);
            $filename = "admin_" . $admin_id . "_" . time() . "." . $extension;
            $target_path = $upload_dir . $filename;
            $absolute_path = $absolute_upload_dir . $filename;
            
            if (move_uploaded_file($_FILES['admin_avatar']['tmp_name'], $absolute_path)) {
                $old_avatar_sql = "SELECT avatar FROM users WHERE id_user = $admin_id";
                $old_avatar_result = $conn->query($old_avatar_sql);
                if ($old_avatar_result && $old_avatar_result->num_rows > 0) {
                    $old_avatar_row = $old_avatar_result->fetch_assoc();
                    if (!empty($old_avatar_row['avatar']) && file_exists(__DIR__ . "/" . $old_avatar_row['avatar'])) {
                        unlink(__DIR__ . "/" . $old_avatar_row['avatar']);
                    }
                }
                
                $update_sql = "UPDATE users SET avatar = '$target_path' WHERE id_user = $admin_id";
                if ($conn->query($update_sql)) {
                    $avatar_message = "Аватарка успешно обновлена!";
                } else {
                    $avatar_message = "Ошибка при сохранении в БД: " . $conn->error;
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

if(isset($_POST['delete_admin_avatar'])) {
    $admin_id = 1;
    
    $avatar_sql = "SELECT avatar FROM users WHERE id_user = $admin_id";
    $avatar_result = $conn->query($avatar_sql);
    if ($avatar_result && $avatar_result->num_rows > 0) {
        $avatar_row = $avatar_result->fetch_assoc();
        if (!empty($avatar_row['avatar']) && file_exists(__DIR__ . "/" . $avatar_row['avatar'])) {
            unlink(__DIR__ . "/" . $avatar_row['avatar']);
        }
    }
    
    $update_sql = "UPDATE users SET avatar = NULL WHERE id_user = $admin_id";
    if ($conn->query($update_sql)) {
        $avatar_message = "Аватарка удалена";
    } else {
        $avatar_message = "Ошибка при удалении аватарки";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Управление товарами и категориями</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background-color: #f5f5f5;
        }
        h1, h2, h3, h4 { color: #333; }
        h1 { text-align: center; color: #4CAF50; }
        .message {
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 4px;
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            border-color: #f5c6cb;
        }
        .container {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }
        .products-section {
            flex: 2;
            min-width: 600px;
        }
        .cart-section {
            flex: 1;
            min-width: 350px;
            background-color: white;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            padding: 20px;
            position: sticky;
            top: 20px;
            max-height: 80vh;
            overflow-y: auto;
        }
        .cart-section h3 {
            margin-top: 0;
            border-bottom: 2px solid #4CAF50;
            padding-bottom: 10px;
        }
        .cart-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        .cart-item-info { flex: 2; }
        .cart-item-name { font-weight: bold; }
        .cart-item-price { color: #666; font-size: 12px; }
        .cart-item-quantity { flex: 1; text-align: center; }
        .cart-item-quantity input { width: 60px; padding: 5px; text-align: center; }
        .cart-item-total { flex: 1; text-align: right; font-weight: bold; color: #4CAF50; }
        .cart-item-remove a { color: #f44336; text-decoration: none; font-size: 18px; margin-left: 10px; }
        .cart-total {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 2px solid #ddd;
            text-align: right;
            font-size: 18px;
            font-weight: bold;
        }
        .cart-buttons {
            margin-top: 15px;
            display: flex;
            gap: 10px;
        }
        .cart-buttons button, .cart-buttons a {
            flex: 1;
            padding: 10px;
            text-align: center;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
        }
        .checkout-btn { background-color: #4CAF50; color: white; }
        .clear-cart-btn { background-color: #6c757d; color: white; }
        .update-cart-btn { background-color: #2196F3; color: white; }
        .empty-cart { text-align: center; color: #999; padding: 30px; }
        .add-to-cart-btn {
            display: inline-block;
            padding: 5px 10px;
            background-color: #4CAF50;
            color: white;
            text-decoration: none;
            border-radius: 3px;
            font-size: 12px;
        }
        .add-to-cart-btn:hover { background-color: #45a049; }
        .cart-badge {
            background-color: #f44336;
            color: white;
            border-radius: 50%;
            padding: 2px 6px;
            font-size: 12px;
            margin-left: 5px;
        }
        .add-product-section, .category-form, .category-list, .filter-section, .search-section {
            background-color: white;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .add-product-form {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            align-items: flex-end;
        }
        .form-group {
            flex: 1;
            min-width: 150px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #555;
            font-size: 13px;
        }
        .form-group input, .form-group select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 3px;
            box-sizing: border-box;
        }
        .add-product-btn {
            padding: 10px 20px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            font-size: 14px;
            height: 38px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background-color: white;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        th {
            background-color: #4CAF50;
            color: white;
            padding: 12px;
            text-align: left;
        }
        td {
            padding: 10px;
            border-bottom: 1px solid #ddd;
        }
        tr:hover { background-color: #f5f5f5; }
        .delete-btn, .edit-cat-btn {
            display: inline-block;
            padding: 5px 10px;
            color: white;
            text-decoration: none;
            border-radius: 3px;
            font-size: 13px;
            margin: 2px;
        }
        .delete-btn { background-color: #f44336; }
        .delete-btn:hover { background-color: #d32f2f; }
        .edit-cat-btn { background-color: #2196F3; }
        .edit-cat-btn:hover { background-color: #1976D2; }
        .checkbox-col { width: 30px; text-align: center; }
        .actions {
            margin-bottom: 15px;
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
        }
        .delete-selected-btn {
            padding: 8px 15px;
            background-color: #f44336;
            color: white;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            font-size: 14px;
        }
        .select-all { cursor: pointer; user-select: none; }
        .category-form input[type="text"], .category-form textarea {
            width: 100%;
            padding: 8px;
            margin: 5px 0 15px 0;
            border: 1px solid #ddd;
            border-radius: 3px;
            box-sizing: border-box;
        }
        .category-form textarea { height: 80px; resize: vertical; }
        .category-form button {
            padding: 10px 15px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            font-size: 14px;
        }
        .category-list {
            background-color: white;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            margin-top: 20px;
        }
        .category-item {
            padding: 10px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .category-item:last-child { border-bottom: none; }
        .category-info { flex: 1; }
        .category-name { font-weight: bold; color: #333; }
        .category-desc { font-size: 12px; color: #666; margin-top: 3px; }
        .category-actions { display: flex; gap: 5px; }
        .category-select {
            padding: 5px;
            border: 1px solid #ddd;
            border-radius: 3px;
            background-color: white;
            font-size: 13px;
        }
        .update-cat-btn {
            padding: 5px 10px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            font-size: 12px;
        }
        .filter-section {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }
        .filter-block {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }
        .filter-select {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 3px;
            min-width: 200px;
        }
        .filter-btn {
            padding: 8px 15px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 3px;
            cursor: pointer;
        }
        .reset-btn {
            padding: 8px 15px;
            background-color: #6c757d;
            color: white;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
        }
        .product-count {
            margin-top: 10px;
            color: #666;
            font-size: 13px;
        }
        .no-data { text-align: center; color: #999; padding: 30px; }
        .sort-radio-group {
            display: flex;
            gap: 15px;
            align-items: center;
        }
        .sort-radio-group label {
            font-weight: normal;
            display: flex;
            align-items: center;
            gap: 3px;
        }
        .vertical-divider {
            width: 1px;
            height: 30px;
            background-color: #ddd;
        }
        .search-section {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }
        .search-form {
            flex: 2;
            display: flex;
            gap: 10px;
            align-items: center;
            min-width: 300px;
        }
        .search-input {
            flex: 1;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 3px;
            font-size: 14px;
        }
        .search-btn {
            padding: 8px 20px;
            background-color: #2196F3;
            color: white;
            border: none;
            border-radius: 3px;
            cursor: pointer;
        }
        .search-clear {
            padding: 8px 15px;
            background-color: #6c757d;
            color: white;
            text-decoration: none;
            border-radius: 3px;
        }
        .highlight { background-color: #ffeb3b; font-weight: bold; }
        .admin-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #4CAF50;
        }
        .header-user {
            display: flex;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
        }
        .avatar-upload-form {
            background-color: white;
            padding: 15px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
        }
        .avatar-upload-form img {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #4CAF50;
        }
        .avatar-buttons {
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
        }
        .avatar-buttons .btn {
            padding: 8px 15px;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            font-size: 13px;
        }
        .btn-upload { background-color: #4CAF50; color: white; }
        .btn-delete { background-color: #f44336; color: white; }
        .file-input { padding: 5px; }
    </style>
</head>
<body>

<h1>Управление товарами и категориями</h1>

<!-- Сообщения -->
<?php if (isset($avatar_message) && !empty($avatar_message)): ?>
    <div class="message <?php echo strpos($avatar_message, 'Ошибка') !== false ? 'error-message' : ''; ?>">
        <?php echo htmlspecialchars($avatar_message); ?>
    </div>
<?php endif; ?>

<?php if (isset($message)): ?>
    <div class="message"><?php echo htmlspecialchars($message); ?></div>
<?php endif; ?>

<?php if (isset($cat_message)): ?>
    <div class="message"><?php echo htmlspecialchars($cat_message); ?></div>
<?php endif; ?>

<?php if (!empty($cart_message)): ?>
    <div class="message"><?php echo htmlspecialchars($cart_message); ?></div>
<?php endif; ?>

<?php if (!empty($order_message)): ?>
    <div class="message <?php echo strpos($order_message, 'Ошибка') !== false ? 'error-message' : ''; ?>">
        <?php echo htmlspecialchars($order_message); ?>
    </div>
<?php endif; ?>

<!-- Шапка с пользователем и формой загрузки аватарки -->
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 15px;">
    <div class="header-user">
        <?php 
        $avatar_img = (!empty($admin_avatar) && file_exists($admin_avatar)) 
            ? $admin_avatar 
            : "https://www.gravatar.com/avatar/" . md5("admin") . "?d=mp&s=50";
        ?>
        <img src="<?php echo $avatar_img; ?>" class="admin-avatar" onerror="this.src='https://www.gravatar.com/avatar/<?php echo md5("admin"); ?>?d=mp&s=50'">
        <span style="padding: 8px 15px; background-color: #4CAF50; color: white; border-radius: 3px;">
            👤 Администратор
        </span>
        <a href="profile.php" style="padding: 8px 15px; background-color: #2196F3; color: white; text-decoration: none; border-radius: 3px;">
            📁 Личный кабинет
        </a>
        <a href="logout.php" style="padding: 8px 15px; background-color: #f44336; color: white; text-decoration: none; border-radius: 3px;">
            🚪 Выйти
        </a>
    </div>
    <div>
        <a href="categories.php" style="display: inline-block; padding: 10px 20px; background-color: #4CAF50; color: white; text-decoration: none; border-radius: 3px; font-weight: bold;">
            📁 Управление категориями
        </a>
    </div>
</div>

<!-- Форма загрузки аватарки для админа -->
<div class="avatar-upload-form">
    <div>
        <strong>Аватарка администратора</strong>
    </div>
    <img src="<?php echo $avatar_img; ?>" alt="Admin Avatar" onerror="this.src='https://www.gravatar.com/avatar/<?php echo md5("admin"); ?>?d=mp&s=60'">
    <form method="post" enctype="multipart/form-data" style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
        <input type="file" name="admin_avatar" accept="image/jpeg,image/png,image/gif,image/webp" class="file-input" required>
        <div class="avatar-buttons">
            <button type="submit" name="upload_admin_avatar" class="btn btn-upload">📤 Загрузить аватар</button>
            <?php if(!empty($admin_avatar)): ?>
                <button type="submit" name="delete_admin_avatar" class="btn btn-delete" onclick="return confirm('Удалить аватарку?')">🗑 Удалить</button>
            <?php endif; ?>
        </div>
    </form>
    <span style="font-size: 11px; color: #666;">Форматы: JPG, PNG, GIF, WEBP (макс. 2MB)</span>
</div>

<!-- Поиск товара -->
<div class="search-section">
    <div class="search-form">
        <form method="get" action="" style="display: flex; gap: 10px; width: 100%;">
            <input type="text" name="search" class="search-input" 
                   placeholder="🔍 Поиск товара по названию..." 
                   value="<?php echo htmlspecialchars($search_query); ?>">
            <button type="submit" class="search-btn">Найти</button>
            <?php if (!empty($search_query)): ?>
                <a href="deda.php" class="search-clear">Очистить</a>
            <?php endif; ?>
        </form>
    </div>
    <?php if (!empty($search_query)): ?>
        <div class="search-info">
            Результаты поиска по запросу: "<strong><?php echo htmlspecialchars($search_query); ?></strong>"
        </div>
    <?php endif; ?>
</div>

<!-- Секция добавления нового товара -->
<div class="add-product-section">
    <h3>Добавление нового товара</h3>
    <form method="post" action="" enctype="multipart/form-data" class="add-product-form">
        <div class="form-group">
            <label>Название товара</label>
            <input type="text" name="name" required>
        </div>
        <div class="form-group">
            <label>Цена</label>
            <input type="number" name="cena" step="0.01" required>
        </div>
        <div class="form-group">
            <label>Количество</label>
            <input type="number" name="kol" required>
        </div>
        <div class="form-group">
            <label>Срок годности</label>
            <input type="date" name="srok">
        </div>
        <div class="form-group">
            <label>Категория</label>
            <select name="category_id">
                <option value="">-- Без категории --</option>
                <?php if ($categories_result && $categories_result->num_rows > 0): ?>
                    <?php $categories_result->data_seek(0); ?>
                    <?php while($cat = $categories_result->fetch_assoc()): ?>
                        <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                    <?php endwhile; ?>
                <?php endif; ?>
            </select>
        </div>
        <div class="form-group">
            <label>Изображение</label>
            <input type="file" name="image" accept="image/*">
        </div>
        <button type="submit" name="add_product" class="add-product-btn">Добавить товар</button>
    </form>
</div>

<!-- Фильтр и сортировка -->
<div class="filter-section">
    <div class="filter-block">
        <label for="category_filter">Категория:</label>
        <form method="get" action="" style="display: flex; gap: 10px; align-items: center; margin: 0;">
            <?php if (!empty($search_query)): ?>
                <input type="hidden" name="search" value="<?php echo htmlspecialchars($search_query); ?>">
            <?php endif; ?>
            <select name="category_filter" id="category_filter" class="filter-select">
                <option value="">Все категории</option>
                <?php if ($categories_result && $categories_result->num_rows > 0): ?>
                    <?php $categories_result->data_seek(0); ?>
                    <?php while($cat = $categories_result->fetch_assoc()): ?>
                        <option value="<?php echo $cat['id']; ?>" <?php echo ($filter_category_id == $cat['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat['name']); ?>
                        </option>
                    <?php endwhile; ?>
                <?php endif; ?>
            </select>
            <button type="submit" name="apply_filter" class="filter-btn">Применить</button>
        </form>
    </div>
    
    <div class="vertical-divider"></div>
    
    <div class="filter-block">
        <label>Сортировка по цене:</label>
        <form method="post" action="" style="display: flex; gap: 10px; align-items: center; margin: 0;">
            <div class="sort-radio-group">
                <label>
                    <input type="radio" name="cena" value="0" <?php echo ($selected_sort == '0' || $selected_sort == '') ? 'checked' : ''; ?>> Без сортировки
                </label>
                <label>
                    <input type="radio" name="cena" value="min" <?php echo ($selected_sort == 'min') ? 'checked' : ''; ?>> По возрастанию
                </label>
                <label>
                    <input type="radio" name="cena" value="max" <?php echo ($selected_sort == 'max') ? 'checked' : ''; ?>> По убыванию
                </label>
            </div>
            <button type="submit" name="sort" class="filter-btn">Сортировать</button>
        </form>
    </div>
    
    <?php if (!empty($filter_category_id) || !empty($selected_sort) || !empty($search_query)): ?>
        <a href="deda.php" class="reset-btn">Сбросить всё</a>
    <?php endif; ?>
</div>

<div class="container">
    <!-- Секция товаров -->
    <div class="products-section">
        <form method="post" action="">
            <div class="actions">
                <label class="select-all">
                    <input type="checkbox" id="selectAll" onclick="toggleAll(this)"> Выбрать все
                </label>
                <button type="submit" name="delete_selected" class="delete-selected-btn" onclick="return confirm('Удалить выбранные товары?')">Удалить выбранные</button>
            </div>

            <table>
                <thead>
                    <tr>
                        <th class="checkbox-col"></th>
                        <th>ID</th>
                        <th>Изображение</th>
                        <th>Наименование</th>
                        <th>Цена</th>
                        <th>Количество</th>
                        <th>Срок годности</th>
                        <th>Категория</th>
                        <th>Действие</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                if ($result && $result->num_rows > 0) {
                    $displayed_count = 0;
                    while($row = $result->fetch_assoc()) {
                        $displayed_count++;
                        
                        $name_display = htmlspecialchars($row["name"]);
                        if (!empty($search_query)) {
                            $name_display = preg_replace('/(' . preg_quote($search_query, '/') . ')/i', '<span class="highlight">$1</span>', $name_display);
                        }
                        
                        echo "<tr>";
                        echo "<td class='checkbox-col'><input type='checkbox' name='selected_ids[]' value='" . $row["id"] . "' class='item-checkbox'></td>";
                        echo "<td>" . $row["id"] . "</td>";
                        
                        echo "<td>";
                        if (!empty($row['image_path']) && file_exists($row['image_path'])) {
                            echo "<img src='" . $row['image_path'] . "' alt='Изображение' style='width: 50px; height: 50px; object-fit: cover; border-radius: 3px;'>";
                        } else {
                            echo "<span style='color: #999; font-size: 11px;'>Нет фото</span>";
                        }
                        echo "</td>";
                        
                        echo "<td>" . $name_display . "</td>";
                        echo "<td>" . $row["cena"] . " руб.</td>";
                        echo "<td>" . $row["kol"] . " шт.</td>";
                        echo "<td>" . ($row["srok"] ? $row["srok"] : '-') . "</td>";
                        
                        echo "<td>";
                        echo "<form method='post' action='' style='display: flex; gap: 5px;'>";
                        echo "<input type='hidden' name='product_id' value='" . $row["id"] . "'>";
                        echo "<select name='category_id' class='category-select' style='width: 120px;'>";
                        echo "<option value=''>Без категории</option>";
                        
                        if ($categories_result) {
                            $categories_result->data_seek(0);
                            while($cat = $categories_result->fetch_assoc()) {
                                $selected = ($row['category_id'] == $cat['id']) ? 'selected' : '';
                                echo "<option value='" . $cat['id'] . "' $selected>" . htmlspecialchars($cat['name']) . "</option>";
                            }
                        }
                        
                        echo "</select>";
                        echo "<button type='submit' name='update_category' class='update-cat-btn'>Обновить</button>";
                        echo "</form>";
                        echo "</td>";
                        
                        echo "<td>";
                        echo "<a href='?add_to_cart=" . $row["id"] . "' class='add-to-cart-btn' style='margin-right: 5px;'>🛒 В корзину</a>";
                        echo "<a href='update.php?id=" . $row["id"] . "' class='edit-cat-btn' style='background-color: #2196F3; margin-right: 5px;'>Редактировать</a>";
                        echo "<a href='?delete_id=" . $row["id"] . "' class='delete-btn' onclick='return confirm(\"Удалить товар?\")'>Удалить</a>";
                        echo "</td>";
                        
                        echo "</tr>";
                    }
                    
                    if ($displayed_count == 0) {
                        echo "<tr><td colspan='9' class='no-data'>Нет товаров, соответствующих фильтру</td></tr>";
                    }
                } else {
                    echo "<tr><td colspan='9' class='no-data'>Нет товаров в базе данных</td></tr>";
                }
                ?>
                </tbody>
            </table>
            
            <div class="product-count">
                <?php
                $count_sql = "SELECT COUNT(*) as total FROM tovars WHERE 1=1";
                if (!empty($filter_category_id)) {
                    $count_sql = "SELECT COUNT(*) as total FROM tovars WHERE category_id = $filter_category_id";
                }
                if (!empty($search_query)) {
                    $count_sql = "SELECT COUNT(*) as total FROM tovars WHERE name LIKE '%$search_query%'";
                    if (!empty($filter_category_id)) {
                        $count_sql = "SELECT COUNT(*) as total FROM tovars WHERE category_id = $filter_category_id AND name LIKE '%$search_query%'";
                    }
                }
                $count_result = $conn->query($count_sql);
                if ($count_result) {
                    $count_row = $count_result->fetch_assoc();
                    echo "Всего товаров: " . $count_row['total'];
                }
                
                if (!empty($selected_sort)) {
                    if ($selected_sort == 'min') {
                        echo " (отсортировано по возрастанию цены)";
                    } elseif ($selected_sort == 'max') {
                        echo " (отсортировано по убыванию цены)";
                    }
                }
                
                if (!empty($search_query) && $result) {
                    echo " | Найдено товаров: " . $result->num_rows;
                }
                ?>
            </div>
        </form>
    </div>
    
    <!-- Корзина -->
    <div class="cart-section">
        <h3>🛒 Корзина <span class="cart-badge"><?php echo count($_SESSION['cart']); ?></span></h3>
        
        <?php if (empty($cart_items)): ?>
            <div class="empty-cart">
                <p>Корзина пуста</p>
                <p style="font-size: 12px;">Добавьте товары, нажав кнопку "В корзину"</p>
            </div>
        <?php else: ?>
            <form method="post" action="">
                <?php foreach ($cart_items as $item): ?>
                    <div class="cart-item">
                        <div class="cart-item-info">
                            <div class="cart-item-name"><?php echo htmlspecialchars($item['name']); ?></div>
                            <div class="cart-item-price"><?php echo $item['cena']; ?> руб/шт</div>
                        </div>
                        <div class="cart-item-quantity">
                            <input type="number" name="quantity[<?php echo $item['id']; ?>]" 
                                   value="<?php echo $item['cart_quantity']; ?>" 
                                   min="1" max="<?php echo $item['kol']; ?>">
                        </div>
                        <div class="cart-item-total">
                            <?php echo number_format($item['item_total'], 2); ?> руб
                        </div>
                        <div class="cart-item-remove">
                            <a href="?remove_from_cart=<?php echo $item['id']; ?>" onclick="return confirm('Удалить товар из корзины?')">✖</a>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <div class="cart-total">
                    Итого: <?php echo number_format($cart_total, 2); ?> руб
                </div>
                
                <div class="cart-buttons">
                    <button type="submit" name="update_cart" class="update-cart-btn">🔄 Обновить</button>
                    <a href="?clear_cart=1" class="clear-cart-btn" onclick="return confirm('Очистить корзину?')">🗑 Очистить</a>
                </div>
                
                <div class="cart-buttons" style="margin-top: 10px;">
                    <button type="submit" name="checkout" class="checkout-btn" onclick="return confirm('Оформить заказ?')">✅ Оформить заказ</button>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>

<!-- Секция категорий -->
<div class="category-list">
    <h3>Существующие категории</h3>
    <?php
    if ($categories_result && $categories_result->num_rows > 0) {
        $categories_result->data_seek(0);
        while($cat = $categories_result->fetch_assoc()) {
            $count_sql = "SELECT COUNT(*) as count FROM tovars WHERE category_id = " . $cat['id'];
            $count_res = $conn->query($count_sql);
            $count = ($count_res) ? $count_res->fetch_assoc()['count'] : 0;
            
            echo "<div class='category-item'>";
            echo "<div class='category-info'>";
            echo "<div class='category-name'>" . htmlspecialchars($cat['name']) . " <span style='color: #666; font-size: 11px;'>(товаров: $count)</span></div>";
            if (!empty($cat['description'])) {
                echo "<div class='category-desc'>" . htmlspecialchars($cat['description']) . "</div>";
            }
            echo "</div>";
            echo "<div class='category-actions'>";
            $filter_url = "?category_filter=" . $cat['id'] . "&apply_filter=1";
            if (!empty($search_query)) {
                $filter_url .= "&search=" . urlencode($search_query);
            }
            echo "<a href='" . $filter_url . "' class='edit-cat-btn' style='background-color: #2196F3;'>Фильтр</a>";
            
            if ($count == 0) {
                echo "<a href='?delete_category=" . $cat['id'] . "' class='delete-btn' onclick='return confirm(\"Удалить категорию " . htmlspecialchars($cat['name']) . "?\")'>Удалить</a>";
            } else {
                echo "<span style='color: #999; font-size: 11px;'>есть товары</span>";
            }
            echo "</div>";
            echo "</div>";
        }
    } else {
        echo "<p style='text-align: center; color: #999;'>Нет категорий</p>";
    }
    ?>
    
    <!-- Форма добавления категории -->
    <div class="category-form" style="margin-top: 20px;">
        <h4>Добавить новую категорию</h4>
        <form method="post" action="">
            <input type="text" name="cat_name" placeholder="Название категории" required>
            <textarea name="cat_description" placeholder="Описание категории"></textarea>
            <button type="submit" name="add_category">Добавить категорию</button>
        </form>
    </div>
</div>

<script>
function toggleAll(source) {
    var checkboxes = document.getElementsByClassName('item-checkbox');
    for(var i=0; i<checkboxes.length; i++) {
        checkboxes[i].checked = source.checked;
    }
}
</script>

</body>
</html>

<?php
$conn->close();
?>