<?php
// Включаем отображение ошибок
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Подключаемся к базе данных
$servername = "localhost";
$username = "bgcdarep";
$password = "i3h7Gj";
$dbname = "bgcdarep_m1";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Ошибка подключения: " . $conn->connect_error);
}

// ПОЛНОСТЬЮ ОТКЛЮЧАЕМ ПРОВЕРКУ ВНЕШНИХ КЛЮЧЕЙ ДЛЯ ВСЕЙ СЕССИИ
$conn->query("SET FOREIGN_KEY_CHECKS=0");

// Простая обработка добавления категории
if (isset($_POST['add_category'])) {
    $cat_name = trim($_POST['cat_name']);
    
    if (!empty($cat_name)) {
        $cat_name = $conn->real_escape_string($cat_name);
        $sql = "INSERT INTO categories (name) VALUES ('$cat_name')";
        
        if ($conn->query($sql) === TRUE) {
            $message = "✅ Категория '$cat_name' успешно добавлена!";
        } else {
            $message = "❌ Ошибка: " . $conn->error;
        }
    }
}

// Простая обработка удаления категории
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    
    // Сначала делаем все товары без категории
    $conn->query("UPDATE tovars SET category_id = NULL WHERE category_id = $id");
    
    // Потом удаляем категорию
    if ($conn->query("DELETE FROM categories WHERE id = $id")) {
        $message = "✅ Категория удалена";
    } else {
        $message = "❌ Ошибка: " . $conn->error;
    }
}

// Получаем список категорий
$categories = $conn->query("SELECT * FROM categories ORDER BY name");

// Получаем количество товаров в каждой категории отдельным запросом
$products_count = [];
$count_res = $conn->query("SELECT category_id, COUNT(*) as count FROM tovars GROUP BY category_id");
while($row = $count_res->fetch_assoc()) {
    $products_count[$row['category_id']] = $row['count'];
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Управление категориями</title>
    <style>
        body { font-family: Arial; margin: 20px; background: #f5f5f5; }
        .container { max-width: 600px; margin: 0 auto; }
        h1 { color: #4CAF50; text-align: center; }
        .message { 
            padding: 10px; 
            margin: 10px 0; 
            border-radius: 4px;
            background: #d4edda; 
            color: #155724; 
            border: 1px solid #c3e6cb; 
        }
        .form-box { 
            background: white; 
            padding: 20px; 
            border-radius: 5px; 
            margin-bottom: 20px; 
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        input[type="text"] { 
            width: 100%; 
            padding: 8px; 
            margin: 10px 0; 
            border: 1px solid #ddd; 
            border-radius: 3px; 
            box-sizing: border-box;
        }
        button { 
            background: #4CAF50; 
            color: white; 
            padding: 10px 20px; 
            border: none; 
            border-radius: 3px; 
            cursor: pointer; 
        }
        button:hover { background: #45a049; }
        table { 
            width: 100%; 
            background: white; 
            border-collapse: collapse; 
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        th { 
            background: #4CAF50; 
            color: white; 
            padding: 10px; 
            text-align: left; 
        }
        td { 
            padding: 10px; 
            border-bottom: 1px solid #ddd; 
        }
        .delete-btn { 
            color: #f44336; 
            text-decoration: none; 
            padding: 5px 10px; 
            border: 1px solid #f44336; 
            border-radius: 3px; 
        }
        .delete-btn:hover { 
            background: #f44336; 
            color: white; 
        }
        .back-link { 
            display: inline-block; 
            margin-bottom: 20px; 
            color: #4CAF50; 
            text-decoration: none; 
        }
        .badge { 
            background: #4CAF50; 
            color: white; 
            padding: 2px 8px; 
            border-radius: 10px; 
            font-size: 12px; 
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📁 Управление категориями</h1>
        
        <a href="deda.php" class="back-link">← Назад к товарам</a>
        
        <?php if (isset($message)): ?>
            <div class="message"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <div class="form-box">
            <h3>Добавить категорию</h3>
            <form method="post">
                <input type="text" name="cat_name" placeholder="Название категории" required>
                <button type="submit" name="add_category">Добавить</button>
            </form>
        </div>
        
        <h3>Список категорий</h3>
        
        <?php if ($categories->num_rows > 0): ?>
            <table>
                <tr>
                    <th>ID</th>
                    <th>Название</th>
                    <th>Товаров</th>
                    <th>Действие</th>
                </tr>
                <?php while($cat = $categories->fetch_assoc()): ?>
                <tr>
                    <td>#<?php echo $cat['id']; ?></td>
                    <td><strong><?php echo htmlspecialchars($cat['name']); ?></strong></td>
                    <td>
                        <span class="badge">
                            <?php echo isset($products_count[$cat['id']]) ? $products_count[$cat['id']] : 0; ?>
                        </span>
                    </td>
                    <td>
                        <a href="?delete=<?php echo $cat['id']; ?>" 
                           class="delete-btn"
                           onclick="return confirm('Удалить категорию?')">
                            Удалить
                        </a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </table>
        <?php else: ?>
            <p style="text-align: center; padding: 20px; background: white; border-radius: 5px;">
                Нет категорий
            </p>
        <?php endif; ?>
    </div>
</body>
</html>

<?php
// Включаем обратно проверку внешних ключей
$conn->query("SET FOREIGN_KEY_CHECKS=1");
$conn->close();
?>