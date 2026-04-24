<?php
$connect = mysqli_connect('localhost', 'bgcdarep', 'i3h7Gj', 'bgcdarep_m1');

if (!$connect) {
    die('Ошибка подключения: ' . mysqli_connect_error());
}

// Проверяем, есть ли колонка image_path в таблице tovars
$check_column = mysqli_query($connect, "SHOW COLUMNS FROM tovars LIKE 'image_path'");
if (mysqli_num_rows($check_column) == 0) {
    mysqli_query($connect, "ALTER TABLE tovars ADD COLUMN image_path VARCHAR(255) DEFAULT NULL");
}

// Обработка загрузки нового товара с фото
if (isset($_POST['add_product'])) {
    $name = mysqli_real_escape_string($connect, $_POST['name']);
    $cena = floatval($_POST['cena']);
    $kol = intval($_POST['kol']);
    $srok = !empty($_POST['srok']) ? "'" . mysqli_real_escape_string($connect, $_POST['srok']) . "'" : "NULL";

    // Обработка загруженного изображения
    $image_path = "NULL";
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $upload_dir = "uploads/";
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $max_size = 5 * 1024 * 1024; // 5 MB

        if (in_array($_FILES['image']['type'], $allowed_types) && $_FILES['image']['size'] <= $max_size) {
            $extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $filename = uniqid() . '_' . time() . '.' . $extension;
            $target_path = $upload_dir . $filename;

            if (move_uploaded_file($_FILES['image']['tmp_name'], $target_path)) {
                $image_path = "'" . $target_path . "'";
            }
        }
    }

    $sql = "INSERT INTO tovars (name, cena, kol, srok, image_path) 
            VALUES ('$name', $cena, $kol, $srok, $image_path)";

    if (mysqli_query($connect, $sql)) {
        echo "<p style='color:green; text-align:center;'>Товар успешно добавлен</p>";
    } else {
        echo "<p style='color:red; text-align:center;'>Ошибка: " . mysqli_error($connect) . "</p>";
    }
}

// Удаление товара
if (isset($_GET['del'])) {
    $id = $_GET['del'];
    $delete = mysqli_query($connect, "DELETE FROM tovars WHERE id=$id");

    if ($delete) {
        echo "<p style='color:green; text-align:center;'>Товар с ID $id успешно удален</p>";
    } else {
        echo "<p style='color:red; text-align:center;'>Ошибка удаления: " . mysqli_error($connect) . "</p>";
    }
}

$result = mysqli_query($connect, "SELECT * FROM tovars");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Товары</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background-color: #f0f0f0;
        }
        
        h3 {
            text-align: center;
            color: #333;
            margin: 20px 0 30px;
            font-size: 24px;
        }
        
        /* Стили для формы добавления */
        .add-form {
            background: white;
            padding: 20px;
            margin-bottom: 30px;
            border: 2px solid #333;
            border-radius: 8px;
            max-width: 800px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .add-form h4 {
            margin-top: 0;
            color: #4CAF50;
        }
        
        .form-row {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 15px;
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
        
        .form-group input {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        
        .form-group input[type="file"] {
            padding: 3px;
        }
        
        .submit-btn {
            background-color: #4CAF50;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            width: 100%;
        }
        
        .submit-btn:hover {
            background-color: #45a049;
        }
        
        /* Сетка для карточек */
        .cards-grid {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 20px;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        /* Карточка товара */
        .card {
            width: 250px;
            background: white;
            border: 2px solid #333;
            border-radius: 8px;
            padding: 15px;
            box-shadow: 2px 2px 5px rgba(0,0,0,0.1);
            transition: transform 0.2s;
        }
        
        .card:hover {
            transform: scale(1.02);
            box-shadow: 4px 4px 10px rgba(0,0,0,0.2);
        }
        
        /* Изображение товара */
        .card-image {
            width: 100%;
            height: 150px;
            overflow: hidden;
            border: 1px solid #ddd;
            margin-bottom: 15px;
            border-radius: 5px;
            background: #f5f5f5;
        }
        
        .card-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .no-image {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #999;
            font-size: 14px;
            background: #e0e0e0;
        }
        
        /* Информация о товаре */
        .card-info {
            margin: 8px 0;
            padding: 5px 0;
            border-bottom: 1px solid #eee;
            font-size: 14px;
        }
        
        .card-info:last-child {
            border-bottom: none;
        }
        
        .card-label {
            font-weight: bold;
            color: #4CAF50;
            display: inline-block;
            width: 80px;
            font-size: 13px;
        }
        
        .card-value {
            color: #333;
        }
        
        /* Название товара */
        .card-title {
            font-size: 16px;
            font-weight: bold;
            color: #333;
            margin: 10px 0;
            text-align: center;
            border-bottom: 2px solid #4CAF50;
            padding-bottom: 5px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            margin-bottom: 30px;
            border: 2px solid #333;
        }
        
        th {
            background: #4CAF50;
            color: white;
            padding: 10px;
            border: 1px solid #333;
        }
        
        td {
            padding: 8px;
            border: 1px solid #333;
            text-align: center;
        }
        
        tr:nth-child(even) {
            background: #f9f9f9;
        }
        
        tr:hover {
            background: #f0f0f0;
        }
        
        .delete-link {
            color: #ff4444;
            text-decoration: none;
            font-weight: bold;
        }
        
        .delete-link:hover {
            color: #cc0000;
            text-decoration: underline;
        }
        
        /* Счетчик товаров */
        .count-info {
            text-align: center;
            margin: 20px 0;
            color: #666;
            font-size: 14px;
        }
        
        /* Сообщение об ошибке/пустоте */
        .empty-message {
            text-align: center;
            padding: 40px;
            background: white;
            border: 2px solid #333;
            border-radius: 8px;
            font-size: 18px;
            color: #666;
        }
        
        .delete-btn {
            display: inline-block;
            margin-top: 10px;
            padding: 5px 10px;
            background-color: #ff4444;
            color: white;
            text-decoration: none;
            border-radius: 3px;
            font-size: 13px;
        }
        
        .delete-btn:hover {
            background-color: #cc0000;
        }
    </style>
</head>
<body>
    <h3>Все товары</h3>
    
    <!-- Форма добавления нового товара с фото -->
    <div class="add-form">
        <h4>Добавить новый товар</h4>
        <form method="post" action="" enctype="multipart/form-data">
            <div class="form-row">
                <div class="form-group">
                    <label>Название товара *</label>
                    <input type="text" name="name" required>
                </div>
                <div class="form-group">
                    <label>Цена *</label>
                    <input type="number" name="cena" step="0.01" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Количество *</label>
                    <input type="number" name="kol" required>
                </div>
                <div class="form-group">
                    <label>Срок годности</label>
                    <input type="date" name="srok">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Изображение товара</label>
                    <input type="file" name="image" accept="image/*">
                </div>
            </div>
            <button type="submit" name="add_product" class="submit-btn">Добавить товар</button>
        </form>
    </div>
    
    <?php
    // Проверяем, есть ли товары в базе
    if (mysqli_num_rows($result) > 0) {
        // Счетчик товаров
        $total = mysqli_num_rows($result);
        echo "<div class='count-info'>Найдено товаров: $total</div>";
        
        echo "<div class='cards-grid'>";
        
        while ($row = mysqli_fetch_assoc($result)) {
            ?>
            
            <div class="card">
                <div class="card-image">
                    <?php
                    $random_images = [
                        'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQ6R98BHdgH4dMsBLMVJ26aZmUy0yK0nJYsHM8K2rhpinj_RNpjPhfKthnaYcvrUdac1trAnEqtjRHiT2p_fW-zK624Tut_OClnbD4u9k0&s=10',
                        'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRFL-zqHaz5yX_RBM2fAWMCqkFLPnZJy1PVww&s',
                        'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQ319iDSI8ZUDMaG1WnWKij-9cy41Vh2zGeWg&s',
                        'https://syromaniya.ru/upload/iblock/688/7bxrxftyni4v6tj92xero2oa32yli8zi/%D0%91%D0%B5%D0%B7%20%D0%B8%D0%BC%D0%B5%D0%BD%D0%B8-1.jpg',
                        'https://img.vkusvill.ru/pim/images/site_LargeWebP/a469c519-c4a7-457b-af46-d3cde7e81c3f.webp?1709548165.7559'
                    ];
                    
                    // Если есть загруженное изображение - показываем его
                    if (isset($row['image_path']) && !empty($row['image_path']) && file_exists($row['image_path'])) {
                        echo '<img src="' . $row['image_path'] . '" alt="' . $row['name'] . '">';
                    } else {
                        // Используем ID товара для выбора "случайной" картинки-заглушки
                        $img_index = ($row['id'] - 1) % count($random_images);
                        echo '<img src="' . $random_images[$img_index] . '" alt="' . $row['name'] . '">';
                    }
                    ?>
                </div>
                
                <div class="card-title">
                    <?php echo $row['name']; ?>
                </div>
                
                <div class="card-info">
                    <span class="card-label">Номер:</span>
                    <span class="card-value"><?php echo $row['id']; ?></span>
                </div>
                
                <div class="card-info">
                    <span class="card-label">Цена:</span>
                    <span class="card-value"><?php echo $row['cena']; ?></span>
                </div>
                
                <div class="card-info">
                    <span class="card-label">Кол-во:</span>
                    <span class="card-value"><?php echo $row['kol']; ?></span>
                </div>
                
                <div class="card-info">
                    <span class="card-label">Дата:</span>
                    <span class="card-value"><?php echo $row['srok']; ?></span>
                </div>
                
                <a href="?del=<?php echo $row['id']; ?>" 
                   class="delete-btn" 
                   onclick="return confirm('Удалить товар &quot;<?php echo $row['name']; ?>&quot;?')">
                     Удалить
                </a>
            </div>
            <?php
        }
        
        echo "</div>"; 
        
    } else {
        echo "<div class='empty-message'>";
        echo "😕 В базе данных нет товаров<br>";
        echo "<small style='font-size: 14px;'>Добавьте товары в таблицу 'tovars'</small>";
        echo "</div>";
    }
    ?>
</body>
</html>

<?php
mysqli_close($connect);
?>