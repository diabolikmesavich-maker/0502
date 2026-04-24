<?php
// Подключаемся к базе данных
$servername = "localhost";
$username = "bgcdarep";
$password = "i3h7Gj";
$dbname = "bgcdarep_m1";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Ошибка подключения: " . $conn->connect_error);
}

// Создаем папку для загрузки изображений, если её нет
$upload_dir = "uploads/";
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// Получаем ID товара из URL
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Обработка обновления товара с изображением
if (isset($_POST['update'])) {
    $name = $conn->real_escape_string($_POST['name']);
    $cena = floatval($_POST['cena']);
    $kol = intval($_POST['kol']);
    $srok = !empty($_POST['srok']) ? "'" . $conn->real_escape_string($_POST['srok']) . "'" : "NULL";
    $category_id = !empty($_POST['category_id']) ? intval($_POST['category_id']) : "NULL";
    
    // Обработка загруженного изображения
    $image_path = null;
    
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $max_size = 5 * 1024 * 1024; // 5 MB
        
        if (in_array($_FILES['image']['type'], $allowed_types) && $_FILES['image']['size'] <= $max_size) {
            // Генерируем уникальное имя файла
            $extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $filename = uniqid() . '_' . time() . '.' . $extension;
            $target_path = $upload_dir . $filename;
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $target_path)) {
                $image_path = $target_path;
            }
        }
    }
    
    // Формируем SQL запрос с учетом изображения
    if ($image_path) {
        // Если есть новое изображение, обновляем и его
        $sql = "UPDATE tovars SET 
                name='$name', 
                cena=$cena, 
                kol=$kol, 
                srok=$srok, 
                category_id=" . ($category_id === "NULL" ? "NULL" : $category_id) . ",
                image_path='$image_path'
                WHERE id=$id";
    } else {
        // Если нет нового изображения, обновляем только остальные поля
        $sql = "UPDATE tovars SET 
                name='$name', 
                cena=$cena, 
                kol=$kol, 
                srok=$srok, 
                category_id=" . ($category_id === "NULL" ? "NULL" : $category_id) . "
                WHERE id=$id";
    }
    
    if ($conn->query($sql) === TRUE) {
        $message = "Товар успешно обновлен";
    } else {
        $message = "Ошибка при обновлении: " . $conn->error;
    }
}

// Получаем данные товара
$sql = "SELECT t.*, c.name as category_name 
        FROM tovars t 
        LEFT JOIN categories c ON t.category_id = c.id 
        WHERE t.id = $id";
$result = $conn->query($sql);

if ($result->num_rows == 0) {
    die("Товар не найден");
}

$row = $result->fetch_assoc();

// Получаем список категорий
$categories_sql = "SELECT * FROM categories ORDER BY name";
$categories_result = $conn->query($categories_sql);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Редактирование товара</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background-color: #f5f5f5;
        }
        
        h1 {
            color: #333;
            text-align: center;
        }
        
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: white;
            padding: 30px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #555;
        }
        
        input[type="text"],
        input[type="number"],
        input[type="date"],
        input[type="file"],
        select,
        textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 3px;
            box-sizing: border-box;
            font-size: 14px;
        }
        
        .current-image {
            margin: 10px 0;
            padding: 10px;
            background-color: #f9f9f9;
            border-radius: 3px;
        }
        
        .current-image img {
            max-width: 200px;
            max-height: 200px;
            border-radius: 3px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        button {
            background-color: #4CAF50;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            font-size: 16px;
            width: 100%;
        }
        
        button:hover {
            background-color: #45a049;
        }
        
        .message {
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 4px;
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #4CAF50;
            text-decoration: none;
        }
        
        .back-link:hover {
            text-decoration: underline;
        }
        
        .image-preview {
            margin-top: 10px;
            display: none;
        }
        
        .image-preview img {
            max-width: 200px;
            max-height: 200px;
            border-radius: 3px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .file-info {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="deda.php" class="back-link">← Вернуться к списку товаров</a>
        <h1>Редактирование товара</h1>
        
        <?php if (isset($message)): ?>
            <div class="message"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <form method="post" enctype="multipart/form-data">
            <div class="form-group">
                <label>Название товара:</label>
                <input type="text" name="name" value="<?php echo htmlspecialchars($row['name']); ?>" required>
            </div>
            
            <div class="form-group">
                <label>Цена (руб.):</label>
                <input type="number" name="cena" step="0.01" value="<?php echo $row['cena']; ?>" required>
            </div>
            
            <div class="form-group">
                <label>Количество:</label>
                <input type="number" name="kol" value="<?php echo $row['kol']; ?>" required>
            </div>
            
            <div class="form-group">
                <label>Срок годности:</label>
                <input type="date" name="srok" value="<?php echo $row['srok']; ?>">
            </div>
            
            <div class="form-group">
                <label>Категория:</label>
                <select name="category_id">
                    <option value="">-- Без категории --</option>
                    <?php
                    $categories_result->data_seek(0);
                    while($cat = $categories_result->fetch_assoc()):
                        $selected = ($row['category_id'] == $cat['id']) ? 'selected' : '';
                    ?>
                        <option value="<?php echo $cat['id']; ?>" <?php echo $selected; ?>>
                            <?php echo htmlspecialchars($cat['name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label>Изображение товара:</label>
                
                <?php if (!empty($row['image_path'])): ?>
                <div class="current-image">
                    <p>Текущее изображение:</p>
                    <img src="<?php echo $row['image_path']; ?>" alt="Текущее изображение">
                </div>
                <?php endif; ?>
                
                <input type="file" name="image" accept="image/*" id="imageInput">
                <div class="file-info">Разрешены: JPG, PNG, GIF, WEBP. Максимальный размер: 5 MB</div>
                
                <div class="image-preview" id="imagePreview">
                    <p>Новое изображение:</p>
                    <img src="" alt="Превью">
                </div>
            </div>
            
            <button type="submit" name="update">Сохранить изменения</button>
        </form>
    </div>
    
    <script>
    // Превью загружаемого изображения
    document.getElementById('imageInput').addEventListener('change', function(e) {
        const preview = document.getElementById('imagePreview');
        const previewImg = preview.querySelector('img');
        
        if (this.files && this.files[0]) {
            const reader = new FileReader();
            
            reader.onload = function(e) {
                previewImg.src = e.target.result;
                preview.style.display = 'block';
            }
            
            reader.readAsDataURL(this.files[0]);
        } else {
            preview.style.display = 'none';
            previewImg.src = '';
        }
    });
    </script>
</body>
</html>

<?php
$conn->close();
?>