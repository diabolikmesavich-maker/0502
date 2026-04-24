<?php
$servername = "localhost";
$username = "bgcdarep";
$password = "i3h7Gj";
$dbname = "bgcdarep_m1";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Ошибка подключения: " . $conn->connect_error);
}

// Проверяем и создаем таблицу categories если её нет
$check_categories_table = "SHOW TABLES LIKE 'categories'";
$categories_table_exists = $conn->query($check_categories_table);

if ($categories_table_exists->num_rows == 0) {
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

if ($column_exists->num_rows == 0) {
    $add_column = "ALTER TABLE tovars ADD COLUMN category_id INT DEFAULT NULL";
    $conn->query($add_column);
    
    // Добавляем внешний ключ
    $add_foreign = "ALTER TABLE tovars ADD FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL";
    $conn->query($add_foreign);
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

// Получаем товары с информацией о категориях
$sql = "SELECT t.*, c.name as category_name 
        FROM tovars t 
        LEFT JOIN categories c ON t.category_id = c.id 
        ORDER BY t.id DESC";
$result = $conn->query($sql);
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
        
        h3, h4 {
            color: #333;
            margin-bottom: 20px;
        }
        
        h3 {
            text-align: center;
        }
        
        .message {
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 4px;
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
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
        
        .categories-section {
            flex: 1;
            min-width: 300px;
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
        
        tr:hover {
            background-color: #f5f5f5;
        }
        
        .delete-btn, .edit-cat-btn {
            display: inline-block;
            padding: 5px 10px;
            color: white;
            text-decoration: none;
            border-radius: 3px;
            font-size: 13px;
            margin: 2px;
        }
        
        .delete-btn {
            background-color: #f44336;
        }
        
        .delete-btn:hover {
            background-color: #d32f2f;
        }
        
        .edit-cat-btn {
            background-color: #2196F3;
        }
        
        .edit-cat-btn:hover {
            background-color: #1976D2;
        }
        
        .category-badge {
            display: inline-block;
            padding: 3px 8px;
            background-color: #e7f3ff;
            border: 1px solid #b8daff;
            border-radius: 15px;
            color: #004085;
            font-size: 12px;
        }
        
        .no-category {
            color: #999;
            font-style: italic;
        }
        
        .checkbox-col {
            width: 30px;
            text-align: center;
        }
        
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
        
        .delete-selected-btn:hover {
            background-color: #d32f2f;
        }
        
        .select-all {
            cursor: pointer;
            user-select: none;
        }
        
        .category-form {
            background-color: white;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .category-form input[type="text"],
        .category-form textarea {
            width: 100%;
            padding: 8px;
            margin: 5px 0 15px 0;
            border: 1px solid #ddd;
            border-radius: 3px;
            box-sizing: border-box;
        }
        
        .category-form textarea {
            height: 80px;
            resize: vertical;
        }
        
        .category-form button {
            padding: 10px 15px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            font-size: 14px;
        }
        
        .category-form button:hover {
            background-color: #45a049;
        }
        
        .category-list {
            background-color: white;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .category-item {
            padding: 10px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .category-item:last-child {
            border-bottom: none;
        }
        
        .category-info {
            flex: 1;
        }
        
        .category-name {
            font-weight: bold;
            color: #333;
        }
        
        .category-desc {
            font-size: 12px;
            color: #666;
            margin-top: 3px;
        }
        
        .category-actions {
            display: flex;
            gap: 5px;
        }
        
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
        
        .update-cat-btn:hover {
            background-color: #45a049;
        }
        
        .filter-section {
            background-color: white;
            padding: 15px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .filter-select {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 3px;
            margin-right: 10px;
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
        
        .filter-btn:hover {
            background-color: #45a049;
        }
        
        .product-count {
            margin-top: 10px;
            color: #666;
            font-size: 13px;
        }
    </style>
</head>
<body>

<h3>Управление товарами и категориями</h3>

<?php if (isset($message)): ?>
    <div class="message"><?php echo $message; ?></div>
<?php endif; ?>

<?php if (isset($cat_message)): ?>
    <div class="message"><?php echo $cat_message; ?></div>
<?php endif; ?>

<div class="container">
    <!-- Секция товаров -->
    <div class="products-section">
        <!-- Фильтр по категориям -->
        <div class="filter-section">
            <form method="get" action="" style="display: flex; align-items: center;">
                <select name="category_filter" class="filter-select">
                    <option value="">Все категории</option>
                    <?php 
                    $categories_result->data_seek(0);
                    while($cat = $categories_result->fetch_assoc()): 
                        $selected = (isset($_GET['category_filter']) && $_GET['category_filter'] == $cat['id']) ? 'selected' : '';
                    ?>
                        <option value="<?php echo $cat['id']; ?>" <?php echo $selected; ?>>
                            <?php echo $cat['name']; ?>
                        </option>
                    <?php endwhile; ?>
                </select>
                <button type="submit" class="filter-btn">Применить фильтр</button>
                <?php if(isset($_GET['category_filter']) && $_GET['category_filter'] != ''): ?>
                    <a href="?" style="margin-left: 10px; color: #666;">Сбросить фильтр</a>
                <?php endif; ?>
            </form>
        </div>

        <form method="post" action="">
            <div class="actions">
                <label class="select-all">
                    <input type="checkbox" id="selectAll" onclick="toggleAll(this)"> Выбрать все
                </label>
                <button type="submit" name="delete_selected" class="delete-selected-btn" onclick="return confirm('Удалить выбранные товары?')">Удалить выбранные</button>
            </div>

            <table>
                <tr>
                    <th class="checkbox-col"></th>
                    <th>ID</th>
                    <th>Наименование</th>
                    <th>Цена</th>
                    <th>Количество</th>
                    <th>Категория</th>
                    <th>Действие</th>
                </tr>
                
                <?php
                if ($result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                        // Применяем фильтр если выбран
                        if (isset($_GET['category_filter']) && $_GET['category_filter'] != '') {
                            if ($row['category_id'] != $_GET['category_filter']) {
                                continue;
                            }
                        }
                        
                        echo "<tr>";
                        echo "<td class='checkbox-col'><input type='checkbox' name='selected_ids[]' value='" . $row["id"] . "' class='item-checkbox'></td>";
                        echo "<td>" . $row["id"] . "</td>";
                        echo "<td>" . $row["name"] . "</td>";
                        echo "<td>" . $row["cena"] . " руб.</td>";
                        echo "<td>" . $row["kol"] . "</td>";
                        
                        // Ячейка с выбором категории
                        echo "<td>";
                        echo "<form method='post' action='' style='display: flex; gap: 5px;'>";
                        echo "<input type='hidden' name='product_id' value='" . $row["id"] . "'>";
                        echo "<select name='category_id' class='category-select' style='width: 120px;'>";
                        echo "<option value=''>Без категории</option>";
                        
                        $categories_result->data_seek(0);
                        while($cat = $categories_result->fetch_assoc()) {
                            $selected = ($row['category_id'] == $cat['id']) ? 'selected' : '';
                            echo "<option value='" . $cat['id'] . "' $selected>" . $cat['name'] . "</option>";
                        }
                        
                        echo "</select>";
                        echo "<button type='submit' name='update_category' class='update-cat-btn'>Обновить</button>";
                        echo "</form>";
                        echo "</td>";
                        
                        echo "<td><a href='?delete_id=" . $row["id"] . "' class='delete-btn' onclick='return confirm(\"Удалить товар?\")'>Удалить</a></td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='7' class='no-data'>Нет товаров в базе данных</td></tr>";
                }
                ?>
            </table>
            
            <div class="product-count">
                <?php
                // Подсчет количества товаров
                $count_sql = "SELECT COUNT(*) as total FROM tovars";
                if (isset($_GET['category_filter']) && $_GET['category_filter'] != '') {
                    $filter_id = intval($_GET['category_filter']);
                    $count_sql = "SELECT COUNT(*) as total FROM tovars WHERE category_id = $filter_id";
                }
                $count_result = $conn->query($count_sql);
                $count_row = $count_result->fetch_assoc();
                echo "Всего товаров: " . $count_row['total'];
                ?>
            </div>
        </form>
    </div>
    
    <!-- Секция категорий -->
    <div class="categories-section">
        <h4>Управление категориями</h4>
        
        
        <!-- Список категорий -->
        <div class="category-list">
            <h4>Существующие категории</h4>
            <?php
            $categories_result->data_seek(0);
            if ($categories_result->num_rows > 0) {
                while($cat = $categories_result->fetch_assoc()) {
                    // Получаем количество товаров в категории
                    $count_sql = "SELECT COUNT(*) as count FROM tovars WHERE category_id = " . $cat['id'];
                    $count_res = $conn->query($count_sql);
                    $count = $count_res->fetch_assoc()['count'];
                    
                    echo "<div class='category-item'>";
                    echo "<div class='category-info'>";
                    echo "<div class='category-name'>" . $cat['name'] . " <span style='color: #666; font-size: 11px;'>(товаров: $count)</span></div>";
                    if (!empty($cat['description'])) {
                        echo "<div class='category-desc'>" . $cat['description'] . "</div>";
                    }
                    echo "</div>";
                    echo "<div class='category-actions'>";
                    if ($count == 0) {
                        echo "<a href='?delete_category=" . $cat['id'] . "' class='delete-btn' onclick='return confirm(\"Удалить категорию?\")'>Удалить</a>";
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
        </div>
    </div>
</div>

<script>
function toggleAll(source) {
    checkboxes = document.getElementsByClassName('item-checkbox');
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